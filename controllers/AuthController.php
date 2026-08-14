<?php

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            Helpers::redirect(Auth::dashboardUrl());
        }
        $flash        = Helpers::getFlash();
        $demoAccounts = $this->buildDemoAccounts();
        $companyName  = (new SystemSettingModel())->getByKey('company_name')
            ?? 'Remix Construction and Trading Corporation';
        require_once __DIR__ . '/../views/layouts/auth.php';
    }

    /**
     * Pick one representative active account per role/company slot for
     * the login page's demo-accounts panel. Slots with no company are
     * company-agnostic roles (super_admin, fleet_admin, driver — drivers
     * are exempt from company scope per the domain rules). Built from
     * live data so the panel never drifts from real credentials.
     *
     * @return array<int, array{label: string, email: string}>
     */
    private function buildDemoAccounts(): array
    {
        $slots = [
            ['role' => ROLE_SUPER_ADMIN, 'company' => null,       'label' => 'Super Admin'],
            ['role' => ROLE_FLEET_ADMIN, 'company' => null,       'label' => 'Fleet Admin'],
            ['role' => ROLE_ADMIN,       'company' => 'IDEAL',    'label' => 'Ideal Admin'],
            ['role' => ROLE_ADMIN,       'company' => 'TENBUILD', 'label' => 'TenBuild Admin'],
            ['role' => ROLE_EMPLOYEE,    'company' => 'REMIX',    'label' => 'Remix Employee'],
            ['role' => ROLE_EMPLOYEE,    'company' => 'IDEAL',    'label' => 'Ideal Employee'],
            ['role' => ROLE_EMPLOYEE,    'company' => 'TENBUILD', 'label' => 'TenBuild Employee'],
            ['role' => ROLE_DRIVER,      'company' => null,       'label' => 'Driver Account'],
        ];

        $activeUsers = (new UserModel())->findActiveWithCompany();

        $accounts = [];
        foreach ($slots as $slot) {
            foreach ($activeUsers as $user) {
                $companyMatches = $slot['company'] === null || $user['company_code'] === $slot['company'];
                if ($user['role'] === $slot['role'] && $companyMatches) {
                    $accounts[] = ['label' => $slot['label'], 'email' => $user['email']];
                    break;
                }
            }
        }

        return $accounts;
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
