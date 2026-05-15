<?php
declare(strict_types=1);

namespace Controllers;

use Core\Auth;

class AdminController extends BaseController
{
    public function index(): void
    {
        require_auth(['admin']);
        $q = trim((string) request('q', ''));
        $params = [];
        $sql = "SELECT admin_id, name, email, phone, address FROM admin WHERE 1=1";
        if ($q !== '') {
            $sql .= " AND (name LIKE :q OR email LIKE :q OR phone LIKE :q)";
            $params['q'] = "%{$q}%";
        }
        $sql .= " ORDER BY name LIMIT 200";
        $admins = db()->fetchAll($sql, $params);
        $title = 'Admin Accounts';
        $this->render('admins/index', compact('title', 'admins', 'q'));
    }

    public function create(): void
    {
        require_auth(['admin']);
        $title = 'Create Admin Account';
        $this->render('admins/create', compact('title'));
    }

    public function store(): void
    {
        require_auth(['admin']);
        $name = trim((string) request('name'));
        $email = strtolower(trim((string) request('email')));
        $password = (string) request('password');

        if ($name === '' || $email === '' || $password === '') {
            flash('error', 'Name, email, and password are required.');
            redirect('/admins/create');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Enter a valid admin email address.');
            redirect('/admins/create');
        }
        if (db()->fetch("SELECT admin_id FROM admin WHERE email = :email LIMIT 1", ['email' => $email])) {
            flash('error', 'An admin account with that email already exists.');
            redirect('/admins/create');
        }

        db()->execute("INSERT INTO admin (name, email, password, phone, address)
            VALUES (:name, :email, :password, :phone, :address)", [
            'name' => $name,
            'email' => $email,
            'password' => Auth::makePassword($password),
            'phone' => trim((string) request('phone', '')),
            'address' => trim((string) request('address', '')),
        ]);
        $adminId = (int) db()->lastInsertId();
        log_activity([
            'action' => 'create',
            'module_name' => 'admins',
            'record_id' => $adminId,
            'description' => 'Created admin account for ' . $name,
        ]);
        flash('success', 'Admin account created successfully.');
        redirect('/admins');
    }

    public function edit(): void
    {
        require_auth(['admin']);
        $id = (int) request('id');
        $admin = db()->fetch("SELECT admin_id, name, email, phone, address FROM admin WHERE admin_id = :id LIMIT 1", ['id' => $id]);
        if (!$admin) {
            flash('error', 'Admin account not found.');
            redirect('/admins');
        }
        $title = 'Edit Admin Account';
        $this->render('admins/edit', compact('title', 'admin'));
    }

    public function update(): void
    {
        require_auth(['admin']);
        $id = (int) request('admin_id');
        $name = trim((string) request('name'));
        $email = strtolower(trim((string) request('email')));

        if ($id <= 0 || $name === '' || $email === '') {
            flash('error', 'Name and email are required.');
            redirect('/admins');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Enter a valid admin email address.');
            redirect('/admins/edit?id=' . $id);
        }
        $duplicate = db()->fetch("SELECT admin_id FROM admin WHERE email = :email AND admin_id <> :id LIMIT 1", ['email' => $email, 'id' => $id]);
        if ($duplicate) {
            flash('error', 'Another admin already uses that email.');
            redirect('/admins/edit?id=' . $id);
        }

        db()->execute("UPDATE admin SET name = :name, email = :email, phone = :phone, address = :address WHERE admin_id = :id", [
            'name' => $name,
            'email' => $email,
            'phone' => trim((string) request('phone', '')),
            'address' => trim((string) request('address', '')),
            'id' => $id,
        ]);
        if (trim((string) request('password', '')) !== '') {
            db()->execute("UPDATE admin SET password = :password WHERE admin_id = :id", [
                'password' => Auth::makePassword((string) request('password')),
                'id' => $id,
            ]);
        }
        log_activity([
            'action' => 'update',
            'module_name' => 'admins',
            'record_id' => $id,
            'description' => 'Updated admin account ' . $name,
        ]);
        flash('success', 'Admin account updated successfully.');
        redirect('/admins');
    }

    public function delete(): void
    {
        require_auth(['admin']);
        $id = (int) request('admin_id');
        $current = current_user();
        if ($id <= 0) {
            flash('error', 'Invalid admin selected.');
            redirect('/admins');
        }
        if (($current['role'] ?? '') === 'admin' && (int)($current['id'] ?? 0) === $id) {
            flash('error', 'You cannot delete the admin account you are currently using.');
            redirect('/admins');
        }
        $count = db()->fetch("SELECT COUNT(*) AS total FROM admin");
        if ((int)($count['total'] ?? 0) <= 1) {
            flash('error', 'You cannot delete the last admin account.');
            redirect('/admins');
        }
        $admin = db()->fetch("SELECT admin_id, name, email FROM admin WHERE admin_id = :id LIMIT 1", ['id' => $id]);
        if (!$admin) {
            flash('error', 'Admin account not found.');
            redirect('/admins');
        }
        db()->execute("DELETE FROM admin WHERE admin_id = :id", ['id' => $id]);
        log_activity([
            'action' => 'delete',
            'module_name' => 'admins',
            'record_id' => $id,
            'description' => 'Deleted admin account ' . ($admin['name'] ?? ('#' . $id)),
            'old_values' => json_encode($admin),
        ]);
        flash('success', 'Admin account deleted successfully.');
        redirect('/admins');
    }
}
