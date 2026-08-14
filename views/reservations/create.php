<div class="page-header">
    <div class="page-header-left"><h2>New Reservation</h2></div>
    <a href="<?= Helpers::url('/reservations') ?>" class="btn btn-outline">← Back</a>
</div>

<form method="POST" action="<?= Helpers::url('/reservations/create') ?>">
<div class="form-card">
    <?php if ($needsDeptPicker): ?>
    <div class="form-section-title">Filed Under</div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Company <span class="required">*</span></label>
            <select class="form-select" id="companySelect" required onchange="handleCompanyChange()">
                <option value="">— Select —</option>
                <?php foreach ($companies as $co): ?>
                <option value="<?= (int) $co['company_id'] ?>"><?= Helpers::e($co['company_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Department <span class="required">*</span></label>
            <select class="form-select" name="department_id" id="departmentSelect"
                    data-all-depts="<?= Helpers::e(json_encode($departments)) ?>" required>
                <option value="">— Select Company First —</option>
            </select>
        </div>
    </div>
    <?php endif; ?>

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
        <div class="form-group" id="projectGroup">
            <label class="form-label">Project <span class="required" id="projectRequiredMark" hidden>*</span></label>
            <select class="form-select" name="project_id" id="projectSelect"
                    <?php if ($needsDeptPicker): ?>data-all-projects="<?= Helpers::e(json_encode($projects)) ?>"<?php endif; ?>>
                <option value="">— Select Project —</option>
                <?php if (!$needsDeptPicker): ?>
                <?php foreach ($projects as $pr): ?>
                <option value="<?= (int) $pr['project_id'] ?>"
                        data-code="<?= Helpers::e($pr['project_code'] ?? '') ?>"><?= Helpers::e($pr['project_name']) ?></option>
                <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <p class="form-hint" id="maxHint" style="display:none;"></p>
            <?php if ($needsDeptPicker): ?>
            <p class="form-hint">Select a company above to populate this list.</p>
            <?php endif; ?>
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

<script>
window.lvmsLocationPicker = {
    mapId:          'destinationMap',
    latInputId:     'destLat',
    lngInputId:     'destLng',
    addressInputId: 'destinationInput',
    lat:            null,
    lng:            null,
    editable:       true,
    defaultCenter:  { lat: <?= json_encode($warehouse_lat) ?>, lng: <?= json_encode($warehouse_lng) ?> },
    defaultZoom:    11,
    markerTitle:    'Destination',
};
</script>
<script src="<?= APP_BASE ?>/public/js/location_picker.js"></script>
<script src="<?= Helpers::assetUrl('/js/searchable_select.js') ?>"></script>
<script
    src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&callback=initLocationPicker"
    loading="async" defer>
</script>
<script>
var projectCombo = SearchableSelect.attach('projectSelect', {
    placeholder: 'Search project name or code…',
    emptyText:   'No matching projects'
});

<?php if ($needsDeptPicker): ?>
// Company → Department / Project cascade, for accounts with no home
// department (super_admin, fleet_admin). Same client-side filtering
// pattern as views/users/create.php's company → department select.
var allDepts    = JSON.parse(document.getElementById('departmentSelect').dataset.allDepts    || '[]');
var allProjects = JSON.parse(document.getElementById('projectSelect').dataset.allProjects || '[]');

function filterDepartments(companyId) {
    var select = document.getElementById('departmentSelect');
    select.innerHTML = '<option value="">— Select Department —</option>';
    allDepts
        .filter(function(d) { return parseInt(d.company_id) === companyId; })
        .forEach(function(d) {
            var opt = document.createElement('option');
            opt.value = d.department_id;
            opt.text  = d.department_name;
            select.add(opt);
        });
}

function filterProjectsByCompany(companyId) {
    projectCombo.setOptions(
        allProjects
            .filter(function (p) { return parseInt(p.company_id) === companyId; })
            .map(function (p) { return { value: p.project_id, label: p.project_name, code: p.project_code || '' }; })
    );
}

function handleCompanyChange() {
    var companyId = parseInt(document.getElementById('companySelect').value) || 0;
    filterDepartments(companyId);
    filterProjectsByCompany(companyId);
}
<?php endif; ?>

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

// Purpose → project requirement toggle.
// The project field stays visible for every purpose so a trip can always be
// tagged to a project; only whether it is MANDATORY changes. The server
// enforces the same rule in ReservationController::store().
function handlePurposeChange() {
    var sel = document.getElementById('purposeSelect');
    var opt = sel.options[sel.selectedIndex];
    var req = opt.getAttribute('data-requires-project') === '1';
    var max = opt.getAttribute('data-max');

    document.getElementById('projectRequiredMark').hidden = !req;
    projectCombo.setRequired(req);

    // Shown whenever a limit exists, not only when the project is mandatory:
    // TripLimitService::check() runs for ANY purpose once a project is
    // attached, so an optional project can hit the cap too.
    var hint = document.getElementById('maxHint');
    if (max !== '') {
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
        setLocationPickerPosition(parseFloat(proj.lat), parseFloat(proj.lng), 14);
    }
});
</script>
