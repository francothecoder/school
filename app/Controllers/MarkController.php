<?php
declare(strict_types=1);

namespace Controllers;

class MarkController extends BaseController
{
    public function index(): void
    {
        require_auth(['admin', 'teacher']);
        $year = current_year();
        $teacherId = current_user()['role'] === 'teacher' ? (int) current_user()['id'] : null;
        $filters = [
            'exam_id' => (int) request('exam_id'),
            'class_id' => (int) request('class_id'),
            'subject_id' => (int) request('subject_id'),
        ];

        $classes = current_user()['role'] === 'teacher'
            ? db()->fetchAll("SELECT DISTINCT c.* FROM class c INNER JOIN subject s ON s.class_id = c.class_id WHERE s.teacher_id = :teacher_id AND s.year = :year ORDER BY c.name_numeric + 0, c.name", ['teacher_id' => $teacherId, 'year' => $year])
            : db()->fetchAll("SELECT * FROM class ORDER BY name_numeric + 0, name");

        $exams = db()->fetchAll("SELECT * FROM exam WHERE year = :year ORDER BY exam_id DESC", ['year' => $year]);
        $subjects = $filters['class_id'] ? subjects_by_class($filters['class_id'], $year, $teacherId) : [];
        $sections = $filters['class_id'] ? sections_by_class($filters['class_id']) : [];

        $students = [];
        $subject = null;
        $exam = null;
        if ($filters['class_id'] && $filters['subject_id'] && $filters['exam_id']) {
            if ($teacherId && !teacher_can_manage_subject($teacherId, $filters['subject_id'], $filters['class_id'], $year)) {
                flash('error', 'You can only enter marks for subjects assigned to you.');
                redirect('/marks?class_id=' . $filters['class_id'] . '&year=' . urlencode($year));
            }

            $subject = db()->fetch("SELECT s.*, c.name_numeric FROM subject s LEFT JOIN class c ON c.class_id = s.class_id WHERE s.subject_id = :id", ['id' => $filters['subject_id']]);
            $exam = db()->fetch("SELECT * FROM exam WHERE exam_id = :id", ['id' => $filters['exam_id']]);
            $students = db()->fetchAll("SELECT st.student_id, st.name, st.student_code, e.roll, e.section_id,
                m.mark_id, m.mark_obtained, m.mark_total, m.comment
                FROM enroll e
                INNER JOIN student st ON st.student_id = e.student_id
                LEFT JOIN mark m ON m.student_id = st.student_id AND m.subject_id = :subject_id AND m.exam_id = :exam_id AND m.year = :year
                WHERE e.class_id = :class_id AND e.year = :year
                ORDER BY COALESCE(e.roll, 999999), st.name", [
                    'year' => $year,
                    'class_id' => $filters['class_id'],
                    'subject_id' => $filters['subject_id'],
                    'exam_id' => $filters['exam_id'],
                ]);
        }

