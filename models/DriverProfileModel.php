<?php

/**
 * DriverProfileModel
 *
 * Wraps the `driver_profiles` table.
 * One profile per driver user — enforced by UNIQUE on user_id.
 *
 * Used in:
 *   UserController driverProfile / updateDriverProfile
 *   ReservationController driver dropdown (available drivers)
 *   TripController status transitions (on_trip / available)
 */
class DriverProfileModel extends BaseModel
{
    /**
     * Return the driver profile for a given user_id, or null if none exists yet.
     *
     * @return array<string, mixed>|null
     */
    public function findByUser(int $userId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM driver_profiles WHERE user_id = :user_id LIMIT 1',
            [':user_id' => $userId]
        );
    }

    /**
     * Return all available drivers joined with their user info.
     * Used in the reservation review form to populate
     * the driver assignment dropdown.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAvailable(): array
    {
        return $this->fetchAll(
            'SELECT   dp.*, u.first_name, u.last_name, u.employee_id
             FROM     driver_profiles dp
             JOIN     users u ON u.user_id = dp.user_id
             WHERE    dp.status = \'available\'
               AND    u.is_active = 1
             ORDER BY u.last_name ASC, u.first_name ASC'
        );
    }

    /**
     * Insert a new driver profile.
     * Called the first time a driver's profile form is submitted.
     */
    public function create(int $userId, array $data): void
    {
        $this->execute(
            'INSERT INTO driver_profiles
                (user_id, license_number, license_type, license_expiry,
                 license_photo, restriction_codes, status)
             VALUES
                (:user_id, :license_number, :license_type, :license_expiry,
                 :license_photo, :restriction_codes, :status)',
            [
                ':user_id'          => $userId,
                ':license_number'   => $data['license_number'],
                ':license_type'     => $data['license_type'],
                ':license_expiry'   => $data['license_expiry'],
                ':license_photo'    => $data['license_photo']    ?? null,
                ':restriction_codes'=> $data['restriction_codes']?? null,
                ':status'           => $data['status']           ?? 'available',
            ]
        );
    }

    /**
     * Update an existing driver profile.
     * Status transitions to 'on_trip' are managed by TripController —
     * this method is for admin manual edits only (off_duty, on_leave, etc.).
     */
    public function update(int $userId, array $data): void
    {
        $this->execute(
            'UPDATE driver_profiles
             SET    license_number    = :license_number,
                    license_type      = :license_type,
                    license_expiry    = :license_expiry,
                    license_photo     = :license_photo,
                    restriction_codes = :restriction_codes,
                    status            = :status
             WHERE  user_id           = :user_id',
            [
                ':license_number'    => $data['license_number'],
                ':license_type'      => $data['license_type'],
                ':license_expiry'    => $data['license_expiry'],
                ':license_photo'     => $data['license_photo']    ?? null,
                ':restriction_codes' => $data['restriction_codes']?? null,
                ':status'            => $data['status'],
                ':user_id'           => $userId,
            ]
        );
    }

    /**
     * Update only the driver's availability status.
     * Called by TripController::start() and complete().
     */
    public function updateStatus(int $userId, string $status): void
    {
        $this->execute(
            'UPDATE driver_profiles SET status = :status WHERE user_id = :user_id',
            [':status' => $status, ':user_id' => $userId]
        );
    }

    /**
     * Return true if this driver has a reservation in a blocking status
     * (RES_BLOCKING_STATUSES) that overlaps the requested window. Mirrors
     * VehicleModel::hasConflictingReservation() — driver_profiles.status
     * only tracks 'on_trip' from the moment the trip actually starts
     * (ReservationController::approve() deliberately leaves an
     * assigned driver 'available' through gatepass review), so it carries
     * no information about a driver's future commitments. This is the
     * check that fills that gap: used by the review form's driver dropdown
     * and by ReservationController::approve() to refuse a double-book
     * server-side.
     */
    public function hasConflictingReservation(
        int    $driverUserId,
        string $requestedDeparture,
        string $requestedReturn,
        ?int   $excludeReservationId = null
    ): bool {
        $params = [
            ':driver_id'           => $driverUserId,
            ':requested_return'    => $requestedReturn,
            ':requested_departure' => $requestedDeparture,
        ];
        $statusList = $this->blockingStatusPlaceholders($params);

        $exclude = '';
        if ($excludeReservationId !== null) {
            $exclude = ' AND reservation_id != :exclude_id';
            $params[':exclude_id'] = $excludeReservationId;
        }

        $row = $this->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM   reservations
             WHERE  assigned_driver_id  = :driver_id
               AND  status              IN ($statusList)
               AND  departure_datetime  < :requested_return
               AND  return_datetime     > :requested_departure
               $exclude",
            $params
        );
        return (int) ($row['cnt'] ?? 0) > 0;
    }

    /**
     * Return available drivers whose window is genuinely free — same base
     * filter as findAvailable() (status = 'available', active user), plus a
     * NOT EXISTS on the blocking-reservation overlap so a driver already
     * committed elsewhere during this window can never be selected, and a
     * license_expiry check so an expired license isn't offered either
     * (license_expiry is NOT NULL on driver_profiles but was never read
     * anywhere before this). Used by the review form's driver dropdown.
     *
     * No new column was needed on users or driver_profiles for this — the
     * interval truth is derived from reservations.assigned_driver_id,
     * exactly as it already is for vehicles via assigned_vehicle_id.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAvailableForWindow(string $requestedDeparture, string $requestedReturn): array
    {
        $params = [
            ':requested_return'    => $requestedReturn,
            ':requested_departure' => $requestedDeparture,
            ':departure_date'      => substr($requestedDeparture, 0, 10),
        ];
        $statusList = $this->blockingStatusPlaceholders($params);

        return $this->fetchAll(
            "SELECT   dp.*, u.first_name, u.last_name, u.employee_id
             FROM     driver_profiles dp
             JOIN     users u ON u.user_id = dp.user_id
             WHERE    dp.status         = 'available'
               AND    u.is_active       = 1
               AND    dp.license_expiry >= :departure_date
               AND    NOT EXISTS (
                   SELECT 1
                   FROM   reservations r
                   WHERE  r.assigned_driver_id = u.user_id
                     AND  r.status              IN ($statusList)
                     AND  r.departure_datetime  < :requested_return
                     AND  r.return_datetime     > :requested_departure
               )
             ORDER BY u.last_name ASC, u.first_name ASC",
            $params
        );
    }

    /**
     * Bind RES_BLOCKING_STATUSES as individual named placeholders (PDO with
     * emulated prepares off cannot bind an array to a single IN() param)
     * and return the comma-joined placeholder list to splice into the SQL.
     * Mutates $params by reference so callers just merge their own bindings
     * in and pass the same array to fetchAll()/fetchOne(). Mirrors the
     * identical helper in VehicleModel.
     */
    private function blockingStatusPlaceholders(array &$params): string
    {
        $placeholders = [];
        foreach (RES_BLOCKING_STATUSES as $i => $status) {
            $key = ":blocking_status_{$i}";
            $placeholders[] = $key;
            $params[$key]   = $status;
        }
        return implode(',', $placeholders);
    }
}
