<?php

/**
 * VehicleRecommendationService
 *
 * Rule-based weighted scoring engine that recommends the most suitable
 * vehicle for a given reservation request.
 *
 * Two-phase process:
 *   Phase 1 — Hard disqualifiers: passenger capacity, cargo capacity,
 *             schedule conflict. Failing ANY disqualifier removes the
 *             vehicle from consideration; ALL failing reasons are recorded
 *             (not just the first), so the disqualified-vehicles list in
 *             the review UI never hides a second or third reason behind
 *             whichever check happened to run first.
 *   Phase 2 — Soft scoring across six weighted criteria
 *             (config/constants.php::RECOMMENDATION_WEIGHTS). A criterion
 *             that doesn't apply to this vehicle/request pair (e.g. no
 *             cargo requested, no maintenance baseline yet) contributes a
 *             null sub-score and is dropped from BOTH the numerator and
 *             the weight total for that vehicle, rather than being counted
 *             as a zero — see the aggregation step below. Final scores are
 *             integers 0-100 (config/constants.php::SCORE_MAX).
 *
 * Weight coding (GVWR / LTO truck ban) moved from a Phase 1 hard
 * disqualifier to a Phase 2 weighted criterion — see WeightCodingService's
 * docblock for why an unconditional exclusion made every truck in the
 * fleet permanently unrecommendable.
 *
 * Every candidate vehicle (disqualified or scored) is written to
 * ai_recommendation_logs for audit and future ML training. A run replaces
 * any prior log rows for the same reservation (AiRecommendationLogModel::
 * deleteByReservation()) so a re-run can never accumulate duplicate rows.
 *
 * Called once per reservation from ReservationController::review(), guarded
 * by AiRecommendationLogModel::hasRunFor() — see that model's docblock for
 * why the guard is no longer reservations.ai_recommended_vehicle_id IS NULL.
 */
