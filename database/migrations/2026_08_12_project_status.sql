-- ============================================================
-- MIGRATION: 2026_08_12_project_status
-- Replaces projects.is_active with a proper request/review
-- workflow (status + requested_by/reviewed_by/reviewed_at/
-- rejection_reason), mirroring the reservations/gatepasses
-- pattern. Run on BOTH local and Hostinger.
-- ============================================================

-- ============================================================
-- 1. projects — add status + review workflow columns
-- ============================================================
ALTER TABLE projects
    ADD COLUMN status           ENUM('pending','active','completed','rejected') NOT NULL DEFAULT 'pending' AFTER end_date,
    ADD COLUMN requested_by     INT UNSIGNED NULL AFTER status,
    ADD COLUMN reviewed_by      INT UNSIGNED NULL AFTER requested_by,
    ADD COLUMN reviewed_at      TIMESTAMP    NULL AFTER reviewed_by,
    ADD COLUMN rejection_reason TEXT         NULL AFTER reviewed_at,
    ADD CONSTRAINT fk_project_requester FOREIGN KEY (requested_by) REFERENCES users(user_id),
    ADD CONSTRAINT fk_project_reviewer  FOREIGN KEY (reviewed_by)  REFERENCES users(user_id);


-- ============================================================
-- 2. Backfill status from is_active before it is dropped
-- ============================================================
UPDATE projects SET status = 'active'    WHERE is_active = 1;
UPDATE projects SET status = 'completed' WHERE is_active = 0;


-- ============================================================
-- 3. DROP is_active — superseded by status
-- ============================================================
ALTER TABLE projects
    DROP COLUMN is_active;


-- ============================================================
-- 4. Index for status filtering scoped by company
-- ============================================================
ALTER TABLE projects
    ADD INDEX idx_project_status (company_id, status);
