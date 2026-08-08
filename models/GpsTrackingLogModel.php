<?php

/**
 * GpsTrackingLogModel
 *
 * Wraps the `gps_tracking_logs` table.
 * High-volume write table — BIGINT PK.
 *
 * Used in:
 *   Step 15 — GpsApiController (Android driver inserts)
 *   Step 16 — GpsController (admin reads for live map feed)
 */
class GpsTrackingLogModel extends BaseModel
{
    /**
     * Insert a GPS tracking point from the Android foreground service.
     * Returns the new log_id.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO gps_tracking_logs
                (trip_id, latitude, longitude,
                 speed_kph, heading_degrees, accuracy_meters)
             VALUES
                (:trip_id, :latitude, :longitude,
                 :speed_kph, :heading_degrees, :accuracy_meters)',
            [
                ':trip_id'          => $data['trip_id'],
                ':latitude'         => $data['latitude'],
                ':longitude'        => $data['longitude'],
                ':speed_kph'        => $data['speed_kph']        ?? null,
                ':heading_degrees'  => $data['heading_degrees']  ?? null,
                ':accuracy_meters'  => $data['accuracy_meters']  ?? null,
            ]
        );
        return $this->lastInsertId();
    }

    /**
     * Return the N most recent GPS points for a trip, newest first.
     * Used by GpsController (Step 16) to feed the admin live map.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLatestByTrip(int $tripId, int $limit = 50): array
    {
        return $this->fetchAll(
            'SELECT latitude, longitude, speed_kph,
                    heading_degrees, accuracy_meters, logged_at
             FROM   gps_tracking_logs
             WHERE  trip_id  = :trip_id
             ORDER  BY logged_at DESC
             LIMIT  :limit',
            [':trip_id' => $tripId, ':limit' => $limit]
        );
    }
}
