<?php
$tripBadge = [
    'pending_start' => ['class' => 'badge-pending',   'label' => 'Pending Start'],
    'in_progress'   => ['class' => 'badge-on-trip',   'label' => 'In Progress'],
    'incident'      => ['class' => 'badge-rejected',  'label' => 'Incident'],
    'completed'     => ['class' => 'badge-available', 'label' => 'Completed'],
    'cancelled'     => ['class' => 'badge-cancelled', 'label' => 'Cancelled'],
];
?>
<div class="dashboard-grid">

    <div class="stat-card stat-card--accent">
        <div class="stat-label">Pending Reservations</div>
        <div class="stat-value"><?= (int) $stats['pending_reservations'] ?></div>
        <div class="stat-sub">Require your review</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Active Trips</div>
        <div class="stat-value"><?= (int) $stats['active_trips'] ?></div>
        <div class="stat-sub">Currently on the road</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Available Vehicles</div>
        <div class="stat-value"><?= (int) $stats['vehicles_available'] ?></div>
        <div class="stat-sub">Ready for dispatch</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Maintenance Due</div>
        <div class="stat-value"><?= (int) $stats['maintenance_due'] ?></div>
        <div class="stat-sub">Overdue or due soon</div>
    </div>

</div>

<div class="section-title">Quick Actions</div>
<div class="quick-actions" style="margin-bottom:32px;">
    <a href="<?= Helpers::url('/reservations') ?>" class="action-card">
        <div class="action-icon action-icon--amber">
            <svg viewBox="0 0 24 24"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
        </div>
        <div class="action-label">Reservations</div>
    </a>
    <a href="<?= Helpers::url('/vehicles') ?>" class="action-card">
        <div class="action-icon action-icon--green">
            <svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.85 7h10.29l1.08 3.11H5.77L6.85 7zM19 17H5v-5h14v5zm-8.5-4c.83 0 1.5.67 1.5 1.5S11.33 16 10.5 16 9 15.33 9 14.5 9.67 13 10.5 13zm3 0c.83 0 1.5.67 1.5 1.5S14.33 16 13.5 16 12 15.33 12 14.5 12.67 13 13.5 13z"/></svg>
        </div>
        <div class="action-label">Vehicles</div>
    </a>
    <a href="<?= Helpers::url('/vehicles') ?>#maintenance-status" class="action-card">
        <div class="action-icon action-icon--red">
            <svg viewBox="0 0 24 24"><path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z"/></svg>
        </div>
        <div class="action-label">Maintenance</div>
    </a>
    <a href="<?= Helpers::url('/users?role=driver') ?>" class="action-card">
        <div class="action-icon action-icon--purple">
            <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        </div>
        <div class="action-label">Drivers</div>
    </a>
</div>

<div class="section-title">Pending Reservation Queue</div>
<div class="card" style="margin-bottom:32px;"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Code</th>
            <th>Requester</th>
            <th>Company</th>
            <th>Destination</th>
            <th>Departure</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($pending_reservations)): ?>
        <tr><td colspan="6" class="td-muted">No pending reservations.</td></tr>
    <?php else: ?>
        <?php foreach ($pending_reservations as $r): ?>
        <tr>
            <td><strong><?= Helpers::e($r['reservation_code']) ?></strong></td>
            <td><?= Helpers::e($r['requester_first_name'] . ' ' . $r['requester_last_name']) ?></td>
            <td class="td-muted"><?= Helpers::e($r['company_code']) ?></td>
            <td class="td-muted"><?= Helpers::e($r['destination']) ?></td>
            <td class="td-muted"><?= date('M d Y, g:i A', strtotime($r['departure_datetime'])) ?></td>
            <td>
                <div class="td-actions">
                    <a href="<?= Helpers::url('/reservations/' . $r['reservation_id'] . '/review') ?>"
                       class="btn btn-outline btn-sm">Review</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table></div></div>

<div class="section-title">Fleet Availability</div>
<div class="dashboard-grid dashboard-grid--3" style="margin-bottom:32px;">
    <div class="stat-card">
        <div class="stat-label">Available</div>
        <div class="stat-value"><?= (int) $fleet_availability['available'] ?></div>
        <div class="stat-sub">Ready for dispatch</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Assigned</div>
        <div class="stat-value"><?= (int) $fleet_availability['assigned'] ?></div>
        <div class="stat-sub">Reserved or on trip</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Maintenance</div>
        <div class="stat-value"><?= (int) $fleet_availability['maintenance'] ?></div>
        <div class="stat-sub">Under service</div>
    </div>
</div>

<div class="section-title">Maintenance Due</div>
<div class="card" style="margin-bottom:32px;"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Vehicle</th>
            <th>Current Odometer</th>
            <th>Next Service At</th>
            <th>Alert</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($maintenance_due)): ?>
        <tr><td colspan="5" class="td-muted">No vehicles due for maintenance.</td></tr>
    <?php else: ?>
        <?php foreach ($maintenance_due as $v):
            $isOverdue = $v['maintenance_alert'] === 'overdue';
        ?>
        <tr>
            <td>
                <strong><?= Helpers::e($v['plate_number']) ?></strong><br>
                <span class="td-muted"><?= Helpers::e($v['brand'] . ' ' . $v['model']) ?></span>
            </td>
            <td class="td-muted"><?= number_format((float) $v['current_odometer_km'], 0) ?> km</td>
            <td class="td-muted"><?= number_format((float) $v['next_service_km'], 0) ?> km</td>
            <td>
                <span class="badge <?= $isOverdue ? 'badge-rejected' : 'badge-pending' ?>">
                    <?= $isOverdue ? 'Overdue' : 'Due Soon' ?>
                </span>
            </td>
            <td>
                <div class="td-actions">
                    <a href="<?= Helpers::url('/vehicles/' . $v['vehicle_id'] . '/maintenance') ?>"
                       class="btn btn-outline btn-sm">View</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table></div></div>

<div class="section-title">Active Trips</div>
<div class="card"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Reservation</th>
            <th>Driver</th>
            <th>Vehicle</th>
            <th>Destination</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($active_trips)): ?>
        <tr><td colspan="6" class="td-muted">No active trips.</td></tr>
    <?php else: ?>
        <?php foreach ($active_trips as $t):
            $tb = $tripBadge[$t['trip_status']] ?? ['class' => 'badge-pending', 'label' => $t['trip_status']];
        ?>
        <tr>
            <td><strong><?= Helpers::e($t['reservation_code']) ?></strong></td>
            <td><?= Helpers::e($t['driver_first_name'] . ' ' . $t['driver_last_name']) ?></td>
            <td class="td-muted"><?= Helpers::e($t['plate_number'] . ' — ' . $t['vehicle_brand'] . ' ' . $t['vehicle_model']) ?></td>
            <td class="td-muted"><?= Helpers::e($t['destination']) ?></td>
            <td><span class="badge <?= $tb['class'] ?>"><?= $tb['label'] ?></span></td>
            <td>
                <div class="td-actions">
                    <a href="<?= Helpers::url('/trips/' . $t['trip_id']) ?>"
                       class="btn btn-outline btn-sm">View</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table></div></div>
