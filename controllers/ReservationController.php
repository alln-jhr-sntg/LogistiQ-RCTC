<?php

class ReservationController
{
    private function render(string $view, array $data = []): void
    {
        extract($data);
        $content_view = __DIR__ . '/../views/reservations/' . $view . '.php';
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    // ── Step 9 — Reservation list ─────────────────────────────────

    // GET /reservations
    public function index(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE);

        $role   = Auth::role();
        $status = $_GET['status'] ?? '';

        $resModel = new ReservationModel();

        if ($role === ROLE_EMPLOYEE) {
            $reservations = $resModel->findForEmployee((int) Auth::id(), $status);
        } else {
            // Admin, fleet_admin and super_admin see every company's
            // reservations here — transparency is intentional. Acting on a
            // record outside your own company is blocked separately, at the
            // point of action (review/approve/reject/cancel/edit).
            $reservations = $resModel->findAll($status);
        }

        $this->render('index', [
            'page_title'   => 'Reservations',
            'reservations' => $reservations,
            'statusFilter' => $status,
        ]);
    }

    // ── Step 9 — Reservation create ───────────────────────────────

    // GET /reservations/create
    public function create(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE);

        $purposeModel = new TripPurposeModel();
        $projectModel = new ProjectModel();

        // Projects scoped to the employee's company
        $companyId = (int) Auth::companyId();
        $projects  = $projectModel->findActiveByCompany($companyId);
        $purposes  = $purposeModel->findActive();

