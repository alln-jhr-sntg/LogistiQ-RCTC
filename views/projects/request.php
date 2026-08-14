<div class="page-header">
    <div class="page-header-left">
        <p>Submit a project for review. You will be notified once it is approved or rejected.</p>
    </div>
    <a href="<?= Helpers::url('/projects') ?>" class="btn btn-outline">← Back</a>
</div>

<form method="POST" action="<?= Helpers::url('/projects/request') ?>">
<div class="form-card">

    <div class="form-section-title">Project Details</div>

    <div class="form-group">
        <label class="form-label">Project Name <span class="required">*</span></label>
        <input type="text" class="form-input" name="project_name" required
               placeholder="e.g. Ortigas Site Fit-Out">
    </div>

    <div class="form-group">
        <label class="form-label">Company</label>
        <input type="text" class="form-input" value="<?= Helpers::e($company['company_name'] ?? '—') ?>" readonly>
        <p class="form-hint">Requests are always filed under your own company.</p>
    </div>

    <div class="form-group">
        <label class="form-label">Description <span class="required">*</span></label>
        <textarea class="form-textarea" name="description" rows="4" required
                  placeholder="What the project involves and why it needs vehicle support"></textarea>
        <p class="form-hint">Explain the work clearly — this is what the reviewer decides on.</p>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Start Date</label>
            <input type="date" class="form-input" name="start_date">
        </div>
        <div class="form-group">
            <label class="form-label">End Date</label>
            <input type="date" class="form-input" name="end_date">
        </div>
    </div>

    <div class="form-section-title">Project Site Location</div>

    <div class="form-group">
        <label class="form-label">Location Name</label>
        <input type="text" class="form-input" name="location" id="locationInput"
               placeholder="e.g. Ortigas, Pasig City">
        <p class="form-hint">Type the name then pin the exact location on the map below — or click the map and we'll suggest an address.</p>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Site Latitude</label>
            <input type="text" class="form-input" name="location_lat" id="siteLat"
                   placeholder="Click map to set" readonly>
        </div>
        <div class="form-group">
            <label class="form-label">Site Longitude</label>
            <input type="text" class="form-input" name="location_lng" id="siteLng"
                   placeholder="Click map to set" readonly>
        </div>
    </div>

    <div class="form-group">
        <p class="form-hint">📍 Click anywhere on the map to pin the project site.</p>
        <div id="projectMap" class="map-panel"></div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-solid">Submit Request</button>
        <a href="<?= Helpers::url('/projects') ?>" class="btn btn-outline">Cancel</a>
    </div>

</div>
</form>

<script>
window.lvmsLocationPicker = {
    mapId:          'projectMap',
    latInputId:     'siteLat',
    lngInputId:     'siteLng',
    addressInputId: 'locationInput',
    lat:            null,
    lng:            null,
    editable:       true,
    defaultCenter:  { lat: <?= json_encode($warehouse_lat) ?>, lng: <?= json_encode($warehouse_lng) ?> },
    defaultZoom:    11,
    markerTitle:    'Project Site',
    warehouseLat:   <?= json_encode($warehouse_lat) ?>,
    warehouseLng:   <?= json_encode($warehouse_lng) ?>,
};
</script>
<script src="<?= APP_BASE ?>/public/js/location_picker.js"></script>
<script
    src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&callback=initLocationPicker"
    loading="async" defer>
</script>
