<?php

/**
 * GpsTrackingLogModel
 *
 * Wraps the `gps_tracking_logs` table.
 * High-volume write table — BIGINT PK.
 *
 * Used in:
 *   GpsApiController (Android driver inserts)
 *   GpsController (admin reads for live map feed)
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
     * Used by GpsController to feed the admin live map.
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

    /**
     * Return this trip's most recent GPS point plus how many seconds have
     * elapsed since it was logged — computed with MySQL's own NOW(), not
     * PHP's time(), so it isn't thrown off by the PHP/MySQL timezone
     * mismatch noted in config/database.php (the session is pinned to
     * +08:00 for logged_at, while PHP's default timezone is not).
     * Used by GpsApiController's movement sanity check.
     *
     * @return array{latitude: string, longitude: string, elapsed_seconds: string}|null
     */
    public function getLastPointWithElapsed(int $tripId): ?array
    {
        return $this->fetchOne(
            'SELECT latitude, longitude,
                    TIMESTAMPDIFF(SECOND, logged_at, NOW()) AS elapsed_seconds
             FROM   gps_tracking_logs
             WHERE  trip_id = :trip_id
             ORDER  BY logged_at DESC
             LIMIT  1',
            [':trip_id' => $tripId]
        );
    }
}
