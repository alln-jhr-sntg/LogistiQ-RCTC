<?php
function nav_is_active(string $path): string {
    $current = '/' . trim($_GET['url'] ?? '', '/');
    return str_starts_with($current, $path) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helpers::e($page_title ?? 'LVMS') ?> — LVMS</title>
    <link rel="stylesheet" href="/lvms/public/css/app.css">
</head>
<body class="app-body">

<div class="app-shell">

    <!-- ── Sidebar ────────────────────────────────────────── -->
    <aside class="sidebar" id="sidebar">

        <div class="sidebar-header">
            <span class="sidebar-logo-text">Logisti<span class=sidebar-logo-text-yellow>Q</span></span>
            <button class="sidebar-menu-btn" id="sidebarOpen" aria-label="Open menu">
                <span></span><span></span><span></span>
            </button>
            <button class="sidebar-close" id="sidebarClose" aria-label="Close menu">&#x2715;</button>
        </div>

        <nav class="sidebar-nav">

            <?php $role = Auth::role(); ?>

            <!-- Dashboard — all roles -->
            <a href="<?= Helpers::url(Auth::dashboardUrl()) ?>" title="Dashboard" class="nav-item <?= nav_is_active('/dashboard') ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                <span class="nav-label">Dashboard</span>
            </a>

            <?php if (in_array($role, [ROLE_SUPER_ADMIN, ROLE_ADMIN])): ?>
            <!-- Reservations -->
            <a href="<?= Helpers::url('/reservations') ?>" title="Reservations" class="nav-item <?= nav_is_active('/reservations') ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
                <span class="nav-label">Reservations</span>
            </a>

            <!-- Trips -->
            <a href="<?= Helpers::url('/trips') ?>" title="Trips" class="nav-item <?= nav_is_active('/trips') ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><path d="M1 3h15v13H1V3zm15 4h4l3 3v6h-7V7zM5.5 20a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm13 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/></svg>
                <span class="nav-label">Trips</span>
            </a>

            <!-- Vehicles -->
            <a href="<?= Helpers::url('/vehicles') ?>" title="Vehicles" class="nav-item <?= nav_is_active('/vehicles') ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.85 7h10.29l1.08 3.11H5.77L6.85 7zM19 17H5v-5h14v5zm-8.5-4c.83 0 1.5.67 1.5 1.5S11.33 16 10.5 16 9 15.33 9 14.5 9.67 13 10.5 13zm3 0c.83 0 1.5.67 1.5 1.5S14.33 16 13.5 16 12 15.33 12 14.5 12.67 13 13.5 13z"/></svg>
                <span class="nav-label">Vehicles</span>
            </a>

            <!-- Projects -->
            <a href="<?= Helpers::url('/projects') ?>" title="Projects" class="nav-item <?= nav_is_active('/projects') ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><path d="M20 6h-2.18c.07-.44.18-.88.18-1.34C18 2.54 15.96.5 13.46.5c-1.29 0-2.35.54-3.16 1.38L10 2.18l-.3-.3C8.9 1.04 7.84.5 6.54.5 4.04.5 2 2.54 2 4.66c0 .46.11.9.18 1.34H0v14c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6zm-9.3-3.49c.38-.43.92-.69 1.76-.69 1.34 0 2.04.7 2.04 1.84 0 .46-.28 1.15-.73 1.84H10.73c-.08-.23-.53-.73-.73-1.14.1-.66.32-1.43.7-1.85zM5.5 4.16c0-1.14.7-1.84 2.04-1.84.84 0 1.38.26 1.76.69.38.43.6 1.19.7 1.85-.2.41-.65.91-.73 1.14H6.23C5.78 5.31 5.5 4.62 5.5 4.16zM20 18H4v-2h16v2zm0-5H4V8h5.08L7 11h2l2-3h2l2 3h2l-2.08-3H20v5z"/></svg>
                <span class="nav-label">Projects</span>
            </a>

            <!-- Reports -->
            <a href="<?= Helpers::url('/reports') ?>" title="Reports" class="nav-item <?= nav_is_active('/reports') ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                <span class="nav-label">Reports</span>
            </a>
            <?php endif; ?>

            <?php if ($role === ROLE_SUPER_ADMIN): ?>
            <!-- Users -->
            <a href="<?= Helpers::url('/users') ?>" title="Users" class="nav-item <?= nav_is_active('/users') ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                <span class="nav-label">Users</span>
            </a>

            <!-- Companies -->
            <a href="<?= Helpers::url('/companies') ?>" title="Companies" class="nav-item <?= nav_is_active('/companies') ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
                <span class="nav-label">Companies</span>
            </a>

            <!-- Settings -->
            <a href="<?= Helpers::url('/settings') ?>" title="Settings" class="nav-item <?= nav_is_active('/settings') ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/></svg>
                <span class="nav-label">Settings</span>
            </a>
            <?php endif; ?>

            <?php if ($role === ROLE_EMPLOYEE): ?>
            <!-- Employee: own reservations only -->
            <a href="<?= Helpers::url('/reservations') ?>" title="My Reservations" class="nav-item <?= nav_is_active('/reservations') ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
                <span class="nav-label">My Reservations</span>
            </a>
            <?php endif; ?>

            <?php if ($role === ROLE_DRIVER): ?>
            <!-- Driver: assigned trips -->
            <a href="<?= Helpers::url('/trips') ?>" title="My Trips" class="nav-item <?= nav_is_active('/trips') ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><path d="M1 3h15v13H1V3zm15 4h4l3 3v6h-7V7zM5.5 20a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm13 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/></svg>
                <span class="nav-label">My Trips</span>
            </a>
            <?php endif; ?>

            <!-- Notifications — all roles -->
            <a href="<?= Helpers::url('/notifications') ?>" title="Notifications" class="nav-item <?= nav_is_active('/notifications') ?>">
                <svg class="nav-icon" viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
                <span class="nav-label">Notifications</span>
            </a>

        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-user-avatar">
                    <?= strtoupper(substr(Auth::fullName() ?? 'U', 0, 1)) ?>
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?= Helpers::e(Auth::fullName() ?? '') ?></div>
                    <div class="sidebar-user-role"><?= Helpers::e(ucfirst(str_replace('_', ' ', Auth::role() ?? ''))) ?></div>
                </div>
            </div>
            <form method="POST" action="<?= Helpers::url('/logout') ?>" id="logoutForm">
                <button type="button" class="nav-item nav-logout" title="Sign out" onclick="document.getElementById('logoutModal').style.display='flex';">
                    <svg class="nav-icon" viewBox="0 0 24 24">
                        <path d="M16 13v-2H7V8l-5 4 5 4v-3h9zm3-10H11c-1.1 0-2 .9-2 2v4h2V5h8v14h-8v-4H9v4c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/>
                    </svg>
                    <span class="nav-label">Sign out</span>
                </button>
            </form>
        </div>

    </aside>

    <!-- ── Overlay (mobile) ────────────────────────────────── -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ── Main content area ─────────────────────────────────── -->
    <div class="main-wrap">

        <!-- Topbar -->
        <header class="topbar">
            <h1 class="topbar-title"><?= Helpers::e($page_title ?? '') ?></h1>
            <div class="topbar-right">
                <a href="<?= Helpers::url('/notifications') ?>" class="topbar-notif" aria-label="Notifications">
                    <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
                </a>
                <a href="<?= Helpers::url('/profile') ?>" class="topbar-avatar" aria-label="Profile">
                    <?= strtoupper(substr(Auth::fullName() ?? 'U', 0, 1)) ?>
                </a>
            </div>
        </header>

        <!-- Flash message (inside main wrap) -->
        <?php $flash = Helpers::getFlash(); ?>
        <?php if ($flash): ?>
        <div class="flash-bar flash-<?= Helpers::e($flash['type']) ?>">
            <?= Helpers::e($flash['message']) ?>
        </div>
        <?php endif; ?>

        <!-- Page content -->
        <main class="main-content">
            <?php require_once $content_view; ?>
        </main>

    </div><!-- /.main-wrap -->

</div><!-- /.app-shell -->

<script src="/lvms/public/js/app.js"></script>

<!-- ── Logout Confirmation Modal ─────────────────────────── -->
<div id="logoutModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-icon modal-icon-danger">
            <svg viewBox="0 0 24 24"><path d="M16 13v-2H7V8l-5 4 5 4v-3h9zm3-10H11c-1.1 0-2 .9-2 2v4h2V5h8v14h-8v-4H9v4c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
        </div>
        <h3 class="modal-title">Confirm Logout</h3>
        <p class="modal-body">Are you sure you want to logout from the portal?</p>
        <div class="modal-actions">
            <button type="button" class="btn btn-outline" onclick="document.getElementById('logoutModal').style.display='none';">Cancel</button>
            <button type="button" class="btn btn-danger" onclick="document.getElementById('logoutForm').submit();">Logout</button>
        </div>
    </div>
</div>
<script>
document.getElementById('logoutModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
</body>
</html>

