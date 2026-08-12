<?php

class ProjectController
{
    private function render(string $view, array $data = []): void
    {
        extract($data);
        $content_view = __DIR__ . '/../views/projects/' . $view . '.php';
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    // Companies list scoped to what the actor may create/edit projects for:
    //   super_admin  — every company
    //   fleet_admin  — REMIX only
    //   admin        — own company only
    // Used to populate the create/edit dropdown. This is a UX filter, not
    // the security boundary — requireProjectCompanyScope() is the boundary.
    private function assignableCompanies(): array
    {
        $companyModel = new CompanyModel();
        $role         = Auth::role();

        if ($role === ROLE_SUPER_ADMIN) {
            return $companyModel->findAll();
        }
        if ($role === ROLE_FLEET_ADMIN) {
            $remix = $companyModel->findByCode('REMIX');
            return $remix ? [$remix] : [];
        }

        $own = $companyModel->findById((int) Auth::companyId());
        return $own ? [$own] : [];
    }

    // Project company-scope rule (differs from the generic Auth guard):
    //   super_admin  — any company
    //   fleet_admin  — REMIX only, not every company
    //   admin        — own company only
    private function requireProjectCompanyScope(int $companyId): void
    {
        $role = Auth::role();

        if ($role === ROLE_SUPER_ADMIN) {
            return;
        }

        if ($role === ROLE_FLEET_ADMIN) {
            $remix = (new CompanyModel())->findByCode('REMIX');
            if ($remix && (int) $remix['company_id'] === $companyId) {
                return;
            }
        } elseif ($role === ROLE_ADMIN && $companyId === (int) Auth::companyId()) {
            return;
        }

        Helpers::setFlash('error', 'You are not permitted to act on projects for that company.');
        Helpers::redirect('/projects');
    }

    // GET /projects
    public function index(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $companyFilter = (int) ($_GET['company_id'] ?? 0);
        $projectModel  = new ProjectModel();
        $projects      = $projectModel->findAll($companyFilter);

        $companyModel  = new CompanyModel();
        $companies     = $companyModel->findAll();

        $this->render('index', [
            'page_title'    => 'Projects',
            'projects'      => $projects,
            'companies'     => $companies,
            'companyFilter' => $companyFilter,
        ]);
    }

    // GET /projects/create
    public function create(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $settingModel = new SystemSettingModel();

        $this->render('create_edit', [
            'page_title'    => 'New Project',
            'project'       => null,
            'companies'     => $this->assignableCompanies(),
            'warehouse_lat' => (float) ($settingModel->getByKey('warehouse_lat') ?? '0'),
            'warehouse_lng' => (float) ($settingModel->getByKey('warehouse_lng') ?? '0'),
        ]);
    }

    // POST /projects/create
    public function store(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $name      = trim($_POST['project_name'] ?? '');
        $code      = trim($_POST['project_code']  ?? '') ?: null;
        $companyId = (int) ($_POST['company_id']  ?? 0);
        $location  = trim($_POST['location']      ?? '') ?: null;
        $lat       = $_POST['location_lat'] !== '' ? (float) $_POST['location_lat'] : null;
        $lng       = $_POST['location_lng'] !== '' ? (float) $_POST['location_lng'] : null;
        $startDate = trim($_POST['start_date'] ?? '') ?: null;
        $endDate   = trim($_POST['end_date']   ?? '') ?: null;
        $desc      = trim($_POST['description'] ?? '') ?: null;

        // An admin creating a project directly already holds the authority a
        // review would confer, so the default is 'active'. Only the statuses
        // an admin may set by hand are accepted — pending and rejected belong
        // to the request/review flow.
        $posted = $_POST['status'] ?? '';
        $status = in_array($posted, PROJ_EDITABLE_STATUSES, true) ? $posted : PROJ_ACTIVE;

        if ($name === '' || $companyId === 0) {
            Helpers::setFlash('error', 'Project name and company are required.');
            Helpers::redirect('/projects/create');
        }

        $this->requireProjectCompanyScope($companyId);

        $projectModel = new ProjectModel();
        $newId        = $projectModel->create([
            'company_id'   => $companyId,
            'created_by'   => (int) Auth::id(),
            'project_name' => $name,
            'project_code' => $code,
            'location'     => $location,
            'location_lat' => $lat,
            'location_lng' => $lng,
            'start_date'   => $startDate,
            'end_date'     => $endDate,
            'description'  => $desc,
            'status'       => $status,
        ]);

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'PROJECT_CREATED',
            'projects',
            $newId,
            null,
            ['project_name' => $name, 'company_id' => $companyId]
        );

        Helpers::setFlash('success', 'Project "' . $name . '" created.');
        Helpers::redirect('/projects');
    }