        $title = 'Marks Entry';
        $this->render('marks/index', compact('title', 'year', 'classes', 'exams', 'subjects', 'sections', 'students', 'filters', 'subject', 'exam'));
    }

    public function save(): void
    {
        require_auth(['admin', 'teacher']);
        ensure_support_tables();
        $year = (string) request('year', current_year());
        $examId = (int) request('exam_id');
        $classId = (int) request('class_id');
        $subjectId = (int) request('subject_id');
        $marks = $_POST['marks'] ?? [];
        $teacherId = current_user()['role'] === 'teacher' ? (int) current_user()['id'] : null;

        if ($teacherId && !teacher_can_manage_subject($teacherId, $subjectId, $classId, $year)) {
            flash('error', 'You can only enter marks for your assigned subjects.');
            redirect('/marks?year=' . urlencode($year) . '&class_id=' . $classId);
        }

        $savedCount = 0;
        foreach ($marks as $studentId => $payload) {
            $studentId = (int) $studentId;
            $enroll = db()->fetch("SELECT section_id FROM enroll WHERE student_id = :student_id AND class_id = :class_id AND year = :year ORDER BY enroll_id DESC LIMIT 1", [
                'student_id' => $studentId,
                'class_id' => $classId,
                'year' => $year,
            ]);
            $sectionId = $enroll['section_id'] ?? null;
            $markValue = ($payload['mark_obtained'] ?? '') === '' ? null : max(0, (int) $payload['mark_obtained']);
            $markTotal = ($payload['mark_total'] ?? '') === '' ? 100 : max(1, (int) $payload['mark_total']);
            $comment = trim((string) ($payload['comment'] ?? ''));

            $existing = db()->fetch("SELECT * FROM mark WHERE student_id = :student_id AND subject_id = :subject_id AND exam_id = :exam_id AND year = :year LIMIT 1", [
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'exam_id' => $examId,
                'year' => $year,
            ]);

            $params = [
                'class_id' => $classId,
                'section_id' => $sectionId,
                'mark_obtained' => $markValue,
                'mark_total' => $markTotal,
                'comment' => $comment ?: null,
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'exam_id' => $examId,
                'year' => $year,
            ];

            if ($existing) {
                db()->execute("UPDATE mark SET class_id = :class_id, section_id = :section_id, mark_obtained = :mark_obtained, mark_total = :mark_total, comment = :comment, updated_by_role = :updated_by_role, updated_by_id = :updated_by_id, updated_at = NOW() WHERE mark_id = :mark_id", [
                    'class_id' => $classId,
                    'section_id' => $sectionId,
                    'mark_obtained' => $markValue,
                    'mark_total' => $markTotal,
                    'comment' => $comment ?: null,
                    'updated_by_role' => auth_role(),
                    'updated_by_id' => auth_id(),
                    'mark_id' => $existing['mark_id'],
                ]);
                $updated = db()->fetch("SELECT * FROM mark WHERE mark_id = :id LIMIT 1", ['id' => $existing['mark_id']]);
                log_activity([
                    'action' => 'update',
                    'module_name' => 'marks',
                    'record_id' => (int) $existing['mark_id'],
                    'description' => 'Updated mark entry for student #' . $studentId . ' in subject #' . $subjectId . ', exam #' . $examId,
                    'old_values' => json_encode($existing),
                    'new_values' => json_encode($updated ?: $params),
                ]);
            } else {
                db()->execute("INSERT INTO mark (student_id, subject_id, class_id, section_id, exam_id, mark_obtained, mark_total, comment, year, created_by_role, created_by_id, created_at, updated_by_role, updated_by_id, updated_at)
                    VALUES (:student_id, :subject_id, :class_id, :section_id, :exam_id, :mark_obtained, :mark_total, :comment, :year, :created_by_role, :created_by_id, NOW(), :updated_by_role, :updated_by_id, NOW())", $params + [
                    'created_by_role' => auth_role(),
                    'created_by_id' => auth_id(),
                    'updated_by_role' => auth_role(),
                    'updated_by_id' => auth_id(),
                ]);
                $markId = (int) db()->lastInsertId();
                log_activity([
                    'action' => 'create',
                    'module_name' => 'marks',
                    'record_id' => $markId,
                    'description' => 'Entered mark for student #' . $studentId . ' in subject #' . $subjectId . ', exam #' . $examId,
                    'new_values' => json_encode($params),
                ]);
            }
            $savedCount++;
        }

        flash('success', $savedCount . ' mark record(s) saved successfully.');
        redirect('/marks?year=' . urlencode($year) . '&class_id=' . $classId . '&subject_id=' . $subjectId . '&exam_id=' . $examId);
    }

    public function classSubjectsApi(): void
    {
        require_auth(['admin', 'teacher']);
        $classId = (int) request('class_id');
        $year = (string) request('year', current_year());
        $teacherId = current_user()['role'] === 'teacher' ? (int) current_user()['id'] : null;
        $subjects = $classId > 0 ? subjects_by_class($classId, $year, $teacherId) : [];
        json_response(['subjects' => $subjects]);
    }

    public function analytics(): void
    {
        require_auth(['admin', 'teacher']);
        $year = current_year();
        $classId = (int) request('class_id');
        $examId = (int) request('exam_id');
        $subjectId = (int) request('subject_id');
        $teacherId = current_user()['role'] === 'teacher' ? (int) current_user()['id'] : null;
        $passMark = passing_mark();

        $classes = current_user()['role'] === 'teacher'
            ? db()->fetchAll("SELECT DISTINCT c.* FROM class c INNER JOIN subject s ON s.class_id = c.class_id WHERE s.teacher_id = :teacher_id AND s.year = :year ORDER BY c.name_numeric + 0, c.name", ['teacher_id' => $teacherId, 'year' => $year])
            : db()->fetchAll("SELECT * FROM class ORDER BY name_numeric + 0, name");
        $exams = db()->fetchAll("SELECT * FROM exam WHERE year = :year ORDER BY exam_id DESC", ['year' => $year]);
        $subjects = $classId ? subjects_by_class($classId, $year, $teacherId) : [];

        $scopeWhere = "m.year = :year";
        $scopeParams = ['year' => $year];
        if ($classId) {
            $scopeWhere .= " AND m.class_id = :class_id";
            $scopeParams['class_id'] = $classId;
        }
        if ($examId) {
            $scopeWhere .= " AND m.exam_id = :exam_id";
            $scopeParams['exam_id'] = $examId;
        }
        if ($subjectId) {
            if ($teacherId && $classId && !teacher_can_manage_subject($teacherId, $subjectId, $classId, $year)) {
                flash('error', 'You can only view analytics for your assigned subjects.');
                redirect('/analytics?class_id=' . $classId . '&exam_id=' . $examId . '&year=' . urlencode($year));
            }
            $scopeWhere .= " AND m.subject_id = :subject_id";
            $scopeParams['subject_id'] = $subjectId;
        }

        $rankings = [];
        if ($classId && $examId) {
            $rankings = db()->fetchAll("SELECT e.student_id, st.student_code, st.name,
                    SUM(COALESCE(m.mark_obtained, 0)) AS total_marks,
                    AVG(COALESCE(m.mark_obtained, 0)) AS average_marks,
                    COUNT(m.mark_id) AS subjects_written
                FROM enroll e
                INNER JOIN student st ON st.student_id = e.student_id
                LEFT JOIN mark m ON m.student_id = e.student_id AND m.class_id = e.class_id AND m.exam_id = :exam_id AND m.year = :year
                WHERE e.class_id = :class_id AND e.year = :year
                GROUP BY e.student_id, st.student_code, st.name
                ORDER BY total_marks DESC, average_marks DESC, st.name", [
                    'exam_id' => $examId,
                    'year' => $year,
                    'class_id' => $classId,
                ]);
        }

        $subjectPerformance = [];
        if ($classId && $examId && $subjectId) {
            $subjectPerformance = db()->fetchAll("SELECT st.student_code, st.name, st.sex, m.mark_obtained, m.mark_total
                FROM mark m
                INNER JOIN student st ON st.student_id = m.student_id
                WHERE m.class_id = :class_id AND m.exam_id = :exam_id AND m.subject_id = :subject_id AND m.year = :year
                ORDER BY m.mark_obtained DESC, st.name", [
                    'class_id' => $classId,
                    'exam_id' => $examId,
                    'subject_id' => $subjectId,
                    'year' => $year,
                ]);
        }

        $overview = db()->fetch("SELECT
                COUNT(DISTINCT m.student_id) AS students_covered,
                COUNT(DISTINCT m.subject_id) AS subjects_covered,
                COUNT(DISTINCT m.class_id) AS classes_covered,
                ROUND(AVG(COALESCE(m.mark_obtained,0)),2) AS school_average,
                MAX(COALESCE(m.mark_obtained,0)) AS highest_mark
            FROM mark m
            WHERE {$scopeWhere}", $scopeParams) ?: [];

        $passFail = db()->fetch("SELECT
                SUM(CASE WHEN COALESCE(m.mark_obtained,0) >= :pass_mark THEN 1 ELSE 0 END) AS pass_count,
                SUM(CASE WHEN COALESCE(m.mark_obtained,0) < :pass_mark THEN 1 ELSE 0 END) AS fail_count,
                COUNT(*) AS total_count
            FROM mark m
            WHERE {$scopeWhere}", $scopeParams + ['pass_mark' => $passMark]) ?: [];

        $sexPerformance = db()->fetchAll("SELECT UPPER(COALESCE(NULLIF(TRIM(st.sex),''),'UNKNOWN')) AS sex,
                COUNT(m.mark_id) AS entries,
                ROUND(AVG(COALESCE(m.mark_obtained,0)),2) AS average_marks,
                SUM(CASE WHEN COALESCE(m.mark_obtained,0) >= :pass_mark THEN 1 ELSE 0 END) AS pass_count,
                SUM(CASE WHEN COALESCE(m.mark_obtained,0) < :pass_mark THEN 1 ELSE 0 END) AS fail_count
            FROM mark m
            INNER JOIN student st ON st.student_id = m.student_id
            WHERE {$scopeWhere}
            GROUP BY UPPER(COALESCE(NULLIF(TRIM(st.sex),''),'UNKNOWN'))
            ORDER BY entries DESC, sex", $scopeParams + ['pass_mark' => $passMark]);

        $bestStudents = db()->fetchAll("SELECT st.student_id, st.student_code, st.name, st.sex,
                ROUND(AVG(COALESCE(m.mark_obtained,0)),2) AS average_marks,
                SUM(COALESCE(m.mark_obtained,0)) AS total_marks,
                COUNT(m.mark_id) AS subjects_written
            FROM mark m
            INNER JOIN student st ON st.student_id = m.student_id
            WHERE {$scopeWhere}
            GROUP BY st.student_id, st.student_code, st.name, st.sex
            HAVING COUNT(m.mark_id) > 0
            ORDER BY average_marks DESC, total_marks DESC, st.name
            LIMIT 10", $scopeParams);

        $bestTeachers = db()->fetchAll("SELECT t.teacher_id, t.name,
                COUNT(m.mark_id) AS scripts_marked,
                ROUND(AVG(COALESCE(m.mark_obtained,0)),2) AS average_marks,
                SUM(CASE WHEN COALESCE(m.mark_obtained,0) >= :pass_mark THEN 1 ELSE 0 END) AS pass_count,
                SUM(CASE WHEN COALESCE(m.mark_obtained,0) < :pass_mark THEN 1 ELSE 0 END) AS fail_count
            FROM mark m
            INNER JOIN subject s ON s.subject_id = m.subject_id
            LEFT JOIN teacher t ON t.teacher_id = s.teacher_id
            WHERE {$scopeWhere}
            GROUP BY t.teacher_id, t.name
            HAVING COUNT(m.mark_id) > 0
            ORDER BY average_marks DESC, scripts_marked DESC, t.name
            LIMIT 10", $scopeParams + ['pass_mark' => $passMark]);

        $subjectAverages = db()->fetchAll("SELECT sub.subject_id, sub.name,
                ROUND(AVG(COALESCE(m.mark_obtained,0)),2) AS average_marks,
                COUNT(m.mark_id) AS entries,
                SUM(CASE WHEN COALESCE(m.mark_obtained,0) >= :pass_mark THEN 1 ELSE 0 END) AS pass_count,
                SUM(CASE WHEN COALESCE(m.mark_obtained,0) < :pass_mark THEN 1 ELSE 0 END) AS fail_count
            FROM mark m
            INNER JOIN subject sub ON sub.subject_id = m.subject_id
            WHERE {$scopeWhere}
            GROUP BY sub.subject_id, sub.name
            HAVING COUNT(m.mark_id) > 0
            ORDER BY average_marks DESC, sub.name", $scopeParams + ['pass_mark' => $passMark]);

        $classAverages = db()->fetchAll("SELECT c.class_id, c.name,
                ROUND(AVG(COALESCE(m.mark_obtained,0)),2) AS average_marks,
                COUNT(m.mark_id) AS entries,
                SUM(CASE WHEN COALESCE(m.mark_obtained,0) >= :pass_mark THEN 1 ELSE 0 END) AS pass_count,
                SUM(CASE WHEN COALESCE(m.mark_obtained,0) < :pass_mark THEN 1 ELSE 0 END) AS fail_count
            FROM mark m
            INNER JOIN class c ON c.class_id = m.class_id
            WHERE {$scopeWhere}
            GROUP BY c.class_id, c.name
            HAVING COUNT(m.mark_id) > 0
            ORDER BY average_marks DESC, c.name_numeric + 0, c.name", $scopeParams + ['pass_mark' => $passMark]);

        $gradeDistribution = [];
        foreach (grading_scale() as $band) {
            $params = $scopeParams;
            $params['band_from'] = (int) $band['from'];
            $params['band_to'] = (int) $band['to'];
            $countRow = db()->fetch("SELECT COUNT(*) AS aggregate_count FROM mark m WHERE {$scopeWhere} AND COALESCE(m.mark_obtained,0) BETWEEN :band_from AND :band_to", $params);
            $gradeDistribution[] = [
                'label' => (string) $band['label'],
                'point' => (int) $band['point'],
                'count' => (int) ($countRow['aggregate_count'] ?? 0),
            ];
        }

        $classTopSummary = [];
        foreach ($classAverages as $row) {
            $params = $scopeParams;
            $params['class_id_local'] = (int) $row['class_id'];
            $best = db()->fetch("SELECT st.name, ROUND(AVG(COALESCE(m.mark_obtained,0)),2) AS average_marks
                FROM mark m
                INNER JOIN student st ON st.student_id = m.student_id
                WHERE {$scopeWhere} AND m.class_id = :class_id_local
                GROUP BY st.student_id, st.name
                HAVING COUNT(m.mark_id) > 0
                ORDER BY average_marks DESC, st.name
                LIMIT 1", $params);
            $classTopSummary[] = [
                'class_name' => $row['name'],
                'average_marks' => $row['average_marks'],
                'top_student' => $best['name'] ?? '-',
                'top_average' => $best['average_marks'] ?? null,
                'pass_count' => $row['pass_count'] ?? 0,
                'fail_count' => $row['fail_count'] ?? 0,
            ];
        }

        $topSubjects = array_slice($subjectAverages, 0, 5);
        $weakSubjects = $subjectAverages;
        usort($weakSubjects, static function(array $a, array $b): int {
            $avgCompare = ((float) ($a['average_marks'] ?? 0)) <=> ((float) ($b['average_marks'] ?? 0));
            return $avgCompare !== 0 ? $avgCompare : strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });
        $weakSubjects = array_slice($weakSubjects, 0, 5);

        $teacherScorecards = [];
        foreach (array_slice($bestTeachers, 0, 6) as $row) {
            $pass = (int) ($row['pass_count'] ?? 0);
            $fail = (int) ($row['fail_count'] ?? 0);
            $total = $pass + $fail;
            $passRate = $total > 0 ? round(($pass / $total) * 100, 1) : 0.0;
            $teacherScorecards[] = [
                'name' => $row['name'] ?: 'Unassigned',
                'average_marks' => (float) ($row['average_marks'] ?? 0),
                'scripts_marked' => (int) ($row['scripts_marked'] ?? 0),
                'pass_rate' => $passRate,
                'pass_count' => $pass,
                'fail_count' => $fail,
            ];
        }

        $classComparisonSeries = [];
        foreach ($classAverages as $row) {
            $pass = (int) ($row['pass_count'] ?? 0);
            $fail = (int) ($row['fail_count'] ?? 0);
            $total = $pass + $fail;
            $classComparisonSeries[] = [
                'name' => $row['name'],
                'average_marks' => (float) ($row['average_marks'] ?? 0),
                'pass_rate' => $total > 0 ? round(($pass / $total) * 100, 1) : 0.0,
                'entries' => (int) ($row['entries'] ?? 0),
                'pass_count' => $pass,
                'fail_count' => $fail,
            ];
        }

        $title = 'Marks Analytics';
        $this->render('marks/analytics', compact(
            'title', 'year', 'classId', 'examId', 'subjectId', 'classes', 'exams', 'subjects', 'passMark',
            'rankings', 'subjectPerformance', 'overview', 'bestStudents', 'bestTeachers',
            'subjectAverages', 'classAverages', 'gradeDistribution', 'classTopSummary', 'sexPerformance', 'passFail',
            'topSubjects', 'weakSubjects', 'teacherScorecards', 'classComparisonSeries'
        ));
    }

}
