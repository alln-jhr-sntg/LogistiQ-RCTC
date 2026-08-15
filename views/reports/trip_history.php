<div class="page-header page-header-end">
    <form method="POST" action="<?= Helpers::url('/reports/trip-history/export') ?>">
        <input type="hidden" name="date_from" value="<?= Helpers::e($filters['date_from']) ?>">
        <input type="hidden" name="date_to" value="<?= Helpers::e($filters['date_to']) ?>">
        <input type="hidden" name="trip_status" value="<?= Helpers::e($filters['trip_status']) ?>">
        <input type="hidden" name="driver_id" value="<?= Helpers::e((string) ($filters['driver_id'] ?? '')) ?>">
        <input type="hidden" name="vehicle_id" value="<?= Helpers::e((string) ($filters['vehicle_id'] ?? '')) ?>">
        <button type="submit" class="btn btn-outline">
            <svg viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
            Export
        </button>
    </form>
</div>

<div class="tab-bar">
    <a href="<?= Helpers::url('/reports/trip-history') ?>"         class="tab-item active">Trip History</a>
    <a href="<?= Helpers::url('/reports/maintenance-history') ?>"  class="tab-item">Maintenance History</a>
    <a href="<?= Helpers::url('/reports/vehicle-utilization') ?>"  class="tab-item">Vehicle Utilization</a>
</div>

<!-- Filters (GET so the filtered URL is bookmarkable) -->
<form method="GET" action="<?= APP_BASE ?>/index.php">
    <input type="hidden" name="url" value="reports/trip-history">
    <div class="filter-bar">
        <input type="date" class="filter-input" name="date_from"
               value="<?= Helpers::e($filters['date_from']) ?>"
               placeholder="From">
        <input type="date" class="filter-input" name="date_to"
               value="<?= Helpers::e($filters['date_to']) ?>"
               placeholder="To">
        <select class="filter-select" name="trip_status">
            <option value="">All Statuses</option>
            <?php foreach (['pending_start','in_progress','completed','incident','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $filters['trip_status'] === $s ? 'selected' : '' ?>>
                <?= ucwords(str_replace('_', ' ', $s)) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select class="filter-select" name="driver_id">
            <option value="">All Drivers</option>
            <?php foreach ($drivers as $d): ?>
            <option value="<?= (int) $d['user_id'] ?>"
                    <?= (string) ($filters['driver_id'] ?? '') === (string) $d['user_id'] ? 'selected' : '' ?>>
                <?= Helpers::e($d['first_name'] . ' ' . $d['last_name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select class="filter-select" name="vehicle_id">
            <option value="">All Vehicles</option>
            <?php foreach ($vehicles as $v): ?>
            <option value="<?= (int) $v['vehicle_id'] ?>"
                    <?= (string) ($filters['vehicle_id'] ?? '') === (string) $v['vehicle_id'] ? 'selected' : '' ?>>
                <?= Helpers::e($v['plate_number'] . ' — ' . $v['brand'] . ' ' . $v['model']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-solid btn-sm">Filter</button>
        <a href="<?= Helpers::url('/reports/trip-history') ?>" class="btn btn-outline btn-sm">Clear</a>
    </div>
</form>

<div class="card"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Reservation</th>
            <th>Vehicle</th>
            <th>Driver</th>
            <th>Destination</th>
            <th>Departure</th>
            <th>Return</th>
            <th>Distance</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($trips)): ?>
        <tr><td colspan="8" class="td-muted td-empty">
            No trips found<?= !empty(array_filter($filters)) ? ' matching the selected filters' : '' ?>.
        </td></tr>
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
            $distance = ($trip['odometer_start_km'] !== null && $trip['odometer_end_km'] !== null)
                ? number_format((float)$trip['odometer_end_km'] - (float)$trip['odometer_start_km'], 0) . ' km'
                : '—';
        ?>
        <tr>
            <td>
                <a href="<?= Helpers::url('/trips/' . (int) $trip['trip_id']) ?>">
                    <strong><?= Helpers::e($trip['reservation_code']) ?></strong>
                </a><br>
                <span class="td-muted"><?= Helpers::e($trip['purpose_name']) ?></span>
            </td>
            <td>
                <?= Helpers::e($trip['plate_number']) ?><br>
                <span class="td-muted"><?= Helpers::e($trip['vehicle_brand'] . ' ' . $trip['vehicle_model']) ?></span>
            </td>
            <td><?= Helpers::e($trip['driver_first_name'] . ' ' . $trip['driver_last_name']) ?></td>
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
            <td class="td-muted"><?= $distance ?></td>
            <td><span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table></div></div>
<?= $pagination ?>
