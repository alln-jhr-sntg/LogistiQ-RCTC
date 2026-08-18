<?php

require_once __DIR__ . '/BaseApiController.php';

/**
 * IncidentApiController
 *
 * Would allow drivers to report trip incidents via the Android app
 * (ROLE_DRIVER only — admins use the web interface), but the route below
 * is commented out in api/index.php ("Future feature"), so this
 * controller is currently unreachable.
 *
 * POST /api/incidents
 * Body: {
 *   "trip_id":        5,
 *   "incident_type":  "breakdown",
 *   "description":    "Engine overheating on EDSA",
 *   "latitude":       14.599512,   (optional)
 *   "longitude":      120.984222,  (optional)
 *   "occurred_at":    "2025-12-11 14:30:00"  (optional, defaults to NOW())
 * }
 * Success 201: {"status": "ok", "incident_id": N}
 * Error 403: {error: "Forbidden"} — wrong driver or wrong role
 */
class IncidentApiController extends BaseApiController
{
    private const ALLOWED_TYPES = ['accident', 'breakdown', 'traffic_delay', 'other'];

    public function store(): void
    {
        $this->requireRole(ROLE_DRIVER);

        $body = $this->body();

        $tripId       = (int)    ($body['trip_id']       ?? 0);
        $incidentType = trim(     $body['incident_type'] ?? '');
        $description  = trim(     $body['description']   ?? '');
        $occurredAt   = trim(     $body['occurred_at']   ?? '') ?: date('Y-m-d H:i:s');

        if ($tripId === 0 || $incidentType === '' || $description === '') {
            $this->error(400, 'trip_id, incident_type, and description are required');
        }

        if (!in_array($incidentType, self::ALLOWED_TYPES, true)) {
            $this->error(400,
                'Invalid incident_type. Allowed: '
                . implode(', ', self::ALLOWED_TYPES)
            );
        }

        // Validate trip ownership — drivers can only report on their own trips
        $tripModel = new TripModel();
        $trip      = $tripModel->findById($tripId);

        if (!$trip || (int) $trip['driver_id'] !== $this->authUserId()) {
            $this->error(403, 'Forbidden');
        }

        if ($trip['trip_status'] === 'completed') {
            $this->error(409, 'Cannot report an incident on a completed trip');
        }

        // Insert the incident
        $incidentModel = new TripIncidentModel();
        $incidentId    = $incidentModel->create([
            'trip_id'       => $tripId,
            'reported_by'   => $this->authUserId(),
            'incident_type' => $incidentType,
            'description'   => $description,
            'latitude'      => isset($body['latitude'])  ? (float) $body['latitude']  : null,
            'longitude'     => isset($body['longitude']) ? (float) $body['longitude'] : null,
            'occurred_at'   => $occurredAt,
        ]);

        // Transition trip status to 'incident'
        $tripModel->updateToIncident($tripId);
        
        (new AuditLogModel())->log(
            $this->authUserId(),
            'INCIDENT_REPORTED',
            'trip_incidents',
            $incidentId,
            null,
            ['trip_id' => $tripId, 'incident_type' => $incidentType, 'source' => 'android']
        );

        // Notify super_admins, fleet_admins, and admins
        $userModel   = new UserModel();
        $superAdmins = array_column($userModel->findByRole(ROLE_SUPER_ADMIN), 'user_id');
        $fleetAdmins = array_column($userModel->findByRole(ROLE_FLEET_ADMIN), 'user_id');
        $admins      = array_column($userModel->findByRole(ROLE_ADMIN),       'user_id');
        $recipients  = array_unique(array_merge($superAdmins, $fleetAdmins, $admins));

        if (!empty($recipients)) {
            $notifModel = new NotificationModel();
            $notifModel->createForUsers($recipients, [
                'title'          => 'Incident Reported (Driver) — '
                    . ucwords(str_replace('_', ' ', $incidentType)),
                'message'        => ($trip['reservation_code'] ?? 'Unknown')
                    . ' to ' . ($trip['destination'] ?? 'Unknown')
                    . ': ' . $description,
                'type'           => 'incident',
                'reference_id'   => $tripId,
                'reference_type' => 'trip',
            ]);
        }

        $this->json(['status' => 'ok', 'incident_id' => $incidentId], 201);
    }
}
