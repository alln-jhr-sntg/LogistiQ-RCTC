<?php

/**
 * ReservationCodeService
 *
 * Generates human-readable reservation codes in the format:
 *   {PREFIX}-{YEAR}-{LPAD(id, 6, '0')}
 *   e.g. RES-2025-000001
 *
 * The prefix comes from system_settings.reservation_prefix so it
 * can be changed by a super admin without touching code.
 *
 * This service is called INSIDE a transaction in ReservationModel::create(),
 * immediately after the INSERT returns a LAST_INSERT_ID(). The reservation
 * row is then UPDATEd with the generated code before COMMIT.
 *
 * Never call generate() before an INSERT — the ID must already exist.
 */
class ReservationCodeService
{
    /**
     * Generate a reservation code for the given reservation_id.
     *
     * @param int $reservationId  The auto-increment ID just returned by
     *                            LAST_INSERT_ID() — must be > 0.
     * @return string             e.g. "RES-2025-000001"
     */
    public static function generate(int $reservationId): string
    {
        $settingModel = new SystemSettingModel();
        $prefix       = $settingModel->getByKey('reservation_prefix') ?? 'RES';
        $year         = date('Y');
        $padded       = str_pad((string) $reservationId, 6, '0', STR_PAD_LEFT);

        return $prefix . '-' . $year . '-' . $padded;
    }
}
