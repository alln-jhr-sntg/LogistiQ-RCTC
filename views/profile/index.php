<div class="page-header"><div class="page-header-left"><h2>My Profile</h2></div></div>
<div style="display:grid;grid-template-columns:1fr 2fr;gap:24px;align-items:start;">
    <div class="detail-card" style="text-align:center;">
        <div style="width:80px;height:80px;border-radius:50%;background:var(--clr-accent);color:var(--clr-primary);font-size:32px;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;"><?= strtoupper(substr(Auth::fullName() ?? 'U', 0, 1)) ?></div>
        <div style="font-size:16px;font-weight:600;"><?= Helpers::e(Auth::fullName() ?? '') ?></div>
        <div style="font-size:13px;color:var(--clr-text-3);margin-top:4px;text-transform:capitalize;"><?= Helpers::e(str_replace('_', ' ', Auth::role() ?? '')) ?></div>
        <div style="margin-top:20px;"><label class="form-label" style="text-align:left;display:block;">Profile Photo</label><input type="file" class="form-input" name="profile_photo" accept="image/*"><p class="form-hint">JPG or PNG, max 2MB</p></div>
    </div>
    <form method="POST" action="<?= Helpers::url('/profile') ?>"><div class="form-card">
        <div class="form-section-title">Personal Information</div>
        <div class="form-row"><div class="form-group"><label class="form-label">First Name <span class="required">*</span></label><input type="text" class="form-input" name="first_name" value="System" required></div><div class="form-group"><label class="form-label">Last Name <span class="required">*</span></label><input type="text" class="form-input" name="last_name" value="Administrator" required></div></div>
        <div class="form-row"><div class="form-group"><label class="form-label">Email <span class="required">*</span></label><input type="email" class="form-input" name="email" value="admin@remix.com.ph" required></div><div class="form-group"><label class="form-label">Phone Number</label><input type="text" class="form-input" name="phone_number"></div></div>
        <div class="form-section-title">Change Password</div>
        <div class="form-group"><label class="form-label">Current Password</label><input type="password" class="form-input" name="current_password" placeholder="Enter current password to change"></div>
        <div class="form-row"><div class="form-group"><label class="form-label">New Password</label><input type="password" class="form-input" name="new_password"></div><div class="form-group"><label class="form-label">Confirm New Password</label><input type="password" class="form-input" name="confirm_password"></div></div>
        <div class="form-actions"><button type="submit" class="btn btn-solid">Save Changes</button></div>
    </div></form>
</div>