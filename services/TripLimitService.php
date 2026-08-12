<?php

/**
 * TripLimitService
 *
 * Enforces trip_purposes.max_per_project — the maximum number of
 * active reservations allowed for a given purpose on a single project.
 *
 * Called from ReservationController::store() immediately before INSERT,
 * whenever a project was selected — the reservation form offers the
 * project field for every purpose, not only those with
 * requires_project = 1, so an optional project is capped just the same.
 *
 * Usage in controller:
 *   $error = TripLimitService::check($projectId, $purposeId);
 *   if ($error !== null) {
 *       Helpers::setFlash('error', $error);
 *       Helpers::redirect('/reservations/create');
 *   }
 *
 * Returns null  → within limit, proceed with INSERT
 * Returns string → limit exceeded, flash this message and block
 */
class TripLimitService
{
    /**
     * Check whether a new reservation would exceed max_per_project.
     *
     * @param  int         $projectId  The selected project.
     * @param  int         $purposeId  The selected trip purpose.
     * @return string|null null if allowed; error message string if blocked.
     */
    public static function check(int $projectId, int $purposeId): ?string
    {
        $purposeModel = new TripPurposeModel();
        $purpose      = $purposeModel->findById($purposeId);

        // Purpose not found or no limit configured → always allow
        if (!$purpose || $purpose['max_per_project'] === null) {
            return null;
        }

        $max = (int) $purpose['max_per_project'];

        $reservationModel = new ReservationModel();
        $count            = $reservationModel->countByProjectAndPurpose($projectId, $purposeId);

        if ($count >= $max) {
            return sprintf(
                'This project has reached the maximum of %d %s reservation%s allowed. '
                . 'Only rejected or cancelled reservations are excluded from this count.',
                $max,
                $purpose['purpose_name'],
                $max === 1 ? '' : 's'
            );
        }

        return null;
    }
}
