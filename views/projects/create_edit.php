<div class="page-header"><div class="page-header-left"><h2><?= isset($project) && $project ? 'Edit Project' : 'New Project' ?></h2></div><a href="<?= Helpers::url('/projects') ?>" class="btn btn-outline">← Back</a></div>
<form method="POST" action="<?= isset($project) && $project ? Helpers::url('/projects/' . $project['project_id'] . '/edit') : Helpers::url('/projects/create') ?>"><div class="form-card">
<div class="form-section-title">Project Details</div>
<div class="form-row">
    <div class="form-group"><label class="form-label">Project Name <span class="required">*</span></label><input type="text" class="form-input" name="project_name" value="<?= Helpers::e($project['project_name'] ?? '') ?>" required></div>
    <div class="form-group"><label class="form-label">Project Code</label><input type="text" class="form-input" name="project_code" value="<?= Helpers::e($project['project_code'] ?? '') ?>" placeholder="e.g. RMX-2025-001"></div>
</div>
<div class="form-group"><label class="form-label">Company <span class="required">*</span></label><select class="form-select" name="company_id" required>
    <option value="">— Select Company —</option>
    <?php foreach ($companies as $co): ?>
    <option value="<?= (int) $co['company_id'] ?>"
        <?= (int)($project['company_id'] ?? 0) === (int)$co['company_id'] ? 'selected' : '' ?>>
        <?= Helpers::e($co['company_name']) ?>
    </option>
    <?php endforeach; ?>
</select></div>
<div class="form-row">
    <div class="form-group"><label class="form-label">Start Date</label><input type="date" class="form-input" name="start_date" value="<?= Helpers::e($project['start_date'] ?? '') ?>"></div>
    <div class="form-group"><label class="form-label">End Date</label><input type="date" class="form-input" name="end_date" value="<?= Helpers::e($project['end_date'] ?? '') ?>"></div>
</div>
<div class="form-section-title">Project Site Location</div>
<div class="form-group"><label class="form-label">Location Name</label><input type="text" class="form-input" name="location" id="locationInput" value="<?= Helpers::e($project['location'] ?? '') ?>" placeholder="e.g. Ortigas, Pasig City"><p class="form-hint">Type the name then pin the exact location on the map below — or click the map and we'll suggest an address.</p></div>
<div class="form-row">
    <div class="form-group"><label class="form-label">Site Latitude</label><input type="text" class="form-input" name="location_lat" id="siteLat" value="<?= Helpers::e($project['location_lat'] ?? '') ?>" placeholder="Click map to set" readonly style="background:var(--clr-surface-2);"></div>
    <div class="form-group"><label class="form-label">Site Longitude</label><input type="text" class="form-input" name="location_lng" id="siteLng" value="<?= Helpers::e($project['location_lng'] ?? '') ?>" placeholder="Click map to set" readonly style="background:var(--clr-surface-2);"></div>
</div>
<div style="margin-bottom:8px;"><div style="font-size:13px;color:var(--clr-text-3);margin-bottom:8px;">📍 Click anywhere on the map to pin the project site.</div><div id="projectMap" style="height:320px;border-radius:var(--radius-md);border:1.5px solid var(--clr-border);overflow:hidden;"></div></div>
<div class="form-group"><label style="display:flex;align-items:center;gap:8px;font-size:14px;color:var(--clr-text-2);cursor:pointer;"><input type="checkbox" name="is_active" value="1" <?= ($project['is_active'] ?? 1) ? 'checked' : '' ?>> Project is active</label></div>
<div class="form-actions"><button type="submit" class="btn btn-solid"><?= isset($project) && $project ? 'Save Changes' : 'Create Project' ?></button><a href="<?= Helpers::url('/projects') ?>" class="btn btn-outline">Cancel</a></div>
</div></form>
<script>
window.lvmsLocationPicker = {
    mapId:          'projectMap',
    latInputId:     'siteLat',
    lngInputId:     'siteLng',
    addressInputId: 'locationInput',
    lat:            <?= !empty($project['location_lat']) ? (float)$project['location_lat'] : 'null' ?>,
    lng:            <?= !empty($project['location_lng']) ? (float)$project['location_lng'] : 'null' ?>,
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