<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<!-- ── Current Trip ─────────────────────────────────────── -->
<div class="section-title" style="margin-bottom:12px;">Current Trip</div>

<!-- Current trip card — dark green treatment to stand out -->
<div style="background:var(--clr-primary);border-radius:var(--radius-lg);margin-bottom:24px;overflow:hidden;">

    <!-- Status bar -->
    <div style="background:rgba(255,255,255,.06);padding:10px 22px;display:flex;align-items:center;gap:16px;border-bottom:1px solid rgba(255,255,255,.1);">
        <span style="font-size:13px;font-weight:600;color:rgba(255,255,255,.9);">RES-2025-00003</span>
        <span class="badge badge-in-progress" style="font-size:11px;">In Progress</span>
        <span style="margin-left:auto;font-size:12px;color:rgba(255,255,255,.45);">Started 06:14 AM</span>
    </div>

    <!-- Trip details -->
    <div style="padding:20px 22px;">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">
            <div>
                <div style="font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.45);margin-bottom:3px;">Destination</div>
                <div style="font-size:14px;font-weight:500;color:#fff;">Laguna Construction Site</div>
            </div>
            <div>
                <div style="font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.45);margin-bottom:3px;">Scheduled Departure</div>
                <div style="font-size:14px;font-weight:500;color:#fff;">Dec 12, 2025 &mdash; 06:00 AM</div>
            </div>
            <div>
                <div style="font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.45);margin-bottom:3px;">Vehicle</div>
                <div style="font-size:14px;font-weight:500;color:#fff;">ABC-1234 &mdash; Toyota Hi-Ace</div>
            </div>
            <div>
                <div style="font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.45);margin-bottom:3px;">Passengers</div>
                <div style="font-size:14px;font-weight:500;color:#fff;">3</div>
            </div>
            <div>
                <div style="font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.45);margin-bottom:3px;">Cargo</div>
                <div style="font-size:14px;font-weight:500;color:#fff;">None</div>
            </div>
            <div>
                <div style="font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.45);margin-bottom:3px;">Purpose</div>
                <div style="font-size:14px;font-weight:500;color:#fff;">Site Visit</div>
            </div>
        </div>

        <!-- Action buttons -->
        <div style="display:flex;gap:10px;padding-top:16px;border-top:1px solid rgba(255,255,255,.1);">
            <button type="button"
                style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:var(--clr-accent);color:var(--clr-primary);border:none;border-radius:var(--radius-md);font-family:var(--font-body);font-size:14px;font-weight:600;cursor:pointer;transition:background .15s;"
                onmouseover="this.style.background='var(--clr-accent-lt)'" onmouseout="this.style.background='var(--clr-accent)'"
                onclick="openOdometerModal()">
                <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                Mark as Completed
            </button>
            <a href="<?= Helpers::url('/trips/1') ?>"
               style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:rgba(255,255,255,.1);color:rgba(255,255,255,.8);border:1.5px solid rgba(255,255,255,.2);border-radius:var(--radius-md);font-family:var(--font-body);font-size:14px;font-weight:500;text-decoration:none;transition:background .15s;"
               onmouseover="this.style.background='rgba(255,255,255,.16)'" onmouseout="this.style.background='rgba(255,255,255,.1)'">
                View Details
            </a>
        </div>
    </div>
</div>

