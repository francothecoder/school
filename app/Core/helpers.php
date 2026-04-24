<?php
declare(strict_types=1);

function db(): Core\Database {
    return $GLOBALS['db'];
}

function config(string $key, $default = null) {
    global $config;
    $segments = explode('.', $key);
    $value = $config;
    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function base_url(string $path = ''): string {
    $base = rtrim((string) config('app.base_url', ''), '/');
    return $base . $path;
}

function redirect(string $path): void {
    header('Location: ' . base_url($path));
    exit;
}

function view(string $view, array $data = []): void {
    extract($data);
    $viewFile = __DIR__ . '/../Views/' . $view . '.php';
    require __DIR__ . '/../Views/layouts/app.php';
}

function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}


function format_mark($value): string
{
    if ($value === null || $value === '') {
        return '-';
    }
    if (!is_numeric($value)) {
        return (string) $value;
    }
    $float = (float) $value;
    if (abs($float - round($float)) < 0.00001) {
        return (string) (int) round($float);
    }
    return rtrim(rtrim(number_format($float, 2, '.', ''), '0'), '.');
}
function request(string $key, $default = null) {
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function flash(string $key, ?string $message = null): ?string {
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $value;
}

function is_post(): bool {
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function current_user(): ?array {
    return $_SESSION['auth'] ?? null;
}

function auth_check(): bool {
    return isset($_SESSION['auth']);
}

function require_auth(array $roles = []): void {
    if (!auth_check()) {
        redirect('/login');
    }
    if ($roles && !in_array(current_user()['role'], $roles, true)) {
        flash('error', 'You do not have permission to access that page.');
        redirect('/dashboard');
    }
}

function years_list(): array {
    $current = (int) date('Y');
    return [
        ($current - 1) . '-' . $current,
        $current . '-' . ($current + 1),
        ($current + 1) . '-' . ($current + 2),
    ];
}


function default_grading_scale(): array {
    return [
        ['from' => 75, 'to' => 100, 'point' => 1, 'label' => 'DISTINCTION'],
        ['from' => 70, 'to' => 74, 'point' => 2, 'label' => 'DISTINCTION'],
        ['from' => 65, 'to' => 69, 'point' => 3, 'label' => 'MERIT'],
        ['from' => 60, 'to' => 64, 'point' => 4, 'label' => 'MERIT'],
        ['from' => 55, 'to' => 59, 'point' => 5, 'label' => 'CREDIT'],
        ['from' => 50, 'to' => 54, 'point' => 6, 'label' => 'CREDIT'],
        ['from' => 45, 'to' => 49, 'point' => 7, 'label' => 'SATISFACTORY'],
        ['from' => 40, 'to' => 44, 'point' => 8, 'label' => 'SATISFACTORY'],
        ['from' => 0, 'to' => 39, 'point' => 9, 'label' => 'UNSATISFACTORY'],
    ];
}

function grading_scale_lines_from_scale(array $scale): string {
    $lines = [];
    foreach ($scale as $band) {
        $lines[] = implode('|', [
            (string) ($band['from'] ?? ''),
            (string) ($band['to'] ?? ''),
            (string) ($band['point'] ?? ''),
            (string) ($band['label'] ?? ''),
        ]);
    }
    return implode("\n", $lines);
}

function parse_grading_scale_lines(?string $raw): array {
    $raw = trim((string) $raw);
    if ($raw === '') {
        return default_grading_scale();
    }
    $lines = preg_split('/\r\n|\r|\n/', $raw);
    $scale = [];
    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line));
        if (count($parts) < 4) {
            continue;
        }
        [$from, $to, $point, $label] = $parts;
        if (!is_numeric($from) || !is_numeric($to) || !is_numeric($point)) {
            continue;
        }
        $scale[] = [
            'from' => (int) $from,
            'to' => (int) $to,
            'point' => (int) $point,
            'label' => (string) $label,
        ];
    }
    if (!$scale) {
        return default_grading_scale();
    }
    usort($scale, function($a, $b) {
        if ((int) $a['from'] === (int) $b['from']) {
            return (int) $b['to'] <=> (int) $a['to'];
        }
        return (int) $b['from'] <=> (int) $a['from'];
    });
    return $scale;
}

