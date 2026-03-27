<?php
declare(strict_types=1);

namespace Controllers;

use Services\ResultService;

class ReportController extends BaseController
{
    private ResultService $results;

    public function __construct()
    {
        $this->results = new ResultService();
    }

    public function student(): void
    {
        require_auth();
        $id = (int) request('id');
        if (current_user()['role'] === 'student') {
            $id = current_user()['id'];
        }
        $query = [
            'id' => $id,
            'year' => (string) request('year', current_year()),
            'exam_id' => (int) request('exam_id'),
            'term' => (string) request('term', ''),
        ];
        redirect('/reports/result-slip?' . http_build_query(array_filter($query, fn($value) => $value !== '' && $value !== 0)));
    }

    public function resultSlip(): void
    {
        require_auth();
        $studentId = (int) request('id');
        if (current_user()['role'] === 'student') {
            $studentId = current_user()['id'];
        }
        $year = (string) request('year', current_year());
        $reportMode = (string) request('report_mode', request('term') ? 'term' : 'exam');
        $term = $reportMode === 'term' ? trim((string) request('term', '')) : '';
        if ($reportMode === 'term' && $term === '') {
            $termRow = db()->fetch("SELECT exam_term FROM exam WHERE year = :year AND exam_term IS NOT NULL AND exam_term <> '' ORDER BY exam_id DESC LIMIT 1", ['year' => $year]);
            $term = trim((string) ($termRow['exam_term'] ?? ''));
        }
        $examId = $reportMode === 'term' ? 0 : (int) request('exam_id');
        $payload = $this->results->buildStudentPayload($studentId, $year, $examId, $term);
        $title = 'Result Slip';
        $this->render('reports/result-slip', compact('title') + $payload + ['publicMode' => false]);
    }

    public function quick(): void
    {
        $studentCode = trim((string) request('student_code'));
        $year = (string) request('year', current_year());
        $reportMode = (string) request('report_mode', request('term') ? 'term' : 'exam');
        $term = $reportMode === 'term' ? trim((string) request('term', '')) : '';
        if ($reportMode === 'term' && $term === '') {
            $termRow = db()->fetch("SELECT exam_term FROM exam WHERE year = :year AND exam_term IS NOT NULL AND exam_term <> '' ORDER BY exam_id DESC LIMIT 1", ['year' => $year]);
            $term = trim((string) ($termRow['exam_term'] ?? ''));
        }
        $examId = $reportMode === 'term' ? 0 : (int) request('exam_id');
        $student = null;
        $payload = [
            'student' => null,
            'enroll' => null,
            'exam' => null,
            'exams' => db()->fetchAll("SELECT exam_id, name, year, exam_term FROM exam WHERE year = :year ORDER BY exam_id DESC", ['year' => $year]),
            'year' => $year,
            'rows' => [],
            'average' => null,
            'total' => 0,
            'count' => 0,
            'stage' => '-',
            'position' => null,
            'rankings' => [],
            'mode' => $reportMode === 'term' ? 'term' : 'exam',
            'term' => $term,
            'terms' => array_values(array_filter(array_map(fn($row) => trim((string) ($row['exam_term'] ?? '')), db()->fetchAll("SELECT DISTINCT exam_term FROM exam WHERE year = :year ORDER BY exam_term", ['year' => $year])))),
            'reportLabel' => '',
            'examHeaders' => [],
            'bestSix' => [],
            'bestSixMetricLabel' => null,
            'bestSixMetricValue' => null,
            'remarks' => '',
        ];
        if ($studentCode !== '') {
            $student = db()->fetch("SELECT * FROM student WHERE student_code = :code OR email = :code OR phone = :code LIMIT 1", ['code' => $studentCode]);
            if ($student) {
                $payload = $this->results->buildStudentPayload((int) $student['student_id'], $year, $examId, $term);
            } else {
                flash('error', 'No student was found with that student code, email, or phone number.');
            }
        }
        $title = 'Quick Result Check';
        $announcements = latest_announcements(3);
        $this->render('reports/quick', compact('title', 'studentCode', 'announcements') + $payload + ['publicMode' => true]);
    }

    public function classSheet(): void
    {
        require_auth(['admin', 'teacher']);
        $year = (string) request('year', current_year());
        $classId = (int) request('class_id');
        $examId = (int) request('exam_id');
        $teacherId = current_user()['role'] === 'teacher' ? (int) current_user()['id'] : null;

        $classes = current_user()['role'] === 'teacher'
            ? db()->fetchAll("SELECT DISTINCT c.* FROM class c INNER JOIN subject s ON s.class_id = c.class_id WHERE s.teacher_id = :teacher_id AND s.year = :year ORDER BY c.name_numeric + 0, c.name", ['teacher_id' => $teacherId, 'year' => $year])
            : db()->fetchAll("SELECT * FROM class ORDER BY name_numeric + 0, name");
        $exams = db()->fetchAll("SELECT * FROM exam WHERE year = :year ORDER BY exam_id DESC", ['year' => $year]);

        $class = null;
        $exam = null;
        $subjects = [];
        $students = [];
        $grid = [];

        if ($classId && $examId) {
            $class = db()->fetch("SELECT * FROM class WHERE class_id = :id", ['id' => $classId]);
            $exam = db()->fetch("SELECT * FROM exam WHERE exam_id = :id", ['id' => $examId]);
            $subjects = subjects_by_class($classId, $year, $teacherId);
            $students = db()->fetchAll("SELECT st.student_id, st.student_code, st.name
                FROM enroll e
                INNER JOIN student st ON st.student_id = e.student_id
                WHERE e.class_id = :class_id AND e.year = :year
                ORDER BY st.name", ['class_id' => $classId, 'year' => $year]);

            $marks = db()->fetchAll("SELECT student_id, subject_id, mark_obtained, mark_total
                FROM mark
                WHERE class_id = :class_id AND exam_id = :exam_id AND year = :year", ['class_id' => $classId, 'exam_id' => $examId, 'year' => $year]);
            foreach ($marks as $mark) {
                $grid[(int) $mark['student_id']][(int) $mark['subject_id']] = $mark;
            }

            if (request('download') === 'csv') {
                header('Content-Type: text/csv; charset=UTF-8');
                header('Content-Disposition: attachment; filename="class-sheet-' . preg_replace('/[^A-Za-z0-9\-_]+/', '-', (string) ($class['name'] ?? 'class')) . '-' . preg_replace('/[^A-Za-z0-9\-_]+/', '-', (string) ($exam['name'] ?? 'exam')) . '.csv"');
                $out = fopen('php://output', 'w');
                fputcsv($out, array_merge(['Student Code', 'Student Name'], array_map(fn($s) => $s['name'], $subjects), ['Total', 'Average']));
                foreach ($students as $studentRow) {
                    $line = [
                        $studentRow['student_code'] ?? '',
                        $studentRow['name'] ?? '',
                    ];
                    $total = 0; $count = 0;
                    foreach ($subjects as $subjectRow) {
                        $score = $grid[(int) $studentRow['student_id']][(int) $subjectRow['subject_id']]['mark_obtained'] ?? '';
                        $line[] = $score;
                        if ($score !== '' && $score !== null) { $total += (int) $score; $count++; }
                    }
                    $line[] = $total;
                    $line[] = $count ? round($total / $count, 2) : '';
                    fputcsv($out, $line);
                }
                fclose($out);
                exit;
            }
        }

        $title = 'Class Exam Sheet';
        $this->render('reports/class-sheet', compact('title', 'year', 'classId', 'examId', 'classes', 'exams', 'class', 'exam', 'subjects', 'students', 'grid'));
    }
}
