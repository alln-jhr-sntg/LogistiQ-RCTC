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
}
