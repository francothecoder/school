<?php
declare(strict_types=1);

namespace Services;

class EmailService
{
    public function isEnabled(): bool
    {
        return \school_meta('email_results_enabled', '0') === '1';
    }

    public function fromAddress(): string
    {
        return trim((string) \school_meta('email_from_address', \school_meta('mail_from_address', \school_meta('system_email', ''))));
    }

    public function fromName(): string
    {
        return trim((string) \school_meta('email_from_name', \school_meta('system_name', 'LearnTrack Pro')));
    }

    public function isValidEmail(string $email): bool
    {
        $email = trim($email);
        if ($email === '' || str_ends_with(strtolower($email), '@school.local')) {
            return false;
        }
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function sendWithPdf(string $to, string $subject, string $htmlBody, string $pdfBinary, string $pdfFileName): array
    {
        $to = trim($to);
        if (!$this->isEnabled()) {
            return ['success' => false, 'status' => 'skipped', 'error' => 'Email results are disabled in settings.', 'response' => null];
        }
        if (!$this->isValidEmail($to)) {
            return ['success' => false, 'status' => 'skipped', 'error' => 'Invalid recipient email address.', 'response' => null];
        }
        $from = $this->fromAddress();
        if (!$this->isValidEmail($from)) {
            return ['success' => false, 'status' => 'failed', 'error' => 'Sender email address is missing or invalid in settings.', 'response' => null];
        }
        if ($pdfBinary === '') {
            return ['success' => false, 'status' => 'failed', 'error' => 'PDF attachment was not generated.', 'response' => null];
        }

        $boundary = '=_LearnTrack_' . bin2hex(random_bytes(12));
        $safeFileName = preg_replace('/[^A-Za-z0-9_\-.]+/', '-', $pdfFileName) ?: 'report-card.pdf';
        if (!str_ends_with(strtolower($safeFileName), '.pdf')) {
            $safeFileName .= '.pdf';
        }

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'From: ' . $this->encodeHeader($this->fromName()) . ' <' . $from . '>';
        $headers[] = 'Reply-To: ' . $from;
        $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

        $body = '';
        $body .= '--' . $boundary . "\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $htmlBody . "\r\n\r\n";
        $body .= '--' . $boundary . "\r\n";
        $body .= 'Content-Type: application/pdf; name="' . $safeFileName . '"' . "\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= 'Content-Disposition: attachment; filename="' . $safeFileName . '"' . "\r\n\r\n";
        $body .= chunk_split(base64_encode($pdfBinary)) . "\r\n";
        $body .= '--' . $boundary . "--\r\n";

        $ok = @mail($to, $this->encodeHeader($subject), $body, implode("\r\n", $headers));
        return [
            'success' => (bool) $ok,
            'status' => $ok ? 'sent' : 'failed',
            'error' => $ok ? null : 'PHP mail() failed. Check server SMTP/mail configuration.',
            'response' => $ok ? 'mail() accepted message' : null,
        ];
    }

    private function encodeHeader(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}
