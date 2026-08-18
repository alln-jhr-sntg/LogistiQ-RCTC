<?php

/**
 * SystemSettingModel
 *
 * Wraps the `system_settings` table.
 *
 * Used in:
 *   SettingsController reads and updates the editable settings (SETTING_SPECS)
 *   ReservationCodeService reads reservation_prefix
 *   GatepassCodeService reads gatepass_prefix
 *   UserModel reads employee_id_prefix
 *   VehicleController / VehicleRecommendationService read maintenance_interval_km
 *   TripController / ReservationController / ProjectController read warehouse_lat/lng
 *   AuthController reads company_name (login footer only — not in SETTING_SPECS)
 */
class SystemSettingModel extends BaseModel
{
    /**
     * Per-request cache for getByKey(), keyed by setting_key. Several
     * requests (e.g. the reservation/project map pickers) read the same
     * key twice in one page load; several more now read it once per view
     * render inside a loop. updateByKey() clears this so a save is
     * reflected immediately within the same request.
     *
     * @var array<string, string|null>
     */
    private static array $cache = [];

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
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $row = $this->fetchOne(
            'SELECT setting_value FROM system_settings
             WHERE setting_key = :key LIMIT 1',
            [':key' => $key]
        );

        return self::$cache[$key] = ($row !== null ? (string) $row['setting_value'] : null);
    }

    /**
     * Create or update a single setting value. An upsert rather than a
     * plain UPDATE: a key that has no row yet (e.g. a newly introduced
     * setting a super admin is configuring for the first time) is inserted
     * instead of silently no-opping. updated_at is handled automatically
     * by ON UPDATE CURRENT_TIMESTAMP; on insert it defaults the same way.
     */
    public function updateByKey(string $key, string $value, int $updatedBy): void
    {
        $this->execute(
            'INSERT INTO system_settings (setting_key, setting_value, updated_by)
             VALUES (:key, :value, :updated_by)
             ON DUPLICATE KEY UPDATE
                 setting_value = VALUES(setting_value),
                 updated_by    = VALUES(updated_by)',
            [
                ':key'        => $key,
                ':value'      => $value,
                ':updated_by' => $updatedBy,
            ]
        );

        unset(self::$cache[$key]);
    }
}
