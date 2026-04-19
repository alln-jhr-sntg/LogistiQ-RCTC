<?php
$role   = Auth::role();
$status = $reservation['status'];

$badgeMap = [
    'pending'     => 'badge-pending',
    'approved'    => 'badge-approved',
    'rejected'    => 'badge-rejected',
    'cancelled'   => 'badge-cancelled',
    'in_progress' => 'badge-in-progress',
    'completed'   => 'badge-completed',
];
$badgeClass  = $badgeMap[$status] ?? 'badge-pending';
$statusLabel = ucwords(str_replace('_', ' ', $status));

$canCancel = false;
if ($role === ROLE_EMPLOYEE && $status === 'pending') $canCancel = true;
if (in_array($role, [ROLE_SUPER_ADMIN, ROLE_ADMIN]) && in_array($status, ['pending', 'approved'])) $canCancel = true;
?>

<div class="page-header">
    <div class="page-header-left">
        <h2><?= Helpers::e($reservation['code']) ?></h2>
        <p>Submitted <?= Helpers::e($reservation['submitted']) ?> &mdash; <span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span></p>
    </div>
    <div class="page-header-actions">
        <a href="<?= Helpers::url('/reservations') ?>" class="btn btn-outline">← Back</a>
        <?php if ($role !== ROLE_EMPLOYEE && $status === 'pending'): ?>
            <a href="<?= Helpers::url('/reservations/1/review') ?>" class="btn btn-solid">Review</a>
        <?php endif; ?>
        <?php if ($status === 'in_progress' && in_array($role, [ROLE_SUPER_ADMIN, ROLE_ADMIN])): ?>
            <a href="<?= Helpers::url('/trips/1/map') ?>" class="btn btn-solid">Live Map</a>
        <?php endif; ?>
        <?php if ($role === ROLE_EMPLOYEE && $status === 'in_progress'): ?>
            <button type="button" class="btn btn-danger" onclick="document.getElementById('incidentModal').style.display='flex';">Report Incident</button>
        <?php endif; ?>
        <?php if ($canCancel): ?>
            <button type="button" class="btn btn-danger" onclick="document.getElementById('cancelModal').style.display='flex';">Cancel Reservation</button>
        <?php endif; ?>
    </div>
</div>

<div class="detail-grid">

    <div class="detail-card">
        <div class="detail-card-title">Trip Information</div>
        <div class="detail-field"><div class="detail-field-label">Purpose</div><div class="detail-field-value"><?= Helpers::e($reservation['purpose']) ?></div></div>
        <div class="detail-field"><div class="detail-field-label">Destination</div><div class="detail-field-value"><?= Helpers::e($reservation['destination']) ?></div></div>
        <div class="detail-field"><div class="detail-field-label">Origin</div><div class="detail-field-value">Company Warehouse (Main)</div></div>
        <div class="detail-field"><div class="detail-field-label">Departure</div><div class="detail-field-value"><?= Helpers::e($reservation['departure']) ?></div></div>
        <div class="detail-field"><div class="detail-field-label">Estimated Return</div><div class="detail-field-value"><?= Helpers::e($reservation['return']) ?></div></div>
        <div class="detail-field"><div class="detail-field-label">Trip Details</div><div class="detail-field-value"><?= Helpers::e($reservation['trip_details']) ?></div></div>
    </div>

    <div class="detail-card">
        <div class="detail-card-title">Requester & Department</div>
        <div class="detail-field"><div class="detail-field-label">Requested By</div><div class="detail-field-value"><?= Helpers::e($reservation['requester']) ?></div></div>
        <div class="detail-field"><div class="detail-field-label">Department</div><div class="detail-field-value"><?= Helpers::e($reservation['department']) ?></div></div>
        <div class="detail-field"><div class="detail-field-label">Company</div><div class="detail-field-value"><?= Helpers::e($reservation['company']) ?></div></div>
        <div class="detail-field"><div class="detail-field-label">Passenger Count</div><div class="detail-field-value"><?= Helpers::e((string)$reservation['passengers']) ?></div></div>
        <div class="detail-field"><div class="detail-field-label">Cargo</div><div class="detail-field-value"><?= Helpers::e($reservation['cargo']) ?></div></div>
    </div>

    <?php if ($role !== ROLE_EMPLOYEE || !in_array($status, ['pending', 'rejected', 'cancelled'])): ?>
    <div class="detail-card">
        <div class="detail-card-title">Assignment</div>
        <?php if (in_array($status, ['pending', 'rejected', 'cancelled'])): ?>
            <div class="detail-field"><div class="detail-field-label">Assigned Vehicle</div><div class="detail-field-value detail-muted">— Not yet assigned</div></div>
            <div class="detail-field"><div class="detail-field-label">Assigned Driver</div><div class="detail-field-value detail-muted">— Not yet assigned</div></div>
            <div class="detail-field"><div class="detail-field-label">Reviewed By</div><div class="detail-field-value detail-muted">— Pending</div></div>
        <?php else: ?>
            <div class="detail-field"><div class="detail-field-label">Assigned Vehicle</div><div class="detail-field-value">ABC-1234 — Toyota Hi-Ace</div></div>
            <div class="detail-field"><div class="detail-field-label">Assigned Driver</div><div class="detail-field-value">Carlos Mendoza</div></div>
            <?php if ($role !== ROLE_EMPLOYEE): ?>
            <div class="detail-field"><div class="detail-field-label">Reviewed By</div><div class="detail-field-value">Admin User</div></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($role !== ROLE_EMPLOYEE): ?>
    <div class="detail-card">
        <div class="detail-card-title">AI Vehicle Recommendation</div>
        <?php if ($status === 'pending'): ?>
            <div class="detail-field"><div class="detail-field-label">Status</div><div class="detail-field-value detail-muted">— Run recommendation engine on review</div></div>
            <div class="detail-field"><div class="detail-field-label">Score</div><div class="detail-field-value detail-muted">—</div></div>
        <?php else: ?>
            <div class="detail-field"><div class="detail-field-label">Recommended Vehicle</div><div class="detail-field-value">ABC-1234 — Toyota Hi-Ace</div></div>
            <div class="detail-field"><div class="detail-field-label">Score</div><div class="detail-field-value">92.50 / 100</div></div>
            <div class="ai-score-grid">
                <div class="ai-score-row"><span class="ai-score-label">Capacity fit</span><span>95</span></div>
                <div class="ai-score-row"><span class="ai-score-label">Cargo fit</span><span>100</span></div>
                <div class="ai-score-row"><span class="ai-score-label">Availability</span><span>90</span></div>
                <div class="ai-score-row"><span class="ai-score-label">Purpose fit</span><span>88</span></div>
                <div class="ai-score-row"><span class="ai-score-label">Maintenance</span><span>90</span></div>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<?php if ($status === 'completed'): ?>
