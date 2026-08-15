<div class="page-header page-header-end">
    <form method="POST" action="<?= Helpers::url('/reports/vehicle-utilization/export') ?>">
        <?= Csrf::field() ?>
        <input type="hidden" name="date_from" value="<?= Helpers::e($date_from) ?>">
        <input type="hidden" name="date_to" value="<?= Helpers::e($date_to) ?>">
        <button type="submit" class="btn btn-outline">
            <svg viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
            Export
        </button>
    </form>
</div>

<div class="tab-bar">
    <a href="<?= Helpers::url('/reports/trip-history') ?>"         class="tab-item">Trip History</a>
    <a href="<?= Helpers::url('/reports/maintenance-history') ?>"  class="tab-item">Maintenance History</a>
    <a href="<?= Helpers::url('/reports/vehicle-utilization') ?>"  class="tab-item active">Vehicle Utilization</a>
</div>

<form method="GET" action="<?= APP_BASE ?>/index.php">
    <input type="hidden" name="url" value="reports/vehicle-utilization">
    <div class="filter-bar">
        <input type="date" class="filter-input" name="date_from"
               value="<?= Helpers::e($date_from) ?>" placeholder="From">
        <input type="date" class="filter-input" name="date_to"
               value="<?= Helpers::e($date_to) ?>" placeholder="To">
        <button type="submit" class="btn btn-solid btn-sm">Filter</button>
        <a href="<?= Helpers::url('/reports/vehicle-utilization') ?>" class="btn btn-outline btn-sm">Clear</a>
    </div>
</form>

<?php if ($date_from !== '' || $date_to !== ''): ?>
<p class="filter-note">
    Showing completed trips
    <?= $date_from ? 'from ' . date('M d, Y', strtotime($date_from)) : '' ?>
    <?= $date_to   ? 'to '   . date('M d, Y', strtotime($date_to))   : '' ?>
</p>
<?php endif; ?>

<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-label">Total Fleet</div>
        <div class="stat-value"><?= $fleet_count ?></div>
        <div class="stat-sub">Non-retired vehicles</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Completed Trips</div>
        <div class="stat-value"><?= number_format($total_trips) ?></div>
        <div class="stat-sub"><?= ($date_from || $date_to) ? 'In selected range' : 'All time' ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Distance</div>
        <div class="stat-value"><?= number_format($total_km, 0) ?> km</div>
        <div class="stat-sub"><?= ($date_from || $date_to) ? 'In selected range' : 'All time' ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Avg. Trips / Vehicle</div>
        <div class="stat-value"><?= $avg_trips ?></div>
        <div class="stat-sub">Across active fleet</div>
    </div>
</div>

<div class="card"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Vehicle</th>
            <th>Category</th>
            <th>Completed Trips</th>
            <th>Distance</th>
            <th>Current Odometer</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($vehicles)): ?>
        <tr><td colspan="6" class="td-muted td-empty">No vehicles found.</td></tr>
    <?php else: ?>
        <?php foreach ($vehicles as $v): ?>
        <?php
            $badgeClass = match($v['status']) {
                'available'         => 'badge-available',
                'reserved'          => 'badge-pending',
                'on_trip'           => 'badge-in-progress',
                'under_maintenance' => 'badge-cancelled',
                default             => 'badge-cancelled',
            };
            $statusLabel = ucwords(str_replace('_', ' ', $v['status']));
        ?>
        <tr>
            <td>
                <strong><?= Helpers::e($v['plate_number']) ?></strong><br>
                <span class="td-muted"><?= Helpers::e($v['brand'] . ' ' . $v['model'] . ' ' . $v['year_model']) ?></span>
            </td>
            <td class="td-muted"><?= Helpers::e($v['category_name']) ?></td>
            <td><?= (int) $v['trip_count'] ?></td>
            <td class="td-muted">
                <?= (int) $v['trip_count'] > 0
                    ? number_format((float) $v['total_km'], 0) . ' km'
                    : '—' ?>
            </td>
            <td class="td-muted"><?= number_format((float) $v['current_odometer_km'], 0) ?> km</td>
            <td><span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table></div></div>
<?= $pagination ?>
