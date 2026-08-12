<?php
// Same mapping as views/projects/index.php — 'completed' uses the grey
// badge-cancelled because badge-completed renders green.
$statusBadge = [
    PROJ_PENDING   => ['class' => 'badge-pending',   'label' => 'Pending'],
    PROJ_ACTIVE    => ['class' => 'badge-available', 'label' => 'Active'],
    PROJ_COMPLETED => ['class' => 'badge-cancelled', 'label' => 'Completed'],
    PROJ_REJECTED  => ['class' => 'badge-rejected',  'label' => 'Rejected'],
];
?>
<div class="dashboard-grid">

    <div class="stat-card">
        <div class="stat-label">Pending Projects</div>
        <div class="stat-value"><?= (int) $stats['pending_projects'] ?></div>
        <div class="stat-sub">Awaiting review</div>
    </div>

    <div class="stat-card stat-card--accent">
        <div class="stat-label">Pending Reservations</div>
        <div class="stat-value"><?= (int) $stats['pending_reservations'] ?></div>
        <div class="stat-sub">Require your review</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Approved Reservations</div>
        <div class="stat-value"><?= (int) $stats['approved_reservations'] ?></div>
        <div class="stat-sub">Cleared for dispatch</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Company Employees</div>
        <div class="stat-value"><?= (int) $stats['company_employees'] ?></div>
        <div class="stat-sub">Active staff</div>
    </div>

</div>

<div class="section-title">Quick Actions</div>
<div class="quick-actions" style="margin-bottom:32px;">
    <a href="<?= Helpers::url('/reservations/create') ?>" class="action-card">
        <div class="action-icon action-icon--green">
            <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
        </div>
        <div class="action-label">Create Reservation</div>
    </a>
    <a href="<?= Helpers::url('/projects/create') ?>" class="action-card">
        <div class="action-icon action-icon--green">
            <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
        </div>
        <div class="action-label">Create Project</div>
    </a>
    <a href="<?= APP_BASE ?>/index.php?url=projects&company_id=<?= (int) $company_id ?>" class="action-card">
        <div class="action-icon action-icon--amber">
            <svg viewBox="0 0 24 24"><path d="M20 6h-2.18c.07-.44.18-.88.18-1.34C18 2.54 15.96.5 13.46.5c-1.29 0-2.35.54-3.16 1.38L10 2.18l-.3-.3C8.9 1.04 7.84.5 6.54.5 4.04.5 2 2.54 2 4.66c0 .46.11.9.18 1.34H0v14c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6zm-9.3-3.49c.38-.43.92-.69 1.76-.69 1.34 0 2.04.7 2.04 1.84 0 .46-.28 1.15-.73 1.84H10.73c-.08-.23-.53-.73-.73-1.14.1-.66.32-1.43.7-1.85zM5.5 4.16c0-1.14.7-1.84 2.04-1.84.84 0 1.38.26 1.76.69.38.43.6 1.19.7 1.85-.2.41-.65.91-.73 1.14H6.23C5.78 5.31 5.5 4.62 5.5 4.16zM20 18H4v-2h16v2zm0-5H4V8h5.08L7 11h2l2-3h2l2 3h2l-2.08-3H20v5z"/></svg>
        </div>
        <div class="action-label">Review Projects</div>
    </a>
    <a href="<?= Helpers::url('/users') ?>" class="action-card">
        <div class="action-icon action-icon--purple">
            <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        </div>
        <div class="action-label">Manage Employees</div>
    </a>
</div>

<div class="section-title">Recent Project Requests</div>
<div class="card" style="margin-bottom:32px;"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Project</th>
            <th>Location</th>
            <th>Status</th>
            <th>Requested</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($recent_projects)): ?>
        <tr><td colspan="5" class="td-muted">No projects yet.</td></tr>
    <?php else: ?>
        <?php foreach ($recent_projects as $p): ?>
        <tr>
            <td>
                <strong><?= Helpers::e($p['project_name']) ?></strong>
                <?php if ($p['project_code']): ?>
                <br><span class="td-muted"><?= Helpers::e($p['project_code']) ?></span>
                <?php endif; ?>
            </td>
            <td class="td-muted"><?= $p['location'] ? Helpers::e($p['location']) : '—' ?></td>
            <td>
                <?php $badge = $statusBadge[$p['status']] ?? ['class' => 'badge-pending', 'label' => $p['status']]; ?>
                <span class="badge <?= $badge['class'] ?>"><?= Helpers::e($badge['label']) ?></span>
            </td>
            <td class="td-muted"><?= date('M d, Y', strtotime($p['created_at'])) ?></td>
            <td>
                <div class="td-actions">
                    <a href="<?= Helpers::url('/projects/' . $p['project_id'] . '/edit') ?>"
                       class="btn btn-outline btn-sm">View</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table></div></div>

<div class="section-title">Upcoming Reservations</div>
<div class="card" style="margin-bottom:32px;"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Code</th>
            <th>Requester</th>
            <th>Destination</th>
            <th>Departure</th>
            <th>Vehicle</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($upcoming_reservations)): ?>
        <tr><td colspan="6" class="td-muted">No upcoming reservations.</td></tr>
    <?php else: ?>
        <?php foreach ($upcoming_reservations as $r): ?>
        <tr>
            <td><strong><?= Helpers::e($r['reservation_code']) ?></strong></td>
            <td><?= Helpers::e($r['requester_first_name'] . ' ' . $r['requester_last_name']) ?></td>
            <td class="td-muted"><?= Helpers::e($r['destination']) ?></td>
            <td class="td-muted"><?= date('M d Y, g:i A', strtotime($r['departure_datetime'])) ?></td>
            <td class="td-muted">
                <?= $r['plate_number'] ? Helpers::e($r['plate_number']) : '—' ?>
            </td>
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
