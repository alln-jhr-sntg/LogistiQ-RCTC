<?php
$statusBadge = [
    'available'         => 'badge-available',
    'reserved'          => 'badge-pending',
    'on_trip'           => 'badge-on-trip',
    'under_maintenance' => 'badge-maintenance',
    'retired'           => 'badge-cancelled',
];
$statusLabel = [
    'available'         => 'Available',
    'reserved'          => 'Reserved',
    'on_trip'           => 'On Trip',
    'under_maintenance' => 'Under Maintenance',
    'retired'           => 'Retired',
];
?>
<div class="page-header">
    <div class="page-header-left">
        <h2>Vehicles</h2>
    </div>
    <div class="page-header-actions">
        <a href="<?= Helpers::url('/vehicles/categories') ?>" class="btn btn-outline">Categories</a>
        <a href="<?= Helpers::url('/vehicles/create') ?>" class="btn btn-solid">
            <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg> Add Vehicle
        </a>
    </div>
</div>

<form method="GET" action="<?= APP_BASE ?>/index.php">
    <input type="hidden" name="url" value="vehicles">
    <?php if ($maintFilter !== ''): ?><input type="hidden" name="maint_status" value="<?= Helpers::e($maintFilter) ?>"><?php endif; ?>
    <div class="filter-bar">
        <select class="filter-select" name="status" onchange="this.form.submit()">
            <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>All Statuses</option>
            <option value="available"         <?= $statusFilter === 'available'         ? 'selected' : '' ?>>Available</option>
            <option value="reserved"          <?= $statusFilter === 'reserved'          ? 'selected' : '' ?>>Reserved</option>
            <option value="on_trip"           <?= $statusFilter === 'on_trip'           ? 'selected' : '' ?>>On Trip</option>
            <option value="under_maintenance" <?= $statusFilter === 'under_maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
            <option value="retired"           <?= $statusFilter === 'retired'           ? 'selected' : '' ?>>Retired</option>
        </select>
        <select class="filter-select" name="category_id" onchange="this.form.submit()">
            <option value="0" <?= $categoryFilter === 0 ? 'selected' : '' ?>>All Categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat['category_id'] ?>"
                <?= $categoryFilter === (int) $cat['category_id'] ? 'selected' : '' ?>>
                <?= Helpers::e($cat['category_name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<div class="card card--spaced"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Plate</th>
            <th>Vehicle</th>
            <th>Category</th>
            <th>Capacity</th>
            <th>Odometer</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($vehicles)): ?>
        <tr>
            <td colspan="7" class="td-muted td-empty">
                <?= $statusFilter !== '' ? 'No vehicles with status "' . Helpers::e($statusFilter) . '".' : 'No vehicles yet.' ?>
            </td>
        </tr>
    <?php else: ?>
        <?php foreach ($vehicles as $v): ?>
        <tr>
            <td><strong><?= Helpers::e($v['plate_number']) ?></strong></td>
            <td>
                <?= Helpers::e($v['brand'] . ' ' . $v['model']) ?><br>
                <span class="td-muted"><?= (int) $v['year_model'] ?> — <?= $v['color'] ? Helpers::e($v['color']) : '—' ?></span>
            </td>
            <td class="td-muted"><?= Helpers::e($v['category_name']) ?></td>
            <td class="td-muted">
                <?= (int) $v['passenger_capacity'] ?> pax /
                <?= number_format((float) $v['cargo_capacity_kg'], 0) ?> kg
            </td>
            <td class="td-muted"><?= number_format((float) $v['current_odometer_km'], 0) ?> km</td>
            <td>
                <?php
                $sKey  = $v['status'];
                $sBadge = $statusBadge[$sKey]  ?? 'badge-pending';
                $sText  = $statusLabel[$sKey]  ?? $sKey;
                ?>
                <span class="badge <?= $sBadge ?>"><?= $sText ?></span>
            </td>
            <td>
                <div class="td-actions">
                    <a href="<?= Helpers::url('/vehicles/' . $v['vehicle_id'] . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a>
                    <a href="<?= Helpers::url('/vehicles/' . $v['vehicle_id'] . '/maintenance') ?>" class="btn btn-outline btn-sm">Maintenance</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table></div></div>

<div class="page-header" id="maintenance-status">
    <div class="page-header-left">
        <h2>Maintenance Status</h2>
    </div>
</div>

<form method="GET" action="<?= APP_BASE ?>/index.php">
    <input type="hidden" name="url" value="vehicles">
    <?php if ($statusFilter !== ''): ?><input type="hidden" name="status" value="<?= Helpers::e($statusFilter) ?>"><?php endif; ?>
    <?php if ($categoryFilter !== 0): ?><input type="hidden" name="category_id" value="<?= $categoryFilter ?>"><?php endif; ?>
    <div class="filter-bar">
        <select class="filter-select" name="maint_status" onchange="this.form.submit()">
            <option value="" <?= $maintFilter === '' ? 'selected' : '' ?>>All Alerts</option>
            <option value="overdue"     <?= $maintFilter === 'overdue'     ? 'selected' : '' ?>>Overdue</option>
            <option value="due_soon"    <?= $maintFilter === 'due_soon'    ? 'selected' : '' ?>>Due Soon</option>
            <option value="ok"          <?= $maintFilter === 'ok'          ? 'selected' : '' ?>>Up to Date</option>
            <option value="no_baseline" <?= $maintFilter === 'no_baseline' ? 'selected' : '' ?>>No Baseline</option>
        </select>
    </div>
</form>

<div class="card"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Vehicle</th>
            <th>Current Odometer</th>
            <th>Next Service At</th>
            <th>Remaining</th>
            <th>Last Service</th>
            <th>Alert</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($maintVehicles)): ?>
        <tr><td colspan="6" class="td-muted td-empty">No vehicles found.</td></tr>
    <?php else: ?>
        <?php foreach ($maintVehicles as $v): ?>
        <?php
            $curr    = (float) $v['current_odometer_km'];
            $nextKm  = $v['next_service_km'] !== null ? (float) $v['next_service_km'] : null;
            $kmLeft  = $nextKm !== null ? $nextKm - $curr : null;

            if ($nextKm === null) {
                $alertClass = 'badge-cancelled';
                $alertLabel = 'No Baseline';
                $rowClass   = '';
                $kmDisplay  = '<span class="td-muted">—</span>';
                $remainDisplay = '<span class="td-muted">No maintenance logged</span>';
            } elseif ($curr >= $nextKm) {
                $alertClass    = 'badge-rejected';
                $alertLabel    = 'Overdue';
                $rowClass      = 'row-danger';
                $kmDisplay     = number_format($nextKm, 0) . ' km';
                $remainDisplay = '<span class="text-danger">'
                    . number_format(abs($kmLeft), 0) . ' km over</span>';
            } elseif ($curr >= $nextKm - 500) {
                $alertClass    = 'badge-pending';
                $alertLabel    = 'Due Soon';
                $rowClass      = 'row-warning';
                $kmDisplay     = number_format($nextKm, 0) . ' km';
                $remainDisplay = '<span class="text-warning">'
                    . number_format($kmLeft, 0) . ' km left</span>';
            } else {
                $alertClass    = 'badge-available';
                $alertLabel    = 'Up to Date';
                $rowClass      = '';
                $kmDisplay     = number_format($nextKm, 0) . ' km';
                $remainDisplay = number_format($kmLeft, 0) . ' km left';
            }
        ?>
        <tr class="<?= $rowClass ?>">
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
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table></div></div>
