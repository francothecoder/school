<?php
declare(strict_types=1);

namespace Controllers;

use Core\Auth;

class ProfileController extends BaseController
{
    public function index(): void
    {
        require_auth();
        $user = current_user();
        $table = $user['role'] === 'admin' ? 'admin' : ($user['role'] === 'teacher' ? 'teacher' : 'student');
        $pk = $user['role'] . '_id';
        $profile = db()->fetch("SELECT * FROM {$table} WHERE {$pk} = :id", ['id' => $user['id']]);
        $title = 'My Profile';
        $this->render('profile/index', compact('title', 'profile', 'table', 'pk'));
    }

    public function update(): void
    {
        require_auth();
        $user = current_user();
        $role = $user['role'];
        $table = $role === 'admin' ? 'admin' : ($role === 'teacher' ? 'teacher' : 'student');
        $pk = $role . '_id';
        db()->execute("UPDATE {$table} SET name = :name, email = :email, phone = :phone, address = :address WHERE {$pk} = :id", [
            'name' => request('name'),
            'email' => request('email'),
            'phone' => request('phone', ''),
            'address' => request('address', ''),
            'id' => $user['id'],
        ]);

        if (request('password')) {
            db()->execute("UPDATE {$table} SET password = :password WHERE {$pk} = :id", [
                'password' => Auth::makePassword((string) request('password')),
                'id' => $user['id'],
            ]);
        }

        $_SESSION['auth']['name'] = (string) request('name');
        $_SESSION['auth']['email'] = (string) request('email');

        flash('success', 'Profile updated successfully.');
        redirect('/profile');
    }
}