<!-- ── Next Trip ─────────────────────────────────────────── -->
<div class="section-title" style="margin-bottom:12px;">Next Trip</div>
<div class="card" style="margin-bottom:24px;">
    <div style="padding:20px 22px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:18px;">
            <div>
                <div style="font-size:16px;font-weight:600;color:var(--clr-text);">RES-2025-00002</div>
                <div style="font-size:13px;color:var(--clr-text-3);margin-top:2px;">Material Delivery &mdash; Quezon City Site</div>
            </div>
            <span class="badge badge-approved">Approved</span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:18px;">
            <div><div class="detail-field-label">Destination</div><div style="font-size:14px;font-weight:500;margin-top:3px;">Quezon City Site</div></div>
            <div><div class="detail-field-label">Scheduled Departure</div><div style="font-size:14px;font-weight:500;margin-top:3px;">Dec 13, 2025 &mdash; 07:00 AM</div></div>
            <div><div class="detail-field-label">Vehicle</div><div style="font-size:14px;font-weight:500;margin-top:3px;">XYZ-5678 &mdash; Mitsubishi L300</div></div>
            <div><div class="detail-field-label">Passengers</div><div style="font-size:14px;font-weight:500;margin-top:3px;">2</div></div>
            <div><div class="detail-field-label">Cargo</div><div style="font-size:14px;font-weight:500;margin-top:3px;">250 kg &mdash; Cement bags</div></div>
            <div><div class="detail-field-label">Purpose</div><div style="font-size:14px;font-weight:500;margin-top:3px;">Material Delivery</div></div>
        </div>
        <div style="padding-top:16px;border-top:1px solid var(--clr-border);">
            <a href="<?= Helpers::url('/trips/2') ?>" class="btn btn-outline">View Details</a>
        </div>
    </div>
</div>

<!-- ── Live Map ───────────────────────────────────────────── -->
<div class="section-title" style="margin-bottom:12px;">Your Current Location</div>

<!-- GPS status strip -->
<div style="display:flex;align-items:center;gap:20px;margin-bottom:10px;padding:10px 16px;background:var(--clr-surface);border:1px solid var(--clr-border);border-radius:var(--radius-md);font-size:13px;">
    <span style="display:flex;align-items:center;gap:6px;">
        <span style="width:8px;height:8px;border-radius:50%;background:var(--clr-success);display:inline-block;box-shadow:0 0 0 3px rgba(39,118,74,.2);"></span>
        <strong style="color:var(--clr-success);">GPS Active</strong>
    </span>
    <span style="color:var(--clr-text-3);">Last ping: <span id="lastPing" style="color:var(--clr-text-2);font-weight:500;">10:42 AM</span></span>
    <span style="color:var(--clr-text-3);">Speed: <span style="color:var(--clr-text-2);font-weight:500;">62 km/h</span></span>
    <span style="color:var(--clr-text-3);">Heading: <span style="color:var(--clr-text-2);font-weight:500;">185° S</span></span>
</div>

<div class="card" style="overflow:hidden;margin-bottom:32px;">
    <div id="driverMap" style="height:380px;"></div>
</div>

<!-- ── Odometer Modal ────────────────────────────────────── -->
<div id="odometerModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:var(--clr-surface);border-radius:var(--radius-lg);padding:28px;max-width:400px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.2);">
        <h3 style="font-size:16px;font-weight:600;margin-bottom:4px;">Complete Trip</h3>
        <p style="font-size:13px;color:var(--clr-text-3);margin-bottom:20px;">Enter the current odometer reading on the vehicle to confirm completion.</p>
        <div class="form-group">
            <label class="form-label">Odometer at Return (km) <span class="required">*</span></label>
            <input type="number" class="form-input" id="odometerInput" placeholder="e.g. 12680" step="0.01" min="12450" oninput="checkOdometer(this.value)">
        </div>
        <div id="odometerWarning" style="display:none;background:#fdf0ef;border:1px solid rgba(192,57,43,.2);border-radius:var(--radius-md);padding:12px 14px;margin-bottom:16px;font-size:13px;color:var(--clr-error);">
            ⚠️ <strong>Unusual reading.</strong> The entered distance differs significantly from the expected trip distance (~230 km). Please double-check the odometer before confirming.
        </div>
        <div id="odometerOk" style="display:none;background:#edf7f1;border:1px solid rgba(39,118,74,.15);border-radius:var(--radius-md);padding:12px 14px;margin-bottom:16px;font-size:13px;color:var(--clr-success);">
            ✓ Reading looks good. Distance: <strong id="calcDist">—</strong> km.
        </div>
        <form method="POST" action="<?= Helpers::url('/trips/1/complete') ?>">
            <input type="hidden" name="odometer_end_km" id="odometerHidden">
            <div style="display:flex;gap:10px;margin-top:8px;">
                <button type="submit" class="btn btn-solid" onclick="setOdometer()" style="flex:1;">Confirm & Complete</button>
                <button type="button" class="btn btn-outline" onclick="closeOdometerModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
