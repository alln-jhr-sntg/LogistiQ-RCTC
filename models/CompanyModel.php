<?php

/**
 * CompanyModel
 *
 * Wraps the `companies` table.
 * Three companies are seeded by the schema (REMIX, IDEAL, TENBUILD)
 * and are never created or deleted through the UI — hence no create()
 * or delete() methods here.
 */
class CompanyModel extends BaseModel
{
    /**
     * Return all companies ordered by company_id.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        return $this->fetchAll(
            'SELECT * FROM companies ORDER BY company_id ASC'
        );
    }

    /**
     * Return all companies with a count of their active departments.
     * Used by CompanyController::index() to populate the company cards.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAllWithDeptCount(): array
    {
        return $this->fetchAll(
            'SELECT c.*,
                    COUNT(d.department_id) AS dept_count
             FROM   companies c
             LEFT   JOIN departments d
                    ON  d.company_id = c.company_id
                    AND d.is_active  = 1
             GROUP  BY c.company_id
             ORDER  BY c.company_id ASC'
        );
    }

    /**
     * Find a single company by primary key.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM companies WHERE company_id = :id LIMIT 1',
            [':id' => $id]
        );
    }

    /**
     * Find a single company by its company_code (e.g. 'REMIX').
     *
     * @return array<string, mixed>|null
     */
    public function findByCode(string $code): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM companies WHERE company_code = :code LIMIT 1',
            [':code' => $code]
        );
    }

    /** Count all companies. Used by the super_admin dashboard stat card. */
    public function countAll(): int
    {
        $row = $this->fetchOne('SELECT COUNT(*) AS cnt FROM companies');
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Return every company with a user count, distinct-vehicle count, and
     * trip count, in a single query (correlated subqueries, not one query
     * per company). Vehicles have no company_id of their own — vehicle
     * usage per company is derived via trips -> reservations -> departments.
     * Used by DashboardController::superAdmin() for the Company Overview table.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAllWithStats(): array
    {
        return $this->fetchAll(
            'SELECT c.*,
                    (SELECT COUNT(*)
                     FROM   users u
                     WHERE  u.company_id = c.company_id
                       AND  u.is_active  = 1)              AS user_count,
                    (SELECT COUNT(DISTINCT t.vehicle_id)
                     FROM   trips t
                     JOIN   reservations r ON r.reservation_id = t.reservation_id
                     JOIN   departments  d ON d.department_id  = r.department_id
                     WHERE  d.company_id = c.company_id)       AS vehicle_count,
                    (SELECT COUNT(*)
                     FROM   trips t
                     JOIN   reservations r ON r.reservation_id = t.reservation_id
                     JOIN   departments  d ON d.department_id  = r.department_id
                     WHERE  d.company_id = c.company_id)       AS trip_count
             FROM   companies c
             ORDER  BY c.company_id ASC'
        );
    }
}
