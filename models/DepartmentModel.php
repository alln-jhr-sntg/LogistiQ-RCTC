<?php

/**
 * DepartmentModel
 *
 * Wraps the `departments` table.
 *
 * Used in:
 *   Step 6a  — CompanyController departments list + create
 *   Step 6g  — UserController employee dropdown
 *   Step 9   — ReservationController department scoping
 */
class DepartmentModel extends BaseModel
{
    /** Find a department by primary key. */
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM departments WHERE department_id = :id LIMIT 1',
            [':id' => $id]
        );
    }

    /**
     * Return all active departments for a given company, with a count
     * of active users assigned to each department.
     * Used by CompanyController::departments() to populate the table.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByCompany(int $companyId): array
    {
        return $this->fetchAll(
            'SELECT   d.*,
                      COUNT(u.user_id) AS user_count
             FROM     departments d
             LEFT JOIN users u
                      ON  u.department_id = d.department_id
                      AND u.is_active     = 1
             WHERE    d.company_id = :company_id
             GROUP BY d.department_id
             ORDER BY d.department_name ASC',
            [':company_id' => $companyId]
        );
    }

    /**
     * Return all active departments across all companies, ordered by name.
     * Used in dropdowns that need the full list (e.g. admin access grant).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        return $this->fetchAll(
            'SELECT * FROM departments WHERE is_active = 1 ORDER BY department_name ASC'
        );
    }

    /**
     * Return all active departments joined with their company name.
     * Useful for dropdowns where the user needs to see which company
     * each department belongs to.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAllWithCompany(): array
    {
        return $this->fetchAll(
            'SELECT   d.*,
                      c.company_name,
                      c.company_code
             FROM     departments d
             JOIN     companies   c ON c.company_id = d.company_id
             WHERE    d.is_active = 1
             ORDER BY c.company_id ASC, d.department_name ASC'
        );
    }

    /**
     * Insert a new department and return its new primary key.
     */
    public function create(int $companyId, string $name, ?string $description): int
    {
        $this->execute(
            'INSERT INTO departments (company_id, department_name, description)
             VALUES (:company_id, :name, :description)',
            [
                ':company_id'  => $companyId,
                ':name'        => $name,
                ':description' => $description,
            ]
        );
        return $this->lastInsertId();
    }
}