function openOdometerModal() {
    document.getElementById('odometerModal').style.display = 'flex';
    document.getElementById('odometerInput').focus();
}
function closeOdometerModal() {
    document.getElementById('odometerModal').style.display = 'none';
    document.getElementById('odometerInput').value = '';
    document.getElementById('odometerWarning').style.display = 'none';
    document.getElementById('odometerOk').style.display = 'none';
}
function checkOdometer(val) {
    var start = 12450, expected = 230, tolerance = 100;
    var entered = parseFloat(val);
    if (isNaN(entered) || entered <= start) {
        document.getElementById('odometerWarning').style.display = 'none';
        document.getElementById('odometerOk').style.display = 'none';
        return;
    }
    var actual = entered - start;
    if (Math.abs(actual - expected) > tolerance) {
        document.getElementById('odometerWarning').style.display = 'block';
        document.getElementById('odometerOk').style.display = 'none';
    } else {
        document.getElementById('odometerWarning').style.display = 'none';
        document.getElementById('odometerOk').style.display = 'block';
        document.getElementById('calcDist').textContent = actual.toFixed(1);
    }
}
function setOdometer() {
    document.getElementById('odometerHidden').value = document.getElementById('odometerInput').value;
}
document.getElementById('odometerModal').addEventListener('click', function(e) {
    if (e.target === this) closeOdometerModal();
});

// GPS ping simulation
var times = ['10:44 AM','10:46 AM','10:48 AM','10:50 AM'];
var i = 0;
setInterval(function() {
    if (times[i]) { document.getElementById('lastPing').textContent = times[i]; i++; }
}, 8000);

// Map
(function () {
    var warehouse   = [14.680456, 121.028051];
    var vehiclePos  = [14.5820, 121.0450];
    var destination = [14.2, 121.15];
    var trail = [[14.680456,121.028051],[14.6580,121.0290],[14.6380,121.0320],[14.6120,121.0380],[14.5950,121.0420],[14.5820,121.0450]];

    var map = L.map('driverMap').setView([14.580, 121.045], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors' }).addTo(map);

    var whIcon = L.divIcon({ html: '<div style="background:#1a3a2a;color:#e8a245;width:30px;height:30px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:15px;box-shadow:0 2px 6px rgba(0,0,0,.3);">🏭</div>', className: '', iconSize:[30,30], iconAnchor:[15,15] });
    L.marker(warehouse, { icon: whIcon }).addTo(map).bindPopup('<strong>Warehouse</strong><br>Trip origin');

    var vehIcon = L.divIcon({ html: '<div style="background:#2d6045;color:#fff;width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 2px 10px rgba(0,0,0,.4);border:3px solid #e8a245;">🚐</div>', className: '', iconSize:[38,38], iconAnchor:[19,19] });
    L.marker(vehiclePos, { icon: vehIcon }).addTo(map).bindPopup('<strong>Your position</strong><br>ABC-1234 Toyota Hi-Ace<br>Speed: 62 km/h').openPopup();

    var destIcon = L.divIcon({ html: '<div style="background:#c0392b;color:#fff;width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;box-shadow:0 2px 6px rgba(0,0,0,.3);">📍</div>', className: '', iconSize:[30,30], iconAnchor:[15,15] });
    L.marker(destination, { icon: destIcon }).addTo(map).bindPopup('<strong>Laguna Construction Site</strong><br>Current trip destination');

    L.polyline(trail, { color: '#2d6045', weight: 3, opacity: 0.7, dashArray: '6 4' }).addTo(map);
})();
</script>