function grading_scale(): array {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $raw = school_meta('grading_scale_lines', '');
    $cached = parse_grading_scale_lines($raw);
    return $cached;
}

function grade_band_for_mark(?int $mark): array {
    if ($mark === null) {
        return ['name' => '-', 'point' => '-'];
    }
    foreach (grading_scale() as $band) {
        if ($mark >= (int) $band['from'] && $mark <= (int) $band['to']) {
            return [
                'name' => (string) ($band['label'] ?? '-'),
                'point' => (string) ($band['point'] ?? '-'),
            ];
        }
    }
    return ['name' => '-', 'point' => '-'];
}

function mark_grade(?int $mark, string $schoolType = 'SENIOR'): array {
    $graded = grade_band_for_mark($mark);
    if (($graded['name'] ?? '-') !== '-') {
        return $graded;
    }
    if ($mark === null) return ['name' => '-', 'point' => '-'];
    $schoolType = strtoupper($schoolType);
    $grade = db()->fetch(
        "SELECT * FROM grade WHERE comment = :comment AND :mark BETWEEN mark_from AND mark_upto LIMIT 1",
        ['comment' => $schoolType, 'mark' => $mark]
    );
    if (!$grade) {
        return ['name' => '-', 'point' => '-'];
    }
    return ['name' => $grade['name'] ?? '-', 'point' => $grade['grade_point'] ?? '-'];
}



function school_settings(): array
{
    static $settings = null;
    if ($settings === null) {
        $rows = db()->fetchAll("SELECT type, description FROM settings");
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['type']] = $row['description'];
        }
    }
    return $settings;
}

function school_meta(string $type, string $default = ''): string {
    $settings = school_settings();
    return $settings[$type] ?? $default;
}


function passing_mark(): float {
    $raw = school_meta('passing_mark', '40');
    return is_numeric($raw) ? (float) $raw : 40.0;
}

function current_year(): string {
    $year = request('year');
    if ($year) {
        return (string) $year;
    }
    $latest = db()->fetch("SELECT year FROM enroll WHERE year IS NOT NULL AND year <> '' ORDER BY enroll_id DESC LIMIT 1");
    return $latest['year'] ?? years_list()[1];
}

function school_stage_by_class(?string $nameNumeric): string {
    $level = (int) preg_replace('/\D+/', '', (string) $nameNumeric);
    return $level >= 10 ? 'SENIOR' : 'JUNIOR';
}

function teacher_can_manage_subject(int $teacherId, int $subjectId, int $classId = 0, string $year = ''): bool
{
    if ($teacherId <= 0 || $subjectId <= 0) {
        return false;
    }

    $params = ['teacher_id' => $teacherId, 'subject_id' => $subjectId];
    $sql = "SELECT subject_id FROM subject WHERE teacher_id = :teacher_id AND subject_id = :subject_id";
    if ($classId > 0) {
        $sql .= " AND class_id = :class_id";
        $params['class_id'] = $classId;
    }
    if ($year !== '') {
        $sql .= " AND year = :year";
        $params['year'] = $year;
    }
    $row = db()->fetch($sql . " LIMIT 1", $params);
    return (bool) $row;
}

function sections_by_class(int $classId): array
{
    return db()->fetchAll("SELECT * FROM section WHERE class_id = :class_id ORDER BY name", ['class_id' => $classId]);
}

function subjects_by_class(int $classId, string $year, ?int $teacherId = null): array
{
    $params = ['class_id' => $classId, 'year' => $year];
    $sql = "SELECT s.*, t.name AS teacher_name
            FROM subject s
            LEFT JOIN teacher t ON t.teacher_id = s.teacher_id
            WHERE s.class_id = :class_id AND s.year = :year";
    if ($teacherId) {
        $sql .= " AND s.teacher_id = :teacher_id";
        $params['teacher_id'] = $teacherId;
    }
    $sql .= " ORDER BY s.name";
    return db()->fetchAll($sql, $params);
}

function next_class_for(int $classId): ?array
{
    $current = db()->fetch("SELECT * FROM class WHERE class_id = :id", ['id' => $classId]);
    if (!$current) {
        return null;
    }
    $level = (int) preg_replace('/\D+/', '', (string) ($current['name_numeric'] ?? '0'));
    $candidates = db()->fetchAll("SELECT * FROM class ORDER BY name_numeric + 0, name");
    foreach ($candidates as $candidate) {
        $candidateLevel = (int) preg_replace('/\D+/', '', (string) ($candidate['name_numeric'] ?? '0'));
        if ($candidateLevel === $level + 1) {
            return $candidate;
        }
    }
    return null;
}

