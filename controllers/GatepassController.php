<?php

/**
 * GatepassController
 *
 * Sits between reservation approval and trip creation. When a reservation
 * is approved (ReservationController::approve()), a gatepass row is
 * created here-adjacent instead of a trip: reservation.status becomes
 * 'gatepass_pending' and the reservation waits for a super_admin to
 * review it via the modal on that reservation's detail page
 * (views/reservations/detail.php) — there is no standalone gatepass
 * queue or review page.
 *
 * Only once GatepassController::approve() runs does a trip row get
 * created — this is the ONLY call site for TripModel::create() in the
 * app. No trip may exist before its gatepass is approved.
 *
 * All approve/reject actions are super_admin ONLY (config/constants.php
 * documents this restriction — narrower than reservation approval, which
 * also allows fleet_admin). printView() is intentionally wider: super_admin,
 * fleet_admin, admin (own company only), and the requester may open the
 * printable document, but only while the gatepass is approved AND the trip
 * is still pending_start — see ReservationController::detail() for the
 * matching $canPrintGatepass flag shown in the view.
 */
class GatepassController
{
    // Renders a gatepass view WITHOUT the sidebar/topbar layout.
    // Used only by printView() — the app's one standalone document page,
    // in the same spirit as views/layouts/auth.php.
    private function renderBare(string $view, array $data = []): void
    {
        extract($data);
        require_once __DIR__ . '/../views/gatepasses/' . $view . '.php';
    }

    // POST /gatepasses/{id}/approve
    public function approve(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN);

        $gpModel  = new GatepassModel();
        $gatepass = $gpModel->findById($id);

        if (!$gatepass || $gatepass['status'] !== 'pending') {
            Helpers::setFlash('error', 'This gatepass cannot be approved.');
            Helpers::redirect($gatepass ? '/reservations/' . $gatepass['reservation_id'] : '/reservations');
        }

        $reservationId = (int) $gatepass['reservation_id'];

        Auth::requireCompanyScope((int) $gatepass['company_id'], '/reservations/' . $reservationId);

        $vehicleId     = (int) $gatepass['assigned_vehicle_id'];
        $driverId      = (int) $gatepass['assigned_driver_id'];

        // Gatepass approval, reservation advance, and trip creation must
        // land together — a failure partway through (e.g. TripModel::create())
        // would otherwise leave the gatepass approved and the reservation
        // 'approved' with no trip ever created for it.
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
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

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }

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
        Helpers::redirect('/reservations/' . $reservationId);
    }

    // POST /gatepasses/{id}/reject
    public function reject(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN);

        $gpModel  = new GatepassModel();
        $gatepass = $gpModel->findById($id);

        if (!$gatepass || $gatepass['status'] !== 'pending') {
            Helpers::setFlash('error', 'This gatepass cannot be rejected.');
            Helpers::redirect($gatepass ? '/reservations/' . $gatepass['reservation_id'] : '/reservations');
        }

        $reservationId = (int) $gatepass['reservation_id'];

        $reason = trim($_POST['rejection_reason'] ?? '');
        if ($reason === '') {
            Helpers::setFlash('error', 'Rejection reason is required.');
            Helpers::redirect('/reservations/' . $reservationId);
        }

        Auth::requireCompanyScope((int) $gatepass['company_id'], '/reservations/' . $reservationId);

        // Gatepass rejection, reservation rejection, and the vehicle release
        // must land together — a failure partway through would otherwise
        // leave the vehicle stuck at 'reserved' with no active reservation
        // holding it.
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
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

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        try {
            $notifModel = new NotificationModel();
            $notifModel->createForUsers([(int) $gatepass['requested_by']], [
                'title'          => 'Gatepass Rejected',
                'message'        => $gatepass['gatepass_code'] . ' was rejected: ' . $reason,
                'type'           => 'reservation',
                'reference_id'   => $reservationId,
                'reference_type' => 'reservation',
            ]);
        } catch (Throwable $e) {
            error_log('[LVMS] Notification failed: ' . $e->getMessage());
        }

        Helpers::setFlash('success', 'Gatepass rejected.');
        Helpers::redirect('/reservations/' . $reservationId);
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

        // super_admin: unrestricted. fleet_admin and admin: own company only —
        // fleet_admin runs the shared fleet across all companies but is also
        // the REMIX admin, so for this specific action it's scoped exactly
        // like admin. Deliberately NOT using Auth::canActOnCompany() here:
        // that helper treats fleet_admin as global via hasGlobalScope(),
        // which is what we're overriding. Anyone else: only if they
        // requested the booking.
        $allowed = Auth::isSuperAdmin()
            || (in_array(Auth::role(), [ROLE_FLEET_ADMIN, ROLE_ADMIN], true)
                && (int) Auth::companyId() === (int) $gatepass['company_id'])
            || ($requestedById !== null && $uid === $requestedById);

        if (!$allowed || $gatepass['status'] !== 'approved') {
            Helpers::setFlash('error', 'This gatepass is not available to print.');
            Helpers::redirect(Auth::dashboardUrl());
        }

        // Printable only from approval through pending_start — once the trip
        // starts (or is cancelled/completed/incident before ever starting),
        // the gate pass stays visible on the reservation but is no longer
        // printable from here.
        $tripModel = new TripModel();
        $trip      = $tripModel->findByReservation((int) $gatepass['reservation_id']);

        if (!$trip || $trip['trip_status'] !== TRIP_PENDING_START) {
            Helpers::setFlash('error', 'This gatepass is no longer available to print.');
            Helpers::redirect(Auth::dashboardUrl());
        }

        $this->renderBare('print', [
            'page_title' => $gatepass['gatepass_code'],
            'gatepass'   => $gatepass,
        ]);
    }
}
