<?php

/**
 * AdminDepartmentAccessModel
 *
 * Wraps the `admin_department_access` table.
 * Controls which admin users can manage which departments outside
 * their own company. Super admins bypass this table entirely in
 * app logic — only admin-role users are stored here.
 */
class AdminDepartmentAccessModel extends BaseModel
{
    /**
     * Return all current access grants with full user, department,
     * company, and granter information. Used to render the grants table
     * on the access management page.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        return $this->fetchAll(
            'SELECT   ada.access_id,
                      ada.granted_at,
                      -- Admin being granted access
                      u.user_id           AS admin_user_id,
                      u.first_name        AS admin_first_name,
                      u.last_name         AS admin_last_name,
                      uc.company_name     AS admin_company,
                      uc.company_code     AS admin_company_code,
                      -- Department access is granted to
                      d.department_id,
                      d.department_name,
                      dc.company_name     AS dept_company,
                      dc.company_code     AS dept_code,
                      -- Super admin who granted access
                      g.first_name        AS granter_first_name,
                      g.last_name         AS granter_last_name
             FROM     admin_department_access ada
             JOIN     users      u  ON  u.user_id      = ada.admin_user_id
             JOIN     companies  uc ON  uc.company_id  = u.company_id
             JOIN     departments d  ON  d.department_id = ada.department_id
             JOIN     companies  dc ON  dc.company_id  = d.company_id
             JOIN     users      g  ON  g.user_id      = ada.granted_by
             ORDER BY ada.granted_at DESC'
        );
    }

    /**
     * Return all department IDs an admin currently has access to.
     * Used by department-scoping queries in Steps 9–14.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getByAdmin(int $adminId): array
    {
        return $this->fetchAll(
            'SELECT department_id FROM admin_department_access
             WHERE  admin_user_id = :admin_id',
            [':admin_id' => $adminId]
        );
    }

    /**
     * Check whether a specific grant already exists.
     * Used in grantAccess() to prevent duplicate grants before INSERT
     * so the controller can flash a meaningful error instead of
     * letting the UNIQUE constraint throw a PDOException.
     */
    public function findByAdminAndDept(int $adminId, int $deptId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM admin_department_access
             WHERE  admin_user_id = :admin_id
               AND  department_id = :dept_id
             LIMIT 1',
            [':admin_id' => $adminId, ':dept_id' => $deptId]
        );
    }

    /**
     * Insert a new access grant.
     * Caller must check findByAdminAndDept() first to avoid a
     * UNIQUE constraint error on (admin_user_id, department_id).
     */
    public function grant(int $adminId, int $deptId, int $grantedBy): void
    {
        $this->execute(
            'INSERT INTO admin_department_access
                (admin_user_id, department_id, granted_by)
             VALUES
                (:admin_id, :dept_id, :granted_by)',
            [
                ':admin_id'   => $adminId,
                ':dept_id'    => $deptId,
                ':granted_by' => $grantedBy,
            ]
        );
    }

    /**
     * Delete an access grant by its primary key (access_id).
     * The router passes the access_id as $id from the route
     * POST /companies/access/{id}/revoke.
     */
    public function revoke(int $accessId): void
    {
        $this->execute(
            'DELETE FROM admin_department_access WHERE access_id = :id',
            [':id' => $accessId]
        );
    }

    /**
     * Return all admin user_ids granted access to a specific department.
     * Used in Step 9 notification targeting: when a reservation is created,
     * notify every admin who can see that department.
     *
     * @return int[]
     */
    public function getAdminsByDepartment(int $deptId): array
    {
        $rows = $this->fetchAll(
            'SELECT admin_user_id FROM admin_department_access
             WHERE department_id = :dept_id',
            [':dept_id' => $deptId]
        );
        return array_column($rows, 'admin_user_id');
    }
}