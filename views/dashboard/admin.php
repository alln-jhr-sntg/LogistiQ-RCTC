<div class="dashboard-grid">

    <div class="stat-card stat-card--accent">
        <div class="stat-label">Pending Reservations</div>
        <div class="stat-value"><?= Helpers::e((string)$stats['pending_res']) ?></div>
        <div class="stat-sub">Require your review</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Approved Today</div>
        <div class="stat-value"><?= Helpers::e((string)$stats['approved_today']) ?></div>
        <div class="stat-sub">Reservations approved</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Active Trips</div>
        <div class="stat-value"><?= Helpers::e((string)$stats['active_trips']) ?></div>
        <div class="stat-sub">Currently on the road</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Vehicles Available</div>
        <div class="stat-value"><?= Helpers::e((string)$stats['vehicles_avail']) ?></div>
        <div class="stat-sub">Ready for dispatch</div>
    </div>

</div>

<div class="section-title">Quick Actions</div>
<div class="quick-actions">
    <a href="<?= Helpers::url('/reservations/create') ?>" class="action-card">
        <div class="action-icon action-icon--green">
            <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
        </div>
        <div class="action-label">New Reservation</div>
    </a>
    <a href="<?= Helpers::url('/reservations') ?>" class="action-card">
        <div class="action-icon action-icon--green">
            <svg viewBox="0 0 24 24"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
        </div>
        <div class="action-label">Review Reservations</div>
    </a>
    <a href="<?= Helpers::url('/trips') ?>" class="action-card">
        <div class="action-icon action-icon--amber">
            <svg viewBox="0 0 24 24"><path d="M1 3h15v13H1V3zm15 4h4l3 3v6h-7V7z"/></svg>
        </div>
        <div class="action-label">Monitor Trips</div>
    </a>
    <a href="<?= Helpers::url('/vehicles') ?>" class="action-card">
        <div class="action-icon action-icon--purple">
            <svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z"/></svg>
        </div>
        <div class="action-label">Vehicle Fleet</div>
    </a>
</div>

