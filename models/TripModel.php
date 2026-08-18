<?php

/**
 * TripModel
 *
 * Wraps the `trips` table. A trip row is created once a reservation's
 * gatepass has been approved (trip_status = 'pending_start'), then
 * transitioned to 'in_progress' when started and 'completed' when
 * finished. No trip may exist before its gatepass is approved.
 *
 * Status transitions (who drives them):
 *   (none)        → pending_start GatepassController::approve() — the only create() call site
 *   pending_start → in_progress   TripController::start() / TripApiController
 *   in_progress   → completed     TripController::complete() / TripApiController
 *   in_progress   → incident      TripController::reportIncident()
 *   incident      → in_progress   TripController::resolveIncident()
 *   incident      → cancelled     TripController::cancelTrip() — terminal, no further transitions
 *
 * Used in:
 *   TripController (start, complete, notes, incident, index, detail)
 *   TripApiController (Android driver API)
 *   GatepassController (create, the only place a trip is inserted)
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
                    c.company_id,
                    c.company_name,
                    c.company_code,
                    gp.gatepass_code,
                    gp.status          AS gatepass_status,
                    gp.reviewed_at     AS gatepass_approved_at,
                    CONCAT(gpr.first_name, \' \', gpr.last_name) AS gatepass_approver
             FROM   trips        t
             JOIN   reservations r   ON r.reservation_id  = t.reservation_id
             JOIN   vehicles     v   ON v.vehicle_id       = t.vehicle_id
             JOIN   users        drv ON drv.user_id        = t.driver_id
             JOIN   users        req ON req.user_id        = r.requested_by
             JOIN   departments  d   ON d.department_id    = r.department_id
             JOIN   companies    c   ON c.company_id       = d.company_id
             JOIN   trip_purposes p  ON p.purpose_id       = r.purpose_id
             LEFT JOIN gatepasses gp  ON gp.reservation_id  = t.reservation_id
             LEFT JOIN users      gpr ON gpr.user_id        = gp.reviewed_by';
    }

    /**
     * Insert a new trip row. Returns the new trip_id.
     *
     * Called from exactly ONE place: GatepassController::approve(), with
     * trip_status = 'pending_start', once a super_admin has approved the
     * reservation's gatepass. TripController::start() does NOT create a
     * trip if one is missing — it looks one up via findByReservation()
     * and bails with a flash error, since a missing trip means the
     * gatepass step hasn't happened yet.
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
     * $limit/$offset are cast to int and interpolated directly rather than
     * bound — PDO::ATTR_EMULATE_PREPARES is off (config/database.php), and
     * MySQL's native prepared-statement protocol does not reliably accept a
     * bound param in LIMIT/OFFSET position. Safe here since both are
     * hard-cast to int right before use, never raw user input. Default null
     * = no LIMIT (full result set).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(
        string $status = '',
        ?int $limit = null,
        ?int $offset = null,
        int $companyId = 0,
        string $dateFrom = ''
    ): array {
        $sql    = $this->baseSelect();
        $params = [];
        $where  = [];

        if ($status !== '') {
            $where[] = 't.trip_status = :status';
            $params[':status'] = $status;
        }
        if ($companyId > 0) {
            $where[] = 'c.company_id = :company_id';
            $params[':company_id'] = $companyId;
        }
        if ($dateFrom !== '') {
            $where[] = 't.created_at >= :date_from';
            $params[':date_from'] = $dateFrom;
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY t.created_at DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(0, $limit) . ' OFFSET ' . max(0, $offset ?? 0);
        }

        return $this->fetchAll($sql, $params);
    }

    /**
     * Count all trips — same WHERE as findAll(), used to compute total pages.
     */
    public function countAll(string $status = '', int $companyId = 0, string $dateFrom = ''): int
    {
        $sql    = 'SELECT COUNT(*) AS cnt
                   FROM   trips        t
                   JOIN   reservations r ON r.reservation_id  = t.reservation_id
                   JOIN   departments  d ON d.department_id   = r.department_id
                   JOIN   companies    c ON c.company_id      = d.company_id';
        $params = [];
        $where  = [];

        if ($status !== '') {
            $where[] = 't.trip_status = :status';
            $params[':status'] = $status;
        }
        if ($companyId > 0) {
            $where[] = 'c.company_id = :company_id';
            $params[':company_id'] = $companyId;
        }
        if ($dateFrom !== '') {
            $where[] = 't.created_at >= :date_from';
            $params[':date_from'] = $dateFrom;
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $row = $this->fetchOne($sql, $params);
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Return trips for a specific employee — reservations they requested.
     * Employees can only see trips tied to their own reservations.
     *
     * $limit/$offset — see findAll() above for why these are interpolated
     * rather than bound. Default null = no LIMIT (full result set).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findForEmployee(
        int $userId,
        string $status = '',
        ?int $limit = null,
        ?int $offset = null,
        string $dateFrom = ''
    ): array {
        $sql    = $this->baseSelect() . ' WHERE r.requested_by = :user_id';
        $params = [':user_id' => $userId];

        if ($status !== '') {
            $sql   .= ' AND t.trip_status = :status';
            $params[':status'] = $status;
        }
        if ($dateFrom !== '') {
            $sql   .= ' AND t.created_at >= :date_from';
            $params[':date_from'] = $dateFrom;
        }

        $sql .= ' ORDER BY t.created_at DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(0, $limit) . ' OFFSET ' . max(0, $offset ?? 0);
        }

        return $this->fetchAll($sql, $params);
    }

    /**
     * Count trips for a specific employee — same WHERE as findForEmployee(),
     * used to compute total pages. Requires the reservations join since
     * requested_by lives on reservations, not trips.
     */
    public function countForEmployee(int $userId, string $status = '', string $dateFrom = ''): int
    {
        $sql = 'SELECT COUNT(*) AS cnt
                FROM   trips t
                JOIN   reservations r ON r.reservation_id = t.reservation_id
                WHERE  r.requested_by = :user_id';
        $params = [':user_id' => $userId];

        if ($status !== '') {
            $sql   .= ' AND t.trip_status = :status';
            $params[':status'] = $status;
        }
        if ($dateFrom !== '') {
            $sql   .= ' AND t.created_at >= :date_from';
            $params[':date_from'] = $dateFrom;
        }

        $row = $this->fetchOne($sql, $params);
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Shared WHERE-clause builder for the Trip History report — used by both
     * findForReport() and countForReport() so the two never drift apart.
     * Uses positional ? params built dynamically — order matters.
     *
     * @param array<string, mixed> $filters    Keys: date_from, date_to,
     *                                         trip_status, driver_id, vehicle_id
     * @param int[]|null            $companyIds Assumed already non-empty —
     *                                          callers check the "empty array
     *                                          fails closed" case themselves,
     *                                          since they return different
     *                                          "nothing" values (0 vs []).
     * @return array{0: string[], 1: array<int,mixed>}
     */
    private function reportConditions(array $filters, ?array $companyIds): array
    {
        $where  = [];
        $params = [];

        if ($companyIds !== null) {
            $placeholders = implode(',', array_fill(0, count($companyIds), '?'));
            $where[]  = "c.company_id IN ($placeholders)";
            $params   = array_values($companyIds);
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

        return [$where, $params];
    }

    /**
     * Return trips for the Trip History report with optional filters.
     * $limit/$offset are cast to int and interpolated directly (not bound) —
     * PDO::ATTR_EMULATE_PREPARES is off (config/database.php), and MySQL's
     * native prepared-statement protocol does not reliably accept a bound
     * param in LIMIT/OFFSET position. Safe here since both are hard-cast to
     * int right before use, never raw user input.
     *
     * @param array<string, mixed> $filters     Keys: date_from, date_to,
     *                                          trip_status, driver_id, vehicle_id
     * @param int[]|null            $companyIds null = no company scoping (every
     *                                          company's trips). An empty array
     *                                          fails closed and returns nothing
     *                                          — it never silently means "all".
     * @param int|null              $limit      null = no LIMIT (full result set —
     *                                          used by CSV export).
     * @return array<int, array<string, mixed>>
     */
    public function findForReport(array $filters = [], ?array $companyIds = null, ?int $limit = null, ?int $offset = null): array
    {
        if ($companyIds !== null && empty($companyIds)) {
            return [];
        }

        [$where, $params] = $this->reportConditions($filters, $companyIds);

        $sql = $this->baseSelect();
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY t.created_at DESC';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(0, $limit) . ' OFFSET ' . max(0, $offset ?? 0);
        }

        return $this->fetchAll($sql, $params);
    }

    /**
     * Count trips matching the Trip History report's filters — same WHERE
     * as findForReport(), used to compute total pages without pulling the
     * full joined result set.
     *
     * @param array<string, mixed> $filters
     * @param int[]|null            $companyIds
     */
    public function countForReport(array $filters = [], ?array $companyIds = null): int
    {
        if ($companyIds !== null && empty($companyIds)) {
            return 0;
        }

        [$where, $params] = $this->reportConditions($filters, $companyIds);

        // Filters only ever reference t.* or (when company-scoped) c.company_id,
        // so counting needs just the join path to companies — not the full
        // baseSelect() column list.
        $sql = 'SELECT COUNT(*) AS cnt FROM trips t';
        if ($companyIds !== null) {
            $sql .= ' JOIN reservations r ON r.reservation_id = t.reservation_id
                      JOIN departments  d ON d.department_id  = r.department_id
                      JOIN companies    c ON c.company_id     = d.company_id';
        }
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $row = $this->fetchOne($sql, $params);
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Return trips assigned to a specific driver.
     * Drivers can only see their own trips.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findForDriver(int $driverId, string $status = '', ?int $limit = null, ?int $offset = null): array
    {
        $sql    = $this->baseSelect() . ' WHERE t.driver_id = :driver_id';
        $params = [':driver_id' => $driverId];

        if ($status !== '') {
            $sql   .= ' AND t.trip_status = :status';
            $params[':status'] = $status;
        }

        $sql .= ' ORDER BY t.created_at DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(0, $limit) . ' OFFSET ' . max(0, $offset ?? 0);
        }

        return $this->fetchAll($sql, $params);
    }

    /**
     * Count trips for a specific driver — same WHERE as findForDriver(),
     * used to compute total pages.
     */
    public function countForDriver(int $driverId, string $status = ''): int
    {
        $sql    = 'SELECT COUNT(*) AS cnt FROM trips t WHERE t.driver_id = :driver_id';
        $params = [':driver_id' => $driverId];

        if ($status !== '') {
            $sql   .= ' AND t.trip_status = :status';
            $params[':status'] = $status;
        }

        $row = $this->fetchOne($sql, $params);
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Count trips whose trip_status is in the given list.
     * Used for dashboard stat cards (e.g. "Active Trips" = in_progress + incident)
     * where only a count is needed — avoids the full baseSelect() joins.
     *
     * @param string[] $statuses
     */
    public function countByStatuses(array $statuses): int
    {
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $row = $this->fetchOne(
            "SELECT COUNT(*) AS cnt FROM trips WHERE trip_status IN ($placeholders)",
            $statuses
        );
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Return all trips whose trip_status is in the given list, fully joined.
     * Used by the fleet_admin dashboard's Active Trips table.
     *
     * @param string[] $statuses
     * @return array<int, array<string, mixed>>
     */
    public function findByStatuses(array $statuses): array
    {
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        return $this->fetchAll(
            $this->baseSelect() . " WHERE t.trip_status IN ($placeholders) ORDER BY t.created_at DESC",
            $statuses
        );
    }

    /**
     * Count trips belonging to a specific employee's own reservations,
     * restricted to the given trip_status list. Used for the employee
     * dashboard's Active Trips / Completed Trips stat cards — a plain
     * COUNT, not the full baseSelect() joins.
     *
     * @param string[] $statuses
     */
    public function countForEmployeeByStatuses(int $userId, array $statuses): int
    {
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $row = $this->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM   trips t
             JOIN   reservations r ON r.reservation_id = t.reservation_id
             WHERE  r.requested_by = ?
               AND  t.trip_status IN ($placeholders)",
            array_merge([$userId], $statuses)
        );
        return (int) ($row['cnt'] ?? 0);
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
     * Guard: only transitions from 'in_progress' or 'incident' itself (a
     * second incident on an already-incident trip is a harmless no-op).
     * A pending_start trip must never pass through here — it has no
     * odometer_start_km / actual_departure yet, and resolveIncident()
     * would later revert it straight to 'in_progress' without either ever
     * having been set.
     */
    public function updateToIncident(int $id): void
    {
        $this->execute(
            "UPDATE trips
             SET    trip_status = 'incident'
             WHERE  trip_id     = :id
               AND  trip_status IN ('in_progress', 'incident')",
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

    /**
     * Transition a trip to 'cancelled'. Terminal — a cancelled trip is
     * never reopened. Called by TripController::cancelTrip() when a trip
     * cannot continue (e.g. an unresolved incident makes the trip unsafe
     * or impossible to finish).
     * Sets cancellation_reason, cancelled_by, and actual_return = NOW()
     * (no odometer_end_km — no closing reading is taken on a cancel, so
     * the vehicle's odometer is left untouched and the maintenance-due
     * check is skipped).
     * Guard: only transitions from a non-terminal status.
     */
    public function cancel(int $id, string $reason, int $cancelledBy): void
    {
        // TRIP_TERMINAL_STATUSES is a fixed internal constant, never user
        // input — safe to interpolate into the IN (...) list below.
        $terminal = "'" . implode("','", TRIP_TERMINAL_STATUSES) . "'";

        $this->execute(
            "UPDATE trips
             SET    trip_status         = 'cancelled',
                    cancellation_reason = :reason,
                    cancelled_by        = :cancelled_by,
                    actual_return       = NOW()
             WHERE  trip_id             = :id
               AND  trip_status NOT IN ($terminal)",
            [
                ':reason'       => $reason,
                ':cancelled_by' => $cancelledBy,
                ':id'           => $id,
            ]
        );
    }
}
