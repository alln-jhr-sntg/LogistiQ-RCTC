<?php

class SettingsController
{
    private function render(string $view, array $data = []): void
    {
        extract($data);
        $content_view = __DIR__ . '/../views/settings/' . $view . '.php';
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    // GET /settings
    public function index(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN);

        $settingModel = new SystemSettingModel();
        $settings     = $settingModel->getAll();

        $this->render('index', ['page_title' => 'System Settings', 'settings' => $settings]);
    }

    // POST /settings
    public function update(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN);

        $submitted = $_POST['settings'] ?? [];

        if (!empty($submitted)) {
            $settingModel = new SystemSettingModel();
            foreach ($submitted as $key => $value) {
                $settingModel->updateByKey(
                    (string) $key,
                    (string) $value,
                    (int) Auth::id()
                );
            }
        }

        Helpers::setFlash('success', 'Settings saved.');
        Helpers::redirect('/settings');
    }
}
