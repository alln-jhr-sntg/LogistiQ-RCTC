<div class="page-header">
    <div class="page-header-left"></div>
    <a href="<?= Helpers::url('/users') ?>" class="btn btn-outline">← Back</a>
</div>

<form method="POST" action="<?= Helpers::url('/users/create') ?>">
<?= Csrf::field() ?>
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
            <div class="password-field">
                <input type="password" class="form-input" id="password" name="password" required>
                <button type="button" class="password-toggle" data-target="password" aria-label="Show password">
                    <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                </button>
            </div>
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
                <?php foreach ((ROLE_ASSIGNABLE[Auth::role()] ?? []) as $r): ?>
                <option value="<?= Helpers::e($r) ?>"><?= Helpers::e(ROLE_LABELS[$r] ?? ucfirst($r)) ?></option>
                <?php endforeach; ?>
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
    var noDept   = ['driver', 'super_admin', 'fleet_admin'];
    deptGrp.style.display = (noDept.indexOf(role) !== -1) ? 'none' : '';
}
</script>
