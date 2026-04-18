<?php
$notifications = [
    ['type'=>'reservation','title'=>'Reservation Approved','message'=>'Your reservation RES-2025-00002 has been approved.','is_read'=>false,'created_at'=>'Dec 11, 2025 08:15 AM'],
    ['type'=>'trip','title'=>'Trip Started','message'=>'Trip for RES-2025-00003 has started. Driver: Carlos Mendoza.','is_read'=>false,'created_at'=>'Dec 12, 2025 06:14 AM'],
    ['type'=>'maintenance','title'=>'Maintenance Due Soon','message'=>'Vehicle DEF-9012 is approaching its next service at 60,000 km.','is_read'=>true,'created_at'=>'Dec 10, 2025 09:00 AM'],
    ['type'=>'system','title'=>'System Settings Updated','message'=>'Warehouse address has been updated.','is_read'=>true,'created_at'=>'Dec 01, 2025 10:00 AM'],
];
$icons = ['reservation'=>'<path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/>','trip'=>'<path d="M1 3h15v13H1V3zm15 4h4l3 3v6h-7V7z"/>','maintenance'=>'<path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z"/>','system'=>'<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>'];
$colors = ['reservation'=>'action-icon--green','trip'=>'action-icon--amber','maintenance'=>'action-icon--purple','system'=>'action-icon--gray'];
?>
<div class="page-header"><div class="page-header-left"><h2>Notifications</h2></div><form method="POST" action="<?= Helpers::url('/notifications/read-all') ?>"><button type="submit" class="btn btn-outline">Mark All Read</button></form></div>
<div class="card">
<?php foreach ($notifications as $n): ?>
<div style="display:flex;gap:14px;padding:16px 20px;border-bottom:1px solid var(--clr-surface-2);<?= !$n['is_read'] ? 'background:var(--clr-bg);' : '' ?>">
    <div class="action-icon <?= $colors[$n['type']] ?>" style="flex-shrink:0;margin-top:2px;"><svg viewBox="0 0 24 24"><?= $icons[$n['type']] ?></svg></div>
    <div style="flex:1;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;"><span style="font-size:14px;font-weight:<?= !$n['is_read'] ? '600' : '500' ?>;"><?= Helpers::e($n['title']) ?></span><?php if (!$n['is_read']): ?><span style="width:7px;height:7px;border-radius:50%;background:var(--clr-primary-lt);flex-shrink:0;"></span><?php endif; ?></div>
        <div style="font-size:13px;color:var(--clr-text-2);"><?= Helpers::e($n['message']) ?></div>
        <div style="font-size:11px;color:var(--clr-text-3);margin-top:4px;"><?= Helpers::e($n['created_at']) ?></div>
    </div>
</div>
<?php endforeach; ?>
</div>