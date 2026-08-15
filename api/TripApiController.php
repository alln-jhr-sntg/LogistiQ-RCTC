<?php

require_once __DIR__ . '/BaseApiController.php';

/**
 * TripApiController
 *
 * Handles trip operations for the Android driver app.
 * All endpoints require ROLE_DRIVER.
 *
 * GET   /api/trips                — driver's active trips
 * PATCH /api/trips/{id}/start     — start a pending trip
 * PATCH /api/trips/{id}/complete  — complete an in-progress trip
 */
class TripApiController extends BaseApiController
{
    // GET /api/trips
    // Returns every trip for the authenticated driver — 'pending_start' and
    // 'in_progress' trips are the ones the app expects the driver to act on
    // (Home / Trips tabs), while 'completed' and 'cancelled' trips feed the
    // History tabs. Terminal trips are capped to the most recent 50 so the
    // payload doesn't grow unbounded as trip history accumulates; active
    // trips are never capped. Rows already arrive newest-first.
    public function index(): void
    {
        $this->requireRole(ROLE_DRIVER);

        $tripModel = new TripModel();
        $rows      = $tripModel->findForDriver($this->authUserId());

        $trips         = [];
        $terminalCount = 0;
        foreach ($rows as $row) {
            $isTerminal = in_array($row['trip_status'], TRIP_TERMINAL_STATUSES, true);
            if ($isTerminal) {
                if ($terminalCount >= 50) {
                    continue;
                }
                $terminalCount++;
            }
            $trips[] = [
                'trip_id'             => (int) $row['trip_id'],
                'reservation_id'      => (int) $row['reservation_id'],
                'reservation_code'    => $row['reservation_code'],
                'destination'         => $row['destination'],
                'departure_datetime'  => $row['departure_datetime'],
                'return_datetime'     => $row['return_datetime'],
                'trip_status'         => $row['trip_status'],
                'plate_number'        => $row['plate_number'],
                'vehicle_brand'       => $row['vehicle_brand'],
                'vehicle_model'       => $row['vehicle_model'],
                'odometer_start_km'  => $row['odometer_start_km'] !== null ? (float) $row['odometer_start_km'] : null,
                'passenger_count'     => (int) $row['passenger_count'],
                'cargo_weight_kg'     => (float) $row['cargo_weight_kg'],
                'purpose_name'        => $row['purpose_name'],
                'odometer_end_km'     => $row['odometer_end_km'] !== null ? (float) $row['odometer_end_km'] : null,
                'actual_departure'    => $row['actual_departure'],
                'actual_return'       => $row['actual_return'],
                'cancellation_reason' => $row['cancellation_reason'],
                // Every trip implies an approved gatepass (trips are only
                // created at gatepass approval), but these are still
                // rendered null-safe on the Android side.
                'gatepass_code'         => $row['gatepass_code'],
                'gatepass_status'       => $row['gatepass_status'],
                'gatepass_approver'     => $row['gatepass_approver'],
                'gatepass_approved_at'  => $row['gatepass_approved_at'],
            ];
        }

        $this->json(['trips' => $trips]);
    }

