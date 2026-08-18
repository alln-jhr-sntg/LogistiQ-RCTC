<?php
$statusBadge = [
    'pending'          => ['class' => 'badge-pending',   'label' => 'Pending'],
    'approved'         => ['class' => 'badge-approved',  'label' => 'Approved'],
    'gatepass_pending' => ['class' => 'badge-pending',   'label' => 'Gatepass'],
    'rejected'         => ['class' => 'badge-cancelled', 'label' => 'Rejected'],
    'cancelled'        => ['class' => 'badge-cancelled', 'label' => 'Cancelled'],
    'in_progress'      => ['class' => 'badge-on-trip',   'label' => 'In Progress'],
    'completed'        => ['class' => 'badge-available', 'label' => 'Completed'],
];
$sb   = $statusBadge[$reservation['status']] ?? ['class' => 'badge-pending', 'label' => $reservation['status']];
$role = Auth::role();
?>
<div class="page-header">
    <div class="page-header-left">
        <p>Submitted <?= date('M d, Y', strtotime($reservation['created_at'])) ?></p>
    </div>
    <div class="page-header-actions">
        <span class="badge badge-lg <?= $sb['class'] ?>"><?= $sb['label'] ?></span>
        <?php if ($canEdit): ?>
        <a href="<?= Helpers::url('/reservations/' . $reservation['reservation_id'] . '/edit') ?>"
           class="btn btn-outline">Edit</a>
        <?php endif; ?>
        <?php if (in_array($role, [ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN]) && $reservation['status'] === 'pending'): ?>
        <a href="<?= Helpers::url('/reservations/' . $reservation['reservation_id'] . '/review') ?>"
           class="btn btn-solid">Review</a>
        <?php endif; ?>
        <?php if ($canCancel): ?>
        <button type="button" class="btn btn-danger"
                onclick="document.getElementById('cancelModal').style.display='flex';">
            Cancel
        </button>
        <?php endif; ?>
        <a href="<?= Helpers::url('/reservations') ?>" class="btn btn-outline">← Back</a>
    </div>
</div>

<?php
/* Cards are captured in priority order, then split by count (not by
   fixed left/right assignment) so both columns stay balanced no matter
   how many conditional cards apply for this status/role — a reservation
   with a gatepass, recommendation, trip execution, and map card would
   otherwise pile everything into one column while the other sits mostly
   empty. See views/trips/detail.php for the same pattern. */
$cards = [];

ob_start(); ?>
<div class="detail-card">
    <div class="detail-card-title">Requester</div>
    <div class="detail-field">
        <div class="detail-field-label">Name</div>
        <div class="detail-field-value">
            <?= Helpers::e($reservation['requester_first_name'] . ' ' . $reservation['requester_last_name']) ?>
        </div>
    </div>
    <div class="detail-field">
        <div class="detail-field-label">Department</div>
        <div class="detail-field-value">
            <?= Helpers::e($reservation['company_code'] . ' — ' . $reservation['department_name']) ?>
        </div>
    </div>
</div>
<?php $cards[] = ob_get_clean();

ob_start(); ?>
<div class="detail-card">
    <div class="detail-card-title">Trip Details</div>
    <div class="detail-field">
        <div class="detail-field-label">Purpose</div>
        <div class="detail-field-value"><?= Helpers::e($reservation['purpose_name']) ?></div>
    </div>
    <?php if ($reservation['project_name']): ?>
    <div class="detail-field">
        <div class="detail-field-label">Project</div>
        <div class="detail-field-value">
            <?= Helpers::e($reservation['project_name']) ?>
            <?php if ($reservation['project_code']): ?>
            <span class="td-muted">(<?= Helpers::e($reservation['project_code']) ?>)</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <div class="detail-field">
        <div class="detail-field-label">Destination</div>
        <div class="detail-field-value"><?= Helpers::e($reservation['destination']) ?></div>
    </div>
    <?php if ($reservation['trip_details']): ?>
    <div class="detail-field">
        <div class="detail-field-label">Notes</div>
        <div class="detail-field-value"><?= Helpers::e($reservation['trip_details']) ?></div>
    </div>
    <?php endif; ?>
</div>
<?php $cards[] = ob_get_clean();

