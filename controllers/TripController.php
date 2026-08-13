<?php

/**
 * TripController
 *
 * Manages the trip lifecycle on the web side. The driver-facing
 * start/complete flow runs through TripApiController (Step 15).
 * These web methods are admin override paths.
 *
 * Role matrix:
 *   index()          — super_admin, fleet_admin, admin, employee. Drivers
 *                      have no web access to trips — the Android app is
 *                      their only trip surface.
 *   detail()         — super_admin, fleet_admin, admin, employee
 *   start()          — super_admin, fleet_admin (web override)
 *   complete()       — super_admin, fleet_admin (web override)
 *   notes()          — super_admin, fleet_admin, admin (admin_notes),
 *                      employee (employee_notes via reservation detail)
 *   reportIncident() — super_admin, fleet_admin, admin (Decision 5)
 *   resolveIncident()— super_admin, fleet_admin, admin
 *   cancelTrip()     — super_admin, fleet_admin — terminal exit for a
 *                      trip that cannot continue (only from 'incident')
 *
 * Admin may act only on trips belonging to their own company; super_admin
 * and fleet_admin act across every company (shared fleet).
 */
class TripController
{
    private function render(string $view, array $data = []): void
    {
        extract($data);
        $content_view = __DIR__ . '/../views/trips/' . $view . '.php';
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    // ── Trip list ─────────────────────────────────────────────────

    // GET /trips
    public function index(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE);

        $role         = Auth::role();
        $statusFilter = $_GET['status'] ?? '';
        $tripModel    = new TripModel();

        $page      = max(1, (int) ($_GET['page'] ?? 1));
        $baseQuery = http_build_query(array_filter(['url' => 'trips', 'status' => $statusFilter]));

        if ($role === ROLE_EMPLOYEE) {
            $userId       = (int) Auth::id();
            $total        = $tripModel->countForEmployee($userId, $statusFilter);
            $pagination   = Helpers::paginate($total, $page, 10, $baseQuery);
            $trips        = $tripModel->findForEmployee($userId, $statusFilter, $pagination['limit'], $pagination['offset']);
        } else {
            // Admin, fleet_admin and super_admin see every company's trips
            // here — transparency is intentional. Acting on a trip outside
            // your own company is blocked separately, at the point of action.
            $total        = $tripModel->countAll($statusFilter);
            $pagination   = Helpers::paginate($total, $page, 10, $baseQuery);
            $trips        = $tripModel->findAll($statusFilter, $pagination['limit'], $pagination['offset']);
        }

        $this->render('index', [
            'page_title'    => $role === ROLE_EMPLOYEE ? 'My Trips' : 'Trips',
            'trips'         => $trips,
            'statusFilter'  => $statusFilter,
            'role'          => $role,
            'pagination'    => $pagination['html'],
        ]);
    }

    // ── Trip detail ───────────────────────────────────────────────

    // GET /trips/{id}
    public function detail(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE);

        $tripModel = new TripModel();
        $trip      = $tripModel->findById($id);

        if (!$trip) {
            Helpers::setFlash('error', 'Trip not found.');
            Helpers::redirect(Auth::isEmployee() ? '/reservations' : '/trips');
        }

        $role = Auth::role();

        // Employees can only see trips for reservations they requested
        if ($role === ROLE_EMPLOYEE && (int) $trip['requested_by'] !== (int) Auth::id()) {
            Helpers::setFlash('error', 'Access denied.');
            Helpers::redirect('/reservations');
        }

        $incidentModel = new TripIncidentModel();
        $incidents     = $incidentModel->findByTrip($id);

