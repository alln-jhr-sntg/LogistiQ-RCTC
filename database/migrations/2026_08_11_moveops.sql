-- ============================================================
-- MIGRATION: 2026_08_11_moveops
-- Fleet admin role, trip cancellation, gate pass workflow,
-- and removal of the unused admin_department_access table.
-- Test data only — no rollback logic.
-- ============================================================

-- ============================================================
-- 1. users.role — add 'fleet_admin'
-- ============================================================
ALTER TABLE users
    MODIFY COLUMN role ENUM('super_admin','fleet_admin','admin','employee','driver') NOT NULL;


-- ============================================================
-- 2. trips.trip_status — add 'cancelled'
-- ============================================================
ALTER TABLE trips
    MODIFY COLUMN trip_status ENUM('pending_start','in_progress','completed','incident','cancelled') NOT NULL DEFAULT 'pending_start';


-- ============================================================
-- 3. trips — cancellation columns (mirrors reservations pattern)
-- ============================================================
ALTER TABLE trips
    ADD COLUMN cancelled_by        INT UNSIGNED NULL AFTER driver_id,
    ADD COLUMN cancellation_reason TEXT         NULL AFTER admin_notes,
    ADD CONSTRAINT fk_trip_canceller FOREIGN KEY (cancelled_by) REFERENCES users(user_id);


-- ============================================================
-- 4. reservations.status — add 'gatepass_pending' between
--    'approved' and 'rejected'
-- ============================================================
ALTER TABLE reservations
    MODIFY COLUMN status ENUM(
                              'pending',
                              'approved',
                              'gatepass_pending',
                              'rejected',
                              'cancelled',
                              'in_progress',
                              'completed'
                          ) NOT NULL DEFAULT 'pending';


-- ============================================================
-- 5. NEW TABLE: gatepasses
-- ============================================================
CREATE TABLE gatepasses (
    gatepass_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id   INT UNSIGNED NOT NULL UNIQUE,
    gatepass_code    VARCHAR(30)  NOT NULL UNIQUE COMMENT 'e.g. GP-2026-00001',
    status           ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by      INT UNSIGNED NULL,
    reviewed_at      TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_gatepass_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id),
    CONSTRAINT fk_gatepass_reviewer    FOREIGN KEY (reviewed_by)    REFERENCES users(user_id)
) ENGINE=InnoDB;


-- ============================================================
-- 6. DROP TABLE admin_department_access
-- InnoDB drops a table's own FK constraints along with it;
-- no other table has a FK pointing at admin_department_access,
-- so no FK checks toggle is needed.
-- ============================================================
DROP TABLE IF EXISTS admin_department_access;
