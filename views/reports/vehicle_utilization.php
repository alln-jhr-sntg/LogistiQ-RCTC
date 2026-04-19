<div class="page-header">
    <div class="page-header-left"><h2>Vehicle Utilization</h2></div>
    <form method="POST" action="<?= Helpers::url('/reports/vehicle-utilization/export') ?>">
        <button type="submit" class="btn btn-outline">
            <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;vertical-align:middle;margin-right:4px;"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
            Export
        </button>
    </form>
</div>
<div class="tab-bar"><a href="<?= Helpers::url('/reports/trip-history') ?>" class="tab-item">Trip History</a><a href="<?= Helpers::url('/reports/maintenance-due') ?>" class="tab-item">Maintenance Due</a><a href="<?= Helpers::url('/reports/vehicle-utilization') ?>" class="tab-item active">Vehicle Utilization</a></div>
<div class="dashboard-grid" style="margin-bottom:24px;">
    <div class="stat-card"><div class="stat-label">Total Fleet</div><div class="stat-value">4</div></div>
    <div class="stat-card"><div class="stat-label">Total Trips</div><div class="stat-value">—</div></div>
    <div class="stat-card"><div class="stat-label">Total Distance</div><div class="stat-value">—</div></div>
    <div class="stat-card"><div class="stat-label">Avg. Trips / Vehicle</div><div class="stat-value">—</div></div>
</div>
<div class="card"><div class="table-wrap"><table class="data-table"><thead><tr><th>Vehicle</th><th>Category</th><th>Total Trips</th><th>Odometer</th><th>Status</th></tr></thead><tbody>
<tr><td><strong>ABC-1234</strong><br><span class="td-muted">Toyota Hi-Ace 2022</span></td><td class="td-muted">Van</td><td>—</td><td class="td-muted">12,450 km</td><td><span class="badge badge-on-trip">On Trip</span></td></tr>
<tr><td><strong>XYZ-5678</strong><br><span class="td-muted">Mitsubishi L300 2021</span></td><td class="td-muted">Van</td><td>—</td><td class="td-muted">34,200 km</td><td><span class="badge badge-available">Available</span></td></tr>
<tr><td><strong>DEF-9012</strong><br><span class="td-muted">Isuzu Elf 2020</span></td><td class="td-muted">Truck</td><td>—</td><td class="td-muted">58,900 km</td><td><span class="badge badge-maintenance">Under Maintenance</span></td></tr>
<tr><td><strong>GHI-3456</strong><br><span class="td-muted">Ford Ranger 2023</span></td><td class="td-muted">Pickup</td><td>—</td><td class="td-muted">8,100 km</td><td><span class="badge badge-available">Available</span></td></tr>
</tbody></table></div></div>