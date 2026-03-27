<?php
declare(strict_types=1);

namespace Core;

class Auth
{
    public static function attempt(string $login, string $password, string $portal): bool
    {
        $db = db();

        if ($portal === 'admin') {
            $user = $db->fetch("SELECT admin_id AS id, name, email, password FROM admin WHERE email = :login LIMIT 1", ['login' => $login]);
            return self::finish($user, $password, 'admin');
        }

        if ($portal === 'teacher') {
            $user = $db->fetch("SELECT teacher_id AS id, name, email, password FROM teacher WHERE email = :login LIMIT 1", ['login' => $login]);
            return self::finish($user, $password, 'teacher');
        }

        if ($portal === 'student') {
            $user = $db->fetch("SELECT student_id AS id, name, email, student_code, password FROM student WHERE email = :login OR student_code = :login LIMIT 1", ['login' => $login]);
            return self::finish($user, $password, 'student');
        }

        return false;
    }

    private static function finish(?array $user, string $password, string $role): bool
    {
        if (!$user || !self::verifyPassword($password, (string) ($user['password'] ?? ''))) {
            return false;
        }

        $_SESSION['auth'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'] ?? 'User',
            'email' => $user['email'] ?? '',
            'role' => $role,
        ];
        return true;
    }

    public static function verifyPassword(string $plain, string $stored): bool
    {
        if ($stored === '') {
            return $plain === '';
        }

        if (strlen($stored) === 40 && ctype_xdigit($stored)) {
            return sha1($plain) === strtolower($stored);
        }

        return password_verify($plain, $stored);
    }

    public static function makePassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    public static function logout(): void
    {
        unset($_SESSION['auth']);
        session_regenerate_id(true);
    }
}
