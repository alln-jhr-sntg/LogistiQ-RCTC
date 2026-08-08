<div class="page-header">
    <div class="page-header-left">
        <h2>Edit Vehicle — <?= Helpers::e($vehicle['plate_number']) ?></h2>
        <p><?= Helpers::e($vehicle['brand'] . ' ' . $vehicle['model'] . ' ' . $vehicle['year_model']) ?></p>
    </div>
    <a href="<?= Helpers::url('/vehicles') ?>" class="btn btn-outline">← Back</a>
</div>

<?php
// Parse the vehicle's preferred_purpose_ids CSV into a set for O(1) lookups.
// PDO returns NULL as null and an empty column as ''. Both mean no preference.
$rawPref         = $vehicle['preferred_purpose_ids'] ?? '';
$selectedPurposes = ($rawPref !== null && $rawPref !== '')
    ? array_flip(array_map('intval', explode(',', $rawPref)))
    : [];
?>

<form method="POST" action="<?= Helpers::url('/vehicles/' . $vehicle['vehicle_id'] . '/edit') ?>">
<div class="form-card">
    <div class="form-section-title">Vehicle Information</div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Plate Number <span class="required">*</span></label>
            <input type="text" class="form-input" name="plate_number"
                   value="<?= Helpers::e($vehicle['plate_number']) ?>" required style="text-transform:uppercase;">
        </div>
        <div class="form-group">
            <label class="form-label">Category <span class="required">*</span></label>
            <select class="form-select" name="category_id" required>
                <option value="">— Select —</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= (int) $cat['category_id'] ?>"
                    <?= (int) $cat['category_id'] === (int) $vehicle['category_id'] ? 'selected' : '' ?>>
                    <?= Helpers::e($cat['category_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form-row form-row-3">
        <div class="form-group">
            <label class="form-label">Brand <span class="required">*</span></label>
            <input type="text" class="form-input" name="brand"
                   value="<?= Helpers::e($vehicle['brand']) ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">Model <span class="required">*</span></label>
            <input type="text" class="form-input" name="model"
                   value="<?= Helpers::e($vehicle['model']) ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">Year Model <span class="required">*</span></label>
            <input type="number" class="form-input" name="year_model"
                   value="<?= (int) $vehicle['year_model'] ?>" min="1990" max="2099" required>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Color</label>
            <input type="text" class="form-input" name="color"
                   value="<?= Helpers::e($vehicle['color'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Fuel Type <span class="required">*</span></label>
            <select class="form-select" name="fuel_type">
                <?php foreach (['diesel','gasoline','electric','hybrid'] as $ft): ?>
                <option value="<?= $ft ?>" <?= $vehicle['fuel_type'] === $ft ? 'selected' : '' ?>>
                    <?= ucfirst($ft) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-section-title">Status &amp; Odometer</div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Current Odometer (km)</label>
            <input type="number" class="form-input" name="current_odometer_km"
                   value="<?= (int) $vehicle['current_odometer_km'] ?>"
                   step="1" min="0">
            <p class="form-hint">Updated automatically when trips complete</p>
        </div>
        <div class="form-group">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <?php foreach (['available','reserved','on_trip','under_maintenance','retired'] as $st): ?>
                <option value="<?= $st ?>" <?= $vehicle['status'] === $st ? 'selected' : '' ?>>
                    <?= ucwords(str_replace('_', ' ', $st)) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form-group">
        <label class="form-label">Gross Weight — GVWR (kg) <span class="required">*</span></label>
        <input type="number" class="form-input" name="gross_weight_kg"
               value="<?= number_format((float) $vehicle['gross_weight_kg'], 2, '.', '') ?>"
               step="0.01" required>
        <p class="form-hint">LTO weight coding compliance</p>
    </div>
    <div class="form-group">
        <label class="form-label">Remarks</label>
        <textarea class="form-textarea" name="remarks"><?= Helpers::e($vehicle['remarks'] ?? '') ?></textarea>
    </div>

    <div class="form-section-title">AI Recommendation</div>
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
                           value="<?= (int) $purpose['purpose_id'] ?>"
                           <?= isset($selectedPurposes[(int) $purpose['purpose_id']]) ? 'checked' : '' ?>>
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
        <button type="submit" class="btn btn-solid">Save Changes</button>
        <a href="<?= Helpers::url('/vehicles') ?>" class="btn btn-outline">Cancel</a>
    </div>
</div>
</form>
