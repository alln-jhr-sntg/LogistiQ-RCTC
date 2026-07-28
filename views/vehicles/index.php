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
        <h2>Fleet Management</h2>
        <p>Shared vehicle fleet across all companies</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="<?= Helpers::url('/vehicles/categories') ?>" class="btn btn-outline">Categories</a>
        <a href="<?= Helpers::url('/vehicles/create') ?>" class="btn btn-solid">
            <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg> Add Vehicle
        </a>
    </div>
</div>

<form method="GET" action="/lvms/index.php">
    <input type="hidden" name="url" value="vehicles">
    <div class="filter-bar">
        <select class="filter-select" name="status" onchange="this.form.submit()">
            <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>All Statuses</option>
            <option value="available"         <?= $statusFilter === 'available'         ? 'selected' : '' ?>>Available</option>
            <option value="reserved"          <?= $statusFilter === 'reserved'          ? 'selected' : '' ?>>Reserved</option>
            <option value="on_trip"           <?= $statusFilter === 'on_trip'           ? 'selected' : '' ?>>On Trip</option>
            <option value="under_maintenance" <?= $statusFilter === 'under_maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
            <option value="retired"           <?= $statusFilter === 'retired'           ? 'selected' : '' ?>>Retired</option>
        </select>
    </div>
</form>

<div class="card"><div class="table-wrap"><table class="data-table">
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
            <td colspan="7" class="td-muted" style="text-align:center;padding:24px;">
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
