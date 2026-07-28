<?php

/**
 * DriverProfileModel
 *
 * Wraps the `driver_profiles` table.
 * One profile per driver user — enforced by UNIQUE on user_id.
 *
 * Used in:
 *   Step 6g  — UserController driverProfile / updateDriverProfile
 *   Step 10  — ReservationController driver dropdown (available drivers)
 *   Step 11  — TripController status transitions (on_trip / available)
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
     * Used in the reservation review form (Step 10) to populate
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
}
