<?php
declare(strict_types=1);

namespace Services;

class SmsService
{
    public function isEnabled(): bool
    {
        return school_meta('sms_enabled', '0') === '1';
    }

    public function provider(): string
    {
        return strtolower(trim((string) school_meta('sms_provider', 'beem')) ?: 'beem');
    }

    public function maxLength(): int
    {
        $length = (int) school_meta('sms_max_length', '192');
        return $length > 0 ? $length : 192;
    }

    public function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $phone = preg_replace('/[^0-9+]/', '', $phone) ?? '';
        if ($phone === '') {
            return '';
        }
        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }
        if (str_starts_with($phone, '0') && strlen($phone) === 10) {
            return '260' . substr($phone, 1);
        }
        if (str_starts_with($phone, '260')) {
            return $phone;
        }
        if (strlen($phone) === 9 && preg_match('/^[79][0-9]{8}$/', $phone)) {
            return '260' . $phone;
        }
        return $phone;
    }

    public function isValidPhone(string $phone): bool
    {
        $phone = $this->normalizePhone($phone);
        return (bool) preg_match('/^260[0-9]{9}$/', $phone);
    }

    public function send(string $phone, string $message): array
    {
        $phone = $this->normalizePhone($phone);
        $message = $this->limitMessage($message);

        if (!$this->isEnabled()) {
            return ['success' => false, 'status' => 'skipped', 'error' => 'SMS is disabled in settings.', 'response' => null];
        }
        if (!$this->isValidPhone($phone)) {
            return ['success' => false, 'status' => 'skipped', 'error' => 'Invalid Zambian phone number.', 'response' => null];
        }
        if ($message === '') {
            return ['success' => false, 'status' => 'skipped', 'error' => 'SMS message is empty.', 'response' => null];
        }

        return $this->provider() === 'zamtel'
            ? $this->sendWithZamtel($phone, $message)
            : $this->sendWithBeem($phone, $message);
    }

    public function limitMessage(string $message): string
    {
        $message = trim(preg_replace('/\s+/', ' ', $message) ?? '');
        $limit = $this->maxLength();
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($message) > $limit ? rtrim(mb_substr($message, 0, $limit - 3)) . '...' : $message;
        }
        return strlen($message) > $limit ? rtrim(substr($message, 0, $limit - 3)) . '...' : $message;
    }

    private function sendWithBeem(string $phone, string $message): array
    {
        $apiKey = trim((string) school_meta('beem_api_key', ''));
        $secretKey = trim((string) school_meta('beem_secret_key', ''));
        $senderId = trim((string) school_meta('beem_sender_id', school_meta('sms_sender_id', 'INFO')));
        $url = trim((string) school_meta('beem_api_url', 'https://apisms.beem.africa/v1/send')) ?: 'https://apisms.beem.africa/v1/send';

        if ($apiKey === '' || $secretKey === '' || $senderId === '') {
            return ['success' => false, 'status' => 'failed', 'error' => 'Beem API key, secret key, or sender ID is missing.', 'response' => null];
        }

        $payload = [
            'source_addr' => $senderId,
            'schedule_time' => '',
            'encoding' => 0,
            'message' => $message,
            'recipients' => [[
                'recipient_id' => '1',
                'dest_addr' => $phone,
            ]],
        ];

        return $this->httpJson($url, $payload, [
            'Authorization: Basic ' . base64_encode($apiKey . ':' . $secretKey),
            'Content-Type: application/json',
        ]);
    }

    private function sendWithZamtel(string $phone, string $message): array
    {
        $url = trim((string) school_meta('zamtel_api_url', ''));
        $username = trim((string) school_meta('zamtel_username', ''));
        $password = trim((string) school_meta('zamtel_password', ''));
        $senderId = trim((string) school_meta('zamtel_sender_id', school_meta('sms_sender_id', 'INFO')));

        if ($url === '' || $username === '' || $password === '' || $senderId === '') {
            return ['success' => false, 'status' => 'failed', 'error' => 'Zamtel API URL, username, password, or sender ID is missing.', 'response' => null];
        }

        $payload = [
            'username' => $username,
            'password' => $password,
            'sender_id' => $senderId,
            'to' => $phone,
            'message' => $message,
        ];

        return $this->httpJson($url, $payload, ['Content-Type: application/json']);
    }

    private function httpJson(string $url, array $payload, array $headers): array
    {
        $response = null;
        $httpCode = 0;
        $error = null;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_TIMEOUT => 30,
            ]);
            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($response === false) {
                $error = curl_error($ch);
            }
            curl_close($ch);
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => implode("\r\n", $headers),
                    'content' => json_encode($payload),
                    'timeout' => 30,
                    'ignore_errors' => true,
                ],
            ]);
            $response = @file_get_contents($url, false, $context);
            $httpCode = 0;
            if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
                $httpCode = (int) $m[1];
            }
            if ($response === false) {
                $error = 'HTTP request failed.';
            }
        }

        $ok = $httpCode >= 200 && $httpCode < 300 && $error === null;
        return [
            'success' => $ok,
            'status' => $ok ? 'sent' : 'failed',
            'error' => $ok ? null : ($error ?: 'SMS provider returned HTTP ' . $httpCode),
            'response' => is_string($response) ? $response : null,
            'http_code' => $httpCode,
        ];
    }
}
