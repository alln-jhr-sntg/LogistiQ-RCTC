<?php

/**
 * GatepassModel
 *
 * Wraps the `gatepasses` table. A gatepass row is created when a
 * reservation is approved (status = 'pending'), sitting between
 * reservation approval and trip creation:
 *
 *   reservation approved -> gatepass created (pending)
 *                            reservation.status = 'gatepass_pending'
 *   super_admin approves gatepass -> trip created (pending_start)
 *                                     reservation.status = 'approved'
 *
 * reservation_id is UNIQUE on this table — strictly one gatepass per
 * reservation. Guards in GatepassController prevent a second create()
 * for the same reservation.
 */
class GatepassModel extends BaseModel
{
    /**
     * Insert a new gatepass row for $reservationId inside a transaction
     * and return its ID. Mirrors ReservationModel::create()'s pattern:
     *
     *   1. BEGIN transaction
     *   2. INSERT with bin2hex(random_bytes(15)) as a unique placeholder
     *      for gatepass_code. 15 bytes -> 30 hex chars = fits VARCHAR(30).
     *      A literal like 'PENDING' would fail the UNIQUE constraint
     *      under concurrent inserts.
     *   3. Read LAST_INSERT_ID() via PDO::lastInsertId()
     *   4. Generate the real code: GatepassCodeService::generate($id)
     *   5. UPDATE gatepass_code with the real code
     *   6. COMMIT
     *
     * Any failure rolls back the entire transaction — no orphaned rows
     * with garbage codes will exist in the DB.
     *
     * Callable either standalone (owns its own transaction, as above) or
     * from inside a caller's already-open transaction — e.g.
     * ReservationController::approve() wraps reservation approval,
     * vehicle status, and this call in one transaction so a failure here
     * also rolls back the reservation/vehicle changes. PDO cannot nest
     * beginTransaction() calls, so this only starts/commits/rolls back
     * its own transaction when it's the outermost caller.
     *
     * @return int  The new gatepass_id.
     * @throws Throwable  On DB error; transaction is rolled back before throw.
     */
    public function create(int $reservationId): int
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }

        try {
            // Step 1 — INSERT with a unique random placeholder
            $this->execute(
                'INSERT INTO gatepasses
                    (reservation_id, gatepass_code, status)
                 VALUES
                    (:reservation_id, :gatepass_code, \'pending\')',
                [
                    ':reservation_id' => $reservationId,
                    // Unique placeholder — 15 bytes -> 30 hex chars (VARCHAR(30))
                    ':gatepass_code'  => bin2hex(random_bytes(15)),
                ]
            );

            // Step 2 — Retrieve the auto-increment ID for this connection
            $id = $this->lastInsertId();

            // Step 3 — Generate the human-readable code e.g. GP-2026-000001
            $code = GatepassCodeService::generate($id);

            // Step 4 — Replace the placeholder with the real code
            $this->execute(
                'UPDATE gatepasses
                 SET    gatepass_code = :code
                 WHERE  gatepass_id   = :id',
                [':code' => $code, ':id' => $id]
            );

            if ($ownsTransaction) {
                $this->db->commit();
            }
            return $id;

        } catch (Throwable $e) {
            if ($ownsTransaction) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Approve a gatepass — set status, reviewer, reviewed_at.
     */
    public function approve(int $id, int $reviewedBy): void
    {
        $this->execute(
            'UPDATE gatepasses
             SET    status      = \'approved\',
                    reviewed_by = :reviewed_by,
                    reviewed_at = NOW()
             WHERE  gatepass_id = :id',
            [':reviewed_by' => $reviewedBy, ':id' => $id]
        );
    }

    /**
     * Reject a gatepass — set status, reason, reviewer, reviewed_at.
     */
    public function reject(int $id, int $reviewedBy, string $reason): void
    {
        $this->execute(
            'UPDATE gatepasses
             SET    status           = \'rejected\',
                    rejection_reason = :reason,
                    reviewed_by      = :reviewed_by,
                    reviewed_at      = NOW()
             WHERE  gatepass_id      = :id',
            [
                ':reason'      => $reason,
                ':reviewed_by' => $reviewedBy,
                ':id'          => $id,
            ]
        );
    }

    /**
     * Shared JOIN fragment used by all list and detail queries.
     * gatepasses has no company_id column of its own — company scoping
     * for Auth::requireCompanyScope() is derived via reservations ->
     * departments -> companies, same as ReservationModel::baseSelect().
     *
     * Includes everything the printable gatepass document needs:
     * reservation code, destination, schedule, load, purpose, project
     * (nullable), vehicle, driver name + license number, requester,
     * and reviewer.
     */
    private function baseSelect(): string
    {
        return
            'SELECT g.*,
                    r.reservation_code,
                    r.destination,
                    r.trip_details,
                    r.passenger_count,
                    r.cargo_weight_kg,
                    r.cargo_description,
                    r.departure_datetime,
                    r.return_datetime,
                    r.status              AS reservation_status,
                    r.requested_by,
                    r.assigned_vehicle_id,
                    r.assigned_driver_id,
                    d.department_name,
                    c.company_id,
                    c.company_name,
                    c.company_code,
                    p.purpose_name,
                    pr.project_name,
                    v.plate_number,
                    v.brand                AS vehicle_brand,
                    v.model                AS vehicle_model,
                    req.first_name         AS requester_first_name,
                    req.last_name          AS requester_last_name,
                    drv.first_name         AS driver_first_name,
                    drv.last_name          AS driver_last_name,
                    dp.license_number      AS driver_license_number,
                    rev.first_name         AS reviewer_first_name,
                    rev.last_name          AS reviewer_last_name
             FROM   gatepasses    g
             JOIN   reservations  r   ON r.reservation_id  = g.reservation_id
             JOIN   users         req ON req.user_id       = r.requested_by
             JOIN   departments   d   ON d.department_id   = r.department_id
             JOIN   companies     c   ON c.company_id      = d.company_id
             JOIN   trip_purposes p   ON p.purpose_id      = r.purpose_id
             LEFT JOIN projects        pr ON pr.project_id = r.project_id
             LEFT JOIN vehicles        v  ON v.vehicle_id  = r.assigned_vehicle_id
             LEFT JOIN users           drv ON drv.user_id  = r.assigned_driver_id
             LEFT JOIN driver_profiles dp  ON dp.user_id   = r.assigned_driver_id
             LEFT JOIN users           rev ON rev.user_id  = g.reviewed_by';
    }

    /**
     * Full gatepass detail — all JOINs, used by the review and print pages.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            $this->baseSelect() . ' WHERE g.gatepass_id = :id LIMIT 1',
            [':id' => $id]
        );
    }

    /**
     * The gatepass tied to a specific reservation, or null if none exists.
     * Used by ReservationController::detail() to render the Gate Pass card.
     *
     * @return array<string, mixed>|null
     */
    public function findByReservation(int $reservationId): ?array
    {
        return $this->fetchOne(
            $this->baseSelect() . ' WHERE g.reservation_id = :res_id LIMIT 1',
            [':res_id' => $reservationId]
        );
    }

    /**
     * Pending gatepass queue for the super_admin review screen.
     * Ordered by departure time — the most urgent gatepass sits on top.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findPending(): array
    {
        return $this->fetchAll(
            $this->baseSelect()
            . " WHERE g.status = 'pending' ORDER BY r.departure_datetime ASC"
        );
    }
}
