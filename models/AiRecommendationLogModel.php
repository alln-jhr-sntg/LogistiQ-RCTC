<?php

/**
 * AiRecommendationLogModel
 *
 * Wraps the `ai_recommendation_logs` table.
 * Every vehicle evaluated during a recommendation run is logged here
 * — both disqualified vehicles (with reason) and scored vehicles
 * (with all six sub-scores). This provides a full audit trail of
 * why a particular vehicle was or wasn't recommended, and feeds
 * future ML training data as noted in the implementation plan.
 *
 * Scores are integers 0-100 (TINYINT UNSIGNED) — see
 * database/migrations/2026_08_18_recommendation_score_int.sql. A null
 * sub-score means that criterion did not apply to this vehicle/request
 * pair (e.g. no cargo requested), not that it scored zero.
 */
class AiRecommendationLogModel extends BaseModel
{
    /**
     * Insert one log row for a single vehicle evaluated against a reservation.
     *
     * @param int           $reservationId
     * @param int           $vehicleId
     * @param int           $score          Final weighted score, 0-100. 0 if disqualified.
     * @param array         $subScores      Keys: capacity, cargo, schedule, purpose_fit,
     *                                      maintenance, weight_coding. Each an int 0-100 or
     *                                      null (not applicable). Empty if disqualified.
     * @param bool          $disqualified   true = did not pass Phase 1 hard filters.
     * @param string|null   $disqualifyReason  Required when $disqualified is true.
     */
    public function log(
        int     $reservationId,
        int     $vehicleId,
        int     $score,
        array   $subScores,
        bool    $disqualified,
        ?string $disqualifyReason
    ): void {
        $this->execute(
            'INSERT INTO ai_recommendation_logs
                (reservation_id, vehicle_id, score,
                 capacity_score, cargo_score, schedule_score,
                 purpose_fit_score, maintenance_score, weight_coding_score,
                 disqualified, disqualify_reason)
             VALUES
                (:reservation_id, :vehicle_id, :score,
                 :capacity_score, :cargo_score, :schedule_score,
                 :purpose_fit_score, :maintenance_score, :weight_coding_score,
                 :disqualified, :disqualify_reason)',
            [
                ':reservation_id'      => $reservationId,
                ':vehicle_id'          => $vehicleId,
                ':score'               => $score,
                ':capacity_score'      => $subScores['capacity']      ?? null,
                ':cargo_score'         => $subScores['cargo']         ?? null,
                ':schedule_score'      => $subScores['schedule']      ?? null,
                ':purpose_fit_score'   => $subScores['purpose_fit']   ?? null,
                ':maintenance_score'   => $subScores['maintenance']   ?? null,
                ':weight_coding_score' => $subScores['weight_coding'] ?? null,
                ':disqualified'        => $disqualified ? 1 : 0,
                ':disqualify_reason'   => $disqualifyReason,
            ]
        );
    }

    /**
     * Return all log rows for a reservation, joined with vehicle and
     * category info. Non-disqualified vehicles come first, ordered by
     * score descending so the best recommendation appears at the top.
     * Ties break to the lower-odometer vehicle (balances fleet
     * utilization), then the lower vehicle_id — deterministic, and matches
     * the tie-break VehicleRecommendationService uses to pick the winner,
     * so a tied non-TOP row can never outrank the TOP row in this list.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByReservation(int $reservationId): array
    {
        return $this->fetchAll(
            'SELECT   l.*,
                      v.plate_number,
                      v.brand,
                      v.model,
                      v.year_model,
                      v.passenger_capacity,
                      v.cargo_capacity_kg,
                      v.current_odometer_km,
                      vc.category_name
             FROM     ai_recommendation_logs l
             JOIN     vehicles           v  ON v.vehicle_id  = l.vehicle_id
             JOIN     vehicle_categories vc ON vc.category_id = v.category_id
             WHERE    l.reservation_id = :reservation_id
             ORDER BY l.disqualified ASC, l.score DESC,
                      v.current_odometer_km ASC, v.vehicle_id ASC',
            [':reservation_id' => $reservationId]
        );
    }

    /**
     * Whether the recommendation engine has already run for this
     * reservation. Used by ReservationController::review() to decide
     * whether to (re-)run VehicleRecommendationService — replaces the old
     * guard on reservations.ai_recommended_vehicle_id IS NULL, which stayed
     * true forever whenever Phase 1 disqualified every candidate, causing
     * the engine to silently re-run (and duplicate every log row) on every
     * page load.
     */
    public function hasRunFor(int $reservationId): bool
    {
        return $this->fetchOne(
            'SELECT log_id FROM ai_recommendation_logs
             WHERE  reservation_id = :reservation_id LIMIT 1',
            [':reservation_id' => $reservationId]
        ) !== null;
    }

    /**
     * Delete all log rows for a reservation. Called at the start of a
     * recommendation run (VehicleRecommendationService::recommend()) so a
     * re-run replaces rather than appends, and from
     * ReservationController::updateReservation() when a pending
     * reservation's dates/passengers/cargo change and the prior run's
     * rows no longer reflect the current request.
     */
    public function deleteByReservation(int $reservationId): void
    {
        $this->execute(
            'DELETE FROM ai_recommendation_logs WHERE reservation_id = :reservation_id',
            [':reservation_id' => $reservationId]
        );
    }
}
