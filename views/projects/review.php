<?php
$hasPin = $project['location_lat'] !== null && $project['location_lng'] !== null;
?>
<div class="page-header">
    <div class="page-header-left">
        <h2>Review <?= Helpers::e($project['project_name']) ?></h2>
        <p>
            <?= Helpers::e($project['company_code']) ?>
            — requested <?= date('M d, Y', strtotime($project['created_at'])) ?>
        </p>
    </div>
    <a href="<?= Helpers::url('/projects') ?>" class="btn btn-outline">← Projects</a>
</div>

<div class="detail-grid">

    <!-- Left column -->
    <div>
        <div class="detail-card">
            <div class="detail-card-title">Requester</div>
            <div class="detail-field">
                <div class="detail-field-label">Name</div>
                <div class="detail-field-value">
                    <?= $requester
                        ? Helpers::e($requester['first_name'] . ' ' . $requester['last_name'])
                        : '—' ?>
                </div>
            </div>
            <div class="detail-field">
                <div class="detail-field-label">Company</div>
                <div class="detail-field-value">
                    <?= Helpers::e($project['company_name']) ?>
                    (<?= Helpers::e($project['company_code']) ?>)
                </div>
            </div>
            <?php if ($requester && $requester['department_name']): ?>
            <div class="detail-field">
                <div class="detail-field-label">Department</div>
                <div class="detail-field-value"><?= Helpers::e($requester['department_name']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($requester && $requester['email']): ?>
            <div class="detail-field">
                <div class="detail-field-label">Email</div>
                <div class="detail-field-value"><?= Helpers::e($requester['email']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="detail-card">
            <div class="detail-card-title">Project</div>
            <div class="detail-field">
                <div class="detail-field-label">Name</div>
                <div class="detail-field-value"><?= Helpers::e($project['project_name']) ?></div>
            </div>
            <div class="detail-field">
                <div class="detail-field-label">Description</div>
                <div class="detail-field-value">
                    <?= $project['description'] ? Helpers::e($project['description']) : '—' ?>
                </div>
            </div>
            <div class="detail-field">
                <div class="detail-field-label">Duration</div>
                <div class="detail-field-value">
                    <?php
                    $start = $project['start_date'] ? date('M d, Y', strtotime($project['start_date'])) : null;
                    $end   = $project['end_date']   ? date('M d, Y', strtotime($project['end_date']))   : null;
                    if ($start && $end)      echo $start . ' — ' . $end;
                    elseif ($start)          echo 'From ' . $start;
                    elseif ($end)            echo 'Until ' . $end;
                    else                     echo '—';
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Right column -->
    <div>
        <div class="detail-card">
            <div class="detail-card-title">Site Location</div>
            <div class="detail-field">
                <div class="detail-field-label">Location</div>
                <div class="detail-field-value">
                    <?= $project['location'] ? Helpers::e($project['location']) : '—' ?>
                </div>
            </div>
            <?php if ($hasPin): ?>
            <div id="reviewMap" class="map-panel map-panel--sm"></div>
            <?php else: ?>
            <p class="detail-muted">No location was pinned on this request.</p>
            <?php endif; ?>
        </div>

        <div class="form-card">
            <div class="form-section-title">Decision</div>
            <p class="form-hint">
                Approving makes this project selectable on reservations for
                <?= Helpers::e($project['company_code']) ?>. Rejecting is final — the
                requester must submit a new request.
            </p>
            <div class="form-actions">
                <form method="POST" action="<?= Helpers::url('/projects/' . $project['project_id'] . '/approve') ?>">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn-solid">Approve Project</button>
                </form>
                <button type="button" class="btn btn-danger"
                        onclick="document.getElementById('rejectModal').style.display='flex';">
                    Reject
                </button>
            </div>
        </div>
    </div>

</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal-overlay">
    <div class="modal-card modal-card-wide">
        <div class="modal-header">
            <h3>Reject Project Request</h3>
            <button type="button" class="modal-close"
                    onclick="document.getElementById('rejectModal').style.display='none';">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= Helpers::url('/projects/' . $project['project_id'] . '/reject') ?>">
            <?= Csrf::field() ?>
            <div class="form-group">
                <label class="form-label">Reason for Rejection <span class="required">*</span></label>
                <textarea class="form-textarea" name="rejection_reason" rows="3" required
                          placeholder="Explain why this project is being rejected..."></textarea>
                <p class="form-hint">The requester sees this reason. Rejection cannot be undone.</p>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                <button type="button" class="btn btn-outline"
                        onclick="document.getElementById('rejectModal').style.display='none';">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>

<?php if ($hasPin): ?>
<script>
window.lvmsLocationPicker = {
    mapId:       'reviewMap',
    editable:    false,
    lat:         <?= (float) $project['location_lat'] ?>,
    lng:         <?= (float) $project['location_lng'] ?>,
    markerTitle: <?= json_encode($project['project_name']) ?>,
};
</script>
<script src="<?= APP_BASE ?>/public/js/location_picker.js"></script>
<script
    src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&callback=initLocationPicker"
    loading="async" defer>
</script>
<?php endif; ?>
