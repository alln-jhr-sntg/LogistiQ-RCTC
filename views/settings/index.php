<div class="page-header"><div class="page-header-left"><h2>System Settings</h2><p>Global configuration — changes affect all companies</p></div></div>
<form method="POST" action="<?= Helpers::url('/settings') ?>"><div class="form-card">
<div class="form-section-title">Application</div>
<?php foreach ($settings as $s): ?>
<div class="form-group">
    <label class="form-label"><?= Helpers::e($s['setting_key']) ?><?php if ($s['description']): ?><span style="font-weight:400;color:var(--clr-text-3);margin-left:6px;">— <?= Helpers::e($s['description']) ?></span><?php endif; ?></label>
    <input type="text" class="form-input" name="settings[<?= Helpers::e($s['setting_key']) ?>]" value="<?= Helpers::e($s['setting_value']) ?>">
</div>
<?php endforeach; ?>
<div class="form-actions"><button type="submit" class="btn btn-solid">Save Settings</button></div>
</div></form>