<?php

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            Helpers::redirect(Auth::dashboardUrl());
        }
        $flash = Helpers::getFlash();
        require_once __DIR__ . '/../views/layouts/auth.php';
    }

    public function handleLogin(): void
    {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            Helpers::setFlash('error', 'Email and password are required.');
            Helpers::redirect('/login');
        }

        $demo = [
            'superadmin@lvms.test' => ['user_id' => 1, 'company_id' => 1, 'role' => ROLE_SUPER_ADMIN, 'first_name' => 'Super',  'last_name' => 'Admin'],
            'admin@lvms.test'      => ['user_id' => 2, 'company_id' => 1, 'role' => ROLE_ADMIN,       'first_name' => 'Admin',  'last_name' => 'User'],
            'employee@lvms.test'   => ['user_id' => 3, 'company_id' => 1, 'role' => ROLE_EMPLOYEE,    'first_name' => 'Juan',   'last_name' => 'dela Cruz'],
            'driver@lvms.test'     => ['user_id' => 4, 'company_id' => 1, 'role' => ROLE_DRIVER,      'first_name' => 'Carlos', 'last_name' => 'Mendoza'],
        ];

        if (!isset($demo[$email]) || $password !== 'password') {
            Helpers::setFlash('error', 'Invalid email or password.');
            Helpers::redirect('/login');
        }

        Auth::login($demo[$email]);
        Helpers::redirect(Auth::dashboardUrl());
    }

    public function handleLogout(): void
    {
        Auth::logout();
        Helpers::setFlash('success', 'You have been logged out.');
        Helpers::redirect('/login');
    }
}
