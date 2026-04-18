<div class="dashboard-grid dashboard-grid--3">
    <div class="stat-card"><div class="stat-label">Pending</div><div class="stat-value"><?= Helpers::e((string)$stats['my_pending']) ?></div><div class="stat-sub">Awaiting admin approval</div></div>
    <div class="stat-card"><div class="stat-label">Approved</div><div class="stat-value"><?= Helpers::e((string)$stats['my_approved']) ?></div><div class="stat-sub">Upcoming trips</div></div>
    <div class="stat-card"><div class="stat-label">Completed</div><div class="stat-value"><?= Helpers::e((string)$stats['my_completed']) ?></div><div class="stat-sub">Past reservations</div></div>
</div>
<div class="section-title">Quick Actions</div>
<div class="quick-actions quick-actions--2">
    <a href="<?= Helpers::url('/reservations/create') ?>" class="action-card">
        <div class="action-icon action-icon--green"><svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg></div>
        <div class="action-label">New Reservation</div>
    </a>
    <a href="<?= Helpers::url('/reservations') ?>" class="action-card">
        <div class="action-icon action-icon--amber"><svg viewBox="0 0 24 24"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg></div>
        <div class="action-label">View My Reservations</div>
    </a>
</div>