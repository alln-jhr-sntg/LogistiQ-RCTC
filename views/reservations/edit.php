<div class="page-header page-header-end">
    <a href="<?= Helpers::url('/reservations/' . $reservation['reservation_id']) ?>"
       class="btn btn-outline">← Back</a>
</div>

<?php
$selectedPid  = (int) ($reservation['project_id'] ?? 0);
$projectIds   = array_map('intval', array_column($projects, 'project_id'));
$staleProject = $selectedPid > 0 && !in_array($selectedPid, $projectIds, true);
?>
<form method="POST"
      action="<?= Helpers::url('/reservations/' . $reservation['reservation_id'] . '/edit') ?>">
<div class="form-card">
    <div class="form-section-title">Trip Details</div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Trip Purpose <span class="required">*</span></label>
            <select class="form-select" name="purpose_id" id="purposeSelect" required
                    onchange="handlePurposeChange()">
                <option value="">— Select Purpose —</option>
                <?php foreach ($purposes as $p): ?>
                <option value="<?= (int) $p['purpose_id'] ?>"
                        data-requires-project="<?= (int) $p['requires_project'] ?>"
                        data-max="<?= $p['max_per_project'] !== null ? (int) $p['max_per_project'] : '' ?>"
                        <?= (int) $p['purpose_id'] === (int) $reservation['purpose_id'] ? 'selected' : '' ?>>
                    <?= Helpers::e($p['purpose_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" id="projectGroup" <?= $selectedPid ? '' : 'hidden' ?>>
            <label class="form-label">Project</label>
            <select class="form-select" name="project_id" id="projectSelect">
                <option value="">— Select Project —</option>
                <?php if ($staleProject): ?>
                <option value="<?= $selectedPid ?>"
                        data-code="<?= Helpers::e($reservation['project_code'] ?? '') ?>"
                        data-note="inactive" selected><?= Helpers::e($reservation['project_name'] ?? ('Project #' . $selectedPid)) ?></option>
                <?php endif; ?>
                <?php foreach ($projects as $pr): ?>
                <option value="<?= (int) $pr['project_id'] ?>"
                        data-code="<?= Helpers::e($pr['project_code'] ?? '') ?>"
                    <?= (int) $pr['project_id'] === $selectedPid ? 'selected' : '' ?>>
                    <?= Helpers::e($pr['project_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php if ($staleProject): ?>
            <p class="form-hint">This project is no longer active. It stays attached unless you pick another one.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="form-group">
        <label class="form-label">Destination <span class="required">*</span></label>
        <input type="text" class="form-input" name="destination" id="destination"
               value="<?= Helpers::e($reservation['destination']) ?>" required>
    </div>

    <input type="hidden" name="destination_lat"
           id="destLat" value="<?= Helpers::e($reservation['destination_lat'] ?? '') ?>">
    <input type="hidden" name="destination_lng"
           id="destLng" value="<?= Helpers::e($reservation['destination_lng'] ?? '') ?>">
    <div class="form-group">
        <div id="destinationMap" style="height:240px;border-radius:var(--radius-md);overflow:hidden;border:1px solid var(--clr-border);"></div>
        <p class="form-hint">Click the map to update the destination pin.</p>
    </div>

    <div class="form-group">
        <label class="form-label">Trip Details</label>
        <textarea class="form-textarea" name="trip_details" rows="2"><?= Helpers::e($reservation['trip_details'] ?? '') ?></textarea>
    </div>

    <div class="form-section-title">Schedule</div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Departure <span class="required">*</span></label>
            <input type="datetime-local" class="form-input" name="departure_datetime"
                   value="<?= date('Y-m-d\TH:i', strtotime($reservation['departure_datetime'])) ?>"
                   required>
        </div>
        <div class="form-group">
            <label class="form-label">Estimated Return <span class="required">*</span></label>
            <input type="datetime-local" class="form-input" name="return_datetime"
                   value="<?= date('Y-m-d\TH:i', strtotime($reservation['return_datetime'])) ?>"
                   required>
        </div>
    </div>

    <div class="form-section-title">Passengers &amp; Cargo</div>
    <div class="form-row form-row-3">
        <div class="form-group">
            <label class="form-label">Passengers <span class="required">*</span></label>
            <input type="number" class="form-input" name="passenger_count" min="1"
                   value="<?= (int) $reservation['passenger_count'] ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">Cargo Weight (kg)</label>
            <input type="number" class="form-input" name="cargo_weight_kg" min="0" step="0.01"
                   value="<?= (float) $reservation['cargo_weight_kg'] ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Cargo Description</label>
            <input type="text" class="form-input" name="cargo_description"
                   value="<?= Helpers::e($reservation['cargo_description'] ?? '') ?>">
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-solid">Save Changes</button>
        <a href="<?= Helpers::url('/reservations/' . $reservation['reservation_id']) ?>"
           class="btn btn-outline">Cancel</a>
    </div>
</div>
</form>

<script src="<?= Helpers::assetUrl('/js/searchable_select.js') ?>"></script>
<script>
var projectCombo = SearchableSelect.attach('projectSelect', {
    placeholder: 'Search project name or code…',
    emptyText:   'No matching projects'
});

function handlePurposeChange() {
    var sel = document.getElementById('purposeSelect');
    var opt = sel.options[sel.selectedIndex];
    var req = opt.getAttribute('data-requires-project') === '1';
    document.getElementById('projectGroup').hidden = !req;
    if (!req) projectCombo.setValue('');
}

window.lvmsLocationPicker = {
    mapId:          'destinationMap',
    latInputId:     'destLat',
    lngInputId:     'destLng',
    addressInputId: 'destination',
    lat:            <?= $reservation['destination_lat'] ? (float)$reservation['destination_lat'] : 'null' ?>,
    lng:            <?= $reservation['destination_lng'] ? (float)$reservation['destination_lng'] : 'null' ?>,
    editable:       true,
    defaultCenter:  { lat: <?= json_encode($warehouse_lat) ?>, lng: <?= json_encode($warehouse_lng) ?> },
    defaultZoom:    11,
    markerTitle:    'Destination',
};
</script>
<script src="<?= APP_BASE ?>/public/js/location_picker.js"></script>
<script
    src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&callback=initLocationPicker"
    loading="async" defer>
</script>
