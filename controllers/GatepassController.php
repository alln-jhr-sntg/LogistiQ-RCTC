<?php

/**
 * GatepassController
 *
 * Sits between reservation approval and trip creation. When a reservation
 * is approved (ReservationController::approve()), a gatepass row is
 * created here-adjacent instead of a trip: reservation.status becomes
 * 'gatepass_pending' and the reservation waits in this controller's queue
 * until a super_admin reviews it.
 *
 * Only once GatepassController::approve() runs does a trip row get
 * created — this is the ONLY call site for TripModel::create() in the
 * app. No trip may exist before its gatepass is approved.
 *
 * All review/approve/reject actions are super_admin ONLY (config/constants.php
 * documents this restriction — narrower than reservation approval, which
 * also allows fleet_admin). printView() is intentionally wider: super_admin,
 * fleet_admin, the requester, and the assigned driver may open the printable
 * document, but only once the gatepass has been approved.
 */
class GatepassController
{
    private function render(string $view, array $data = []): void
    {
        extract($data);
        $content_view = __DIR__ . '/../views/gatepasses/' . $view . '.php';
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    // Renders a gatepass view WITHOUT the sidebar/topbar layout.
    // Used only by printView() — the app's one standalone document page,
    // in the same spirit as views/layouts/auth.php.
    private function renderBare(string $view, array $data = []): void
    {
        extract($data);
        require_once __DIR__ . '/../views/gatepasses/' . $view . '.php';
    }

    // GET /gatepasses
    public function index(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN);

        $gpModel = new GatepassModel();

        $this->render('index', [
            'page_title' => 'Gatepasses',
            'gatepasses' => $gpModel->findPending(),
        ]);
    }

    // GET /gatepasses/{id}/review
    public function review(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN);

        $gpModel  = new GatepassModel();
        $gatepass = $gpModel->findById($id);

        if (!$gatepass || $gatepass['status'] !== 'pending') {
            Helpers::setFlash('error', 'This gatepass cannot be reviewed.');
            Helpers::redirect('/gatepasses');
        }

        Auth::requireCompanyScope((int) $gatepass['company_id'], '/gatepasses');

