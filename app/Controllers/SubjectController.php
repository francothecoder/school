<?php
declare(strict_types=1);

namespace Controllers;

class SubjectController extends BaseController
{
    private function subjectRedirect(?int $classId = null): string
    {
        $classId = $classId ?: (int) request('class_id');
        return $classId > 0 ? '/subjects?class_id=' . $classId : '/subjects';
    }

    public function index(): void
    {
        require_auth(['admin', 'teacher']);

        $year = (string) request('year', current_year());
        $selectedClassId = (int) request('class_id');

        if (current_user()['role'] === 'teacher') {
            $classes = db()->fetchAll("SELECT DISTINCT c.*
                FROM class c
                INNER JOIN subject s ON s.class_id = c.class_id
                WHERE s.teacher_id = :teacher_id AND s.year = :year
                ORDER BY c.name_numeric + 0, c.name", [
                'teacher_id' => current_user()['id'],
                'year' => $year,
            ]);
        } else {
            $classes = db()->fetchAll("SELECT * FROM class ORDER BY name_numeric + 0, name");
        }

        $selectedClass = null;
        $subjects = [];
        $teachers = current_user()['role'] === 'admin'
            ? db()->fetchAll("SELECT teacher_id, name FROM teacher ORDER BY name")
            : [];

        if ($selectedClassId > 0) {
            $allowedClassIds = array_map(static fn($row) => (int) $row['class_id'], $classes);
            if (!in_array($selectedClassId, $allowedClassIds, true)) {
                flash('error', 'You do not have access to manage subjects for the selected class.');
                redirect('/subjects');
            }

            $selectedClass = db()->fetch("SELECT * FROM class WHERE class_id = :id LIMIT 1", ['id' => $selectedClassId]);
            if (!$selectedClass) {
                flash('error', 'Selected class was not found.');
                redirect('/subjects');
            }

            $params = [
                'year' => $year,
                'class_id' => $selectedClassId,
            ];
            $sql = "SELECT s.*, c.name AS class_name, c.name_numeric, t.name AS teacher_name
                FROM subject s
                LEFT JOIN class c ON c.class_id = s.class_id
                LEFT JOIN teacher t ON t.teacher_id = s.teacher_id
                WHERE s.year = :year AND s.class_id = :class_id";

            if (current_user()['role'] === 'teacher') {
                $sql .= " AND s.teacher_id = :teacher_id";
                $params['teacher_id'] = current_user()['id'];
            }

            $sql .= " ORDER BY s.name";
            $subjects = db()->fetchAll($sql, $params);
        }

