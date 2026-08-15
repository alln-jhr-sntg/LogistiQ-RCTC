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

    // ── b) Maintenance History ──────────────────────────────────────

    // GET /reports/maintenance-history
    public function maintenanceHistory(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $filters = array_filter([
            'date_from'        => $_GET['date_from']        ?? '',
            'date_to'          => $_GET['date_to']          ?? '',
            'vehicle_id'       => (int) ($_GET['vehicle_id'] ?? 0) ?: null,
            'maintenance_type' => $_GET['maintenance_type']  ?? '',
        ], fn($v) => $v !== '' && $v !== null);

        $maintModel   = new VehicleMaintenanceModel();
        $vehicleModel = new VehicleModel();

        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $total      = $maintModel->countForReport($filters);
        $baseQuery  = http_build_query(array_merge(['url' => 'reports/maintenance-history'], $filters));
        $pagination = Helpers::paginate($total, $page, 10, $baseQuery);

        $this->render('maintenance_history', [
            'page_title' => 'Maintenance History',
            'records'    => $maintModel->findForReport($filters, $pagination['limit'], $pagination['offset']),
            'vehicles'   => $vehicleModel->findAll(),
            'filters'    => array_merge([
                'date_from'        => '',
                'date_to'          => '',
                'vehicle_id'       => '',
                'maintenance_type' => '',
            ], $filters),
            'pagination' => $pagination['html'],
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
        // current page, so the unbounded list is fetched once and reused
        // both for the counts below and as the pagination total.
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
        } elseif (str_contains($url, 'maintenance-history')) {
            $this->exportMaintenanceHistory();
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
            ['Reservation', 'Purpose', 'Vehicle', 'Driver', 'Destination', 'Departure', 'Return', 'Distance (km)', 'Status'],
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
                    $t['actual_return'] ? date('Y-m-d H:i', strtotime($t['actual_return'])) : '',
                    $distance,
                    ucwords(str_replace('_', ' ', $t['trip_status'])),
                ];
            }
        );
    }

    private function exportMaintenanceHistory(): void
    {
        $filters = array_filter([
            'date_from'        => $_POST['date_from']        ?? '',
            'date_to'          => $_POST['date_to']          ?? '',
            'vehicle_id'       => (int) ($_POST['vehicle_id'] ?? 0) ?: null,
            'maintenance_type' => $_POST['maintenance_type']  ?? '',
        ], fn($v) => $v !== '' && $v !== null);

        $records = (new VehicleMaintenanceModel())->findForReport($filters);

        $this->streamCsv(
            'maintenance_history_' . date('Y-m-d') . '.csv',
            ['Date', 'Vehicle', 'Type', 'Odometer (km)', 'Next Service (km)', 'Cost', 'Performed By', 'Recorded By', 'Description'],
            $records,
            fn(array $r): array => [
                date('Y-m-d', strtotime($r['service_date'])),
                trim($r['plate_number'] . ' — ' . $r['brand'] . ' ' . $r['model']),
                $r['maintenance_type'],
                $r['odometer_at_service'] !== null ? (string) (float) $r['odometer_at_service'] : '',
                $r['next_service_km']     !== null ? (string) (float) $r['next_service_km']     : '',
                $r['cost']                !== null ? (string) (float) $r['cost']                : '',
                (string) $r['performed_by'],
                trim($r['first_name'] . ' ' . $r['last_name']),
                (string) $r['description'],
            ]
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
     *
     * A plain CSV carries no column-width info, so Excel always opens it
     * with uniform default-width columns and long values visually spill
     * into their neighbors. Rather than a real spreadsheet format, the first
     * row is a one-cell tip on the keyboard shortcut to auto-fit — cheap and
     * sits above the real header row, so it never shifts column data.
     */
    private function streamCsv(string $filename, array $headers, array $rows, callable $rowMapper): never
    {
        // config/app.php:14 leaves an output buffer active for the whole
        // request; discard anything already in it so nothing prepends
        // itself to the CSV and corrupts the file.
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        // Excel on Windows misreads UTF-8 without a BOM and mangles
        // characters like ñ and é — common in Philippine place names.
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Tip: select all cells (Ctrl+A), then press Alt, H, O, I to auto-fit these columns to their content.']);
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, $rowMapper($row));
        }
        fclose($out);
        exit;
    }
}
