<div class="page-header">
    <div class="page-header-left">
        <p>
            <?= $user['employee_id'] ? Helpers::e($user['employee_id']) . ' — ' : '' ?>
            <?= Helpers::e($user['company_name']) ?>
        </p>
    </div>
    <a href="<?= Helpers::url('/users') ?>" class="btn btn-outline">← Back</a>
</div>

<?php if (!$profile): ?>
<div style="background:var(--clr-surface-2);border:1px solid var(--clr-border);border-radius:var(--radius-lg);
            padding:16px 20px;margin-bottom:24px;font-size:14px;color:var(--clr-text-3);">
    No driver profile yet. Fill in the form below to create one.
</div>
<?php endif; ?>

<form method="POST" action="<?= Helpers::url('/users/' . $user['user_id'] . '/driver-profile') ?>"
      enctype="multipart/form-data">
<?= Csrf::field() ?>
<div class="form-card">
    <div class="form-section-title">License Information</div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">License Number <span class="required">*</span></label>
            <input type="text" class="form-input" name="license_number"
                   value="<?= Helpers::e($profile['license_number'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">License Type <span class="required">*</span></label>
            <select class="form-select" name="license_type" required>
                <?php foreach (['Professional', 'Non-Professional'] as $lt): ?>
                <option value="<?= $lt ?>"
                    <?= ($profile['license_type'] ?? '') === $lt ? 'selected' : '' ?>>
                    <?= $lt ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">License Expiry <span class="required">*</span></label>
            <input type="date" class="form-input" name="license_expiry"
                   value="<?= Helpers::e($profile['license_expiry'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">DL Codes</label>
            <input type="text" class="form-input" name="restriction_codes"
                   value="<?= Helpers::e($profile['restriction_codes'] ?? '') ?>"
                   placeholder="e.g. A,B,C">
            <p class="form-hint">LTO DL codes, comma-separated (e.g. A, B, C, D)</p>
        </div>
    </div>
    <div class="form-group">
        <label class="form-label">License Photo</label>
        <input type="file" class="form-input" name="license_photo" accept="image/jpeg,image/png">
        <?php if (!empty($profile['license_photo'])): ?>
        <p class="form-hint">Current: <?= Helpers::e($profile['license_photo']) ?></p>
        <?php endif; ?>
    </div>

    <div class="form-section-title">Availability Status</div>
    <div class="form-group">
        <label class="form-label">Current Status <span class="required">*</span></label>
        <select class="form-select" name="status" required>
            <?php foreach (['available', 'off_duty', 'on_leave'] as $st): ?>
            <option value="<?= $st ?>"
                <?= ($profile['status'] ?? 'available') === $st ? 'selected' : '' ?>>
                <?= ucwords(str_replace('_', ' ', $st)) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <p class="form-hint">"On Trip" status is managed automatically by the trip system.</p>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-solid">Save Driver Profile</button>
        <a href="<?= Helpers::url('/users') ?>" class="btn btn-outline">Cancel</a>
    </div>
</div>
</form>
