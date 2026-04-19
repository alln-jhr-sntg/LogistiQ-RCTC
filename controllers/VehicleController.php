<?php
class VehicleController {
    private function render(string $view, array $data = []): void { extract($data); $content_view = __DIR__ . '/../views/vehicles/' . $view . '.php'; require_once __DIR__ . '/../views/layouts/main.php'; }
    public function index(): void          { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); $this->render('index', ['page_title' => 'Fleet Management']); }
    public function create(): void         { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); $this->render('create', ['page_title' => 'Add Vehicle']); }
    public function store(): void          { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); Helpers::setFlash('success', 'Vehicle added. (Capstone 1)'); Helpers::redirect('/vehicles'); }
    public function edit(): void           { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); $this->render('edit', ['page_title' => 'Edit Vehicle']); }
    public function update(): void         { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); Helpers::setFlash('success', 'Vehicle updated. (Capstone 1)'); Helpers::redirect('/vehicles'); }
    public function categories(): void     { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); $this->render('categories', ['page_title' => 'Vehicle Categories']); }
    public function storeCategory(): void  { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); Helpers::setFlash('success', 'Vehicle category saved. (Capstone 1)'); Helpers::redirect('/vehicles/categories'); }
    public function updateCategory(): void { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); Helpers::setFlash('success', 'Vehicle category updated. (Capstone 1)'); Helpers::redirect('/vehicles/categories'); }
    public function maintenance(): void    { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); $this->render('maintenance', ['page_title' => 'Maintenance Log']); }
    public function storeMaintenance(): void { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); Helpers::setFlash('success', 'Maintenance saved. (Capstone 1)'); Helpers::redirect('/vehicles'); }
}
