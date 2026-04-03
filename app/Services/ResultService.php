<?php
declare(strict_types=1);

namespace Services;

class ResultService
{
    public function buildStudentPayload(int $studentId, string $year, int $examId = 0, string $term = ''): array
    {
        $student = \db()->fetch("SELECT * FROM student WHERE student_id = :id", ['id' => $studentId]);
        $enroll = \db()->fetch("SELECT e.*, c.name AS class_name, c.name_numeric, s.name AS section_name
            FROM enroll e
            LEFT JOIN class c ON c.class_id = e.class_id
            LEFT JOIN section s ON s.section_id = e.section_id
            WHERE e.student_id = :student_id AND e.year = :year
            ORDER BY e.enroll_id DESC LIMIT 1", ['student_id' => $studentId, 'year' => $year]);

        if (!$enroll) {
            $enroll = \db()->fetch("SELECT e.*, c.name AS class_name, c.name_numeric, s.name AS section_name
                FROM enroll e
                LEFT JOIN class c ON c.class_id = e.class_id
                LEFT JOIN section s ON s.section_id = e.section_id
                WHERE e.student_id = :student_id
                ORDER BY e.enroll_id DESC LIMIT 1", ['student_id' => $studentId]);
        }

        $termsRaw = \db()->fetchAll("SELECT DISTINCT exam_term FROM exam WHERE year = :year AND exam_term IS NOT NULL AND exam_term <> '' ORDER BY exam_term", ['year' => $year]);
        $terms = [];
        foreach ($termsRaw as $row) {
            $normalizedTerm = $this->normalizeTermValue((string) ($row['exam_term'] ?? ''));
            if ($normalizedTerm !== '' && !in_array($normalizedTerm, $terms, true)) {
                $terms[] = $normalizedTerm;
            }
        }
        usort($terms, fn(string $a, string $b) => $this->termSortWeight($a) <=> $this->termSortWeight($b));
        $exams = \db()->fetchAll("SELECT exam_id, name, year, exam_term, date FROM exam WHERE year = :year ORDER BY exam_id DESC", ['year' => $year]);

        $normalizedRequestedTerm = $this->normalizeTermValue($term);
        $mode = $normalizedRequestedTerm !== '' ? 'term' : 'exam';
        $term = $normalizedRequestedTerm;
        $exam = null;
        $reportLabel = '';
        $examHeaders = [];
        $rows = [];
        $total = 0;
        $count = 0;
        $average = null;
        $bestSix = [];
        $bestSixMetricValue = null;
        $bestSixMetricLabel = null;
        $remarks = '';
        $rankings = [];
        $position = null;
        $stage = 'ACADEMIC';
        $className = (string) ($enroll['class_name'] ?? '');
        $classLevel = $this->classLevel((string) ($enroll['name_numeric'] ?? $className));
        $combineScience = $this->shouldCombineScience($className);

        if ($mode === 'term') {
            if ($term === '' && $terms) {
                $term = $terms[0];
            }
            $reportLabel = $term !== '' ? ('Term Summary · ' . $this->termDisplayLabel($term)) : 'Term Summary';
            [$rows, $examHeaders] = $this->buildTermRows($studentId, $year, $term, $combineScience, $classLevel);
        } else {
            if ($examId <= 0) {
                $latest = \db()->fetch("SELECT exam_id FROM exam WHERE year = :year ORDER BY exam_id DESC LIMIT 1", ['year' => $year]);
                $examId = (int) ($latest['exam_id'] ?? 0);
            }
            $exam = $examId > 0 ? \db()->fetch("SELECT * FROM exam WHERE exam_id = :id", ['id' => $examId]) : null;
            if ($exam && trim((string) ($exam['exam_term'] ?? '')) !== '' && $term === '') {
                $term = $this->normalizeTermValue((string) $exam['exam_term']);
            }
            $reportLabel = 'Exam Result · ' . ($exam['name'] ?? '-');
            $rows = $this->buildExamRows($studentId, $year, $examId, $classLevel);
        }

        foreach ($rows as $row) {
            if ($row['score'] !== null) {
                $total += (int) $row['score'];
                $count++;
            }
        }
        $average = $count > 0 ? round($total / $count, 2) : null;

        [$bestSix, $bestSixMetricLabel, $bestSixMetricValue, $remarks] = $this->buildBestSixSummary($rows, $classLevel);

        if (!empty($enroll['class_id'])) {
            $rankings = $this->buildRankings((int) $enroll['class_id'], $year, $mode, $examId, $term, $combineScience, $classLevel);
            foreach ($rankings as $index => $row) {
                if ((int) $row['student_id'] === $studentId) {
                    $position = $index + 1;
                    break;
                }
            }
        }

        return [
            'student' => $student,
            'enroll' => $enroll,
            'exam' => $exam,
            'exams' => $exams,
            'year' => $year,
            'rows' => $rows,
            'average' => $average,
            'total' => $total,
            'count' => $count,
            'stage' => $stage,
            'position' => $position,
            'rankings' => $rankings,
            'mode' => $mode,
            'term' => $term,
            'terms' => $terms,
            'reportLabel' => $reportLabel,
            'examHeaders' => $examHeaders,
            'bestSix' => $bestSix,
            'bestSixMetricLabel' => $bestSixMetricLabel,
            'bestSixMetricValue' => $bestSixMetricValue,
            'remarks' => $remarks,
            'termLabel' => $this->termDisplayLabel($term),
        ];
    }

    private function buildExamRows(int $studentId, string $year, int $examId, int $classLevel): array
    {
        if ($examId <= 0) {
            return [];
        }
        $marks = \db()->fetchAll("SELECT m.*, sub.name AS subject_name
            FROM mark m
            LEFT JOIN subject sub ON sub.subject_id = m.subject_id
            WHERE m.student_id = :student_id AND m.year = :year AND m.exam_id = :exam_id
            ORDER BY sub.name", [
                'student_id' => $studentId,
                'year' => $year,
                'exam_id' => $examId,
            ]);
        $rows = [];
        foreach ($marks as $mark) {
            $score = $mark['mark_obtained'] !== null ? (int) round($this->percentScore((int) $mark['mark_obtained'], (int) ($mark['mark_total'] ?? 100))) : null;
            $grade = \mark_grade($score, 'CUSTOM');
            $rows[] = [
                'subject_name' => $mark['subject_name'] ?? 'Unknown',
                'score' => $score,
                'grade_name' => $grade['name'],
                'grade_point' => $this->safeInt($grade['point']),
                'mark_total' => (int) ($mark['mark_total'] ?? 100),
                'comment' => $mark['comment'] ?? '',
                'marks' => [],
            ];
        }
        return $rows;
    }

    private function buildTermRows(int $studentId, string $year, string $term, bool $combineScience, int $classLevel): array
    {
        if ($term === '') {
            return [[], []];
        }

        $marks = \db()->fetchAll("SELECT m.*, sub.name AS subject_name, ex.name AS exam_name, ex.exam_term, ex.exam_id
            FROM mark m
            INNER JOIN exam ex ON ex.exam_id = m.exam_id
            LEFT JOIN subject sub ON sub.subject_id = m.subject_id
            WHERE m.student_id = :student_id AND m.year = :year
            ORDER BY sub.name, ex.exam_id", [
                'student_id' => $studentId,
                'year' => $year,
            ]);

        $examHeaders = [];
        $subjects = [];
        foreach ($marks as $mark) {
            if ($this->normalizeTermValue((string) ($mark['exam_term'] ?? '')) !== $term) {
                continue;
            }
            $subjectName = trim((string) ($mark['subject_name'] ?? 'Unknown')) ?: 'Unknown';
            if ($combineScience && in_array(strtoupper($subjectName), ['PHY', 'CHE'], true)) {
                $subjectName = 'SCI';
            }
            $examName = trim((string) ($mark['exam_name'] ?? 'Exam')) ?: 'Exam';
            if (!in_array($examName, $examHeaders, true)) {
                $examHeaders[] = $examName;
            }
            if (!isset($subjects[$subjectName])) {
                $subjects[$subjectName] = [
                    'subject_name' => $subjectName,
                    'marks' => [],
                    'total_obtained' => 0.0,
                    'total_possible' => 0.0,
                ];
            }
            $obtained = $mark['mark_obtained'] !== null ? (float) $mark['mark_obtained'] : null;
            $markTotal = max(1.0, (float) ($mark['mark_total'] ?? 100));
            if ($obtained !== null) {
                if (!isset($subjects[$subjectName]['marks'][$examName])) {
                    $subjects[$subjectName]['marks'][$examName] = ['obtained' => 0.0, 'total' => 0.0];
                }
                $subjects[$subjectName]['marks'][$examName]['obtained'] += $obtained;
                $subjects[$subjectName]['marks'][$examName]['total'] += $markTotal;
                $subjects[$subjectName]['total_obtained'] += $obtained;
                $subjects[$subjectName]['total_possible'] += $markTotal;
            }
        }

        usort($examHeaders, 'strnatcasecmp');
        $rows = [];
        ksort($subjects);
        foreach ($subjects as $subjectName => $payload) {
            $score = $payload['total_possible'] > 0 ? (int) round($this->percentScore($payload['total_obtained'], $payload['total_possible'])) : null;
            $grade = $this->gradeAndPointsForSummary($score, $classLevel);
            $marksPerExam = [];
            foreach ($examHeaders as $examName) {
                $values = $payload['marks'][$examName] ?? null;
                $marksPerExam[$examName] = $values && $values['total'] > 0
                    ? (int) round($this->percentScore((float) $values['obtained'], (float) $values['total']))
                    : null;
            }
            $rows[] = [
                'subject_name' => $subjectName,
                'score' => $score,
                'grade_name' => $grade['grade'],
                'grade_point' => $grade['points'],
                'mark_total' => 100,
                'comment' => '',
                'marks' => $marksPerExam,
            ];
        }

        return [$rows, $examHeaders];
    }

