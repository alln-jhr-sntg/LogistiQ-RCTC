<div class="page-header page-header-end">
    <div class="page-header-actions">
        <button type="button" class="btn btn-solid" onclick="document.getElementById('addDepartmentModal').style.display='flex';">
            <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg> Add Department
        </button>
        <a href="<?= Helpers::url('/companies') ?>" class="btn btn-outline">← Companies</a>
    </div>
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
                    <td colspan="5" class="td-muted td-empty">
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
                            <button type="button" class="btn btn-outline btn-sm"
                                onclick="lvmsEditDepartment(
                                    <?= (int) $dept['department_id'] ?>,
                                    '<?= addslashes(Helpers::e($dept['department_name'])) ?>',
                                    '<?= addslashes(Helpers::e($dept['description'] ?? '')) ?>'
                                )">Edit</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Department Modal -->
<div id="addDepartmentModal" class="modal-overlay">
    <div class="modal-card modal-card-wide">
        <div class="modal-header">
            <h3>Add Department</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('addDepartmentModal').style.display='none';" aria-label="Close">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
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
            <div class="modal-actions">
                <button type="submit" class="btn btn-solid">Add Department</button>
                <button type="button" class="btn btn-outline" onclick="document.getElementById('addDepartmentModal').style.display='none';">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Department Modal -->
<div id="editDepartmentModal" class="modal-overlay">
    <div class="modal-card modal-card-wide">
        <div class="modal-header">
            <h3>Edit Department</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('editDepartmentModal').style.display='none';" aria-label="Close">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form id="editDepartmentForm" method="POST" action="">
            <?= Csrf::field() ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Department Name <span class="required">*</span></label>
                    <input type="text" class="form-input" name="department_name" id="editDepartmentName" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <input type="text" class="form-input" name="description" id="editDepartmentDescription">
                </div>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-solid">Save Changes</button>
                <button type="button" class="btn btn-outline" onclick="document.getElementById('editDepartmentModal').style.display='none';">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function lvmsEditDepartment(id, name, description) {
    document.getElementById('editDepartmentForm').action = APP_BASE + '/index.php?url=companies/<?= (int) $company['company_id'] ?>/departments/' + id + '/edit';
    document.getElementById('editDepartmentName').value = name;
    document.getElementById('editDepartmentDescription').value = description;
    document.getElementById('editDepartmentModal').style.display = 'flex';
}
['addDepartmentModal','editDepartmentModal'].forEach(function(id) {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});
</script>
