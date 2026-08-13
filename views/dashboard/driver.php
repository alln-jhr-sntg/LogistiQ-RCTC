<div class="page-header">
    <div class="page-header-left">
        <h2>Welcome, <?= Helpers::e(Auth::fullName() ?? '') ?></h2>
        <p>Para sa inyong biyahe, gamitin ang MoveOps Driver App.</p>
    </div>
</div>

<?php
// Hostinger's edge CDN caches the .apk by URL regardless of file content,
// so every release must use a distinct URL or drivers get served a stale
// binary. version.json's versionCode is the one place already updated per
// release, so the download link piggybacks on it as a cache-busting param.
$_apkVersion = 0;
$_versionJsonPath = __DIR__ . '/../../public/downloads/version.json';
if (is_readable($_versionJsonPath)) {
    $_apkVersion = (int) (json_decode(file_get_contents($_versionJsonPath), true)['versionCode'] ?? 0);
}
?>
<div class="notice-card">
    <div class="notice-card-icon">1</div>
    <div class="notice-card-body">
        <div class="notice-card-title">I-download ang Application</div>
        <p>I-download ang MoveOps Driver App mula sa link sa ibaba.</p>
        <a href="<?= APP_BASE ?>/public/downloads/moveops-driver.apk?v=<?= $_apkVersion ?>" download="MoveOps-Driver.apk" class="btn btn-solid">Download App</a>
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
