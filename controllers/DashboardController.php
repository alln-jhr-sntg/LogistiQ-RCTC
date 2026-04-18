<?php

class DashboardController
{
    private function render(string $view, array $data = []): void
    {
        extract($data);
        $content_view = __DIR__ . '/../views/dashboard/' . $view . '.php';
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    public function superAdmin(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN);
        $this->render('super_admin', [
            'page_title' => 'Dashboard',
            'stats' => ['total_vehicles' => '—', 'active_trips' => '—', 'pending_res' => '—', 'total_users' => '—'],
        ]);
    }

    public function admin(): void
    {
        Auth::requireRole(ROLE_ADMIN);
        $this->render('admin', [
            'page_title' => 'Dashboard',
            'stats' => ['pending_res' => '—', 'approved_today' => '—', 'active_trips' => '—', 'vehicles_avail' => '—'],
        ]);
    }

    public function employee(): void
    {
        Auth::requireRole(ROLE_EMPLOYEE);
        $this->render('employee', [
            'page_title' => 'Dashboard',
            'stats' => ['my_pending' => '—', 'my_approved' => '—', 'my_completed' => '—'],
        ]);
    }

    public function driver(): void
    {
        Auth::requireRole(ROLE_DRIVER);
        $this->render('driver', [
            'page_title' => 'Dashboard',
            'stats' => ['assigned_trips' => '—', 'completed_trips' => '—'],
        ]);
    }
}
