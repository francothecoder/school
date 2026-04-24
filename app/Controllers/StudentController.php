<?php
declare(strict_types=1);

namespace Controllers;

use Core\Auth;

class StudentController extends BaseController
{
    public function index(): void
    {
        require_auth(['admin', 'teacher']);
        $q = trim((string) request('q', ''));
        $year = current_year();
        $classId = (int) request('class_id');
        $params = ['year' => $year];
        $sql = "SELECT st.*, e.enroll_id, e.roll, e.year, c.name AS class_name, sec.name AS section_name
                FROM student st
                LEFT JOIN enroll e ON e.student_id = st.student_id AND e.year = :year
                LEFT JOIN class c ON c.class_id = e.class_id
                LEFT JOIN section sec ON sec.section_id = e.section_id
                WHERE 1=1";
        if ($q !== '') {
            $sql .= " AND (st.name LIKE :q OR st.student_code LIKE :q OR st.email LIKE :q)";
            $params['q'] = "%{$q}%";
        }
        if ($classId > 0) {
            $sql .= " AND e.class_id = :class_id";
            $params['class_id'] = $classId;
        }
        if (current_user()['role'] === 'teacher') {
            $sql .= " AND EXISTS (
                SELECT 1 FROM subject sj
                WHERE sj.class_id = e.class_id AND sj.teacher_id = :teacher_id AND sj.year = :year
            )";
            $params['teacher_id'] = current_user()['id'];
        }
        $sql .= " ORDER BY c.name_numeric + 0, c.name, COALESCE(e.roll, 999999), st.name LIMIT 500";
        $students = db()->fetchAll($sql, $params);
        $classes = current_user()['role'] === 'teacher'
            ? db()->fetchAll("SELECT DISTINCT c.* FROM class c INNER JOIN subject s ON s.class_id = c.class_id WHERE s.teacher_id = :teacher_id AND s.year = :year ORDER BY c.name_numeric + 0, c.name", ['teacher_id' => current_user()['id'], 'year' => $year])
            : db()->fetchAll("SELECT * FROM class ORDER BY name_numeric + 0, name");

        if (request('download') === 'csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="student-list-' . ($classId > 0 ? preg_replace('/[^A-Za-z0-9\-_]+/', '-', (string) ((db()->fetch("SELECT name FROM class WHERE class_id = :id LIMIT 1", ['id' => $classId])['name'] ?? 'class'))) : 'all') . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Student Name', 'Student Code', 'Sex', 'Email', 'Phone', 'Class', 'Section', 'Roll', 'Academic Year']);
            foreach ($students as $row) {
                fputcsv($out, [
                    $row['name'] ?? '',
                    $row['student_code'] ?? '',
                    $row['sex'] ?? '',
                    $row['email'] ?? '',
                    $row['phone'] ?? '',
                    $row['class_name'] ?? '',
                    $row['section_name'] ?? '',
                    $row['roll'] ?? '',
                    $row['year'] ?? '',
                ]);
            }
            fclose($out);
            exit;
        }

