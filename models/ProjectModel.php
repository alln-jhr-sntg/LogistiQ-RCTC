<?php

/**
 * ProjectModel
 *
 * Wraps the `projects` table.
 *
 * Used in:
 *   ProjectController CRUD
 *   TripLimitService project-based trip counting
 *   ReservationController project dropdown
 */
class ProjectModel extends BaseModel
{
    /**
     * Return all projects joined with company name.
     * Optional company_id and status filters for the project list page.
     *
     * Pending sorts first so the list is actionable for reviewers — the
     * rows that need a decision are the ones at the top.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(int $companyId = 0, string $status = ''): array
    {
        $sql    = 'SELECT   p.*, c.company_name, c.company_code
                   FROM     projects p
                   JOIN     companies c ON c.company_id = p.company_id
                   WHERE    1 = 1';
        $params = [];

        if ($companyId > 0) {
            $sql .= ' AND p.company_id = :company_id';
            $params[':company_id'] = $companyId;
        }
        if ($status !== '') {
            $sql .= ' AND p.status = :status';
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY (p.status = 'pending') DESC, p.created_at DESC";

        return $this->fetchAll($sql, $params);
    }

    /**
     * Return a single project with company info.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT   p.*, c.company_name, c.company_code
             FROM     projects p
             JOIN     companies c ON c.company_id = p.company_id
             WHERE    p.project_id = :id
             LIMIT    1',
            [':id' => $id]
        );
    }

    /**
     * Return all ACTIVE projects for a given company.
     * Used by ReservationController to populate the project dropdown
     * when a purpose requires_project = 1.
     *
     * Only status = 'active' is offered — a project awaiting review, one
     * that was rejected, or one that has finished must never be bookable.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findActiveByCompany(int $companyId): array
    {
        return $this->fetchAll(
            "SELECT * FROM projects
             WHERE  company_id = :company_id AND status = 'active'
             ORDER  BY project_name ASC",
            [':company_id' => $companyId]
        );
    }

    /**
     * Return all ACTIVE projects across every company, ordered by name.
     * Used by ReservationController::create() for accounts with no home
     * department (super_admin, fleet_admin) — they pick a company first,
     * and the project dropdown is filtered to match client-side.
     *
     * Same status = 'active' rule as findActiveByCompany(): both feed the
     * same reservation dropdown and must agree on what is bookable.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findActiveWithCompany(): array
    {
        return $this->fetchAll(
            "SELECT * FROM projects
             WHERE  status = 'active'
             ORDER  BY project_name ASC"
        );
    }

    /**
     * Projects visible to an employee on the read-only project list.
     *
     * Company projects that are active or completed, PLUS the employee's
     * OWN pending and rejected requests. A colleague's pending or rejected
     * row is excluded by the WHERE clause, so one employee never sees
     * another's rejection reason.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findForEmployee(int $companyId, int $userId): array
    {
        return $this->fetchAll(
            "SELECT   p.*, c.company_name, c.company_code
             FROM     projects p
             JOIN     companies c ON c.company_id = p.company_id
             WHERE    p.company_id = :company_id
               AND    (
                          p.status IN ('active', 'completed')
                       OR (p.status IN ('pending', 'rejected')
                           AND p.requested_by = :user_id)
                      )
             ORDER BY (p.status = 'pending') DESC, p.created_at DESC",
            [':company_id' => $companyId, ':user_id' => $userId]
        );
    }

    /**
     * The most recent projects for a single company.
     * Used by the admin dashboard's "Recent Project Requests" table.
     * Pending sorts first so the card is actionable — an admin should see
     * what needs reviewing, not merely what was created last.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findRecentByCompany(int $companyId, int $limit = 5): array
    {
        return $this->fetchAll(
            "SELECT   p.*, c.company_name, c.company_code
             FROM     projects p
             JOIN     companies c ON c.company_id = p.company_id
             WHERE    p.company_id = :company_id
             ORDER BY (p.status = 'pending') DESC, p.created_at DESC
             LIMIT    :limit",
            [':company_id' => $companyId, ':limit' => $limit]
        );
    }

    /**
     * Count projects awaiting review for one company.
     * Feeds the admin dashboard's "Pending Projects" stat card.
     */
    public function countPendingByCompany(int $companyId): int
    {
        $row = $this->fetchOne(
            "SELECT COUNT(*) AS cnt FROM projects
             WHERE  company_id = :company_id AND status = 'pending'",
            [':company_id' => $companyId]
        );
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Suggest the next sequential project_code for one company in
     * {company_code}-{year}-NNN form, one past the highest numeric suffix
     * already used by that company this year. project_code is free text,
     * so codes that don't match the shape (or belong to a different
     * company/year) are ignored rather than breaking the sequence.
     * Create-form prefill only — the field stays editable and this format
     * is never enforced on save.
     */
    public function nextProjectCodeSuggestion(string $companyCode, string $year): string
    {
        $prefix = $companyCode . '-' . $year . '-';
        $rows   = $this->fetchAll(
            'SELECT project_code FROM projects WHERE project_code LIKE :prefix',
            [':prefix' => $prefix . '%']
        );

        $maxSeq = 0;
        foreach ($rows as $row) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $row['project_code'], $m)) {
                $maxSeq = max($maxSeq, (int) $m[1]);
            }
        }

        return $prefix . str_pad((string) ($maxSeq + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Insert a new project. Returns the new project_id.
     *
     * Status defaults to 'pending' to match the column default: an
     * employee request arrives unreviewed. ProjectController::store()
     * passes 'active' explicitly, since an admin creating a project
     * directly already holds the authority a review would confer.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO projects
                (company_id, created_by, requested_by, project_name, project_code,
                 location, location_lat, location_lng,
                 start_date, end_date, description, status)
             VALUES
                (:company_id, :created_by, :requested_by, :project_name, :project_code,
                 :location, :location_lat, :location_lng,
                 :start_date, :end_date, :description, :status)',
            [
                ':company_id'   => $data['company_id'],
                ':created_by'   => $data['created_by'],
                ':requested_by' => $data['requested_by'] ?? null,
                ':project_name' => $data['project_name'],
                ':project_code' => $data['project_code']  ?? null,
                ':location'     => $data['location']      ?? null,
                ':location_lat' => $data['location_lat']  ?? null,
                ':location_lng' => $data['location_lng']  ?? null,
                ':start_date'   => $data['start_date']    ?? null,
                ':end_date'     => $data['end_date']      ?? null,
                ':description'  => $data['description']   ?? null,
                ':status'       => $data['status']        ?? PROJ_PENDING,
            ]
        );
        return $this->lastInsertId();
    }

    /**
     * Update an existing project.
     *
     * The caller decides what 'status' may become — see
     * ProjectController::update(), which discards the posted status
     * outright on a pending or rejected row.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $this->execute(
            'UPDATE projects
             SET    project_name = :project_name,
                    project_code = :project_code,
                    company_id   = :company_id,
                    location     = :location,
                    location_lat = :location_lat,
                    location_lng = :location_lng,
                    start_date   = :start_date,
                    end_date     = :end_date,
                    description  = :description,
                    status       = :status
             WHERE  project_id   = :id',
            [
                ':project_name' => $data['project_name'],
                ':project_code' => $data['project_code']  ?? null,
                ':company_id'   => $data['company_id'],
                ':location'     => $data['location']      ?? null,
                ':location_lat' => $data['location_lat']  ?? null,
                ':location_lng' => $data['location_lng']  ?? null,
                ':start_date'   => $data['start_date']    ?? null,
                ':end_date'     => $data['end_date']      ?? null,
                ':description'  => $data['description']   ?? null,
                ':status'       => $data['status'],
                ':id'           => $id,
            ]
        );
    }

    /**
     * Approve a pending project request.
     *
     * The AND status = 'pending' guard makes a double submit a no-op
     * rather than re-stamping reviewed_at on an already-live project.
     * rejection_reason is cleared so a previously rejected row can never
     * carry a stale reason forward.
     */
    public function approve(int $id, int $reviewerId): void
    {
        $this->execute(
            "UPDATE projects
             SET    status           = 'active',
                    reviewed_by      = :reviewer,
                    reviewed_at      = NOW(),
                    rejection_reason = NULL
             WHERE  project_id       = :id
               AND  status           = 'pending'",
            [':reviewer' => $reviewerId, ':id' => $id]
        );
    }

    /**
     * Reject a pending project request with a reason.
     *
     * Rejection is terminal — there is no path back to pending. The
     * requester files a new request instead, which is why the reason is
     * surfaced to them in the notification.
     */
    public function reject(int $id, int $reviewerId, string $reason): void
    {
        $this->execute(
            "UPDATE projects
             SET    status           = 'rejected',
                    reviewed_by      = :reviewer,
                    reviewed_at      = NOW(),
                    rejection_reason = :reason
             WHERE  project_id       = :id
               AND  status           = 'pending'",
            [':reviewer' => $reviewerId, ':reason' => $reason, ':id' => $id]
        );
    }
}
