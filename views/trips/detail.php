<?php
$isAdmin    = Auth::isAdminOrAbove();
$isDriver   = Auth::isDriver();
$isEmployee = Auth::isEmployee();

$isPendingStart = $trip['trip_status'] === 'pending_start';
$isInProgress   = $trip['trip_status'] === 'in_progress';
$isIncident     = $trip['trip_status'] === 'incident';
$isCancelled    = $trip['trip_status'] === 'cancelled';
$isTerminal     = in_array($trip['trip_status'], TRIP_TERMINAL_STATUSES, true);
$isTracking     = in_array($trip['trip_status'], TRIP_TRACKING_STATUSES, true);

$badgeClass = match($trip['trip_status']) {
    'pending_start' => 'badge-pending',
    'in_progress'   => 'badge-in-progress',
    'completed'     => 'badge-completed',
    'incident'      => 'badge-rejected',
    'cancelled'     => 'badge-cancelled',
    default         => 'badge-pending',
};
$statusLabel = match($trip['trip_status']) {
    'pending_start' => 'Pending Start',
    'in_progress'   => 'In Progress',
    'completed'     => 'Completed',
    'incident'      => 'Incident',
    'cancelled'     => 'Cancelled',
    default         => ucfirst($trip['trip_status']),
};

// Prefer returning to wherever they actually came from; fall back to the
// role-based default when there's no usable same-site referer (direct link,
// bookmark, referer stripped by browser privacy settings, etc.)
$referer     = $_SERVER['HTTP_REFERER'] ?? '';
$refererPath = $referer !== '' ? (parse_url($referer, PHP_URL_PATH) ?? '') : '';
$isSameSite  = str_starts_with($refererPath, APP_BASE . '/index.php');

$backUrl = $isSameSite
    ? $referer
    : ($isEmployee
        ? Helpers::url('/reservations/' . (int) $trip['reservation_id'])
        : Helpers::url('/trips'));
?>

<div class="page-header">
    <div class="page-header-left">
        <span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
    </div>
    <div class="page-header-actions">
        <a href="<?= $backUrl ?>" class="btn btn-outline">← Back</a>
        <?php if ($isAdmin && $isTracking): ?>
            <a href="<?= Helpers::url('/trips/' . (int) $trip['trip_id'] . '/map') ?>"
               class="btn btn-solid">Live Map</a>
        <?php endif; ?>
        <?php if ($isAdmin && $isIncident): ?>
            <button type="button" class="btn btn-danger"
                    onclick="document.getElementById('cancelTripModal').style.display='flex';">
                Cancel Trip
            </button>
        <?php endif; ?>
    </div>
</div>

<?php
/* Cards are captured in priority order, then split by count (not fixed
   left/right slots) so both columns stay balanced across every
   role/status combo — an employee only ever gets Trip Info + Vehicle&Driver
   + My Notes, which used to leave "My Notes" stranded alone under Trip
   Info while Vehicle & Driver sat with nothing beside it. See
   views/reservations/detail.php for the same pattern. */
$cards = [];

ob_start(); ?>
    <div class="detail-card">
        <div class="detail-card-title">Trip Information</div>
        <div class="detail-field">
            <div class="detail-field-label">Reservation</div>
            <div class="detail-field-value">
                <a href="<?= Helpers::url('/reservations/' . (int) $trip['reservation_id']) ?>">
                    <?= Helpers::e($trip['reservation_code']) ?>
                </a>
            </div>
        </div>
        <div class="detail-field">
            <div class="detail-field-label">Purpose</div>
            <div class="detail-field-value"><?= Helpers::e($trip['purpose_name']) ?></div>
        </div>
        <div class="detail-field">
            <div class="detail-field-label">Destination</div>
            <div class="detail-field-value"><?= Helpers::e($trip['destination']) ?></div>
        </div>
        <div class="detail-field">
            <div class="detail-field-label">Scheduled Departure</div>
            <div class="detail-field-value"><?= date('M d Y, g:i A', strtotime($trip['departure_datetime'])) ?></div>
        </div>
        <div class="detail-field">
            <div class="detail-field-label">Scheduled Return</div>
            <div class="detail-field-value"><?= date('M d Y, g:i A', strtotime($trip['return_datetime'])) ?></div>
        </div>
        <div class="detail-field">
            <div class="detail-field-label">Actual Departure</div>
            <div class="detail-field-value">
                <?= $trip['actual_departure']
                    ? date('M d Y, g:i A', strtotime($trip['actual_departure']))
                    : '<span class="td-muted">— Not yet started</span>' ?>
            </div>
        </div>
        <div class="detail-field">
            <div class="detail-field-label">Actual Return</div>
            <div class="detail-field-value">
                <?= $trip['actual_return']
                    ? date('M d Y, g:i A', strtotime($trip['actual_return']))
                    : '<span class="td-muted">— Still on trip</span>' ?>
            </div>
        </div>
    </div>
