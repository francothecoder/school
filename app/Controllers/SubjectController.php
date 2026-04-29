<?php
declare(strict_types=1);

namespace Controllers;

class SubjectController extends BaseController
{
    public function index(): void
    {
        require_auth(['admin', 'teacher']);
        $params = ['year' => current_year()];
        $sql = "SELECT s.*, c.name AS class_name, c.name_numeric, t.name AS teacher_name
            FROM subject s
            LEFT JOIN class c ON c.class_id = s.class_id
            LEFT JOIN teacher t ON t.teacher_id = s.teacher_id
            WHERE s.year = :year";
        if (current_user()['role'] === 'teacher') {
            $sql .= " AND s.teacher_id = :teacher_id";
            $params['teacher_id'] = current_user()['id'];
        }
        $sql .= " ORDER BY c.name_numeric + 0, c.name, s.name";
        $subjects = db()->fetchAll($sql, $params);
        $classes = db()->fetchAll("SELECT * FROM class ORDER BY name_numeric + 0, name");
        $title = 'Subjects';
        $this->render('subjects/index', compact('title', 'subjects', 'classes'));
    }

    public function create(): void
    {
        require_auth(['admin']);
        $classes = db()->fetchAll("SELECT * FROM class ORDER BY name_numeric + 0, name");
        $teachers = db()->fetchAll("SELECT teacher_id, name FROM teacher ORDER BY name");
        $title = 'Add Subject';
        $this->render('subjects/create', compact('title', 'classes', 'teachers'));
    }

    public function store(): void
    {
        require_auth(['admin']);
        $names = preg_split('/
||
|,/', (string) request('name'));
        $classId = (int) request('class_id');
        $teacherId = request('teacher_id') ?: null;
        $year = (string) request('year', current_year());
        $count = 0;

        foreach ($names as $rawName) {
            $name = strtoupper(trim((string) $rawName));
            if ($name === '') {
                continue;
            }
            $exists = db()->fetch("SELECT subject_id FROM subject WHERE name = :name AND class_id = :class_id AND year = :year LIMIT 1", [
                'name' => $name,
                'class_id' => $classId,
                'year' => $year,
            ]);
            if ($exists) {
                continue;
            }
            db()->execute("INSERT INTO subject (name, class_id, teacher_id, year)
                VALUES (:name, :class_id, :teacher_id, :year)", [
                'name' => $name,
                'class_id' => $classId,
                'teacher_id' => $teacherId,
                'year' => $year,
            ]);
            $count++;
        }

        flash('success', $count . ' subject(s) added successfully.');
        $redirectClassId = (int) request('redirect_class_id', $classId);
        if ($redirectClassId > 0) {
            redirect('/classes/show?id=' . $redirectClassId);
        }
        redirect('/subjects');
    }

    public function edit(): void
    {
        require_auth(['admin']);
        $id = (int) request('id');
        $subject = db()->fetch("SELECT * FROM subject WHERE subject_id = :id LIMIT 1", ['id' => $id]);
        if (!$subject) {
            flash('error', 'Subject not found.');
            redirect('/subjects');
        }
        $classes = db()->fetchAll("SELECT * FROM class ORDER BY name_numeric + 0, name");
        $teachers = db()->fetchAll("SELECT teacher_id, name FROM teacher ORDER BY name");
        $title = 'Edit Subject';
        $this->render('subjects/edit', compact('title', 'subject', 'classes', 'teachers'));
    }

    public function update(): void
    {
        require_auth(['admin']);
        $id = (int) request('subject_id');
        if ($id <= 0) {
            flash('error', 'Invalid subject selected.');
            redirect('/subjects');
        }
        $old = db()->fetch("SELECT * FROM subject WHERE subject_id = :id LIMIT 1", ['id' => $id]);
        if (!$old) {
            flash('error', 'Subject not found.');
            redirect('/subjects');
        }
        db()->execute("UPDATE subject SET name = :name, class_id = :class_id, teacher_id = :teacher_id, year = :year WHERE subject_id = :id", [
            'name' => strtoupper(trim((string) request('name'))),
            'class_id' => (int) request('class_id'),
            'teacher_id' => request('teacher_id') ?: null,
            'year' => request('year', current_year()),
            'id' => $id,
        ]);
        log_activity([
            'action' => 'update',
            'module_name' => 'subjects',
            'record_id' => $id,
            'description' => 'Updated subject details/teacher assignment.',
            'old_values' => json_encode($old),
            'new_values' => json_encode([
                'name' => request('name'),
                'class_id' => request('class_id'),
                'teacher_id' => request('teacher_id'),
                'year' => request('year', current_year()),
            ]),
        ]);
        flash('success', 'Subject updated successfully. Teacher assignment changed without affecting existing marks.');
        redirect('/subjects');
    }

    public function delete(): void
    {
        require_auth(['admin']);
        $subjectId = (int) request('subject_id');
        $classId = (int) request('class_id');
        $marks = db()->fetch("SELECT COUNT(*) AS total FROM mark WHERE subject_id = :subject_id", ['subject_id' => $subjectId]);
        if ((int) ($marks['total'] ?? 0) > 0) {
            flash('error', 'This subject already has mark records and cannot be deleted. You can edit its teacher assignment instead.');
            redirect('/classes/show?id=' . $classId);
        }
        db()->execute("DELETE FROM subject WHERE subject_id = :subject_id", ['subject_id' => $subjectId]);
        flash('success', 'Subject deleted successfully.');
        redirect('/classes/show?id=' . $classId);
    }
}
