<div class="page-header">
    <div class="page-header-left"><h2>Add User</h2></div>
    <a href="<?= Helpers::url('/users') ?>" class="btn btn-outline">← Back</a>
</div>

<form method="POST" action="<?= Helpers::url('/users/create') ?>">
<div class="form-card">
    <div class="form-section-title">Account Information</div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">First Name <span class="required">*</span></label>
            <input type="text" class="form-input" name="first_name" required>
        </div>
        <div class="form-group">
            <label class="form-label">Last Name <span class="required">*</span></label>
            <input type="text" class="form-input" name="last_name" required>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Email <span class="required">*</span></label>
            <input type="email" class="form-input" name="email" required>
        </div>
        <div class="form-group">
            <label class="form-label">Employee ID</label>
            <input type="text" class="form-input" name="employee_id" placeholder="e.g. EMP-0099">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Password <span class="required">*</span></label>
            <input type="password" class="form-input" name="password" required>
        </div>
        <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="text" class="form-input" name="phone_number">
        </div>
    </div>

    <div class="form-section-title">Role &amp; Assignment</div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Role <span class="required">*</span></label>
            <select class="form-select" name="role" id="roleSelect" required onchange="handleRoleChange()">
                <option value="">— Select Role —</option>
                <option value="admin">Admin</option>
                <option value="employee">Employee</option>
                <option value="driver">Driver</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Company <span class="required">*</span></label>
            <select class="form-select" name="company_id" id="companySelect" required
                    onchange="filterDepartments(parseInt(this.value), 0)">
                <option value="">— Select —</option>
                <?php foreach ($companies as $co): ?>
                <option value="<?= (int) $co['company_id'] ?>"><?= Helpers::e($co['company_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form-group" id="deptGroup">
        <label class="form-label">Department</label>
        <select class="form-select" name="department_id" id="departmentSelect"
                data-all-depts="<?= Helpers::e(json_encode($departments)) ?>">
            <option value="">— Select Department (optional) —</option>
        </select>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-solid">Create User</button>
        <a href="<?= Helpers::url('/users') ?>" class="btn btn-outline">Cancel</a>
    </div>
</div>
</form>

<script>
var allDepts = JSON.parse(document.getElementById('departmentSelect').dataset.allDepts || '[]');

function filterDepartments(companyId, selectedDeptId) {
    var select = document.getElementById('departmentSelect');
    select.innerHTML = '<option value="">— Select Department (optional) —</option>';
    allDepts
        .filter(function(d) { return companyId === 0 || parseInt(d.company_id) === companyId; })
        .forEach(function(d) {
            var opt = document.createElement('option');
            opt.value = d.department_id;
            opt.text  = d.department_name;
            if (parseInt(d.department_id) === selectedDeptId) opt.selected = true;
            select.add(opt);
        });
}

function handleRoleChange() {
    var role     = document.getElementById('roleSelect').value;
    var deptGrp  = document.getElementById('deptGroup');
    deptGrp.style.display = (role === 'driver') ? 'none' : '';
}
</script>
