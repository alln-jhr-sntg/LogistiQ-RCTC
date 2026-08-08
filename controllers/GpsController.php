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
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN);

        $tripModel = new TripModel();
        $trip      = $tripModel->findById($tripId);

        if (!$trip) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['error' => 'Trip not found']);
            exit;
        }

        // Admin dept scoping — same rule as trips index and reports
        if (Auth::role() === ROLE_ADMIN) {
            $accessModel = new AdminDepartmentAccessModel();
            $deptIds     = array_map('intval', array_column(
                $accessModel->getByAdmin((int) Auth::id()), 'department_id'
            ));
            if (!in_array((int) $trip['department_id'], $deptIds, true)) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden']);
                exit;
            }
        }

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
