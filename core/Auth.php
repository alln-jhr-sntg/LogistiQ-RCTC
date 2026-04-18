<?php

class Auth
{
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['user_id'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['full_name']  = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['company_id'] = $user['company_id'];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function check(): bool    { return isset($_SESSION['user_id']); }
    public static function id(): ?int       { return $_SESSION['user_id']   ?? null; }
    public static function role(): ?string  { return $_SESSION['role']      ?? null; }
    public static function fullName(): ?string { return $_SESSION['full_name'] ?? null; }

    public static function isSuperAdmin(): bool   { return self::role() === ROLE_SUPER_ADMIN; }
    public static function isAdmin(): bool        { return self::role() === ROLE_ADMIN; }
    public static function isEmployee(): bool     { return self::role() === ROLE_EMPLOYEE; }
    public static function isDriver(): bool       { return self::role() === ROLE_DRIVER; }
    public static function isAdminOrAbove(): bool { return in_array(self::role(), [ROLE_SUPER_ADMIN, ROLE_ADMIN], true); }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /lvms/index.php?url=login');
            exit;
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        if (!in_array(self::role(), $roles, true)) {
            http_response_code(403);
            echo '403 Forbidden';
            exit;
        }
    }

    public static function dashboardUrl(): string
    {
        return ROLE_DASHBOARD[self::role()] ?? '/dashboard';
    }
}
