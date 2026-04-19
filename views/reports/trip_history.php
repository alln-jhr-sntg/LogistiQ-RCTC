<div class="page-header">
    <div class="page-header-left"><h2>Trip History</h2></div>
    <form method="POST" action="<?= Helpers::url('/reports/trip-history/export') ?>">
        <button type="submit" class="btn btn-outline">
            <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;vertical-align:middle;margin-right:4px;"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
            Export
        </button>
    </form>
</div>
<div class="tab-bar"><a href="<?= Helpers::url('/reports/trip-history') ?>" class="tab-item active">Trip History</a><a href="<?= Helpers::url('/reports/maintenance-due') ?>" class="tab-item">Maintenance Due</a><a href="<?= Helpers::url('/reports/vehicle-utilization') ?>" class="tab-item">Vehicle Utilization</a></div>
<div class="filter-bar"><input type="date" class="filter-input"><input type="date" class="filter-input"><select class="filter-select"><option>All Companies</option><option>REMIX</option><option>IDEAL</option><option>TENBUILD</option></select></div>
<div class="card"><div class="table-wrap"><table class="data-table"><thead><tr><th>Reservation</th><th>Vehicle</th><th>Driver</th><th>Destination</th><th>Date</th><th>Distance</th><th>Status</th></tr></thead><tbody>
<tr><td><strong>RES-2025-00004</strong></td><td>DEF-9012 — Isuzu Elf</td><td>Marco Villanueva</td><td>Makati Office</td><td class="td-muted">Dec 08, 2025</td><td class="td-muted">42 km</td><td><span class="badge badge-completed">Completed</span></td></tr>
<tr><td><strong>RES-2025-00005</strong></td><td>GHI-3456 — Ford Ranger</td><td>Anton Lim</td><td>Bulacan Site</td><td class="td-muted">Dec 05, 2025</td><td class="td-muted">68 km</td><td><span class="badge badge-completed">Completed</span></td></tr>
</tbody></table></div></div>