        $this->render('create', [
            'page_title' => 'New Reservation',
            'purposes'   => $purposes,
            'projects'   => $projects,
        ]);
    }

    // POST /reservations/create
    public function store(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE);

        $deptId    = Auth::departmentId();
        if (!$deptId) {
            Helpers::setFlash('error',
                'Your account has no department assigned. '
                . 'Contact your admin before creating a reservation.');
            Helpers::redirect('/reservations');
        }

        $purposeId  = (int)   ($_POST['purpose_id']         ?? 0);
        $projectId  = $_POST['project_id'] !== '' ? (int) $_POST['project_id'] : null;
        $dest       = trim(    $_POST['destination']         ?? '');
        $destLat    = $_POST['destination_lat'] !== '' ? (float) $_POST['destination_lat'] : null;
        $destLng    = $_POST['destination_lng'] !== '' ? (float) $_POST['destination_lng'] : null;
        $details    = trim(    $_POST['trip_details']         ?? '') ?: null;
        $pax        = (int)   ($_POST['passenger_count']     ?? 0);
        $cargo      = (float) ($_POST['cargo_weight_kg']     ?? 0);
        $cargoDesc  = trim(    $_POST['cargo_description']   ?? '') ?: null;
        $departure  = trim(    $_POST['departure_datetime']  ?? '');
        $return     = trim(    $_POST['return_datetime']     ?? '');

        if ($purposeId === 0 || $dest === '' || $pax < 1 ||
            $departure === '' || $return === '') {
            Helpers::setFlash('error', 'Please fill in all required fields.');
            Helpers::redirect('/reservations/create');
        }

        if ($departure >= $return) {
            Helpers::setFlash('error', 'Return time must be after departure time.');
            Helpers::redirect('/reservations/create');
        }

        // Check project requirement
        $purposeModel = new TripPurposeModel();
        $purpose      = $purposeModel->findById($purposeId);

        if ($purpose && (int) $purpose['requires_project'] === 1 && !$projectId) {
            Helpers::setFlash('error', 'A project is required for this trip purpose.');
            Helpers::redirect('/reservations/create');
        }

        // TripLimitService check
        if ($projectId && $purpose) {
            $limitError = TripLimitService::check($projectId, $purposeId);
            if ($limitError !== null) {
                Helpers::setFlash('error', $limitError);
                Helpers::redirect('/reservations/create');
            }
        }

        $resModel = new ReservationModel();
        $newId    = $resModel->create([
            'requested_by'       => (int) Auth::id(),
            'department_id'      => (int) $deptId,
            'purpose_id'         => $purposeId,
            'project_id'         => $projectId,
            'destination'        => $dest,
            'destination_lat'    => $destLat,
            'destination_lng'    => $destLng,
            'trip_details'       => $details,
            'passenger_count'    => $pax,
            'cargo_weight_kg'    => $cargo,
            'cargo_description'  => $cargoDesc,
            'departure_datetime' => $departure,
            'return_datetime'    => $return,
        ]);

        // Fetch the generated code for audit log
        $newRes = $resModel->findById($newId);

        // Audit log
        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'RESERVATION_CREATED',
            'reservations',
            $newId,
            null,
            ['reservation_code' => $newRes['reservation_code'],
             'destination'       => $dest]
        );

        // Notify all admins who can see this department + super_admins
        $this->notifyAdminsOfNewReservation($newId, (int) $deptId, $newRes['reservation_code'], $dest);

        Helpers::setFlash('success',
            'Reservation ' . $newRes['reservation_code'] . ' submitted.');
        Helpers::redirect('/reservations/' . $newId);
    }

    // ── Step 9 — Reservation detail ───────────────────────────────

    // GET /reservations/{id}
    public function detail(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE);

        $resModel    = new ReservationModel();
        $reservation = $resModel->findById($id);

        if (!$reservation) {
            Helpers::setFlash('error', 'Reservation not found.');
            Helpers::redirect('/reservations');
        }

        // Access control: employees can only see their own reservations
        $role = Auth::role();
        if ($role === ROLE_EMPLOYEE &&
            (int) $reservation['requested_by'] !== (int) Auth::id()) {
            Helpers::setFlash('error', 'Access denied.');
            Helpers::redirect('/reservations');
        }

        $canEdit = $role === ROLE_EMPLOYEE
            && $reservation['status'] === 'pending'
            && (int) $reservation['requested_by'] === (int) Auth::id();

        $canCancel = false;
        if ($role === ROLE_EMPLOYEE && $reservation['status'] === 'pending'
            && (int) $reservation['requested_by'] === (int) Auth::id()) {
            $canCancel = true;
        }
        if (in_array($role, [ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN], true)
            && in_array($reservation['status'], ['pending', 'approved'])) {
            $canCancel = true;
        }

        $this->render('detail', [
            'page_title'   => $reservation['reservation_code'],
            'reservation'  => $reservation,
            'canEdit'      => $canEdit,
            'canCancel'    => $canCancel,
        ]);
    }

    // ── Step 9 — Reservation cancel ───────────────────────────────

    // POST /reservations/{id}/cancel
    public function cancel(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE);

        $reason = trim($_POST['cancellation_reason'] ?? '');
        if ($reason === '') {
            Helpers::setFlash('error', 'Cancellation reason is required.');
            Helpers::redirect('/reservations/' . $id);
        }

        $resModel    = new ReservationModel();
        $reservation = $resModel->findById($id);

        if (!$reservation) {
            Helpers::setFlash('error', 'Reservation not found.');
            Helpers::redirect('/reservations');
        }

        Auth::requireCompanyScope((int) $reservation['company_id'], '/reservations/' . $id);

        $role = Auth::role();

        // Employees can only cancel their own pending reservations
        if ($role === ROLE_EMPLOYEE) {
            if ($reservation['status'] !== 'pending' ||
                (int) $reservation['requested_by'] !== (int) Auth::id()) {
                Helpers::setFlash('error', 'You cannot cancel this reservation.');
                Helpers::redirect('/reservations/' . $id);
            }
        } else {
            // Admins/super_admins can cancel pending or approved
            if (!in_array($reservation['status'], ['pending', 'approved'])) {
                Helpers::setFlash('error', 'Only pending or approved reservations can be cancelled.');
                Helpers::redirect('/reservations/' . $id);
            }
        }

        $resModel->cancel($id, (int) Auth::id(), $reason);

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'RESERVATION_CANCELLED',
            'reservations',
            $id,
            ['status' => $reservation['status']],
            ['status' => 'cancelled', 'cancellation_reason' => $reason]
        );

        Helpers::setFlash('success', 'Reservation cancelled.');
        Helpers::redirect('/reservations/' . $id);
    }

    // ── Step 9 — Employee self-edit ───────────────────────────────

    // GET /reservations/{id}/edit
    public function editReservation(int $id): void
    {
        Auth::requireRole(ROLE_EMPLOYEE, ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $resModel    = new ReservationModel();
        $reservation = $resModel->findById($id);

        if (!$reservation) {
            Helpers::setFlash('error', 'Reservation not found.');
            Helpers::redirect('/reservations');
        }

        Auth::requireCompanyScope((int) $reservation['company_id'], '/reservations/' . $id);

        // Only the requester can edit their own pending reservation
        if (Auth::role() === ROLE_EMPLOYEE &&
            ($reservation['status'] !== 'pending' ||
             (int) $reservation['requested_by'] !== (int) Auth::id())) {
            Helpers::setFlash('error', 'This reservation cannot be edited.');
            Helpers::redirect('/reservations/' . $id);
        }

        $purposeModel = new TripPurposeModel();
        $projectModel = new ProjectModel();
        $companyId    = (int) Auth::companyId();

        $this->render('edit', [
            'page_title'  => 'Edit ' . $reservation['reservation_code'],
            'reservation' => $reservation,
            'purposes'    => $purposeModel->findActive(),
            'projects'    => $projectModel->findActiveByCompany($companyId),
        ]);
    }

    // POST /reservations/{id}/edit
    public function updateReservation(int $id): void
    {
        Auth::requireRole(ROLE_EMPLOYEE, ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $purposeId = (int)   ($_POST['purpose_id']         ?? 0);
        $projectId = $_POST['project_id'] !== '' ? (int) $_POST['project_id'] : null;
        $dest      = trim(    $_POST['destination']         ?? '');
        $destLat   = $_POST['destination_lat'] !== '' ? (float) $_POST['destination_lat'] : null;
        $destLng   = $_POST['destination_lng'] !== '' ? (float) $_POST['destination_lng'] : null;
        $details   = trim(    $_POST['trip_details']         ?? '') ?: null;
        $pax       = (int)   ($_POST['passenger_count']     ?? 0);
        $cargo     = (float) ($_POST['cargo_weight_kg']     ?? 0);
        $cargoDesc = trim(    $_POST['cargo_description']   ?? '') ?: null;
        $departure = trim(    $_POST['departure_datetime']  ?? '');
        $return    = trim(    $_POST['return_datetime']     ?? '');

        if ($purposeId === 0 || $dest === '' || $pax < 1 ||
            $departure === '' || $return === '') {
            Helpers::setFlash('error', 'Please fill in all required fields.');
            Helpers::redirect('/reservations/' . $id . '/edit');
        }

        if ($departure >= $return) {
            Helpers::setFlash('error', 'Return time must be after departure time.');
            Helpers::redirect('/reservations/' . $id . '/edit');
        }

        // Fetch old values for audit log before update
        $resModel = new ReservationModel();
        $old      = $resModel->findById($id);

        if (!$old) {
            Helpers::setFlash('error', 'Reservation not found.');
            Helpers::redirect('/reservations');
        }

        Auth::requireCompanyScope((int) $old['company_id'], '/reservations/' . $id);

        $affected = $resModel->updateByEmployee($id, (int) Auth::id(), [
            'purpose_id'         => $purposeId,
            'project_id'         => $projectId,
            'destination'        => $dest,
            'destination_lat'    => $destLat,
            'destination_lng'    => $destLng,
            'trip_details'       => $details,
            'passenger_count'    => $pax,
            'cargo_weight_kg'    => $cargo,
            'cargo_description'  => $cargoDesc,
            'departure_datetime' => $departure,
            'return_datetime'    => $return,
        ]);

        if ($affected === 0) {
            Helpers::setFlash('error',
                'This reservation could not be edited — '
                . 'it may have been reviewed or cancelled since you opened the form.');
            Helpers::redirect('/reservations/' . $id);
        }

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'RESERVATION_UPDATED',
            'reservations',
            $id,
            ['destination'       => $old['destination'],
             'departure_datetime'=> $old['departure_datetime'],
             'passenger_count'   => $old['passenger_count']],
            ['destination'       => $dest,
             'departure_datetime'=> $departure,
             'passenger_count'   => $pax]
        );

        Helpers::setFlash('success', 'Reservation updated.');
        Helpers::redirect('/reservations/' . $id);
    }

    // ── Step 10 — Review / Approve / Reject ──────────────────────

    // GET /reservations/{id}/review
    public function review(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);

        $resModel    = new ReservationModel();
        $reservation = $resModel->findById($id);

        if (!$reservation || $reservation['status'] !== 'pending') {
            Helpers::setFlash('error', 'This reservation cannot be reviewed.');
            Helpers::redirect('/reservations');
        }

        Auth::requireCompanyScope((int) $reservation['company_id'], '/reservations');

        // Run AI recommendation only once — guard by checking if already done
        if ($reservation['ai_recommended_vehicle_id'] === null) {
            $topId = VehicleRecommendationService::recommend($reservation);

            // Build summary note from log results
            $logModel = new AiRecommendationLogModel();
            $logs     = $logModel->findByReservation($id);
            $topScore = 0.0;
            $topName  = 'None';
            foreach ($logs as $log) {
                if (!$log['disqualified']
                    && (int) $log['vehicle_id'] === (int) $topId) {
                    $topScore = $log['score'];
                    $topName  = $log['plate_number']
                        . ' — ' . $log['brand'] . ' ' . $log['model'];
                }
            }
            $notes = $topId
                ? "Recommended: $topName (score: $topScore)"
                : 'No eligible vehicles found after disqualification filters.';

            $resModel->updateAiRecommendation($id, $topId, $topScore, $notes);
            $reservation = $resModel->findById($id); // refresh with AI fields
        }

        $logModel   = new AiRecommendationLogModel();
        $aiLogs     = $logModel->findByReservation($id);

        $vehicleModel = new VehicleModel();
        $driverModel  = new DriverProfileModel();

        $this->render('review', [
            'page_title'  => 'Review ' . $reservation['reservation_code'],
            'reservation' => $reservation,
            'aiLogs'      => $aiLogs,
            'vehicles'    => $vehicleModel->findForRecommendation(),
            'drivers'     => $driverModel->findAvailable(),
        ]);
    }

    // POST /reservations/{id}/approve
    public function approve(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);

        $vehicleId = (int) ($_POST['assigned_vehicle_id'] ?? 0);
        $driverId  = (int) ($_POST['assigned_driver_id']  ?? 0);

        if ($vehicleId === 0 || $driverId === 0) {
            Helpers::setFlash('error', 'Please select both a vehicle and a driver.');
            Helpers::redirect('/reservations/' . $id . '/review');
        }

        $resModel    = new ReservationModel();
        $reservation = $resModel->findById($id);

        if (!$reservation || $reservation['status'] !== 'pending') {
            Helpers::setFlash('error', 'This reservation cannot be approved.');
            Helpers::redirect('/reservations');
        }

        Auth::requireCompanyScope((int) $reservation['company_id'], '/reservations');

        // Approve the reservation
        $resModel->approve($id, [
            'vehicle_id'  => $vehicleId,
            'driver_id'   => $driverId,
            'reviewed_by' => (int) Auth::id(),
        ]);

        // Set vehicle to 'reserved' — driver stays 'available' until trip starts (Step 11)
        $vehicleModel = new VehicleModel();
        $vehicleModel->updateStatus($vehicleId, 'reserved');

        // Create the trip row — pending_start, ready for the driver to start via the app
        $tripModel = new TripModel();
        $tripModel->create([
            'reservation_id' => $id,
            'vehicle_id'     => $vehicleId,
            'driver_id'      => $driverId,
        ]);

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'RESERVATION_APPROVED',
            'reservations',
            $id,
            ['status' => 'pending'],
            ['status' => 'approved',
             'assigned_vehicle_id' => $vehicleId,
             'assigned_driver_id'  => $driverId]
        );

        $notifModel = new NotificationModel();
        $notifModel->createForUsers([(int) $reservation['requested_by']], [
            'title'          => 'Reservation Approved',
            'message'        => $reservation['reservation_code'] . ' — '
                . $reservation['destination'] . ' has been approved.',
            'type'           => 'reservation',
            'reference_id'   => $id,
            'reference_type' => 'reservation',
        ]);

        Helpers::setFlash('success',
            'Reservation ' . $reservation['reservation_code'] . ' approved.');
        Helpers::redirect('/reservations/' . $id);
    }

    // POST /reservations/{id}/reject
    public function reject(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);

        $reason = trim($_POST['rejection_reason'] ?? '');
        if ($reason === '') {
            Helpers::setFlash('error', 'Rejection reason is required.');
            Helpers::redirect('/reservations/' . $id . '/review');
        }

        $resModel    = new ReservationModel();
        $reservation = $resModel->findById($id);

        if (!$reservation || $reservation['status'] !== 'pending') {
            Helpers::setFlash('error', 'This reservation cannot be rejected.');
            Helpers::redirect('/reservations');
        }

        Auth::requireCompanyScope((int) $reservation['company_id'], '/reservations');

        $resModel->reject($id, [
            'reason'      => $reason,
            'reviewed_by' => (int) Auth::id(),
        ]);

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'RESERVATION_REJECTED',
            'reservations',
            $id,
            ['status' => 'pending'],
            ['status' => 'rejected', 'rejection_reason' => $reason]
        );

        $notifModel = new NotificationModel();
        $notifModel->createForUsers([(int) $reservation['requested_by']], [
            'title'          => 'Reservation Rejected',
            'message'        => $reservation['reservation_code'] . ' was rejected: ' . $reason,
            'type'           => 'reservation',
            'reference_id'   => $id,
            'reference_type' => 'reservation',
        ]);

        Helpers::setFlash('success', 'Reservation rejected.');
        Helpers::redirect('/reservations/' . $id);
    }

    // ── 6f — Trip Purposes ────────────────────────────────────────

    public function purposes(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);
        $purposeModel = new TripPurposeModel();
        $catModel     = new VehicleCategoryModel();
        $this->render('purposes', [
            'page_title' => 'Trip Purposes',
            'purposes'   => $purposeModel->findAll(),
            'categories' => $catModel->findAll(),
        ]);
    }

    public function storePurpose(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);
        $name          = trim($_POST['purpose_name']    ?? '');
        $desc          = trim($_POST['description']     ?? '') ?: null;
        $reqProject    = isset($_POST['requires_project']) ? 1 : 0;
        $maxPerProject = $_POST['max_per_project'] !== '' ? (int) $_POST['max_per_project'] : null;
        $rawIds        = $_POST['preferred_category_ids'] ?? [];
        $csvIds        = !empty($rawIds) ? implode(',', array_map('intval', (array) $rawIds)) : null;
        if ($name === '') { Helpers::setFlash('error', 'Purpose name is required.'); Helpers::redirect('/reservations/purposes'); }
        try {
            $purposeModel = new TripPurposeModel();
            $newId = $purposeModel->create(['purpose_name' => $name, 'description' => $desc, 'requires_project' => $reqProject, 'max_per_project' => $maxPerProject, 'preferred_category_ids' => $csvIds, 'is_active' => 1]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') { Helpers::setFlash('error', 'A purpose with that name already exists.'); Helpers::redirect('/reservations/purposes'); }
            throw $e;
        }
        (new AuditLogModel())->log((int) Auth::id(), 'TRIP_PURPOSE_CREATED', 'trip_purposes', $newId, null, ['purpose_name' => $name]);
        Helpers::setFlash('success', 'Purpose "' . $name . '" added.');
        Helpers::redirect('/reservations/purposes');
    }

    public function updatePurpose(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);
        $name          = trim($_POST['purpose_name']    ?? '');
        $desc          = trim($_POST['description']     ?? '') ?: null;
        $reqProject    = isset($_POST['requires_project']) ? 1 : 0;
        $maxPerProject = $_POST['max_per_project'] !== '' ? (int) $_POST['max_per_project'] : null;
        $rawIds        = $_POST['preferred_category_ids'] ?? [];
        $csvIds        = !empty($rawIds) ? implode(',', array_map('intval', (array) $rawIds)) : null;
        $isActive      = isset($_POST['is_active']) ? 1 : 0;
        if ($name === '') { Helpers::setFlash('error', 'Purpose name is required.'); Helpers::redirect('/reservations/purposes'); }
        $purposeModel = new TripPurposeModel();
        $old          = $purposeModel->findById($id);
        if (!$old) { Helpers::setFlash('error', 'Purpose not found.'); Helpers::redirect('/reservations/purposes'); }
        try {
            $purposeModel->update($id, ['purpose_name' => $name, 'description' => $desc, 'requires_project' => $reqProject, 'max_per_project' => $maxPerProject, 'preferred_category_ids' => $csvIds, 'is_active' => $isActive]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') { Helpers::setFlash('error', 'A purpose with that name already exists.'); Helpers::redirect('/reservations/purposes'); }
            throw $e;
        }
        (new AuditLogModel())->log((int) Auth::id(), 'TRIP_PURPOSE_UPDATED', 'trip_purposes', $id, ['purpose_name' => $old['purpose_name']], ['purpose_name' => $name, 'is_active' => $isActive]);
        Helpers::setFlash('success', 'Purpose "' . $name . '" updated.');
        Helpers::redirect('/reservations/purposes');
    }

    // ── Notification helper ───────────────────────────────────────

    private function notifyAdminsOfNewReservation(
        int    $reservationId,
        int    $deptId,
        string $code,
        string $destination
    ): void {
        try {
            $userModel  = new UserModel();
            $recipients = array_merge(
                array_column($userModel->findByRole(ROLE_SUPER_ADMIN), 'user_id'),
                array_column($userModel->findByRole(ROLE_FLEET_ADMIN), 'user_id')
            );

            // Admins of the reservation's own company
            $deptModel = new DepartmentModel();
            $dept      = $deptModel->findById($deptId);
            if ($dept) {
                $companyAdmins = $userModel->findAll(ROLE_ADMIN, (int) $dept['company_id']);
                $recipients    = array_merge($recipients, array_column($companyAdmins, 'user_id'));
            }

            $recipients = array_unique($recipients);

            if (empty($recipients)) {
                return;
            }

            $notifModel = new NotificationModel();
            $notifModel->createForUsers($recipients, [
                'title'          => 'New Reservation Pending',
                'message'        => $code . ' — ' . $destination,
                'type'           => 'reservation',
                'reference_id'   => $reservationId,
                'reference_type' => 'reservation',
            ]);
        } catch (Throwable $e) {
            // Notification failure must never block reservation creation
            error_log('[LVMS] Notification failed: ' . $e->getMessage());
        }
    }
}