ob_start(); ?>
<div class="detail-card">
    <div class="detail-card-title">Schedule</div>
    <div class="detail-field">
        <div class="detail-field-label">Departure</div>
        <div class="detail-field-value">
            <?= date('D, M d Y — g:i A', strtotime($reservation['departure_datetime'])) ?>
        </div>
    </div>
    <div class="detail-field">
        <div class="detail-field-label">Est. Return</div>
        <div class="detail-field-value">
            <?= date('D, M d Y — g:i A', strtotime($reservation['return_datetime'])) ?>
        </div>
    </div>
</div>
<?php $cards[] = ob_get_clean();

ob_start(); ?>
<div class="detail-card">
    <div class="detail-card-title">Passengers &amp; Cargo</div>
    <div class="detail-field">
        <div class="detail-field-label">Passengers</div>
        <div class="detail-field-value"><?= (int) $reservation['passenger_count'] ?></div>
    </div>
    <div class="detail-field">
        <div class="detail-field-label">Cargo</div>
        <div class="detail-field-value">
            <?= number_format((float) $reservation['cargo_weight_kg'], 2) ?> kg
            <?= $reservation['cargo_description'] ? ' — ' . Helpers::e($reservation['cargo_description']) : '' ?>
        </div>
    </div>
</div>
<?php $cards[] = ob_get_clean();

if (in_array($reservation['status'], ['gatepass_pending', 'approved', 'in_progress', 'completed'])):
ob_start(); ?>
<div class="detail-card">
    <div class="detail-card-title">Assignment</div>
    <div class="detail-field">
        <div class="detail-field-label">Vehicle</div>
        <div class="detail-field-value">
            <?= $reservation['plate_number']
                ? Helpers::e($reservation['plate_number'] . ' — ' . $reservation['vehicle_brand'] . ' ' . $reservation['vehicle_model'])
                : '—' ?>
        </div>
    </div>
    <div class="detail-field">
        <div class="detail-field-label">Driver</div>
        <div class="detail-field-value">
            <?= $reservation['driver_first_name']
                ? Helpers::e($reservation['driver_first_name'] . ' ' . $reservation['driver_last_name'])
                : '—' ?>
        </div>
    </div>
    <div class="detail-field">
        <div class="detail-field-label">Reviewed By</div>
        <div class="detail-field-value">
            <?= $reservation['reviewer_first_name']
                ? Helpers::e($reservation['reviewer_first_name'] . ' ' . $reservation['reviewer_last_name'])
                : '—' ?>
        </div>
    </div>
</div>
<?php $cards[] = ob_get_clean();
endif;

if (!empty($gatepass)):
    $gpBadge = [
        'pending'  => ['class' => 'badge-pending',   'label' => 'Pending Review'],
        'approved' => ['class' => 'badge-approved',  'label' => 'Approved'],
        'rejected' => ['class' => 'badge-cancelled', 'label' => 'Rejected'],
    ];
    $gb = $gpBadge[$gatepass['status']] ?? ['class' => 'badge-pending', 'label' => $gatepass['status']];
ob_start(); ?>
<div class="detail-card">
    <div class="detail-card-title detail-card-title-row">
        <span>Gate Pass</span>
        <?php if ($canReviewGatepass): ?>
        <a href="javascript:void(0);" class="detail-card-link"
           onclick="document.getElementById('gatepassReviewModal').style.display='flex';">
            Review →
        </a>
        <?php elseif ($canPrintGatepass): ?>
        <a href="<?= Helpers::url('/gatepasses/' . $gatepass['gatepass_id'] . '/print') ?>"
           target="_blank" class="detail-card-link">
            Print →
        </a>
        <?php endif; ?>
    </div>
    <div class="detail-field">
        <div class="detail-field-label">Code</div>
        <div class="detail-field-value"><?= Helpers::e($gatepass['gatepass_code']) ?></div>
    </div>
    <div class="detail-field">
        <div class="detail-field-label">Status</div>
        <div class="detail-field-value">
            <span class="badge <?= $gb['class'] ?>"><?= $gb['label'] ?></span>
        </div>
    </div>
    <?php if ($gatepass['reviewer_first_name']): ?>
    <div class="detail-field">
        <div class="detail-field-label">Reviewed By</div>
        <div class="detail-field-value">
            <?= Helpers::e($gatepass['reviewer_first_name'] . ' ' . $gatepass['reviewer_last_name']) ?>
            <?php if ($gatepass['reviewed_at']): ?>
            <span class="td-muted">— <?= date('M d, Y g:i A', strtotime($gatepass['reviewed_at'])) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php $cards[] = ob_get_clean();
