<?php
class UserController {
    private function render(string $view, array $data = []): void { extract($data); $content_view = __DIR__ . '/../views/users/' . $view . '.php'; require_once __DIR__ . '/../views/layouts/main.php'; }
    public function index(): void               { Auth::requireRole(ROLE_SUPER_ADMIN); $this->render('index', ['page_title' => 'Users']); }
    public function create(): void              { Auth::requireRole(ROLE_SUPER_ADMIN); $this->render('create', ['page_title' => 'Add User']); }
    public function store(): void               { Auth::requireRole(ROLE_SUPER_ADMIN); Helpers::setFlash('success', 'User created. (Capstone 1)'); Helpers::redirect('/users'); }
    public function edit(): void                { Auth::requireRole(ROLE_SUPER_ADMIN); $this->render('edit', ['page_title' => 'Edit User']); }
    public function update(): void              { Auth::requireRole(ROLE_SUPER_ADMIN); Helpers::setFlash('success', 'User updated. (Capstone 1)'); Helpers::redirect('/users'); }
    public function driverProfile(): void       { Auth::requireRole(ROLE_SUPER_ADMIN); $this->render('driver_profile', ['page_title' => 'Driver Profile']); }
    public function updateDriverProfile(): void { Auth::requireRole(ROLE_SUPER_ADMIN); Helpers::setFlash('success', 'Driver profile updated. (Capstone 1)'); Helpers::redirect('/users'); }
}
