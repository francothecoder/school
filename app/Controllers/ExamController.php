<?php
declare(strict_types=1);

namespace Controllers;

class ExamController extends BaseController
{
    public function index(): void
    {
        require_auth(['admin', 'teacher']);
        $year = current_year();
        $exams = db()->fetchAll("SELECT * FROM exam WHERE year = :year ORDER BY exam_id DESC", ['year' => $year]);
        $title = 'Exams';
        $this->render('exams/index', compact('title', 'exams', 'year'));
    }

    public function create(): void
    {
        require_auth(['admin']);
        $title = 'Create Exam';
        $this->render('exams/create', compact('title'));
    }

    public function store(): void
    {
        require_auth(['admin']);
        db()->execute("INSERT INTO exam (name, exam_term, date, year, comment)
            VALUES (:name, :exam_term, :date, :year, :comment)", [
            'name' => trim((string) request('name')),
            'exam_term' => $this->normalizeTerm(request('exam_term', '')),
            'date' => request('date', ''),
            'year' => request('year', current_year()),
            'comment' => request('comment', ''),
        ]);
        flash('success', 'Exam created successfully.');
        redirect('/exams');
    }

    public function edit(): void
    {
        require_auth(['admin']);
        $id = (int) request('id');
        $exam = db()->fetch("SELECT * FROM exam WHERE exam_id = :id LIMIT 1", ['id' => $id]);
        if (!$exam) {
            flash('error', 'Exam not found.');
            redirect('/exams');
        }
        $title = 'Edit Exam';
        $this->render('exams/edit', compact('title', 'exam'));
    }

    public function update(): void
    {
        require_auth(['admin']);
        $id = (int) request('exam_id');
        if ($id <= 0) {
            flash('error', 'Invalid exam selected.');
            redirect('/exams');
        }
        $old = db()->fetch("SELECT * FROM exam WHERE exam_id = :id LIMIT 1", ['id' => $id]);
        if (!$old) {
            flash('error', 'Exam not found.');
            redirect('/exams');
        }
        db()->execute("UPDATE exam SET name = :name, exam_term = :exam_term, date = :date, year = :year, comment = :comment WHERE exam_id = :id", [
            'name' => trim((string) request('name')),
            'exam_term' => $this->normalizeTerm(request('exam_term', '')),
            'date' => request('date', ''),
            'year' => request('year', current_year()),
            'comment' => request('comment', ''),
            'id' => $id,
        ]);
        log_activity([
            'action' => 'update',
            'module_name' => 'exams',
            'record_id' => $id,
            'description' => 'Updated exam name/term/details.',
            'old_values' => json_encode($old),
            'new_values' => json_encode([
                'name' => request('name'),
                'exam_term' => request('exam_term'),
                'date' => request('date'),
                'year' => request('year', current_year()),
                'comment' => request('comment'),
            ]),
        ]);
        flash('success', 'Exam updated successfully.');
        redirect('/exams');
    }

    private function normalizeTerm($term): string
    {
        $term = trim((string) $term);
        if (preg_match('/([123])/', $term, $m)) {
            return 'Term ' . $m[1];
        }
        return $term;
    }
}