    // PATCH /api/trips/{id}/start
    // {id} is the trip_id. The trip row already exists as
    // 'pending_start' (created at reservation approval).
    public function start(int $tripId): void
    {
        $this->requireRole(ROLE_DRIVER);

        $body          = $this->body();
        $odometerStart = (float) ($body['odometer_start_km'] ?? 0);

        $tripModel = new TripModel();
        $trip      = $tripModel->findById($tripId);

        if (!$trip) {
            $this->error(404, 'Trip not found');
        }

        // Drivers can only act on their own trips
        if ((int) $trip['driver_id'] !== $this->authUserId()) {
            $this->error(403, 'Forbidden');
        }

        if ($trip['trip_status'] !== 'pending_start') {
            $this->error(409, 'Trip has already been started or completed');
        }

        if ($odometerStart < 0) {
            $this->error(400, 'Odometer reading cannot be negative');
        }

        // Trip start, reservation status, and vehicle/driver status must
        // land together — a failure partway through would otherwise leave
        // the trip started but the vehicle/driver still shown as available.
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $tripModel->updateStarted($tripId, $odometerStart);

            // Transition reservation, vehicle, driver
            (new ReservationModel())->updateStatus((int) $trip['reservation_id'], 'in_progress');
            (new VehicleModel())->updateStatus((int) $trip['vehicle_id'], 'on_trip');
            (new DriverProfileModel())->updateStatus($this->authUserId(), 'on_trip');
            (new AuditLogModel())->log(
                $this->authUserId(),
                'TRIP_STARTED',
                'trips',
                $tripId,
                ['trip_status' => 'pending_start'],
                ['trip_status' => 'in_progress', 'odometer_start_km' => $odometerStart, 'source' => 'android']
            );

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        $this->json([
            'status'  => 'ok',
            'trip_id' => $tripId,
            'message' => 'Trip started',
        ]);
    }

    // PATCH /api/trips/{id}/complete
    public function complete(int $tripId): void
    {
        $this->requireRole(ROLE_DRIVER);

        $body        = $this->body();
        $odometerEnd = (float) ($body['odometer_end_km'] ?? 0);

        $tripModel = new TripModel();
        $trip      = $tripModel->findById($tripId);

        if (!$trip) {
            $this->error(404, 'Trip not found');
        }

        if ((int) $trip['driver_id'] !== $this->authUserId()) {
            $this->error(403, 'Forbidden');
        }

        if (in_array($trip['trip_status'], TRIP_TERMINAL_STATUSES, true)) {
            $this->error(409, 'Trip is already ' . $trip['trip_status']);
        }

        if ($trip['trip_status'] === 'pending_start') {
            $this->error(409, 'Trip has not been started yet');
        }

        $odometerStart = (float) $trip['odometer_start_km'];
        if ($odometerEnd < $odometerStart) {
            $this->error(400,
                'End odometer (' . $odometerEnd . ') cannot be less than'
                . ' start odometer (' . $odometerStart . ')'
            );
        }

        $vehicleId = (int) $trip['vehicle_id'];

        // Trip completion, reservation status, and vehicle/driver release
        // must land together — a failure partway through would otherwise
        // leave the trip completed but the vehicle/driver still shown as
        // on_trip, or the odometer left unupdated.
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $tripModel->updateCompleted($tripId, $odometerEnd);

            (new ReservationModel())->updateStatus((int) $trip['reservation_id'], 'completed');
            (new VehicleModel())->updateStatus($vehicleId, 'available');
            (new VehicleModel())->updateOdometer($vehicleId, $odometerEnd);
            (new DriverProfileModel())->updateStatus($this->authUserId(), 'available');
            (new AuditLogModel())->log(
                $this->authUserId(),
                'TRIP_COMPLETED',
                'trips',
                $tripId,
                ['trip_status' => $trip['trip_status']],
                ['trip_status' => 'completed', 'odometer_end_km' => $odometerEnd, 'source' => 'android']
            );

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        // MaintenanceService check — same try-catch pattern as web complete()
        try {
            MaintenanceService::checkAfterTrip($vehicleId, $odometerEnd);
        } catch (Throwable $e) {
            error_log('[LVMS API] MaintenanceService failed: ' . $e->getMessage());
        }

        // Same try-catch pattern — a notification failure must never fail
        // the trip-completion response.
        try {
            (new NotificationModel())->createForUsers([$this->authUserId()], [
                'title'          => 'Trip Completed',
                'message'        => 'Your trip has been successfully recorded: '
                    . $trip['reservation_code'] . '.',
                'type'           => 'trip',
                'reference_id'   => $tripId,
                'reference_type' => 'trip',
            ]);
        } catch (Throwable $e) {
            error_log('[LVMS API] Notification failed: ' . $e->getMessage());
        }

        $this->json([
            'status'  => 'ok',
            'trip_id' => $tripId,
            'message' => 'Trip completed',
        ]);
    }
}
