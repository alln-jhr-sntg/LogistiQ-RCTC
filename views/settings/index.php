<form method="POST" action="<?= Helpers::url('/settings') ?>"><?= Csrf::field() ?>
<?php foreach ($groups as $groupKey => $groupLabel): ?>
    <?php
        $groupSpecs = array_filter($specs, static fn($spec) => $spec['group'] === $groupKey);
        if (empty($groupSpecs)) {
            continue;
        }
    ?>
    <div class="form-card">
        <div class="form-section-title"><?= Helpers::e($groupLabel) ?></div>
        <?php foreach ($groupSpecs as $key => $spec): ?>
            <?php $value = $values[$key] ?? ''; ?>
            <div class="form-group">
                <label class="form-label" for="setting_<?= Helpers::e($key) ?>"><?= Helpers::e($spec['label']) ?></label>
                <?php if (in_array($spec['type'], ['latitude', 'longitude', 'int_min'], true)): ?>
                <input type="number"
                       step="<?= $spec['type'] === 'int_min' ? '1' : 'any' ?>"
                       <?= $spec['type'] === 'int_min' ? 'min="' . (int) $spec['min'] . '"' : '' ?>
                       class="form-input" id="setting_<?= Helpers::e($key) ?>"
                       name="settings[<?= Helpers::e($key) ?>]" value="<?= Helpers::e($value) ?>">
                <?php elseif ($spec['type'] === 'prefix'): ?>
                <input type="text" maxlength="6" class="form-input" id="setting_<?= Helpers::e($key) ?>"
                       name="settings[<?= Helpers::e($key) ?>]" value="<?= Helpers::e($value) ?>">
                <?php else: ?>
                <input type="text" maxlength="<?= (int) ($spec['max'] ?? 255) ?>" class="form-input" id="setting_<?= Helpers::e($key) ?>"
                       name="settings[<?= Helpers::e($key) ?>]" value="<?= Helpers::e($value) ?>">
                <?php endif; ?>
                <?php if (!empty($descriptions[$key])): ?>
                <p class="form-hint"><?= Helpers::e($descriptions[$key]) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
<div class="form-actions"><button type="submit" class="btn btn-solid">Save Settings</button></div>
</form>
