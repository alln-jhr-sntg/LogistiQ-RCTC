<?php

class VehicleController
{
    private function render(string $view, array $data = []): void
    {
        extract($data);
        $content_view = __DIR__ . '/../views/vehicles/' . $view . '.php';
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    // GET /vehicles
    public function index(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);

        $statusFilter   = $_GET['status'] ?? '';
        $categoryFilter = (int) ($_GET['category_id'] ?? 0);
        $maintFilter    = $_GET['maint_status'] ?? '';

        $vehicleModel = new VehicleModel();
        $vehicles     = $vehicleModel->findAll($statusFilter, $categoryFilter);
        $categories   = (new VehicleCategoryModel())->findAll();
        $maintVehicles = $vehicleModel->findForMaintenanceReport(null, null, $maintFilter ?: null);

        $this->render('index', [
            'page_title'      => 'Fleet Management',
            'vehicles'        => $vehicles,
            'statusFilter'    => $statusFilter,
            'categoryFilter'  => $categoryFilter,
            'categories'      => $categories,
            'maintVehicles'   => $maintVehicles,
            'maintFilter'     => $maintFilter,
        ]);
    }

    // GET /vehicles/create
    public function create(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);

        $catModel   = new VehicleCategoryModel();
        $categories = $catModel->findAll();
        $purposeModel = new TripPurposeModel();
        $purposes     = $purposeModel->findActive();

