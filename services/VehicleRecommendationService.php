<?php

/**
 * VehicleRecommendationService
 *
 * Rule-based weighted scoring engine that recommends the most suitable
 * vehicle for a given reservation request.
 *
 * Two-phase process:
 *   Phase 1 — Hard disqualifiers: passenger capacity, cargo capacity,
 *             schedule conflict, weight coding. Failing ANY disqualifier
 *             removes the vehicle from consideration.
 *   Phase 2 — Soft scoring: five criteria each weighted equally at 0.20.
 *             Score range: 0.00–1.00.
 *
 * Every candidate vehicle (disqualified or scored) is written to
 * ai_recommendation_logs for audit and future ML training.
 *
 * Called once per reservation from ReservationController::review()
 * when the admin opens the review form for the first time
 * (guarded by ai_recommended_vehicle_id being null on the reservation).
 */
class VehicleRecommendationService
{
    /**
     * Equal weights across all five scoring criteria. Sum = 1.0.
     * Each factor contributes equally to the recommendation — defensible
     * and simple to explain to panelists.
     */
    private const WEIGHTS = [
        'capacity'     => 0.20,
        'cargo'        => 0.20,
        'availability' => 0.20,
        'purpose_fit'  => 0.20,
        'maintenance'  => 0.20,
    ];

