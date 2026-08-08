<?php
$icons = [
    'reservation' => '<path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/>',
    'trip'        => '<path d="M1 3h15v13H1V3zm15 4h4l3 3v6h-7V7zM5.5 20a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm13 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>',
    'maintenance' => '<path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z"/>',
    'incident'    => '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>',
    'system'      => '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>',
];

$colors = [
    'reservation' => 'action-icon--green',
    'trip'        => 'action-icon--amber',
    'maintenance' => 'action-icon--purple',
    'incident'    => 'action-icon--red',
    'system'      => 'action-icon--gray',
];
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Notifications</h2>
    </div>
    <?php if (!empty($notifications)): ?>
    <form method="POST" action="<?= Helpers::url('/notifications/read-all') ?>">
        <button type="submit" class="btn btn-outline">Mark All Read</button>
    </form>
    <?php endif; ?>
</div>

<div class="card">
    <?php if (empty($notifications)): ?>
        <div style="padding:32px 20px; text-align:center; color:var(--clr-text-3); font-size:14px;">
            No notifications yet.
        </div>
    <?php else: ?>
        <?php foreach ($notifications as $n): ?>
        <?php
            $type     = $n['type'] ?? 'system';
            $icon     = $icons[$type]  ?? $icons['system'];
            $color    = $colors[$type] ?? $colors['system'];
            $isUnread = !(bool) $n['is_read'];

            // All cards link to the mark-read route, which marks read then
            // redirects to the referenced resource (or back to notifications).
            $cardUrl = Helpers::url('/notifications/' . (int) $n['notification_id'] . '/read');
        ?>
        <a href="<?= $cardUrl ?>"
           style="display:flex; gap:14px; padding:16px 20px;
                  border-bottom:1px solid var(--clr-border);
                  text-decoration:none; color:inherit;
                  <?= $isUnread ? 'background:var(--clr-bg);' : '' ?>
                  transition:background 0.15s;">
            <div class="action-icon <?= $color ?>" style="flex-shrink:0; margin-top:2px;">
                <svg viewBox="0 0 24 24"><?= $icon ?></svg>
            </div>
            <div style="flex:1; min-width:0;">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:3px;">
                    <span style="font-size:14px; font-weight:<?= $isUnread ? '600' : '500' ?>; color:var(--clr-text);">
                        <?= Helpers::e($n['title']) ?>
                    </span>
                    <?php if ($isUnread): ?>
                    <span style="width:7px; height:7px; border-radius:50%;
                                 background:var(--clr-primary-lt); flex-shrink:0;"></span>
                    <?php endif; ?>
                </div>
                <div style="font-size:13px; color:var(--clr-text-2); margin-bottom:4px;">
                    <?= Helpers::e($n['message']) ?>
                </div>
                <div style="font-size:11px; color:var(--clr-text-3);">
                    <?= date('M d Y, g:i A', strtotime($n['created_at'])) ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