        $title = 'Subjects';
        $this->render('subjects/index', compact('title', 'subjects', 'classes', 'teachers', 'selectedClassId', 'selectedClass', 'year'));
    }

    public function create(): void
    {
        require_auth(['admin']);
        $selectedClassId = (int) request('class_id');
        $classes = db()->fetchAll("SELECT * FROM class ORDER BY name_numeric + 0, name");
        $teachers = db()->fetchAll("SELECT teacher_id, name FROM teacher ORDER BY name");
        $title = 'Add Subject';
        $this->render('subjects/create', compact('title', 'classes', 'teachers', 'selectedClassId'));
    }

    public function store(): void
    {
        require_auth(['admin']);
        $names = preg_split('/\r\n|\r|\n|,/', (string) request('name'));
        $classId = (int) request('class_id');
        $teacherId = request('teacher_id') !== '' ? (int) request('teacher_id') : null;
        $year = (string) request('year', current_year());
        $count = 0;

        if ($classId <= 0) {
            flash('error', 'Please select a class before adding subjects.');
            redirect('/subjects');
        }

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

        log_activity([
            'action' => 'create',
            'module_name' => 'subjects',
            'record_id' => $classId,
            'description' => 'Added ' . $count . ' subject(s) to class #' . $classId . ' for ' . $year . '.',
            'new_values' => json_encode([
                'class_id' => $classId,
                'teacher_id' => $teacherId,
                'year' => $year,
                'count' => $count,
            ]),
        ]);

        flash('success', $count . ' subject(s) added successfully.');
        redirect('/subjects?class_id=' . $classId . '&year=' . urlencode($year));
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
        $returnClassId = (int) request('return_class_id', (int) ($subject['class_id'] ?? 0));
        $title = 'Edit Subject';
        $this->render('subjects/edit', compact('title', 'subject', 'classes', 'teachers', 'returnClassId'));
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

        $classId = (int) request('class_id');
        $year = (string) request('year', current_year());
        db()->execute("UPDATE subject SET name = :name, class_id = :class_id, teacher_id = :teacher_id, year = :year WHERE subject_id = :id", [
            'name' => strtoupper(trim((string) request('name'))),
            'class_id' => $classId,
            'teacher_id' => request('teacher_id') !== '' ? (int) request('teacher_id') : null,
            'year' => $year,
            'id' => $id,
        ]);
        log_activity([
            'action' => 'update',
            'module_name' => 'subjects',
            'record_id' => $id,
            'description' => 'Updated subject details/teacher assignment from class-wise subject management.',
            'old_values' => json_encode($old),
            'new_values' => json_encode([
                'name' => request('name'),
                'class_id' => $classId,
                'teacher_id' => request('teacher_id'),
                'year' => $year,
            ]),
        ]);
        flash('success', 'Subject updated successfully. Existing marks remain intact.');
        redirect('/subjects?class_id=' . $classId . '&year=' . urlencode($year));
    }

    public function reassignTeacher(): void
    {
        require_auth(['admin']);
        $subjectId = (int) request('subject_id');
        $teacherId = request('teacher_id') !== '' ? (int) request('teacher_id') : null;
        $returnClassId = (int) request('class_id');
        $year = (string) request('year', current_year());

        if ($subjectId <= 0) {
            flash('error', 'Invalid subject selected.');
            redirect('/subjects' . ($returnClassId > 0 ? '?class_id=' . $returnClassId : ''));
        }

        $subject = db()->fetch("SELECT s.*, t.name AS old_teacher_name
            FROM subject s
            LEFT JOIN teacher t ON t.teacher_id = s.teacher_id
            WHERE s.subject_id = :id LIMIT 1", ['id' => $subjectId]);

        if (!$subject) {
            flash('error', 'Subject not found.');
            redirect('/subjects' . ($returnClassId > 0 ? '?class_id=' . $returnClassId : ''));
        }

        $newTeacher = null;
        if ($teacherId !== null) {
            $newTeacher = db()->fetch("SELECT teacher_id, name FROM teacher WHERE teacher_id = :id LIMIT 1", ['id' => $teacherId]);
            if (!$newTeacher) {
                flash('error', 'Selected teacher was not found.');
                redirect('/subjects?class_id=' . ((int) ($subject['class_id'] ?? $returnClassId)) . '&year=' . urlencode($year));
            }
        }

        db()->execute("UPDATE subject SET teacher_id = :teacher_id WHERE subject_id = :subject_id", [
            'teacher_id' => $teacherId,
            'subject_id' => $subjectId,
        ]);

        log_activity([
            'action' => 'reassign',
            'module_name' => 'subjects',
            'record_id' => $subjectId,
            'description' => 'Reassigned subject ' . ($subject['name'] ?? ('#' . $subjectId)) . ' from ' . ($subject['old_teacher_name'] ?? 'Unassigned') . ' to ' . ($newTeacher['name'] ?? 'Unassigned') . '. Existing marks were preserved.',
            'old_values' => json_encode([
                'subject_id' => $subjectId,
                'teacher_id' => $subject['teacher_id'] ?? null,
                'teacher_name' => $subject['old_teacher_name'] ?? null,
            ]),
            'new_values' => json_encode([
                'subject_id' => $subjectId,
                'teacher_id' => $teacherId,
                'teacher_name' => $newTeacher['name'] ?? null,
            ]),
        ]);

        flash('success', 'Subject reassigned successfully. Existing marks remain intact.');
        redirect('/subjects?class_id=' . ((int) ($subject['class_id'] ?? $returnClassId)) . '&year=' . urlencode($year));
    }

    public function delete(): void
    {
        require_auth(['admin']);
        $subjectId = (int) request('subject_id');
        $classId = (int) request('class_id');
        $year = (string) request('year', current_year());

        if ($subjectId <= 0) {
            flash('error', 'Invalid subject selected.');
            redirect('/subjects' . ($classId > 0 ? '?class_id=' . $classId : ''));
        }

        $subject = db()->fetch("SELECT * FROM subject WHERE subject_id = :subject_id LIMIT 1", ['subject_id' => $subjectId]);
        if (!$subject) {
            flash('error', 'Subject not found.');
            redirect('/subjects' . ($classId > 0 ? '?class_id=' . $classId : ''));
        }

        $marks = db()->fetch("SELECT COUNT(*) AS total FROM mark WHERE subject_id = :subject_id", ['subject_id' => $subjectId]);
        if ((int) ($marks['total'] ?? 0) > 0) {
            flash('error', 'This subject already has mark records and cannot be deleted. You can edit or reassign its teacher instead.');
            redirect('/subjects?class_id=' . ((int) ($subject['class_id'] ?? $classId)) . '&year=' . urlencode($year));
        }

        db()->execute("DELETE FROM subject WHERE subject_id = :subject_id", ['subject_id' => $subjectId]);
        log_activity([
            'action' => 'delete',
            'module_name' => 'subjects',
            'record_id' => $subjectId,
            'description' => 'Deleted subject ' . ($subject['name'] ?? ('#' . $subjectId)) . ' from class-wise subject management.',
            'old_values' => json_encode($subject),
        ]);
        flash('success', 'Subject deleted successfully.');
        redirect('/subjects?class_id=' . ((int) ($subject['class_id'] ?? $classId)) . '&year=' . urlencode($year));
    }
}
