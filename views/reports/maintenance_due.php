<div class="page-header"><div class="page-header-left"><h2>Maintenance Due</h2></div></div>
<div class="tab-bar"><a href="<?= Helpers::url('/reports/trip-history') ?>" class="tab-item">Trip History</a><a href="<?= Helpers::url('/reports/maintenance-due') ?>" class="tab-item active">Maintenance Due</a><a href="<?= Helpers::url('/reports/vehicle-utilization') ?>" class="tab-item">Vehicle Utilization</a></div>
<div class="dashboard-grid dashboard-grid--3" style="margin-bottom:24px;">
    <div class="stat-card stat-card--accent"><div class="stat-label">Overdue</div><div class="stat-value">—</div><div class="stat-sub">Past next service km</div></div>
    <div class="stat-card"><div class="stat-label">Due Within 500 km</div><div class="stat-value">—</div><div class="stat-sub">Approaching interval</div></div>
    <div class="stat-card"><div class="stat-label">Up to Date</div><div class="stat-value">—</div><div class="stat-sub">Within safe interval</div></div>
</div>
<div class="card"><div class="table-wrap"><table class="data-table"><thead><tr><th>Vehicle</th><th>Current Odometer</th><th>Next Service At</th><th>Remaining</th><th>Alert</th></tr></thead><tbody>
<tr><td><strong>DEF-9012</strong><br><span class="td-muted">Isuzu Elf 2020</span></td><td>58,900 km</td><td>60,000 km</td><td style="color:var(--clr-error);font-weight:600;">1,100 km</td><td><span class="badge badge-pending">Due Soon</span></td></tr>
<tr><td><strong>XYZ-5678</strong><br><span class="td-muted">Mitsubishi L300 2021</span></td><td>34,200 km</td><td>35,000 km</td><td style="color:var(--clr-error);font-weight:600;">800 km</td><td><span class="badge badge-pending">Due Soon</span></td></tr>
<tr><td><strong>ABC-1234</strong><br><span class="td-muted">Toyota Hi-Ace 2022</span></td><td>12,450 km</td><td>15,000 km</td><td>2,550 km</td><td><span class="badge badge-available">OK</span></td></tr>
</tbody></table></div></div>