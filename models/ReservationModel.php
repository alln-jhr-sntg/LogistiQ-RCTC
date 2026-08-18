<?php

/**
 * ReservationModel
 *
 * Wraps the `reservations` table.
 *
 * Step 7:  create()                          — transaction + code generation
 * Step 8:  countByProjectAndPurpose()        — TripLimitService check
 * Step 9:  findForEmployee(), findAll(), findById(),
 *          updateStatus(), cancel()          — list + detail + edit + cancel
 * Step 10: updateReview()                    — approve / reject
 */
class ReservationModel extends BaseModel
{
    /**
     * Insert a new reservation inside a transaction and return its ID.
     *
     * Flow (Pre-Step Decision 4 from the implementation plan):
     *   1. BEGIN transaction
     *   2. INSERT with bin2hex(random_bytes(15)) as a unique placeholder
     *      for reservation_code. 15 bytes → 30 hex chars = fits VARCHAR(30).
     *      Using random_bytes avoids UNIQUE collisions under concurrent inserts
     *      (a literal 'PENDING' would fail the UNIQUE constraint immediately).
     *   3. Read LAST_INSERT_ID() via PDO::lastInsertId()
     *   4. Generate the real code: ReservationCodeService::generate($id)
     *   5. UPDATE reservation_code with the real code
     *   6. COMMIT
     *
     * Any failure rolls back the entire transaction — no orphaned rows with
     * garbage codes will exist in the DB.
     *
     * @param  array<string, mixed> $data  Must include all NOT NULL columns
     *                                     except reservation_code and timestamps.
     * @return int  The new reservation_id.
     * @throws Throwable  On DB error; transaction is rolled back before throw.
     */
    public function create(array $data): int
    {
        $this->db->beginTransaction();

        try {
            // Step 1 — INSERT with a unique random placeholder
            $this->execute(
                'INSERT INTO reservations
                    (reservation_code,
                     requested_by, department_id, purpose_id, project_id,
                     destination, destination_lat, destination_lng,
                     trip_details, passenger_count, cargo_weight_kg,
                     cargo_description, departure_datetime, return_datetime,
                     status)
                 VALUES
                    (:reservation_code,
                     :requested_by, :department_id, :purpose_id, :project_id,
                     :destination, :destination_lat, :destination_lng,
                     :trip_details, :passenger_count, :cargo_weight_kg,
                     :cargo_description, :departure_datetime, :return_datetime,
                     \'pending\')',
                [
                    // Unique placeholder — 15 bytes → 30 hex chars (VARCHAR(30))
                    ':reservation_code'  => bin2hex(random_bytes(15)),
                    ':requested_by'      => $data['requested_by'],
                    ':department_id'     => $data['department_id'],
                    ':purpose_id'        => $data['purpose_id'],
                    ':project_id'        => $data['project_id']        ?? null,
                    ':destination'       => $data['destination'],
                    ':destination_lat'   => $data['destination_lat']   ?? null,
                    ':destination_lng'   => $data['destination_lng']   ?? null,
                    ':trip_details'      => $data['trip_details']       ?? null,
                    ':passenger_count'   => $data['passenger_count'],
                    ':cargo_weight_kg'   => $data['cargo_weight_kg']   ?? 0,
                    ':cargo_description' => $data['cargo_description'] ?? null,
                    ':departure_datetime'=> $data['departure_datetime'],
                    ':return_datetime'   => $data['return_datetime'],
                ]
            );

            // Step 2 — Retrieve the auto-increment ID for this connection
            $id = $this->lastInsertId();

            // Step 3 — Generate the human-readable code e.g. RES-2025-000001
            $code = ReservationCodeService::generate($id);

            // Step 4 — Replace the placeholder with the real code
            $this->execute(
                'UPDATE reservations
                 SET    reservation_code = :code
                 WHERE  reservation_id   = :id',
                [':code' => $code, ':id' => $id]
            );

            $this->db->commit();
            return $id;

        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Store the recommendation result on the reservation row.
     * Called once from ReservationController::review() after the scoring
     * engine runs. Guarded by AiRecommendationLogModel::hasRunFor(),
     * not by ai_recommended_vehicle_id IS NULL — see that model's docblock
     * for why the old guard re-ran the engine on every page load whenever
     * nothing survived Phase 1.
     *
     * $score is an int 0-100 (see database/migrations/2026_08_18_recommendation_score_int.sql).
     */
    public function updateAiRecommendation(
        int    $id,
        ?int   $vehicleId,
        int    $score,
        string $notes
    ): void {
        $this->execute(
            'UPDATE reservations
             SET    ai_recommended_vehicle_id = :vehicle_id,
                    ai_recommendation_score   = :score,
                    ai_recommendation_notes   = :notes
             WHERE  reservation_id            = :id',
            [
                ':vehicle_id' => $vehicleId,
                ':score'      => $score,
                ':notes'      => $notes,
                ':id'         => $id,
            ]
        );
    }

    /**
     * Clear a stale recommendation. Called from
     * ReservationController::updateReservation() when a pending
     * reservation's dates, passenger count, or cargo weight are edited —
     * any recommendation already computed (by an admin who opened the
     * review form before the edit) was scored against the old values and
     * no longer reflects the current request. The next review() visit
     * re-runs the engine from scratch.
     */
    public function clearAiRecommendation(int $id): void
    {
        $this->execute(
            'UPDATE reservations
             SET    ai_recommended_vehicle_id = NULL,
                    ai_recommendation_score   = NULL,
                    ai_recommendation_notes   = NULL
             WHERE  reservation_id            = :id',
            [':id' => $id]
        );
    }

    /**
     * Approve a reservation — set status, vehicle, driver, reviewer.
     * Driver status is NOT updated here; that happens in TripController::start()
     * per the plan (Pre-Step Decision, Step 10 note).
     *
     * Status goes to 'gatepass_pending', not 'approved' — a gatepass must
     * be created (GatepassModel::create(), called right after this from
     * ReservationController::approve()) and then reviewed by a super_admin
     * before the reservation becomes 'approved' and a trip is created.
     * See GatepassController::approve().
     */
    public function approve(int $id, array $data): void
    {
        $this->execute(
            'UPDATE reservations
             SET    status              = \'gatepass_pending\',
                    assigned_vehicle_id = :vehicle_id,
                    assigned_driver_id  = :driver_id,
                    reviewed_by         = :reviewed_by,
                    reviewed_at         = NOW()
             WHERE  reservation_id      = :id',
            [
                ':vehicle_id'  => $data['vehicle_id'],
                ':driver_id'   => $data['driver_id'],
                ':reviewed_by' => $data['reviewed_by'],
                ':id'          => $id,
            ]
        );
    }

    /**
     * Reject a reservation — set status, reason, reviewer.
     */
    public function reject(int $id, array $data): void
    {
        $this->execute(
            'UPDATE reservations
             SET    status           = \'rejected\',
                    rejection_reason = :reason,
                    reviewed_by      = :reviewed_by,
                    reviewed_at      = NOW()
             WHERE  reservation_id   = :id',
            [
                ':reason'      => $data['reason'],
                ':reviewed_by' => $data['reviewed_by'],
                ':id'          => $id,
            ]
        );
    }

    /**
     * Shared JOIN fragment used by all list and detail queries.
     * Aliased to avoid ambiguity between multiple joined user rows.
     *
     * Note: preferred_category_ids was removed from this SELECT.
     * Purpose-fit scoring now reads preferred_purpose_ids on vehicles
     * directly — VehicleRecommendationService no longer needs a
     * category preference column from the reservation's joined purpose row.
     */
    private function baseSelect(): string
    {
        return
            'SELECT r.*,
                    req.first_name        AS requester_first_name,
                    req.last_name         AS requester_last_name,
                    req.employee_id       AS requester_employee_id,
                    d.department_name,
                    c.company_id,
                    c.company_name,
                    c.company_code,
                    p.purpose_name,
                    p.requires_project,
                    p.max_per_project,
                    pr.project_name,
                    pr.project_code,
                    v.plate_number,
                    v.brand               AS vehicle_brand,
                    v.model               AS vehicle_model,
                    drv.first_name        AS driver_first_name,
                    drv.last_name         AS driver_last_name,
                    rev.first_name        AS reviewer_first_name,
                    rev.last_name         AS reviewer_last_name
             FROM   reservations r
             JOIN   users         req ON req.user_id      = r.requested_by
             JOIN   departments   d   ON d.department_id  = r.department_id
             JOIN   companies     c   ON c.company_id     = d.company_id
             JOIN   trip_purposes p   ON p.purpose_id     = r.purpose_id
             LEFT JOIN projects   pr  ON pr.project_id    = r.project_id
             LEFT JOIN vehicles   v   ON v.vehicle_id     = r.assigned_vehicle_id
             LEFT JOIN users      drv ON drv.user_id      = r.assigned_driver_id
             LEFT JOIN users      rev ON rev.user_id      = r.reviewed_by';
    }

    /**
     * Full reservation detail — all JOINs, used by detail page.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            $this->baseSelect() . ' WHERE r.reservation_id = :id LIMIT 1',
            [':id' => $id]
        );
    }

    /**
     * Reservations for a specific employee (their own requests only).
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
            $sql   .= ' AND r.status = :status';
            $params[':status'] = $status;
        }
        if ($dateFrom !== '') {
            $sql   .= ' AND r.created_at >= :date_from';
            $params[':date_from'] = $dateFrom;
        }

        $sql .= ' ORDER BY r.created_at DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(0, $limit) . ' OFFSET ' . max(0, $offset ?? 0);
        }

        return $this->fetchAll($sql, $params);
    }

    /**
     * Count reservations for a specific employee — same WHERE as
     * findForEmployee(), used to compute total pages.
     */
    public function countForEmployee(int $userId, string $status = '', string $dateFrom = ''): int
    {
        $sql    = 'SELECT COUNT(*) AS cnt FROM reservations r WHERE r.requested_by = :user_id';
        $params = [':user_id' => $userId];

        if ($status !== '') {
            $sql   .= ' AND r.status = :status';
            $params[':status'] = $status;
        }
        if ($dateFrom !== '') {
            $sql   .= ' AND r.created_at >= :date_from';
            $params[':date_from'] = $dateFrom;
        }

        $row = $this->fetchOne($sql, $params);
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * All reservations, no scoping. Used for the shared admin/fleet_admin/
     * super_admin list view — cross-company visibility is intentional
     * (transparency, per spec). Acting on a record outside your own company
     * is blocked separately, at the point of action.
     *
     * $limit/$offset — see findForEmployee() above for why these are
     * interpolated rather than bound. Default null = no LIMIT (full result set).
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
            $where[] = 'r.status = :status';
            $params[':status'] = $status;
        }
        if ($companyId > 0) {
            $where[] = 'c.company_id = :company_id';
            $params[':company_id'] = $companyId;
        }
        if ($dateFrom !== '') {
            $where[] = 'r.created_at >= :date_from';
            $params[':date_from'] = $dateFrom;
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY r.created_at DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(0, $limit) . ' OFFSET ' . max(0, $offset ?? 0);
        }

        return $this->fetchAll($sql, $params);
    }

    /**
     * Count all reservations, no scoping — same WHERE as findAll(), used to
     * compute total pages.
     */
    public function countAll(string $status = '', int $companyId = 0, string $dateFrom = ''): int
    {
        $sql    = 'SELECT COUNT(*) AS cnt
                   FROM   reservations r
                   JOIN   departments  d ON d.department_id = r.department_id
                   JOIN   companies    c ON c.company_id    = d.company_id';
        $params = [];
        $where  = [];

        if ($status !== '') {
            $where[] = 'r.status = :status';
            $params[':status'] = $status;
        }
        if ($companyId > 0) {
            $where[] = 'c.company_id = :company_id';
            $params[':company_id'] = $companyId;
        }
        if ($dateFrom !== '') {
            $where[] = 'r.created_at >= :date_from';
            $params[':date_from'] = $dateFrom;
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $row = $this->fetchOne($sql, $params);
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Reservations belonging to a single company (via department -> company),
     * optionally filtered by status. Used by the admin dashboard, which —
     * unlike the shared /reservations list — is scoped to the admin's own
     * company_id rather than showing every company's requests.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findForCompany(int $companyId, string $status = ''): array
    {
        $sql    = $this->baseSelect() . ' WHERE c.company_id = :company_id';
        $params = [':company_id' => $companyId];

        if ($status !== '') {
            $sql   .= ' AND r.status = :status';
            $params[':status'] = $status;
        }

        return $this->fetchAll($sql . ' ORDER BY r.created_at DESC', $params);
    }

    /**
     * Employee self-edit — only succeeds when the reservation is still
     * pending AND belongs to this employee. Returns rowCount:
     *   1 = updated successfully
     *   0 = status changed mid-form (race condition) or wrong owner
     * Caller must check rowCount and flash an appropriate error on 0.
     */
    public function updateByEmployee(int $id, int $userId, array $data): int
    {
        return $this->execute(
            'UPDATE reservations
             SET    purpose_id         = :purpose_id,
                    project_id         = :project_id,
                    destination        = :destination,
                    destination_lat    = :destination_lat,
                    destination_lng    = :destination_lng,
                    trip_details       = :trip_details,
                    passenger_count    = :passenger_count,
                    cargo_weight_kg    = :cargo_weight_kg,
                    cargo_description  = :cargo_description,
                    departure_datetime = :departure_datetime,
                    return_datetime    = :return_datetime
             WHERE  reservation_id     = :id
               AND  status             = \'pending\'
               AND  requested_by       = :user_id',
            [
                ':purpose_id'         => $data['purpose_id'],
                ':project_id'         => $data['project_id']         ?? null,
                ':destination'        => $data['destination'],
                ':destination_lat'    => $data['destination_lat']    ?? null,
                ':destination_lng'    => $data['destination_lng']    ?? null,
                ':trip_details'       => $data['trip_details']        ?? null,
                ':passenger_count'    => $data['passenger_count'],
                ':cargo_weight_kg'    => $data['cargo_weight_kg']    ?? 0,
                ':cargo_description'  => $data['cargo_description']  ?? null,
                ':departure_datetime' => $data['departure_datetime'],
                ':return_datetime'    => $data['return_datetime'],
                ':id'                 => $id,
                ':user_id'            => $userId,
            ]
        );
    }

    /**
     * Update the status column directly.
     * Used by TripController to transition: approved → in_progress → completed.
     * All other status transitions (approve, reject, cancel) use their own
     * dedicated methods that also set related columns (reviewed_by, etc.).
     */
    public function updateStatus(int $id, string $status): void
    {
        $this->execute(
            'UPDATE reservations SET status = :status WHERE reservation_id = :id',
            [':status' => $status, ':id' => $id]
        );
    }

    /**
     * Cancel a reservation. Admins can cancel pending or approved;
     * employees can only cancel pending (enforced in the controller,
     * not here — this method trusts the caller's decision).
     */
    public function cancel(int $id, int $cancelledBy, string $reason): void
    {
        $this->execute(
            'UPDATE reservations
             SET    status              = \'cancelled\',
                    cancelled_by        = :cancelled_by,
                    cancellation_reason = :reason
             WHERE  reservation_id      = :id',
            [
                ':cancelled_by' => $cancelledBy,
                ':reason'       => $reason,
                ':id'           => $id,
            ]
        );
    }

    /**
     * Count non-cancelled/rejected reservations for a specific
     * project + purpose combination. Used by TripLimitService to
     * enforce trip_purposes.max_per_project.
     *
     * Excludes 'rejected' and 'cancelled' — only active reservations
     * (pending, approved, in_progress, completed) count toward the limit.
     */
    public function countByProjectAndPurpose(int $projectId, int $purposeId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt
             FROM   reservations
             WHERE  project_id = :project_id
               AND  purpose_id = :purpose_id
               AND  status NOT IN (\'rejected\', \'cancelled\')',
            [
                ':project_id' => $projectId,
                ':purpose_id' => $purposeId,
            ]
        );
        return (int) ($row['cnt'] ?? 0);
    }
}
