<?php
class ReportController {
    private function render(string $view, array $data = []): void { extract($data); $content_view = __DIR__ . '/../views/reports/' . $view . '.php'; require_once __DIR__ . '/../views/layouts/main.php'; }
    public function index(): void               { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); Helpers::redirect('/reports/trip-history'); }
    public function tripHistory(): void         { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); $this->render('trip_history', ['page_title' => 'Trip History']); }
    public function maintenanceDue(): void      { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); $this->render('maintenance_due', ['page_title' => 'Maintenance Due']); }
    public function vehicleUtilization(): void  { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); $this->render('vehicle_utilization', ['page_title' => 'Vehicle Utilization']); }
    public function export(): void {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN);
        Helpers::setFlash('info', 'Export will be available in Capstone 2.');
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        parse_str(parse_url($ref, PHP_URL_QUERY) ?? '', $params);
        $back = $params['url'] ?? 'reports/trip-history';
        Helpers::redirect('/' . ltrim($back, '/'));
    }}
