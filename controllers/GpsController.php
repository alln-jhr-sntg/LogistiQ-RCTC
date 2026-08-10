<?php

/**
 * GpsController
 *
 * Serves GPS data to the admin live map page.
 * Separate from TripController because GPS data access has its own
 * auth and scoping rules.
 *
 * GET /gps/{trip_id}/feed → JSON (polled by live_map.js)
 */
class GpsController
{
    // GET /gps/{id}/feed
    // Returns latest GPS points for a trip as JSON.
    // Also returns trip_status so live_map.js can stop polling
    // when the trip is completed.
    //
    // Response shape:
    //   { "trip_status": "in_progress",
    //     "points": [
    //       { "latitude": "14.59951200", "longitude": "120.98422200",
    //         "speed_kph": "45.50", "heading_degrees": 180,
    //         "accuracy_meters": "8.50", "logged_at": "2025-12-11 14:30:25" },
    //       ...
    //     ]
    //   }
    // Points are ordered newest-first (latest position = points[0]).
    public function feed(int $tripId): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $tripModel = new TripModel();
        $trip      = $tripModel->findById($tripId);

        if (!$trip) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['error' => 'Trip not found']);
            exit;
        }

        // No company scoping — the live map feed is a read-only view, and
        // admin/fleet_admin/super_admin may all view any company's trips.

        $gpsModel = new GpsTrackingLogModel();
        $points   = $gpsModel->getLatestByTrip($tripId, 50);

        header('Content-Type: application/json');
        echo json_encode([
            'trip_status' => $trip['trip_status'],
            'points'      => $points,
        ]);
        exit;
    }
}
