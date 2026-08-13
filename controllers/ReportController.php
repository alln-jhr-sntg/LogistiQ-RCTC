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
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);
        Helpers::redirect('/reports/trip-history');
    }

    // ── a) Trip History ───────────────────────────────────────────

    // GET /reports/trip-history
    public function tripHistory(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $filters = array_filter([
            'date_from'   => $_GET['date_from']                          ?? '',
            'date_to'     => $_GET['date_to']                            ?? '',
            'trip_status' => $_GET['trip_status']                        ?? '',
            'driver_id'   => (int) ($_GET['driver_id']   ?? 0) ?: null,
            'vehicle_id'  => (int) ($_GET['vehicle_id']  ?? 0) ?: null,
        ], fn($v) => $v !== '' && $v !== null);

        // Every company's trips are visible here — transparency is intentional.
        $tripModel    = new TripModel();
        $vehicleModel = new VehicleModel();
        $userModel    = new UserModel();

        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $total      = $tripModel->countForReport($filters);
        $baseQuery  = http_build_query(array_merge(['url' => 'reports/trip-history'], $filters));
        $pagination = Helpers::paginate($total, $page, 10, $baseQuery);

        $this->render('trip_history', [
            'page_title' => 'Trip History',
            'trips'      => $tripModel->findForReport($filters, null, $pagination['limit'], $pagination['offset']),
            'vehicles'   => $vehicleModel->findAll(),
            'drivers'    => $userModel->findByRole(ROLE_DRIVER),
            'filters'    => array_merge([
                'date_from'   => '',
                'date_to'     => '',
                'trip_status' => '',
                'driver_id'   => '',
                'vehicle_id'  => '',
            ], $filters),
            'pagination' => $pagination['html'],
        ]);
    }

    // ── b) Maintenance Due ────────────────────────────────────────

    // GET /reports/maintenance-due
    public function maintenanceDue(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $vehicleModel = new VehicleModel();

        // Stat cards must reflect the whole fleet, not just the current
        // page, so the unbounded list is fetched once and reused both for
        // the counts below and as the pagination total — a second, cheap
        // COUNT query would just recompute a number already in hand.
        $allVehicles = $vehicleModel->findForMaintenanceReport();

        $overdue = $dueSoon = $ok = $noBaseline = 0;
        foreach ($allVehicles as $v) {
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

        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $pagination = Helpers::paginate(count($allVehicles), $page, 10, 'url=reports/maintenance-due');
        $vehicles   = $vehicleModel->findForMaintenanceReport($pagination['limit'], $pagination['offset']);

        $this->render('maintenance_due', [
            'page_title'    => 'Maintenance Due',
            'vehicles'      => $vehicles,
            'overdue'       => $overdue,
            'due_soon'      => $dueSoon,
            'ok'            => $ok,
            'no_baseline'   => $noBaseline,
            'pagination'    => $pagination['html'],
        ]);
    }

    // ── c) Vehicle Utilization ────────────────────────────────────

    // GET /reports/vehicle-utilization
    public function vehicleUtilization(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo   = $_GET['date_to']   ?? '';

        $vehicleModel = new VehicleModel();

        // Stat cards must reflect the whole filtered fleet, not just the
        // current page — same reasoning as maintenanceDue() above.
        $allVehicles = $vehicleModel->findForUtilizationReport($dateFrom, $dateTo);

        $fleetCount = count($allVehicles);
        $totalTrips = (int) array_sum(array_column($allVehicles, 'trip_count'));
        $totalKm    = (float) array_sum(array_column($allVehicles, 'total_km'));
        $avgTrips   = $fleetCount > 0 ? round($totalTrips / $fleetCount, 1) : 0;

        $page         = max(1, (int) ($_GET['page'] ?? 1));
        $queryFilters = array_filter(['date_from' => $dateFrom, 'date_to' => $dateTo], fn($v) => $v !== '');
        $baseQuery    = http_build_query(array_merge(['url' => 'reports/vehicle-utilization'], $queryFilters));
        $pagination   = Helpers::paginate($fleetCount, $page, 10, $baseQuery);

        $vehicles = $vehicleModel->findForUtilizationReport($dateFrom, $dateTo, $pagination['limit'], $pagination['offset']);

        $this->render('vehicle_utilization', [
            'page_title'   => 'Vehicle Utilization',
            'vehicles'     => $vehicles,
            'date_from'    => $dateFrom,
            'date_to'      => $dateTo,
            'fleet_count'  => $fleetCount,
            'total_trips'  => $totalTrips,
            'total_km'     => $totalKm,
            'avg_trips'    => $avgTrips,
            'pagination'   => $pagination['html'],
        ]);
    }

    // ── Export ───────────────────────────────────────────────────
    // All three /reports/{report}/export routes point here (see index.php) —
    // the report is disambiguated from $_GET['url'], which the router always
    // populates the same way for GET and POST. Each export reads its filters
    // from POST (mirrored as hidden fields on the report's export form, so
    // it always matches whatever is currently applied on screen) and pulls
    // the FULL filtered set from the model — no LIMIT/OFFSET — so the CSV
    // is never truncated to the current page.

    public function export(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $url = $_GET['url'] ?? '';

        if (str_contains($url, 'trip-history')) {
            $this->exportTripHistory();
        } elseif (str_contains($url, 'maintenance-due')) {
            $this->exportMaintenanceDue();
        } elseif (str_contains($url, 'vehicle-utilization')) {
            $this->exportVehicleUtilization();
        } else {
            Helpers::redirect('/reports/trip-history');
        }
    }

    private function exportTripHistory(): void
    {
        $filters = array_filter([
            'date_from'   => $_POST['date_from']                          ?? '',
            'date_to'     => $_POST['date_to']                            ?? '',
            'trip_status' => $_POST['trip_status']                        ?? '',
            'driver_id'   => (int) ($_POST['driver_id']   ?? 0) ?: null,
            'vehicle_id'  => (int) ($_POST['vehicle_id']  ?? 0) ?: null,
        ], fn($v) => $v !== '' && $v !== null);

        $trips = (new TripModel())->findForReport($filters);

        $this->streamCsv(
            'trip_history_' . date('Y-m-d') . '.csv',
            ['Reservation', 'Purpose', 'Vehicle', 'Driver', 'Destination', 'Departure', 'Distance (km)', 'Status'],
            $trips,
            function (array $t): array {
                $distance = ($t['odometer_start_km'] !== null && $t['odometer_end_km'] !== null)
                    ? (string) ((float) $t['odometer_end_km'] - (float) $t['odometer_start_km'])
                    : '';

                return [
                    $t['reservation_code'],
                    $t['purpose_name'],
                    trim($t['plate_number'] . ' — ' . $t['vehicle_brand'] . ' ' . $t['vehicle_model']),
                    trim($t['driver_first_name'] . ' ' . $t['driver_last_name']),
                    $t['destination'],
                    $t['actual_departure'] ? date('Y-m-d H:i', strtotime($t['actual_departure'])) : '',
                    $distance,
                    ucwords(str_replace('_', ' ', $t['trip_status'])),
                ];
            }
        );
    }

    private function exportMaintenanceDue(): void
    {
        $vehicles = (new VehicleModel())->findForMaintenanceReport();

        $this->streamCsv(
            'maintenance_due_' . date('Y-m-d') . '.csv',
            ['Plate Number', 'Vehicle', 'Current Odometer (km)', 'Next Service (km)', 'Remaining (km)', 'Last Service', 'Alert'],
            $vehicles,
            function (array $v): array {
                $curr = (float) $v['current_odometer_km'];
                $next = $v['next_service_km'] !== null ? (float) $v['next_service_km'] : null;

                if ($next === null) {
                    $alert     = 'No Baseline';
                    $remaining = '';
                } elseif ($curr >= $next) {
                    $alert     = 'Overdue';
                    $remaining = (string) ($curr - $next) . ' over';
                } elseif ($curr >= $next - 500) {
                    $alert     = 'Due Soon';
                    $remaining = (string) ($next - $curr);
                } else {
                    $alert     = 'OK';
                    $remaining = (string) ($next - $curr);
                }

                return [
                    $v['plate_number'],
                    trim($v['brand'] . ' ' . $v['model'] . ' ' . $v['year_model']),
                    (string) $curr,
                    $next !== null ? (string) $next : '',
                    $remaining,
                    $v['last_service_date'] ? date('Y-m-d', strtotime($v['last_service_date'])) : '',
                    $alert,
                ];
            }
        );
    }

    private function exportVehicleUtilization(): void
    {
        $dateFrom = $_POST['date_from'] ?? '';
        $dateTo   = $_POST['date_to']   ?? '';

        $vehicles = (new VehicleModel())->findForUtilizationReport($dateFrom, $dateTo);

        $this->streamCsv(
            'vehicle_utilization_' . date('Y-m-d') . '.csv',
            ['Plate Number', 'Vehicle', 'Category', 'Completed Trips', 'Distance (km)', 'Current Odometer (km)', 'Status'],
            $vehicles,
            fn(array $v): array => [
                $v['plate_number'],
                trim($v['brand'] . ' ' . $v['model'] . ' ' . $v['year_model']),
                $v['category_name'],
                (string) $v['trip_count'],
                (string) $v['total_km'],
                (string) $v['current_odometer_km'],
                ucwords(str_replace('_', ' ', $v['status'])),
            ]
        );
    }

    /**
     * Stream $rows as a CSV attachment named $filename. $rowMapper converts
     * one model row into a flat array of CSV cell values, in $headers order.
     * Terminates execution — never returns.
     */
    private function streamCsv(string $filename, array $headers, array $rows, callable $rowMapper): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, $rowMapper($row));
        }
        fclose($out);
        exit;
    }
}
