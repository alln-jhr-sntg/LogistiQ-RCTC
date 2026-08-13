<div class="page-header">
    <div class="page-header-left">
        <h2>Welcome, <?= Helpers::e(Auth::fullName() ?? '') ?></h2>
        <p>Para sa inyong biyahe, gamitin ang MoveOps Driver App.</p>
    </div>
</div>

<div class="notice-card">
    <div class="notice-card-icon">1</div>
    <div class="notice-card-body">
        <div class="notice-card-title">I-download ang Application</div>
        <p>I-download ang MoveOps Driver App mula sa link sa ibaba.</p>
        <a href="<?= APP_BASE ?>/public/downloads/moveops-driver.apk" download="MoveOps-Driver.apk" class="btn btn-solid">Download App</a>
    </div>
</div>

<div class="notice-card">
    <div class="notice-card-icon">2</div>
    <div class="notice-card-body">
        <div class="notice-card-title">Install the App</div>
        <p>I-install ang application sa inyong Android phone.</p>
    </div>
</div>

<div class="notice-card">
    <div class="notice-card-icon">3</div>
    <div class="notice-card-body">
        <div class="notice-card-title">Login</div>
        <p>Mag-login gamit ang parehong email at password ng inyong MoveOps account.</p>
    </div>
</div>

<p class="detail-muted">Kailangan mo ng tulong? Contact your administrator.</p>
