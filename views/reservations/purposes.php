<div class="page-header">
    <div class="page-header-left">
        <h2>Trip Purposes</h2>
        <p>Manage valid trip purposes for reservations</p>
    </div>
    <div style="display:flex; gap:8px;">
        <a href="<?= Helpers::url('/reservations') ?>" class="btn btn-outline">← Reservations</a>
        <button class="btn btn-solid" onclick="document.getElementById('addPurposeModal').style.display='flex';">
            <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg> Add Purpose
        </button>
    </div>
</div>

<div class="card"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Purpose</th>
            <th>Requires Project</th>
            <th>Max / Project</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($purposes)): ?>
        <tr>
            <td colspan="5" class="td-muted" style="text-align:center;padding:24px;">
                No trip purposes yet. Add one above.
            </td>
        </tr>
    <?php else: ?>
        <?php foreach ($purposes as $p): ?>
        <tr>
            <td>
                <strong><?= Helpers::e($p['purpose_name']) ?></strong>
                <?php if ($p['description']): ?>
                <br><span class="td-muted"><?= Helpers::e($p['description']) ?></span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ((int) $p['requires_project'] === 1): ?>
                    <span class="badge badge-approved">Yes</span>
                <?php else: ?>
                    <span class="badge badge-cancelled">No</span>
                <?php endif; ?>
            </td>
            <td class="td-muted">
                <?= $p['max_per_project'] !== null ? (int) $p['max_per_project'] : '—' ?>
            </td>
            <td>
                <?php if ((int) $p['is_active'] === 1): ?>
                    <span class="badge badge-available">Active</span>
                <?php else: ?>
                    <span class="badge badge-cancelled">Inactive</span>
                <?php endif; ?>
            </td>
            <td><div class="td-actions">
                <button class="btn btn-outline btn-sm"
                    onclick="lvmsEditPurpose(
                        <?= (int) $p['purpose_id'] ?>,
                        '<?= addslashes(Helpers::e($p['purpose_name'])) ?>',
                        '<?= addslashes(Helpers::e($p['description'] ?? '')) ?>',
                        <?= (int) $p['requires_project'] ?>,
                        <?= $p['max_per_project'] !== null ? (int) $p['max_per_project'] : 'null' ?>,
                        <?= (int) $p['is_active'] ?>
                    )">Edit</button>
            </div></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table></div></div>

<!-- ── Add Purpose Modal ────────────────────────────────── -->
<div id="addPurposeModal" class="modal-overlay">
    <div class="modal-card modal-card-wide">
        <div class="modal-header">
            <h3>New Trip Purpose</h3>
            <button type="button" class="modal-close"
                    onclick="document.getElementById('addPurposeModal').style.display='none';" aria-label="Close">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= Helpers::url('/reservations/purposes') ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Purpose Name <span class="required">*</span></label>
                    <input type="text" class="form-input" name="purpose_name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Max Trips per Project</label>
                    <input type="number" class="form-input" name="max_per_project" min="1"
                           placeholder="Leave blank for no limit">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <input type="text" class="form-input" name="description">
            </div>
            <div class="form-group">
                <label class="form-label">
                    <input type="checkbox" name="requires_project" value="1">
                    Requires a project to be selected
                </label>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-solid">Save Purpose</button>
                <button type="button" class="btn btn-outline"
                        onclick="document.getElementById('addPurposeModal').style.display='none';">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Edit Purpose Modal ───────────────────────────────── -->
<div id="editPurposeModal" class="modal-overlay">
    <div class="modal-card modal-card-wide">
        <div class="modal-header">
            <h3>Edit Trip Purpose</h3>
            <button type="button" class="modal-close"
                    onclick="document.getElementById('editPurposeModal').style.display='none';" aria-label="Close">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form id="editPurposeForm" method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Purpose Name <span class="required">*</span></label>
                    <input type="text" class="form-input" name="purpose_name" id="editPurposeName" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Max Trips per Project</label>
                    <input type="number" class="form-input" name="max_per_project"
                           id="editMaxPerProject" min="1" placeholder="Leave blank for no limit">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <input type="text" class="form-input" name="description" id="editDescription">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" name="requires_project" id="editRequiresProject" value="1">
                        Requires a project to be selected
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" name="is_active" id="editIsActive" value="1" checked>
                        Active
                    </label>
                </div>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-solid">Save Changes</button>
                <button type="button" class="btn btn-outline"
                        onclick="document.getElementById('editPurposeModal').style.display='none';">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function lvmsEditPurpose(id, name, description, requiresProject, maxPerProject, isActive) {
    document.getElementById('editPurposeForm').action =
        APP_BASE + '/index.php?url=reservations/purposes/' + id + '/edit';
    document.getElementById('editPurposeName').value       = name;
    document.getElementById('editDescription').value       = description;
    document.getElementById('editRequiresProject').checked = requiresProject === 1;
    document.getElementById('editMaxPerProject').value     = maxPerProject !== null ? maxPerProject : '';
    document.getElementById('editIsActive').checked        = isActive === 1;

    document.getElementById('editPurposeModal').style.display = 'flex';
}

['addPurposeModal', 'editPurposeModal'].forEach(function(id) {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});
</script>
