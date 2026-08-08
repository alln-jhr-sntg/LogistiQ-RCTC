<?php

/**
 * TripModel
 *
 * Wraps the `trips` table. A trip row is created when a reservation is
 * approved (trip_status = 'pending_start'), then transitioned to
 * 'in_progress' when started and 'completed' when finished.
 *
 * Status transitions (who drives them):
 *   pending_start → in_progress   TripController::start() / TripApiController
 *   in_progress   → completed     TripController::complete() / TripApiController
 *   in_progress   → incident      TripController::reportIncident() / IncidentApiController
 *   incident      → in_progress   TripController::resolveIncident()
 *
 * Used in:
 *   Step 11 — TripController (start, complete, notes, incident, index, detail)
 *   Step 15 — TripApiController (Android driver API)
 */
class TripModel extends BaseModel
{
    /**
     * Shared JOIN fragment for all trip queries.
     * Pulls in vehicle, driver, reservation, department, and requester data.
     */
    private function baseSelect(): string
    {
        return
            'SELECT t.*,
                    r.reservation_code,
                    r.destination,
                    r.destination_lat,
                    r.destination_lng,
                    r.departure_datetime,
                    r.return_datetime,
                    r.passenger_count,
                    r.cargo_weight_kg,
                    r.cargo_description,
                    r.status           AS reservation_status,
                    r.requested_by,
                    p.purpose_name,
                    v.plate_number,
                    v.brand            AS vehicle_brand,
                    v.model            AS vehicle_model,
                    v.current_odometer_km AS vehicle_current_odometer,
                    drv.first_name     AS driver_first_name,
                    drv.last_name      AS driver_last_name,
                    drv.phone_number   AS driver_phone,
                    req.first_name     AS requester_first_name,
                    req.last_name      AS requester_last_name,
                    d.department_name,
                    d.department_id,
                    c.company_name,
                    c.company_code
             FROM   trips        t
             JOIN   reservations r   ON r.reservation_id  = t.reservation_id
             JOIN   vehicles     v   ON v.vehicle_id       = t.vehicle_id
             JOIN   users        drv ON drv.user_id        = t.driver_id
             JOIN   users        req ON req.user_id        = r.requested_by
             JOIN   departments  d   ON d.department_id    = r.department_id
             JOIN   companies    c   ON c.company_id       = d.company_id
             JOIN   trip_purposes p  ON p.purpose_id       = r.purpose_id';
    }

    /**
     * Insert a new trip row. Returns the new trip_id.
     *
     * Called from ReservationController::approve() with trip_status = 'pending_start'.
     * Called from TripController::start() if somehow no pre-created row exists.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO trips
                (reservation_id, vehicle_id, driver_id,
                 odometer_start_km, actual_departure,
                 trip_status, employee_notes, admin_notes)
             VALUES
                (:reservation_id, :vehicle_id, :driver_id,
                 :odometer_start_km, :actual_departure,
                 :trip_status, :employee_notes, :admin_notes)',
            [
                ':reservation_id'   => $data['reservation_id'],
                ':vehicle_id'       => $data['vehicle_id'],
                ':driver_id'        => $data['driver_id'],
                ':odometer_start_km'=> $data['odometer_start_km'] ?? null,
                ':actual_departure' => $data['actual_departure']  ?? null,
                ':trip_status'      => $data['trip_status']       ?? 'pending_start',
                ':employee_notes'   => $data['employee_notes']    ?? null,
                ':admin_notes'      => $data['admin_notes']       ?? null,
            ]
        );
        return $this->lastInsertId();
    }

    /**
     * Return a single trip by primary key, with all JOINed fields.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            $this->baseSelect() . ' WHERE t.trip_id = :id LIMIT 1',
            [':id' => $id]
        );
    }

    /**
     * Return the trip row for a given reservation_id, or null if none exists.
     * Used by start() to guard against double-starts.
     *
     * @return array<string, mixed>|null
     */
    public function findByReservation(int $reservationId): ?array
    {
        return $this->fetchOne(
            $this->baseSelect() . ' WHERE t.reservation_id = :res_id LIMIT 1',
            [':res_id' => $reservationId]
        );
    }

    /**
     * Return all trips — super_admin only.
     * Optional status filter.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(string $status = ''): array
    {
        $sql    = $this->baseSelect();
        $params = [];

        if ($status !== '') {
            $sql   .= ' WHERE t.trip_status = :status';
            $params = [':status' => $status];
        }

        return $this->fetchAll($sql . ' ORDER BY t.created_at DESC', $params);
    }

    /**
     * Return trips scoped to the departments an admin has access to.
     * If $deptIds is empty, returns nothing — not all records.
     *
     * @param int[] $deptIds
     * @return array<int, array<string, mixed>>
     */
    public function findForAdmin(array $deptIds, string $status = ''): array
    {
        if (empty($deptIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($deptIds), '?'));
        $sql          = $this->baseSelect()
            . " WHERE d.department_id IN ($placeholders)";
        $params       = array_values($deptIds);

        if ($status !== '') {
            $sql    .= ' AND t.trip_status = ?';
            $params[] = $status;
        }

        return $this->fetchAll($sql . ' ORDER BY t.created_at DESC', $params);
    }