function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function settings_upsert(string $type, string $description): void
{
    $existing = db()->fetch("SELECT settings_id FROM settings WHERE type = :type LIMIT 1", ['type' => $type]);
    if ($existing) {
        db()->execute("UPDATE settings SET description = :description WHERE settings_id = :settings_id", [
            'description' => $description,
            'settings_id' => $existing['settings_id'],
        ]);
        return;
    }
    db()->execute("INSERT INTO settings (type, description) VALUES (:type, :description)", [
        'type' => $type,
        'description' => $description,
    ]);
}

function latest_announcements(int $limit = 5): array
{
    return db()->fetchAll(
        "SELECT * FROM noticeboard WHERE COALESCE(status,1)=1 ORDER BY CAST(COALESCE(create_timestamp,'0') AS UNSIGNED) DESC, notice_id DESC LIMIT " . (int) $limit
    );
}

function announcement_date(array $row): string
{
    $ts = (int) ($row['create_timestamp'] ?? 0);
    if ($ts > 0) {
        return date('d M Y · H:i', $ts);
    }
    return '';
}

function app_mail_from(): string
{
    return trim((string) school_meta('mail_from_address', 'support@learntrackschools.online')) ?: 'support@learntrackschools.online';
}

function send_portal_email(string $to, string $subject, string $html, ?string $replyTo = null): bool
{
    $to = trim($to);
    if ($to === '') {
        return false;
    }
    $from = app_mail_from();
    $reply = $replyTo ?: $from;
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . school_meta('system_name', 'LearnTrack Schools') . ' <' . $from . '>';
    $headers[] = 'Reply-To: ' . $reply;
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    return @mail($to, $subject, $html, implode("\r\n", $headers));
}


function announcement_excerpt(string $text, int $limit = 160): string
{
    $clean = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($clean) > $limit ? rtrim(mb_substr($clean, 0, $limit - 1)) . '…' : $clean;
    }
    return strlen($clean) > $limit ? rtrim(substr($clean, 0, $limit - 1)) . '…' : $clean;
}


function auth_role(): string
{
    return (string) (current_user()['role'] ?? 'guest');
}

function auth_id(): int
{
    return (int) (current_user()['id'] ?? 0);
}

function auth_name(): string
{
    return (string) (current_user()['name'] ?? 'System');
}

function client_ip(): string
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        $value = trim((string) ($_SERVER[$key] ?? ''));
        if ($value !== '') {
            return trim(explode(',', $value)[0]);
        }
    }
    return '127.0.0.1';
}

