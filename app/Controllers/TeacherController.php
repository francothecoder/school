<?php
declare(strict_types=1);

namespace Controllers;

use Core\Auth;

class TeacherController extends BaseController
{
    public function index(): void
    {
        require_auth(['admin']);
        $q = trim((string) request('q', ''));
        $sql = "SELECT t.*,
                (SELECT COUNT(*) FROM subject s WHERE s.teacher_id = t.teacher_id AND s.year = :year) AS assigned_subjects
                FROM teacher t WHERE 1=1";
        $params = ['year' => current_year()];
        if ($q !== '') {
            $sql .= " AND (t.name LIKE :q OR t.email LIKE :q OR t.phone LIKE :q)";
            $params['q'] = "%{$q}%";
        }
        $sql .= " ORDER BY t.name LIMIT 200";
        $teachers = db()->fetchAll($sql, $params);
        $title = 'Teachers';
        $this->render('teachers/index', compact('title', 'teachers', 'q'));
    }

    public function create(): void
    {
        require_auth(['admin']);
        $title = 'Add Teacher';
        $this->render('teachers/create', compact('title'));
    }

    public function store(): void
    {
        require_auth(['admin']);
        db()->execute("INSERT INTO teacher (name, email, password, sex, phone, address, designation, show_on_website)
            VALUES (:name, :email, :password, :sex, :phone, :address, :designation, 0)", [
            'name' => request('name'),
            'email' => request('email'),
            'password' => Auth::makePassword((string) request('password')),
            'sex' => request('sex', ''),
            'phone' => request('phone', ''),
            'address' => request('address', ''),
            'designation' => request('designation', 'Subject Teacher'),
        ]);
        flash('success', 'Teacher created successfully.');
        redirect('/teachers');
    }

    public function show(): void
    {
        require_auth(['admin', 'teacher']);
        $id = (int) request('id');
        if (current_user()['role'] === 'teacher') $id = current_user()['id'];
        $teacher = db()->fetch("SELECT * FROM teacher WHERE teacher_id = :id", ['id' => $id]);
        $subjects = db()->fetchAll("SELECT s.*, c.name AS class_name
            FROM subject s
            LEFT JOIN class c ON c.class_id = s.class_id
            WHERE s.teacher_id = :id
            ORDER BY c.name_numeric + 0, c.name, s.name", ['id' => $id]);
        $title = 'Teacher Profile';
        $this->render('teachers/show', compact('title', 'teacher', 'subjects'));
    }

    public function edit(): void
    {
        require_auth(['admin']);
        $id = (int) request('id');
        $teacher = db()->fetch("SELECT * FROM teacher WHERE teacher_id = :id", ['id' => $id]);
        $title = 'Edit Teacher';
        $this->render('teachers/edit', compact('title', 'teacher'));
    }

    public function update(): void
    {
        require_auth(['admin']);
        $id = (int) request('teacher_id');
        db()->execute("UPDATE teacher SET
            name = :name, email = :email, sex = :sex, phone = :phone, address = :address, designation = :designation
            WHERE teacher_id = :id", [
            'name' => request('name'),
            'email' => request('email'),
            'sex' => request('sex'),
            'phone' => request('phone'),
            'address' => request('address'),
            'designation' => request('designation'),
            'id' => $id,
        ]);
        if (request('password')) {
            db()->execute("UPDATE teacher SET password = :password WHERE teacher_id = :id", [
                'password' => Auth::makePassword((string) request('password')),
                'id' => $id,
            ]);
        }
        flash('success', 'Teacher updated successfully.');
        redirect('/teachers/show?id=' . $id);
    }

    public function delete(): void
    {
        require_auth(['admin']);
        $id = (int) request('teacher_id');
        if ($id <= 0) {
            flash('error', 'Invalid teacher selected.');
            redirect('/teachers');
        }
        $teacher = db()->fetch("SELECT * FROM teacher WHERE teacher_id = :id LIMIT 1", ['id' => $id]);
        if (!$teacher) {
            flash('error', 'Teacher not found.');
            redirect('/teachers');
        }

        $pdo = db()->pdo();
        try {
            $pdo->beginTransaction();
            db()->execute("UPDATE subject SET teacher_id = NULL WHERE teacher_id = :teacher_id", ['teacher_id' => $id]);
            db()->execute("DELETE FROM teacher WHERE teacher_id = :teacher_id", ['teacher_id' => $id]);
            $pdo->commit();
            log_activity([
                'action' => 'delete',
                'module_name' => 'teachers',
                'record_id' => $id,
                'description' => 'Deleted teacher ' . ($teacher['name'] ?? ('#' . $id)) . '. Assigned subjects were unassigned; marks entered by the teacher were preserved.',
                'old_values' => json_encode($teacher),
            ]);
            flash('success', 'Teacher deleted successfully. Assigned subjects are now unassigned, and entered marks remain intact.');
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('error', 'Unable to delete teacher right now.');
        }
        redirect('/teachers');
    }

}
