<div class="page-header">
    <div class="page-header-left">
        <h2>Edit <?= Helpers::e($reservation['reservation_code']) ?></h2>
        <p>Only pending reservations can be edited. Changes are logged.</p>
    </div>
    <a href="<?= Helpers::url('/reservations/' . $reservation['reservation_id']) ?>"
       class="btn btn-outline">← Back</a>
</div>

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
        <div class="form-group" id="projectGroup"
             style="<?= $reservation['project_id'] ? '' : 'display:none' ?>">
            <label class="form-label">Project</label>
            <select class="form-select" name="project_id" id="projectSelect">
                <option value="">— Select Project —</option>
                <?php foreach ($projects as $pr): ?>
                <option value="<?= (int) $pr['project_id'] ?>"
                    <?= (int) $pr['project_id'] === (int) ($reservation['project_id'] ?? 0) ? 'selected' : '' ?>>
                    <?= Helpers::e($pr['project_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form-group">
        <label class="form-label">Destination <span class="required">*</span></label>
        <input type="text" class="form-input" name="destination"
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

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
function handlePurposeChange() {
    var sel = document.getElementById('purposeSelect');
    var opt = sel.options[sel.selectedIndex];
    var req = opt.getAttribute('data-requires-project') === '1';
    document.getElementById('projectGroup').style.display = req ? '' : 'none';
    if (!req) document.getElementById('projectSelect').value = '';
}

// Initialise map — centre on existing pin if coordinates saved, else Metro Manila
var existingLat = <?= $reservation['destination_lat'] ? (float)$reservation['destination_lat'] : 'null' ?>;
var existingLng = <?= $reservation['destination_lng'] ? (float)$reservation['destination_lng'] : 'null' ?>;
var center = (existingLat && existingLng) ? [existingLat, existingLng] : [14.6804, 121.0281];
var zoom   = (existingLat && existingLng) ? 14 : 11;

var map    = L.map('destinationMap').setView(center, zoom);
var marker = null;
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Show existing pin
if (existingLat && existingLng) {
    marker = L.marker([existingLat, existingLng]).addTo(map)
        .bindPopup('Current destination').openPopup();
}

map.on('click', function(e) {
    var lat = e.latlng.lat.toFixed(8);
    var lng = e.latlng.lng.toFixed(8);
    document.getElementById('destLat').value = lat;
    document.getElementById('destLng').value = lng;
    if (marker) marker.remove();
    marker = L.marker([lat, lng]).addTo(map)
        .bindPopup('Destination').openPopup();
});
</script>
