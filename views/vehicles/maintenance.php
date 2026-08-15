<div class="page-header">
    <div class="page-header-left">
        <p>
            <?= Helpers::e($vehicle['brand'] . ' ' . $vehicle['model'] . ' ' . $vehicle['year_model']) ?>
            — Current odometer: <?= number_format((float) $vehicle['current_odometer_km'], 0) ?> km
        </p>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-solid" onclick="document.getElementById('addLogModal').style.display='flex';">
            <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg> Add Log
        </button>
        <form method="POST"
              action="<?= Helpers::url('/vehicles/' . $vehicle['vehicle_id'] . '/maintenance/check') ?>">
            <button type="submit" class="btn btn-outline">Check Maintenance Status</button>
        </form>
        <a href="<?= Helpers::url('/vehicles') ?>" class="btn btn-outline">← Fleet</a>
    </div>
</div>

<?php if ($maintenanceAlert): ?>
    <?php if ($maintenanceAlert['type'] === 'overdue'): ?>
    <div class="alert-banner alert-banner-danger">
        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        <div>
            <strong class="alert-banner-title">Service overdue — next was at <?= number_format($maintenanceAlert['next_km'], 0) ?> km</strong>
            <span class="alert-banner-sub">— <?= number_format(abs($maintenanceAlert['km_remaining']), 0) ?> km past due</span>
        </div>
    </div>
    <?php elseif ($maintenanceAlert['type'] === 'due_soon'): ?>
    <div class="alert-banner alert-banner-warning">
        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        <div>
            <strong class="alert-banner-title">Next service due at <?= number_format($maintenanceAlert['next_km'], 0) ?> km</strong>
            <span class="alert-banner-sub">— <?= number_format($maintenanceAlert['km_remaining'], 0) ?> km remaining</span>
        </div>
    </div>
    <?php endif; ?>
<?php elseif (!$latest): ?>
    <div class="alert-banner alert-banner-neutral">
        No maintenance baseline yet. Add the first record below to enable service interval tracking.
    </div>
<?php endif; ?>

<!-- Add Log Modal -->
<div id="addLogModal" class="modal-overlay">
    <div class="modal-card modal-card-wide">
        <div class="modal-header">
            <h3>Log Maintenance Record</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('addLogModal').style.display='none';" aria-label="Close">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= Helpers::url('/vehicles/' . $vehicle['vehicle_id'] . '/maintenance') ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Maintenance Type <span class="required">*</span></label>
                    <select class="form-select" name="maintenance_type" required>
                        <option value="">Select type…</option>
                        <?php foreach (MAINTENANCE_TYPES as $type): ?>
                        <option value="<?= Helpers::e($type) ?>"><?= Helpers::e($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Service Date <span class="required">*</span></label>
                    <input type="date" class="form-input" name="service_date" required>
                </div>
            </div>
            <div class="form-row form-row-3">
                <div class="form-group">
                    <label class="form-label">Odometer at Service (km)</label>
                    <input type="number" class="form-input" name="odometer_at_service" step="1" min="0"
                           placeholder="<?= (int) $vehicle['current_odometer_km'] ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Next Service (km)</label>
                    <input type="number" class="form-input" name="next_service_km" step="1" min="0">
                    <p class="form-hint">Auto: odometer + 5,000 km if left blank</p>
                </div>
                <div class="form-group">
                    <label class="form-label">Cost (₱)</label>
                    <input type="number" class="form-input" name="cost" step="0.01" min="0">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Performed By</label>
                <input type="text" class="form-input" name="performed_by" placeholder="Shop or technician">
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-textarea" name="description"></textarea>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-solid">Save Record</button>
                <button type="button" class="btn btn-outline" onclick="document.getElementById('addLogModal').style.display='none';">Cancel</button>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('addLogModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>

<div class="section-title">Maintenance History</div>

<form method="GET" action="<?= APP_BASE ?>/index.php">
    <input type="hidden" name="url" value="vehicles/<?= (int) $vehicle['vehicle_id'] ?>/maintenance">
    <div class="filter-bar">
        <input type="date" class="filter-input" name="date_from"
               value="<?= Helpers::e($filters['date_from']) ?>"
               placeholder="From">
        <input type="date" class="filter-input" name="date_to"
               value="<?= Helpers::e($filters['date_to']) ?>"
               placeholder="To">
        <select class="filter-select" name="maintenance_type">
            <option value="">All Types</option>
            <?php foreach (MAINTENANCE_TYPES as $type): ?>
            <option value="<?= Helpers::e($type) ?>" <?= $filters['maintenance_type'] === $type ? 'selected' : '' ?>>
                <?= Helpers::e($type) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-solid btn-sm">Filter</button>
        <a href="<?= Helpers::url('/vehicles/' . $vehicle['vehicle_id'] . '/maintenance') ?>" class="btn btn-outline btn-sm">Clear</a>
    </div>
</form>

<div class="card"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Odometer</th>
            <th>Next Service</th>
            <th>Cost</th>
            <th>Performed By</th>
            <th>Recorded By</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($history)): ?>
        <tr>
            <td colspan="7" class="td-muted td-empty">
                <?= !empty(array_filter($filters)) ? 'No maintenance records matching the selected filters.' : 'No maintenance records yet.' ?>
            </td>
        </tr>
    <?php else: ?>
        <?php foreach ($history as $h): ?>
        <tr>
            <td><?= date('M d, Y', strtotime($h['service_date'])) ?></td>
            <td><strong><?= Helpers::e($h['maintenance_type']) ?></strong>
                <?php if ($h['description']): ?>
                <br><span class="td-muted"><?= Helpers::e($h['description']) ?></span>
                <?php endif; ?>
            </td>
            <td class="td-muted">
                <?= $h['odometer_at_service'] !== null ? number_format((float) $h['odometer_at_service'], 0) . ' km' : '—' ?>
            </td>
            <td class="td-muted">
                <?= $h['next_service_km'] !== null ? number_format((float) $h['next_service_km'], 0) . ' km' : '—' ?>
            </td>
            <td class="td-muted">
                <?= $h['cost'] !== null ? '₱' . number_format((float) $h['cost'], 2) : '—' ?>
            </td>
            <td class="td-muted"><?= $h['performed_by'] ? Helpers::e($h['performed_by']) : '—' ?></td>
            <td class="td-muted"><?= Helpers::e($h['first_name'] . ' ' . $h['last_name']) ?></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table></div></div>
<?= $pagination ?>