function ensure_support_tables(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    db()->execute("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_role VARCHAR(30) NOT NULL,
        user_id INT NOT NULL DEFAULT 0,
        user_name VARCHAR(190) NULL,
        action VARCHAR(100) NOT NULL,
        module_name VARCHAR(100) NOT NULL,
        record_id INT NULL,
        description TEXT NULL,
        old_values LONGTEXT NULL,
        new_values LONGTEXT NULL,
        ip_address VARCHAR(64) NULL,
        user_agent TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    db()->execute("CREATE TABLE IF NOT EXISTS backups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        file_name VARCHAR(255) NOT NULL,
        backup_type VARCHAR(50) NOT NULL,
        file_path TEXT NOT NULL,
        created_by_role VARCHAR(30) NULL,
        created_by_id INT NULL,
        created_by_name VARCHAR(190) NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'ready',
        notes TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    db()->execute("CREATE TABLE IF NOT EXISTS sms_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NULL,
        class_id INT NULL,
        exam_id INT NULL,
        year VARCHAR(30) NULL,
        phone VARCHAR(30) NOT NULL,
        message TEXT NOT NULL,
        provider VARCHAR(40) NOT NULL,
        send_mode VARCHAR(40) NOT NULL DEFAULT 'class',
        status VARCHAR(30) NOT NULL DEFAULT 'pending',
        error_message TEXT NULL,
        provider_response LONGTEXT NULL,
        sent_by_role VARCHAR(30) NULL,
        sent_by_id INT NULL,
        sent_by_name VARCHAR(190) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_sms_logs_student (student_id),
        INDEX idx_sms_logs_exam (exam_id),
        INDEX idx_sms_logs_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    db()->execute("CREATE TABLE IF NOT EXISTS email_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NULL,
        class_id INT NULL,
        exam_id INT NULL,
        year VARCHAR(30) NULL,
        recipient_email VARCHAR(190) NOT NULL,
        email_type VARCHAR(40) NOT NULL DEFAULT 'report_card',
        subject VARCHAR(255) NOT NULL,
        attachment_name VARCHAR(255) NULL,
        send_mode VARCHAR(40) NOT NULL DEFAULT 'class',
        status VARCHAR(30) NOT NULL DEFAULT 'pending',
        error_message TEXT NULL,
        provider_response LONGTEXT NULL,
        sent_by_role VARCHAR(30) NULL,
        sent_by_id INT NULL,
        sent_by_name VARCHAR(190) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email_logs_student (student_id),
        INDEX idx_email_logs_exam (exam_id),
        INDEX idx_email_logs_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $columns = db()->fetchAll("SHOW COLUMNS FROM mark");
    $existingCols = array_map(fn($row) => strtolower((string)($row['Field'] ?? '')), $columns);
    $alterStatements = [
        'created_by_role' => "ALTER TABLE mark ADD COLUMN created_by_role VARCHAR(30) NULL",
        'created_by_id' => "ALTER TABLE mark ADD COLUMN created_by_id INT NULL",
        'updated_by_role' => "ALTER TABLE mark ADD COLUMN updated_by_role VARCHAR(30) NULL",
        'updated_by_id' => "ALTER TABLE mark ADD COLUMN updated_by_id INT NULL",
        'created_at' => "ALTER TABLE mark ADD COLUMN created_at DATETIME NULL",
        'updated_at' => "ALTER TABLE mark ADD COLUMN updated_at DATETIME NULL",
    ];
    foreach ($alterStatements as $column => $sql) {
        if (!in_array($column, $existingCols, true)) {
            try {
                db()->execute($sql);
            } catch (\Throwable $e) {
                // leave silently for shared-hosting/MySQL variants
            }
        }
    }
}

function log_activity(array $payload): void
{
    ensure_support_tables();
    try {
        db()->execute("INSERT INTO activity_logs (
            user_role, user_id, user_name, action, module_name, record_id, description,
            old_values, new_values, ip_address, user_agent
        ) VALUES (
            :user_role, :user_id, :user_name, :action, :module_name, :record_id, :description,
            :old_values, :new_values, :ip_address, :user_agent
        )", [
            'user_role' => (string)($payload['user_role'] ?? auth_role()),
            'user_id' => (int)($payload['user_id'] ?? auth_id()),
            'user_name' => (string)($payload['user_name'] ?? auth_name()),
            'action' => (string)($payload['action'] ?? 'view'),
            'module_name' => (string)($payload['module_name'] ?? 'general'),
            'record_id' => isset($payload['record_id']) ? (int)$payload['record_id'] : null,
            'description' => (string)($payload['description'] ?? ''),
            'old_values' => isset($payload['old_values']) ? (string)$payload['old_values'] : null,
            'new_values' => isset($payload['new_values']) ? (string)$payload['new_values'] : null,
            'ip_address' => (string)($payload['ip_address'] ?? client_ip()),
            'user_agent' => substr((string)($payload['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 65535),
        ]);
    } catch (\Throwable $e) {
        // avoid breaking user flows because of logging
    }
}

function storage_path(string $path = ''): string
{
    $root = dirname(__DIR__, 2) . '/storage';
    if (!is_dir($root)) {
        @mkdir($root, 0777, true);
    }
    return $root . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function backup_dir(): string
{
    $dir = storage_path('backups');
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

function uploads_dir(): string
{
    return dirname(__DIR__, 2) . '/public/uploads';
}

function record_backup(string $fileName, string $filePath, string $type, string $notes = ''): int
{
    ensure_support_tables();
    db()->execute("INSERT INTO backups (file_name, backup_type, file_path, created_by_role, created_by_id, created_by_name, notes)
        VALUES (:file_name, :backup_type, :file_path, :created_by_role, :created_by_id, :created_by_name, :notes)", [
        'file_name' => $fileName,
        'backup_type' => $type,
        'file_path' => $filePath,
        'created_by_role' => auth_role(),
        'created_by_id' => auth_id(),
        'created_by_name' => auth_name(),
        'notes' => $notes,
    ]);
    return (int) db()->lastInsertId();
}

function database_tables(): array
{
    $dbName = (string) config('db.dbname', '');
    $rows = db()->fetchAll("SELECT table_name FROM information_schema.tables WHERE table_schema = :schema ORDER BY table_name", ['schema' => $dbName]);
    return array_values(array_filter(array_map(fn($row) => $row['table_name'] ?? null, $rows)));
}

function sql_literal($value): string
{
    if ($value === null) {
        return "NULL";
    }
    return db()->pdo()->quote((string)$value);
}

function build_database_backup_sql(): string
{
    $dbName = (string) config('db.dbname', 'database');
    $tables = database_tables();
    $sql = "-- LearnTrack PRO backup\n";
    $sql .= "-- Database: {$dbName}\n";
    $sql .= "-- Generated at: " . date('Y-m-d H:i:s') . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    foreach ($tables as $table) {
        $create = db()->fetch("SHOW CREATE TABLE `{$table}`");
        $createSql = $create['Create Table'] ?? array_values($create ?? [])[1] ?? '';
        $sql .= "-- ----------------------------\n";
        $sql .= "-- Table structure for `{$table}`\n";
        $sql .= "-- ----------------------------\n";
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= $createSql . ";\n\n";

        $rows = db()->fetchAll("SELECT * FROM `{$table}`");
        if ($rows) {
            $columns = array_keys($rows[0]);
            $columnsSql = '`' . implode('`,`', $columns) . '`';
            $sql .= "-- ----------------------------\n";
            $sql .= "-- Records of `{$table}`\n";
            $sql .= "-- ----------------------------\n";
            foreach ($rows as $row) {
                $values = [];
                foreach ($columns as $column) {
                    $values[] = sql_literal($row[$column] ?? null);
                }
                $sql .= "INSERT INTO `{$table}` ({$columnsSql}) VALUES (" . implode(',', $values) . ");\n";
            }
            $sql .= "\n";
        }
    }
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $sql;
}

function create_database_backup_file(): array
{
    $fileName = 'db_backup_' . date('Ymd_His') . '.sql';
    $filePath = backup_dir() . '/' . $fileName;
    file_put_contents($filePath, build_database_backup_sql());
    $backupId = record_backup($fileName, $filePath, 'database', 'Manual database backup');
    log_activity([
        'action' => 'backup',
        'module_name' => 'backups',
        'record_id' => $backupId,
        'description' => 'Created database backup ' . $fileName,
        'new_values' => json_encode(['type' => 'database', 'file_name' => $fileName]),
    ]);
    return ['id' => $backupId, 'file_name' => $fileName, 'file_path' => $filePath];
}

function add_dir_to_zip(\ZipArchive $zip, string $dirPath, string $basePathInZip = ''): void
{
    if (!is_dir($dirPath)) {
        return;
    }
    $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($dirPath, \FilesystemIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($files as $file) {
        /** @var \SplFileInfo $file */
        $realPath = $file->getRealPath();
        if ($realPath === false) {
            continue;
        }
        $relative = ltrim(str_replace('\\', '/', substr($realPath, strlen($dirPath))), '/');
        $zipPath = trim($basePathInZip . '/' . $relative, '/');
        if ($file->isDir()) {
            $zip->addEmptyDir($zipPath);
        } else {
            $zip->addFile($realPath, $zipPath);
        }
    }
}

function create_full_backup_file(): array
{
    $db = create_database_backup_file();
    $fileName = 'full_backup_' . date('Ymd_His') . '.zip';
    $filePath = backup_dir() . '/' . $fileName;
    $zip = new \ZipArchive();
    if ($zip->open($filePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
        throw new \RuntimeException('Unable to create backup zip file.');
    }
    $zip->addFile($db['file_path'], 'database.sql');
    add_dir_to_zip($zip, uploads_dir(), 'uploads');
    $zip->close();

    $backupId = record_backup($fileName, $filePath, 'full', 'Full backup with uploads');
    log_activity([
        'action' => 'backup',
        'module_name' => 'backups',
        'record_id' => $backupId,
        'description' => 'Created full backup ' . $fileName,
        'new_values' => json_encode(['type' => 'full', 'file_name' => $fileName]),
    ]);
    return ['id' => $backupId, 'file_name' => $fileName, 'file_path' => $filePath];
}

function import_sql_statements(string $sql): void
{
    $pdo = db()->pdo();
    $pdo->beginTransaction();
    try {
        $buffer = '';
        $inString = false;
        $quoteChar = '';
        $length = strlen($sql);
        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $prev = $i > 0 ? $sql[$i - 1] : '';
            if (($char === "'" || $char === '"') && $prev !== '\\') {
                if (!$inString) {
                    $inString = true;
                    $quoteChar = $char;
                } elseif ($quoteChar === $char) {
                    $inString = false;
                    $quoteChar = '';
                }
            }
            $buffer .= $char;
            if ($char === ';' && !$inString) {
                $statement = trim($buffer);
                $buffer = '';
                if ($statement === '' || str_starts_with($statement, '--') || str_starts_with($statement, '/*')) {
                    continue;
                }
                $pdo->exec($statement);
            }
        }
        $statement = trim($buffer);
        if ($statement !== '' && !str_starts_with($statement, '--') && !str_starts_with($statement, '/*')) {
            $pdo->exec($statement);
        }
        $pdo->commit();
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function restore_backup_upload(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new \RuntimeException('Please upload a valid backup file.');
    }
    $name = $file['name'] ?? 'backup';
    $tmp = $file['tmp_name'] ?? '';
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $preRestore = create_database_backup_file();
    $notes = 'Automatic pre-restore backup: ' . $preRestore['file_name'];

    if ($ext === 'sql') {
        $sql = file_get_contents($tmp);
        if ($sql === false) {
            throw new \RuntimeException('Unable to read uploaded SQL file.');
        }
        import_sql_statements($sql);
        log_activity([
            'action' => 'restore',
            'module_name' => 'backups',
            'description' => 'Restored database from SQL file ' . $name,
            'new_values' => json_encode(['file_name' => $name, 'pre_restore_backup' => $preRestore['file_name']]),
        ]);
        return $notes;
    }

    if ($ext === 'zip') {
        $workDir = storage_path('restore_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)));
        @mkdir($workDir, 0777, true);
        $zip = new \ZipArchive();
        if ($zip->open($tmp) !== true) {
            throw new \RuntimeException('Unable to open uploaded zip file.');
        }
        $zip->extractTo($workDir);
        $zip->close();

        $sqlPath = is_file($workDir . '/database.sql') ? $workDir . '/database.sql' : null;
        if (!$sqlPath) {
            $found = glob($workDir . '/*.sql');
            $sqlPath = $found[0] ?? null;
        }
        if (!$sqlPath || !is_file($sqlPath)) {
            throw new \RuntimeException('No SQL file found inside the uploaded zip.');
        }

        $sql = file_get_contents($sqlPath);
        if ($sql === false) {
            throw new \RuntimeException('Unable to read SQL file inside zip.');
        }
        import_sql_statements($sql);

        $uploadsSource = is_dir($workDir . '/uploads') ? $workDir . '/uploads' : null;
        if ($uploadsSource && is_dir($uploadsSource)) {
            @mkdir(uploads_dir(), 0777, true);
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($uploadsSource, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                $target = uploads_dir() . '/' . ltrim(str_replace('\\', '/', substr($item->getPathname(), strlen($uploadsSource))), '/');
                if ($item->isDir()) {
                    @mkdir($target, 0777, true);
                } else {
                    @mkdir(dirname($target), 0777, true);
                    copy($item->getPathname(), $target);
                }
            }
        }

        log_activity([
            'action' => 'restore',
            'module_name' => 'backups',
            'description' => 'Restored full backup from zip file ' . $name,
            'new_values' => json_encode(['file_name' => $name, 'pre_restore_backup' => $preRestore['file_name']]),
        ]);
        return $notes;
    }

    throw new \RuntimeException('Unsupported backup file type. Use .sql or .zip');
}
