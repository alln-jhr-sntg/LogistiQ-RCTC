-- ============================================================
-- 2026_08_13 — projects.description
--
-- Adds the justification an employee writes when requesting a
-- project. Required on the employee request form; optional on the
-- admin create/edit form, where the actor already has the authority
-- to create a project outright.
--
-- Run on BOTH local and Hostinger.
-- ============================================================

ALTER TABLE projects
    ADD COLUMN description TEXT NULL AFTER end_date;
