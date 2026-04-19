<?php
class CompanyController {
    private function render(string $view, array $data = []): void { extract($data); $content_view = __DIR__ . '/../views/companies/' . $view . '.php'; require_once __DIR__ . '/../views/layouts/main.php'; }
    public function index(): void           { Auth::requireRole(ROLE_SUPER_ADMIN); $this->render('index', ['page_title' => 'Companies']); }
    public function departments(): void     { Auth::requireRole(ROLE_SUPER_ADMIN); $this->render('departments', ['page_title' => 'Departments']); }
    public function storeDepartment(): void { Auth::requireRole(ROLE_SUPER_ADMIN); Helpers::setFlash('success', 'Department saved. (Capstone 1)'); Helpers::redirect('/companies'); }
    public function access(): void         { Auth::requireRole(ROLE_SUPER_ADMIN); $this->render('access', ['page_title' => 'Admin Access']); }
    public function grantAccess(): void    { Auth::requireRole(ROLE_SUPER_ADMIN); Helpers::setFlash('success', 'Access granted. (Capstone 1)'); Helpers::redirect('/companies/access'); }
    public function revokeAccess(): void   { Auth::requireRole(ROLE_SUPER_ADMIN); Helpers::setFlash('success', 'Access revoked. (Capstone 1)'); Helpers::redirect('/companies/access'); }
}
