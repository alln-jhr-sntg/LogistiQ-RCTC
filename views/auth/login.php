<div class="auth-wrap">
    <div class="auth-brand">
        <div class="auth-brand-top">
            <div class="auth-logo">
                <div class="auth-logo-mark">
                    <svg viewBox="0 0 24 24"><path d="M1 3h15v13H1V3zm15 4h4l3 3v6h-7V7zM5.5 20a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm13 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/></svg>
                </div>
                <div class="auth-logo-text">Remix Construction<br>and Trading Corp.</div>
            </div>
            <div class="auth-brand-headline">Logistics &amp; Vehicle<br><strong>Management System</strong></div>
            <p class="auth-brand-sub">Centralized fleet dispatching and trip monitoring for Remix, Ideal Home, and TenBuild operations.</p>
        </div>
        <div class="auth-brand-bottom">
            <div class="auth-brand-stats">
                <div><div class="auth-stat-label">Companies</div><div class="auth-stat-value">3</div></div>
                <div><div class="auth-stat-label">Shared Fleet</div><div class="auth-stat-value">—</div></div>
                <div><div class="auth-stat-label">System</div><div class="auth-stat-value">LogistiQ</div></div>
            </div>
        </div>
    </div>
    <div class="auth-form-panel">
        <div class="auth-form-inner">
            <h1 class="auth-form-title">Welcome back</h1>
            <p class="auth-form-sub">Sign in to your account to continue.</p>
            <?php if (!empty($flash)): ?>
                <div class="flash flash-<?= Helpers::e($flash['type']) ?>"><?= Helpers::e($flash['message']) ?></div>
            <?php endif; ?>
            <form method="POST" action="<?= Helpers::url('/login') ?>" novalidate>
                <div class="form-group">
                    <label class="form-label" for="email">Email address</label>
                    <input class="form-input" type="email" id="email" name="email" placeholder="you@remix.com.ph" autocomplete="email" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-input" type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn-primary">Sign in</button>
            </form>
            <div style="margin-top:20px;padding:14px;background:var(--clr-surface-2);border-radius:var(--radius-md);font-size:12px;color:var(--clr-text-3);line-height:1.9;">
                <strong style="color:var(--clr-text-2);display:block;margin-bottom:4px;">Demo accounts &mdash; password: <code>password</code></strong>
                superadmin@lvms.test &mdash; Super Admin<br>
                admin@lvms.test &mdash; Admin<br>
                employee@lvms.test &mdash; Employee<br>
                driver@lvms.test &mdash; Driver
            </div>
            <p class="auth-footer">LogistiQ &mdash; Internal Use Only<br>&copy; <?= date('Y') ?> Remix Construction and Trading Corporation</p>
        </div>
    </div>
</div>
