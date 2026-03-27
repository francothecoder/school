<?php
declare(strict_types=1);

namespace Controllers;

class SectionController extends BaseController
{
    public function index(): void
    {
        require_auth(['admin', 'teacher']);
        $sections = db()->fetchAll("SELECT s.*, c.name AS class_name, c.name_numeric, t.name AS teacher_name
            FROM section s
            LEFT JOIN class c ON c.class_id = s.class_id
            LEFT JOIN teacher t ON t.teacher_id = s.teacher_id
            ORDER BY c.name_numeric + 0, c.name, s.name");
        $title = 'Sections';
        $this->render('sections/index', compact('title', 'sections'));
    }

    public function create(): void
    {
        require_auth(['admin']);
        $classes = db()->fetchAll("SELECT * FROM class ORDER BY name_numeric + 0, name");
        $teachers = db()->fetchAll("SELECT teacher_id, name FROM teacher ORDER BY name");
        $title = 'Add Section';
        $this->render('sections/create', compact('title', 'classes', 'teachers'));
    }

    public function store(): void
    {
        require_auth(['admin']);
        db()->execute("INSERT INTO section (name, nick_name, class_id, teacher_id) VALUES (:name, :nick_name, :class_id, :teacher_id)", [
            'name' => trim((string) request('name')),
            'nick_name' => trim((string) request('nick_name', '')),
            'class_id' => (int) request('class_id'),
            'teacher_id' => request('teacher_id') ?: null,
        ]);
        flash('success', 'Section created successfully.');
        redirect('/sections');
    }

    public function edit(): void
    {
        require_auth(['admin']);
        $id = (int) request('id');
        $section = db()->fetch("SELECT * FROM section WHERE section_id = :id", ['id' => $id]);
        $classes = db()->fetchAll("SELECT * FROM class ORDER BY name_numeric + 0, name");
        $teachers = db()->fetchAll("SELECT teacher_id, name FROM teacher ORDER BY name");
        $title = 'Edit Section';
        $this->render('sections/edit', compact('title', 'section', 'classes', 'teachers'));
    }

    public function update(): void
    {
        require_auth(['admin']);
        db()->execute("UPDATE section SET name = :name, nick_name = :nick_name, class_id = :class_id, teacher_id = :teacher_id WHERE section_id = :id", [
            'name' => trim((string) request('name')),
            'nick_name' => trim((string) request('nick_name', '')),
            'class_id' => (int) request('class_id'),
            'teacher_id' => request('teacher_id') ?: null,
            'id' => (int) request('section_id'),
        ]);
        flash('success', 'Section updated successfully.');
        redirect('/sections');
    }

    public function delete(): void
    {
        require_auth(['admin']);
        $id = (int) request('section_id');
        $used = db()->fetch("SELECT COUNT(*) AS total FROM enroll WHERE section_id = :id", ['id' => $id]);
        if ((int) ($used['total'] ?? 0) > 0) {
            flash('error', 'This section cannot be deleted because it already has enrollment records.');
            redirect('/sections');
        }
        db()->execute("DELETE FROM section WHERE section_id = :id", ['id' => $id]);
        flash('success', 'Section deleted successfully.');
        redirect('/sections');
    }

    public function classSectionsApi(): void
    {
        require_auth(['admin', 'teacher']);
        $classId = (int) request('class_id');
        $sections = $classId > 0 ? sections_by_class($classId) : [];
        json_response(['sections' => $sections]);
    }
}
