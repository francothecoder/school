<?php
declare(strict_types=1);

namespace Controllers;

use Throwable;

class SearchController extends BaseController
{
    public function suggest(): void
    {
        require_auth(['admin']);
        $q = trim((string) request('q', ''));
        if ($q === '' || strlen($q) < 1) {
            json_response(['suggestions' => []]);
            return;
        }

        $needle = '%' . $q . '%';
        $suggestions = [];

        $append = static function (array $items) use (&$suggestions): void {
            foreach ($items as $item) {
                $suggestions[] = $item;
            }
        };

        try {
            $students = db()->fetchAll(
                "SELECT student_id, name, student_code, phone, email
                 FROM student
                 WHERE name LIKE :q OR student_code LIKE :q OR phone LIKE :q OR email LIKE :q
                 ORDER BY name ASC
                 LIMIT 6",
                ['q' => $needle]
            );
            $append(array_map(static function (array $row): array {
                return [
                    'type' => 'Student',
                    'title' => $row['name'] ?: 'Student',
                    'meta' => 'Code: ' . ($row['student_code'] ?: '-') . (($row['phone'] ?? '') ? ' · ' . $row['phone'] : ''),
                    'url' => base_url('/students/show?id=' . (int) $row['student_id']),
                ];
            }, $students));
        } catch (Throwable $e) {
            // keep search alive even if one query fails on a schema difference
        }

        try {
            $teachers = db()->fetchAll(
                "SELECT teacher_id, name, email, phone, designation
                 FROM teacher
                 WHERE name LIKE :q OR email LIKE :q OR phone LIKE :q OR designation LIKE :q
                 ORDER BY name ASC
                 LIMIT 5",
                ['q' => $needle]
            );
            $append(array_map(static function (array $row): array {
                $meta = $row['designation'] ?: ($row['email'] ?: ($row['phone'] ?: 'Teacher record'));
                return [
                    'type' => 'Teacher',
                    'title' => $row['name'] ?: 'Teacher',
                    'meta' => $meta,
                    'url' => base_url('/teachers/show?id=' . (int) $row['teacher_id']),
                ];
            }, $teachers));
        } catch (Throwable $e) {
        }

        try {
            $classes = db()->fetchAll(
                "SELECT class_id, name, name_numeric
                 FROM class
                 WHERE name LIKE :q OR CAST(name_numeric AS CHAR) LIKE :q
                 ORDER BY COALESCE(name_numeric, 0) + 0 ASC, name ASC
                 LIMIT 5",
                ['q' => $needle]
            );
            $append(array_map(static function (array $row): array {
                $meta = 'Class';
                if (!empty($row['name_numeric'])) {
                    $meta .= ' · ' . $row['name_numeric'];
                }
                return [
                    'type' => 'Class',
                    'title' => $row['name'] ?: 'Class',
                    'meta' => $meta,
                    'url' => base_url('/classes/show?id=' . (int) $row['class_id']),
                ];
            }, $classes));
        } catch (Throwable $e) {
        }

        try {
            $subjects = db()->fetchAll(
                "SELECT s.subject_id, s.name, c.name AS class_name, t.name AS teacher_name
                 FROM subject s
                 LEFT JOIN class c ON c.class_id = s.class_id
                 LEFT JOIN teacher t ON t.teacher_id = s.teacher_id
                 WHERE s.name LIKE :q OR c.name LIKE :q OR t.name LIKE :q
                 ORDER BY s.name ASC
                 LIMIT 6",
                ['q' => $needle]
            );
            $append(array_map(static function (array $row): array {
                $meta = 'Class: ' . ($row['class_name'] ?: '-');
                if (!empty($row['teacher_name'])) {
                    $meta .= ' · ' . $row['teacher_name'];
                }
                return [
                    'type' => 'Subject',
                    'title' => $row['name'] ?: 'Subject',
                    'meta' => $meta,
                    'url' => base_url('/subjects?highlight=' . (int) $row['subject_id']),
                ];
            }, $subjects));
        } catch (Throwable $e) {
        }

        try {
            $exams = db()->fetchAll(
                "SELECT exam_id, name, exam_term, date, year
                 FROM exam
                 WHERE name LIKE :q OR exam_term LIKE :q OR CAST(year AS CHAR) LIKE :q
                 ORDER BY year DESC, exam_id DESC
                 LIMIT 5",
                ['q' => $needle]
            );
            $append(array_map(static function (array $row): array {
                $parts = array_filter([$row['exam_term'] ?? '', $row['year'] ?? '', $row['date'] ?? '']);
                return [
                    'type' => 'Exam',
                    'title' => $row['name'] ?: 'Exam',
                    'meta' => $parts ? implode(' · ', $parts) : 'Jump to analytics',
                    'url' => base_url('/analytics?exam_id=' . (int) $row['exam_id']),
                ];
            }, $exams));
        } catch (Throwable $e) {
        }

        try {
            $announcements = db()->fetchAll(
                "SELECT notice_id, notice_title, create_timestamp
                 FROM noticeboard
                 WHERE notice_title LIKE :q OR notice LIKE :q
                 ORDER BY notice_id DESC
                 LIMIT 5",
                ['q' => $needle]
            );
            $append(array_map(static function (array $row): array {
                $stamp = !empty($row['create_timestamp']) ? date('d M Y', (int) $row['create_timestamp']) : 'recently';
                return [
                    'type' => 'Announcement',
                    'title' => $row['notice_title'] ?: 'Announcement',
                    'meta' => 'Posted: ' . $stamp,
                    'url' => base_url('/announcements'),
                ];
            }, $announcements));
        } catch (Throwable $e) {
        }

        json_response(['suggestions' => array_slice($suggestions, 0, 15)]);
    }
}
