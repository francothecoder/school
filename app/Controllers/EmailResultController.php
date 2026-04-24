<?php
declare(strict_types=1);

namespace Controllers;

use Services\EmailService;
use Services\ReportPdfService;
use Services\ResultService;
use Services\SimplePdf;

class EmailResultController extends BaseController
{
    private EmailService $email;
    private ResultService $results;
    private ReportPdfService $pdf;

    public function __construct()
    {
        $this->email = new EmailService();
        $this->results = new ResultService();
        $this->pdf = new ReportPdfService();
    }

    public function results(): void
    {
        require_auth(['admin']);
        ensure_support_tables();

        $year = (string) request('year', current_year());
        $classId = (int) request('class_id');
        $examId = (int) request('exam_id');
        $term = trim((string) request('term', ''));
        $studentId = (int) request('student_id');
        $customEmail = trim((string) request('email', ''));
        $sendMode = (string) request('send_mode', 'class');
        $viewMode = (string) request('view_mode', 'exam');
        $emailType = (string) request('email_type', 'report_card');
        $action = (string) request('action', 'preview');

        if ($viewMode !== 'term') {
            $term = '';
        }

        $classes = db()->fetchAll("SELECT * FROM class ORDER BY name_numeric + 0, name");
        $exams = db()->fetchAll("SELECT * FROM exam WHERE year = :year ORDER BY exam_id DESC", ['year' => $year]);
        $termsRaw = db()->fetchAll("SELECT DISTINCT exam_term FROM exam WHERE year = :year AND exam_term IS NOT NULL AND exam_term <> '' ORDER BY exam_term", ['year' => $year]);
        $terms = [];
        foreach ($termsRaw as $row) {
            $val = trim((string) ($row['exam_term'] ?? ''));
            if ($val !== '' && !in_array($val, $terms, true)) {
                $terms[] = $val;
            }
        }
        $studentsForSelect = $classId > 0 ? $this->studentsInClass($classId, $year) : [];

        $previewRows = [];
        $summary = null;
        $selectionOk = $sendMode === 'class' ? $classId > 0 : $studentId > 0;
        $reportOk = $viewMode === 'term' ? $term !== '' : $examId > 0;

        if (is_post() && $selectionOk && $reportOk) {
            $previewRows = $sendMode === 'class'
                ? $this->prepareClassEmails($classId, $year, $examId, $term, $viewMode, $emailType)
                : $this->prepareSingleEmail($studentId, $year, $examId, $term, $viewMode, $emailType, $customEmail);
            $summary = $this->summarizeRows($previewRows);

            if ($action === 'send') {
                $summary = $this->sendRows($previewRows, $year, $examId, $classId, $sendMode, $emailType);
                flash('success', 'Email processing complete. Sent: ' . $summary['sent'] . ', Failed: ' . $summary['failed'] . ', Skipped: ' . $summary['skipped'] . '.');
                redirect('/email-results?year=' . urlencode($year) . '&class_id=' . $classId . '&exam_id=' . $examId . '&term=' . urlencode($term) . '&view_mode=' . urlencode($viewMode) . '&email_type=' . urlencode($emailType) . '&send_mode=' . urlencode($sendMode));
            }
        }

        $title = 'Send Results by Email';
        $this->render('email-results/results', compact('title', 'year', 'classId', 'examId', 'term', 'studentId', 'customEmail', 'sendMode', 'viewMode', 'emailType', 'classes', 'exams', 'terms', 'studentsForSelect', 'previewRows', 'summary'));
    }

