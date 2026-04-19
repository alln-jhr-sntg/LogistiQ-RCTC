<?php
class ProjectController {
    private function render(string $view, array $data = []): void { extract($data); $content_view = __DIR__ . '/../views/projects/' . $view . '.php'; require_once __DIR__ . '/../views/layouts/main.php'; }
    public function index(): void  { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); $this->render('index', ['page_title' => 'Projects']); }
    public function create(): void { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); $this->render('create_edit', ['page_title' => 'New Project', 'project' => null]); }
    public function store(): void  { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); Helpers::setFlash('success', 'Project created. (Capstone 1)'); Helpers::redirect('/projects'); }
    public function edit(): void {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN);
        $parts = array_values(array_filter(explode('/', trim($_GET['url'] ?? '', '/'))));
        $id = (int) ($parts[1] ?? 1);
        $mock = [
            1 => ['project_id'=>1,'project_name'=>'REMIX Tower Phase 1','project_code'=>'RMX-2025-001','company_id'=>1,'start_date'=>'2025-01-01','end_date'=>'2026-12-31','location'=>'Ortigas, Pasig City','location_lat'=>'14.58630000','location_lng'=>'121.06010000','is_active'=>1],
            2 => ['project_id'=>2,'project_name'=>'Ideal Home Subdivision','project_code'=>'IDL-2025-002','company_id'=>2,'start_date'=>'2025-03-01','end_date'=>'2027-06-30','location'=>'Bulacan','location_lat'=>'14.79430000','location_lng'=>'120.87850000','is_active'=>1],
            3 => ['project_id'=>3,'project_name'=>'TenBuild Commercial Hub','project_code'=>'TBD-2025-003','company_id'=>3,'start_date'=>'2025-06-01','end_date'=>'2026-12-31','location'=>'Laguna','location_lat'=>'14.16500000','location_lng'=>'121.24310000','is_active'=>1],
        ];
        $this->render('create_edit', ['page_title' => 'Edit Project', 'project' => $mock[$id] ?? $mock[1]]);
    }
    public function update(): void { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); Helpers::setFlash('success', 'Project updated. (Capstone 1)'); Helpers::redirect('/projects'); }
}
