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

    // Next project_code suggestion for each of the given companies, keyed
    // by company_id. Feeds the create/edit form's prefill and the client-
    // side recompute when the company dropdown changes.
    //
    // @param array<int, array<string, mixed>> $companies
    // @return array<int, string>
    private function projectCodeSuggestions(array $companies): array
    {
        $projectModel = new ProjectModel();
        $year         = date('Y');
        $suggestions  = [];

        foreach ($companies as $co) {
            $suggestions[(int) $co['company_id']] = $projectModel->nextProjectCodeSuggestion(
                (string) $co['company_code'],
                $year
            );
        }

        return $suggestions;
    }

    // Project company-scope rule (differs from the generic Auth guard):
    //   super_admin  — any company
    //   fleet_admin  — REMIX only, not every company
    //   admin        — own company only
    //
    // Also decides who may REVIEW a project request, since reviewing is
    // just another act on a company's projects. Returns a plain bool so
    // views can hide the Review action without a redirect; the guard
    // below is what actually enforces it.
    private function canActOnProjectCompany(int $companyId): bool
    {
        $role = Auth::role();

        if ($role === ROLE_SUPER_ADMIN) {
            return true;
        }

        if ($role === ROLE_FLEET_ADMIN) {
            // Resolve REMIX by company_code — never by a literal id.
            $remix = (new CompanyModel())->findByCode('REMIX');
            return $remix && (int) $remix['company_id'] === $companyId;
        }

        return $role === ROLE_ADMIN && $companyId === (int) Auth::companyId();
    }

    // Guard form of canActOnProjectCompany(). Every project write — create,
    // edit, approve, reject — passes through here after the record loads.
    private function requireProjectCompanyScope(int $companyId): void
    {
        if ($this->canActOnProjectCompany($companyId)) {
            return;
        }

        Helpers::setFlash('error', 'You are not permitted to act on projects for that company.');
        Helpers::redirect('/projects');
    }

    // Company ids the current actor may review project requests for.
    // Used by index() to decide which rows show a Review action.
    private function reviewableCompanyIds(): array
    {
        $ids = [];
        foreach ((new CompanyModel())->findAll() as $co) {
            if ($this->canActOnProjectCompany((int) $co['company_id'])) {
                $ids[] = (int) $co['company_id'];
            }
        }
        return $ids;
    }

    /**
     * User ids who may review a project request for $companyId — the same
     * rule as canActOnProjectCompany(), inverted to find people instead of
     * asking about one.
     *
     * @return int[]
     */
    private function reviewersForCompany(int $companyId): array
    {
        $userModel  = new UserModel();
        $recipients = array_column($userModel->findByRole(ROLE_SUPER_ADMIN), 'user_id');

        // fleet_admin reviews REMIX only, so only notify them for REMIX.
        $remix = (new CompanyModel())->findByCode('REMIX');
        if ($remix && (int) $remix['company_id'] === $companyId) {
            $recipients = array_merge(
                $recipients,
                array_column($userModel->findByRole(ROLE_FLEET_ADMIN), 'user_id')
            );
        }

        // Admins of the project's own company.
        $recipients = array_merge(
            $recipients,
            array_column($userModel->findAll(ROLE_ADMIN, $companyId), 'user_id')
        );

        return array_values(array_unique(array_map('intval', $recipients)));
    }

    /**
     * Send a project notification, never letting a notification failure
     * block the write that triggered it.
     *
     * type is always 'system'. It must never be 'trip' — the driver feed
     * (NotificationModel::getForDriver) filters on type = 'trip', and
     * project rows must not surface in the Android app.
     *
     * @param int[] $userIds
     */
    private function notifyProject(array $userIds, string $title, string $message, int $projectId): void
    {
        if (empty($userIds)) {
            return;
        }
        try {
            (new NotificationModel())->createForUsers($userIds, [
                'title'          => $title,
                'message'        => $message,
                'type'           => NOTIF_SYSTEM,
                'reference_id'   => $projectId,
                'reference_type' => 'project',
            ]);
        } catch (Throwable $e) {
            error_log('[LVMS] Notification failed: ' . $e->getMessage());
        }
    }

    // GET /projects
    public function index(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE);

        $projectModel = new ProjectModel();

        // Employees get a read-only view of their own company, scoped so a
        // colleague's pending or rejected request never appears. No filters
        // — there is nothing for them to filter across.
        if (Auth::isEmployee()) {
            $this->render('index', [
                'page_title'          => 'Projects',
                'projects'            => $projectModel->findForEmployee(
                    (int) Auth::companyId(),
                    (int) Auth::id()
                ),
                'companies'           => [],
                'companyFilter'       => 0,
                'statusFilter'        => '',
                'reviewableCompanies' => [],
                'isEmployee'          => true,
            ]);
            return;
        }

        $companyFilter = (int) ($_GET['company_id'] ?? 0);
        $statusFilter  = (string) ($_GET['status'] ?? '');
        if (!in_array($statusFilter, [PROJ_PENDING, PROJ_ACTIVE, PROJ_COMPLETED, PROJ_REJECTED], true)) {
            $statusFilter = '';
        }

        $this->render('index', [
            'page_title'          => 'Projects',
            'projects'            => $projectModel->findAll($companyFilter, $statusFilter),
            'companies'           => (new CompanyModel())->findAll(),
            'companyFilter'       => $companyFilter,
            'statusFilter'        => $statusFilter,
            'reviewableCompanies' => $this->reviewableCompanyIds(),
            'isEmployee'          => false,
        ]);
    }

    // GET /projects/create
    public function create(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $settingModel = new SystemSettingModel();
        $companies    = $this->assignableCompanies();

        $this->render('create_edit', [
            'page_title'      => 'New Project',
            'project'         => null,
            'companies'       => $companies,
            'codeSuggestions' => $this->projectCodeSuggestions($companies),
            'warehouse_lat'   => (float) ($settingModel->getByKey('warehouse_lat') ?? '0'),
            'warehouse_lng'   => (float) ($settingModel->getByKey('warehouse_lng') ?? '0'),
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
        // ?? '' matters: without it a POST that omits the field reads as
        // null, which is !== '' and casts to 0.0 — silently pinning the
        // project at 0,0 in the Gulf of Guinea instead of leaving it unset.
        $lat       = trim($_POST['location_lat'] ?? '') !== '' ? (float) $_POST['location_lat'] : null;
        $lng       = trim($_POST['location_lng'] ?? '') !== '' ? (float) $_POST['location_lng'] : null;
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
        $companies    = $this->assignableCompanies();

        $this->render('create_edit', [
            'page_title'      => 'Edit Project — ' . $project['project_name'],
            'project'         => $project,
            'companies'       => $companies,
            'codeSuggestions' => $this->projectCodeSuggestions($companies),
            'warehouse_lat'   => (float) ($settingModel->getByKey('warehouse_lat') ?? '0'),
            'warehouse_lng'   => (float) ($settingModel->getByKey('warehouse_lng') ?? '0'),
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
        // ?? '' matters: without it a POST that omits the field reads as
        // null, which is !== '' and casts to 0.0 — silently pinning the
        // project at 0,0 in the Gulf of Guinea instead of leaving it unset.
        $lat       = trim($_POST['location_lat'] ?? '') !== '' ? (float) $_POST['location_lat'] : null;
        $lng       = trim($_POST['location_lng'] ?? '') !== '' ? (float) $_POST['location_lng'] : null;
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

    // ── Employee request flow ─────────────────────────────────────

    // GET /projects/request
    public function requestForm(): void
    {
        Auth::requireRole(ROLE_EMPLOYEE);

        $settingModel = new SystemSettingModel();

        $this->render('request', [
            'page_title'    => 'Request a Project',
            // Shown read-only so the requester can see which company the
            // request is filed under. submitRequest() reads the session,
            // not this value.
            'company'       => (new CompanyModel())->findById((int) Auth::companyId()),
            'warehouse_lat' => (float) ($settingModel->getByKey('warehouse_lat') ?? '0'),
            'warehouse_lng' => (float) ($settingModel->getByKey('warehouse_lng') ?? '0'),
        ]);
    }

    // POST /projects/request
    public function submitRequest(): void
    {
        Auth::requireRole(ROLE_EMPLOYEE);

        $name      = trim($_POST['project_name'] ?? '');
        $desc      = trim($_POST['description']  ?? '');
        $location  = trim($_POST['location']     ?? '') ?: null;
        $lat       = trim($_POST['location_lat'] ?? '') !== '' ? (float) $_POST['location_lat'] : null;
        $lng       = trim($_POST['location_lng'] ?? '') !== '' ? (float) $_POST['location_lng'] : null;
        $startDate = trim($_POST['start_date']   ?? '') ?: null;
        $endDate   = trim($_POST['end_date']     ?? '') ?: null;

        // The company is taken from the session, never from POST — an
        // employee cannot file a request against another company.
        $companyId = (int) Auth::companyId();

        if ($name === '' || $desc === '') {
            Helpers::setFlash('error', 'Project name and description are required.');
            Helpers::redirect('/projects/request');
        }
        if ($companyId === 0) {
            Helpers::setFlash('error', 'Your account has no company assigned. Contact an administrator.');
            Helpers::redirect('/projects');
        }

        $projectModel = new ProjectModel();
        $newId        = $projectModel->create([
            'company_id'   => $companyId,
            'created_by'   => (int) Auth::id(),
            'requested_by' => (int) Auth::id(),
            'project_name' => $name,
            // project_code is left NULL — the reviewer assigns one on Edit
            // after approving, keeping the hand-entered code format intact.
            'project_code' => null,
            'location'     => $location,
            'location_lat' => $lat,
            'location_lng' => $lng,
            'start_date'   => $startDate,
            'end_date'     => $endDate,
            'description'  => $desc,
            'status'       => PROJ_PENDING,
        ]);

        (new AuditLogModel())->log(
            (int) Auth::id(),
            'PROJECT_REQUESTED',
            'projects',
            $newId,
            null,
            ['project_name' => $name, 'company_id' => $companyId]
        );

        $this->notifyProject(
            $this->reviewersForCompany($companyId),
            'New Project Request',
            Auth::fullName() . ' requested a new project: ' . $name . '.',
            $newId
        );

        Helpers::setFlash('success', 'Project request submitted. You will be notified once it is reviewed.');
        Helpers::redirect('/projects');
    }

    // ── Review ────────────────────────────────────────────────────

    /**
     * Load a project for review, enforcing both company scope and the
     * pending precondition. Redirects (and therefore exits) on failure,
     * so callers can treat the return value as valid.
     *
     * @return array<string, mixed>
     */
    private function loadForReview(int $id, bool $requirePending): array
    {
        $project = (new ProjectModel())->findById($id);

        if (!$project) {
            Helpers::setFlash('error', 'Project not found.');
            Helpers::redirect('/projects');
        }

        $this->requireProjectCompanyScope((int) $project['company_id']);

        if ($requirePending && $project['status'] !== PROJ_PENDING) {
            Helpers::setFlash('error', 'That project is not awaiting review.');
            Helpers::redirect('/projects');
        }

        return $project;
    }

    // GET /projects/{id}/review
    public function review(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $project   = $this->loadForReview($id, true);
        $requester = $project['requested_by']
            ? (new UserModel())->findById((int) $project['requested_by'])
            : null;

        $this->render('review', [
            'page_title' => 'Review Project — ' . $project['project_name'],
            'project'    => $project,
            'requester'  => $requester,
        ]);
    }

    // POST /projects/{id}/approve
    public function approve(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $project = $this->loadForReview($id, true);

        (new ProjectModel())->approve($id, (int) Auth::id());

        (new AuditLogModel())->log(
            (int) Auth::id(),
            'PROJECT_APPROVED',
            'projects',
            $id,
            ['status' => PROJ_PENDING],
            ['status' => PROJ_ACTIVE]
        );

        if ($project['requested_by']) {
            $this->notifyProject(
                [(int) $project['requested_by']],
                'Project Approved',
                $project['project_name'] . ' was approved and can now be selected on a reservation.',
                $id
            );
        }

        Helpers::setFlash('success', 'Project "' . $project['project_name'] . '" approved.');
        Helpers::redirect('/projects');
    }

    // POST /projects/{id}/reject
    public function reject(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $reason = trim($_POST['rejection_reason'] ?? '');
        if ($reason === '') {
            Helpers::setFlash('error', 'A rejection reason is required.');
            Helpers::redirect('/projects/' . $id . '/review');
        }

        $project = $this->loadForReview($id, true);

        (new ProjectModel())->reject($id, (int) Auth::id(), $reason);

        (new AuditLogModel())->log(
            (int) Auth::id(),
            'PROJECT_REJECTED',
            'projects',
            $id,
            ['status' => PROJ_PENDING],
            ['status' => PROJ_REJECTED, 'rejection_reason' => $reason]
        );

        // Rejection is terminal — say so, so the requester knows to file a
        // new request rather than waiting for this one to be reconsidered.
        if ($project['requested_by']) {
            $this->notifyProject(
                [(int) $project['requested_by']],
                'Project Rejected',
                $project['project_name'] . ' was rejected: ' . $reason
                    . ' This decision is final — please submit a new request if the project should still go ahead.',
                $id
            );
        }

        Helpers::setFlash('success', 'Project "' . $project['project_name'] . '" rejected.');
        Helpers::redirect('/projects');
    }
}
