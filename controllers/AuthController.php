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
        $email    = trim($_POST['email']    ?? '');
        $password =      $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            Helpers::setFlash('error', 'Email and password are required.');
            Helpers::redirect('/login');
        }

        $userModel  = new UserModel();
        $auditModel = new AuditLogModel();

        $user = $userModel->findByEmail($email);

        // Treat "not found" and "deactivated" identically — same error
        // message and same audit entry — to prevent account enumeration.
        if ($user === null || (int) $user['is_active'] === 0) {
            $auditModel->log(
                null,
                'LOGIN_FAILED',
                'users',
                null,
                null,
                ['email' => $email, 'reason' => 'user not found or inactive']
            );
            Helpers::setFlash('error', 'Invalid email or password.');
            Helpers::redirect('/login');
        }

        if (!password_verify($password, $user['password_hash'])) {
            $auditModel->log(
                null,
                'LOGIN_FAILED',
                'users',
                (int) $user['user_id'],
                null,
                ['email' => $email, 'reason' => 'wrong password']
            );
            Helpers::setFlash('error', 'Invalid email or password.');
            Helpers::redirect('/login');
        }

        // Credentials verified — stamp last login and write success log.
        $userModel->updateLastLogin((int) $user['user_id']);

        $auditModel->log(
            (int) $user['user_id'],
            'LOGIN_SUCCESS',
            'users',
            (int) $user['user_id']
        );

        Auth::login($user);
        Helpers::redirect(Auth::dashboardUrl());
    }

    public function handleLogout(): void
    {
        Auth::logout();
        Helpers::setFlash('success', 'You have been logged out.');
        Helpers::redirect('/login');
    }
}
