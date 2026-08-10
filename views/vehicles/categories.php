<div class="page-header">
    <div class="page-header-left"><h2>Vehicle Categories</h2></div>
    <div style="display:flex;gap:8px;">
        <a href="<?= Helpers::url('/vehicles') ?>" class="btn btn-outline">← Fleet</a>
        <button class="btn btn-solid" onclick="document.getElementById('addCategoryModal').style.display='flex';">
            <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg> Add Category
        </button>
    </div>
</div>

<div class="card"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Category</th>
            <th>Max Passengers</th>
            <th>Max Cargo (kg)</th>
            <th>Vehicles</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($categories)): ?>
        <tr>
            <td colspan="5" class="td-muted" style="text-align:center;padding:24px;">
                No categories yet. Add one above.
            </td>
        </tr>
    <?php else: ?>
        <?php foreach ($categories as $cat): ?>
        <tr>
            <td><strong><?= Helpers::e($cat['category_name']) ?></strong></td>
            <td><?= (int) $cat['max_passengers'] ?></td>
            <td><?= number_format((float) $cat['max_cargo_kg'], 2, '.', '') ?></td>
            <td class="td-muted"><?= (int) $cat['vehicle_count'] ?></td>
            <td><div class="td-actions">
                <button class="btn btn-outline btn-sm"
                    onclick="lvmsEditCategory(
                        <?= (int)   $cat['category_id']   ?>,
                        '<?= addslashes(Helpers::e($cat['category_name'])) ?>',
                        <?= (int)   $cat['max_passengers'] ?>,
                        <?= number_format((float) $cat['max_cargo_kg'], 2, '.', '') ?>
                    )">Edit</button>
            </div></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table></div></div>

<!-- Add Category Modal -->
<div id="addCategoryModal" class="modal-overlay">
    <div class="modal-card modal-card-wide">
        <div class="modal-header">
            <h3>New Vehicle Category</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('addCategoryModal').style.display='none';" aria-label="Close">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= Helpers::url('/vehicles/categories') ?>">
            <div class="form-row form-row-3">
                <div class="form-group"><label class="form-label">Name <span class="required">*</span></label><input type="text" class="form-input" name="category_name" required></div>
                <div class="form-group"><label class="form-label">Max Passengers</label><input type="number" class="form-input" name="max_passengers" min="1" value="1"></div>
                <div class="form-group"><label class="form-label">Max Cargo (kg)</label><input type="number" class="form-input" name="max_cargo_kg" min="0" step="0.01" value="0"></div>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-solid">Save Category</button>
                <button type="button" class="btn btn-outline" onclick="document.getElementById('addCategoryModal').style.display='none';">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editCategoryModal" class="modal-overlay">
    <div class="modal-card modal-card-wide">
        <div class="modal-header">
            <h3>Edit Vehicle Category</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('editCategoryModal').style.display='none';" aria-label="Close">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form id="editCategoryForm" method="POST" action="">
            <div class="form-row form-row-3">
                <div class="form-group"><label class="form-label">Name <span class="required">*</span></label><input type="text" class="form-input" name="category_name" id="editCategoryName" required></div>
                <div class="form-group"><label class="form-label">Max Passengers</label><input type="number" class="form-input" name="max_passengers" id="editCategoryPassengers" min="1"></div>
                <div class="form-group"><label class="form-label">Max Cargo (kg)</label><input type="number" class="form-input" name="max_cargo_kg" id="editCategoryCargo" min="0" step="0.01"></div>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-solid">Save Changes</button>
                <button type="button" class="btn btn-outline" onclick="document.getElementById('editCategoryModal').style.display='none';">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function lvmsEditCategory(id, name, passengers, cargo) {
    document.getElementById('editCategoryForm').action = APP_BASE + '/index.php?url=vehicles/categories/' + id + '/edit';
    document.getElementById('editCategoryName').value = name;
    document.getElementById('editCategoryPassengers').value = passengers;
    document.getElementById('editCategoryCargo').value = cargo;
    document.getElementById('editCategoryModal').style.display = 'flex';
}
['addCategoryModal','editCategoryModal'].forEach(function(id) {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});
</script>