endif;

if (in_array($role, [ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN])):
ob_start(); ?>
<div class="detail-card">
    <div class="detail-card-title">Vehicle Recommendation</div>
    <?php if ($reservation['ai_recommended_vehicle_id']): ?>
    <div class="detail-field">
        <div class="detail-field-label">Score</div>
        <div class="detail-field-value">
            <?= (int) $reservation['ai_recommendation_score'] ?>
            / 100
        </div>
    </div>
    <div class="detail-field">
        <div class="detail-field-label">Notes</div>
        <div class="detail-field-value text-sm">
            <?= Helpers::e($reservation['ai_recommendation_notes'] ?? '—') ?>
        </div>
    </div>
    <?php elseif ($reservation['status'] === 'pending'): ?>
    <p class="td-muted detail-note">
        Vehicle recommendation runs when an admin opens the review form.
        <a href="<?= Helpers::url('/reservations/' . $reservation['reservation_id'] . '/review') ?>">
            Review now →
        </a>
    </p>
    <?php else: ?>
    <p class="td-muted detail-note">No recommendation data available.</p>
    <?php endif; ?>
</div>
<?php $cards[] = ob_get_clean();
endif;

if (!empty($trip)):
    $tripBadge = [
        'pending_start' => ['class' => 'badge-pending',   'label' => 'Pending Start'],
        'in_progress'   => ['class' => 'badge-on-trip',   'label' => 'In Progress'],
        'completed'     => ['class' => 'badge-available', 'label' => 'Completed'],
        'incident'      => ['class' => 'badge-cancelled', 'label' => 'Incident'],
    ];
    $tb = $tripBadge[$trip['trip_status']] ?? ['class' => 'badge-pending', 'label' => $trip['trip_status']];
ob_start(); ?>
<div class="detail-card">
    <div class="detail-card-title detail-card-title-row">
        <span>Trip Execution</span>
        <a href="<?= Helpers::url('/trips/' . (int) $trip['trip_id']) ?>" class="detail-card-link">
            View full details →
        </a>
    </div>
    <div class="detail-field">
        <div class="detail-field-label">Status</div>
        <div class="detail-field-value">
            <span class="badge <?= $tb['class'] ?>"><?= $tb['label'] ?></span>
        </div>
    </div>
    <div class="detail-field">
        <div class="detail-field-label">Actual Departure</div>
        <div class="detail-field-value">
            <?= $trip['actual_departure']
                ? date('D, M d Y — g:i A', strtotime($trip['actual_departure']))
                : '<span class="td-muted">Not yet started</span>' ?>
        </div>
    </div>
    <div class="detail-field">
        <div class="detail-field-label">Actual Return</div>
        <div class="detail-field-value">
            <?= $trip['actual_return']
                ? date('D, M d Y — g:i A', strtotime($trip['actual_return']))
                : '<span class="td-muted">Still on trip</span>' ?>
        </div>
    </div>
    <?php if ($trip['odometer_start_km'] !== null && $trip['odometer_end_km'] !== null): ?>
    <div class="detail-field">
        <div class="detail-field-label">Distance Travelled</div>
        <div class="detail-field-value">
            <?= number_format((float)$trip['odometer_end_km'] - (float)$trip['odometer_start_km'], 0) ?> km
        </div>
    </div>
    <?php endif; ?>
</div>
<?php $cards[] = ob_get_clean();
endif;

if ($reservation['status'] === 'rejected'):
ob_start(); ?>
<div class="detail-card detail-card-danger">
    <div class="detail-card-title text-danger">Rejection Reason</div>
    <p class="detail-card-text">
        <?= Helpers::e($reservation['rejection_reason'] ?? 'No reason provided.') ?>
    </p>
</div>
<?php $cards[] = ob_get_clean();
endif;

