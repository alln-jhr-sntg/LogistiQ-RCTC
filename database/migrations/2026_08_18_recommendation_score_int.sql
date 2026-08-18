-- ============================================================
-- 2026_08_18 — recommendation engine scores: decimal(0-1) -> int(0-100)
--
-- Rescales the recommendation engine's score columns from a 0.00-1.00
-- DECIMAL(5,2) to a 0-100 TINYINT UNSIGNED, and renames/adds a column to
-- match the reworked services/VehicleRecommendationService.php and
-- services/WeightCodingService.php:
--   - availability_score (always a hard-coded 1.0 — a dead criterion with
--     zero discriminating power) is renamed to schedule_score, now a real
--     schedule-headroom signal.
--   - weight_coding_score is new — GVWR/LTO truck-coding moved from a
--     Phase 1 hard disqualifier (which made every vehicle over the GVWR
--     limit permanently unrecommendable) to a Phase 2 weighted criterion.
--
-- Run on BOTH local and Hostinger.
--
-- Not safely re-runnable: Step 1 multiplies existing values by 100 while
-- the columns are still DECIMAL; running it twice would double-scale any
-- row it already touched. Steps 2-3 narrow/rename columns that won't exist
-- under their old names/types afterward, so re-running fails loudly rather
-- than silently corrupting data. If unsure whether this has already run on
-- a given environment, check ai_recommendation_logs' column list first.
-- ============================================================

-- Step 1 — rescale existing 0.00-1.00 values to 0-100 while the columns
-- are still DECIMAL(5,2) (max 999.99, so x100 always fits).
UPDATE ai_recommendation_logs
SET    score              = score              * 100,
       capacity_score     = capacity_score      * 100,
       cargo_score        = cargo_score         * 100,
       availability_score = availability_score  * 100,
       purpose_fit_score  = purpose_fit_score   * 100,
       maintenance_score  = maintenance_score   * 100;

UPDATE reservations
SET    ai_recommendation_score = ai_recommendation_score * 100
WHERE  ai_recommendation_score IS NOT NULL;

-- Step 2 — narrow the type now that every value is a whole number 0-100.
ALTER TABLE ai_recommendation_logs
    MODIFY COLUMN score              TINYINT UNSIGNED NOT NULL,
    MODIFY COLUMN capacity_score     TINYINT UNSIGNED NULL,
    MODIFY COLUMN cargo_score        TINYINT UNSIGNED NULL,
    MODIFY COLUMN availability_score TINYINT UNSIGNED NULL,
    MODIFY COLUMN purpose_fit_score  TINYINT UNSIGNED NULL,
    MODIFY COLUMN maintenance_score  TINYINT UNSIGNED NULL;

ALTER TABLE reservations
    MODIFY COLUMN ai_recommendation_score TINYINT UNSIGNED NULL;

-- Step 3 — availability_score -> schedule_score (the criterion it was
-- always meant to be — see VehicleRecommendationService's docblock), and
-- add weight_coding_score for the new GVWR/truck-ban criterion.
ALTER TABLE ai_recommendation_logs
    CHANGE COLUMN availability_score schedule_score TINYINT UNSIGNED NULL,
    ADD    COLUMN weight_coding_score TINYINT UNSIGNED NULL AFTER maintenance_score;
