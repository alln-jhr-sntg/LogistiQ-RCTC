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
$role = Auth::role();
?>
<div class="page-header page-header-end">
    <div style="display: flex; gap:8px;">
        <?php if (in_array($role, [ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN])): ?>
        <a href="<?= Helpers::url('/reservations/purposes') ?>" class="btn btn-outline">Purposes</a>
        <?php endif; ?>

        <?php if (in_array($role, [ROLE_EMPLOYEE, ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_FLEET_ADMIN])): ?>
        <a href="<?= Helpers::url('/reservations/create') ?>" class="btn btn-solid">
            <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            New Reservation
        </a>
        <?php endif; ?>
    </div>
</div>

<form method="GET" action="<?= APP_BASE ?>/index.php">
    <input type="hidden" name="url" value="reservations">
    <div class="filter-bar">
        <select class="filter-select" name="status" onchange="this.form.submit()">
            <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>All Statuses</option>
            <?php foreach (RES_STATUS_LABELS as $key => $label): ?>
            <option value="<?= Helpers::e($key) ?>" <?= $statusFilter === $key ? 'selected' : '' ?>>
                <?= Helpers::e($label) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($companies)): ?>
        <select class="filter-select" name="company_id" onchange="this.form.submit()">
            <option value="0" <?= $companyFilter === 0 ? 'selected' : '' ?>>All Companies</option>
            <?php foreach ($companies as $co): ?>
            <option value="<?= (int) $co['company_id'] ?>"
                <?= $companyFilter === (int) $co['company_id'] ? 'selected' : '' ?>>
                <?= Helpers::e($co['company_code']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <select class="filter-select" name="range" onchange="this.form.submit()">
            <option value=""       <?= $rangeFilter === ''       ? 'selected' : '' ?>>All Time</option>
            <option value="today"  <?= $rangeFilter === 'today'  ? 'selected' : '' ?>>Today</option>
            <option value="week"   <?= $rangeFilter === 'week'   ? 'selected' : '' ?>>This Week</option>
            <option value="month"  <?= $rangeFilter === 'month'  ? 'selected' : '' ?>>This Month</option>
            <option value="last30" <?= $rangeFilter === 'last30' ? 'selected' : '' ?>>Last 30 Days</option>
        </select>
    </div>
</form>

<div class="card"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Code</th>
            <th>Requester</th>
            <th>Department</th>
            <th>Purpose</th>
            <th>Destination</th>
            <th>Departure</th>
            <th>Return</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($reservations)): ?>
        <tr>
            <td colspan="9" class="td-muted" style="text-align:center;padding:24px;">
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
            <td class="td-muted"><?= date('M d Y, g:i A', strtotime($r['return_datetime'])) ?></td>
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
<?= $pagination ?>