    private function buildBestSixSummary(array $rows, int $classLevel): array
    {
        $eligible = [];
        foreach ($rows as $row) {
            if ($row['score'] === null) {
                continue;
            }
            $eligible[] = [
                'subject_name' => (string) $row['subject_name'],
                'score' => (int) $row['score'],
                'grade_point' => $this->safeInt($row['grade_point']),
            ];
        }
        if (!$eligible) {
            return [[], null, null, ''];
        }

        $eng = null;
        foreach ($eligible as $item) {
            if ($this->isEnglishSubject((string) $item['subject_name'])) {
                $eng = $item;
                break;
            }
        }

        usort($eligible, function($a, $b) {
            if ($a['grade_point'] === $b['grade_point']) {
                if ($a['score'] === $b['score']) {
                    return strcasecmp((string) $a['subject_name'], (string) $b['subject_name']);
                }
                return $b['score'] <=> $a['score'];
            }
            return $a['grade_point'] <=> $b['grade_point'];
        });

        $selected = array_slice($eligible, 0, 6);
        if ($eng && !$this->containsSubject($selected, (string) $eng['subject_name'])) {
            $selected = array_slice($eligible, 0, 5);
            $selected[] = $eng;
            usort($selected, function($a, $b) {
                if ($a['grade_point'] === $b['grade_point']) {
                    if ($a['score'] === $b['score']) {
                        return strcasecmp((string) $a['subject_name'], (string) $b['subject_name']);
                    }
                    return $b['score'] <=> $a['score'];
                }
                return $a['grade_point'] <=> $b['grade_point'];
            });
            $selected = array_slice($selected, 0, 6);
        }

        $metricValue = array_sum(array_map(fn($item) => (int) $item['grade_point'], $selected));
        $remarks = $this->pointsBasedRemarks($metricValue);
        return [$selected, 'Best 6 Points', $metricValue, $remarks];
    }

