<div class="dashboard-grid dashboard-grid--3">
    <div class="stat-card">
        <div class="stat-label">Pending</div>
        <div class="stat-value"><?= (int) $stats['pending'] ?></div>
        <div class="stat-sub">Awaiting admin approval</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Approved</div>
        <div class="stat-value"><?= (int) $stats['approved'] ?></div>
        <div class="stat-sub">Cleared for dispatch</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Active Trips</div>
        <div class="stat-value"><?= (int) $stats['active_trips'] ?></div>
        <div class="stat-sub">Currently on the road</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Completed Trips</div>
        <div class="stat-value"><?= (int) $stats['completed_trips'] ?></div>
        <div class="stat-sub">Past trips</div>
    </div>
</div>

<div class="section-title">Quick Actions</div>
<div class="quick-actions" style="margin-bottom:32px;">
    <a href="<?= Helpers::url('/reservations/create') ?>" class="action-card">
        <div class="action-icon action-icon--green"><svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg></div>
        <div class="action-label">New Reservation</div>
    </a>
    <a href="<?= Helpers::url('/reservations') ?>" class="action-card">
        <div class="action-icon action-icon--amber"><svg viewBox="0 0 24 24"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg></div>
        <div class="action-label">My Reservations</div>
    </a>
    <a href="<?= Helpers::url('/trips') ?>" class="action-card">
        <div class="action-icon action-icon--purple"><svg viewBox="0 0 24 24"><path d="M1 3h15v13H1V3zm15 4h4l3 3v6h-7V7zM5.5 20a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm13 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/></svg></div>
        <div class="action-label">My Trips</div>
    </a>
</div>

<div class="section-title">Upcoming Trip</div>
<?php if ($upcoming_trip === null): ?>
<div class="card empty-state" style="margin-bottom:32px;">
    <svg viewBox="0 0 24 24"><path d="M1 3h15v13H1V3zm15 4h4l3 3v6h-7V7z"/></svg>
    <p>No upcoming trips.</p>
</div>
<?php else: ?>
<div class="detail-card" style="margin-bottom:32px;">
    <div class="detail-card-title">
        <?= Helpers::e($upcoming_trip['reservation_code']) ?>
        <span class="badge badge-pending">Pending Start</span>
    </div>
    <div class="detail-field">
        <div class="detail-field-label">Destination</div>
        <div class="detail-field-value"><?= Helpers::e($upcoming_trip['destination']) ?></div>
    </div>
    <div class="detail-field">
        <div class="detail-field-label">Scheduled Departure</div>
        <div class="detail-field-value"><?= date('M d, Y — g:i A', strtotime($upcoming_trip['departure_datetime'])) ?></div>
    </div>
    <div class="detail-field">
        <div class="detail-field-label">Vehicle</div>
        <div class="detail-field-value"><?= Helpers::e($upcoming_trip['plate_number'] . ' — ' . $upcoming_trip['vehicle_brand'] . ' ' . $upcoming_trip['vehicle_model']) ?></div>
    </div>
    <div class="detail-field">
        <div class="detail-field-label">Driver</div>
        <div class="detail-field-value"><?= Helpers::e($upcoming_trip['driver_first_name'] . ' ' . $upcoming_trip['driver_last_name']) ?></div>
    </div>
</div>
<?php endif; ?>

<div class="section-title">Recent Activity</div>
<div class="card"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Activity</th>
            <th>When</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($recent_activity)): ?>
        <tr><td colspan="2" class="td-muted">No recent activity.</td></tr>
    <?php else: ?>
        <?php foreach ($recent_activity as $n): ?>
        <tr>
            <td>
                <strong><?= Helpers::e($n['title']) ?></strong><br>
                <span class="td-muted"><?= Helpers::e($n['message']) ?></span>
            </td>
            <td class="td-muted"><?= date('M d Y, g:i A', strtotime($n['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table></div></div>
