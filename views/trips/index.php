<?php 
    $isDriver = Auth::isDriver();
    $isAdmin = Auth::isAdminOrAbove();
?>

<form method="GET" action="<?= APP_BASE ?>/index.php">
    <input type="hidden" name="url" value="trips">
    <div class="filter-bar">
        <select class="filter-select" name="status" onchange="this.form.submit()">
            <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>All Statuses</option>
            <?php foreach (TRIP_STATUS_LABELS as $key => $label): ?>
                <?php if ($key === TRIP_INCIDENT && $isDriver) continue; ?>
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

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Reservation</th>
                    <th>Vehicle</th>
                    <?php if (!$isDriver): ?><th>Driver</th><?php endif; ?>
                    <th>Destination</th>
                    <th>Actual Departure</th>
                    <th>Actual Return</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($trips)): ?>
                <tr>
                    <td colspan="<?= $isDriver ? 7 : 8 ?>" class="td-muted"
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
                        'cancelled'     => 'badge-cancelled',
                        default         => 'badge-pending',
                    };
                    $statusLabel = match($trip['trip_status']) {
                        'pending_start' => 'Pending Start',
                        'in_progress'   => 'In Progress',
                        'completed'     => 'Completed',
                        'incident'      => 'Incident',
                        'cancelled'     => 'Cancelled',
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
                    <td class="td-muted">
                        <?= $trip['actual_departure']
                            ? date('M d Y, g:i A', strtotime($trip['actual_departure']))
                            : '—' ?>
                    </td>
                    <td class="td-muted">
                        <?= $trip['actual_return']
                            ? date('M d Y, g:i A', strtotime($trip['actual_return']))
                            : '—' ?>
                    </td>
                    <td><span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span></td>
                    <td><div class="td-actions">
                        <a href="<?= Helpers::url('/trips/' . (int) $trip['trip_id']) ?>"
                           class="btn btn-outline btn-sm">View</a>
                        <?php if ($isAdmin && in_array($trip['trip_status'], TRIP_TRACKING_STATUSES, true)): ?>
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
<?= $pagination ?>
