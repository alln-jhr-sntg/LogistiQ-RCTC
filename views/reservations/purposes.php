<div class="page-header">
    <div class="page-header-left"><h2>Trip Purposes</h2><p>Manage the list of valid trip purposes for reservations</p></div>
    <button class="btn btn-solid" onclick="document.getElementById('addForm').style.display='block';this.style.display='none';"><svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg> Add Purpose</button>
</div>
<div class="form-card" id="addForm" style="display:none;margin-bottom:20px;">
    <div class="form-section-title">New Trip Purpose</div>
    <form method="POST" action="<?= Helpers::url('/reservations/purposes') ?>">
        <div class="form-row">
            <div class="form-group"><label class="form-label">Purpose Name <span class="required">*</span></label><input type="text" class="form-input" name="purpose_name" required></div>
            <div class="form-group"><label class="form-label">Max Trips per Project</label><input type="number" class="form-input" name="max_per_project" min="1" placeholder="Leave blank for no limit"></div>
        </div>
        <div class="form-group"><label style="display:flex;align-items:center;gap:8px;font-size:14px;color:var(--clr-text-2);cursor:pointer;"><input type="checkbox" name="requires_project" value="1"> Requires a project to be selected</label></div>
        <div class="form-actions"><button type="submit" class="btn btn-solid">Save</button><button type="button" class="btn btn-outline" onclick="document.getElementById('addForm').style.display='none';document.querySelector('.btn-solid').style.display='';">Cancel</button></div>
    </form>
</div>
<div class="card"><div class="table-wrap"><table class="data-table">
    <thead><tr><th>Purpose</th><th>Requires Project</th><th>Max / Project</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
        <tr><td><strong>Site Visit</strong></td><td><span class="badge badge-approved">Yes</span></td><td class="td-muted">2</td><td><span class="badge badge-available">Active</span></td><td><div class="td-actions"><a href="#" class="btn btn-outline btn-sm">Edit</a></div></td></tr>
        <tr><td><strong>Material Delivery</strong></td><td><span class="badge badge-approved">Yes</span></td><td class="td-muted">No limit</td><td><span class="badge badge-available">Active</span></td><td><div class="td-actions"><a href="#" class="btn btn-outline btn-sm">Edit</a></div></td></tr>
        <tr><td><strong>Client Meeting</strong></td><td><span class="badge badge-cancelled">No</span></td><td class="td-muted">—</td><td><span class="badge badge-available">Active</span></td><td><div class="td-actions"><a href="#" class="btn btn-outline btn-sm">Edit</a></div></td></tr>
        <tr><td><strong>Equipment Transport</strong></td><td><span class="badge badge-approved">Yes</span></td><td class="td-muted">5</td><td><span class="badge badge-available">Active</span></td><td><div class="td-actions"><a href="#" class="btn btn-outline btn-sm">Edit</a></div></td></tr>
    </tbody>
</table></div></div>