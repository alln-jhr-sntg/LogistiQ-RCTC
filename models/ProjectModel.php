<?php

/**
 * ProjectModel
 *
 * Wraps the `projects` table.
 *
 * Used in:
 *   Step 6e  — ProjectController CRUD
 *   Step 8   — TripLimitService project-based trip counting
 *   Step 9   — ReservationController project dropdown
 */
class ProjectModel extends BaseModel
{
    /**
     * Return all projects joined with company name.
     * Optional company_id filter for the project list page.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(int $companyId = 0): array
    {
        $sql    = 'SELECT   p.*, c.company_name, c.company_code
                   FROM     projects p
                   JOIN     companies c ON c.company_id = p.company_id';
        $params = [];

        if ($companyId > 0) {
            $sql   .= ' WHERE p.company_id = :company_id';
            $params = [':company_id' => $companyId];
        }

        $sql .= ' ORDER BY p.is_active DESC, p.created_at DESC';

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
     * Return all active projects for a given company.
     * Used by ReservationController to populate the project dropdown
     * when a purpose requires_project = 1.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findActiveByCompany(int $companyId): array
    {
        return $this->fetchAll(
            'SELECT * FROM projects
             WHERE  company_id = :company_id AND is_active = 1
             ORDER  BY project_name ASC',
            [':company_id' => $companyId]
        );
    }

    /**
     * Insert a new project. Returns the new project_id.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO projects
                (company_id, created_by, project_name, project_code,
                 location, location_lat, location_lng,
                 start_date, end_date, is_active)
             VALUES
                (:company_id, :created_by, :project_name, :project_code,
                 :location, :location_lat, :location_lng,
                 :start_date, :end_date, :is_active)',
            [
                ':company_id'   => $data['company_id'],
                ':created_by'   => $data['created_by'],
                ':project_name' => $data['project_name'],
                ':project_code' => $data['project_code']  ?? null,
                ':location'     => $data['location']      ?? null,
                ':location_lat' => $data['location_lat']  ?? null,
                ':location_lng' => $data['location_lng']  ?? null,
                ':start_date'   => $data['start_date']    ?? null,
                ':end_date'     => $data['end_date']      ?? null,
                ':is_active'    => $data['is_active']     ?? 1,
            ]
        );
        return $this->lastInsertId();
    }

    /**
     * Update an existing project.
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
                    is_active    = :is_active
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
                ':is_active'    => $data['is_active'],
                ':id'           => $id,
            ]
        );
    }
}
