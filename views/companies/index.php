<?php
// Color and badge abbreviation map keyed by company_code.
// These are fixed — three seeded companies, always the same codes.
$colorMap = [
    'REMIX'    => 'var(--clr-primary)',
    'IDEAL'    => '#1565a0',
    'TENBUILD' => '#5a3e8a',
];
$badgeMap = [
    'REMIX'    => 'RMX',
    'IDEAL'    => 'IDL',
    'TENBUILD' => 'TBD',
];
?>
<div class="page-header">
    <div class="page-header-left">
        <h2>Companies</h2>
        <p>The three companies sharing the vehicle fleet</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;">
<?php foreach ($companies as $co):
    $code  = $co['company_code'];
    $color = $colorMap[$code] ?? '#555';
    $badge = $badgeMap[$code] ?? strtoupper(substr($code, 0, 3));
?>
    <div class="detail-card">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
            <div style="width:44px;height:44px;background:<?= Helpers::e($color) ?>;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;">
                <span style="color:#fff;font-weight:700;font-size:13px;"><?= Helpers::e($badge) ?></span>
            </div>
            <div>
                <div style="font-weight:600;font-size:15px;"><?= Helpers::e($co['company_name']) ?></div>
                <div style="font-size:12px;color:var(--clr-text-3);"><?= Helpers::e($code) ?></div>
            </div>
        </div>
        <div class="detail-field">
            <div class="detail-field-label">Full Name</div>
            <div class="detail-field-value"><?= Helpers::e($co['company_name']) ?></div>
        </div>
        <div class="detail-field">
            <div class="detail-field-label">Departments</div>
            <div class="detail-field-value"><?= (int) $co['dept_count'] ?> department<?= (int) $co['dept_count'] !== 1 ? 's' : '' ?></div>
        </div>
        <div style="margin-top:16px;">
            <a href="<?= Helpers::url('/companies/' . $co['company_id'] . '/departments') ?>" class="btn btn-outline btn-sm">View Departments</a>
        </div>
    </div>
<?php endforeach; ?>
</div>
