-- ============================================================
-- MIGRATION: 2026_08_19_system_settings_audit
-- Seed the two code prefixes that services already read but that
-- were never inserted (gatepass_prefix, employee_id_prefix), and
-- correct the gatepass_code column comment, which documented 5-digit
-- padding while GatepassCodeService has always generated 6.
-- ============================================================

INSERT INTO system_settings (setting_key, setting_value, description) VALUES
    ('gatepass_prefix',    'GP',  'Prefix used for gatepass codes'),
    ('employee_id_prefix', 'EMP', 'Prefix used for suggested employee IDs')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- MODIFY COLUMN redefines only the column itself; it does not touch the
-- existing UNIQUE index from the original CREATE TABLE, so UNIQUE is
-- deliberately omitted here to avoid MySQL adding a second, redundant
-- unique index on this column.
ALTER TABLE gatepasses
    MODIFY gatepass_code VARCHAR(30) NOT NULL COMMENT 'e.g. GP-2026-000001';
