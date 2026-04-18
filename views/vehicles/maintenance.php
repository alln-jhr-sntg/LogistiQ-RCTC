<div class="page-header"><div class="page-header-left"><h2>Maintenance — ABC-1234</h2><p>Toyota Hi-Ace 2022 — Current odometer: 12,450 km</p></div><a href="<?= Helpers::url('/vehicles') ?>" class="btn btn-outline">← Fleet</a></div>
<div style="background:#fef9e7;border:1px solid #f5d76e;border-radius:var(--radius-lg);padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;gap:12px;">
    <svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:#9a6c00;flex-shrink:0;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
    <div><strong style="font-size:14px;color:#7a5200;">Next service due at 15,000 km</strong><span style="font-size:13px;color:#9a6c00;margin-left:8px;">— 2,550 km remaining</span></div>
</div>
<div class="form-card" style="margin-bottom:24px;"><div class="form-section-title">Log Maintenance Record</div>
<form method="POST" action="<?= Helpers::url('/vehicles/1/maintenance') ?>">
<div class="form-row"><div class="form-group"><label class="form-label">Maintenance Type <span class="required">*</span></label><input type="text" class="form-input" name="maintenance_type" placeholder="e.g. Oil Change" required></div><div class="form-group"><label class="form-label">Service Date <span class="required">*</span></label><input type="date" class="form-input" name="service_date" required></div></div>
<div class="form-row-3 form-row"><div class="form-group"><label class="form-label">Odometer at Service (km)</label><input type="number" class="form-input" name="odometer_at_service" step="0.01"></div><div class="form-group"><label class="form-label">Next Service (km)</label><input type="number" class="form-input" name="next_service_km" step="0.01"><p class="form-hint">Auto: odometer + 5,000 km</p></div><div class="form-group"><label class="form-label">Cost (₱)</label><input type="number" class="form-input" name="cost" step="0.01" min="0"></div></div>
<div class="form-group"><label class="form-label">Performed By</label><input type="text" class="form-input" name="performed_by" placeholder="Shop or technician"></div>
<div class="form-group"><label class="form-label">Description</label><textarea class="form-textarea" name="description"></textarea></div>
<div class="form-actions"><button type="submit" class="btn btn-solid">Save Record</button></div>
</form></div>
<div class="section-title" style="margin-bottom:12px;">Maintenance History</div>
<div class="card"><div class="table-wrap"><table class="data-table"><thead><tr><th>Date</th><th>Type</th><th>Odometer</th><th>Next Service</th><th>Cost</th><th>Performed By</th></tr></thead><tbody>
<tr><td>Nov 15, 2025</td><td><strong>Oil Change</strong></td><td class="td-muted">10,000 km</td><td class="td-muted">15,000 km</td><td class="td-muted">₱1,800</td><td class="td-muted">Toyota PH Service</td></tr>
<tr><td>Aug 02, 2025</td><td><strong>PMS 5,000 km</strong></td><td class="td-muted">5,000 km</td><td class="td-muted">10,000 km</td><td class="td-muted">₱3,500</td><td class="td-muted">Toyota PH Service</td></tr>
</tbody></table></div></div>