    // GET /projects/{id}/edit
    public function edit(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $projectModel = new ProjectModel();
        $project      = $projectModel->findById($id);

        if (!$project) {
            Helpers::setFlash('error', 'Project not found.');
            Helpers::redirect('/projects');
        }

        $this->requireProjectCompanyScope((int) $project['company_id']);

        $settingModel = new SystemSettingModel();

        $this->render('create_edit', [
            'page_title'    => 'Edit Project — ' . $project['project_name'],
            'project'       => $project,
            'companies'     => $this->assignableCompanies(),
            'warehouse_lat' => (float) ($settingModel->getByKey('warehouse_lat') ?? '0'),
            'warehouse_lng' => (float) ($settingModel->getByKey('warehouse_lng') ?? '0'),
        ]);
    }

    // POST /projects/{id}/edit
    public function update(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $name      = trim($_POST['project_name'] ?? '');
        $code      = trim($_POST['project_code']  ?? '') ?: null;
        $companyId = (int) ($_POST['company_id']  ?? 0);
        $location  = trim($_POST['location']      ?? '') ?: null;
        $lat       = $_POST['location_lat'] !== '' ? (float) $_POST['location_lat'] : null;
        $lng       = $_POST['location_lng'] !== '' ? (float) $_POST['location_lng'] : null;
        $startDate = trim($_POST['start_date'] ?? '') ?: null;
        $endDate   = trim($_POST['end_date']   ?? '') ?: null;
        $desc      = trim($_POST['description'] ?? '') ?: null;

        if ($name === '' || $companyId === 0) {
            Helpers::setFlash('error', 'Project name and company are required.');
            Helpers::redirect('/projects/' . $id . '/edit');
        }

        $projectModel = new ProjectModel();
        $old          = $projectModel->findById($id);

        if (!$old) {
            Helpers::setFlash('error', 'Project not found.');
            Helpers::redirect('/projects');
        }

        // Actor must be permitted to act on the project's current company
        // AND on the company it's being re-parented to (if different).
        $this->requireProjectCompanyScope((int) $old['company_id']);
        $this->requireProjectCompanyScope($companyId);

        // A pending or rejected project cannot change status here — for ANY
        // role, super_admin included. approve() and reject() are the only
        // exits from pending, and rejected is terminal. The posted value is
        // discarded rather than validated, so a hand-crafted POST that skips
        // the disabled select still cannot activate a request unreviewed.
        $posted = $_POST['status'] ?? '';
        if (in_array($old['status'], PROJ_LOCKED_STATUSES, true)) {
            $status = $old['status'];
        } else {
            $status = in_array($posted, PROJ_EDITABLE_STATUSES, true) ? $posted : $old['status'];
        }

        $projectModel->update($id, [
            'project_name' => $name,
            'project_code' => $code,
            'company_id'   => $companyId,
            'location'     => $location,
            'location_lat' => $lat,
            'location_lng' => $lng,
            'start_date'   => $startDate,
            'end_date'     => $endDate,
            'description'  => $desc,
            'status'       => $status,
        ]);

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'PROJECT_UPDATED',
            'projects',
            $id,
            ['project_name' => $old['project_name'], 'status' => $old['status']],
            ['project_name' => $name, 'status' => $status]
        );

        Helpers::setFlash('success', 'Project "' . $name . '" updated.');
        Helpers::redirect('/projects');
    }
}
