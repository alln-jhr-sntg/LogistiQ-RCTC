<?php

/**
 * SystemSettingModel
 *
 * Wraps the `system_settings` table.
 *
 * Used in:
 *   Step 5  — SettingsController reads and updates settings
 *   Step 7  — ReservationCodeService reads reservation_prefix
 *   Step 12 — MaintenanceService reads maintenance_interval_km
 *   Step 16 — GpsController reads warehouse_lat / warehouse_lng
 */
class SystemSettingModel extends BaseModel
{
    /**
     * Return all settings rows, ordered by setting_key.
     * Each row contains: setting_id, setting_key, setting_value,
     * description, updated_by, updated_at.
     * The settings view loops over this to render the form.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAll(): array
    {
        return $this->fetchAll(
            'SELECT * FROM system_settings ORDER BY setting_key ASC'
        );
    }

    /**
     * Return the value for a single setting key, or null if not found.
     * Callers that need a non-null default should use the null-coalescing
     * operator: getByKey('reservation_prefix') ?? 'RES'
     */
    public function getByKey(string $key): ?string
    {
        $row = $this->fetchOne(
            'SELECT setting_value FROM system_settings
             WHERE setting_key = :key LIMIT 1',
            [':key' => $key]
        );
        return $row !== null ? (string) $row['setting_value'] : null;
    }

    /**
     * Update a single setting value.
     * updated_at is handled automatically by ON UPDATE CURRENT_TIMESTAMP.
     * updated_by records which super_admin made the change.
     */
    public function updateByKey(string $key, string $value, int $updatedBy): void
    {
        $this->execute(
            'UPDATE system_settings
             SET setting_value = :value,
                 updated_by    = :updated_by
             WHERE setting_key = :key',
            [
                ':value'      => $value,
                ':updated_by' => $updatedBy,
                ':key'        => $key,
            ]
        );
    }
}
