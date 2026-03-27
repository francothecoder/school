<?php
declare(strict_types=1);

namespace Controllers;

class AttendanceController extends BaseController
{
    public function index(): void
    {
        require_auth(['admin', 'teacher']);
        $year = current_year();
        $date = (string) request('date', date('Y-m-d'));
        $classId = (int) request('class_id');
        $classes = db()->fetchAll("SELECT * FROM class ORDER BY name_numeric + 0, name");

        $students = [];
        if ($classId) {
            $timestamp = strtotime($date . ' 08:00:00');
            $params = ['class_id' => $classId, 'year' => $year, 'timestamp' => (string) $timestamp];
            $sql = "SELECT st.student_id, st.name, st.student_code, e.roll, a.attendance_id, a.status
                FROM enroll e
                INNER JOIN student st ON st.student_id = e.student_id
                LEFT JOIN attendance a ON a.student_id = st.student_id AND a.class_id = e.class_id AND a.year = :year AND a.timestamp = :timestamp
                WHERE e.class_id = :class_id AND e.year = :year";
            if (current_user()['role'] === 'teacher') {
                $sql .= " AND EXISTS (SELECT 1 FROM subject sj WHERE sj.class_id = e.class_id AND sj.teacher_id = :teacher_id)";
                $params['teacher_id'] = current_user()['id'];
            }
            $sql .= " ORDER BY st.name";
            $students = db()->fetchAll($sql, $params);
        }

        $title = 'Attendance';
        $this->render('attendance/index', compact('title', 'year', 'date', 'classId', 'classes', 'students'));
    }

    public function save(): void
    {
        require_auth(['admin', 'teacher']);
        $year = (string) request('year', current_year());
        $classId = (int) request('class_id');
        $date = (string) request('date', date('Y-m-d'));
        $timestamp = (string) strtotime($date . ' 08:00:00');
        $items = $_POST['attendance'] ?? [];

        foreach ($items as $studentId => $status) {
            $existing = db()->fetch("SELECT attendance_id FROM attendance WHERE student_id = :student_id AND class_id = :class_id AND year = :year AND timestamp = :timestamp LIMIT 1", [
                'student_id' => $studentId,
                'class_id' => $classId,
                'year' => $year,
                'timestamp' => $timestamp,
            ]);
            if ($existing) {
                db()->execute("UPDATE attendance SET status = :status WHERE attendance_id = :id", [
                    'status' => (int) $status,
                    'id' => $existing['attendance_id'],
                ]);
            } else {
                db()->execute("INSERT INTO attendance (timestamp, year, class_id, section_id, student_id, class_routine_id, status)
                    VALUES (:timestamp, :year, :class_id, NULL, :student_id, NULL, :status)", [
                    'timestamp' => $timestamp,
                    'year' => $year,
                    'class_id' => $classId,
                    'student_id' => $studentId,
                    'status' => (int) $status,
                ]);
            }
        }

        flash('success', 'Attendance saved successfully.');
        redirect('/attendance?year=' . urlencode($year) . '&class_id=' . $classId . '&date=' . urlencode($date));
    }
}
