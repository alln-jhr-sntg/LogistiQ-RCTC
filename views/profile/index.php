<div style="display:grid;grid-template-columns:1fr 2fr;gap:24px;align-items:start;">

    <!-- Avatar / Photo card -->
    <div class="detail-card" style="text-align:center;">
        <?php if ($user['profile_photo']): ?>
        <img src="<?= APP_BASE ?>/public/uploads/profile_photos/<?= Helpers::e($user['profile_photo']) ?>"
             alt="Profile photo"
             style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin:0 auto 16px;display:block;">
        <?php else: ?>
        <div style="width:80px;height:80px;border-radius:50%;background:var(--clr-accent);
                    color:var(--clr-primary);font-size:32px;font-weight:700;
                    display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
        </div>
        <?php endif; ?>

        <div style="font-size:16px;font-weight:600;">
            <?= Helpers::e($user['first_name'] . ' ' . $user['last_name']) ?>
        </div>
        <div style="font-size:13px;color:var(--clr-text-3);margin-top:4px;text-transform:capitalize;">
            <?= Helpers::e(str_replace('_', ' ', $user['role'])) ?>
            &mdash; <?= Helpers::e($user['company_code']) ?>
        </div>
        <?php if ($user['employee_id']): ?>
        <div style="font-size:12px;color:var(--clr-text-3);margin-top:4px;">
            <?= Helpers::e($user['employee_id']) ?>
        </div>
        <?php endif; ?>

        <div style="margin-top:20px;border-top:1px solid var(--clr-border);padding-top:16px;">
            <button type="button" class="btn btn-outline"
                    onclick="document.getElementById('passwordModal').style.display='flex';">
                Change Password
            </button>
        </div>
    </div>

    <!-- Profile form -->
    <form method="POST" action="<?= Helpers::url('/profile') ?>" enctype="multipart/form-data">
        <input type="hidden" name="_section" value="profile">
        <div class="form-card">
            <div class="form-section-title">Personal Information</div>
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
                    <label class="form-label">Phone Number</label>
                    <input type="text" class="form-input" name="phone_number"
                           value="<?= Helpers::e($user['phone_number'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Profile Photo</label>
                <input type="file" class="form-input" name="profile_photo"
                       accept="image/jpeg,image/png">
                <p class="form-hint">JPG or PNG, max 2MB</p>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-solid">Save Changes</button>
            </div>
        </div>
    </form>

</div>

<!-- ── Change Password Modal ────────────────────────────── -->
<div id="passwordModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Change Password</h3>
            <button type="button" class="modal-close"
                    onclick="document.getElementById('passwordModal').style.display='none';"
                    aria-label="Close">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= Helpers::url('/profile') ?>">
            <input type="hidden" name="_section" value="password">
            <div class="form-group">
                <label class="form-label">Current Password <span class="required">*</span></label>
                <div class="password-field">
                    <input type="password" class="form-input" id="current_password" name="current_password" required>
                    <button type="button" class="password-toggle" data-target="current_password" aria-label="Show password">
                        <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">New Password <span class="required">*</span></label>
                <div class="password-field">
                    <input type="password" class="form-input" id="new_password" name="new_password"
                           minlength="8" required>
                    <button type="button" class="password-toggle" data-target="new_password" aria-label="Show password">
                        <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    </button>
                </div>
                <p class="form-hint">Minimum 8 characters</p>
            </div>
            <div class="form-group">
                <label class="form-label">Confirm New Password <span class="required">*</span></label>
                <div class="password-field">
                    <input type="password" class="form-input" id="confirm_password" name="confirm_password" required>
                    <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Show password">
                        <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    </button>
                </div>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-solid">Update Password</button>
                <button type="button" class="btn btn-outline"
                        onclick="document.getElementById('passwordModal').style.display='none';">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('passwordModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