if ($reservation['status'] === 'cancelled' && $reservation['cancellation_reason']):
ob_start(); ?>
<div class="detail-card">
    <div class="detail-card-title">Cancellation Reason</div>
    <p class="detail-card-text">
        <?= Helpers::e($reservation['cancellation_reason']) ?>
    </p>
</div>
<?php $cards[] = ob_get_clean();
endif;

// Destination map — shown when coordinates were pinned on create/edit
if ($reservation['destination_lat'] && $reservation['destination_lng']):
ob_start(); ?>
<div class="detail-card detail-card-flush">
    <div id="detailMap" class="detail-map"></div>
</div>
<script>
window.lvmsLocationPicker = {
    mapId:       'detailMap',
    editable:    false,
    lat:         <?= (float) $reservation['destination_lat'] ?>,
    lng:         <?= (float) $reservation['destination_lng'] ?>,
    markerTitle: <?= json_encode($reservation['destination']) ?>,
};
</script>
<script src="<?= APP_BASE ?>/public/js/location_picker.js"></script>
<script
    src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&callback=initLocationPicker"
    loading="async" defer>
</script>
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
</div>

<?php if ($canCancel): ?>
<!-- Cancel Modal -->
<div id="cancelModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Cancel Reservation</h3>
            <button type="button" class="modal-close"
                    onclick="document.getElementById('cancelModal').style.display='none';">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form method="POST"
              action="<?= Helpers::url('/reservations/' . $reservation['reservation_id'] . '/cancel') ?>">
            <?= Csrf::field() ?>
            <div class="form-group">
                <label class="form-label">Reason for Cancellation <span class="required">*</span></label>
                <textarea class="form-textarea" name="cancellation_reason" rows="3" required></textarea>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                <button type="button" class="btn btn-outline"
                        onclick="document.getElementById('cancelModal').style.display='none';">
                    Keep Reservation
                </button>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
<?php endif; ?>

<?php if ($canReviewGatepass): ?>
<!-- Gate Pass Review Modal -->
<div id="gatepassReviewModal" class="modal-overlay">
    <div class="modal-card modal-card-wide">
        <div class="modal-header">
            <h3>Review <?= Helpers::e($gatepass['gatepass_code']) ?></h3>
            <button type="button" class="modal-close"
                    onclick="document.getElementById('gatepassReviewModal').style.display='none';">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>

        <div class="tab-toggle">
            <button type="button" id="gpBtnApprove" class="btn btn-solid"
                    onclick="showGatepassSection('approve')">Approve</button>
            <button type="button" id="gpBtnReject" class="btn btn-outline"
                    onclick="showGatepassSection('reject')">Reject</button>
        </div>

        <div id="gpSectionApprove" class="form-card">
            <div class="form-section-title">Approve Gatepass</div>
            <p class="form-hint form-hint-flush">
                Approving clears this reservation for departure and creates the trip.
            </p>
            <form method="POST" action="<?= Helpers::url('/gatepasses/' . $gatepass['gatepass_id'] . '/approve') ?>">
                <?= Csrf::field() ?>
                <div class="form-actions">
                    <button type="submit" class="btn btn-solid">Approve Gatepass</button>
                </div>
            </form>
        </div>

        <div id="gpSectionReject" class="form-card hidden">
            <div class="form-section-title">Rejection Reason</div>
            <form method="POST" action="<?= Helpers::url('/gatepasses/' . $gatepass['gatepass_id'] . '/reject') ?>">
                <?= Csrf::field() ?>
                <div class="form-group">
                    <label class="form-label">Reason <span class="required">*</span></label>
                    <textarea class="form-textarea" name="rejection_reason" rows="4" required
                              placeholder="Explain why this gatepass is being rejected..."></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-danger">Reject Gatepass</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('gatepassReviewModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
function showGatepassSection(which) {
    document.getElementById('gpSectionApprove').classList.toggle('hidden', which !== 'approve');
    document.getElementById('gpSectionReject').classList.toggle('hidden', which !== 'reject');
    document.getElementById('gpBtnApprove').className = which === 'approve' ? 'btn btn-solid' : 'btn btn-outline';
    document.getElementById('gpBtnReject').className  = which === 'reject'  ? 'btn btn-solid' : 'btn btn-outline';
}
</script>
<?php endif; ?>