    private function buildRankings(int $classId, string $year, string $mode, int $examId, string $term, bool $combineScience, int $classLevel): array
    {
        $students = \db()->fetchAll("SELECT e.student_id, st.name
            FROM enroll e
            INNER JOIN student st ON st.student_id = e.student_id
            WHERE e.class_id = :class_id AND e.year = :year
            ORDER BY st.name", ['class_id' => $classId, 'year' => $year]);
        if (!$students) {
            return [];
        }

        if ($mode === 'term') {
            $allMarks = \db()->fetchAll("SELECT m.student_id, m.mark_obtained, m.mark_total, sub.name AS subject_name, ex.exam_term
                FROM mark m
                INNER JOIN exam ex ON ex.exam_id = m.exam_id
                LEFT JOIN subject sub ON sub.subject_id = m.subject_id
                WHERE m.class_id = :class_id AND m.year = :year", [
                'class_id' => $classId,
                'year' => $year,
            ]);
            $stats = [];
            foreach ($allMarks as $mark) {
                if ($this->normalizeTermValue((string) ($mark['exam_term'] ?? '')) !== $term) {
                    continue;
                }
                $studentId = (int) $mark['student_id'];
                $subjectName = trim((string) ($mark['subject_name'] ?? 'Unknown')) ?: 'Unknown';
                if ($combineScience && in_array(strtoupper($subjectName), ['PHY', 'CHE'], true)) {
                    $subjectName = 'SCI';
                }
                $score = $mark['mark_obtained'] !== null ? (float) $mark['mark_obtained'] : null;
                if ($score === null) {
                    continue;
                }
                $stats[$studentId][$subjectName]['obtained'] = ($stats[$studentId][$subjectName]['obtained'] ?? 0.0) + $score;
                $stats[$studentId][$subjectName]['total'] = ($stats[$studentId][$subjectName]['total'] ?? 0.0) + max(1.0, (float) ($mark['mark_total'] ?? 100));
            }
            $rankings = [];
            foreach ($students as $student) {
                $studentId = (int) $student['student_id'];
                $subjectGroups = $stats[$studentId] ?? [];
                $subjectAverages = [];
                foreach ($subjectGroups as $subjectName => $totals) {
                    if (($totals['total'] ?? 0) > 0) {
                        $subjectAverages[] = (int) round($this->percentScore((float) $totals['obtained'], (float) $totals['total']));
                    }
                }
                $totalMarks = array_sum($subjectAverages);
                $averageMarks = $subjectAverages ? round($totalMarks / count($subjectAverages), 2) : 0;
                $rankings[] = [
                    'student_id' => $studentId,
                    'name' => $student['name'],
                    'total_marks' => $totalMarks,
                    'average_marks' => $averageMarks,
                ];
            }
        } else {
            $allMarks = \db()->fetchAll("SELECT m.student_id, COALESCE(m.mark_obtained,0) AS mark_obtained, COALESCE(m.mark_total,100) AS mark_total
                FROM mark m
                WHERE m.class_id = :class_id AND m.year = :year AND m.exam_id = :exam_id", [
                'class_id' => $classId,
                'year' => $year,
                'exam_id' => $examId,
            ]);
            $stats = [];
            $counts = [];
            foreach ($allMarks as $mark) {
                $studentId = (int) $mark['student_id'];
                $stats[$studentId] = ($stats[$studentId] ?? 0) + (int) round($this->percentScore((float) $mark['mark_obtained'], (float) $mark['mark_total']));
                $counts[$studentId] = ($counts[$studentId] ?? 0) + 1;
            }
            $rankings = [];
            foreach ($students as $student) {
                $studentId = (int) $student['student_id'];
                $totalMarks = (int) ($stats[$studentId] ?? 0);
                $averageMarks = !empty($counts[$studentId]) ? round($totalMarks / $counts[$studentId], 2) : 0;
                $rankings[] = [
                    'student_id' => $studentId,
                    'name' => $student['name'],
                    'total_marks' => $totalMarks,
                    'average_marks' => $averageMarks,
                ];
            }
        }

