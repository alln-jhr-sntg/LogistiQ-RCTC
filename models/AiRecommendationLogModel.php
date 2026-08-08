<?php

/**
 * AiRecommendationLogModel
 *
 * Wraps the `ai_recommendation_logs` table.
 * Every vehicle evaluated during a recommendation run is logged here
 * — both disqualified vehicles (with reason) and scored vehicles
 * (with all five sub-scores). This provides a full audit trail of
 * why a particular vehicle was or wasn't recommended, and feeds
 * future ML training data as noted in the implementation plan.
 */
class AiRecommendationLogModel extends BaseModel
{
    /**
     * Insert one log row for a single vehicle evaluated against a reservation.
     *
     * @param int           $reservationId
     * @param int           $vehicleId
     * @param float         $score          Final weighted score. 0 if disqualified.
     * @param array         $subScores      Keys: capacity, cargo, availability,
     *                                      purpose_fit, maintenance. Empty if disqualified.
     * @param bool          $disqualified   true = did not pass Phase 1 hard filters.
     * @param string|null   $disqualifyReason  Required when $disqualified is true.
     */
    public function log(
        int     $reservationId,
        int     $vehicleId,
        float   $score,
        array   $subScores,
        bool    $disqualified,
        ?string $disqualifyReason
    ): void {
        $this->execute(
            'INSERT INTO ai_recommendation_logs
                (reservation_id, vehicle_id, score,
                 capacity_score, cargo_score, availability_score,
                 purpose_fit_score, maintenance_score,
                 disqualified, disqualify_reason)
             VALUES
                (:reservation_id, :vehicle_id, :score,
                 :capacity_score, :cargo_score, :availability_score,
                 :purpose_fit_score, :maintenance_score,
                 :disqualified, :disqualify_reason)',
            [
                ':reservation_id'     => $reservationId,
                ':vehicle_id'         => $vehicleId,
                ':score'              => $score,
                ':capacity_score'     => $subScores['capacity']     ?? null,
                ':cargo_score'        => $subScores['cargo']        ?? null,
                ':availability_score' => $subScores['availability'] ?? null,
                ':purpose_fit_score'  => $subScores['purpose_fit']  ?? null,
                ':maintenance_score'  => $subScores['maintenance']  ?? null,
                ':disqualified'       => $disqualified ? 1 : 0,
                ':disqualify_reason'  => $disqualifyReason,
            ]
        );
    }

    /**
     * Return all log rows for a reservation, joined with vehicle and
     * category info. Non-disqualified vehicles come first, ordered by
     * score descending so the best recommendation appears at the top.
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
             ORDER BY l.disqualified ASC, l.score DESC',
            [':reservation_id' => $reservationId]
        );
    }
}
