<?php
declare(strict_types=1);

namespace Controllers;

use Services\SmsService;

class SmsController extends BaseController
{
    private SmsService $sms;

    public function __construct()
    {
        $this->sms = new SmsService();
    }

    public function results(): void
    {
        require_auth(['admin']);
        ensure_support_tables();

        $year = (string) request('year', current_year());
        $classId = (int) request('class_id');
        $examId = (int) request('exam_id');
        $studentId = (int) request('student_id');
        $customPhone = trim((string) request('phone', ''));
        $sendMode = (string) request('send_mode', 'class');
        $action = (string) request('action', 'preview');

        $classes = db()->fetchAll("SELECT * FROM class ORDER BY name_numeric + 0, name");
        $exams = db()->fetchAll("SELECT * FROM exam WHERE year = :year ORDER BY exam_id DESC", ['year' => $year]);
        $studentsForSelect = $classId > 0 ? $this->studentsInClass($classId, $year) : [];

        $previewRows = [];
        $summary = null;

        if (is_post() && $examId > 0 && ($sendMode === 'class' ? $classId > 0 : $studentId > 0)) {
            $previewRows = $sendMode === 'class'
                ? $this->prepareClassMessages($classId, $examId, $year)
                : $this->prepareSingleMessage($studentId, $examId, $year, $customPhone);

            $summary = $this->summarizeRows($previewRows);

            if ($action === 'send') {
                $summary = $this->sendRows($previewRows, $examId, $classId, $year, $sendMode);
                flash('success', 'SMS processing complete. Sent: ' . $summary['sent'] . ', Failed: ' . $summary['failed'] . ', Skipped: ' . $summary['skipped'] . '.');
                redirect('/sms/results?year=' . urlencode($year) . '&class_id=' . $classId . '&exam_id=' . $examId . '&send_mode=' . urlencode($sendMode));
            }
        }

        $title = 'Send Results by SMS';
        $this->render('sms/results', compact('title', 'year', 'classId', 'examId', 'studentId', 'customPhone', 'sendMode', 'classes', 'exams', 'studentsForSelect', 'previewRows', 'summary'));
    }

