<?php $role = Auth::role(); ?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Trip — RES-2025-00003</h2>
        <p>Laguna Construction Site &mdash; <span class="badge badge-in-progress">In Progress</span></p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="<?= Helpers::url('/trips') ?>" class="btn btn-outline">← Back</a>
        <?php if ($role === ROLE_DRIVER): ?>
            <a href="<?= Helpers::url('/trips/1/active') ?>" class="btn btn-solid">
                <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;"><path d="M8 5v14l11-7z"/></svg>
                Go to Active Trip
            </a>
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

    <div class="detail-card">
        <div class="detail-card-title">Notes</div>
        <div class="detail-field">
            <div class="detail-field-label">Driver Notes</div>
            <div class="detail-field-value" style="color:var(--clr-text-3);font-style:italic;">No notes added.</div>
        </div>
        <?php if ($role !== ROLE_DRIVER): ?>
        <div class="detail-field">
            <div class="detail-field-label">Admin Notes</div>
            <form method="POST" action="<?= Helpers::url('/trips/1/notes') ?>" style="margin-top:6px;">
                <textarea class="form-textarea" name="admin_notes" placeholder="Add admin note…" style="min-height:70px;"></textarea>
                <button type="submit" class="btn btn-outline btn-sm" style="margin-top:8px;">Save Note</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <div class="detail-card">
        <div class="detail-card-title">Incidents</div>
        <div style="color:var(--clr-text-3);font-size:14px;font-style:italic;padding:8px 0;">No incidents reported for this trip.</div>
    </div>

</div>

<?php if ($role !== ROLE_DRIVER): ?>
<div style="display:flex;gap:10px;margin-top:4px;">
    <form method="POST" action="<?= Helpers::url('/trips/1/complete') ?>">
        <button type="submit" class="btn btn-solid">Mark Completed</button>
    </form>
</div>
<?php endif; ?>
