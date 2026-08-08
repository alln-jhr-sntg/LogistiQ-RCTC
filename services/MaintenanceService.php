<?php

/**
 * MaintenanceService
 *
 * Checks a vehicle's odometer against its scheduled next service km
 * and dispatches notifications to all admins when the threshold is crossed.
 *
 * Called from two places:
 *   TripController::complete()         — automatic, every trip completion
 *   VehicleController::checkMaintenance() — manual, from the vehicle maintenance page
 *
 * Both callers handle the return value differently:
 *   - TripController wraps the call in try-catch and ignores the return value.
 *     A failure here must never block trip completion.
 *   - VehicleController uses the return value to flash an appropriate message.
 *
 * Decision 3 (Pre-Step): hooked into TripController::complete() — the only
 * guaranteed moment the new odometer reading is in the DB.
 */
class MaintenanceService
{
    /**
     * Check a vehicle's odometer against its next scheduled service and
     * send maintenance notifications if thresholds are crossed.
     *
     * Deduplication guard: if a maintenance notification for this vehicle
     * was already sent within the last 24 hours, skip all inserts. This
     * prevents notification spam from repeated manual button clicks or
     * multiple trips completing in one day.
     *
     * @param  int   $vehicleId       The vehicle to check.
     * @param  float $currentOdometer The odometer reading after the trip
     *                                (already written to vehicles table by caller).
     *
     * @return string Status code — use this to determine what to flash:
     *   'skipped_no_baseline'  No maintenance record exists; nothing to compare.
     *   'skipped_dedup'        Notification already sent within last 24 hours.
     *   'ok'                   Odometer is within the safe interval; no action.
     *   'notified_overdue'     Vehicle is at or past service km; admins notified.
     *   'notified_due_soon'    Vehicle is within 500 km of service; admins notified.
     */
    public static function checkAfterTrip(int $vehicleId, float $currentOdometer): string
    {
        // ── 1. Fetch latest maintenance record ──────────────────
        $maintModel = new VehicleMaintenanceModel();
        $latest     = $maintModel->getLatestByVehicle($vehicleId);

        if (!$latest || $latest['next_service_km'] === null) {
            // No maintenance history or no next_service_km set — nothing to compare.
            // This is expected for new vehicles that haven't had their first service logged.
            return 'skipped_no_baseline';
        }

        $nextServiceKm = (float) $latest['next_service_km'];

        // ── 2. Deduplication check ──────────────────────────────
        $notifModel = new NotificationModel();
        if ($notifModel->hasRecentMaintenance($vehicleId)) {
            return 'skipped_dedup';
        }

        // ── 3. Threshold evaluation ─────────────────────────────
        if ($currentOdometer >= $nextServiceKm) {
            $notificationType = 'notified_overdue';
            $title            = 'Vehicle Due for Service';
        } elseif ($currentOdometer >= $nextServiceKm - 500) {
            $notificationType = 'notified_due_soon';
            $title            = 'Vehicle Service Due Soon';
        } else {
            // Odometer is still comfortably within the service window.
            return 'ok';
        }

        // ── 4. Build notification message ───────────────────────
        // Fetch the vehicle plate for the human-readable message.
        $vehicleModel = new VehicleModel();
        $vehicle      = $vehicleModel->findById($vehicleId);
        $plate        = $vehicle['plate_number'] ?? ('Vehicle #' . $vehicleId);

        if ($notificationType === 'notified_overdue') {
            $message = $plate . ' has reached its service interval at '
                . number_format($currentOdometer, 0) . ' km. Schedule maintenance immediately.';
        } else {
            $message = $plate . ' is approaching its service interval — '
                . 'due at ' . number_format($nextServiceKm, 0) . ' km, '
                . 'currently at ' . number_format($currentOdometer, 0) . ' km.';
        }

        // ── 5. Notify all super_admins and admins ───────────────
        $userModel   = new UserModel();
        $superAdmins = array_column($userModel->findByRole(ROLE_SUPER_ADMIN), 'user_id');
        $admins      = array_column($userModel->findByRole(ROLE_ADMIN), 'user_id');
        $recipients  = array_unique(array_merge($superAdmins, $admins));

        if (!empty($recipients)) {
            $notifModel->createForUsers($recipients, [
                'title'          => $title,
                'message'        => $message,
                'type'           => 'maintenance',
                'reference_id'   => $vehicleId,
                'reference_type' => 'maintenance',
            ]);
        }

        return $notificationType;
    }
}
