<div class="page-header page-header-end">
    <form method="POST" action="<?= Helpers::url('/reports/maintenance-due/export') ?>">
        <button type="submit" class="btn btn-outline">
            <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;vertical-align:middle;margin-right:4px;"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
            Export
        </button>
    </form>
</div>

<div class="tab-bar">
    <a href="<?= Helpers::url('/reports/trip-history') ?>"         class="tab-item">Trip History</a>
    <a href="<?= Helpers::url('/reports/maintenance-due') ?>"      class="tab-item active">Maintenance Due</a>
    <a href="<?= Helpers::url('/reports/vehicle-utilization') ?>"  class="tab-item">Vehicle Utilization</a>
</div>

<div class="dashboard-grid dashboard-grid--3" style="margin-bottom:24px;">
    <div class="stat-card stat-card--accent">
        <div class="stat-label">Overdue</div>
        <div class="stat-value"><?= $overdue ?></div>
        <div class="stat-sub">Past next service km</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Due Within 500 km</div>
        <div class="stat-value"><?= $due_soon ?></div>
        <div class="stat-sub">Approaching interval</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Up to Date</div>
        <div class="stat-value"><?= $ok ?></div>
        <div class="stat-sub">Within safe interval</div>
    </div>
</div>

<div class="card"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Vehicle</th>
            <th>Current Odometer</th>
            <th>Next Service At</th>
            <th>Remaining</th>
            <th>Last Service</th>
            <th>Alert</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($vehicles)): ?>
        <tr><td colspan="7" class="td-muted" style="text-align:center;padding:24px;">No vehicles found.</td></tr>
    <?php else: ?>
        <?php foreach ($vehicles as $v): ?>
        <?php
            $curr    = (float) $v['current_odometer_km'];
            $nextKm  = $v['next_service_km'] !== null ? (float) $v['next_service_km'] : null;
            $kmLeft  = $nextKm !== null ? $nextKm - $curr : null;

            if ($nextKm === null) {
                $alertClass = 'badge-cancelled';
                $alertLabel = 'No Baseline';
                $rowColor   = '';
                $kmDisplay  = '<span class="td-muted">—</span>';
                $remainDisplay = '<span class="td-muted">No maintenance logged</span>';
            } elseif ($curr >= $nextKm) {
                $alertClass    = 'badge-rejected';
                $alertLabel    = 'Overdue';
                $rowColor      = 'background:rgba(229,50,59,0.04);';
                $kmDisplay     = number_format($nextKm, 0) . ' km';
                $remainDisplay = '<span style="color:var(--clr-danger);font-weight:600;">'
                    . number_format(abs($kmLeft), 0) . ' km over</span>';
            } elseif ($curr >= $nextKm - 500) {
                $alertClass    = 'badge-pending';
                $alertLabel    = 'Due Soon';
                $rowColor      = 'background:rgba(255,171,0,0.05);';
                $kmDisplay     = number_format($nextKm, 0) . ' km';
                $remainDisplay = '<span style="color:var(--clr-warning,#f59e0b);font-weight:600;">'
                    . number_format($kmLeft, 0) . ' km left</span>';
            } else {
                $alertClass    = 'badge-available';
                $alertLabel    = 'OK';
                $rowColor      = '';
                $kmDisplay     = number_format($nextKm, 0) . ' km';
                $remainDisplay = number_format($kmLeft, 0) . ' km left';
            }
        ?>
        <tr style="<?= $rowColor ?>">
            <td>
                <strong><?= Helpers::e($v['plate_number']) ?></strong><br>
                <span class="td-muted"><?= Helpers::e($v['brand'] . ' ' . $v['model'] . ' ' . $v['year_model']) ?></span>
            </td>
            <td><?= number_format($curr, 0) ?> km</td>
            <td><?= $kmDisplay ?></td>
            <td><?= $remainDisplay ?></td>
            <td class="td-muted">
                <?= $v['last_service_date']
                    ? date('M d, Y', strtotime($v['last_service_date']))
                    : '—' ?>
            </td>
            <td><span class="badge <?= $alertClass ?>"><?= $alertLabel ?></span></td>
            <td>
                <a href="<?= Helpers::url('/vehicles/' . (int) $v['vehicle_id'] . '/maintenance') ?>"
                   class="btn btn-outline btn-sm">View</a>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table></div></div>
<?= $pagination ?>
