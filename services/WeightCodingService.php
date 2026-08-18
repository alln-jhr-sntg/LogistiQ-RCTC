<?php

/**
 * WeightCodingService
 *
 * Models Philippine LTO weight coding for the recommendation engine as a
 * Phase 2 SOFT criterion (weight_coding, config/constants.php::RECOMMENDATION_WEIGHTS),
 * not a hard disqualifier. Previously any vehicle with gross_weight_kg over
 * the GVWR threshold was excluded outright regardless of the trip's actual
 * schedule — which meant every truck in the fleet was permanently
 * unrecommendable, and a heavy-cargo reservation reliably landed on
 * "No Eligible Vehicles" even on a Sunday.
 *
 * The real LTO truck ban only restricts city-road access during specific
 * weekday hours (TRUCK_BAN_DAYS / TRUCK_BAN_WINDOWS in config/constants.php).
 * So a vehicle over the GVWR limit is only penalized in proportion to how
 * much of the REQUESTED window actually falls inside a ban period:
 *   - Vehicle at or under the GVWR limit               -> null (N/A)
 *   - Over the limit, but the window never touches a
 *     ban period (e.g. a Sunday trip, or a weekday trip
 *     entirely between ban windows)                     -> null (N/A)
 *   - Over the limit AND the window overlaps a ban
 *     period                                             -> 0-100, proportional
 *
 * null is a deliberate "this criterion doesn't apply to this vehicle for
 * this trip" signal, not a score of zero — VehicleRecommendationService
 * drops null sub-scores from the weighted average entirely rather than
 * counting them against the vehicle.
 *
 * In a production system this would also check route-specific limits
 * (EDSA, C5, etc.) and seasonal exemptions. Out of scope for the capstone.
 */
class WeightCodingService
{
    /**
     * Score how much the requested window is affected by the LTO truck ban
     * for this vehicle, or null if the criterion doesn't apply.
     *
     * @param array<string, mixed> $vehicle  Row from the vehicles table —
     *                                       must include gross_weight_kg.
     */
    public static function score(array $vehicle, string $departureDatetime, string $returnDatetime): ?int
    {
        $gvwr = (float) ($vehicle['gross_weight_kg'] ?? 0);
        if ($gvwr <= TRUCK_BAN_GVWR_KG) {
            return null;
        }

        $windowStart = strtotime($departureDatetime);
        $windowEnd   = strtotime($returnDatetime);
        if ($windowStart === false || $windowEnd === false || $windowEnd <= $windowStart) {
            return null;
        }

        $bannedSeconds = self::bannedSecondsInWindow($windowStart, $windowEnd);
        if ($bannedSeconds <= 0) {
            // Window never touches a ban period — not a live restriction
            // for this specific trip, so the criterion doesn't apply.
            return null;
        }

        $windowSeconds = $windowEnd - $windowStart;
        $score         = 100 * (1 - $bannedSeconds / $windowSeconds);

        return (int) round(max(0, min(100, $score)));
    }

    /**
     * Sum of seconds within [$windowStart, $windowEnd) that fall inside a
     * TRUCK_BAN_WINDOWS period on a TRUCK_BAN_DAYS day. Walks the window
     * day by day so it handles multi-day windows and ones that cross
     * midnight correctly.
     */
    private static function bannedSecondsInWindow(int $windowStart, int $windowEnd): int
    {
        $banned   = 0;
        $dayStart = strtotime('midnight', $windowStart);

        while ($dayStart < $windowEnd) {
            $dayOfWeek = (int) date('N', $dayStart); // 1 (Mon) .. 7 (Sun)

            if (in_array($dayOfWeek, TRUCK_BAN_DAYS, true)) {
                $dateStr = date('Y-m-d', $dayStart);
                foreach (TRUCK_BAN_WINDOWS as [$from, $to]) {
                    $banStart = strtotime("$dateStr $from");
                    $banEnd   = strtotime("$dateStr $to");

                    $overlapStart = max($windowStart, $banStart);
                    $overlapEnd   = min($windowEnd, $banEnd);
                    if ($overlapEnd > $overlapStart) {
                        $banned += $overlapEnd - $overlapStart;
                    }
                }
            }

            $dayStart = strtotime('+1 day', $dayStart);
        }

        return $banned;
    }
}
