<?php
class ProfileController {
    private function render(string $view, array $data = []): void { extract($data); $content_view = __DIR__ . '/../views/profile/' . $view . '.php'; require_once __DIR__ . '/../views/layouts/main.php'; }
    public function index(): void  { Auth::requireLogin(); $this->render('index', ['page_title' => 'My Profile']); }
    public function update(): void { Auth::requireLogin(); Helpers::setFlash('success', 'Profile updated. (Capstone 1)'); Helpers::redirect('/profile'); }
}
