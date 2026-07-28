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
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE);

        $role   = Auth::role();
        $status = $_GET['status'] ?? '';

        $resModel = new ReservationModel();

        if ($role === ROLE_EMPLOYEE) {
            $reservations = $resModel->findForEmployee((int) Auth::id(), $status);
        } elseif ($role === ROLE_ADMIN) {
            $accessModel = new AdminDepartmentAccessModel();
            $deptIds     = array_column(
                $accessModel->getByAdmin((int) Auth::id()), 'department_id'
            );
            $reservations = $resModel->findForAdmin($deptIds, $status);
        } else {
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
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE);

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
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE);

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
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE);

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
        if (in_array($role, [ROLE_SUPER_ADMIN, ROLE_ADMIN])
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
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE);

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
        Auth::requireRole(ROLE_EMPLOYEE, ROLE_SUPER_ADMIN, ROLE_ADMIN);

        $resModel    = new ReservationModel();
        $reservation = $resModel->findById($id);

        if (!$reservation) {
            Helpers::setFlash('error', 'Reservation not found.');
            Helpers::redirect('/reservations');
        }

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
        Auth::requireRole(ROLE_EMPLOYEE, ROLE_SUPER_ADMIN, ROLE_ADMIN);

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

    // ── Step 10 — Review / Approve / Reject (stubs until Step 10) ─

    public function review(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN);
        $this->render('review', ['page_title' => 'Review Reservation']);
    }

    public function approve(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN);
        Helpers::setFlash('success', 'Reservation approved. (Step 10 pending)');
        Helpers::redirect('/reservations/' . $id);
    }

    public function reject(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN);
        Helpers::setFlash('success', 'Reservation rejected. (Step 10 pending)');
        Helpers::redirect('/reservations/' . $id);
    }

    // ── 6f — Trip Purposes ────────────────────────────────────────

    public function purposes(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN);
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
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN);
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
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN);
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
            // Admins with access to this department
            $accessModel = new AdminDepartmentAccessModel();
            $adminIds    = $accessModel->getAdminsByDepartment($deptId);

            // Super admins always receive notifications
            $userModel    = new UserModel();
            $superAdmins  = $userModel->findByRole(ROLE_SUPER_ADMIN);
            $superIds     = array_column($superAdmins, 'user_id');

            $recipients = array_unique(array_merge($adminIds, $superIds));

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
