<?php

require_once __DIR__ . '/BaseApiController.php';

/**
 * GpsApiController
 *
 * Accepts GPS coordinates posted by the Android foreground service
 * during an active trip and inserts them into gps_tracking_logs.
 *
 * POST /api/gps
 * Body: {
 *   "trip_id":          5,
 *   "latitude":         14.599512,
 *   "longitude":        120.984222,
 *   "speed_kph":        45.5,        (optional)
 *   "heading_degrees":  180,         (optional)
 *   "accuracy_meters":  8.5          (optional)
 * }
 * Success 201: {"status": "ok"}
 */
class GpsApiController extends BaseApiController
{
    public function store(): void
    {
        $body = $this->body();

        $tripId   = (int)   ($body['trip_id']   ?? 0);
        $lat      = (float) ($body['latitude']  ?? 0);
        $lng      = (float) ($body['longitude'] ?? 0);

        if ($tripId === 0 || ($lat === 0.0 && $lng === 0.0)) {
            $this->error(400, 'trip_id, latitude, and longitude are required');
        }

        // Validate that this trip belongs to the authenticated driver.
        // Prevents a driver from posting GPS to another driver's trip.
        $tripModel = new TripModel();
        $trip      = $tripModel->findById($tripId);

        if (!$trip) {
            $this->error(404, 'Trip not found');
        }

        if ((int) $trip['driver_id'] !== $this->authUserId()) {
            $this->error(403, 'Forbidden');
        }

        if ($trip['trip_status'] !== 'in_progress') {
            $this->error(409, 'GPS tracking is only active for in-progress trips');
        }

        $gpsModel = new GpsTrackingLogModel();
        $gpsModel->create([
            'trip_id'         => $tripId,
            'latitude'        => $lat,
            'longitude'       => $lng,
            'speed_kph'       => isset($body['speed_kph'])       ? (float) $body['speed_kph']       : null,
            'heading_degrees' => isset($body['heading_degrees'])  ? (int)   $body['heading_degrees']  : null,
            'accuracy_meters' => isset($body['accuracy_meters'])  ? (float) $body['accuracy_meters']  : null,
        ]);

        $this->json(['status' => 'ok'], 201);
    }
}
