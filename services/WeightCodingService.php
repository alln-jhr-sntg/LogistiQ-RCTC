<?php

/**
 * WeightCodingService
 *
 * Checks Philippine LTO weight coding compliance for candidate vehicles.
 * Used as a hard disqualifier in Phase 1 of VehicleRecommendationService.
 *
 * Simplified rule for capstone scope:
 *   Vehicles with GVWR > 4,500 kg are disqualified.
 *   This corresponds to the LTO Class 3 vehicle threshold for
 *   standard city-road access under Philippine weight coding rules.
 *
 * In a production system this would also check:
 *   - Day-of-week coding by plate end digit
 *   - Route-specific limits (EDSA, C5, etc.)
 *   - Seasonal exemptions
 * These are out of scope for the capstone.
 */
class WeightCodingService
{
    /**
     * GVWR threshold in kilograms.
     * Vehicles above this are considered weight-restricted.
     */
    private const GVWR_LIMIT_KG = 4500.0;

    /**
     * Return true if the vehicle should be disqualified due to
     * weight coding restrictions.
     *
     * @param array<string, mixed> $vehicle  Row from the vehicles table.
     */
    public static function isDisqualified(array $vehicle): bool
    {
        return (float) $vehicle['gross_weight_kg'] > self::GVWR_LIMIT_KG;
    }

    /**
     * Human-readable reason for disqualification.
     * Written to ai_recommendation_logs.disqualify_reason.
     */
    public static function disqualifyReason(): string
    {
        return 'Vehicle GVWR exceeds ' . self::GVWR_LIMIT_KG . ' kg LTO weight coding limit';
    }
}
