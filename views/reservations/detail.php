<?php
$statusBadge = [
    'pending'     => ['class' => 'badge-pending',   'label' => 'Pending'],
    'approved'    => ['class' => 'badge-approved',  'label' => 'Approved'],
    'rejected'    => ['class' => 'badge-cancelled', 'label' => 'Rejected'],
    'cancelled'   => ['class' => 'badge-cancelled', 'label' => 'Cancelled'],
    'in_progress' => ['class' => 'badge-on-trip',   'label' => 'In Progress'],
    'completed'   => ['class' => 'badge-available', 'label' => 'Completed'],
];
$sb   = $statusBadge[$reservation['status']] ?? ['class' => 'badge-pending', 'label' => $reservation['status']];
$role = Auth::role();
?>
<div class="page-header">
    <div class="page-header-left">
        <h2><?= Helpers::e($reservation['reservation_code']) ?></h2>
        <p>Submitted <?= date('M d, Y', strtotime($reservation['created_at'])) ?></p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <span class="badge <?= $sb['class'] ?>" style="font-size:13px;padding:6px 12px;"><?= $sb['label'] ?></span>
        <?php if ($canEdit): ?>
        <a href="<?= Helpers::url('/reservations/' . $reservation['reservation_id'] . '/edit') ?>"
           class="btn btn-outline">Edit</a>
        <?php endif; ?>
        <?php if (in_array($role, [ROLE_SUPER_ADMIN, ROLE_ADMIN]) && $reservation['status'] === 'pending'): ?>
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

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

    <!-- Left column -->
    <div>
        <div class="detail-card" style="margin-bottom:20px;">
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

        <div class="detail-card" style="margin-bottom:20px;">
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
    </div>

    <!-- Right column -->
    <div>
        <div class="detail-card" style="margin-bottom:20px;">
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

        <?php if (in_array($reservation['status'], ['approved', 'in_progress', 'completed'])): ?>
        <div class="detail-card" style="margin-bottom:20px;">
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
        <?php endif; ?>

        <?php if ($reservation['status'] === 'rejected'): ?>
        <div class="detail-card" style="margin-bottom:20px;border-color:#f5a09a;">
            <div class="detail-card-title" style="color:#c0392b;">Rejection Reason</div>
            <p style="font-size:14px;color:var(--clr-text-2);margin:0;">
                <?= Helpers::e($reservation['rejection_reason'] ?? 'No reason provided.') ?>
            </p>
        </div>
        <?php endif; ?>

        <?php if ($reservation['status'] === 'cancelled' && $reservation['cancellation_reason']): ?>
        <div class="detail-card" style="margin-bottom:20px;">
            <div class="detail-card-title">Cancellation Reason</div>
            <p style="font-size:14px;color:var(--clr-text-2);margin:0;">
                <?= Helpers::e($reservation['cancellation_reason']) ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- AI recommendation panel — wired in Step 10 -->
        <?php if (in_array($role, [ROLE_SUPER_ADMIN, ROLE_ADMIN])): ?>
        <div class="detail-card" style="margin-bottom:20px;">
            <div class="detail-card-title">Vehicle Recommendation</div>
            <p class="td-muted" style="font-size:13px;">
                AI recommendation scoring is wired in Step 10.
            </p>
        </div>
        <?php endif; ?>

        <!-- Trip section — wired in Step 11 -->
        <?php if (in_array($reservation['status'], ['in_progress', 'completed'])): ?>
        <div class="detail-card">
            <div class="detail-card-title">Trip Execution</div>
            <p class="td-muted" style="font-size:13px;">
                Odometer and actual times are wired in Step 11.
            </p>
        </div>
        <?php endif; ?>

        <!-- Destination map — shown when coordinates were pinned on create/edit -->
        <?php if ($reservation['destination_lat'] && $reservation['destination_lng']): ?>
        <div class="detail-card" style="padding:0;overflow:hidden;">
            <div id="detailMap" style="height:220px;width:100%;"></div>
        </div>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
        var dLat = <?= (float) $reservation['destination_lat'] ?>;
        var dLng = <?= (float) $reservation['destination_lng'] ?>;
        var dMap = L.map('detailMap', { zoomControl: true, scrollWheelZoom: false })
                    .setView([dLat, dLng], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(dMap);
        L.marker([dLat, dLng]).addTo(dMap)
            .bindPopup('<?= addslashes(Helpers::e($reservation['destination'])) ?>')
            .openPopup();
        </script>
        <?php endif; ?>
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