    /**
     * Return trips for a specific employee — reservations they requested.
     * Employees can only see trips tied to their own reservations.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findForEmployee(int $userId, string $status = ''): array
    {
        $sql    = $this->baseSelect() . ' WHERE r.requested_by = :user_id';
        $params = [':user_id' => $userId];

        if ($status !== '') {
            $sql   .= ' AND t.trip_status = :status';
            $params[':status'] = $status;
        }

        return $this->fetchAll($sql . ' ORDER BY t.created_at DESC', $params);
    }

    /**
     * Return trips for the Trip History report with optional filters.
     * Uses positional ? params built dynamically — order matters.
     *
     * @param array<string, mixed> $filters  Keys: date_from, date_to,
     *                                       trip_status, driver_id, vehicle_id
     * @param int[]                $deptIds  Non-empty = admin scoping.
     *                                       Empty = super_admin (no scope).
     * @return array<int, array<string, mixed>>
     */
    public function findForReport(array $filters = [], array $deptIds = []): array
    {
        $where  = [];
        $params = [];

        // Admin dept scoping
        if (!empty($deptIds)) {
            $placeholders = implode(',', array_fill(0, count($deptIds), '?'));
            $where[]  = "d.department_id IN ($placeholders)";
            $params   = array_merge($params, array_values($deptIds));
        }

        if (!empty($filters['date_from'])) {
            $where[]  = 'DATE(t.actual_departure) >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[]  = 'DATE(t.actual_departure) <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['trip_status'])) {
            $where[]  = 't.trip_status = ?';
            $params[] = $filters['trip_status'];
        }
        if (!empty($filters['driver_id'])) {
            $where[]  = 't.driver_id = ?';
            $params[] = (int) $filters['driver_id'];
        }
        if (!empty($filters['vehicle_id'])) {
            $where[]  = 't.vehicle_id = ?';
            $params[] = (int) $filters['vehicle_id'];
        }

        $sql = $this->baseSelect();
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        return $this->fetchAll($sql . ' ORDER BY t.created_at DESC', $params);
    }

    /**
     * Return trips assigned to a specific driver.
     * Drivers can only see their own trips.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findForDriver(int $driverId, string $status = ''): array
    {
        $sql    = $this->baseSelect() . ' WHERE t.driver_id = :driver_id';
        $params = [':driver_id' => $driverId];

        if ($status !== '') {
            $sql   .= ' AND t.trip_status = :status';
            $params[':status'] = $status;
        }

        return $this->fetchAll($sql . ' ORDER BY t.created_at DESC', $params);
    }

    /**
     * Transition a pending_start trip to in_progress.
     * Called by TripController::start().
     * Sets odometer_start_km and actual_departure = NOW().
     */
    public function updateStarted(int $id, float $odometerStart): void
    {
        $this->execute(
            'UPDATE trips
             SET    trip_status       = \'in_progress\',
                    odometer_start_km = :odo_start,
                    actual_departure  = NOW()
             WHERE  trip_id           = :id',
            [':odo_start' => $odometerStart, ':id' => $id]
        );
    }

    /**
     * Transition an in_progress trip to completed.
     * Called by TripController::complete().
     * Sets odometer_end_km and actual_return = NOW().
     */
    public function updateCompleted(int $id, float $odometerEnd): void
    {
        $this->execute(
            'UPDATE trips
             SET    trip_status     = \'completed\',
                    odometer_end_km = :odo_end,
                    actual_return   = NOW()
             WHERE  trip_id         = :id',
            [':odo_end' => $odometerEnd, ':id' => $id]
        );
    }

    /**
     * Update admin_notes or employee_notes.
     * $field must be either 'admin_notes' or 'employee_notes' —
     * validated by the caller before this is invoked.
     */
    public function updateNotes(int $id, string $field, string $notes): void
    {
        // Whitelist the column name — never interpolate raw user input
        $allowed = ['admin_notes', 'employee_notes'];
        if (!in_array($field, $allowed, true)) {
            throw new InvalidArgumentException("Invalid notes field: $field");
        }

        $this->execute(
            "UPDATE trips SET $field = :notes WHERE trip_id = :id",
            [':notes' => $notes, ':id' => $id]
        );
    }

    /**
     * Set trip_status = 'incident'.
     * Called by TripController::reportIncident() when an unresolved incident is filed.
     * Guard: only transitions from in_progress, not from completed.
     */
    public function updateToIncident(int $id): void
    {
        $this->execute(
            'UPDATE trips
             SET    trip_status = \'incident\'
             WHERE  trip_id     = :id
               AND  trip_status != \'completed\'',
            [':id' => $id]
        );
    }

    /**
     * Revert trip_status from 'incident' back to 'in_progress'.
     * Called when an incident is resolved.
     */
    public function revertFromIncident(int $id): void
    {
        $this->execute(
            'UPDATE trips
             SET    trip_status = \'in_progress\'
             WHERE  trip_id     = :id
               AND  trip_status = \'incident\'',
            [':id' => $id]
        );
    }
}
