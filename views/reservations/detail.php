<?php
$role   = Auth::role();
$status = $reservation['status'];

// Badge class mapping
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
?>

<div class="page-header">
    <div class="page-header-left">
        <h2><?= Helpers::e($reservation['code']) ?></h2>
        <p>Submitted <?= Helpers::e($reservation['submitted']) ?> &mdash; <span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span></p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="<?= Helpers::url('/reservations') ?>" class="btn btn-outline">← Back</a>
        <?php if ($role !== ROLE_EMPLOYEE && $status === 'pending'): ?>
            <a href="<?= Helpers::url('/reservations/1/review') ?>" class="btn btn-solid">Review</a>
        <?php endif; ?>
        <?php if ($status === 'in_progress' && in_array($role, [ROLE_SUPER_ADMIN, ROLE_ADMIN])): ?>
            <a href="<?= Helpers::url('/trips/1/map') ?>" class="btn btn-solid">Live Map</a>
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

    <!-- Assignment: all roles see this, but employees only see it on non-pending statuses -->
    <?php if ($role !== ROLE_EMPLOYEE || !in_array($status, ['pending', 'rejected', 'cancelled'])): ?>
    <div class="detail-card">
        <div class="detail-card-title">Assignment</div>
        <?php if (in_array($status, ['pending', 'rejected', 'cancelled'])): ?>
            <div class="detail-field"><div class="detail-field-label">Assigned Vehicle</div><div class="detail-field-value" style="color:var(--clr-text-3);">— Not yet assigned</div></div>
            <div class="detail-field"><div class="detail-field-label">Assigned Driver</div><div class="detail-field-value" style="color:var(--clr-text-3);">— Not yet assigned</div></div>
            <div class="detail-field"><div class="detail-field-label">Reviewed By</div><div class="detail-field-value" style="color:var(--clr-text-3);">— Pending</div></div>
        <?php else: ?>
            <div class="detail-field"><div class="detail-field-label">Assigned Vehicle</div><div class="detail-field-value">ABC-1234 — Toyota Hi-Ace</div></div>
            <div class="detail-field"><div class="detail-field-label">Assigned Driver</div><div class="detail-field-value">Carlos Mendoza</div></div>
            <?php if ($role !== ROLE_EMPLOYEE): ?>
            <div class="detail-field"><div class="detail-field-label">Reviewed By</div><div class="detail-field-value">Admin User</div></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- AI Recommendation: admin and super admin only -->
    <?php if ($role !== ROLE_EMPLOYEE): ?>
    <div class="detail-card">
        <div class="detail-card-title">AI Vehicle Recommendation</div>
        <?php if ($status === 'pending'): ?>
            <div class="detail-field"><div class="detail-field-label">Status</div><div class="detail-field-value" style="color:var(--clr-text-3);">— Run recommendation engine on review</div></div>
            <div class="detail-field"><div class="detail-field-label">Score</div><div class="detail-field-value" style="color:var(--clr-text-3);">—</div></div>
        <?php else: ?>
            <div class="detail-field"><div class="detail-field-label">Recommended Vehicle</div><div class="detail-field-value">ABC-1234 — Toyota Hi-Ace</div></div>
            <div class="detail-field"><div class="detail-field-label">Score</div><div class="detail-field-value">92.50 / 100</div></div>
            <div style="display:grid;gap:4px;margin-top:8px;background:var(--clr-bg);border-radius:var(--radius-md);padding:12px;">
                <div style="display:flex;justify-content:space-between;font-size:13px;"><span style="color:var(--clr-text-3);">Capacity fit</span><span>95</span></div>
                <div style="display:flex;justify-content:space-between;font-size:13px;"><span style="color:var(--clr-text-3);">Cargo fit</span><span>100</span></div>
                <div style="display:flex;justify-content:space-between;font-size:13px;"><span style="color:var(--clr-text-3);">Availability</span><span>90</span></div>
                <div style="display:flex;justify-content:space-between;font-size:13px;"><span style="color:var(--clr-text-3);">Purpose fit</span><span>88</span></div>
                <div style="display:flex;justify-content:space-between;font-size:13px;"><span style="color:var(--clr-text-3);">Maintenance</span><span>90</span></div>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<?php if ($status === 'completed'): ?>
<!-- Trip Summary for completed reservations -->
<div class="section-title" style="margin-bottom:12px;">Trip Summary</div>
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
        <div class="detail-field"><div class="detail-field-label">Driver Notes</div><div class="detail-field-value">Traffic was light. Arrived ahead of schedule.</div></div>
        <?php if ($role !== ROLE_EMPLOYEE): ?>
        <div class="detail-field"><div class="detail-field-label">Admin Notes</div><div class="detail-field-value" style="color:var(--clr-text-3);font-style:italic;">No notes.</div></div>
        <?php endif; ?>
        <div class="detail-field"><div class="detail-field-label">Incidents</div><div class="detail-field-value" style="color:var(--clr-success);">✓ No incidents reported</div></div>
    </div>
</div>
<?php endif; ?>

<?php if ($status === 'rejected'): ?>
<div style="background:var(--clr-error-bg);border:1px solid rgba(192,57,43,.15);border-radius:var(--radius-lg);padding:18px 22px;margin-top:4px;">
    <div style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--clr-error);margin-bottom:6px;">Rejection Reason</div>
    <div style="font-size:14px;color:var(--clr-text);">No vehicles available on the requested date. Please submit a new request for a different date.</div>
</div>
<?php endif; ?>

<?php if ($status === 'in_progress' && $role === ROLE_EMPLOYEE): ?>
<!-- Report Incident — only visible to employees on active trips -->
<div class="form-card" style="margin-top:24px;max-width:100%;">
    <div class="form-section-title">Report an Incident</div>
    <p style="font-size:13px;color:var(--clr-text-3);margin-bottom:16px;">
        If something has happened during this trip, report it here. Your report will be forwarded to the admin immediately.
    </p>
    <form method="POST" action="<?= Helpers::url('/trips/1/incident') ?>">
        <input type="hidden" name="reservation_id" value="<?= Helpers::e($reservation['code']) ?>">
        <input type="hidden" name="reservation_url_id" value="<?= Helpers::e(explode('/', trim($_GET['url'] ?? '1/', '/'))[1] ?? '1') ?>">
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
        <div class="form-actions">
            <button type="submit" class="btn btn-danger">
                <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                Submit Incident Report
            </button>
        </div>
    </form>
</div>
<?php endif; ?>