class VehicleRecommendationService
{
    /**
     * Run the recommendation engine for a given reservation.
     *
     * @param  array<string, mixed> $reservation  Full reservation row from
     *                                             ReservationModel::findById().
     *                                             Must include reservation_id,
     *                                             purpose_id, passenger_count,
     *                                             cargo_weight_kg,
     *                                             departure_datetime,
     *                                             return_datetime.
     * @return array{vehicle_id:int, score:int, label:string}|null  The
     *         winning vehicle, or null if no candidate passed Phase 1.
     */
    public static function recommend(array $reservation): ?array
    {
        $weightSum = array_sum(RECOMMENDATION_WEIGHTS);
        if ($weightSum !== SCORE_MAX) {
            throw new LogicException(
                "RECOMMENDATION_WEIGHTS must sum to SCORE_MAX ($weightSum given, expected " . SCORE_MAX . ')'
            );
        }

        $vehicleModel = new VehicleModel();
        $logModel     = new AiRecommendationLogModel();
        $maintModel   = new VehicleMaintenanceModel();
        $settingModel = new SystemSettingModel();

        $reservationId = (int) $reservation['reservation_id'];
        // PDO returns integers as strings, so cast explicitly — this value
        // is compared with intval'd CSV entries.
        $reservationPurposeId = (int) $reservation['purpose_id'];
        $passengerCount       = (int) $reservation['passenger_count'];
        $cargoWeight          = (float) $reservation['cargo_weight_kg'];
        $departure            = (string) $reservation['departure_datetime'];
        $return               = (string) $reservation['return_datetime'];

        $maintenanceInterval = (float) ($settingModel->getByKey('maintenance_interval_km') ?? MAINTENANCE_INTERVAL_KM);
        if ($maintenanceInterval <= 0) {
            $maintenanceInterval = MAINTENANCE_INTERVAL_KM;
        }

        // Candidate pool: 'available' + 'reserved' vehicles.
        // 'reserved' vehicles are included because they may be free during
        // a different date window — the Phase 1 date-overlap check below
        // determines actual scheduling conflicts, not the status column.
        // Excluded: 'on_trip', 'under_maintenance', 'retired'.
        $vehicles = $vehicleModel->findForRecommendation();

        $db              = Database::getInstance();
        $ownsTransaction = !$db->inTransaction();
        if ($ownsTransaction) {
            $db->beginTransaction();
        }

        try {
            // Replace any prior run for this reservation — see class docblock.
            $logModel->deleteByReservation($reservationId);

            $best = null; // ['vehicle_id', 'score', 'odometer', 'label']

            foreach ($vehicles as $vehicle) {
                $vehicleId = (int) $vehicle['vehicle_id'];

                // ── Phase 1: Hard disqualifiers ──────────────────────
                // Collect every failing reason rather than stopping at the
                // first, so the disqualified-vehicles list is accurate even
                // when a vehicle fails more than one check.

                $reasons = [];

                if ((int) $vehicle['passenger_capacity'] < $passengerCount) {
                    $reasons[] = 'Insufficient passenger capacity ('
                        . $vehicle['passenger_capacity'] . ' seats < '
                        . $passengerCount . ' requested)';
                }

                if ($cargoWeight > (float) $vehicle['cargo_capacity_kg']) {
                    $reasons[] = 'Insufficient cargo capacity ('
                        . $vehicle['cargo_capacity_kg'] . ' kg < '
                        . $cargoWeight . ' kg requested)';
                }

                // Strict inequalities — 5pm end does not conflict with 5pm
                // start on a different reservation.
                if ($vehicleModel->hasConflictingReservation($vehicleId, $departure, $return, $reservationId)) {
                    $reasons[] = 'Schedule conflict with an existing held reservation';
                }

                if (!empty($reasons)) {
                    $logModel->log($reservationId, $vehicleId, 0, [], true, implode('; ', $reasons));
                    continue;
                }

                // ── Phase 2: Soft scoring ─────────────────────────────

                // a) Capacity — penalize excess seats rather than scoring a
                //    raw requested/available ratio, which cratered for
                //    small parties on a big vehicle regardless of fit
                //    (one passenger scored 0.07 on a 15-seater). Null when
                //    no passengers were requested — nothing to score.
                if ($passengerCount <= 0) {
                    $capacityScore = null;
                } else {
                    $excessSeats   = (int) $vehicle['passenger_capacity'] - $passengerCount;
                    $capacityScore = (int) round(100 * max(0, 1 - $excessSeats / CAPACITY_EXCESS_SEATS_FULL_PENALTY));
                }

                // b) Cargo — ratio of requested/available. Null when no
                //    cargo was requested. Phase 1 already guarantees
                //    cargo_capacity_kg >= cargoWeight whenever cargoWeight
                //    is positive, so the denominator can't be zero here.
                $cargoScore = $cargoWeight <= 0
                    ? null
                    : (int) round(100 * min(1, $cargoWeight / (float) $vehicle['cargo_capacity_kg']));

                // c) Schedule — real headroom around the requested window,
                //    replacing the old 'availability' criterion, which was
                //    a hard-coded 1.0 for every candidate (a real conflict
                //    already fails Phase 1, so it never discriminated
                //    between candidates at all).
                $gapHours      = $vehicleModel->nearestBookingGapHours($vehicleId, $departure, $return, $reservationId);
                $scheduleScore = $gapHours === null
                    ? 100
                    : (int) round(100 * min(1, $gapHours / SCHEDULE_BUFFER_HOURS));

                // d) Purpose fit — check preferred_purpose_ids on the
                //    vehicle itself.
                //
                //    Design rationale: vehicles in the same category have
                //    meaningfully different actual use profiles (e.g.
                //    Crosswind -> Delivery + Site Visit, Mazda Sedan ->
                //    Site Visit only). Category-level preference on
                //    trip_purposes was too coarse; this gives per-vehicle
                //    accuracy.
                //
                //    Scoring: NULL/empty list -> null (no preference set,
                //    no penalty); reservation's purpose_id in the vehicle's
                //    list -> 100; not in the list -> 0.
                $rawPref        = $vehicle['preferred_purpose_ids'] ?? '';
                $prefPurposeIds = ($rawPref !== null && $rawPref !== '')
                    ? array_filter(array_map('intval', explode(',', (string) $rawPref)))
                    : [];

                $purposeFitScore = empty($prefPurposeIds)
                    ? null
                    : (in_array($reservationPurposeId, $prefPurposeIds, true) ? 100 : 0);

                // e) Maintenance — km remaining to next scheduled service,
                //    against the current maintenance_interval_km setting
                //    (falling back to MAINTENANCE_INTERVAL_KM) rather than
                //    a hard-coded 5000, so changing the setting doesn't
                //    silently disagree with the engine.
                //    No maintenance baseline -> null. At/past due -> 0.
                //    Just serviced (>= one full interval remaining) -> 100.
                $latest = $maintModel->getLatestByVehicle($vehicleId);
                if (!$latest) {
                    $maintenanceScore = null;
                } else {
                    $kmRemaining      = (float) $latest['next_service_km'] - (float) $vehicle['current_odometer_km'];
                    $maintenanceScore = $kmRemaining <= 0
                        ? 0
                        : (int) round(100 * min(1, $kmRemaining / $maintenanceInterval));
                }

                // f) Weight coding — see WeightCodingService's docblock.
                $weightCodingScore = WeightCodingService::score($vehicle, $departure, $return);

                $subScores = [
                    'capacity'      => $capacityScore,
                    'cargo'         => $cargoScore,
                    'schedule'      => $scheduleScore,
                    'purpose_fit'   => $purposeFitScore,
                    'maintenance'   => $maintenanceScore,
                    'weight_coding' => $weightCodingScore,
                ];

                // Weighted mean over applicable criteria only — a null
                // sub-score drops both its numerator contribution AND its
                // weight from the denominator, so (for example) a
                // cargo-only trip isn't dragged down by an inapplicable
                // capacity criterion, and vice versa.
                $weightedSum  = 0.0;
                $appliedWeight = 0;
                foreach (RECOMMENDATION_WEIGHTS as $key => $weight) {
                    if ($subScores[$key] !== null) {
                        $weightedSum   += $subScores[$key] * $weight;
                        $appliedWeight += $weight;
                    }
                }
                $finalScore = $appliedWeight > 0 ? (int) round($weightedSum / $appliedWeight) : 0;

                $logModel->log($reservationId, $vehicleId, $finalScore, $subScores, false, null);

                $odometer = (float) $vehicle['current_odometer_km'];

                // Deterministic tie-break: higher score wins; on an exact
                // tie prefer the lower-odometer vehicle (balances fleet
                // utilization), then the lower vehicle_id. Iteration order
                // is v.plate_number ASC (VehicleModel::findForRecommendation()),
                // which used to be the de-facto tie-break by accident.
                if ($best === null
                    || $finalScore > $best['score']
                    || ($finalScore === $best['score'] && $odometer < $best['odometer'])
                    || ($finalScore === $best['score'] && $odometer === $best['odometer'] && $vehicleId < $best['vehicle_id'])
                ) {
                    $best = [
                        'vehicle_id' => $vehicleId,
                        'score'      => $finalScore,
                        'odometer'   => $odometer,
                        'label'      => $vehicle['plate_number'] . ' — ' . $vehicle['brand'] . ' ' . $vehicle['model'],
                    ];
                }
            }

            if ($ownsTransaction) {
                $db->commit();
            }
        } catch (Throwable $e) {
            if ($ownsTransaction) {
                $db->rollBack();
            }
            throw $e;
        }

        return $best === null ? null : [
            'vehicle_id' => $best['vehicle_id'],
            'score'      => $best['score'],
            'label'      => $best['label'],
        ];
    }
}
