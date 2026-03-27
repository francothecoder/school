<?php
declare(strict_types=1);

namespace Controllers;

class PromotionController extends BaseController
{
    public function index(): void
    {
        require_auth(['admin']);
        $fromYear = (string) request('from_year', current_year());
        $toYear = (string) request('to_year', years_list()[2] ?? current_year());
        $classId = (int) request('class_id');
        $classes = db()->fetchAll("SELECT * FROM class ORDER BY name_numeric + 0, name");
        $students = [];
        $recommendedClass = null;

        if ($classId) {
            $recommendedClass = next_class_for($classId);
            $students = db()->fetchAll("SELECT e.enroll_id, e.student_id, e.roll, e.section_id, st.student_code, st.name,
                    c.name AS class_name, sec.name AS section_name
                FROM enroll e
                INNER JOIN student st ON st.student_id = e.student_id
                LEFT JOIN class c ON c.class_id = e.class_id
                LEFT JOIN section sec ON sec.section_id = e.section_id
                WHERE e.class_id = :class_id AND e.year = :year
                ORDER BY COALESCE(e.roll, 999999), st.name", [
                    'class_id' => $classId,
                    'year' => $fromYear,
                ]);
        }

        $title = 'Class Promotion';
        $this->render('promotion/index', compact('title', 'fromYear', 'toYear', 'classId', 'classes', 'students', 'recommendedClass'));
    }

    public function process(): void
    {
        require_auth(['admin']);
        $fromYear = (string) request('from_year', current_year());
        $toYear = (string) request('to_year');
        $fromClassId = (int) request('from_class_id');
        $toClassId = (int) request('to_class_id');
        $toSectionId = request('to_section_id') ?: null;
        $studentIds = $_POST['student_ids'] ?? [];

        if (!$toYear || !$fromClassId || !$toClassId || !$studentIds) {
            flash('error', 'Please select the source class, destination class, destination year and students to promote.');
            redirect('/promotion?from_year=' . urlencode($fromYear) . '&to_year=' . urlencode($toYear) . '&class_id=' . $fromClassId);
        }

        $created = 0;
        foreach ($studentIds as $studentId) {
            $studentId = (int) $studentId;
            $exists = db()->fetch("SELECT enroll_id FROM enroll WHERE student_id = :student_id AND year = :year LIMIT 1", [
                'student_id' => $studentId,
                'year' => $toYear,
            ]);
            if ($exists) {
                continue;
            }

            $latest = db()->fetch("SELECT roll FROM enroll WHERE student_id = :student_id AND year = :year LIMIT 1", [
                'student_id' => $studentId,
                'year' => $fromYear,
            ]);

            db()->execute("INSERT INTO enroll (enroll_code, student_id, class_id, section_id, roll, date_added, year)
                VALUES (:enroll_code, :student_id, :class_id, :section_id, :roll, :date_added, :year)", [
                'enroll_code' => substr(md5((string) microtime(true) . $studentId . $toYear), 0, 7),
                'student_id' => $studentId,
                'class_id' => $toClassId,
                'section_id' => $toSectionId,
                'roll' => $latest['roll'] ?? null,
                'date_added' => (string) time(),
                'year' => $toYear,
            ]);
            $created++;
        }

        flash('success', $created . ' student(s) promoted successfully.');
        redirect('/promotion?from_year=' . urlencode($fromYear) . '&to_year=' . urlencode($toYear) . '&class_id=' . $fromClassId);
    }
}
