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
            'name' => request('name'),
            'exam_term' => request('exam_term', ''),
            'date' => request('date', ''),
            'year' => request('year', current_year()),
            'comment' => request('comment', ''),
        ]);
        flash('success', 'Exam created successfully.');
        redirect('/exams');
    }
}