        $this->render('review', [
            'page_title' => 'Review ' . $gatepass['gatepass_code'],
            'gatepass'   => $gatepass,
        ]);
    }

    // POST /gatepasses/{id}/approve
    public function approve(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN);

        $gpModel  = new GatepassModel();
        $gatepass = $gpModel->findById($id);

        if (!$gatepass || $gatepass['status'] !== 'pending') {
            Helpers::setFlash('error', 'This gatepass cannot be approved.');
            Helpers::redirect('/gatepasses');
        }

        Auth::requireCompanyScope((int) $gatepass['company_id'], '/gatepasses');

        $reservationId = (int) $gatepass['reservation_id'];
        $vehicleId     = (int) $gatepass['assigned_vehicle_id'];
        $driverId      = (int) $gatepass['assigned_driver_id'];

        // Approve the gatepass
        $gpModel->approve($id, (int) Auth::id());

        // Advance the reservation from gatepass_pending -> approved
        $resModel = new ReservationModel();
        $resModel->updateStatus($reservationId, 'approved');

        // Create the trip row now — pending_start, ready for the driver to
        // start via the app. This is the ONLY place TripModel::create() is
        // called; no trip exists until this point.
        $tripModel = new TripModel();
        $tripId    = $tripModel->create([
            'reservation_id' => $reservationId,
            'vehicle_id'     => $vehicleId,
            'driver_id'      => $driverId,
        ]);

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'GATEPASS_APPROVED',
            'gatepasses',
            $id,
            ['status' => 'pending'],
            ['status' => 'approved', 'reservation_id' => $reservationId]
        );

        // Notify the requester that the gatepass cleared, and the driver
        // that a new trip is waiting. These are two different notifications
        // — drivers must not see gatepass status (drivers_app.txt), only
        // that a trip was assigned to them.
        try {
            $notifModel = new NotificationModel();
            $notifModel->createForUsers(
                [(int) $gatepass['requested_by']],
                [
                    'title'          => 'Gatepass Approved',
                    'message'        => $gatepass['gatepass_code'] . ' — '
                        . $gatepass['reservation_code'] . ' is cleared to depart.',
                    'type'           => 'reservation',
                    'reference_id'   => $reservationId,
                    'reference_type' => 'reservation',
                ]
            );
            $notifModel->createForUsers(
                [$driverId],
                [
                    'title'          => 'New Trip Assigned',
                    'message'        => 'You have been assigned a new trip: '
                        . $gatepass['reservation_code'] . ', departing '
                        . date('M j, Y g:i A', strtotime($gatepass['departure_datetime'])) . '.',
                    'type'           => 'trip',
                    'reference_id'   => $tripId,
                    'reference_type' => 'trip',
                ]
            );
        } catch (Throwable $e) {
            error_log('[LVMS] Notification failed: ' . $e->getMessage());
        }

        Helpers::setFlash('success',
            'Gatepass ' . $gatepass['gatepass_code'] . ' approved. Trip is ready to start.');
        Helpers::redirect('/gatepasses');
    }

    // POST /gatepasses/{id}/reject
    public function reject(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN);

        $reason = trim($_POST['rejection_reason'] ?? '');
        if ($reason === '') {
            Helpers::setFlash('error', 'Rejection reason is required.');
            Helpers::redirect('/gatepasses/' . $id . '/review');
        }

        $gpModel  = new GatepassModel();
        $gatepass = $gpModel->findById($id);

        if (!$gatepass || $gatepass['status'] !== 'pending') {
            Helpers::setFlash('error', 'This gatepass cannot be rejected.');
            Helpers::redirect('/gatepasses');
        }

        Auth::requireCompanyScope((int) $gatepass['company_id'], '/gatepasses');

        $gpModel->reject($id, (int) Auth::id(), $reason);

        $resModel = new ReservationModel();
        $resModel->reject((int) $gatepass['reservation_id'], [
            'reason'      => $reason,
            'reviewed_by' => (int) Auth::id(),
        ]);

        // Release the vehicle back to the pool — driver was never moved off
        // 'available' at reservation approval time, so nothing to reset there.
        if ($gatepass['assigned_vehicle_id']) {
            $vehicleModel = new VehicleModel();
            $vehicleModel->updateStatus((int) $gatepass['assigned_vehicle_id'], 'available');
        }

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'GATEPASS_REJECTED',
            'gatepasses',
            $id,
            ['status' => 'pending'],
            ['status' => 'rejected', 'rejection_reason' => $reason]
        );

        try {
            $notifModel = new NotificationModel();
            $notifModel->createForUsers([(int) $gatepass['requested_by']], [
                'title'          => 'Gatepass Rejected',
                'message'        => $gatepass['gatepass_code'] . ' was rejected: ' . $reason,
                'type'           => 'reservation',
                'reference_id'   => (int) $gatepass['reservation_id'],
                'reference_type' => 'reservation',
            ]);
        } catch (Throwable $e) {
            error_log('[LVMS] Notification failed: ' . $e->getMessage());
        }

        Helpers::setFlash('success', 'Gatepass rejected.');
        Helpers::redirect('/gatepasses');
    }

    // GET /gatepasses/{id}/print
    public function printView(int $id): void
    {
        // Wider than the other gatepass actions on purpose — the printed
        // document is what security personnel check at the gate, so the
        // requester needs to be able to open it too, not just admin-and-above.
        // Drivers no longer have web access to this page — the assigned
        // driver checks the pass on the printed copy the requester carries.
        // Auth::requireRole() calls Auth::requireLogin() first and exits for
        // anyone without an active session, so Auth::id() below is always a
        // real authenticated user id — never null — by the time it's used.
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE);

        $gpModel  = new GatepassModel();
        $gatepass = $gpModel->findById($id);

        if (!$gatepass) {
            Helpers::setFlash('error', 'Gatepass not found.');
            Helpers::redirect(Auth::dashboardUrl());
        }

        // Compare as nullable ints, never as (int) casts of both sides — casting
        // null to 0 on both sides would let a null-id match a requester-less row.
        $uid           = Auth::id();
        $requestedById = $gatepass['requested_by'] !== null ? (int) $gatepass['requested_by'] : null;

        $allowed = Auth::hasGlobalScope()
            || ($requestedById !== null && $uid === $requestedById);

        if (!$allowed || $gatepass['status'] !== 'approved') {
            Helpers::setFlash('error', 'This gatepass is not available to print.');
            Helpers::redirect(Auth::dashboardUrl());
        }

        $this->renderBare('print', [
            'page_title' => $gatepass['gatepass_code'],
            'gatepass'   => $gatepass,
        ]);
    }
}
