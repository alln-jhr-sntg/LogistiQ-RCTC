-- ============================================================
-- MIGRATION: 2026_08_11_moveops_rebrand
-- Rebrand: update system_settings.system_name from the old
-- LogistiQ display name to MoveOps.
-- ============================================================

UPDATE system_settings
SET setting_value = 'MoveOps'
WHERE setting_key = 'system_name';
