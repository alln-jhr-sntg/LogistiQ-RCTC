<div class="page-header">
    <div class="page-header-left"><h2>Admin Department Access</h2><p>Grant admins access to manage departments outside their own company</p></div>
    <a href="<?= Helpers::url('/companies') ?>" class="btn btn-outline">← Companies</a>
</div>
<div class="form-card" style="margin-bottom:24px;">
    <div class="form-section-title">Grant Access</div>
    <form method="POST" action="<?= Helpers::url('/companies/access') ?>">
        <div class="form-row">
            <div class="form-group"><label class="form-label">Admin User <span class="required">*</span></label><select class="form-select" name="admin_user_id" required><option value="">— Select Admin —</option><option value="5">Lito Reyes (Admin — REMIX)</option><option value="6">Grace Tan (Admin — IDEAL)</option></select></div>
            <div class="form-group"><label class="form-label">Department <span class="required">*</span></label><select class="form-select" name="department_id" required><option value="">— Select —</option><optgroup label="REMIX"><option>Engineering</option><option>Operations</option></optgroup><optgroup label="IDEAL"><option>Operations</option></optgroup></select></div>
        </div>
        <div class="form-actions"><button type="submit" class="btn btn-solid">Grant Access</button></div>
    </form>
</div>
<div class="section-title" style="margin-bottom:12px;">Current Access Grants</div>
<div class="card"><div class="table-wrap"><table class="data-table">
    <thead><tr><th>Admin User</th><th>Granted Access To</th><th>Granted By</th><th>Granted At</th><th>Actions</th></tr></thead>
    <tbody>
        <tr>
            <td><strong>Lito Reyes</strong><br><span class="td-muted">Admin — REMIX</span></td>
            <td>IDEAL — Operations</td>
            <td class="td-muted">System Administrator</td>
            <td class="td-muted">Dec 01, 2025</td>
            <td><div class="td-actions">
                <button type="button" class="btn btn-danger btn-sm" onclick="lvmsConfirmRevoke(1, 'Lito Reyes', 'IDEAL — Operations')">Revoke</button>
            </div></td>
        </tr>
        <tr>
            <td><strong>Grace Tan</strong><br><span class="td-muted">Admin — IDEAL</span></td>
            <td>REMIX — Engineering</td>
            <td class="td-muted">System Administrator</td>
            <td class="td-muted">Dec 03, 2025</td>
            <td><div class="td-actions">
                <button type="button" class="btn btn-danger btn-sm" onclick="lvmsConfirmRevoke(2, 'Grace Tan', 'REMIX — Engineering')">Revoke</button>
            </div></td>
        </tr>
    </tbody>
</table></div></div>

<!-- Hidden revoke forms -->
<form id="revokeForm1" method="POST" action="<?= Helpers::url('/companies/access/1/revoke') ?>"></form>
<form id="revokeForm2" method="POST" action="<?= Helpers::url('/companies/access/2/revoke') ?>"></form>

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
            <button type="button" class="btn btn-outline" onclick="document.getElementById('revokeModal').style.display='none';">Cancel</button>
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
