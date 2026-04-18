<?php

class SettingsController
{
    private function render(string $view, array $data = []): void
    {
        extract($data);
        $content_view = __DIR__ . '/../views/settings/' . $view . '.php';
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    // GET /settings
    public function index(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN);

        // Cap 1: static placeholder rows matching the seeded system_settings keys
        $settings = [
            ['setting_key' => 'warehouse_address',      'setting_value' => 'Insert Warehouse Address Here', 'description' => 'Fixed dispatch/return point for all trips'],
            ['setting_key' => 'warehouse_lat',           'setting_value' => '14.680456',                   'description' => 'Warehouse GPS latitude'],
            ['setting_key' => 'warehouse_lng',           'setting_value' => '121.028051',                   'description' => 'Warehouse GPS longitude'],
            ['setting_key' => 'system_name',             'setting_value' => 'LogistiQ', 'description' => 'Application display name'],
            ['setting_key' => 'company_name',            'setting_value' => 'Remix Construction and Trading Corporation', 'description' => 'Primary company name'],
            ['setting_key' => 'reservation_prefix',      'setting_value' => 'RES',                          'description' => 'Prefix used for reservation codes'],
            ['setting_key' => 'maintenance_interval_km', 'setting_value' => '5000',                         'description' => 'Standard vehicle maintenance interval in km'],
        ];

        $this->render('index', ['page_title' => 'System Settings', 'settings' => $settings]);
    }

    // POST /settings
    public function update(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN);
        Helpers::setFlash('success', 'Settings saved. (Capstone 1 — not saved to DB)');
        Helpers::redirect('/settings');
    }
}
