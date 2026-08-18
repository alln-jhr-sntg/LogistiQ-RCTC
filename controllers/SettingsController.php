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
        $values       = [];
        $descriptions = [];
        foreach ($settingModel->getAll() as $row) {
            $values[$row['setting_key']]       = $row['setting_value'];
            $descriptions[$row['setting_key']] = $row['description'];
        }

        $this->render('index', [
            'page_title'   => 'System Settings',
            'groups'       => SETTING_GROUPS,
            'specs'        => SETTING_SPECS,
            'values'       => $values,
            'descriptions' => $descriptions,
        ]);
    }

    // POST /settings
    public function update(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN);

        $submitted = $_POST['settings'] ?? [];

        // Reject anything not in SETTING_SPECS outright — this is also what
        // keeps 'company_name' (deliberately absent from SETTING_SPECS)
        // read-only even against a hand-crafted POST.
        $unknown = array_diff(array_keys($submitted), array_keys(SETTING_SPECS));
        if (!empty($unknown)) {
            Helpers::setFlash('error', 'Unknown setting(s): ' . implode(', ', $unknown) . '.');
            Helpers::redirect('/settings');
        }

        // Validate everything before writing anything — a single bad field
        // must not cost the admin every other edit on the form.
        $clean  = [];
        $errors = [];
        foreach (SETTING_SPECS as $key => $spec) {
            if (!array_key_exists($key, $submitted)) {
                continue;
            }
            $result = $this->validate($spec, (string) $submitted[$key]);
            if ($result === null) {
                $errors[] = $spec['label'] . ' is invalid.';
            } else {
                $clean[$key] = $result;
            }
        }

        if (!empty($errors)) {
            Helpers::setFlash('error', implode(' ', $errors));
            Helpers::redirect('/settings');
        }

        if (empty($clean)) {
            Helpers::setFlash('success', 'Settings saved.');
            Helpers::redirect('/settings');
        }

        $settingModel = new SystemSettingModel();
        $old          = [];
        foreach ($clean as $key => $value) {
            $old[$key] = $settingModel->getByKey($key);
        }
        // Only keys whose value actually changed go into the audit entry.
        $changedOld = [];
        $changedNew = [];
        foreach ($clean as $key => $value) {
            if ($old[$key] !== $value) {
                $changedOld[$key] = $old[$key];
                $changedNew[$key] = $value;
            }
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $updatedBy = (int) Auth::id();
            foreach ($clean as $key => $value) {
                $settingModel->updateByKey($key, $value, $updatedBy);
            }

            if (!empty($changedNew)) {
                $auditModel = new AuditLogModel();
                $auditModel->log(
                    $updatedBy,
                    'SETTINGS_UPDATED',
                    'system_settings',
                    null,
                    $changedOld,
                    $changedNew
                );
            }

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        Helpers::setFlash('success', 'Settings saved.');
        Helpers::redirect('/settings');
    }

    /**
     * Validate one posted value against its spec. Returns the cleaned
     * string to store, or null if the value is invalid.
     *
     * @param array<string, mixed> $spec
     */
    private function validate(array $spec, string $raw): ?string
    {
        $value = trim($raw);

        switch ($spec['type']) {
            case 'text':
                if ($value === '' || mb_strlen($value) > $spec['max']) {
                    return null;
                }
                return $value;

            case 'latitude':
                if (!is_numeric($value) || (float) $value < -90 || (float) $value > 90) {
                    return null;
                }
                return $value;

            case 'longitude':
                if (!is_numeric($value) || (float) $value < -180 || (float) $value > 180) {
                    return null;
                }
                return $value;

            case 'prefix':
                $value = strtoupper($value);
                if (!preg_match('/^[A-Z][A-Z0-9]{0,5}$/', $value)) {
                    return null;
                }
                return $value;

            case 'int_min':
                $int = filter_var($value, FILTER_VALIDATE_INT);
                if ($int === false || $int < $spec['min']) {
                    return null;
                }
                return (string) $int;

            default:
                return null;
        }
    }
}