<?php $cards[] = ob_get_clean();

ob_start(); ?>
    <div class="detail-card">
        <div class="detail-card-title">Vehicle &amp; Driver</div>
        <div class="detail-field">
            <div class="detail-field-label">Vehicle</div>
            <div class="detail-field-value">
                <?= Helpers::e($trip['plate_number']) ?> —
                <?= Helpers::e($trip['vehicle_brand'] . ' ' . $trip['vehicle_model']) ?>
            </div>
        </div>
        <div class="detail-field">
            <div class="detail-field-label">Driver</div>
            <div class="detail-field-value">
                <?= Helpers::e($trip['driver_first_name'] . ' ' . $trip['driver_last_name']) ?>
                <?php if ($trip['driver_phone'] && !$isEmployee): ?>
                    <br><span class="td-muted"><?= Helpers::e($trip['driver_phone']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="detail-field">
            <div class="detail-field-label">Passengers</div>
            <div class="detail-field-value"><?= (int) $trip['passenger_count'] ?></div>
        </div>
        <div class="detail-field">
            <div class="detail-field-label">Cargo</div>
            <div class="detail-field-value">
                <?= $trip['cargo_weight_kg'] > 0
                    ? number_format((float) $trip['cargo_weight_kg'], 2) . ' kg'
                        . ($trip['cargo_description'] ? ' — ' . Helpers::e($trip['cargo_description']) : '')
                    : '—' ?>
            </div>
        </div>
        <?php if (!$isEmployee): ?>
        <div class="detail-field">
            <div class="detail-field-label">Odometer Start</div>
            <div class="detail-field-value">
                <?= $trip['odometer_start_km'] !== null
                    ? number_format((float) $trip['odometer_start_km'], 2) . ' km'
                    : '<span class="td-muted">— Not yet recorded</span>' ?>
            </div>
        </div>
        <div class="detail-field">
            <div class="detail-field-label">Odometer End</div>
            <div class="detail-field-value">
                <?php if ($trip['odometer_end_km'] !== null): ?>
                    <?= number_format((float) $trip['odometer_end_km'], 2) ?> km
                    <span class="td-muted">
                        (<?= number_format((float)$trip['odometer_end_km'] - (float)$trip['odometer_start_km'], 0) ?> km travelled)
                    </span>
                <?php else: ?>
                    <span class="td-muted">— Not yet recorded</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="detail-field">
            <div class="detail-field-label">Department</div>
            <div class="detail-field-value">
                <?= Helpers::e($trip['company_code'] . ' — ' . $trip['department_name']) ?>
            </div>
        </div>
        <?php else: ?>
        <?php if ($trip['odometer_start_km'] !== null && $trip['odometer_end_km'] !== null): ?>
        <div class="detail-field">
            <div class="detail-field-label">Distance Travelled</div>
            <div class="detail-field-value">
                <?= number_format((float)$trip['odometer_end_km'] - (float)$trip['odometer_start_km'], 0) ?> km
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
<?php $cards[] = ob_get_clean();

// Admin Controls (admin only) — Start Trip / Complete Trip / Cancellation
// are mutually exclusive, only one of the three ever applies.
if ($isAdmin):

if ($isPendingStart):
ob_start(); ?>
    <div class="detail-card">
        <div class="detail-card-title">Start Trip</div>
        <p style="font-size:14px; color:var(--clr-text-2); margin-bottom:16px;">
            Record the starting odometer and mark the trip as in progress.
        </p>
        <form method="POST" action="<?= Helpers::url('/trips/' . (int) $trip['reservation_id'] . '/start') ?>">
            <?= Csrf::field() ?>
            <div class="form-group">
                <label class="form-label">Odometer Start (km) <span class="required">*</span></label>
                <input type="number" class="form-input" name="odometer_start_km"
                       step="0.01" min="0" required
                       value="<?= number_format((float) ($trip['vehicle_current_odometer'] ?? 0), 2, '.', '') ?>"
                       placeholder="e.g. 12450.00">
                <p class="form-hint">Pre-filled from vehicle's last recorded odometer — verify before saving</p>
            </div>
            <div class="form-actions" style="margin-top:12px;">
                <button type="submit" class="btn btn-solid">Start Trip</button>
            </div>
        </form>
    </div>
<?php $cards[] = ob_get_clean();
endif;

if ($isInProgress):
ob_start(); ?>
    <div class="detail-card">
        <div class="detail-card-title">Complete Trip</div>
        <p style="font-size:14px; color:var(--clr-text-2); margin-bottom:16px;">
            Record the ending odometer to close this trip.
            The vehicle and driver will be returned to available.
        </p>
        <form method="POST" action="<?= Helpers::url('/trips/' . (int) $trip['trip_id'] . '/complete') ?>">
            <?= Csrf::field() ?>
            <div class="form-group">
                <label class="form-label">Odometer End (km) <span class="required">*</span></label>
                <input type="number" class="form-input" name="odometer_end_km"
                       step="0.01"
                       min="<?= (float) $trip['odometer_start_km'] ?>"
                       required placeholder="e.g. 12850.00">
                <p class="form-hint">
                    Must be ≥ start reading
                    (<?= number_format((float) $trip['odometer_start_km'], 2) ?> km)
                </p>
            </div>
            <div class="form-actions" style="margin-top:12px;">
                <button type="submit" class="btn btn-solid">Mark Completed</button>
            </div>
        </form>
    </div>
<?php $cards[] = ob_get_clean();
endif;

if ($isCancelled):
ob_start(); ?>
    <div class="detail-card">
        <div class="detail-card-title">Cancellation</div>
        <div class="detail-field">
            <div class="detail-field-label">Reason</div>
            <div class="detail-field-value"><?= nl2br(Helpers::e($trip['cancellation_reason'] ?? '')) ?></div>
        </div>
    </div>
<?php $cards[] = ob_get_clean();
endif;

ob_start(); ?>
    <div class="detail-card">
        <div class="detail-card-title">Admin Notes</div>
        <?php if ($trip['admin_notes']): ?>
            <p style="font-size:14px; color:var(--clr-text); margin-bottom:12px;">
                <?= nl2br(Helpers::e($trip['admin_notes'])) ?>
            </p>
        <?php elseif ($isTerminal): ?>
            <p class="td-muted" style="font-size:14px;">No notes written.</p>
        <?php endif; ?>
        <?php if (!$isTerminal): ?>
        <form method="POST" action="<?= Helpers::url('/trips/' . (int) $trip['trip_id'] . '/notes') ?>">
            <?= Csrf::field() ?>
            <textarea class="form-textarea" name="admin_notes"
                      placeholder="Add or update admin note…"><?= Helpers::e($trip['admin_notes'] ?? '') ?></textarea>
            <button type="submit" class="btn btn-outline btn-sm btn-mt">Save Note</button>
        </form>
        <?php endif; ?>
    </div>
<?php $cards[] = ob_get_clean();

endif; /* isAdmin */

// Employee Notes: always visible to the employee; visible to admin only when
// notes exist (something to read) or trip is done (confirm empty state).
if ($isEmployee || ($isAdmin && ($trip['employee_notes'] || $isTerminal))):
ob_start(); ?>
    <div class="detail-card">
        <div class="detail-card-title"><?= $isEmployee ? 'My Notes' : 'Employee Notes' ?></div>
        <?php if ($trip['employee_notes']): ?>
            <p style="font-size:14px; color:var(--clr-text); margin-bottom:12px;">
                <?= nl2br(Helpers::e($trip['employee_notes'])) ?>
            </p>
            <?php if ($isAdmin): ?>
            <p class="form-hint">
                Submitted by <?= Helpers::e($trip['requester_first_name'] . ' ' . $trip['requester_last_name']) ?>
            </p>
            <?php endif; ?>
        <?php elseif ($isTerminal): ?>
            <p class="td-muted" style="font-size:14px;">No notes written.</p>
        <?php endif; ?>
        <?php if ($isEmployee && !$isTerminal): ?>
        <form method="POST" action="<?= Helpers::url('/trips/' . (int) $trip['trip_id'] . '/notes') ?>">
            <?= Csrf::field() ?>
            <textarea class="form-textarea" name="employee_notes"
                      placeholder="Add a note about this trip…"><?= Helpers::e($trip['employee_notes'] ?? '') ?></textarea>
            <button type="submit" class="btn btn-outline btn-sm btn-mt">Save Note</button>
        </form>
        <?php endif; ?>
    </div>
<?php $cards[] = ob_get_clean();
endif;

$mid        = (int) ceil(count($cards) / 2);
$leftCards  = array_slice($cards, 0, $mid);
$rightCards = array_slice($cards, $mid);
?>

<div class="detail-grid">
    <div class="detail-card-stack">
        <?php foreach ($leftCards as $card): ?>
        <?= $card ?>
        <?php endforeach; ?>
    </div>
    <div class="detail-card-stack">
        <?php foreach ($rightCards as $card): ?>
        <?= $card ?>
        <?php endforeach; ?>
    </div>
</div><!-- .detail-grid -->

<!-- Incidents -->
<?php if ($isAdmin || $isEmployee): ?>
<div class="section-header" style="margin-top:24px;">
    <h3 style="font-size:16px; font-weight:600; color:var(--clr-text); margin:0;">
        Incidents
        <?php if (!empty($incidents)): ?>
            <span class="badge <?= $isIncident ? 'badge-rejected' : 'badge-completed' ?>"
                  style="margin-left:8px; font-size:11px;"><?= count($incidents) ?></span>
        <?php endif; ?>
    </h3>
</div>

<?php if (!empty($incidents)): ?>
<div class="card" style="margin-top:8px;">
    <?php foreach ($incidents as $inc): ?>
    <div style="padding:16px 20px; border-bottom:1px solid var(--clr-border);">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
            <span class="badge <?= $inc['resolved'] ? 'badge-completed' : 'badge-rejected' ?>">
                <?= $inc['resolved'] ? 'Resolved' : 'Open' ?>
            </span>
            <strong style="font-size:14px;"><?= Helpers::e(ucwords(str_replace('_', ' ', $inc['incident_type']))) ?></strong>
        </div>
        <div style="font-size:12px; color:var(--clr-text-3); margin-bottom:8px;">
            <span>Occurred: <?= date('M d Y, g:i A', strtotime($inc['occurred_at'])) ?></span>
            <?php if ($isAdmin): ?>
            &nbsp;&bull;&nbsp;
            <span>Reported by <?= Helpers::e($inc['reporter_first_name'] . ' ' . $inc['reporter_last_name']) ?></span>
            <?php endif; ?>
        </div>
        <p style="font-size:14px; color:var(--clr-text); margin:0 0 8px;">
            <?= nl2br(Helpers::e($inc['description'])) ?>
        </p>
        <?php if ($inc['resolved'] && $inc['resolution_notes']): ?>
        <p style="font-size:13px; color:var(--clr-text-2); margin:0;">
            <strong>Resolution:</strong> <?= Helpers::e($inc['resolution_notes']) ?>
        </p>
        <?php endif; ?>
        <?php if ($isAdmin && !$inc['resolved'] && !$isTerminal): ?>
        <form method="POST"
              action="<?= Helpers::url('/trips/' . (int) $trip['trip_id'] . '/incident/' . (int) $inc['incident_id'] . '/resolve') ?>"
              style="margin-top:10px;">
            <?= Csrf::field() ?>
            <input type="text" class="form-input" name="resolution_notes"
                   placeholder="Resolution notes (required)" required
                   style="max-width:400px; display:inline-block; margin-right:8px;">
            <button type="submit" class="btn btn-outline btn-sm">Mark Resolved</button>
        </form>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<p class="td-muted" style="font-size:14px; margin:8px 0 16px;">No incidents on this trip.</p>
<?php endif; ?>

<?php if ($isAdmin && ($isInProgress || $isIncident)): ?>
<div class="form-card form-card--mt">
    <div class="form-section-title">Report Incident</div>
    <form method="POST" action="<?= Helpers::url('/trips/' . (int) $trip['trip_id'] . '/incident') ?>">
        <?= Csrf::field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Incident Type <span class="required">*</span></label>
                <select class="form-select" name="incident_type" required>
                    <option value="">— Select —</option>
                    <option value="accident">Accident</option>
                    <option value="breakdown">Breakdown</option>
                    <option value="traffic_delay">Traffic Delay</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Occurred At</label>
                <input type="datetime-local" class="form-input" name="occurred_at"
                       value="<?= date('Y-m-d\TH:i') ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Description <span class="required">*</span></label>
            <textarea class="form-textarea" name="description" required
                      placeholder="Describe what happened…"></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-solid">Report Incident</button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php endif; /* isAdmin || isEmployee */ ?>

<?php if ($isAdmin && $isIncident): ?>
<!-- Cancel Trip Modal -->
<div id="cancelTripModal" class="modal-overlay">
    <div class="modal-card modal-card-wide">
        <div class="modal-header">
            <h3>Cancel Trip</h3>
            <button type="button" class="modal-close"
                    onclick="document.getElementById('cancelTripModal').style.display='none';">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form method="POST"
              action="<?= Helpers::url('/trips/' . (int) $trip['trip_id'] . '/cancel') ?>">
            <?= Csrf::field() ?>
            <div class="form-group">
                <label class="form-label">Reason for Cancellation <span class="required">*</span></label>
                <textarea class="form-textarea" name="cancellation_reason" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Vehicle Condition <span class="required">*</span></label>
                <select class="form-select" name="vehicle_condition" required>
                    <option value="">— Select —</option>
                    <option value="under_maintenance">Vehicle needs inspection/repair</option>
                    <option value="available">Vehicle is operational</option>
                </select>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                <button type="button" class="btn btn-outline"
                        onclick="document.getElementById('cancelTripModal').style.display='none';">
                    Keep Trip
                </button>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('cancelTripModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
<?php endif; ?>
