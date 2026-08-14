<div class="page-header">
    <div class="page-header-left">
        <p>
            <?= Helpers::e($vehicle['brand'] . ' ' . $vehicle['model'] . ' ' . $vehicle['year_model']) ?>
            — Current odometer: <?= number_format((float) $vehicle['current_odometer_km'], 0) ?> km
        </p>
    </div>
    <div style="display:flex;gap:8px;">
        <form method="POST"
              action="<?= Helpers::url('/vehicles/' . $vehicle['vehicle_id'] . '/maintenance/check') ?>"
              style="display:inline;">
            <button type="submit" class="btn btn-outline">Check Maintenance Status</button>
        </form>
        <a href="<?= Helpers::url('/vehicles') ?>" class="btn btn-outline">← Fleet</a>
    </div>
</div>

<?php if ($maintenanceAlert): ?>
    <?php if ($maintenanceAlert['type'] === 'overdue'): ?>
    <div style="background:#fdecea;border:1px solid #f5a09a;border-radius:var(--radius-lg);padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;gap:12px;">
        <svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:#c0392b;flex-shrink:0;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        <div>
            <strong style="font-size:14px;color:#922b21;">Service overdue — next was at <?= number_format($maintenanceAlert['next_km'], 0) ?> km</strong>
            <span style="font-size:13px;color:#c0392b;margin-left:8px;">
                — <?= number_format(abs($maintenanceAlert['km_remaining']), 0) ?> km past due
            </span>
        </div>
    </div>
    <?php elseif ($maintenanceAlert['type'] === 'due_soon'): ?>
    <div style="background:#fef9e7;border:1px solid #f5d76e;border-radius:var(--radius-lg);padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;gap:12px;">
        <svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:#9a6c00;flex-shrink:0;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        <div>
            <strong style="font-size:14px;color:#7a5200;">Next service due at <?= number_format($maintenanceAlert['next_km'], 0) ?> km</strong>
            <span style="font-size:13px;color:#9a6c00;margin-left:8px;">
                — <?= number_format($maintenanceAlert['km_remaining'], 0) ?> km remaining
            </span>
        </div>
    </div>
    <?php endif; ?>
<?php elseif (!$latest): ?>
    <div style="background:var(--clr-surface-2);border:1px solid var(--clr-border);border-radius:var(--radius-lg);padding:16px 20px;margin-bottom:24px;color:var(--clr-text-3);font-size:14px;">
        No maintenance baseline yet. Add the first record below to enable service interval tracking.
    </div>
<?php endif; ?>

<div class="form-card" style="margin-bottom:24px;">
    <div class="form-section-title">Log Maintenance Record</div>
    <form method="POST" action="<?= Helpers::url('/vehicles/' . $vehicle['vehicle_id'] . '/maintenance') ?>">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Maintenance Type <span class="required">*</span></label>
                <input type="text" class="form-input" name="maintenance_type" placeholder="e.g. Oil Change" required>
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
        <div class="form-actions">
            <button type="submit" class="btn btn-solid">Save Record</button>
        </div>
    </form>
</div>

<div class="section-title" style="margin-bottom:12px;">Maintenance History</div>
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
            <td colspan="7" class="td-muted" style="text-align:center;padding:24px;">
                No maintenance records yet.
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
