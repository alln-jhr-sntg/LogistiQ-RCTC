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

    // POST /companies/{id}/departments/{id}/edit
    public function updateDepartment(int $companyId, int $deptId): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN);

        $name = trim($_POST['department_name'] ?? '');
        $desc = trim($_POST['description']     ?? '') ?: null;

        if ($name === '') {
            Helpers::setFlash('error', 'Department name is required.');
            Helpers::redirect('/companies/' . $companyId . '/departments');
        }

        $deptModel = new DepartmentModel();
        $old       = $deptModel->findById($deptId);

        if (!$old || (int) $old['company_id'] !== $companyId) {
            Helpers::setFlash('error', 'Department not found.');
            Helpers::redirect('/companies/' . $companyId . '/departments');
        }

        $deptModel->update($deptId, $name, $desc);

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'DEPARTMENT_UPDATED',
            'departments',
            $deptId,
            $old,
            ['department_name' => $name, 'description' => $desc]
        );

        Helpers::setFlash('success', 'Department "' . $name . '" updated.');
        Helpers::redirect('/companies/' . $companyId . '/departments');
    }

}
