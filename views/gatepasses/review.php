<div class="page-header">
    <div class="page-header-left">
        <h2>Review <?= Helpers::e($gatepass['gatepass_code']) ?></h2>
        <p>
            <?= Helpers::e($gatepass['reservation_code']) ?>
            — <?= Helpers::e($gatepass['destination']) ?>
            — <?= date('M d, Y g:i A', strtotime($gatepass['departure_datetime'])) ?>
        </p>
    </div>
    <a href="<?= Helpers::url('/gatepasses') ?>" class="btn btn-outline">← Queue</a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

    <!-- Left column -->
    <div>
        <div class="detail-card" style="margin-bottom:20px;">
            <div class="detail-card-title">Requester</div>
            <div class="detail-field">
                <div class="detail-field-label">Name</div>
                <div class="detail-field-value">
                    <?= Helpers::e($gatepass['requester_first_name'] . ' ' . $gatepass['requester_last_name']) ?>
                </div>
            </div>
            <div class="detail-field">
                <div class="detail-field-label">Department</div>
                <div class="detail-field-value">
                    <?= Helpers::e($gatepass['company_code'] . ' — ' . $gatepass['department_name']) ?>
                </div>
            </div>
        </div>

        <div class="detail-card" style="margin-bottom:20px;">
            <div class="detail-card-title">Trip Details</div>
            <div class="detail-field">
                <div class="detail-field-label">Purpose</div>
                <div class="detail-field-value"><?= Helpers::e($gatepass['purpose_name']) ?></div>
            </div>
            <div class="detail-field">
                <div class="detail-field-label">Destination</div>
                <div class="detail-field-value"><?= Helpers::e($gatepass['destination']) ?></div>
            </div>
            <?php if ($gatepass['trip_details']): ?>
            <div class="detail-field">
                <div class="detail-field-label">Notes</div>
                <div class="detail-field-value"><?= Helpers::e($gatepass['trip_details']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="detail-card">
            <div class="detail-card-title">Schedule</div>
            <div class="detail-field">
                <div class="detail-field-label">Departure</div>
                <div class="detail-field-value">
                    <?= date('D, M d Y — g:i A', strtotime($gatepass['departure_datetime'])) ?>
                </div>
            </div>
            <div class="detail-field">
                <div class="detail-field-label">Est. Return</div>
                <div class="detail-field-value">
                    <?= date('D, M d Y — g:i A', strtotime($gatepass['return_datetime'])) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Right column -->
    <div>
        <div class="detail-card" style="margin-bottom:20px;">
            <div class="detail-card-title">Assignment</div>
            <div class="detail-field">
                <div class="detail-field-label">Vehicle</div>
                <div class="detail-field-value">
                    <?= $gatepass['plate_number']
                        ? Helpers::e($gatepass['plate_number'] . ' — ' . $gatepass['vehicle_brand'] . ' ' . $gatepass['vehicle_model'])
                        : '—' ?>
                </div>
            </div>
            <div class="detail-field">
                <div class="detail-field-label">Driver</div>
                <div class="detail-field-value">
                    <?= $gatepass['driver_first_name']
                        ? Helpers::e($gatepass['driver_first_name'] . ' ' . $gatepass['driver_last_name'])
                        : '—' ?>
                </div>
            </div>
            <div class="detail-field">
                <div class="detail-field-label">Driver License No.</div>
                <div class="detail-field-value">
                    <?= $gatepass['driver_license_number'] ? Helpers::e($gatepass['driver_license_number']) : '—' ?>
                </div>
            </div>
        </div>

        <div class="detail-card" style="margin-bottom:20px;">
            <div class="detail-card-title">Passengers &amp; Cargo</div>
            <div class="detail-field">
                <div class="detail-field-label">Passengers</div>
                <div class="detail-field-value"><?= (int) $gatepass['passenger_count'] ?></div>
            </div>
            <div class="detail-field">
                <div class="detail-field-label">Cargo</div>
                <div class="detail-field-value">
                    <?= number_format((float) $gatepass['cargo_weight_kg'], 2) ?> kg
                    <?= $gatepass['cargo_description'] ? ' — ' . Helpers::e($gatepass['cargo_description']) : '' ?>
                </div>
            </div>
        </div>

        <!-- Tab toggle -->
        <div style="display:flex;gap:8px;margin-bottom:16px;">
            <button type="button" id="btnApprove" class="btn btn-solid"
                    onclick="showSection('approve')">Approve</button>
            <button type="button" id="btnReject"  class="btn btn-outline"
                    onclick="showSection('reject')">Reject</button>
        </div>

        <!-- Approve form -->
        <div id="sectionApprove" class="form-card">
            <div class="form-section-title">Approve Gatepass</div>
            <p class="form-hint" style="margin-top:0;">
                Approving clears this reservation for departure and creates the trip.
            </p>
            <form method="POST" action="<?= Helpers::url('/gatepasses/' . $gatepass['gatepass_id'] . '/approve') ?>">
                <div class="form-actions">
                    <button type="submit" class="btn btn-solid">Approve Gatepass</button>
                </div>
            </form>
        </div>

        <!-- Reject form (hidden by default) -->
        <div id="sectionReject" class="form-card" style="display:none;">
            <div class="form-section-title">Rejection Reason</div>
            <form method="POST" action="<?= Helpers::url('/gatepasses/' . $gatepass['gatepass_id'] . '/reject') ?>">
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
function showSection(which) {
    document.getElementById('sectionApprove').style.display = which === 'approve' ? '' : 'none';
    document.getElementById('sectionReject').style.display  = which === 'reject'  ? '' : 'none';
    document.getElementById('btnApprove').className = which === 'approve' ? 'btn btn-solid' : 'btn btn-outline';
    document.getElementById('btnReject').className  = which === 'reject'  ? 'btn btn-solid' : 'btn btn-outline';
}
</script>
