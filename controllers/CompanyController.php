<?php

class CompanyController
{
    private function render(string $view, array $data = []): void
    {
        extract($data);
        $content_view = __DIR__ . '/../views/companies/' . $view . '.php';
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    // ── 6a — Companies ───────────────────────────────────────────

    // GET /companies
    public function index(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN);

        $companyModel = new CompanyModel();
        $companies    = $companyModel->findAllWithDeptCount();

        $this->render('index', [
            'page_title' => 'Companies',
            'companies'  => $companies,
        ]);
    }

    // ── 6a — Departments ─────────────────────────────────────────

    // GET /companies/{id}/departments
    public function departments(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN);

        $companyModel = new CompanyModel();
        $company      = $companyModel->findById($id);

        if (!$company) {
            Helpers::setFlash('error', 'Company not found.');
            Helpers::redirect('/companies');
        }

        $deptModel   = new DepartmentModel();
        $departments = $deptModel->findByCompany($id);

        $this->render('departments', [
            'page_title'  => 'Departments — ' . $company['company_name'],
            'company'     => $company,
            'departments' => $departments,
        ]);
    }

    // POST /companies/{id}/departments
    public function storeDepartment(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN);

        $name = trim($_POST['department_name'] ?? '');
        $desc = trim($_POST['description']     ?? '') ?: null;

        if ($name === '') {
            Helpers::setFlash('error', 'Department name is required.');
            Helpers::redirect('/companies/' . $id . '/departments');
        }

        $deptModel = new DepartmentModel();
        $newId     = $deptModel->create($id, $name, $desc);

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'DEPARTMENT_CREATED',
            'departments',
            $newId,
            null,
            ['company_id' => $id, 'department_name' => $name]
        );

        Helpers::setFlash('success', 'Department "' . $name . '" added.');
        Helpers::redirect('/companies/' . $id . '/departments');
    }

    // ── 6b — Admin Department Access ─────────────────────────────

    // GET /companies/access
    public function access(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN);

        $userModel   = new UserModel();
        $deptModel   = new DepartmentModel();
        $accessModel = new AdminDepartmentAccessModel();

        $admins      = $userModel->findByRole(ROLE_ADMIN);
        $departments = $deptModel->findAllWithCompany();
        $grants      = $accessModel->findAll();

        $this->render('access', [
            'page_title'  => 'Admin Access',
            'admins'      => $admins,
            'departments' => $departments,
            'grants'      => $grants,
        ]);
    }

    // POST /companies/access
    public function grantAccess(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN);

        $adminId = (int) ($_POST['admin_user_id']  ?? 0);
        $deptId  = (int) ($_POST['department_id']  ?? 0);

        if ($adminId === 0 || $deptId === 0) {
            Helpers::setFlash('error', 'Please select both an admin and a department.');
            Helpers::redirect('/companies/access');
        }

        $accessModel = new AdminDepartmentAccessModel();

        // Duplicate check — prevents UNIQUE constraint exception and
        // gives the user a meaningful error message instead.
        if ($accessModel->findByAdminAndDept($adminId, $deptId)) {
            Helpers::setFlash('error', 'That admin already has access to that department.');
            Helpers::redirect('/companies/access');
        }

        $accessModel->grant($adminId, $deptId, (int) Auth::id());

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'ACCESS_GRANTED',
            'admin_department_access',
            null,
            null,
            ['admin_user_id' => $adminId, 'department_id' => $deptId]
        );

        Helpers::setFlash('success', 'Access granted.');
        Helpers::redirect('/companies/access');
    }

    // POST /companies/access/{id}/revoke
    public function revokeAccess(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN);

        $accessModel = new AdminDepartmentAccessModel();
        $accessModel->revoke($id);

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'ACCESS_REVOKED',
            'admin_department_access',
            $id
        );

        Helpers::setFlash('success', 'Access revoked.');
        Helpers::redirect('/companies/access');
    }
}
