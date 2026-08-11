<div class="page-header">
    <div class="page-header-left">
        <h2>Gatepasses</h2>
        <p>Pending gatepasses awaiting review before a trip can start.</p>
    </div>
    <a href="<?= Helpers::url('/reservations') ?>" class="btn btn-outline">← Reservations</a>
</div>

<div class="card"><div class="table-wrap"><table class="data-table">
    <thead>
        <tr>
            <th>Gatepass Code</th>
            <th>Reservation</th>
            <th>Requester</th>
            <th>Vehicle</th>
            <th>Driver</th>
            <th>Destination</th>
            <th>Departure</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($gatepasses)): ?>
        <tr>
            <td colspan="8" class="td-muted" style="text-align:center;padding:24px;">
                No gatepasses pending review.
            </td>
        </tr>
    <?php else: ?>
        <?php foreach ($gatepasses as $g): ?>
        <tr>
            <td><strong><?= Helpers::e($g['gatepass_code']) ?></strong></td>
            <td class="td-muted"><?= Helpers::e($g['reservation_code']) ?></td>
            <td><?= Helpers::e($g['requester_first_name'] . ' ' . $g['requester_last_name']) ?></td>
            <td class="td-muted">
                <?= $g['plate_number']
                    ? Helpers::e($g['plate_number'] . ' — ' . $g['vehicle_brand'] . ' ' . $g['vehicle_model'])
                    : '—' ?>
            </td>
            <td class="td-muted">
                <?= $g['driver_first_name']
                    ? Helpers::e($g['driver_first_name'] . ' ' . $g['driver_last_name'])
                    : '—' ?>
            </td>
            <td class="td-muted"><?= Helpers::e($g['destination']) ?></td>
            <td class="td-muted"><?= date('M d Y, g:i A', strtotime($g['departure_datetime'])) ?></td>
            <td>
                <div class="td-actions">
                    <a href="<?= Helpers::url('/gatepasses/' . $g['gatepass_id'] . '/review') ?>"
                       class="btn btn-outline btn-sm">Review</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table></div></div>
