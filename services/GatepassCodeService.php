<?php

/**
 * GatepassCodeService
 *
 * Generates human-readable gatepass codes in the format:
 *   {PREFIX}-{YEAR}-{LPAD(id, 6, '0')}
 *   e.g. GP-2026-000001
 *
 * Mirrors ReservationCodeService's format exactly (same 6-digit padding),
 * so a gatepass code and its reservation code line up visually wherever
 * they appear side by side — on the printed gatepass and in list views.
 *
 * The prefix comes from system_settings.gatepass_prefix so it can be
 * changed by a super admin without touching code. No such row is seeded
 * by default; the '?? GP' fallback covers that.
 *
 * generate() has NO collision handling of its own — it is a pure function
 * of the gatepass_id, which MySQL's AUTO_INCREMENT guarantees is unique.
 * Collision safety for the UNIQUE gatepass_code column during the gap
 * between INSERT and UPDATE lives in the CALLER: GatepassModel::create()
 * inserts a bin2hex(random_bytes(15)) placeholder inside a transaction,
 * exactly like ReservationModel::create() does for reservation_code.
 *
 * This service is called INSIDE that transaction, immediately after the
 * INSERT returns a LAST_INSERT_ID(). The gatepass row is then UPDATEd
 * with the generated code before COMMIT.
 *
 * Never call generate() before an INSERT — the ID must already exist.
 */
class GatepassCodeService
{
    /**
     * Generate a gatepass code for the given gatepass_id.
     *
     * @param int $gatepassId  The auto-increment ID just returned by
     *                         LAST_INSERT_ID() — must be > 0.
     * @return string          e.g. "GP-2026-000001"
     */
    public static function generate(int $gatepassId): string
    {
        $settingModel = new SystemSettingModel();
        $prefix       = $settingModel->getByKey('gatepass_prefix') ?? 'GP';
        $year         = date('Y');
        $padded       = str_pad((string) $gatepassId, 6, '0', STR_PAD_LEFT);

        return $prefix . '-' . $year . '-' . $padded;
    }
}