        $this->render('create', [
            'page_title' => 'Add Vehicle',
            'categories' => $categories,
            'purposes'   => $purposes,
        ]);
    }

    // POST /vehicles/create
    public function store(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);

        $plate      = strtoupper(trim($_POST['plate_number']   ?? ''));
        $categoryId = (int)   ($_POST['category_id']           ?? 0);
        $brand      = trim(    $_POST['brand']                 ?? '');
        $model      = trim(    $_POST['model']                 ?? '');
        $yearModel  = (int)   ($_POST['year_model']            ?? 0);
        $color      = trim(    $_POST['color']                 ?? '') ?: null;
        $fuelType   = trim(    $_POST['fuel_type']             ?? 'diesel');
        $paxCap     = (int)   ($_POST['passenger_capacity']    ?? 1);
        $cargoCap   = (float) ($_POST['cargo_capacity_kg']     ?? 0);
        $odometer   = (float) ($_POST['current_odometer_km']  ?? 0);
        $gvwr       = (float) ($_POST['gross_weight_kg']       ?? 0);
        $status     = trim(    $_POST['status']                ?? 'available');
        $remarks    = trim(    $_POST['remarks']               ?? '') ?: null;
        $preferredPurposeIds = !empty($_POST['preferred_purpose_ids'])
            ? implode(',', array_map('intval', $_POST['preferred_purpose_ids']))
            : null;

        if ($plate === '' || $categoryId === 0 || $brand === '' ||
            $model === '' || $yearModel === 0  || $gvwr  === 0.0) {
            Helpers::setFlash('error', 'Please fill in all required fields.');
            Helpers::redirect('/vehicles/create');
        }

        try {
            $vehicleModel = new VehicleModel();
            $newId        = $vehicleModel->create([
                'category_id'         => $categoryId,
                'plate_number'        => $plate,
                'brand'               => $brand,
                'model'               => $model,
                'year_model'          => $yearModel,
                'color'               => $color,
                'fuel_type'           => $fuelType,
                'passenger_capacity'  => $paxCap,
                'cargo_capacity_kg'   => $cargoCap,
                'current_odometer_km' => $odometer,
                'gross_weight_kg'     => $gvwr,
                'status'              => $status,
                'remarks'             => $remarks,
                'preferred_purpose_ids' => $preferredPurposeIds,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Helpers::setFlash('error', 'A vehicle with plate "' . $plate . '" already exists.');
                Helpers::redirect('/vehicles/create');
            }
            throw $e;
        }

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'VEHICLE_CREATED',
            'vehicles',
            $newId,
            null,
            ['plate_number' => $plate, 'brand' => $brand, 'model' => $model]
        );

        Helpers::setFlash('success', 'Vehicle ' . $plate . ' added.');
        Helpers::redirect('/vehicles');
    }

    // GET /vehicles/{id}/edit
    public function edit(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);

        $vehicleModel = new VehicleModel();
        $vehicle      = $vehicleModel->findById($id);

        if (!$vehicle) {
            Helpers::setFlash('error', 'Vehicle not found.');
            Helpers::redirect('/vehicles');
        }

        $catModel   = new VehicleCategoryModel();
        $categories = $catModel->findAll();
        $purposeModel = new TripPurposeModel();
        $purposes     = $purposeModel->findActive();

        $this->render('edit', [
            'page_title' => 'Edit Vehicle — ' . $vehicle['plate_number'],
            'vehicle'    => $vehicle,
            'categories' => $categories,
            'purposes'   => $purposes,
        ]);
    }

    // POST /vehicles/{id}/edit
    public function update(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);

        $plate      = strtoupper(trim($_POST['plate_number']   ?? ''));
        $categoryId = (int)   ($_POST['category_id']           ?? 0);
        $brand      = trim(    $_POST['brand']                 ?? '');
        $model      = trim(    $_POST['model']                 ?? '');
        $yearModel  = (int)   ($_POST['year_model']            ?? 0);
        $color      = trim(    $_POST['color']                 ?? '') ?: null;
        $fuelType   = trim(    $_POST['fuel_type']             ?? 'diesel');
        $gvwr       = (float) ($_POST['gross_weight_kg']       ?? 0);
        $odometer   = (float) ($_POST['current_odometer_km']  ?? 0);
        $status     = trim(    $_POST['status']                ?? 'available');
        $remarks    = trim(    $_POST['remarks']               ?? '') ?: null;
        $preferredPurposeIds = !empty($_POST['preferred_purpose_ids'])
            ? implode(',', array_map('intval', $_POST['preferred_purpose_ids']))
            : null;

        if ($plate === '' || $categoryId === 0 || $brand === '' ||
            $model === '' || $yearModel === 0) {
            Helpers::setFlash('error', 'Please fill in all required fields.');
            Helpers::redirect('/vehicles/' . $id . '/edit');
        }

        $vehicleModel = new VehicleModel();
        $old          = $vehicleModel->findById($id);

        if (!$old) {
            Helpers::setFlash('error', 'Vehicle not found.');
            Helpers::redirect('/vehicles');
        }

        try {
            $vehicleModel->update($id, [
                'category_id'         => $categoryId,
                'plate_number'        => $plate,
                'brand'               => $brand,
                'model'               => $model,
                'year_model'          => $yearModel,
                'color'               => $color,
                'fuel_type'           => $fuelType,
                'gross_weight_kg'     => $gvwr,
                'current_odometer_km' => $odometer,
                'status'              => $status,
                'remarks'             => $remarks,
                'preferred_purpose_ids' => $preferredPurposeIds,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Helpers::setFlash('error', 'A vehicle with plate "' . $plate . '" already exists.');
                Helpers::redirect('/vehicles/' . $id . '/edit');
            }
            throw $e;
        }

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'VEHICLE_UPDATED',
            'vehicles',
            $id,
            $old,
            ['plate_number' => $plate, 'status' => $status, 'current_odometer_km' => $odometer]
        );

        Helpers::setFlash('success', 'Vehicle ' . $plate . ' updated.');
        Helpers::redirect('/vehicles');
    }

    // GET /vehicles/categories
    public function categories(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);

        $catModel   = new VehicleCategoryModel();
        $categories = $catModel->findAll();

        $this->render('categories', [
            'page_title' => 'Vehicle Categories',
            'categories' => $categories,
        ]);
    }

    // POST /vehicles/categories
    public function storeCategory(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);

        $name          = trim($_POST['category_name'] ?? '');
        $maxPassengers = max(1, (int)   ($_POST['max_passengers'] ?? 1));
        $maxCargoKg    = max(0.0, (float) ($_POST['max_cargo_kg'] ?? 0));

        if ($name === '') {
            Helpers::setFlash('error', 'Category name is required.');
            Helpers::redirect('/vehicles/categories');
        }

        try {
            $catModel = new VehicleCategoryModel();
            $newId    = $catModel->create($name, $maxPassengers, $maxCargoKg);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Helpers::setFlash('error', 'A category with that name already exists.');
                Helpers::redirect('/vehicles/categories');
            }
            throw $e;
        }

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'VEHICLE_CATEGORY_CREATED',
            'vehicle_categories',
            $newId,
            null,
            ['category_name' => $name, 'max_passengers' => $maxPassengers, 'max_cargo_kg' => $maxCargoKg]
        );

        Helpers::setFlash('success', 'Category "' . $name . '" added.');
        Helpers::redirect('/vehicles/categories');
    }

    // POST /vehicles/categories/{id}/edit
    public function updateCategory(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);

        $name          = trim($_POST['category_name'] ?? '');
        $maxPassengers = max(1, (int)   ($_POST['max_passengers'] ?? 1));
        $maxCargoKg    = max(0.0, (float) ($_POST['max_cargo_kg'] ?? 0));

        if ($name === '') {
            Helpers::setFlash('error', 'Category name is required.');
            Helpers::redirect('/vehicles/categories');
        }

        $catModel = new VehicleCategoryModel();
        $old      = $catModel->findById($id);

        if (!$old) {
            Helpers::setFlash('error', 'Category not found.');
            Helpers::redirect('/vehicles/categories');
        }

        try {
            $catModel->update($id, $name, $maxPassengers, $maxCargoKg);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Helpers::setFlash('error', 'A category with that name already exists.');
                Helpers::redirect('/vehicles/categories');
            }
            throw $e;
        }

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'VEHICLE_CATEGORY_UPDATED',
            'vehicle_categories',
            $id,
            $old,
            ['category_name' => $name, 'max_passengers' => $maxPassengers, 'max_cargo_kg' => $maxCargoKg]
        );

        Helpers::setFlash('success', 'Category updated.');
        Helpers::redirect('/vehicles/categories');
    }

    // GET /vehicles/{id}/maintenance
    public function maintenance(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);

        $vehicleModel = new VehicleModel();
        $vehicle      = $vehicleModel->findById($id);

        if (!$vehicle) {
            Helpers::setFlash('error', 'Vehicle not found.');
            Helpers::redirect('/vehicles');
        }

        $filters = array_filter([
            'date_from'        => $_GET['date_from']        ?? '',
            'date_to'          => $_GET['date_to']          ?? '',
            'maintenance_type' => $_GET['maintenance_type'] ?? '',
        ], fn($v) => $v !== '');

        $maintModel = new VehicleMaintenanceModel();
        $latest     = $maintModel->getLatestByVehicle($id);

        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $total      = $maintModel->countByVehicle($id, $filters);
        $baseQuery  = http_build_query(array_merge(['url' => 'vehicles/' . $id . '/maintenance'], $filters));
        $pagination = Helpers::paginate($total, $page, 10, $baseQuery);
        $history    = $maintModel->findByVehicle($id, $filters, $pagination['limit'], $pagination['offset']);

        // Pre-compute maintenance alert state in the controller so
        // the view only has to render what it receives.
        $maintenanceAlert = null;
        if ($latest && $latest['next_service_km'] !== null) {
            $currentOdo   = (float) $vehicle['current_odometer_km'];
            $nextServiceKm = (float) $latest['next_service_km'];
            $kmRemaining  = $nextServiceKm - $currentOdo;

            if ($kmRemaining <= 0) {
                $maintenanceAlert = [
                    'type'         => 'overdue',
                    'next_km'      => $nextServiceKm,
                    'km_remaining' => $kmRemaining,
                ];
            } elseif ($kmRemaining <= 500) {
                $maintenanceAlert = [
                    'type'         => 'due_soon',
                    'next_km'      => $nextServiceKm,
                    'km_remaining' => $kmRemaining,
                ];
            }
        }

        $this->render('maintenance', [
            'page_title'        => 'Maintenance — ' . $vehicle['plate_number'],
            'vehicle'           => $vehicle,
            'latest'            => $latest,
            'history'           => $history,
            'maintenanceAlert'  => $maintenanceAlert,
            'filters'           => array_merge(
                ['date_from' => '', 'date_to' => '', 'maintenance_type' => ''],
                $filters
            ),
            'pagination'        => $pagination['html'],
        ]);
    }

    // POST /vehicles/{id}/maintenance
    public function storeMaintenance(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);

        $maintType  = trim($_POST['maintenance_type']    ?? '');
        $serviceDate = trim($_POST['service_date']       ?? '');
        $desc       = trim($_POST['description']         ?? '') ?: null;
        $odoAtSvc   = $_POST['odometer_at_service'] !== '' ? (float) $_POST['odometer_at_service'] : null;
        $nextSvcKm  = $_POST['next_service_km']     !== '' ? (float) $_POST['next_service_km']     : null;
        $nextSvcDate = trim($_POST['next_service_date']  ?? '') ?: null;
        $cost       = $_POST['cost']                !== '' ? (float) $_POST['cost']                : null;
        $perfBy     = trim($_POST['performed_by']        ?? '') ?: null;

        if ($maintType === '' || $serviceDate === '') {
            Helpers::setFlash('error', 'Maintenance type and service date are required.');
            Helpers::redirect('/vehicles/' . $id . '/maintenance');
        }

        if (!in_array($maintType, MAINTENANCE_TYPES, true)) {
            Helpers::setFlash('error', 'Invalid maintenance type.');
            Helpers::redirect('/vehicles/' . $id . '/maintenance');
        }

        // Validate that next_service_km makes logical sense.
        // If explicitly provided, it must be greater than the odometer
        // at service — otherwise the vehicle would be immediately overdue.
        if ($nextSvcKm !== null && $odoAtSvc !== null && $nextSvcKm <= $odoAtSvc) {
            Helpers::setFlash(
                'error',
                'Next service km (' . number_format($nextSvcKm, 0) . ') '
                . 'must be greater than the odometer at service ('
                . number_format($odoAtSvc, 0) . ').'
            );
            Helpers::redirect('/vehicles/' . $id . '/maintenance');
        }

        // Auto-calculate next_service_km if not provided.
        // Mirrors VehicleRecommendationService's fallback exactly: the
        // system setting is the live value, MAINTENANCE_INTERVAL_KM is only
        // the fallback when the row is missing or <= 0 — not a bare 5000
        // literal, so the two call sites can't drift apart.
        if ($nextSvcKm === null && $odoAtSvc !== null) {
            $settingModel = new SystemSettingModel();
            $interval     = (float) ($settingModel->getByKey('maintenance_interval_km') ?? MAINTENANCE_INTERVAL_KM);
            if ($interval <= 0) {
                $interval = MAINTENANCE_INTERVAL_KM;
            }
            $nextSvcKm    = $odoAtSvc + $interval;
        }

        $maintModel = new VehicleMaintenanceModel();
        $maintModel->create($id, (int) Auth::id(), [
            'maintenance_type'    => $maintType,
            'description'         => $desc,
            'odometer_at_service' => $odoAtSvc,
            'service_date'        => $serviceDate,
            'next_service_date'   => $nextSvcDate,
            'next_service_km'     => $nextSvcKm,
            'cost'                => $cost,
            'performed_by'        => $perfBy,
        ]);

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'MAINTENANCE_LOGGED',
            'vehicle_maintenance',
            null,
            null,
            ['vehicle_id' => $id, 'maintenance_type' => $maintType, 'service_date' => $serviceDate]
        );

        Helpers::setFlash('success', 'Maintenance record saved.');
        Helpers::redirect('/vehicles/' . $id . '/maintenance');
    }

    // POST /vehicles/{id}/maintenance/check
    public function checkMaintenance(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);

        $vehicleModel = new VehicleModel();
        $vehicle      = $vehicleModel->findById($id);

        if (!$vehicle) {
            Helpers::setFlash('error', 'Vehicle not found.');
            Helpers::redirect('/vehicles');
        }

        $result = MaintenanceService::checkAfterTrip(
            $id,
            (float) $vehicle['current_odometer_km']
        );

        $flashes = [
            'notified_overdue'    => ['success', $vehicle['plate_number'] . ' is overdue for service — admins have been notified.'],
            'notified_due_soon'   => ['success', $vehicle['plate_number'] . ' is approaching its service interval — admins have been notified.'],
            'skipped_dedup'       => ['info',    'A maintenance notification for ' . $vehicle['plate_number'] . ' was already sent in the last 24 hours.'],
            'skipped_no_baseline' => ['info',    'No maintenance history on record for ' . $vehicle['plate_number'] . ' — log a service entry first to enable this check.'],
            'ok'                  => ['info',    $vehicle['plate_number'] . ' odometer is within the service interval. No notification needed.'],
        ];

        [$type, $message] = $flashes[$result] ?? ['info', 'Maintenance check complete.'];
        Helpers::setFlash($type, $message);
        Helpers::redirect('/vehicles/' . $id . '/maintenance');
    }
}
