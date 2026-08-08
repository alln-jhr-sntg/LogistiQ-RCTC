<?php 
    $isDriver = Auth::isDriver();
    $isAdmin = Auth::isAdminOrAbove();
?>

<div class="page-header">
    <div class="page-header-left">
        <h2><?= $isDriver ? 'My Trips' : 'Trips' ?></h2>
        <p><?= $isDriver ? 'Your assigned vehicle trips' : 'Active and past fleet trips' ?></p>
    </div>
</div>

<?php
// Tab status filter — build URLs preserving current filter
$statuses = [
    ''              => 'All',
    'pending_start' => 'Pending Start',
    'in_progress'   => 'In Progress',
    'completed'     => 'Completed',
    'incident'      => 'Incident',
];
?>
<div class="tab-bar">
    <?php foreach ($statuses as $val => $label): ?>
        <?php if ($val === 'incident' && $isDriver) continue; ?>
        <a href="/lvms/index.php?url=trips<?= $val !== '' ? '&status=' . $val : '' ?>"
           class="tab-item <?= $statusFilter === $val ? 'active' : '' ?>">
            <?= $label ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Reservation</th>
                    <th>Vehicle</th>
                    <?php if (!$isDriver): ?><th>Driver</th><?php endif; ?>
                    <th>Destination</th>
                    <th>Departure</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($trips)): ?>
                <tr>
                    <td colspan="<?= $isDriver ? 6 : 7 ?>" class="td-muted"
                        style="text-align:center; padding:24px;">
                        No trips found<?= $statusFilter !== '' ? ' with status "' . Helpers::e(str_replace('_', ' ', $statusFilter)) . '"' : '' ?>.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($trips as $trip): ?>
                <?php
                    $badgeClass = match($trip['trip_status']) {
                        'pending_start' => 'badge-pending',
                        'in_progress'   => 'badge-in-progress',
                        'completed'     => 'badge-completed',
                        'incident'      => 'badge-rejected',
                        default         => 'badge-pending',
                    };
                    $statusLabel = match($trip['trip_status']) {
                        'pending_start' => 'Pending Start',
                        'in_progress'   => 'In Progress',
                        'completed'     => 'Completed',
                        'incident'      => 'Incident',
                        default         => ucfirst($trip['trip_status']),
                    };
                ?>
                <tr>
                    <td>
                        <strong><?= Helpers::e($trip['reservation_code']) ?></strong><br>
                        <span class="td-muted"><?= Helpers::e($trip['purpose_name']) ?></span>
                    </td>
                    <td>
                        <?= Helpers::e($trip['plate_number']) ?><br>
                        <span class="td-muted"><?= Helpers::e($trip['vehicle_brand'] . ' ' . $trip['vehicle_model']) ?></span>
                    </td>
                    <?php if (!$isDriver): ?>
                    <td><?= Helpers::e($trip['driver_first_name'] . ' ' . $trip['driver_last_name']) ?></td>
                    <?php endif; ?>
                    <td><?= Helpers::e($trip['destination']) ?></td>
                    <td class="td-muted"><?= date('M d Y, g:i A', strtotime($trip['departure_datetime'])) ?></td>
                    <td><span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span></td>
                    <td><div class="td-actions">
                        <a href="<?= Helpers::url('/trips/' . (int) $trip['trip_id']) ?>"
                           class="btn btn-outline btn-sm">View</a>
                        <?php if ($isAdmin && $trip['trip_status'] === 'in_progress'): ?>
                            <a href="<?= Helpers::url('/trips/' . (int) $trip['trip_id'] . '/map') ?>"
                               class="btn btn-solid btn-sm">Live Map</a>
                        <?php endif; ?>
                    </div></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
