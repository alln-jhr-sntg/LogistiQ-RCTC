<div class="page-header">
    <div class="page-header-left"><h2>Review — RES-2025-00001</h2><p>Approve or reject and assign a vehicle and driver</p></div>
    <a href="<?= Helpers::url('/reservations/1') ?>" class="btn btn-outline">← Back</a>
</div>
<div class="detail-grid">
    <div class="detail-card">
        <div class="detail-card-title">Request Summary</div>
        <div class="detail-field"><div class="detail-field-label">Requester</div><div class="detail-field-value">Juan dela Cruz — Engineering (REMIX)</div></div>
        <div class="detail-field"><div class="detail-field-label">Destination</div><div class="detail-field-value">Pasig City Warehouse</div></div>
        <div class="detail-field"><div class="detail-field-label">Departure</div><div class="detail-field-value">Dec 10, 2025 08:00 AM</div></div>
        <div class="detail-field"><div class="detail-field-label">Return</div><div class="detail-field-value">Dec 10, 2025 05:00 PM</div></div>
        <div class="detail-field"><div class="detail-field-label">Passengers</div><div class="detail-field-value">3</div></div>
        <div class="detail-field"><div class="detail-field-label">Cargo</div><div class="detail-field-value">0 kg</div></div>
    </div>
    <div class="detail-card">
        <div class="detail-card-title">AI Vehicle Recommendation</div>
        <div style="background:var(--clr-bg);border-radius:var(--radius-md);padding:14px;margin-bottom:12px;">
            <div style="font-size:12px;font-weight:600;text-transform:uppercase;color:var(--clr-text-3);margin-bottom:8px;">Top Match</div>
            <div style="font-size:15px;font-weight:600;">ABC-1234 — Toyota Hi-Ace</div>
            <div style="font-size:13px;color:var(--clr-text-3);margin:4px 0 10px;">Score: 92.50 / 100</div>
            <div style="display:grid;gap:4px;">
                <div style="display:flex;justify-content:space-between;font-size:13px;"><span style="color:var(--clr-text-3);">Capacity fit</span><span>95</span></div>
                <div style="display:flex;justify-content:space-between;font-size:13px;"><span style="color:var(--clr-text-3);">Cargo fit</span><span>100</span></div>
                <div style="display:flex;justify-content:space-between;font-size:13px;"><span style="color:var(--clr-text-3);">Availability</span><span>90</span></div>
                <div style="display:flex;justify-content:space-between;font-size:13px;"><span style="color:var(--clr-text-3);">Purpose fit</span><span>88</span></div>
                <div style="display:flex;justify-content:space-between;font-size:13px;"><span style="color:var(--clr-text-3);">Maintenance</span><span>90</span></div>
            </div>
        </div>
        <p style="font-size:12px;color:var(--clr-text-3);">Capstone 1 — scores are static placeholders.</p>
    </div>
</div>
<div class="form-card" style="margin-bottom:16px;">
    <div class="form-section-title">Assign & Approve</div>
    <form method="POST" action="<?= Helpers::url('/reservations/1/approve') ?>">
        <div class="form-row">
            <div class="form-group"><label class="form-label">Assign Vehicle <span class="required">*</span></label><select class="form-select" name="vehicle_id" required><option value="">— Select Vehicle —</option><option value="1">ABC-1234 — Toyota Hi-Ace (Available)</option><option value="2">XYZ-5678 — Mitsubishi L300 (Available)</option></select></div>
            <div class="form-group"><label class="form-label">Assign Driver <span class="required">*</span></label><select class="form-select" name="driver_id" required><option value="">— Select Driver —</option><option value="1">Carlos Mendoza (Available)</option><option value="2">Roberto Cruz (Available)</option></select></div>
        </div>
        <div class="form-actions"><button type="submit" class="btn btn-solid">Approve & Assign</button></div>
    </form>
</div>
<div class="form-card">
    <div class="form-section-title">Reject</div>
    <form method="POST" action="<?= Helpers::url('/reservations/1/reject') ?>">
        <div class="form-group"><label class="form-label">Rejection Reason <span class="required">*</span></label><textarea class="form-textarea" name="rejection_reason" placeholder="State the reason…" required></textarea></div>
        <div class="form-actions"><button type="submit" class="btn btn-danger">Reject Reservation</button></div>
    </form>
</div>