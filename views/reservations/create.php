<div class="page-header">
    <div class="page-header-left"><h2>New Reservation</h2></div>
    <a href="<?= Helpers::url('/reservations') ?>" class="btn btn-outline">← Back</a>
</div>

<form method="POST" action="<?= Helpers::url('/reservations/create') ?>">
<div class="form-card">
    <div class="form-section-title">Trip Details</div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Trip Purpose <span class="required">*</span></label>
            <select class="form-select" name="purpose_id" id="purposeSelect" required onchange="handlePurposeChange()">
                <option value="">— Select Purpose —</option>
                <?php foreach ($purposes as $p): ?>
                <option value="<?= (int) $p['purpose_id'] ?>"
                        data-requires-project="<?= (int) $p['requires_project'] ?>"
                        data-max="<?= $p['max_per_project'] !== null ? (int) $p['max_per_project'] : '' ?>">
                    <?= Helpers::e($p['purpose_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" id="projectGroup" style="display:none;">
            <label class="form-label">Project <span class="required">*</span></label>
            <select class="form-select" name="project_id" id="projectSelect">
                <option value="">— Select Project —</option>
                <?php foreach ($projects as $pr): ?>
                <option value="<?= (int) $pr['project_id'] ?>"><?= Helpers::e($pr['project_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="form-hint" id="maxHint" style="display:none;"></p>
        </div>
    </div>
    <div class="form-group">
        <label class="form-label">Destination <span class="required">*</span></label>
        <input type="text" class="form-input" name="destination" id="destinationInput"
               placeholder="Work site or delivery address" required>
    </div>

    <!-- Hidden coordinate fields + map directly under destination -->
    <input type="hidden" name="destination_lat" id="destLat" value="">
    <input type="hidden" name="destination_lng" id="destLng" value="">
    <div class="form-group">
        <div id="destinationMap" style="height:240px;border-radius:var(--radius-md);overflow:hidden;border:1px solid var(--clr-border);"></div>
        <p class="form-hint">Click the map to pin the destination. Coordinates are optional.</p>
    </div>

    <div class="form-group">
        <label class="form-label">Trip Details</label>
        <textarea class="form-textarea" name="trip_details" rows="2"
                  placeholder="Additional context for the admin reviewing this request"></textarea>
    </div>

    <div class="form-section-title">Schedule</div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Departure <span class="required">*</span></label>
            <input type="datetime-local" class="form-input" name="departure_datetime" required>
        </div>
        <div class="form-group">
            <label class="form-label">Estimated Return <span class="required">*</span></label>
            <input type="datetime-local" class="form-input" name="return_datetime" required>
        </div>
    </div>

    <div class="form-section-title">Passengers &amp; Cargo</div>
    <div class="form-row form-row-3">
        <div class="form-group">
            <label class="form-label">Passengers <span class="required">*</span></label>
            <input type="number" class="form-input" name="passenger_count" min="1" required>
        </div>
        <div class="form-group">
            <label class="form-label">Cargo Weight (kg)</label>
            <input type="number" class="form-input" name="cargo_weight_kg" min="0" step="0.01" value="0">
        </div>
        <div class="form-group">
            <label class="form-label">Cargo Description</label>
            <input type="text" class="form-input" name="cargo_description" placeholder="e.g. Cement bags, rebar">
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-solid">Submit Reservation</button>
        <a href="<?= Helpers::url('/reservations') ?>" class="btn btn-outline">Cancel</a>
    </div>
</div>
</form>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Project location lookup — prefill destination when a project is selected
var projectData = <?= json_encode(array_combine(
    array_column($projects, 'project_id'),
    array_map(function($p) {
        return [
            'location' => $p['location']     ?? '',
            'lat'      => $p['location_lat'] ?? null,
            'lng'      => $p['location_lng'] ?? null,
        ];
    }, $projects)
)) ?>;

// Purpose → project field toggle
function handlePurposeChange() {
    var sel = document.getElementById('purposeSelect');
    var opt = sel.options[sel.selectedIndex];
    var req = opt.getAttribute('data-requires-project') === '1';
    var max = opt.getAttribute('data-max');

    document.getElementById('projectGroup').style.display = req ? '' : 'none';
    if (!req) document.getElementById('projectSelect').value = '';

    var hint = document.getElementById('maxHint');
    if (req && max !== '') {
        hint.textContent = 'Max ' + max + ' reservation(s) of this type per project.';
        hint.style.display = '';
    } else {
        hint.style.display = 'none';
    }
}

// Project → prefill destination address + map pin
document.getElementById('projectSelect').addEventListener('change', function() {
    var pid  = this.value;
    var proj = pid ? projectData[pid] : null;
    if (!proj) return;

    if (proj.location) {
        document.getElementById('destinationInput').value = proj.location;
    }
    if (proj.lat && proj.lng) {
        document.getElementById('destLat').value = proj.lat;
        document.getElementById('destLng').value = proj.lng;
        if (marker) marker.remove();
        marker = L.marker([proj.lat, proj.lng]).addTo(map)
            .bindPopup('Project Location').openPopup();
        map.setView([proj.lat, proj.lng], 14);
    }
});

// Leaflet destination map
var map    = L.map('destinationMap').setView([14.6804, 121.0281], 11);
var marker = null;
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

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
