<div class="page-header">
    <div class="page-header-left">
        <h2>Departments &mdash; <?= Helpers::e($company['company_name']) ?></h2>
    </div>
    <a href="<?= Helpers::url('/companies') ?>" class="btn btn-outline">← Companies</a>
</div>

<div class="form-card" style="margin-bottom:20px;">
    <div class="form-section-title">Add Department</div>
    <form method="POST" action="<?= Helpers::url('/companies/' . $company['company_id'] . '/departments') ?>">
        <?= Csrf::field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Department Name <span class="required">*</span></label>
                <input type="text" class="form-input" name="department_name" required>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <input type="text" class="form-input" name="description">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-solid">Add Department</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Description</th>
                    <th>Members</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($departments)): ?>
                <tr>
                    <td colspan="5" class="td-muted" style="text-align:center;padding:24px;">
                        No departments yet. Add one above.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($departments as $dept): ?>
                <tr>
                    <td><strong><?= Helpers::e($dept['department_name']) ?></strong></td>
                    <td class="td-muted"><?= $dept['description'] ? Helpers::e($dept['description']) : '—' ?></td>
                    <td class="td-muted"><?= (int) $dept['user_count'] ?> user<?= (int) $dept['user_count'] !== 1 ? 's' : '' ?></td>
                    <td>
                        <?php if ((int) $dept['is_active'] === 1): ?>
                            <span class="badge badge-available">Active</span>
                        <?php else: ?>
                            <span class="badge badge-cancelled">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="td-actions">
                            <a href="#" class="btn btn-outline btn-sm">Edit</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
