<?php

/**
 * TripCancellationService
 *
 * Releases the vehicle and driver held by a trip that is being cancelled,
 * and sends the driver-facing "Trip Cancelled" notification (picked up by
 * the Android driver app's trip list/history and notification feed).
 * Shared by TripController::cancelTrip() (only reachable from trip_status
 * = 'incident') and ReservationController::cancel() (an approved
 * reservation always has a trip — created at gatepass approval — so
 * cancelling the reservation must cancel it too).
 *
 * Does NOT transition the trip or reservation row itself — callers do
 * that first (TripModel::cancel() / ReservationModel::cancel()) since the
 * two call sites disagree on other side effects (incident resolution,
 * admin notifications) that stay call-site-specific.
 */
class TripCancellationService
{
    public static function releaseAndNotify(array $trip, string $vehicleStatus): void
    {
        $vehicleModel = new VehicleModel();
        $vehicleModel->updateStatus((int) $trip['vehicle_id'], $vehicleStatus);

        $driverModel = new DriverProfileModel();
        $driverModel->updateStatus((int) $trip['driver_id'], DRV_AVAILABLE);

        // Simpler, driver-facing wording (drivers_app.txt) rather than the
        // admin/requester message. This is also how a driver learns why
        // GPS tracking stopped: the app's GPS service gets a 409 from the
        // server the next time it tries to post a location for a trip that
        // is no longer in_progress.
        $notifModel = new NotificationModel();
        $notifModel->createForUsers([(int) $trip['driver_id']], [
            'title'          => 'Trip Cancelled',
            'message'        => 'Your assigned trip has been cancelled: '
                . $trip['reservation_code'] . '.',
            'type'           => 'trip',
            'reference_id'   => (int) $trip['trip_id'],
            'reference_type' => 'trip',
        ]);
    }
}
