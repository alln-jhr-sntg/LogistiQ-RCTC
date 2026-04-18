<?php
class ProjectController {
    private function render(string $view, array $data = []): void { extract($data); $content_view = __DIR__ . '/../views/projects/' . $view . '.php'; require_once __DIR__ . '/../views/layouts/main.php'; }
    public function index(): void  { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); $this->render('index', ['page_title' => 'Projects']); }
    public function create(): void { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); $this->render('create_edit', ['page_title' => 'New Project', 'project' => null]); }
    public function store(): void  { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); Helpers::setFlash('success', 'Project created. (Capstone 1)'); Helpers::redirect('/projects'); }
    public function edit(): void   { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); $this->render('create_edit', ['page_title' => 'Edit Project', 'project' => null]); }
    public function update(): void { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); Helpers::setFlash('success', 'Project updated. (Capstone 1)'); Helpers::redirect('/projects'); }
}
