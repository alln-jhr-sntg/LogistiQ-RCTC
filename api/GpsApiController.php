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
 * Error   400: {"error": "<message>"}
 *              — malformed request, or a field outside its sane range
 *              (see the *_MIN/*_MAX constants below). Nothing is inserted.
 * Error   409: {"error": "trip_not_active", "trip_status": "<status>"}
 *              — trip exists and belongs to this driver, but its status
 *              isn't one of TRIP_TRACKING_STATUSES ('in_progress' or
 *              'incident' — tracking deliberately continues through an
 *              incident, since location matters most during an accident).
 *              Nothing is inserted. The Android service should stop
 *              itself on this response; 'completed' and 'cancelled' are
 *              terminal (do not retry), 'pending_start' may still start.
 * Error   422: {"error": "gps_implausible_movement", "implied_speed_kph": N}
 *              — the straight-line distance from this trip's last logged
 *              point, divided by the time elapsed, implies a speed no
 *              fleet vehicle could plausibly reach. Usually a GPS jump/
 *              glitch rather than real movement. Nothing is inserted.
 */
class GpsApiController extends BaseApiController
{
    private const LATITUDE_MIN  = -90.0;
    private const LATITUDE_MAX  = 90.0;
    private const LONGITUDE_MIN = -180.0;
    private const LONGITUDE_MAX = 180.0;

    // Heading is a compass bearing: 0 (inclusive) up to but not including
    // 360, since 360 is the same direction as 0.
    private const HEADING_MAX = 360.0;

    // No fleet vehicle plausibly exceeds this on a public road. Used both
    // to reject an obviously-wrong self-reported speed_kph and as the
    // ceiling for the implied-speed movement check below.
    private const MAX_PLAUSIBLE_SPEED_KPH = 200.0;

    // Mirrors MAX_ACCEPTABLE_ACCURACY_METERS in the Android app's
    // GpsTrackingService — the app already discards any fix worse than
    // this client-side, so a ping above it means a stale/non-standard
    // client rather than a legitimately poor fix.
    private const MAX_ACCURACY_METERS = 50.0;

    // gps_tracking_logs.logged_at is a TIMESTAMP (1-second resolution), so
    // two pings can land in the same second. Below this floor the implied
    // speed is dominated by rounding/noise rather than real movement, so
    // the movement check is skipped rather than risking a false reject.
    private const MIN_SANITY_INTERVAL_SECONDS = 2;

    public function store(): void
    {
        $body = $this->body();

        $tripId   = (int)   ($body['trip_id']   ?? 0);
        $lat      = (float) ($body['latitude']  ?? 0);
        $lng      = (float) ($body['longitude'] ?? 0);

        if ($tripId === 0 || ($lat === 0.0 && $lng === 0.0)) {
            $this->error(400, 'trip_id, latitude, and longitude are required');
        }

        if ($lat < self::LATITUDE_MIN || $lat > self::LATITUDE_MAX) {
            $this->error(400, 'latitude must be between -90 and 90');
        }

        if ($lng < self::LONGITUDE_MIN || $lng > self::LONGITUDE_MAX) {
            $this->error(400, 'longitude must be between -180 and 180');
        }

        $speedKph = null;
        if (isset($body['speed_kph'])) {
            $speedKph = (float) $body['speed_kph'];
            if ($speedKph < 0 || $speedKph > self::MAX_PLAUSIBLE_SPEED_KPH) {
                $this->error(400, 'speed_kph must be between 0 and ' . self::MAX_PLAUSIBLE_SPEED_KPH);
            }
        }

        $headingDegrees = null;
        if (isset($body['heading_degrees'])) {
            $heading = (float) $body['heading_degrees'];
            if ($heading < 0 || $heading >= self::HEADING_MAX) {
                $this->error(400, 'heading_degrees must be between 0 and 359.999...');
            }
            $headingDegrees = (int) $heading;
        }

        $accuracyMeters = null;
        if (isset($body['accuracy_meters'])) {
            $accuracyMeters = (float) $body['accuracy_meters'];
            if ($accuracyMeters < 0 || $accuracyMeters > self::MAX_ACCURACY_METERS) {
                $this->error(400, 'accuracy_meters must be between 0 and ' . self::MAX_ACCURACY_METERS);
            }
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

        // Machine-readable so the Android service can stop itself and
        // tell the driver which terminal (or otherwise inactive) state
        // the trip landed in. Tracking continues through 'incident'.
        if (!in_array($trip['trip_status'], TRIP_TRACKING_STATUSES, true)) {
            $this->json([
                'error'       => 'trip_not_active',
                'trip_status' => $trip['trip_status'],
            ], 409);
        }

        $gpsModel = new GpsTrackingLogModel();

        // Basic movement sanity check against this trip's last logged
        // point — see the 422 case in the class docblock.
        $lastPoint = $gpsModel->getLastPointWithElapsed($tripId);
        if ($lastPoint !== null) {
            $elapsedSeconds = (int) $lastPoint['elapsed_seconds'];
            if ($elapsedSeconds >= self::MIN_SANITY_INTERVAL_SECONDS) {
                $impliedSpeedKph = self::haversineKm(
                    (float) $lastPoint['latitude'],
                    (float) $lastPoint['longitude'],
                    $lat,
                    $lng
                ) / ($elapsedSeconds / 3600);

                if ($impliedSpeedKph > self::MAX_PLAUSIBLE_SPEED_KPH) {
                    $this->json([
                        'error'             => 'gps_implausible_movement',
                        'implied_speed_kph' => round($impliedSpeedKph, 1),
                    ], 422);
                }
            }
        }

        $gpsModel->create([
            'trip_id'         => $tripId,
            'latitude'        => $lat,
            'longitude'       => $lng,
            'speed_kph'       => $speedKph,
            'heading_degrees' => $headingDegrees,
            'accuracy_meters' => $accuracyMeters,
        ]);

        $this->json(['status' => 'ok'], 201);
    }

    // Great-circle distance between two coordinates, in kilometers.
    private static function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);

        // Normalize the longitude delta into (-180, 180] so this stays
        // correct across the antimeridian (not a real concern for a
        // Philippines-only fleet, but cheap to get right).
        $dLng = fmod($lng2 - $lng1 + 180.0, 360.0);
        if ($dLng < 0) {
            $dLng += 360.0;
        }
        $dLng = deg2rad($dLng - 180.0);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }
}