        $this->render('detail', [
            'page_title' => 'Trip — ' . $trip['reservation_code'],
            'trip'       => $trip,
            'incidents'  => $incidents,
            'role'       => $role,
        ]);
    }

    // ── Start trip (admin web override) ──────────────────────────

    // POST /trips/{reservation_id}/start
    // $id here is the reservation_id — the trip row is pre-created
    // with 'pending_start' at approval time.
    public function start(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);

        $odometerStart = (float) ($_POST['odometer_start_km'] ?? 0);

        $tripModel = new TripModel();
        $trip      = $tripModel->findByReservation($id);

        if (!$trip) {
            Helpers::setFlash('error', 'No trip found for this reservation. Has it been approved?');
            Helpers::redirect('/reservations/' . $id);
        }

        Auth::requireCompanyScope((int) $trip['company_id'], '/reservations/' . $id);

        // Guard against double-start: only pending_start can be transitioned
        if ($trip['trip_status'] !== 'pending_start') {
            Helpers::setFlash('error', 'This trip has already been started or completed.');
            Helpers::redirect('/trips/' . $trip['trip_id']);
        }

        if ($odometerStart < 0) {
            Helpers::setFlash('error', 'Odometer reading cannot be negative.');
            Helpers::redirect('/trips/' . $trip['trip_id']);
        }

        $tripModel->updateStarted($trip['trip_id'], $odometerStart);

        // Update reservation status
        $resModel = new ReservationModel();
        $resModel->updateStatus($id, 'in_progress');

        // Transition vehicle and driver to on_trip
        $vehicleModel = new VehicleModel();
        $vehicleModel->updateStatus((int) $trip['vehicle_id'], 'on_trip');

        $driverModel = new DriverProfileModel();
        $driverModel->updateStatus((int) $trip['driver_id'], 'on_trip');

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'TRIP_STARTED',
            'trips',
            $trip['trip_id'],
            ['trip_status' => 'pending_start'],
            ['trip_status'       => 'in_progress',
             'odometer_start_km' => $odometerStart]
        );

        Helpers::setFlash('success', 'Trip started.');
        Helpers::redirect('/trips/' . $trip['trip_id']);
    }

    // ── Complete trip (admin web override) ────────────────────────

    // POST /trips/{id}/complete
    public function complete(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);

        $odometerEnd = (float) ($_POST['odometer_end_km'] ?? 0);

        $tripModel = new TripModel();
        $trip      = $tripModel->findById($id);

        if (!$trip) {
            Helpers::setFlash('error', 'Trip not found.');
            Helpers::redirect('/trips');
        }

        Auth::requireCompanyScope((int) $trip['company_id'], '/trips/' . $id);

        if (in_array($trip['trip_status'], TRIP_TERMINAL_STATUSES, true)) {
            Helpers::setFlash('error', 'This trip is already ' . $trip['trip_status'] . '.');
            Helpers::redirect('/trips/' . $id);
        }

        if ($trip['trip_status'] === 'pending_start') {
            Helpers::setFlash('error', 'Trip has not been started yet.');
            Helpers::redirect('/trips/' . $id);
        }

        if ((new TripIncidentModel())->hasUnresolvedByTrip($id)) {
            Helpers::setFlash('error', 'Resolve or cancel the open incident before completing this trip.');
            Helpers::redirect('/trips/' . $id);
        }

        // Odometer end must be >= start (allow equal for same-location edge case)
        $odometerStart = (float) $trip['odometer_start_km'];
        if ($odometerEnd < $odometerStart) {
            Helpers::setFlash(
                'error',
                'End odometer (' . number_format($odometerEnd, 2)
                . ' km) cannot be less than start ('
                . number_format($odometerStart, 2) . ' km).'
            );
            Helpers::redirect('/trips/' . $id);
        }

        $tripModel->updateCompleted($id, $odometerEnd);

        // Update reservation to completed
        $resModel = new ReservationModel();
        $resModel->updateStatus((int) $trip['reservation_id'], 'completed');

        // Return vehicle to available and update odometer
        $vehicleModel = new VehicleModel();
        $vehicleModel->updateStatus((int) $trip['vehicle_id'], 'available');
        $vehicleModel->updateOdometer((int) $trip['vehicle_id'], $odometerEnd);

        // Return driver to available
        $driverModel = new DriverProfileModel();
        $driverModel->updateStatus((int) $trip['driver_id'], 'available');

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'TRIP_COMPLETED',
            'trips',
            $id,
            ['trip_status' => $trip['trip_status']],
            ['trip_status'    => 'completed',
             'odometer_end_km'=> $odometerEnd]
        );

        // MaintenanceService hook (Step 12 — safe stub, swallows error if not yet implemented)
        try {
            MaintenanceService::checkAfterTrip((int) $trip['vehicle_id'], $odometerEnd);
        } catch (Throwable $e) {
            error_log('[LVMS] MaintenanceService::checkAfterTrip failed: ' . $e->getMessage());
            // Never block trip completion on maintenance check failure
        }

        Helpers::setFlash('success', 'Trip completed.');
        Helpers::redirect('/trips/' . $id);
    }

    // ── Admin / Employee notes ────────────────────────────────────

    // POST /trips/{id}/notes
    public function notes(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE);

        $role = Auth::role();

        $tripModel = new TripModel();
        $trip      = $tripModel->findById($id);

        if (!$trip) {
            Helpers::setFlash('error', 'Trip not found.');
            Helpers::redirect('/trips');
        }

        Auth::requireCompanyScope((int) $trip['company_id'], '/trips/' . $id);

        if (in_array($trip['trip_status'], TRIP_TERMINAL_STATUSES, true)) {
            Helpers::setFlash('error', 'Notes cannot be added to a ' . $trip['trip_status'] . ' trip.');
            Helpers::redirect('/trips/' . $id);
        }

        if ($role === ROLE_EMPLOYEE) {
            $notes = trim($_POST['employee_notes'] ?? '');
            $field = 'employee_notes';

            // Employees can only update notes on their own reservation's trip
            if ((int) $trip['requested_by'] !== (int) Auth::id()) {
                Helpers::setFlash('error', 'Access denied.');
                Helpers::redirect('/reservations');
            }
        } else {
            $notes = trim($_POST['admin_notes'] ?? '');
            $field = 'admin_notes';
        }

        $tripModel->updateNotes($id, $field, $notes);

        Helpers::setFlash('success', 'Note saved.');
        Helpers::redirect('/trips/' . $id);
    }

    // ── Report incident (admin web, Decision 5) ───────────────────

    // POST /trips/{id}/incident
    public function reportIncident(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $tripModel = new TripModel();
        $trip      = $tripModel->findById($id);

        if (!$trip) {
            Helpers::setFlash('error', 'Trip not found.');
            Helpers::redirect('/trips');
        }

        Auth::requireCompanyScope((int) $trip['company_id'], '/trips/' . $id);

        // Only a trip that has actually started can have an incident — a
        // pending_start trip has no odometer_start_km / actual_departure
        // yet, and reportIncident()'s side effect of flipping trip_status
        // to 'incident' followed by resolveIncident() reverting it to
        // 'in_progress' would produce an in-progress trip that was never
        // started, silently corrupting those two columns.
        if (!in_array($trip['trip_status'], ['in_progress', 'incident'], true)) {
            Helpers::setFlash('error', 'Incidents can only be reported on a trip that has been started.');
            Helpers::redirect('/trips/' . $id);
        }

        $incidentType = trim($_POST['incident_type']  ?? '');
        $description  = trim($_POST['description']    ?? '');
        $occurredAt   = trim($_POST['occurred_at']    ?? '') ?: date('Y-m-d H:i:s');

        $allowed = ['accident', 'breakdown', 'traffic_delay', 'other'];
        if (!in_array($incidentType, $allowed, true) || $description === '') {
            Helpers::setFlash('error', 'Incident type and description are required.');
            Helpers::redirect('/trips/' . $id);
        }

        $incidentModel = new TripIncidentModel();
        $incidentId    = $incidentModel->create([
            'trip_id'       => $id,
            'reported_by'   => (int) Auth::id(),
            'incident_type' => $incidentType,
            'description'   => $description,
            'occurred_at'   => $occurredAt,
        ]);

        // Transition trip status to 'incident'
        $tripModel->updateToIncident($id);

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'INCIDENT_REPORTED',
            'trip_incidents',
            $incidentId,
            null,
            ['trip_id'       => $id,
             'incident_type' => $incidentType]
        );

        // Notify super_admins, fleet_admins, admins, and the reservation requester
        $userModel   = new UserModel();
        $superAdmins = array_column($userModel->findByRole(ROLE_SUPER_ADMIN), 'user_id');
        $fleetAdmins = array_column($userModel->findByRole(ROLE_FLEET_ADMIN), 'user_id');
        $admins      = array_column($userModel->findByRole(ROLE_ADMIN), 'user_id');
        $recipients  = array_unique(array_merge($superAdmins, $fleetAdmins, $admins));

        // Include the requester if they're not the reporter
        if ((int) $trip['requested_by'] !== (int) Auth::id()) {
            $recipients[] = (int) $trip['requested_by'];
            $recipients   = array_unique($recipients);
        }

        $notifModel = new NotificationModel();
        $notifModel->createForUsers($recipients, [
            'title'          => 'Incident Reported — '
                . ucwords(str_replace('_', ' ', $incidentType)),
            'message'        => $trip['reservation_code'] . ' to '
                . $trip['destination'] . ': ' . $description,
            'type'           => 'incident',
            'reference_id'   => $id,
            'reference_type' => 'trip',
        ]);

        Helpers::setFlash('success', 'Incident reported.');
        Helpers::redirect('/trips/' . $id);
    }

    // ── Live map stub (Step 16) ───────────────────────────────────

    // GET /trips/{id}/map
    public function liveMap(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $tripModel = new TripModel();
        $trip      = $tripModel->findById($id);

        if (!$trip) {
            Helpers::setFlash('error', 'Trip not found.');
            Helpers::redirect('/trips');
        }

        // Warehouse coordinates from system settings — these are the trip origin
        $settingModel = new SystemSettingModel();
        $warehouseLat = (float) ($settingModel->getByKey('warehouse_lat') ?? '0');
        $warehouseLng = (float) ($settingModel->getByKey('warehouse_lng') ?? '0');

        $this->render('live_map', [
            'page_title'    => 'Live Map — ' . $trip['reservation_code'],
            'trip'          => $trip,
            'warehouse_lat' => $warehouseLat,
            'warehouse_lng' => $warehouseLng,
        ]);
    }

    // ── Active trip stub — driver screen (Step 15) ────────────────

    // GET /trips/{id}/active
    // Was a graceful-fallback redirect for drivers landing here from the
    // Android app. Drivers no longer have any web access to trips, so this
    // route has no valid caller left — it 403s for every role instead of
    // silently redirecting into a page the driver can no longer see.
    public function active(int $id): void
    {
        Auth::requireRole();
    }

    // ── Resolve incident ──────────────────────────────────────────

    // POST /trips/{trip_id}/incident/{incident_id}/resolve
    public function resolveIncident(int $tripId, int $incidentId): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $resolutionNotes = trim($_POST['resolution_notes'] ?? '');

        if ($resolutionNotes === '') {
            Helpers::setFlash('error', 'Resolution notes are required.');
            Helpers::redirect('/trips/' . $tripId);
        }

        $tripModel = new TripModel();
        $trip      = $tripModel->findById($tripId);

        if (!$trip) {
            Helpers::setFlash('error', 'Trip not found.');
            Helpers::redirect('/trips');
        }

        Auth::requireCompanyScope((int) $trip['company_id'], '/trips/' . $tripId);

        $incidentModel = new TripIncidentModel();
        $incident      = $incidentModel->findById($incidentId);

        if (!$incident || (int) $incident['trip_id'] !== $tripId) {
            Helpers::setFlash('error', 'Incident not found on this trip.');
            Helpers::redirect('/trips/' . $tripId);
        }

        $updated = $incidentModel->markResolved($incidentId, $resolutionNotes);

        if (!$updated) {
            Helpers::setFlash('error', 'Incident not found or already resolved.');
            Helpers::redirect('/trips/' . $tripId);
        }

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'INCIDENT_RESOLVED',
            'trip_incidents',
            $incidentId,
            ['resolved' => 0],
            ['resolved' => 1, 'resolution_notes' => $resolutionNotes]
        );

        // If no more unresolved incidents, revert trip_status to in_progress
        if (!$incidentModel->hasUnresolvedByTrip($tripId)) {
            $tripModel->revertFromIncident($tripId);
            Helpers::setFlash('success', 'Incident resolved. Trip status restored to In Progress.');
        } else {
            Helpers::setFlash('success', 'Incident resolved. Other incidents still open.');
        }

        Helpers::redirect('/trips/' . $tripId);
    }

    // ── Cancel trip — terminal exit for a trip that cannot continue ────

    // POST /trips/{id}/cancel
    // Only reachable from trip_status = 'incident'. Releases the vehicle
    // and driver, cancels the reservation, and resolves any incidents
    // still open on the trip — closing every part of the lifecycle that
    // would otherwise be left stuck.
    public function cancelTrip(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);

        $reason           = trim($_POST['cancellation_reason'] ?? '');
        $vehicleCondition = $_POST['vehicle_condition'] ?? '';

        $tripModel = new TripModel();
        $trip      = $tripModel->findById($id);

        if (!$trip) {
            Helpers::setFlash('error', 'Trip not found.');
            Helpers::redirect('/trips');
        }

        Auth::requireCompanyScope((int) $trip['company_id'], '/trips/' . $id);

        if ($trip['trip_status'] !== 'incident') {
            Helpers::setFlash('error', 'Only a trip with an open incident can be cancelled.');
            Helpers::redirect('/trips/' . $id);
        }

        if ($reason === '') {
            Helpers::setFlash('error', 'A cancellation reason is required.');
            Helpers::redirect('/trips/' . $id);
        }

        // Whitelist — never pass a raw POST value into VehicleModel::updateStatus().
        $allowedConditions = [VEH_MAINTENANCE, VEH_AVAILABLE];
        if (!in_array($vehicleCondition, $allowedConditions, true)) {
            Helpers::setFlash('error', 'Select the vehicle\'s condition before cancelling.');
            Helpers::redirect('/trips/' . $id);
        }

        $tripModel->cancel($id, $reason, (int) Auth::id());

        $incidentModel = new TripIncidentModel();
        $incidentModel->resolveAllUnresolvedByTrip($id, $reason);

        $resModel = new ReservationModel();
        $resModel->cancel((int) $trip['reservation_id'], (int) Auth::id(), $reason);

        TripCancellationService::releaseAndNotify($trip, $vehicleCondition);

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'CANCELLED_TRIP',
            'trips',
            $id,
            ['trip_status' => 'incident'],
            ['trip_status'         => 'cancelled',
             'cancellation_reason' => $reason,
             'vehicle_status'      => $vehicleCondition]
        );

        // Notify super_admins, fleet_admins, and the reservation requester
        $userModel   = new UserModel();
        $superAdmins = array_column($userModel->findByRole(ROLE_SUPER_ADMIN), 'user_id');
        $fleetAdmins = array_column($userModel->findByRole(ROLE_FLEET_ADMIN), 'user_id');
        $recipients  = array_unique(array_merge($superAdmins, $fleetAdmins));

        if ((int) $trip['requested_by'] !== (int) Auth::id()) {
            $recipients[] = (int) $trip['requested_by'];
            $recipients   = array_unique($recipients);
        }

        $notifModel = new NotificationModel();
        $notifModel->createForUsers($recipients, [
            'title'          => 'Trip Cancelled — ' . $trip['reservation_code'],
            'message'        => $trip['reservation_code'] . ' to '
                . $trip['destination'] . ' was cancelled: ' . $reason,
            'type'           => 'trip',
            'reference_id'   => $id,
            'reference_type' => 'trip',
        ]);

        Helpers::setFlash('success', 'Trip cancelled.');
        Helpers::redirect('/trips/' . $id);
    }
}
