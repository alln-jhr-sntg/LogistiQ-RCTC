<?php
class NotificationController {
    private function render(string $view, array $data = []): void { extract($data); $content_view = __DIR__ . '/../views/notifications/' . $view . '.php'; require_once __DIR__ . '/../views/layouts/main.php'; }
    public function index(): void      { Auth::requireLogin(); $this->render('index', ['page_title' => 'Notifications']); }
    public function markAllRead(): void { Auth::requireLogin(); Helpers::setFlash('success', 'All marked read. (Capstone 1)'); Helpers::redirect('/notifications'); }
}