    public function logs(): void
    {
        require_auth(['admin']);
        ensure_support_tables();
        $logs = db()->fetchAll("SELECT l.*, st.name AS student_name, ex.name AS exam_name, c.name AS class_name
            FROM sms_logs l
            LEFT JOIN student st ON st.student_id = l.student_id
            LEFT JOIN exam ex ON ex.exam_id = l.exam_id
            LEFT JOIN class c ON c.class_id = l.class_id
            ORDER BY l.id DESC LIMIT 300");
        $title = 'SMS Logs';
        $this->render('sms/logs', compact('title', 'logs'));
    }

    private function studentsInClass(int $classId, string $year): array
    {
        return db()->fetchAll("SELECT st.student_id, st.student_code, st.name, st.phone, e.roll
            FROM enroll e
            INNER JOIN student st ON st.student_id = e.student_id
            WHERE e.class_id = :class_id AND e.year = :year
            ORDER BY st.name ASC, COALESCE(e.roll, 999999)", ['class_id' => $classId, 'year' => $year]);
    }

    private function prepareClassMessages(int $classId, int $examId, string $year): array
    {
        $rows = [];
        foreach ($this->studentsInClass($classId, $year) as $student) {
            $rows[] = $this->buildMessageRow((int) $student['student_id'], $examId, $year, (string) ($student['phone'] ?? ''), $classId);
        }
        return $rows;
    }

    private function prepareSingleMessage(int $studentId, int $examId, string $year, string $customPhone): array
    {
        $student = db()->fetch("SELECT st.*, e.class_id FROM student st LEFT JOIN enroll e ON e.student_id = st.student_id AND e.year = :year WHERE st.student_id = :student_id ORDER BY e.enroll_id DESC LIMIT 1", [
            'student_id' => $studentId,
            'year' => $year,
        ]);
        if (!$student) {
            return [];
        }
        $phone = $customPhone !== '' ? $customPhone : (string) ($student['phone'] ?? '');
        return [$this->buildMessageRow($studentId, $examId, $year, $phone, (int) ($student['class_id'] ?? 0))];
    }

    private function buildMessageRow(int $studentId, int $examId, string $year, string $phone, int $classId = 0): array
    {
        $student = db()->fetch("SELECT * FROM student WHERE student_id = :id LIMIT 1", ['id' => $studentId]);
        $exam = db()->fetch("SELECT * FROM exam WHERE exam_id = :id LIMIT 1", ['id' => $examId]);
        $marks = db()->fetchAll("SELECT s.name AS subject_name, m.mark_obtained, m.mark_total
            FROM mark m
            INNER JOIN subject s ON s.subject_id = m.subject_id
            WHERE m.student_id = :student_id AND m.exam_id = :exam_id AND m.year = :year
            ORDER BY s.name", ['student_id' => $studentId, 'exam_id' => $examId, 'year' => $year]);

        $message = $this->composeResultMessage((string) ($student['name'] ?? 'Student'), (string) ($exam['name'] ?? 'Exam'), $marks);
        $normalizedPhone = $this->sms->normalizePhone($phone);
        $status = 'ready';
        $reason = '';
        if (!$this->sms->isValidPhone($phone)) {
            $status = 'skipped';
            $reason = 'Missing or invalid phone number';
        } elseif (!$marks) {
            $status = 'skipped';
            $reason = 'No marks found for selected exam';
        } elseif (!$this->sms->isEnabled()) {
            $status = 'skipped';
            $reason = 'SMS is disabled in settings';
        }

        return [
            'student_id' => $studentId,
            'student_name' => (string) ($student['name'] ?? ''),
            'student_code' => (string) ($student['student_code'] ?? ''),
            'phone' => $normalizedPhone,
            'raw_phone' => $phone,
            'class_id' => $classId,
            'exam_id' => $examId,
            'exam_name' => (string) ($exam['name'] ?? ''),
            'message' => $message,
            'status' => $status,
            'reason' => $reason,
            'length' => strlen($message),
        ];
    }

    private function composeResultMessage(string $studentName, string $examName, array $marks): string
    {
        $firstName = trim(explode(' ', trim($studentName))[0] ?? $studentName);
        $examShort = $this->shortenExamName($examName);
        $parts = [];
        foreach ($marks as $mark) {
            $subject = $this->shortenSubject((string) ($mark['subject_name'] ?? 'Sub'));
            $obtained = \format_mark($mark['mark_obtained'] ?? null);
            $total = \format_mark($mark['mark_total'] ?? 100);
            $parts[] = $total !== '100' ? $subject . $obtained . '/' . $total : $subject . $obtained;
        }
        $message = trim($firstName . ' - ' . $examShort . ': ' . implode(' ', $parts));
        if (trim((string) school_meta('sms_footer', 'LearnTrack Pro')) !== '') {
            $message .= '. ' . trim((string) school_meta('sms_footer', 'LearnTrack Pro'));
        }
        return $this->sms->limitMessage($message);
    }

    private function shortenExamName(string $name): string
    {
        $name = trim($name);
        $replace = [
            'END OF TERM' => 'EOT',
            'END TERM' => 'EOT',
            'MID TERM' => 'MID',
            'EXAMINATION' => 'EXAM',
            'ASSESSMENT' => 'ASSMT',
        ];
        return trim(str_ireplace(array_keys($replace), array_values($replace), $name));
    }

    private function shortenSubject(string $name): string
    {
        $map = [
            'ENGLISH' => 'Eng', 'MATHEMATICS' => 'Math', 'MATHS' => 'Math', 'SCIENCE' => 'Sci',
            'INTEGRATED SCIENCE' => 'Sci', 'BIOLOGY' => 'Bio', 'CHEMISTRY' => 'Chem', 'PHYSICS' => 'Phy',
            'GEOGRAPHY' => 'Geo', 'HISTORY' => 'Hist', 'CIVIC EDUCATION' => 'Civic',
            'SOCIAL STUDIES' => 'Soc', 'RELIGIOUS EDUCATION' => 'RE', 'COMPUTER STUDIES' => 'ICT',
            'INFORMATION AND COMMUNICATION TECHNOLOGY' => 'ICT', 'BUSINESS STUDIES' => 'Bus',
            'COMMERCE' => 'Com', 'ACCOUNTS' => 'Acc', 'AGRICULTURAL SCIENCE' => 'Agric',
        ];
        $upper = strtoupper(trim($name));
        if (isset($map[$upper])) {
            return $map[$upper];
        }
        $words = preg_split('/\s+/', trim($name)) ?: [];
        if (count($words) > 1) {
            $abbr = '';
            foreach ($words as $word) {
                $abbr .= strtoupper(substr($word, 0, 1));
            }
            return substr($abbr, 0, 5);
        }
        return substr($name, 0, 5);
    }

    private function summarizeRows(array $rows): array
    {
        $summary = ['total' => count($rows), 'ready' => 0, 'skipped' => 0, 'sent' => 0, 'failed' => 0];
        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? 'ready');
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }
        return $summary;
    }

    private function sendRows(array $rows, int $examId, int $classId, string $year, string $sendMode): array
    {
        $summary = ['total' => count($rows), 'ready' => 0, 'skipped' => 0, 'sent' => 0, 'failed' => 0];
        foreach ($rows as $row) {
            if (($row['status'] ?? '') !== 'ready') {
                $summary['skipped']++;
                $this->recordLog($row, 'skipped', (string) ($row['reason'] ?? 'Skipped'), null, $sendMode, $year);
                continue;
            }
            $result = $this->sms->send((string) $row['phone'], (string) $row['message']);
            $status = $result['success'] ? 'sent' : (($result['status'] ?? '') === 'skipped' ? 'skipped' : 'failed');
            $summary[$status]++;
            $this->recordLog($row, $status, (string) ($result['error'] ?? ''), (string) ($result['response'] ?? ''), $sendMode, $year);
        }
        log_activity([
            'action' => 'send',
            'module_name' => 'sms_results',
            'record_id' => $examId,
            'description' => 'Processed result SMS batch for exam #' . $examId . ', class #' . $classId . '. Sent: ' . $summary['sent'] . ', Failed: ' . $summary['failed'] . ', Skipped: ' . $summary['skipped'],
            'new_values' => json_encode($summary),
        ]);
        return $summary;
    }

    private function recordLog(array $row, string $status, string $error, ?string $providerResponse, string $sendMode, string $year): void
    {
        db()->execute("INSERT INTO sms_logs (student_id, class_id, exam_id, year, phone, message, provider, send_mode, status, error_message, provider_response, sent_by_role, sent_by_id, sent_by_name)
            VALUES (:student_id, :class_id, :exam_id, :year, :phone, :message, :provider, :send_mode, :status, :error_message, :provider_response, :sent_by_role, :sent_by_id, :sent_by_name)", [
            'student_id' => (int) ($row['student_id'] ?? 0),
            'class_id' => (int) ($row['class_id'] ?? 0),
            'exam_id' => (int) ($row['exam_id'] ?? 0),
            'year' => $year,
            'phone' => (string) ($row['phone'] ?? ''),
            'message' => (string) ($row['message'] ?? ''),
            'provider' => $this->sms->provider(),
            'send_mode' => $sendMode,
            'status' => $status,
            'error_message' => $error,
            'provider_response' => $providerResponse,
            'sent_by_role' => auth_role(),
            'sent_by_id' => auth_id(),
            'sent_by_name' => auth_name(),
        ]);
    }
}
