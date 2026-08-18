<?php

/**
 * TripIncidentModel
 *
 * Wraps the `trip_incidents` table.
 *
 * Incidents are reported via the web by admin-and-above roles
 * (TripController::reportIncident()). IncidentApiController offers a
 * driver-facing Android path, but its route is commented out in
 * api/index.php ("Future feature"), so it isn't reachable yet.
 *
 * reported_by distinguishes the filer by role.
 */
class TripIncidentModel extends BaseModel
{
    /**
     * Insert a new incident record. Returns the new incident_id.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO trip_incidents
                (trip_id, reported_by, incident_type, description,
                 latitude, longitude, occurred_at, resolved, resolution_notes)
             VALUES
                (:trip_id, :reported_by, :incident_type, :description,
                 :latitude, :longitude, :occurred_at, 0, NULL)',
            [
                ':trip_id'       => $data['trip_id'],
                ':reported_by'   => $data['reported_by'],
                ':incident_type' => $data['incident_type'],
                ':description'   => $data['description'],
                ':latitude'      => $data['latitude']    ?? null,
                ':longitude'     => $data['longitude']   ?? null,
                ':occurred_at'   => $data['occurred_at'],
            ]
        );
        return $this->lastInsertId();
    }

    /**
     * Return all incidents for a given trip, newest first.
     * Joined with reporter's name for display.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByTrip(int $tripId): array
    {
        return $this->fetchAll(
            'SELECT   i.*,
                      u.first_name AS reporter_first_name,
                      u.last_name  AS reporter_last_name,
                      u.role       AS reporter_role
             FROM     trip_incidents i
             JOIN     users          u ON u.user_id = i.reported_by
             WHERE    i.trip_id      = :trip_id
             ORDER BY i.occurred_at  DESC',
            [':trip_id' => $tripId]
        );
    }

    /**
     * Return a single incident by primary key.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT i.*, u.first_name AS reporter_first_name,
                         u.last_name  AS reporter_last_name
             FROM   trip_incidents i
             JOIN   users          u ON u.user_id = i.reported_by
             WHERE  i.incident_id  = :id
             LIMIT  1',
            [':id' => $id]
        );
    }

    /**
     * Mark an incident as resolved.
     * Returns true if the row was actually updated (was unresolved before).
     */
    public function markResolved(int $id, string $resolutionNotes): bool
    {
        $affected = $this->execute(
            'UPDATE trip_incidents
             SET    resolved          = 1,
                    resolution_notes  = :notes
             WHERE  incident_id       = :id
               AND  resolved          = 0',
            [':notes' => $resolutionNotes, ':id' => $id]
        );
        return $affected > 0;
    }

    /**
     * Check whether any unresolved incidents exist for a trip.
     * Used to decide whether to revert trip_status from 'incident'
     * back to 'in_progress' when one incident is resolved.
     */
    public function hasUnresolvedByTrip(int $tripId): bool
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt
             FROM   trip_incidents
             WHERE  trip_id  = :trip_id
               AND  resolved = 0',
            [':trip_id' => $tripId]
        );
        return (int) ($row['cnt'] ?? 0) > 0;
    }

    /**
     * Mark every unresolved incident on a trip as resolved, all with the
     * same resolution notes. Called by TripController::cancelTrip() so a
     * cancellation reason closes out every open incident at once, rather
     * than leaving them unresolved on a trip that will never revisit them.
     * Returns the number of incidents resolved.
     */
    public function resolveAllUnresolvedByTrip(int $tripId, string $resolutionNotes): int
    {
        return $this->execute(
            'UPDATE trip_incidents
             SET    resolved          = 1,
                    resolution_notes  = :notes
             WHERE  trip_id           = :trip_id
               AND  resolved          = 0',
            [':notes' => $resolutionNotes, ':trip_id' => $tripId]
        );
    }
}
