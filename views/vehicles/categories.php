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
    <thead><tr><th>Category</th><th>Max Passengers</th><th>Max Cargo (kg)</th><th>Vehicles</th><th>Actions</th></tr></thead>
    <tbody>
        <tr>
            <td><strong>Van</strong></td><td>15</td><td>500.00</td><td class="td-muted">2</td>
            <td><div class="td-actions">
                <button class="btn btn-outline btn-sm" onclick="lvmsEditCategory(1,'Van',15,500.00)">Edit</button>
            </div></td>
        </tr>
        <tr>
            <td><strong>Truck</strong></td><td>3</td><td>5000.00</td><td class="td-muted">1</td>
            <td><div class="td-actions">
                <button class="btn btn-outline btn-sm" onclick="lvmsEditCategory(2,'Truck',3,5000.00)">Edit</button>
            </div></td>
        </tr>
        <tr>
            <td><strong>Pickup</strong></td><td>5</td><td>1000.00</td><td class="td-muted">1</td>
            <td><div class="td-actions">
                <button class="btn btn-outline btn-sm" onclick="lvmsEditCategory(3,'Pickup',5,1000.00)">Edit</button>
            </div></td>
        </tr>
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
                <div class="form-group"><label class="form-label">Max Passengers</label><input type="number" class="form-input" name="max_passengers" min="1"></div>
                <div class="form-group"><label class="form-label">Max Cargo (kg)</label><input type="number" class="form-input" name="max_cargo_kg" min="0" step="0.01"></div>
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
    document.getElementById('editCategoryForm').action = '/lvms/index.php?url=vehicles/categories/' + id + '/edit';
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
