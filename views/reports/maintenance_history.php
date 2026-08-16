<div class="tab-bar">
    <a href="<?= Helpers::url('/reports/trip-history') ?>"         class="tab-item">Trip History</a>
    <a href="<?= Helpers::url('/reports/maintenance-history') ?>"  class="tab-item active">Maintenance History</a>
    <a href="<?= Helpers::url('/reports/vehicle-utilization') ?>"  class="tab-item">Vehicle Utilization</a>
    <div class="tab-bar-actions">
        <form method="POST" action="<?= Helpers::url('/reports/maintenance-history/export') ?>">
            <?= Csrf::field() ?>
            <input type="hidden" name="date_from" value="<?= Helpers::e($filters['date_from']) ?>">
            <input type="hidden" name="date_to" value="<?= Helpers::e($filters['date_to']) ?>">
            <input type="hidden" name="vehicle_id" value="<?= Helpers::e((string) ($filters['vehicle_id'] ?? '')) ?>">
            <input type="hidden" name="maintenance_type" value="<?= Helpers::e($filters['maintenance_type']) ?>">
            <button type="submit" class="btn btn-outline">
                <svg viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
                Export
            </button>
        </form>
    </div>
</div>

<!-- Filters (GET so the filtered URL is bookmarkable) -->
<form method="GET" action="<?= APP_BASE ?>/index.php">
    <input type="hidden" name="url" value="reports/maintenance-history">
    <div class="filter-bar">
        <input type="date" class="filter-input" name="date_from"
               value="<?= Helpers::e($filters['date_from']) ?>"
               placeholder="From">
        <input type="date" class="filter-input" name="date_to"
               value="<?= Helpers::e($filters['date_to']) ?>"
               placeholder="To">
        <select class="filter-select" name="vehicle_id">
            <option value="">All Vehicles</option>
            <?php foreach ($vehicles as $v): ?>
            <option value="<?= (int) $v['vehicle_id'] ?>"
                    <?= (string) ($filters['vehicle_id'] ?? '') === (string) $v['vehicle_id'] ? 'selected' : '' ?>>
                <?= Helpers::e($v['plate_number'] . ' — ' . $v['brand'] . ' ' . $v['model']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select class="filter-select" name="maintenance_type">
            <option value="">All Types</option>
            <?php foreach (MAINTENANCE_TYPES as $type): ?>
            <option value="<?= Helpers::e($type) ?>" <?= $filters['maintenance_type'] === $type ? 'selected' : '' ?>>
                <?= Helpers::e($type) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-solid btn-sm">Filter</button>
        <a href="<?= Helpers::url('/reports/maintenance-history') ?>" class="btn btn-outline btn-sm">Clear</a>
    </div>
</form>

<div class="card"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Vehicle</th>
            <th>Type</th>
            <th>Odometer</th>
            <th>Next Service</th>
            <th>Cost</th>
            <th>Performed By</th>
            <th>Recorded By</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($records)): ?>
        <tr><td colspan="8" class="td-muted td-empty">
            No maintenance records found<?= !empty(array_filter($filters)) ? ' matching the selected filters' : '' ?>.
        </td></tr>
    <?php else: ?>
        <?php foreach ($records as $r): ?>
        <tr>
            <td><?= date('M d, Y', strtotime($r['service_date'])) ?></td>
            <td>
                <?= Helpers::e($r['plate_number']) ?><br>
                <span class="td-muted"><?= Helpers::e($r['brand'] . ' ' . $r['model']) ?></span>
            </td>
            <td><strong><?= Helpers::e($r['maintenance_type']) ?></strong>
                <?php if ($r['description']): ?>
                <br><span class="td-muted"><?= Helpers::e($r['description']) ?></span>
                <?php endif; ?>
            </td>
            <td class="td-muted">
                <?= $r['odometer_at_service'] !== null ? number_format((float) $r['odometer_at_service'], 0) . ' km' : '—' ?>
            </td>
            <td class="td-muted">
                <?= $r['next_service_km'] !== null ? number_format((float) $r['next_service_km'], 0) . ' km' : '—' ?>
            </td>
            <td class="td-muted">
                <?= $r['cost'] !== null ? '₱' . number_format((float) $r['cost'], 2) : '—' ?>
            </td>
            <td class="td-muted"><?= $r['performed_by'] ? Helpers::e($r['performed_by']) : '—' ?></td>
            <td class="td-muted"><?= Helpers::e($r['first_name'] . ' ' . $r['last_name']) ?></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table></div></div>
<?= $pagination ?>
