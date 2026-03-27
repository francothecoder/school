<?php
declare(strict_types=1);

namespace Controllers;

use Core\Auth;

class DashboardController extends BaseController
{
    public function loginRedirect(): void
    {
        if (auth_check()) {
            redirect('/dashboard');
        }
        redirect('/login');
    }

    public function login(): void
    {
        if (auth_check()) {
            redirect('/dashboard');
        }
        $announcements = latest_announcements(3);
        $quickExams = db()->fetchAll("SELECT exam_id, name, year FROM exam ORDER BY exam_id DESC LIMIT 12");
        $this->render('auth/login', ['title' => 'Login', 'announcements' => $announcements, 'quickExams' => $quickExams]);
    }

    public function loginSubmit(): void
    {
        $login = trim((string) request('login'));
        $password = (string) request('password');
        $portal = (string) request('portal', 'admin');

        if (Auth::attempt($login, $password, $portal)) {
            flash('success', 'Welcome back.');
            redirect('/dashboard');
        }

        flash('error', 'Invalid credentials for the selected portal.');
        redirect('/login');
    }

    public function logout(): void
    {
        Auth::logout();
        flash('success', 'You have been signed out.');
        redirect('/login');
    }

    public function index(): void
    {
        require_auth();
        $year = current_year();
        $user = current_user();
        $isTeacher = $user['role'] === 'teacher';
        $isStudent = $user['role'] === 'student';
        $announcements = latest_announcements(5);

        if ($isStudent) {
            $student = db()->fetch("SELECT * FROM student WHERE student_id = :id", ['id' => $user['id']]);
            $enrollment = db()->fetch("SELECT e.*, c.name AS class_name, s.name AS section_name
                FROM enroll e
                LEFT JOIN class c ON c.class_id = e.class_id
                LEFT JOIN section s ON s.section_id = e.section_id
                WHERE e.student_id = :student_id
                ORDER BY e.enroll_id DESC LIMIT 1", ['student_id' => $user['id']]);
            $recentMarks = db()->fetchAll("SELECT m.*, sub.name AS subject_name, ex.name AS exam_name
                FROM mark m
                LEFT JOIN subject sub ON sub.subject_id = m.subject_id
                LEFT JOIN exam ex ON ex.exam_id = m.exam_id
                WHERE m.student_id = :student_id
                ORDER BY m.mark_id DESC LIMIT 12", ['student_id' => $user['id']]);
            $title = 'Student Dashboard';
            $this->render('dashboard/student', compact('title', 'student', 'enrollment', 'recentMarks', 'year', 'announcements'));
            return;
        }

        if ($isTeacher) {
            $teacherId = $user['id'];
            $assignedSubjects = db()->fetchAll("SELECT s.*, c.name AS class_name
                FROM subject s
                LEFT JOIN class c ON c.class_id = s.class_id
                WHERE s.teacher_id = :teacher_id
                ORDER BY c.name_numeric + 0, c.name, s.name", ['teacher_id' => $teacherId]);
            $classIds = array_values(array_unique(array_filter(array_map(fn($r) => $r['class_id'], $assignedSubjects))));
            $studentCount = 0;
            if ($classIds) {
                $in = implode(',', array_fill(0, count($classIds), '?'));
                $row = db()->fetch("SELECT COUNT(*) AS total FROM enroll WHERE year = ? AND class_id IN ($in)", array_merge([$year], $classIds));
                $studentCount = (int) ($row['total'] ?? 0);
            }
            $title = 'Teacher Dashboard';
            $this->render('dashboard/teacher', compact('title', 'year', 'assignedSubjects', 'studentCount', 'announcements'));
            return;
        }

        $stats = [
            'students' => (int) (db()->fetch("SELECT COUNT(*) AS total FROM student")['total'] ?? 0),
            'teachers' => (int) (db()->fetch("SELECT COUNT(*) AS total FROM teacher")['total'] ?? 0),
            'classes'  => (int) (db()->fetch("SELECT COUNT(*) AS total FROM class")['total'] ?? 0),
            'subjects' => (int) (db()->fetch("SELECT COUNT(*) AS total FROM subject WHERE year = :year", ['year' => $year])['total'] ?? 0),
            'enrollments' => (int) (db()->fetch("SELECT COUNT(*) AS total FROM enroll WHERE year = :year", ['year' => $year])['total'] ?? 0),
            'exams' => (int) (db()->fetch("SELECT COUNT(*) AS total FROM exam WHERE year = :year", ['year' => $year])['total'] ?? 0),
        ];

        $recentEnrollments = db()->fetchAll("SELECT e.*, st.name AS student_name, st.student_code, c.name AS class_name, sec.name AS section_name
            FROM enroll e
            LEFT JOIN student st ON st.student_id = e.student_id
            LEFT JOIN class c ON c.class_id = e.class_id
            LEFT JOIN section sec ON sec.section_id = e.section_id
            WHERE e.year = :year
            ORDER BY e.enroll_id DESC
            LIMIT 12", ['year' => $year]);

        $topClasses = db()->fetchAll("SELECT c.name, COUNT(e.enroll_id) AS total_students
            FROM class c
            LEFT JOIN enroll e ON e.class_id = c.class_id AND e.year = :year
            GROUP BY c.class_id, c.name
            ORDER BY total_students DESC, c.name
            LIMIT 8", ['year' => $year]);

        $title = 'Dashboard';
        $this->render('dashboard/index', compact('title', 'stats', 'recentEnrollments', 'topClasses', 'year', 'announcements'));
    }
}
