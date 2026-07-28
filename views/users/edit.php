<div class="page-header">
    <div class="page-header-left">
        <h2>Edit User — <?= Helpers::e($user['first_name'] . ' ' . $user['last_name']) ?></h2>
        <p>
            <?= $user['employee_id'] ? Helpers::e($user['employee_id']) . ' — ' : '' ?>
            <?= ucfirst($user['role']) ?> — <?= Helpers::e($user['company_code']) ?>
            <?= $user['department_name'] ? ' ' . Helpers::e($user['department_name']) : '' ?>
        </p>
    </div>
    <a href="<?= Helpers::url('/users') ?>" class="btn btn-outline">← Back</a>
</div>

<form method="POST" action="<?= Helpers::url('/users/' . $user['user_id'] . '/edit') ?>"
      enctype="multipart/form-data">
<div class="form-card">
    <div class="form-section-title">Account Information</div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">First Name <span class="required">*</span></label>
            <input type="text" class="form-input" name="first_name"
                   value="<?= Helpers::e($user['first_name']) ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">Last Name <span class="required">*</span></label>
            <input type="text" class="form-input" name="last_name"
                   value="<?= Helpers::e($user['last_name']) ?>" required>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Email <span class="required">*</span></label>
            <input type="email" class="form-input" name="email"
                   value="<?= Helpers::e($user['email']) ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">Employee ID</label>
            <input type="text" class="form-input" name="employee_id"
                   value="<?= Helpers::e($user['employee_id'] ?? '') ?>">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="text" class="form-input" name="phone_number"
                   value="<?= Helpers::e($user['phone_number'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">New Password</label>
            <input type="password" class="form-input" name="password"
                   placeholder="Leave blank to keep current">
        </div>
    </div>
    <div class="form-group">
        <label class="form-label">Profile Photo</label>
        <input type="file" class="form-input" name="profile_photo" accept="image/jpeg,image/png">
        <?php if ($user['profile_photo']): ?>
        <p class="form-hint">Current: <?= Helpers::e($user['profile_photo']) ?></p>
        <?php endif; ?>
    </div>

    <div class="form-section-title">Role &amp; Assignment</div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Role <span class="required">*</span></label>
            <select class="form-select" name="role" id="roleSelect" required onchange="handleRoleChange()">
                <?php foreach (['admin', 'employee', 'driver'] as $r): ?>
                <option value="<?= $r ?>" <?= $user['role'] === $r ? 'selected' : '' ?>>
                    <?= ucfirst($r) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Company <span class="required">*</span></label>
            <select class="form-select" name="company_id" id="companySelect" required
                    onchange="filterDepartments(parseInt(this.value), 0)">
                <?php foreach ($companies as $co): ?>
                <option value="<?= (int) $co['company_id'] ?>"
                    <?= (int) $co['company_id'] === (int) $user['company_id'] ? 'selected' : '' ?>>
                    <?= Helpers::e($co['company_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form-group" id="deptGroup"
         <?= $user['role'] === 'driver' ? 'style="display:none"' : '' ?>>
        <label class="form-label">Department</label>
        <select class="form-select" name="department_id" id="departmentSelect"
                data-all-depts="<?= Helpers::e(json_encode($departments)) ?>">
            <option value="">— None (optional) —</option>
        </select>
    </div>

    <div class="form-section-title">Status</div>
    <div class="form-group">
        <label class="form-label">Account Status</label>
        <select class="form-select" name="is_active">
            <option value="1" <?= (int) $user['is_active'] === 1 ? 'selected' : '' ?>>Active</option>
            <option value="0" <?= (int) $user['is_active'] === 0 ? 'selected' : '' ?>>Inactive</option>
        </select>
        <p class="form-hint">Inactive users cannot log in. Records are preserved.</p>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-solid">Save Changes</button>
        <a href="<?= Helpers::url('/users') ?>" class="btn btn-outline">Cancel</a>
    </div>
</div>
</form>

<script>
var allDepts      = JSON.parse(document.getElementById('departmentSelect').dataset.allDepts || '[]');
var currentDeptId = <?= (int) ($user['department_id'] ?? 0) ?>;
var currentCompId = <?= (int) $user['company_id'] ?>;

function filterDepartments(companyId, selectedDeptId) {
    var select = document.getElementById('departmentSelect');
    select.innerHTML = '<option value="">— None (optional) —</option>';
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
    var role = document.getElementById('roleSelect').value;
    document.getElementById('deptGroup').style.display = (role === 'driver') ? 'none' : '';
}

// Pre-populate departments for the current company on page load
filterDepartments(currentCompId, currentDeptId);
</script>
