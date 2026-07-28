<div class="page-header">
    <div class="page-header-left">
        <h2>Admin Department Access</h2>
        <p>Grant admins access to manage departments outside their own company</p>
    </div>
    <a href="<?= Helpers::url('/companies') ?>" class="btn btn-outline">← Companies</a>
</div>

<div class="form-card" style="margin-bottom:24px;">
    <div class="form-section-title">Grant Access</div>
    <form method="POST" action="<?= Helpers::url('/companies/access') ?>">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Admin User <span class="required">*</span></label>
                <select class="form-select" name="admin_user_id" required>
                    <option value="">— Select Admin —</option>
                    <?php foreach ($admins as $admin): ?>
                    <option value="<?= (int) $admin['user_id'] ?>">
                        <?= Helpers::e($admin['first_name'] . ' ' . $admin['last_name']) ?>
                        (Admin — <?= Helpers::e($admin['company_code']) ?>)
                    </option>
                    <?php endforeach; ?>
                    <?php if (empty($admins)): ?>
                    <option disabled>No admin accounts yet</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Department <span class="required">*</span></label>
                <select class="form-select" name="department_id" required>
                    <option value="">— Select —</option>
                    <?php
                    // Group departments by company code for optgroups
                    $grouped = [];
                    foreach ($departments as $dept) {
                        $grouped[$dept['company_code']][] = $dept;
                    }
                    foreach ($grouped as $code => $depts):
                    ?>
                    <optgroup label="<?= Helpers::e($code) ?>">
                        <?php foreach ($depts as $dept): ?>
                        <option value="<?= (int) $dept['department_id'] ?>">
                            <?= Helpers::e($dept['department_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endforeach; ?>
                    <?php if (empty($departments)): ?>
                    <option disabled>No departments yet</option>
                    <?php endif; ?>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-solid">Grant Access</button>
        </div>
    </form>
</div>

<div class="section-title" style="margin-bottom:12px;">Current Access Grants</div>
<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Admin User</th>
                    <th>Granted Access To</th>
                    <th>Granted By</th>
                    <th>Granted At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($grants)): ?>
                <tr>
                    <td colspan="5" class="td-muted" style="text-align:center;padding:24px;">
                        No access grants yet.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($grants as $g): ?>
                <tr>
                    <td>
                        <strong><?= Helpers::e($g['admin_first_name'] . ' ' . $g['admin_last_name']) ?></strong><br>
                        <span class="td-muted">Admin — <?= Helpers::e($g['admin_company']) ?></span>
                    </td>
                    <td><?= Helpers::e($g['dept_company'] . ' — ' . $g['department_name']) ?></td>
                    <td class="td-muted"><?= Helpers::e($g['granter_first_name'] . ' ' . $g['granter_last_name']) ?></td>
                    <td class="td-muted"><?= date('M d, Y', strtotime($g['granted_at'])) ?></td>
                    <td>
                        <div class="td-actions">
                            <button type="button" class="btn btn-danger btn-sm"
                                onclick="lvmsConfirmRevoke(
                                    <?= (int) $g['access_id'] ?>,
                                    '<?= addslashes(Helpers::e($g['admin_first_name'] . ' ' . $g['admin_last_name'])) ?>',
                                    '<?= addslashes(Helpers::e($g['dept_company'] . ' — ' . $g['department_name'])) ?>'
                                )">
                                Revoke
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($grants as $g): ?>
<form id="revokeForm<?= (int) $g['access_id'] ?>"
      method="POST"
      action="<?= Helpers::url('/companies/access/' . $g['access_id'] . '/revoke') ?>">
</form>
<?php endforeach; ?>

<!-- Revoke Confirmation Modal -->
<div id="revokeModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-icon modal-icon-danger">
            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        </div>
        <h3 class="modal-title">Revoke Access?</h3>
        <p class="modal-body modal-body-gap-sm">You are about to revoke access for</p>
        <p id="revokeDescription" class="modal-reason-preview"></p>
        <p class="modal-body">This admin will no longer be able to manage that department.</p>
        <div class="modal-actions">
            <button type="button" class="btn btn-outline"
                onclick="document.getElementById('revokeModal').style.display='none';">
                Cancel
            </button>
            <button type="button" id="revokeConfirmBtn" class="btn btn-danger">Revoke</button>
        </div>
    </div>
</div>

<script>
function lvmsConfirmRevoke(id, adminName, dept) {
    document.getElementById('revokeDescription').textContent = adminName + ' → ' + dept;
    document.getElementById('revokeConfirmBtn').onclick = function() {
        document.getElementById('revokeForm' + id).submit();
    };
    document.getElementById('revokeModal').style.display = 'flex';
}
document.getElementById('revokeModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
