-- ============================================================
-- 2026_08_15 — vehicle_maintenance.maintenance_type enum
--
-- Converts maintenance_type from free-text VARCHAR(100) to a fixed
-- ENUM, matching the type options used by the maintenance log form
-- and the maintenance report/history type filter — guarantees every
-- logged record matches a filter option.
--
-- Existing rows whose maintenance_type text doesn't match one of the
-- fixed values are remapped to 'Other' first, since converting the
-- column would otherwise truncate/reject them.
--
-- Run on BOTH local and Hostinger.
-- ============================================================

UPDATE vehicle_maintenance
SET    maintenance_type = 'Other'
WHERE  maintenance_type NOT IN (
    'Oil Change', 'Preventive Maintenance', 'Brake Inspection',
    'Tire Inspection', 'Engine Check', 'Repair', 'Other'
);

ALTER TABLE vehicle_maintenance
    MODIFY COLUMN maintenance_type
        ENUM('Oil Change','Preventive Maintenance','Brake Inspection',
             'Tire Inspection','Engine Check','Repair','Other') NOT NULL;