<div class="section-title">Trip Summary</div>
<div class="detail-grid">
    <div class="detail-card">
        <div class="detail-card-title">Actual Trip Data</div>
        <div class="detail-field"><div class="detail-field-label">Actual Departure</div><div class="detail-field-value">Dec 08, 2025 09:12 AM</div></div>
        <div class="detail-field"><div class="detail-field-label">Actual Return</div><div class="detail-field-value">Dec 08, 2025 03:48 PM</div></div>
        <div class="detail-field"><div class="detail-field-label">Odometer Start</div><div class="detail-field-value">11,920 km</div></div>
        <div class="detail-field"><div class="detail-field-label">Odometer End</div><div class="detail-field-value">11,962 km</div></div>
        <div class="detail-field"><div class="detail-field-label">Distance Travelled</div><div class="detail-field-value"><strong>42 km</strong></div></div>
    </div>
    <div class="detail-card">
        <div class="detail-card-title">Notes & Incidents</div>
        <?php if (in_array($role, [ROLE_SUPER_ADMIN, ROLE_ADMIN])): ?>
        <div class="detail-field"><div class="detail-field-label">Admin Notes</div><div class="detail-field-value detail-muted detail-italic">No notes.</div></div>
        <?php endif; ?>
        <div class="detail-field"><div class="detail-field-label">Incidents</div><div class="detail-field-value detail-success">✓ No incidents reported</div></div>
    </div>
</div>
<?php endif; ?>

<?php if ($status === 'rejected'): ?>
<div class="rejection-banner">
    <div class="rejection-banner__label">Rejection Reason</div>
    <div>No vehicles available on the requested date. Please submit a new request for a different date.</div>
</div>
<?php endif; ?>

<?php if ($role === ROLE_EMPLOYEE && in_array($status, ['in_progress', 'completed'])): ?>
<div class="form-card form-card--mt">
    <div class="form-section-title">Your Remarks</div>
    <p class="form-hint">
        <?= $status === 'completed' ? 'Remarks can be submitted within 24 hours of trip completion.' : 'You may add remarks while this trip is in progress.' ?>
    </p>
    <form method="POST" action="<?= Helpers::url('/trips/1/notes') ?>">
        <div class="form-group">
            <textarea class="form-textarea" name="employee_remarks" placeholder="Add your remarks about this trip…" required></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-solid">Submit Remarks</button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if ($canCancel): ?>
<!-- Cancel Reservation Modal -->
<div id="cancelModal" class="modal-overlay">
    <div class="modal-card modal-card-wide">
        <div class="modal-header">
            <h3>Cancel Reservation</h3>
        </div>
        <p class="modal-body">This action cannot be undone. Please provide a reason for cancellation.</p>
        <form method="POST" action="<?= Helpers::url('/reservations/1/cancel') ?>">
            <div class="form-group">
                <label class="form-label">Cancellation Reason <span class="required">*</span></label>
                <textarea class="form-textarea" name="cancellation_reason" placeholder="Explain why this reservation is being cancelled…" required></textarea>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                <button type="button" class="btn btn-outline" onclick="document.getElementById('cancelModal').style.display='none';">Go Back</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($role === ROLE_EMPLOYEE && $status === 'in_progress'): ?>
<!-- Report Incident Modal -->
<div id="incidentModal" class="modal-overlay">
    <div class="modal-card modal-card-wide">
        <div class="modal-header">
            <h3>Report an Incident</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('incidentModal').style.display='none';" aria-label="Close">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <p class="modal-body">If something has happened during this trip, report it here. Your report will be forwarded to the admin immediately.</p>
        <form method="POST" action="<?= Helpers::url('/trips/1/incident') ?>">
            <input type="hidden" name="reservation_id" value="<?= Helpers::e($reservation['code']) ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Incident Type <span class="required">*</span></label>
                    <select class="form-select" name="incident_type" required>
                        <option value="">— Select Type —</option>
                        <option value="accident">Accident</option>
                        <option value="breakdown">Breakdown</option>
                        <option value="traffic_delay">Traffic Delay</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">When did it happen?</label>
                    <input type="datetime-local" class="form-input" name="occurred_at">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Description <span class="required">*</span></label>
                <textarea class="form-textarea" name="description" placeholder="Describe what happened in detail…" required></textarea>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-danger">Submit Incident Report</button>
                <button type="button" class="btn btn-outline" onclick="document.getElementById('incidentModal').style.display='none';">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
['cancelModal','incidentModal'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('click', function(e) { if (e.target === this) this.style.display = 'none'; });
});
</script>