    /**
     * Run the recommendation engine for a given reservation.
     *
     * @param  array<string, mixed> $reservation  Full reservation row from
     *                                             ReservationModel::findById().
     *                                             Must include purpose_id
     *                                             (present on reservations table).
     * @return int|null  The recommended vehicle_id, or null if no candidates pass
     *                   Phase 1 disqualification.
     */
    public static function recommend(array $reservation): ?int
    {
        $vehicleModel = new VehicleModel();
        $logModel     = new AiRecommendationLogModel();
        $maintModel   = new VehicleMaintenanceModel();

        // The purpose this reservation is for. PDO returns integers as strings,
        // so cast explicitly — this value is compared with intval'd CSV entries.
        $reservationPurposeId = (int) $reservation['purpose_id'];

        // Candidate pool: 'available' + 'reserved' vehicles.
        // 'reserved' vehicles are included because they may be free during
        // a different date window — the Phase 1 date-overlap check below
        // determines actual scheduling conflicts, not the status column.
        // Excluded: 'on_trip', 'under_maintenance', 'retired'.
        $vehicles      = $vehicleModel->findForRecommendation();
        $bestVehicleId = null;
        $bestScore     = -1.0;

        foreach ($vehicles as $vehicle) {
            $vehicleId = (int) $vehicle['vehicle_id'];

            // ── Phase 1: Hard disqualifiers ──────────────────────────

            $disqualified     = false;
            $disqualifyReason = null;

            // a) Passenger capacity
            if ((int) $vehicle['passenger_capacity'] < (int) $reservation['passenger_count']) {
                $disqualified     = true;
                $disqualifyReason = 'Insufficient passenger capacity ('
                    . $vehicle['passenger_capacity'] . ' seats < '
                    . $reservation['passenger_count'] . ' requested)';
            }

            // b) Cargo capacity
            if (!$disqualified
                && (float) $vehicle['cargo_capacity_kg'] < (float) $reservation['cargo_weight_kg']) {
                $disqualified     = true;
                $disqualifyReason = 'Insufficient cargo capacity ('
                    . $vehicle['cargo_capacity_kg'] . ' kg < '
                    . $reservation['cargo_weight_kg'] . ' kg requested)';
            }

            // c) Schedule conflict (strict inequalities — 5pm end does not
            //    conflict with 5pm start on a different reservation)
            if (!$disqualified && $vehicleModel->hasConflictingReservation(
                $vehicleId,
                $reservation['departure_datetime'],
                $reservation['return_datetime']
            )) {
                $disqualified     = true;
                $disqualifyReason = 'Schedule conflict with an existing approved reservation';
            }

            // d) Weight coding
            if (!$disqualified && WeightCodingService::isDisqualified($vehicle)) {
                $disqualified     = true;
                $disqualifyReason = WeightCodingService::disqualifyReason();
            }

            if ($disqualified) {
                $logModel->log(
                    (int) $reservation['reservation_id'],
                    $vehicleId,
                    0.0, [], true, $disqualifyReason
                );
                continue;
            }

            // ── Phase 2: Soft scoring ─────────────────────────────────

            // a) Capacity score — prefer right-sized vehicles over grossly oversized ones.
            //    Ratio of requested/available: 1.0 = perfect fit, lower = overkill.
            $paxScore = min(1.0,
                (int) $reservation['passenger_count']
                / max(1, (int) $vehicle['passenger_capacity'])
            );

            // b) Cargo score — same ratio logic as capacity.
            //    No cargo requested → full score (any vehicle works).
            $cargoReq   = (float) $reservation['cargo_weight_kg'];
            $cargoCap   = (float) $vehicle['cargo_capacity_kg'];
            $cargoScore = ($cargoReq == 0.0)
                ? 1.0
                : (($cargoCap == 0.0) ? 0.0 : min(1.0, $cargoReq / $cargoCap));

            // c) Availability — 1.0 for all vehicles that passed Phase 1.
            //    The schedule conflict check already eliminates unavailable ones.
            $availScore = 1.0;

            // d) Purpose fit — check preferred_purpose_ids on the vehicle itself.
            //
            //    Design rationale: vehicles in the same category have meaningfully
            //    different actual use profiles (e.g. Crosswind → Delivery + Site Visit,
            //    Mazda Sedan → Site Visit only). Category-level preference on
            //    trip_purposes was too coarse; this gives per-vehicle accuracy.
            //
            //    Scoring:
            //      NULL / empty list → 0.5 neutral (no preference set, no penalty)
            //      reservation's purpose_id is in the vehicle's list → 1.0
            //      reservation's purpose_id is not in the list → 0.0
            $rawPref        = $vehicle['preferred_purpose_ids'] ?? '';
            $prefPurposeIds = ($rawPref !== null && $rawPref !== '')
                ? array_filter(array_map('intval', explode(',', (string) $rawPref)))
                : [];

            if (empty($prefPurposeIds)) {
                $purposeFitScore = 0.5;
            } elseif (in_array($reservationPurposeId, $prefPurposeIds, true)) {
                $purposeFitScore = 1.0;
            } else {
                $purposeFitScore = 0.0;
            }

            // e) Maintenance score — km remaining to next scheduled service.
            //    No maintenance baseline → neutral 0.5.
            //    At/past due → 0.0. Just serviced (≥5000 km remaining) → 1.0.
            $latest = $maintModel->getLatestByVehicle($vehicleId);
            if (!$latest || $latest['next_service_km'] === null) {
                $maintScore = 0.5;
            } else {
                $kmRemaining = (float) $latest['next_service_km']
                             - (float) $vehicle['current_odometer_km'];
                $maintScore  = ($kmRemaining <= 0)
                    ? 0.0
                    : min(1.0, $kmRemaining / 5000.0);
            }

            $subScores = [
                'capacity'     => round($paxScore,       2),
                'cargo'        => round($cargoScore,      2),
                'availability' => round($availScore,      2),
                'purpose_fit'  => round($purposeFitScore, 2),
                'maintenance'  => round($maintScore,      2),
            ];

            $finalScore = 0.0;
            foreach (self::WEIGHTS as $key => $weight) {
                $finalScore += ($subScores[$key] ?? 0.0) * $weight;
            }
            $finalScore = round($finalScore, 2);

            $logModel->log(
                (int) $reservation['reservation_id'],
                $vehicleId,
                $finalScore,
                $subScores,
                false,
                null
            );

            if ($finalScore > $bestScore) {
                $bestScore     = $finalScore;
                $bestVehicleId = $vehicleId;
            }
        }

        return $bestVehicleId;
    }
}
