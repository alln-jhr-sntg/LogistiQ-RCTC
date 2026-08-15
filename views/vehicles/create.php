<div class="page-header">
    <div class="page-header-left"></div>
    <a href="<?= Helpers::url('/vehicles') ?>" class="btn btn-outline">← Back</a>
</div>

<form method="POST" action="<?= Helpers::url('/vehicles/create') ?>">
<?= Csrf::field() ?>
<div class="form-card">
    <div class="form-section-title">Vehicle Information</div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Plate Number <span class="required">*</span></label>
            <input type="text" class="form-input" name="plate_number" required style="text-transform:uppercase;">
        </div>
        <div class="form-group">
            <label class="form-label">Category <span class="required">*</span></label>
            <select class="form-select" name="category_id" required>
                <option value="">— Select —</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= (int) $cat['category_id'] ?>"><?= Helpers::e($cat['category_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form-row form-row-3">
        <div class="form-group">
            <label class="form-label">Brand <span class="required">*</span></label>
            <input type="text" class="form-input" name="brand" required>
        </div>
        <div class="form-group">
            <label class="form-label">Model <span class="required">*</span></label>
            <input type="text" class="form-input" name="model" required>
        </div>
        <div class="form-group">
            <label class="form-label">Year Model <span class="required">*</span></label>
            <input type="number" class="form-input" name="year_model" min="1990" max="2099" required>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Color</label>
            <input type="text" class="form-input" name="color">
        </div>
        <div class="form-group">
            <label class="form-label">Fuel Type <span class="required">*</span></label>
            <select class="form-select" name="fuel_type">
                <option value="diesel">Diesel</option>
                <option value="gasoline">Gasoline</option>
                <option value="electric">Electric</option>
                <option value="hybrid">Hybrid</option>
            </select>
        </div>
    </div>

    <div class="form-section-title">Capacity &amp; Weight</div>
    <div class="form-row form-row-3">
        <div class="form-group">
            <label class="form-label">Passenger Capacity <span class="required">*</span></label>
            <input type="number" class="form-input" name="passenger_capacity" min="1" required>
        </div>
        <div class="form-group">
            <label class="form-label">Cargo Capacity (kg)</label>
            <input type="number" class="form-input" name="cargo_capacity_kg" min="0" step="0.01" value="0">
        </div>
        <div class="form-group">
            <label class="form-label">Gross Weight — GVWR (kg) <span class="required">*</span></label>
            <input type="number" class="form-input" name="gross_weight_kg" step="0.01" required>
            <p class="form-hint">LTO weight coding compliance</p>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Current Odometer (km)</label>
            <input type="number" class="form-input" name="current_odometer_km" value="0" step="0.01">
        </div>
        <div class="form-group">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="available">Available</option>
                <option value="under_maintenance">Under Maintenance</option>
            </select>
        </div>
    </div>
    <div class="form-group">
        <label class="form-label">Remarks</label>
        <textarea class="form-textarea" name="remarks"></textarea>
    </div>

    <div class="form-section-title">Recommendation</div>
    <div class="form-group">
        <label class="form-label">Preferred Trip Purposes</label>
        <div style="border: 1.5px solid var(--clr-border); border-radius: var(--radius-md);
                    padding: 10px 14px; max-height: 160px; overflow-y: auto;
                    background: var(--clr-bg); display: flex; flex-direction: column; gap: 8px;">
            <?php if (empty($purposes)): ?>
                <p style="font-size:13px; color:var(--clr-text-3); margin:0;">No active trip purposes found.</p>
            <?php else: ?>
                <?php foreach ($purposes as $purpose): ?>
                <label style="display:flex; align-items:center; gap:8px;
                               font-size:14px; color:var(--clr-text); cursor:pointer;">
                    <input type="checkbox"
                           name="preferred_purpose_ids[]"
                           value="<?= (int) $purpose['purpose_id'] ?>">
                    <?= Helpers::e($purpose['purpose_name']) ?>
                </label>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <p class="form-hint">
            Checked purposes score 1.0 for this vehicle; unchecked purposes score 0.0.
            Leave all unchecked for neutral scoring (0.5) — use this when the vehicle
            has no strong preference for any particular trip type.
        </p>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-solid">Add Vehicle</button>
        <a href="<?= Helpers::url('/vehicles') ?>" class="btn btn-outline">Cancel</a>
    </div>
</div>
</form>
