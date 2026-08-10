<?php
$statusBadge = [
    'pending'     => ['class' => 'badge-pending',    'label' => 'Pending'],
    'approved'    => ['class' => 'badge-approved',   'label' => 'Approved'],
    'rejected'    => ['class' => 'badge-cancelled',  'label' => 'Rejected'],
    'cancelled'   => ['class' => 'badge-cancelled',  'label' => 'Cancelled'],
    'in_progress' => ['class' => 'badge-on-trip',    'label' => 'In Progress'],
    'completed'   => ['class' => 'badge-available',  'label' => 'Completed'],
];
$tabs = [
    ''            => 'All',
    'pending'     => 'Pending',
    'approved'    => 'Approved',
    'in_progress' => 'In Progress',
    'completed'   => 'Completed',
    'rejected'    => 'Rejected',
    'cancelled'   => 'Cancelled',
];
$role = Auth::role();
?>
<div class="page-header">
    <div class="page-header-left"><h2>Reservations</h2></div>
    <div style="display: flex; gap:8px;">
        <?php if (in_array($role, [ROLE_SUPER_ADMIN, ROLE_ADMIN])): ?>
        <a href="<?= Helpers::url('/reservations/purposes') ?>" class="btn btn-outline">Purposes</a>
        <?php endif; ?>

        <?php if (in_array($role, [ROLE_EMPLOYEE, ROLE_SUPER_ADMIN, ROLE_ADMIN])): ?>
        <a href="<?= Helpers::url('/reservations/create') ?>" class="btn btn-solid">
            <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            New Reservation
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Status tabs -->
<div class="tab-bar" style="margin-bottom:20px;">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="<?= APP_BASE ?>/index.php?url=reservations<?= $key !== '' ? '&status=' . $key : '' ?>"
       class="tab-item <?= $statusFilter === $key ? 'active' : '' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="card"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Code</th>
            <th>Requester</th>
            <th>Department</th>
            <th>Purpose</th>
            <th>Destination</th>
            <th>Departure</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($reservations)): ?>
        <tr>
            <td colspan="8" class="td-muted" style="text-align:center;padding:24px;">
                No reservations<?= $statusFilter !== '' ? ' with status "' . Helpers::e($statusFilter) . '"' : '' ?>.
            </td>
        </tr>
    <?php else: ?>
        <?php foreach ($reservations as $r):
            $sb = $statusBadge[$r['status']] ?? ['class' => 'badge-pending', 'label' => $r['status']];
        ?>
        <tr>
            <td><strong><?= Helpers::e($r['reservation_code']) ?></strong></td>
            <td><?= Helpers::e($r['requester_first_name'] . ' ' . $r['requester_last_name']) ?></td>
            <td class="td-muted"><?= Helpers::e($r['company_code'] . ' — ' . $r['department_name']) ?></td>
            <td class="td-muted"><?= Helpers::e($r['purpose_name']) ?></td>
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