    public function logs(): void
    {
        require_auth(['admin']);
        ensure_support_tables();
        $logs = db()->fetchAll("SELECT l.*, st.name AS student_name, ex.name AS exam_name, c.name AS class_name
            FROM email_logs l
            LEFT JOIN student st ON st.student_id = l.student_id
            LEFT JOIN exam ex ON ex.exam_id = l.exam_id
            LEFT JOIN class c ON c.class_id = l.class_id
            ORDER BY l.id DESC LIMIT 300");
        $title = 'Email Logs';
        $this->render('email-results/logs', compact('title', 'logs'));
    }

    private function studentsInClass(int $classId, string $year): array
    {
        return db()->fetchAll("SELECT st.student_id, st.student_code, st.name, st.email, e.roll
            FROM enroll e
            INNER JOIN student st ON st.student_id = e.student_id
            WHERE e.class_id = :class_id AND e.year = :year
            ORDER BY st.name ASC, COALESCE(e.roll, 999999)", ['class_id' => $classId, 'year' => $year]);
    }

    private function prepareClassEmails(int $classId, string $year, int $examId, string $term, string $viewMode, string $emailType): array
    {
        $rows = [];
        foreach ($this->studentsInClass($classId, $year) as $student) {
            $rows[] = $this->buildEmailRow((int) $student['student_id'], $year, $examId, $term, $viewMode, $emailType, (string) ($student['email'] ?? ''), $classId);
        }
        return $rows;
    }

    private function prepareSingleEmail(int $studentId, string $year, int $examId, string $term, string $viewMode, string $emailType, string $customEmail): array
    {
        $student = db()->fetch("SELECT st.*, e.class_id FROM student st LEFT JOIN enroll e ON e.student_id = st.student_id AND e.year = :year WHERE st.student_id = :student_id ORDER BY e.enroll_id DESC LIMIT 1", [
            'student_id' => $studentId,
            'year' => $year,
        ]);
        if (!$student) {
            return [];
        }
        $recipient = $customEmail !== '' ? $customEmail : (string) ($student['email'] ?? '');
        return [$this->buildEmailRow($studentId, $year, $examId, $term, $viewMode, $emailType, $recipient, (int) ($student['class_id'] ?? 0))];
    }

    private function buildEmailRow(int $studentId, string $year, int $examId, string $term, string $viewMode, string $emailType, string $recipient, int $classId = 0): array
    {
        $payload = $this->results->buildStudentPayload($studentId, $year, $viewMode === 'term' ? 0 : $examId, $viewMode === 'term' ? $term : '');
        $student = $payload['student'] ?? [];
        $subject = ((string) \school_meta('system_name', 'LearnTrack Pro')) . ' - ' . ($emailType === 'marks' ? 'Marks Summary' : 'Report Card') . ' - ' . (string) ($student['name'] ?? 'Student');
        $attachmentName = $emailType === 'marks'
            ? $this->marksFileName($payload)
            : $this->pdf->fileName($payload);
        $status = 'ready';
        $reason = '';
        if (!$this->email->isEnabled()) {
            $status = 'skipped';
            $reason = 'Email results are disabled in settings';
        } elseif (!$this->email->isValidEmail($recipient)) {
            $status = 'skipped';
            $reason = 'Missing or invalid email address';
        } elseif (empty($payload['rows'])) {
            $status = 'skipped';
            $reason = 'No marks found for selected report';
        }

        return [
            'student_id' => $studentId,
            'student_name' => (string) ($student['name'] ?? ''),
            'student_code' => (string) ($student['student_code'] ?? ''),
            'recipient_email' => $recipient,
            'class_id' => $classId,
            'exam_id' => $viewMode === 'term' ? 0 : $examId,
            'year' => $year,
            'view_mode' => $viewMode,
            'term' => $term,
            'email_type' => $emailType,
            'subject' => $subject,
            'attachment_name' => $attachmentName,
            'status' => $status,
            'reason' => $reason,
            'preview' => $this->previewText($payload, $emailType),
            'payload' => $payload,
        ];
    }

    private function previewText(array $payload, string $emailType): string
    {
        if ($emailType === 'marks') {
            $parts = [];
            foreach (($payload['rows'] ?? []) as $row) {
                $parts[] = (string) ($row['subject_name'] ?? '-') . ': ' . \format_mark($row['score'] ?? null);
            }
            return implode(', ', array_slice($parts, 0, 8));
        }
        return (string) ($payload['reportLabel'] ?? 'Report Card') . ' with ' . count($payload['rows'] ?? []) . ' subject row(s).';
    }

    private function sendRows(array $rows, string $year, int $examId, int $classId, string $sendMode, string $emailType): array
    {
        $summary = ['total' => count($rows), 'ready' => 0, 'skipped' => 0, 'sent' => 0, 'failed' => 0];
        foreach ($rows as $row) {
            if (($row['status'] ?? '') !== 'ready') {
                $summary['skipped']++;
                $this->recordLog($row, 'skipped', (string) ($row['reason'] ?? 'Skipped'), null, $sendMode);
                continue;
            }

            $payload = $row['payload'];
            if (($row['email_type'] ?? '') === 'marks') {
                $pdfBinary = $this->generateMarksPdf($payload);
                $html = $this->marksEmailBody($payload);
            } else {
                $pdfBinary = $this->pdf->generate($payload);
                $html = $this->pdf->emailBody($payload);
            }
            $result = $this->email->sendWithPdf((string) $row['recipient_email'], (string) $row['subject'], $html, $pdfBinary, (string) $row['attachment_name']);
            $status = $result['success'] ? 'sent' : (($result['status'] ?? '') === 'skipped' ? 'skipped' : 'failed');
            $summary[$status]++;
            $this->recordLog($row, $status, (string) ($result['error'] ?? ''), (string) ($result['response'] ?? ''), $sendMode);
        }
        log_activity([
            'action' => 'send',
            'module_name' => 'email_results',
            'record_id' => $examId,
            'description' => 'Processed result emails. Sent: ' . $summary['sent'] . ', Failed: ' . $summary['failed'] . ', Skipped: ' . $summary['skipped'],
            'new_values' => json_encode(['year' => $year, 'class_id' => $classId, 'send_mode' => $sendMode, 'email_type' => $emailType]),
        ]);
        return $summary;
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

    private function generateMarksPdf(array $payload): string
    {
        $pdf = new SimplePdf();
        return $pdf->output($this->marksLines($payload), 'Marks Summary');
    }

    private function marksFileName(array $payload): string
    {
        $student = $payload['student'] ?? [];
        $safe = preg_replace('/[^A-Za-z0-9_\-]+/', '-', (string) ($student['student_code'] ?? 'student')) ?: 'student';
        return 'marks-summary-' . $safe . '.pdf';
    }

    private function marksEmailBody(array $payload): string
    {
        $student = $payload['student'] ?? [];
        return '<div style="font-family:Arial,sans-serif;font-size:14px;color:#222"><p>Dear Parent/Guardian,</p><p>Please find attached the marks summary for <strong>' . htmlspecialchars((string) ($student['name'] ?? 'Student'), ENT_QUOTES, 'UTF-8') . '</strong>.</p><p>Regards,<br>' . htmlspecialchars(\school_meta('report_school_name', \school_meta('system_name', 'LearnTrack Pro')), ENT_QUOTES, 'UTF-8') . '</p></div>';
    }

    private function marksLines(array $payload): array
    {
        $student = $payload['student'] ?? [];
        $lines = [];
        $lines[] = strtoupper(\school_meta('report_school_name', \school_meta('system_name', 'LEARNTRACK PRO')));
        $lines[] = 'MARKS SUMMARY';
        $lines[] = str_repeat('-', 70);
        $lines[] = 'Student: ' . (string) ($student['name'] ?? '-');
        $lines[] = 'Student Code: ' . (string) ($student['student_code'] ?? '-');
        $lines[] = 'Report: ' . (string) ($payload['reportLabel'] ?? '-');
        $lines[] = 'Academic Year: ' . (string) ($payload['year'] ?? '-');
        $lines[] = str_repeat('-', 70);
        foreach (($payload['rows'] ?? []) as $row) {
            $lines[] = str_pad(substr((string) ($row['subject_name'] ?? '-'), 0, 35), 38) . \format_mark($row['score'] ?? null);
        }
        $lines[] = str_repeat('-', 70);
        $lines[] = 'Powered by LearnTrack Pro';
        return $lines;
    }

    private function recordLog(array $row, string $status, string $error = '', ?string $response = null, string $sendMode = 'class'): void
    {
        try {
            db()->execute("INSERT INTO email_logs (student_id, class_id, exam_id, year, recipient_email, email_type, subject, attachment_name, send_mode, status, error_message, provider_response, sent_by_role, sent_by_id, sent_by_name)
                VALUES (:student_id, :class_id, :exam_id, :year, :recipient_email, :email_type, :subject, :attachment_name, :send_mode, :status, :error_message, :provider_response, :sent_by_role, :sent_by_id, :sent_by_name)", [
                'student_id' => $row['student_id'] ?? null,
                'class_id' => $row['class_id'] ?? null,
                'exam_id' => $row['exam_id'] ?: null,
                'year' => $row['year'] ?? '',
                'recipient_email' => (string) ($row['recipient_email'] ?? ''),
                'email_type' => (string) ($row['email_type'] ?? 'report_card'),
                'subject' => (string) ($row['subject'] ?? ''),
                'attachment_name' => (string) ($row['attachment_name'] ?? ''),
                'send_mode' => $sendMode,
                'status' => $status,
                'error_message' => $error,
                'provider_response' => $response,
                'sent_by_role' => auth_role(),
                'sent_by_id' => auth_id(),
                'sent_by_name' => auth_name(),
            ]);
        } catch (\Throwable $e) {
            // Never break sending because of logs.
        }
    }
}
