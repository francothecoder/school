<?php
declare(strict_types=1);

namespace Controllers;

class ClassController extends BaseController
{
    public function index(): void
    {
        require_auth(['admin', 'teacher']);
        $classes = db()->fetchAll("SELECT c.*, t.name AS teacher_name,
            (SELECT COUNT(*) FROM section s WHERE s.class_id = c.class_id) AS sections_count,
            (SELECT COUNT(*) FROM subject sj WHERE sj.class_id = c.class_id AND sj.year = :year) AS subjects_count,
            (SELECT COUNT(*) FROM enroll e WHERE e.class_id = c.class_id AND e.year = :year) AS students_count
            FROM class c
            LEFT JOIN teacher t ON t.teacher_id = c.teacher_id
            ORDER BY c.name_numeric + 0, c.name", ['year' => current_year()]);
        $title = 'Classes';
        $this->render('classes/index', compact('title', 'classes'));
    }

    public function create(): void
    {
        require_auth(['admin']);
        $teachers = db()->fetchAll("SELECT teacher_id, name FROM teacher ORDER BY name");
        $title = 'Add Class';
        $this->render('classes/create', compact('title', 'teachers'));
    }

    public function store(): void
    {
        require_auth(['admin']);
        db()->execute("INSERT INTO class (name, name_numeric, teacher_id) VALUES (:name, :name_numeric, :teacher_id)", [
            'name' => trim((string) request('name')),
            'name_numeric' => trim((string) request('name_numeric')),
            'teacher_id' => request('teacher_id') ?: null,
        ]);
        $classId = (int) db()->lastInsertId();

        if (trim((string) request('section_name')) !== '') {
            db()->execute("INSERT INTO section (name, nick_name, class_id, teacher_id) VALUES (:name, :nick_name, :class_id, :teacher_id)", [
                'name' => request('section_name'),
                'nick_name' => request('section_nick_name', ''),
                'class_id' => $classId,
                'teacher_id' => request('teacher_id') ?: null,
            ]);
        }

        flash('success', 'Class created successfully.');
        redirect('/classes/show?id=' . $classId);
    }

    public function show(): void
    {
        require_auth(['admin', 'teacher']);
        $id = (int) request('id');
        $year = current_year();
        $class = db()->fetch("SELECT c.*, t.name AS teacher_name FROM class c LEFT JOIN teacher t ON t.teacher_id = c.teacher_id WHERE c.class_id = :id", ['id' => $id]);
        $sections = db()->fetchAll("SELECT s.*, t.name AS teacher_name
            FROM section s LEFT JOIN teacher t ON t.teacher_id = s.teacher_id
            WHERE s.class_id = :id ORDER BY s.name", ['id' => $id]);
        $subjects = db()->fetchAll("SELECT s.*, t.name AS teacher_name
            FROM subject s LEFT JOIN teacher t ON t.teacher_id = s.teacher_id
            WHERE s.class_id = :id AND s.year = :year ORDER BY s.name", ['id' => $id, 'year' => $year]);
        $students = db()->fetchAll("SELECT st.student_id, st.student_code, st.name, e.roll, sec.name AS section_name
            FROM enroll e
            INNER JOIN student st ON st.student_id = e.student_id
            LEFT JOIN section sec ON sec.section_id = e.section_id
            WHERE e.class_id = :id AND e.year = :year
            ORDER BY COALESCE(e.roll, 999999), st.name", ['id' => $id, 'year' => $year]);

        if (current_user()['role'] === 'teacher') {
            $allowed = db()->fetch("SELECT subject_id FROM subject WHERE class_id = :class_id AND teacher_id = :teacher_id AND year = :year LIMIT 1", [
                'class_id' => $id,
                'teacher_id' => current_user()['id'],
                'year' => $year,
            ]);
            if (!$allowed) {
                flash('error', 'You are not assigned to this class.');
                redirect('/classes');
            }
        }

        $teachers = db()->fetchAll("SELECT teacher_id, name FROM teacher ORDER BY name");
        $title = 'Class Profile';
        $this->render('classes/show', compact('title', 'year', 'class', 'sections', 'subjects', 'students', 'teachers'));
    }


    public function delete(): void
    {
        require_auth(['admin']);
        $id = (int) request('class_id');
        if ($id <= 0) {
            flash('error', 'Invalid class selected.');
            redirect('/classes');
        }
        $class = db()->fetch("SELECT * FROM class WHERE class_id = :id LIMIT 1", ['id' => $id]);
        if (!$class) {
            flash('error', 'Class not found.');
            redirect('/classes');
        }

        $studentRows = db()->fetchAll("SELECT DISTINCT student_id FROM enroll WHERE class_id = :class_id", ['class_id' => $id]);
        $studentIds = array_values(array_filter(array_map(static fn($r) => (int)($r['student_id'] ?? 0), $studentRows)));
        $deletedStudents = count($studentIds);
        $pdo = db()->pdo();

        try {
            $pdo->beginTransaction();

            if ($studentIds) {
                $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
                db()->execute("DELETE FROM attendance WHERE student_id IN ({$placeholders})", $studentIds);
                db()->execute("DELETE FROM mark WHERE student_id IN ({$placeholders})", $studentIds);
                db()->execute("DELETE FROM enroll WHERE student_id IN ({$placeholders})", $studentIds);
                db()->execute("DELETE FROM student WHERE student_id IN ({$placeholders})", $studentIds);
            }

            db()->execute("DELETE FROM attendance WHERE class_id = :class_id", ['class_id' => $id]);
            db()->execute("DELETE FROM mark WHERE class_id = :class_id", ['class_id' => $id]);
            db()->execute("DELETE FROM enroll WHERE class_id = :class_id", ['class_id' => $id]);
            db()->execute("DELETE FROM subject WHERE class_id = :class_id", ['class_id' => $id]);
            db()->execute("DELETE FROM section WHERE class_id = :class_id", ['class_id' => $id]);
            db()->execute("DELETE FROM class WHERE class_id = :class_id", ['class_id' => $id]);

            $pdo->commit();
            log_activity([
                'action' => 'delete',
                'module_name' => 'classes',
                'record_id' => $id,
                'description' => 'Deleted class ' . ($class['name'] ?? ('#' . $id)) . ' and removed ' . $deletedStudents . ' linked student(s) with their enrollments, marks, and attendance records.',
                'old_values' => json_encode($class),
            ]);
            flash('success', 'Class deleted successfully. ' . $deletedStudents . ' linked student(s) and related data were removed.');
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('error', 'Unable to delete class. Please try again or check related database constraints.');
        }

        redirect('/classes');
    }

}
