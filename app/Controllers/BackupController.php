<?php
declare(strict_types=1);

namespace Controllers;

class BackupController extends BaseController
{
    public function index(): void
    {
        require_auth(['admin']);
        ensure_support_tables();
        $backups = db()->fetchAll("SELECT * FROM backups ORDER BY id DESC LIMIT 100");
        $title = 'Backup & Restore';
        $this->render('admin/backups', compact('title', 'backups'));
    }

    public function createDatabase(): void
    {
        require_auth(['admin']);
        try {
            create_database_backup_file();
            flash('success', 'Database backup created successfully.');
        } catch (\Throwable $e) {
            flash('error', 'Unable to create database backup: ' . $e->getMessage());
        }
        redirect('/backups');
    }

    public function createFull(): void
    {
        require_auth(['admin']);
        try {
            create_full_backup_file();
            flash('success', 'Full backup created successfully.');
        } catch (\Throwable $e) {
            flash('error', 'Unable to create full backup: ' . $e->getMessage());
        }
        redirect('/backups');
    }

    public function download(): void
    {
        require_auth(['admin']);
        $id = (int) request('id');
        $backup = db()->fetch("SELECT * FROM backups WHERE id = :id LIMIT 1", ['id' => $id]);
        if (!$backup || !is_file((string) ($backup['file_path'] ?? ''))) {
            flash('error', 'Backup file not found.');
            redirect('/backups');
        }
        log_activity([
            'action' => 'download',
            'module_name' => 'backups',
            'record_id' => $id,
            'description' => 'Downloaded backup ' . ($backup['file_name'] ?? ''),
            'new_values' => json_encode(['file_name' => $backup['file_name'] ?? '']),
        ]);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename((string) $backup['file_name']) . '"');
        header('Content-Length: ' . filesize((string) $backup['file_path']));
        readfile((string) $backup['file_path']);
        exit;
    }

    public function restore(): void
    {
        require_auth(['admin']);
        $confirm = trim((string) request('confirm_phrase', ''));
        if (strcasecmp($confirm, 'RESTORE') !== 0) {
            flash('error', 'Type RESTORE to confirm the restore action.');
            redirect('/backups');
        }
        try {
            $notes = restore_backup_upload($_FILES['backup_file'] ?? []);
            flash('success', 'Restore completed successfully. ' . $notes);
        } catch (\Throwable $e) {
            flash('error', 'Restore failed: ' . $e->getMessage());
        }
        redirect('/backups');
    }
}
