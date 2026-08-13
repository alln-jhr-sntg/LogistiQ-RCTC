<?php

class Auth
{
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']       = $user['user_id'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['full_name']     = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['company_id']    = $user['company_id'];
        $_SESSION['department_id'] = $user['department_id'] ?? null;
        $_SESSION['profile_photo'] = $user['profile_photo'] ?? null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function check(): bool        { return isset($_SESSION['user_id']); }
    public static function id(): ?int           { return $_SESSION['user_id']       ?? null; }
    public static function role(): ?string      { return $_SESSION['role']          ?? null; }
    public static function fullName(): ?string  { return $_SESSION['full_name']     ?? null; }
    public static function companyId(): ?int    { return $_SESSION['company_id']    ?? null; }
    public static function departmentId(): ?int { return $_SESSION['department_id'] ?? null; }
    public static function profilePhoto(): ?string { return $_SESSION['profile_photo'] ?? null; }

    public static function isSuperAdmin(): bool   { return self::role() === ROLE_SUPER_ADMIN; }
    public static function isFleetAdmin(): bool   { return self::role() === ROLE_FLEET_ADMIN; }
    public static function isAdmin(): bool        { return self::role() === ROLE_ADMIN; }
    public static function isEmployee(): bool     { return self::role() === ROLE_EMPLOYEE; }
    public static function isDriver(): bool       { return self::role() === ROLE_DRIVER; }
    public static function isAdminOrAbove(): bool { return in_array(self::role(), [ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN], true); }

    // Roles that act across every company.
    public static function hasGlobalScope(): bool
    {
        return in_array(self::role(), [ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN], true);
    }

    // May the current user ACT on a record owned by $companyId?
    // Viewing is unrestricted for admin-and-above; this gates writes only.
    public static function canActOnCompany(?int $companyId): bool
    {
        // Vehicles and drivers are a shared fleet across all three companies —
        // a driver's users.company_id is an HR record, not an access scope. A
        // REMIX driver assigned to an IDEAL trip must still be able to act on
        // it. Driver access is gated separately by the existing driver_id
        // ownership checks in the controllers, not by company.
        if (self::isDriver())       { return true; }
        if (self::hasGlobalScope()) { return true; }
        if ($companyId === null)    { return false; }
        return (int) $companyId === (int) self::companyId();
    }

    // Guard: flash + redirect when the user may not act on this record.
    // Employee and driver ownership is enforced separately by the existing
    // requested_by / driver_id checks — this is the company-level layer.
    public static function requireCompanyScope(?int $companyId, string $redirectTo): void
    {
        if (self::canActOnCompany($companyId)) {
            return;
        }
        Helpers::setFlash('error', 'You can only act on records belonging to your own company.');
        Helpers::redirect($redirectTo);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ' . APP_BASE . '/index.php?url=login');
            exit;
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        if (!in_array(self::role(), $roles, true)) {
            render_error_page(403, 'Forbidden', 'You don\'t have permission to view this page.', showLink: true);
            exit;
        }
    }

    public static function dashboardUrl(): string
    {
        return ROLE_DASHBOARD[self::role()] ?? '/dashboard';
    }
}
