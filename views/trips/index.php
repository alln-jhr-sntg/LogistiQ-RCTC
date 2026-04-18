<?php $role = Auth::role(); ?>

<div class="page-header">
    <div class="page-header-left">
        <h2><?= $role === ROLE_DRIVER ? 'My Trips' : 'Trips' ?></h2>
        <p><?= $role === ROLE_DRIVER ? 'Your assigned vehicle trips' : 'All active and past trips' ?></p>
    </div>
</div>

<div class="tab-bar">
    <a href="#" class="tab-item active">All</a>
    <a href="#" class="tab-item">Pending Start</a>
    <a href="#" class="tab-item">In Progress</a>
    <a href="#" class="tab-item">Completed</a>
    <?php if ($role !== ROLE_DRIVER): ?>
    <a href="#" class="tab-item">Incident</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Reservation</th>
                    <th>Vehicle</th>
                    <?php if ($role !== ROLE_DRIVER): ?><th>Driver</th><?php endif; ?>
                    <th>Destination</th>
                    <th>Departure</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>

                <!-- In-progress trip -->
                <tr>
                    <td><strong>RES-2025-00003</strong></td>
                    <td>ABC-1234<br><span class="td-muted">Toyota Hi-Ace</span></td>
                    <?php if ($role !== ROLE_DRIVER): ?>
                    <td>Carlos Mendoza</td>
                    <?php endif; ?>
                    <td>Laguna Construction Site</td>
                    <td class="td-muted">Dec 12, 2025 06:00</td>
                    <td><span class="badge badge-in-progress">In Progress</span></td>
                    <td>
                        <div class="td-actions">
                            <a href="<?= Helpers::url('/trips/1') ?>" class="btn btn-outline btn-sm">View</a>
                            <?php if ($role !== ROLE_DRIVER): ?>
                                <a href="<?= Helpers::url('/trips/1/map') ?>" class="btn btn-solid btn-sm">Live Map</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>

                <!-- Pending start trip -->
                <tr>
                    <td><strong>RES-2025-00002</strong></td>
                    <td>XYZ-5678<br><span class="td-muted">Mitsubishi L300</span></td>
                    <?php if ($role !== ROLE_DRIVER): ?>
                    <td>Roberto Cruz</td>
                    <?php endif; ?>
                    <td>Quezon City Site</td>
                    <td class="td-muted">Dec 13, 2025 07:00</td>
                    <td><span class="badge badge-pending">Pending Start</span></td>
                    <td>
                        <div class="td-actions">
                            <a href="<?= Helpers::url('/trips/2') ?>" class="btn btn-outline btn-sm">View</a>
                        </div>
                    </td>
                </tr>

                <!-- Completed trip -->
                <tr>
                    <td><strong>RES-2025-00004</strong></td>
                    <td>DEF-9012<br><span class="td-muted">Isuzu Elf</span></td>
                    <?php if ($role !== ROLE_DRIVER): ?>
                    <td>Marco Villanueva</td>
                    <?php endif; ?>
                    <td>Makati Office</td>
                    <td class="td-muted">Dec 08, 2025 09:00</td>
                    <td><span class="badge badge-completed">Completed</span></td>
                    <td>
                        <div class="td-actions">
                            <a href="<?= Helpers::url('/trips/3') ?>" class="btn btn-outline btn-sm">View</a>
                        </div>
                    </td>
                </tr>

            </tbody>
        </table>
    </div>
</div>
