<?php
$statusBadge = [
    'pending'          => ['class' => 'badge-pending',    'label' => 'Pending'],
    'approved'         => ['class' => 'badge-approved',   'label' => 'Approved'],
    'gatepass_pending' => ['class' => 'badge-pending',    'label' => 'Gatepass'],
    'rejected'         => ['class' => 'badge-cancelled',  'label' => 'Rejected'],
    'cancelled'        => ['class' => 'badge-cancelled',  'label' => 'Cancelled'],
    'in_progress'      => ['class' => 'badge-on-trip',    'label' => 'In Progress'],
    'completed'        => ['class' => 'badge-available',  'label' => 'Completed'],
];
?>
<div class="dashboard-grid">

    <div class="stat-card">
        <div class="stat-label">Total Companies</div>
        <div class="stat-value"><?= (int) $stats['total_companies'] ?></div>
        <div class="stat-sub">Sharing the fleet</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Total Users</div>
        <div class="stat-value"><?= (int) $stats['total_users'] ?></div>
        <div class="stat-sub">Active accounts, all companies</div>
    </div>

    <div class="stat-card stat-card--accent">
        <div class="stat-label">Gatepass Pending</div>
        <div class="stat-value"><?= (int) $stats['gatepass_pending'] ?></div>
        <div class="stat-sub">Awaiting your review</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Active Trips</div>
        <div class="stat-value"><?= (int) $stats['active_trips'] ?></div>
        <div class="stat-sub">Currently on the road</div>
    </div>

</div>

<div class="section-title">Quick Actions</div>
<div class="quick-actions" style="margin-bottom:32px;">
    <a href="<?= Helpers::url('/gatepasses') ?>" class="action-card">
        <div class="action-icon action-icon--amber">
            <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
        </div>
        <div class="action-label">Review Gatepass</div>
    </a>
    <a href="<?= Helpers::url('/users') ?>" class="action-card">
        <div class="action-icon action-icon--purple">
            <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        </div>
        <div class="action-label">Manage Users</div>
    </a>
    <a href="<?= Helpers::url('/companies') ?>" class="action-card">
        <div class="action-icon action-icon--green">
            <svg viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
        </div>
        <div class="action-label">Manage Companies</div>
    </a>
    <a href="<?= Helpers::url('/reports') ?>" class="action-card">
        <div class="action-icon action-icon--gray">
            <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
        </div>
        <div class="action-label">Reports</div>
    </a>
</div>

<div class="section-title">Pending Gatepass Requests</div>
<div class="card" style="margin-bottom:32px;"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Gatepass Code</th>
            <th>Requester</th>
            <th>Vehicle</th>
            <th>Destination</th>
            <th>Departure</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($pending_gatepasses)): ?>
        <tr><td colspan="6" class="td-muted">No pending gatepass requests.</td></tr>
    <?php else: ?>
        <?php foreach ($pending_gatepasses as $g): ?>
        <tr>
            <td><strong><?= Helpers::e($g['gatepass_code']) ?></strong></td>
            <td><?= Helpers::e($g['requester_first_name'] . ' ' . $g['requester_last_name']) ?></td>
            <td class="td-muted">
                <?= $g['plate_number']
                    ? Helpers::e($g['plate_number'] . ' — ' . $g['vehicle_brand'] . ' ' . $g['vehicle_model'])
                    : '—' ?>
            </td>
            <td class="td-muted"><?= Helpers::e($g['destination']) ?></td>
            <td class="td-muted"><?= date('M d Y, g:i A', strtotime($g['departure_datetime'])) ?></td>
            <td>
                <div class="td-actions">
                    <a href="<?= Helpers::url('/gatepasses/' . $g['gatepass_id'] . '/review') ?>"
                       class="btn btn-outline btn-sm">Review</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table></div></div>

<div class="section-title">Company Overview</div>
<div class="card" style="margin-bottom:32px;"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Company</th>
            <th>Users</th>
            <th>Vehicles Used</th>
            <th>Trips</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($companies)): ?>
        <tr><td colspan="4" class="td-muted">No companies found.</td></tr>
    <?php else: ?>
        <?php foreach ($companies as $co): ?>
        <tr>
            <td>
                <strong><?= Helpers::e($co['company_name']) ?></strong><br>
                <span class="td-muted"><?= Helpers::e($co['company_code']) ?></span>
            </td>
            <td><?= (int) $co['user_count'] ?></td>
            <td><?= (int) $co['vehicle_count'] ?></td>
            <td><?= (int) $co['trip_count'] ?></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table></div></div>

<div class="section-title">Recent Reservations</div>
<div class="card" style="margin-bottom:32px;"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Code</th>
            <th>Requester</th>
            <th>Company</th>
            <th>Destination</th>
            <th>Departure</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($recent_reservations)): ?>
        <tr><td colspan="7" class="td-muted">No reservations yet.</td></tr>
    <?php else: ?>
        <?php foreach ($recent_reservations as $r):
            $sb = $statusBadge[$r['status']] ?? ['class' => 'badge-pending', 'label' => $r['status']];
        ?>
        <tr>
            <td><strong><?= Helpers::e($r['reservation_code']) ?></strong></td>
            <td><?= Helpers::e($r['requester_first_name'] . ' ' . $r['requester_last_name']) ?></td>
            <td class="td-muted"><?= Helpers::e($r['company_code']) ?></td>
            <td class="td-muted"><?= Helpers::e($r['destination']) ?></td>
            <td class="td-muted"><?= date('M d Y, g:i A', strtotime($r['departure_datetime'])) ?></td>
            <td><span class="badge <?= $sb['class'] ?>"><?= $sb['label'] ?></span></td>
            <td>
                <div class="td-actions">
                    <a href="<?= Helpers::url('/reservations/' . $r['reservation_id']) ?>"
                       class="btn btn-outline btn-sm">View</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table></div></div>

<div class="section-title">Fleet Summary</div>
<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-label">Total</div>
        <div class="stat-value"><?= (int) $fleet_summary['total'] ?></div>
        <div class="stat-sub">All vehicles</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Available</div>
        <div class="stat-value"><?= (int) $fleet_summary['available'] ?></div>
        <div class="stat-sub">Ready for dispatch</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Assigned</div>
        <div class="stat-value"><?= (int) $fleet_summary['assigned'] ?></div>
        <div class="stat-sub">Reserved or on trip</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Maintenance</div>
        <div class="stat-value"><?= (int) $fleet_summary['maintenance'] ?></div>
        <div class="stat-sub">Under service</div>
    </div>
</div>