        usort($rankings, function($a, $b) {
            if ($a['total_marks'] === $b['total_marks']) {
                if ((float) $a['average_marks'] === (float) $b['average_marks']) {
                    return strcasecmp((string) $a['name'], (string) $b['name']);
                }
                return (float) $b['average_marks'] <=> (float) $a['average_marks'];
            }
            return (int) $b['total_marks'] <=> (int) $a['total_marks'];
        });
        return $rankings;
    }

    private function gradeAndPointsForSummary(?int $averageMarks, int $classLevel): array
    {
        if ($averageMarks === null) {
            return ['grade' => '-', 'points' => 0];
        }
        $grade = \grade_band_for_mark($averageMarks);
        return [
            'grade' => (string) ($grade['name'] ?? '-'),
            'points' => $this->safeInt($grade['point'] ?? 0),
        ];
    }

    private function classLevel(string $classNumericOrName): int
    {
        preg_match('/\d+/', $classNumericOrName, $matches);
        return isset($matches[0]) ? (int) $matches[0] : 0;
    }

    private function shouldCombineScience(string $className): bool
    {
        return in_array(strtoupper(trim($className)), ['10AB', '11AB', '12BC', '10DT', '11DT', '12DT'], true);
    }

    private function isEnglishSubject(string $subjectName): bool
    {
        $name = strtoupper(trim($subjectName));
        return in_array($name, ['ENG', 'ENGLISH', 'ENGLISH LANGUAGE'], true) || str_contains($name, 'ENGLISH');
    }

    private function containsSubject(array $items, string $subjectName): bool
    {
        foreach ($items as $item) {
            if (strcasecmp((string) ($item['subject_name'] ?? ''), $subjectName) === 0) {
                return true;
            }
        }
        return false;
    }

    private function safeInt($value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function pointsBasedRemarks(int $totalPoints): string
    {
        $scale = \grading_scale();
        $points = array_map(fn($band) => (int) ($band['point'] ?? 0), $scale);
        $points = array_values(array_filter($points, fn($point) => $point > 0));
        if (!$points) {
            return '';
        }

        $minPoint = min($points);
        $maxPoint = max($points);
        $bestSixCount = 6;
        $minTotal = $minPoint * $bestSixCount;
        $maxTotal = $maxPoint * $bestSixCount;
        if ($maxTotal <= $minTotal) {
            return 'Performance recorded.';
        }

        $normalized = 1 - (($totalPoints - $minTotal) / ($maxTotal - $minTotal));
        if ($normalized >= 0.85) return 'Excellent performance noted. Please keep motivating the learner.';
        if ($normalized >= 0.70) return 'Very good performance. Keep encouraging the learner to continue working hard.';
        if ($normalized >= 0.55) return 'Good performance noted. Continued effort will improve results further.';
        if ($normalized >= 0.40) return 'Average performance noted. The learner can do better with more consistency.';
        if ($normalized >= 0.25) return 'More effort is needed for the learner to improve academic performance.';
        return 'Performance is below expectation. Please encourage the learner to work much harder.';
    }

    private function normalizeTermValue(string $term): string
    {
        $term = trim($term);
        if ($term === '') {
            return '';
        }
        if (preg_match('/([123])/', strtoupper($term), $matches)) {
            return (string) $matches[1];
        }
        return strtoupper($term);
    }

    private function termDisplayLabel(string $term): string
    {
        $normalized = $this->normalizeTermValue($term);
        if (in_array($normalized, ['1', '2', '3'], true)) {
            return 'Term ' . $normalized;
        }
        return $normalized !== '' ? $normalized : '-';
    }

    private function termSortWeight(string $term): int
    {
        $normalized = $this->normalizeTermValue($term);
        if (ctype_digit($normalized)) {
            return (int) $normalized;
        }
        return 99;
    }

    private function percentScore(float $obtained, float $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }
        return max(0.0, min(100.0, ($obtained / $total) * 100));
    }
}
