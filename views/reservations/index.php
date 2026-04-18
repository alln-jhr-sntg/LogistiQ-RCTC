<?php $role = Auth::role(); ?>
<div class="page-header">
    <div class="page-header-left"><h2>Reservations</h2><p><?= $role === ROLE_EMPLOYEE ? 'Your vehicle reservation requests' : 'All reservation requests across departments' ?></p></div>
    <?php if (in_array($role, [ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE])): ?>
    <a href="<?= Helpers::url('/reservations/create') ?>" class="btn btn-solid"><svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg> New Reservation</a>
    <?php endif; ?>
</div>
<div class="tab-bar">
    <a href="#" class="tab-item active">All</a><a href="#" class="tab-item">Pending</a><a href="#" class="tab-item">Approved</a><a href="#" class="tab-item">In Progress</a><a href="#" class="tab-item">Completed</a><a href="#" class="tab-item">Rejected</a>
</div>
<div class="filter-bar">
    <input type="text" class="filter-input" placeholder="Search by code or destination…" style="flex:1;min-width:200px;">
    <?php if ($role !== ROLE_EMPLOYEE): ?><select class="filter-select"><option>All Companies</option><option>REMIX</option><option>IDEAL</option><option>TENBUILD</option></select><?php endif; ?>
</div>
<div class="card"><div class="table-wrap"><table class="data-table">
    <thead><tr><th>Code</th><?php if ($role !== ROLE_EMPLOYEE): ?><th>Requested By</th><?php endif; ?><th>Destination</th><th>Departure</th><th>Return</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
        <tr><td><strong>RES-2025-00001</strong></td><td>Juan dela Cruz<br><span class="td-muted">Engineering — REMIX</span></td><td>Pasig City Warehouse</td><td class="td-muted">Dec 10, 2025 08:00</td><td class="td-muted">Dec 10, 2025 17:00</td><td><span class="badge badge-pending">Pending</span></td><td><div class="td-actions"><a href="<?= Helpers::url('/reservations/1') ?>" class="btn btn-outline btn-sm">View</a><?php if ($role !== ROLE_EMPLOYEE): ?><a href="<?= Helpers::url('/reservations/1/review') ?>" class="btn btn-solid btn-sm">Review</a><?php endif; ?></div></td></tr>
        <tr><td><strong>RES-2025-00002</strong></td><td>Maria Santos<br><span class="td-muted">Operations — IDEAL</span></td><td>Quezon City Site</td><td class="td-muted">Dec 11, 2025 07:00</td><td class="td-muted">Dec 11, 2025 18:00</td><td><span class="badge badge-approved">Approved</span></td><td><div class="td-actions"><a href="<?= Helpers::url('/reservations/2') ?>" class="btn btn-outline btn-sm">View</a></div></td></tr>
        <tr><td><strong>RES-2025-00003</strong></td><td>Pedro Reyes<br><span class="td-muted">Logistics — TENBUILD</span></td><td>Laguna Construction Site</td><td class="td-muted">Dec 12, 2025 06:00</td><td class="td-muted">Dec 12, 2025 20:00</td><td><span class="badge badge-in-progress">In Progress</span></td><td><div class="td-actions"><a href="<?= Helpers::url('/reservations/3') ?>" class="btn btn-outline btn-sm">View</a></div></td></tr>
        <tr><td><strong>RES-2025-00004</strong></td><td>Ana Lim<br><span class="td-muted">Admin — REMIX</span></td><td>Makati Office</td><td class="td-muted">Dec 08, 2025 09:00</td><td class="td-muted">Dec 08, 2025 16:00</td><td><span class="badge badge-completed">Completed</span></td><td><div class="td-actions"><a href="<?= Helpers::url('/reservations/4') ?>" class="btn btn-outline btn-sm">View</a></div></td></tr>
    </tbody>
</table></div></div>