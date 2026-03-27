<?php
declare(strict_types=1);

namespace Controllers;

class EnrollmentController extends BaseController
{
    public function index(): void
    {
        require_auth(['admin', 'teacher']);
        $year = current_year();
        $params = ['year' => $year];
        $sql = "SELECT e.*, st.name AS student_name, st.student_code, c.name AS class_name, sec.name AS section_name
            FROM enroll e
            LEFT JOIN student st ON st.student_id = e.student_id
            LEFT JOIN class c ON c.class_id = e.class_id
            LEFT JOIN section sec ON sec.section_id = e.section_id
            WHERE e.year = :year";
        if (current_user()['role'] === 'teacher') {
            $sql .= " AND EXISTS (SELECT 1 FROM subject sj WHERE sj.class_id = e.class_id AND sj.teacher_id = :teacher_id)";
            $params['teacher_id'] = current_user()['id'];
        }
        $sql .= " ORDER BY e.enroll_id DESC LIMIT 300";
        $enrollments = db()->fetchAll($sql, $params);
        $title = 'Enrollments';
        $this->render('enrollments/index', compact('title', 'enrollments', 'year'));
    }

    public function create(): void
    {
        require_auth(['admin']);
        $students = db()->fetchAll("SELECT student_id, name, student_code FROM student ORDER BY student_id DESC LIMIT 500");
        $classes = db()->fetchAll("SELECT * FROM class ORDER BY name_numeric + 0, name");
        $sections = db()->fetchAll("SELECT * FROM section ORDER BY class_id, name");
        $title = 'Enroll Student';
        $this->render('enrollments/create', compact('title', 'students', 'classes', 'sections'));
    }

    public function store(): void
    {
        require_auth(['admin']);
        db()->execute("INSERT INTO enroll (enroll_code, student_id, class_id, section_id, roll, date_added, year)
            VALUES (:enroll_code, :student_id, :class_id, :section_id, :roll, :date_added, :year)", [
            'enroll_code' => substr(md5((string) microtime(true)), 0, 7),
            'student_id' => request('student_id'),
            'class_id' => request('class_id'),
            'section_id' => request('section_id') ?: null,
            'roll' => request('roll') ?: null,
            'date_added' => (string) time(),
            'year' => request('year', current_year()),
        ]);
        flash('success', 'Enrollment saved successfully.');
        redirect('/enrollments');
    }
}
