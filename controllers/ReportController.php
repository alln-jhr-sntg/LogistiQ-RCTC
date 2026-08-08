<?php

class ReportController
{
    private function render(string $view, array $data = []): void
    {
        extract($data);
        $content_view = __DIR__ . '/../views/reports/' . $view . '.php';
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    // Redirect /reports to the first report
    public function index(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN);
        Helpers::redirect('/reports/trip-history');
    }

    // ── a) Trip History ───────────────────────────────────────────

    // GET /reports/trip-history
    public function tripHistory(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN);

        $role    = Auth::role();
        $filters = array_filter([
            'date_from'   => $_GET['date_from']                          ?? '',
            'date_to'     => $_GET['date_to']                            ?? '',
            'trip_status' => $_GET['trip_status']                        ?? '',
            'driver_id'   => (int) ($_GET['driver_id']   ?? 0) ?: null,
            'vehicle_id'  => (int) ($_GET['vehicle_id']  ?? 0) ?: null,
        ], fn($v) => $v !== '' && $v !== null);

        $deptIds = [];
        if ($role === ROLE_ADMIN) {
            $accessModel = new AdminDepartmentAccessModel();
            $deptIds     = array_column(
                $accessModel->getByAdmin((int) Auth::id()), 'department_id'
            );
        }

        $tripModel    = new TripModel();
        $vehicleModel = new VehicleModel();
        $userModel    = new UserModel();

        $this->render('trip_history', [
            'page_title' => 'Trip History',
            'trips'      => $tripModel->findForReport($filters, $deptIds),
            'vehicles'   => $vehicleModel->findAll(),
            'drivers'    => $userModel->findByRole(ROLE_DRIVER),
            'filters'    => array_merge([
                'date_from'   => '',
                'date_to'     => '',
                'trip_status' => '',
                'driver_id'   => '',
                'vehicle_id'  => '',
            ], $filters),
        ]);
    }

    // ── b) Maintenance Due ────────────────────────────────────────

    // GET /reports/maintenance-due
    public function maintenanceDue(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN);

        $vehicleModel = new VehicleModel();
        $vehicles     = $vehicleModel->findForMaintenanceReport();

        $overdue = $dueSoon = $ok = $noBaseline = 0;
        foreach ($vehicles as $v) {
            $next = (float) ($v['next_service_km'] ?? 0);
            $curr = (float)  $v['current_odometer_km'];
            if ($next === 0.0) {
                $noBaseline++;
            } elseif ($curr >= $next) {
                $overdue++;
            } elseif ($curr >= $next - 500) {
                $dueSoon++;
            } else {
                $ok++;
            }
        }

        $this->render('maintenance_due', [
            'page_title'    => 'Maintenance Due',
            'vehicles'      => $vehicles,
            'overdue'       => $overdue,
            'due_soon'      => $dueSoon,
            'ok'            => $ok,
            'no_baseline'   => $noBaseline,
        ]);
    }

    // ── c) Vehicle Utilization ────────────────────────────────────

    // GET /reports/vehicle-utilization
    public function vehicleUtilization(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN);

        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo   = $_GET['date_to']   ?? '';

        $vehicleModel = new VehicleModel();
        $vehicles     = $vehicleModel->findForUtilizationReport($dateFrom, $dateTo);

        $fleetCount = count($vehicles);
        $totalTrips = (int) array_sum(array_column($vehicles, 'trip_count'));
        $totalKm    = (float) array_sum(array_column($vehicles, 'total_km'));
        $avgTrips   = $fleetCount > 0 ? round($totalTrips / $fleetCount, 1) : 0;

        $this->render('vehicle_utilization', [
            'page_title'   => 'Vehicle Utilization',
            'vehicles'     => $vehicles,
            'date_from'    => $dateFrom,
            'date_to'      => $dateTo,
            'fleet_count'  => $fleetCount,
            'total_trips'  => $totalTrips,
            'total_km'     => $totalKm,
            'avg_trips'    => $avgTrips,
        ]);
    }

    // ── Export stub (deferred) ────────────────────────────────────

    public function export(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN);
        Helpers::setFlash('info', 'Export will be available in a future update.');
        $ref    = $_SERVER['HTTP_REFERER'] ?? '';
        parse_str(parse_url($ref, PHP_URL_QUERY) ?? '', $params);
        $back   = $params['url'] ?? 'reports/trip-history';
        Helpers::redirect('/' . ltrim($back, '/'));
    }
}
