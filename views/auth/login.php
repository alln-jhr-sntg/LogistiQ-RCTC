<div class="auth-wrap">
    <div class="auth-brand">
        <div class="auth-brand-content">
            <div class="auth-logo">
                <div class="auth-logo-mark">
                    <img src="<?= Helpers::assetUrl('/img/logo.jpg') ?>" alt="MoveOps logo">
                </div>
                <div class="auth-logo-text">Move<span class="accent">Ops</span></div>
            </div>
            <div class="auth-brand-headline">Where movement meets<br><strong>operational excellence.</strong></div>
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
                    <div class="password-field">
                        <input class="form-input" type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
                        <button type="button" class="password-toggle" data-target="password" aria-label="Show password">
                            <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-primary">Sign in</button>
            </form>
            <?php if (!empty($demoAccounts)): ?>
            <div class="demo-accounts">
                <strong>Demo Accounts <br>
                Password: <code>password</code></strong>
                <?php foreach ($demoAccounts as $account): ?>
                <div class="demo-accounts-row">
                    <span><?= Helpers::e($account['label']) ?>:</span>
                    <span><?= Helpers::e($account['email']) ?></span>                    
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <p class="auth-footer">&copy; <?= date('Y') ?> <?= Helpers::e($companyName) ?></p>
        </div>
    </div>
</div>
