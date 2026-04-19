<?php $role = Auth::role(); ?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Trip — RES-2025-00003</h2>
        <p>Laguna Construction Site &mdash; <span class="badge badge-in-progress">In Progress</span></p>
    </div>
    <div class="page-header-actions">
        <a href="<?= Helpers::url('/trips') ?>" class="btn btn-outline">← Back</a>
        <?php if ($role === ROLE_DRIVER): ?>
            <a href="<?= Helpers::url('/trips/1/active') ?>" class="btn btn-solid">Go to Active Trip</a>
        <?php else: ?>
            <a href="<?= Helpers::url('/trips/1/map') ?>" class="btn btn-solid">Live Map</a>
        <?php endif; ?>
    </div>
</div>

<div class="detail-grid">

    <div class="detail-card">
        <div class="detail-card-title">Trip Information</div>
        <div class="detail-field"><div class="detail-field-label">Reservation</div><div class="detail-field-value">RES-2025-00003</div></div>
        <div class="detail-field"><div class="detail-field-label">Destination</div><div class="detail-field-value">Laguna Construction Site</div></div>
        <div class="detail-field"><div class="detail-field-label">Scheduled Departure</div><div class="detail-field-value">Dec 12, 2025 06:00 AM</div></div>
        <div class="detail-field"><div class="detail-field-label">Scheduled Return</div><div class="detail-field-value">Dec 12, 2025 08:00 PM</div></div>
        <div class="detail-field"><div class="detail-field-label">Actual Departure</div><div class="detail-field-value">Dec 12, 2025 06:14 AM</div></div>
        <div class="detail-field"><div class="detail-field-label">Actual Return</div><div class="detail-field-value">— Still on trip</div></div>
    </div>

    <div class="detail-card">
        <div class="detail-card-title">Vehicle & Driver</div>
        <div class="detail-field"><div class="detail-field-label">Vehicle</div><div class="detail-field-value">ABC-1234 — Toyota Hi-Ace</div></div>
        <div class="detail-field"><div class="detail-field-label">Driver</div><div class="detail-field-value">Carlos Mendoza</div></div>
        <div class="detail-field"><div class="detail-field-label">Passengers</div><div class="detail-field-value">3</div></div>
        <div class="detail-field"><div class="detail-field-label">Cargo</div><div class="detail-field-value">0 kg</div></div>
        <div class="detail-field"><div class="detail-field-label">Odometer Start</div><div class="detail-field-value">12,450.00 km</div></div>
        <div class="detail-field"><div class="detail-field-label">Odometer End</div><div class="detail-field-value">— Not yet recorded</div></div>
    </div>

    <?php if (in_array($role, [ROLE_SUPER_ADMIN, ROLE_ADMIN])): ?>
    <div class="detail-card">
        <div class="detail-card-title">Admin Notes</div>
        <form method="POST" action="<?= Helpers::url('/trips/1/notes') ?>">
            <textarea class="form-textarea" name="admin_notes" placeholder="Add admin note…"></textarea>
            <button type="submit" class="btn btn-outline btn-sm btn-mt">Save Note</button>
        </form>
    </div>
    <?php endif; ?>

</div>