        $title = 'Students';
        $this->render('students/index', compact('title', 'students', 'q', 'year', 'classes', 'classId'));
    }

    public function create(): void
    {
        require_auth(['admin']);
        $classes = db()->fetchAll("SELECT * FROM class ORDER BY name_numeric + 0, name");
        $sections = db()->fetchAll("SELECT * FROM section ORDER BY class_id, name");
        $suggestedStudentCode = $this->generateUniqueStudentCode();
        $title = 'Add Student';
        $this->render('students/create', compact('title', 'classes', 'sections', 'suggestedStudentCode'));
    }

    public function store(): void
    {
        require_auth(['admin']);
        $requestedStudentCode = trim((string) request('student_code'));
        $studentCode = $this->generateUniqueStudentCode($requestedStudentCode);
        $name = trim((string) request('name'));
        $email = trim((string) request('email'));
        $password = (string) request('password');
        if ($password === '') {
            $password = $studentCode;
        }
        $classId = (int) request('class_id');
        $sectionId = (int) request('section_id');
        $year = (string) request('year', current_year());

        db()->execute("INSERT INTO student (student_code, name, email, password, sex, phone, address, parent_id)
            VALUES (:student_code, :name, :email, :password, :sex, :phone, :address, 0)", [
            'student_code' => $studentCode,
            'name' => $name,
            'email' => $this->cleanOptionalEmail($email),
            'password' => Auth::makePassword($password),
            'sex' => request('sex', ''),
            'phone' => request('phone', ''),
            'address' => request('address', ''),
        ]);

        $studentId = (int) db()->lastInsertId();

        db()->execute("INSERT INTO enroll (enroll_code, student_id, class_id, section_id, roll, date_added, year)
            VALUES (:enroll_code, :student_id, :class_id, :section_id, :roll, :date_added, :year)", [
            'enroll_code' => substr(md5((string) microtime(true) . $studentId), 0, 7),
            'student_id' => $studentId,
            'class_id' => $classId,
            'section_id' => $sectionId ?: null,
            'roll' => request('roll') ?: null,
            'date_added' => (string) time(),
            'year' => $year,
        ]);

        $message = 'Student created successfully.';
        if ($requestedStudentCode === '' || strcasecmp($requestedStudentCode, $studentCode) !== 0) {
            $message .= ' Assigned code: ' . $studentCode . '.';
        }
        flash('success', $message);
        redirect('/students');
    }

    public function bulkAdmission(): void
    {
        require_auth(['admin']);
        $classes = db()->fetchAll("SELECT * FROM class ORDER BY name_numeric + 0, name");
        $sections = db()->fetchAll("SELECT * FROM section ORDER BY class_id, name");
        $title = 'Bulk Admission';
        $this->render('students/bulk-admission', compact('title', 'classes', 'sections'));
    }

    public function bulkStore(): void
    {
        require_auth(['admin']);
        $classId = (int) request('class_id');
        $sectionId = request('section_id') ?: null;
        $year = (string) request('year', current_year());
        $defaultPassword = (string) request('default_password', '123456');
        $created = 0;
        $skipped = 0;

        foreach ($this->collectBulkStudentLines() as $parts) {
            $parts = array_map(static fn($value) => trim((string) $value), (array) $parts);
            if (!$parts || $this->isBulkHeaderRow($parts)) {
                continue;
            }

            $studentCode = $this->generateUniqueStudentCode($parts[0] ?? '');
            $name = $parts[1] ?? '';
            $sex = $this->normalizeSex($parts[2] ?? '');
            $email = $this->cleanOptionalEmail($parts[3] ?? '');
            $phone = $parts[4] ?? '';
            $roll = $parts[5] ?? null;

            if ($name === '' || $sex === '') {
                $skipped++;
                continue;
            }

            try {
                db()->execute("INSERT INTO student (student_code, name, email, password, sex, phone, address, parent_id)
                    VALUES (:student_code, :name, :email, :password, :sex, :phone, :address, 0)", [
                    'student_code' => $studentCode,
                    'name' => $name,
                    'email' => $email,
                    'password' => Auth::makePassword($defaultPassword !== '' ? $defaultPassword : $studentCode),
                    'sex' => $sex,
                    'phone' => $phone,
                    'address' => '',
                ]);
                $studentId = (int) db()->lastInsertId();
            } catch (\PDOException $e) {
                $skipped++;
                continue;
            }

            $enrolled = db()->fetch("SELECT enroll_id FROM enroll WHERE student_id = :student_id AND year = :year LIMIT 1", [
                'student_id' => $studentId,
                'year' => $year,
            ]);
            if ($enrolled) {
                continue;
            }

            db()->execute("INSERT INTO enroll (enroll_code, student_id, class_id, section_id, roll, date_added, year)
                VALUES (:enroll_code, :student_id, :class_id, :section_id, :roll, :date_added, :year)", [
                'enroll_code' => substr(md5((string) microtime(true) . $studentId . $year), 0, 7),
                'student_id' => $studentId,
                'class_id' => $classId,
                'section_id' => $sectionId,
                'roll' => $roll !== '' ? $roll : null,
                'date_added' => (string) time(),
                'year' => $year,
            ]);
            $created++;
        }

        flash('success', $created . ' student(s) admitted successfully. Blank/duplicate emails were safely ignored, and missing/duplicate student codes were auto-corrected.' . ($skipped > 0 ? ' Skipped rows: ' . $skipped . '.' : ''));
        redirect('/students');
    }

    public function show(): void
    {
        require_auth();
        $id = (int) request('id');
        if (current_user()['role'] === 'student') {
            $id = current_user()['id'];
        }
        $student = db()->fetch("SELECT * FROM student WHERE student_id = :id", ['id' => $id]);
        $enrollments = db()->fetchAll("SELECT e.*, c.name AS class_name, sec.name AS section_name
            FROM enroll e
            LEFT JOIN class c ON c.class_id = e.class_id
            LEFT JOIN section sec ON sec.section_id = e.section_id
            WHERE e.student_id = :id
            ORDER BY e.enroll_id DESC", ['id' => $id]);
        $title = 'Student Profile';
        $this->render('students/show', compact('title', 'student', 'enrollments'));
    }

    public function edit(): void
    {
        require_auth(['admin']);
        $id = (int) request('id');
        $student = db()->fetch("SELECT * FROM student WHERE student_id = :id", ['id' => $id]);
        $currentEnrollment = db()->fetch("SELECT * FROM enroll WHERE student_id = :id ORDER BY enroll_id DESC LIMIT 1", ['id' => $id]);
        $classes = db()->fetchAll("SELECT * FROM class ORDER BY name_numeric + 0, name");
        $sections = db()->fetchAll("SELECT * FROM section ORDER BY class_id, name");
        $title = 'Edit Student';
        $this->render('students/edit', compact('title', 'student', 'currentEnrollment', 'classes', 'sections'));
    }

    public function update(): void
    {
        require_auth(['admin']);
        $id = (int) request('student_id');
        $requestedStudentCode = trim((string) request('student_code'));
        $studentCode = $this->generateUniqueStudentCode($requestedStudentCode, $id);
        db()->execute("UPDATE student SET
            student_code = :student_code,
            name = :name,
            email = :email,
            sex = :sex,
            phone = :phone,
            address = :address
            WHERE student_id = :id", [
            'student_code' => $studentCode,
            'name' => request('name'),
            'email' => $this->cleanOptionalEmail(request('email'), $id),
            'sex' => request('sex'),
            'phone' => request('phone'),
            'address' => request('address'),
            'id' => $id,
        ]);
        if (request('password')) {
            db()->execute("UPDATE student SET password = :password WHERE student_id = :id", [
                'password' => Auth::makePassword((string) request('password')),
                'id' => $id,
            ]);
        }
        $enrollmentId = (int) request('enroll_id');
        if ($enrollmentId) {
            db()->execute("UPDATE enroll SET class_id = :class_id, section_id = :section_id, year = :year, roll = :roll WHERE enroll_id = :id", [
                'class_id' => request('class_id'),
                'section_id' => request('section_id') ?: null,
                'year' => request('year'),
                'roll' => request('roll') ?: null,
                'id' => $enrollmentId,
            ]);
        }
        $message = 'Student updated successfully.';
        if ($requestedStudentCode === '' || strcasecmp($requestedStudentCode, $studentCode) !== 0) {
            $message .= ' Saved code: ' . $studentCode . '.';
        }
        flash('success', $message);
        redirect('/students/show?id=' . $id);
    }


    public function delete(): void
    {
        require_auth(['admin']);
        $id = (int) request('student_id');
        if ($id <= 0) {
            flash('error', 'Invalid student selected.');
            redirect('/students');
        }

        $student = db()->fetch("SELECT student_id, name FROM student WHERE student_id = :id LIMIT 1", ['id' => $id]);
        if (!$student) {
            flash('error', 'Student record not found.');
            redirect('/students');
        }

        $pdo = db()->pdo();
        try {
            $pdo->beginTransaction();
            db()->execute("DELETE FROM attendance WHERE student_id = :student_id", ['student_id' => $id]);
            db()->execute("DELETE FROM mark WHERE student_id = :student_id", ['student_id' => $id]);
            db()->execute("DELETE FROM enroll WHERE student_id = :student_id", ['student_id' => $id]);
            db()->execute("DELETE FROM student WHERE student_id = :student_id", ['student_id' => $id]);
            $pdo->commit();
            log_activity([
                'action' => 'delete',
                'module_name' => 'students',
                'record_id' => $id,
                'description' => 'Deleted student ' . ($student['name'] ?? ('#' . $id)) . ' together with enrollment and academic records.',
                'old_values' => json_encode($student),
            ]);
            flash('success', 'Student deleted successfully. Enrollment and academic records were removed too.');
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('error', 'Unable to delete student right now.');
        }
        redirect('/students');
    }

    private function cleanOptionalEmail($email, ?int $ignoreStudentId = null): ?string
    {
        $email = trim((string) $email);
        if ($email === '') {
            return null;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $sql = "SELECT student_id FROM student WHERE email = :email";
        $params = ['email' => $email];
        if ($ignoreStudentId !== null && $ignoreStudentId > 0) {
            $sql .= " AND student_id != :ignore_student_id";
            $params['ignore_student_id'] = $ignoreStudentId;
        }
        $sql .= " LIMIT 1";

        return db()->fetch($sql, $params) ? null : $email;
    }

    private function normalizeSex(string $sex): string
    {
        $sex = strtolower(trim($sex));
        if (in_array($sex, ['m', 'male'], true)) {
            return 'male';
        }
        if (in_array($sex, ['f', 'female'], true)) {
            return 'female';
        }
        return $sex;
    }

    private function collectBulkStudentLines(): array
    {
        $lines = [];
        if (!empty($_FILES['students_csv']['tmp_name']) && is_uploaded_file($_FILES['students_csv']['tmp_name'])) {
            $handle = fopen($_FILES['students_csv']['tmp_name'], 'r');
            if ($handle !== false) {
                while (($row = fgetcsv($handle)) !== false) {
                    $lines[] = $row;
                }
                fclose($handle);
            }
        }

        $blob = trim((string) request('students_blob', ''));
        if ($blob !== '') {
            foreach (preg_split('/\r\n|\r|\n/', $blob) as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $lines[] = str_getcsv($line);
                }
            }
        }
        return $lines;
    }

    private function isBulkHeaderRow(array $parts): bool
    {
        $first = strtolower(trim((string) ($parts[0] ?? '')));
        $second = strtolower(trim((string) ($parts[1] ?? '')));
        return in_array($first, ['student_code', 'code', 'student code'], true)
            || in_array($second, ['full_name', 'fullname', 'name', 'student name'], true);
    }

    private function generateUniqueStudentCode(string $preferred = '', ?int $ignoreStudentId = null): string
    {
        $preferred = strtoupper(trim($preferred));
        $preferred = preg_replace('/[^A-Z0-9\-_]/', '', $preferred) ?: '';

        if ($preferred !== '' && !$this->studentCodeExists($preferred, $ignoreStudentId)) {
            return $preferred;
        }

        if ($preferred !== '') {
            for ($suffix = 2; $suffix <= 9999; $suffix++) {
                $candidate = $preferred . '-' . $suffix;
                if (!$this->studentCodeExists($candidate, $ignoreStudentId)) {
                    return $candidate;
                }
            }
        }

        $rows = db()->fetchAll("SELECT student_code FROM student WHERE student_code LIKE 'STU%' ORDER BY student_id DESC LIMIT 500");
        $max = 0;
        foreach ($rows as $row) {
            if (preg_match('/^STU(\d+)$/', (string) ($row['student_code'] ?? ''), $match)) {
                $max = max($max, (int) $match[1]);
            }
        }
        $next = max(1, $max + 1);
        for ($i = 0; $i < 10000; $i++) {
            $candidate = 'STU' . str_pad((string) ($next + $i), 4, '0', STR_PAD_LEFT);
            if (!$this->studentCodeExists($candidate, $ignoreStudentId)) {
                return $candidate;
            }
        }

        return 'STU' . strtoupper(substr(md5((string) microtime(true)), 0, 8));
    }

    private function studentCodeExists(string $studentCode, ?int $ignoreStudentId = null): bool
    {
        $sql = "SELECT student_id FROM student WHERE student_code = :student_code";
        $params = ['student_code' => $studentCode];
        if ($ignoreStudentId !== null && $ignoreStudentId > 0) {
            $sql .= " AND student_id != :ignore_student_id";
            $params['ignore_student_id'] = $ignoreStudentId;
        }
        $sql .= " LIMIT 1";
        return (bool) db()->fetch($sql, $params);
    }
}
