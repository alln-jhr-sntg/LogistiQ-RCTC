<?php
$roleBadge = [
    'super_admin' => ['class' => 'badge-in-progress', 'label' => 'Super Admin'],
    'admin'       => ['class' => 'badge-approved',    'label' => 'Admin'],
    'employee'    => ['class' => 'badge-pending',     'label' => 'Employee'],
    'driver'      => ['class' => 'badge-maintenance', 'label' => 'Driver'],
];
?>
<div class="page-header">
    <div class="page-header-left"><h2>Users</h2><p>All system users across all companies</p></div>
    <a href="<?= Helpers::url('/users/create') ?>" class="btn btn-solid">
        <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg> Add User
    </a>
</div>

<form method="GET" action="<?= APP_BASE ?>/index.php">
    <input type="hidden" name="url" value="users">
    <div class="filter-bar">
        <select class="filter-select" name="role" onchange="this.form.submit()">
            <option value="" <?= $roleFilter === '' ? 'selected' : '' ?>>All Roles</option>
            <option value="super_admin" <?= $roleFilter === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
            <option value="admin"       <?= $roleFilter === 'admin'       ? 'selected' : '' ?>>Admin</option>
            <option value="employee"    <?= $roleFilter === 'employee'    ? 'selected' : '' ?>>Employee</option>
            <option value="driver"      <?= $roleFilter === 'driver'      ? 'selected' : '' ?>>Driver</option>
        </select>
        <select class="filter-select" name="company_id" onchange="this.form.submit()">
            <option value="0" <?= $companyFilter === 0 ? 'selected' : '' ?>>All Companies</option>
            <?php foreach ($companies as $co): ?>
            <option value="<?= (int) $co['company_id'] ?>"
                <?= $companyFilter === (int) $co['company_id'] ? 'selected' : '' ?>>
                <?= Helpers::e($co['company_code']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<div class="card"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr><th>Name</th><th>Role</th><th>Company / Dept</th><th>Email</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if (empty($users)): ?>
        <tr><td colspan="6" class="td-muted" style="text-align:center;padding:24px;">No users found.</td></tr>
    <?php else: ?>
        <?php foreach ($users as $u):
            $rb = $roleBadge[$u['role']] ?? ['class' => 'badge-pending', 'label' => $u['role']];
        ?>
        <tr>
            <td>
                <strong><?= Helpers::e($u['first_name'] . ' ' . $u['last_name']) ?></strong>
                <?php if ($u['employee_id']): ?>
                <br><span class="td-muted"><?= Helpers::e($u['employee_id']) ?></span>
                <?php endif; ?>
            </td>
            <td><span class="badge <?= $rb['class'] ?>"><?= $rb['label'] ?></span></td>
            <td class="td-muted">
                <?= Helpers::e($u['company_code']) ?>
                <?= $u['department_name'] ? ' — ' . Helpers::e($u['department_name']) : ' — —' ?>
            </td>
            <td class="td-muted"><?= Helpers::e($u['email']) ?></td>
            <td>
                <?php if ((int) $u['is_active'] === 1): ?>
                    <span class="badge badge-available">Active</span>
                <?php else: ?>
                    <span class="badge badge-cancelled">Inactive</span>
                <?php endif; ?>
            </td>
            <td><div class="td-actions">
                <a href="<?= Helpers::url('/users/' . $u['user_id'] . '/edit') ?>"
                   class="btn btn-outline btn-sm">Edit</a>
                <?php if ($u['role'] === ROLE_DRIVER): ?>
                <a href="<?= Helpers::url('/users/' . $u['user_id'] . '/driver-profile') ?>"
                   class="btn btn-outline btn-sm">License</a>
                <?php endif; ?>
            </div></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table></div></div>
