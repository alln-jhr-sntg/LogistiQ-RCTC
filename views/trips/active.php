<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<!-- Trip header strip -->
<div style="background:var(--clr-primary);border-radius:var(--radius-lg);padding:18px 22px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <div style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:rgba(255,255,255,.5);margin-bottom:4px;">Active Trip</div>
        <div style="font-size:18px;font-weight:600;color:#fff;">RES-2025-00003 &mdash; Laguna Construction Site</div>
        <div style="font-size:13px;color:rgba(255,255,255,.55);margin-top:2px;">ABC-1234 Toyota Hi-Ace &mdash; Departed 06:14 AM</div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
        <span class="badge badge-in-progress" style="font-size:12px;padding:5px 12px;">In Progress</span>
        <a href="<?= Helpers::url('/trips/1') ?>" class="btn btn-outline" style="color:rgba(255,255,255,.7);border-color:rgba(255,255,255,.2);">Details</a>
        <a href="<?= Helpers::url('/dashboard/driver') ?>" class="btn btn-outline" style="color:rgba(255,255,255,.7);border-color:rgba(255,255,255,.2);">Dashboard</a>
    </div>
</div>

<!-- GPS status bar -->
<div style="display:flex;align-items:center;gap:20px;margin-bottom:14px;font-size:13px;color:var(--clr-text-3);">
    <span>● <strong style="color:var(--clr-success);">GPS Active</strong></span>
    <span>Last ping: <span id="lastPing">10:42 AM</span></span>
    <span>Speed: 62 km/h</span>
</div>

<!-- Map -->
<div class="card" style="overflow:hidden;">
    <div id="activeMap" style="height:calc(100vh - 280px);min-height:400px;"></div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    var warehouse   = [14.680456, 121.028051];
    var vehiclePos  = [14.5820, 121.0450];
    var destination = [14.2, 121.15];
    var trail = [[14.680456,121.028051],[14.6580,121.0290],[14.6380,121.0320],[14.6120,121.0380],[14.5950,121.0420],[14.5820,121.0450]];

    var map = L.map('activeMap').setView([14.580, 121.045], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors' }).addTo(map);

    // Warehouse
    var whIcon = L.divIcon({ html: '<div style="background:#1a3a2a;color:#e8a245;width:30px;height:30px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:15px;box-shadow:0 2px 6px rgba(0,0,0,.3);">🏭</div>', className: '', iconSize:[30,30], iconAnchor:[15,15] });
    L.marker(warehouse, { icon: whIcon }).addTo(map).bindPopup('<strong>Warehouse</strong><br>Trip origin');

    // Vehicle
    var vehIcon = L.divIcon({ html: '<div style="background:#2d6045;color:#fff;width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 2px 10px rgba(0,0,0,.4);border:3px solid #e8a245;">🚐</div>', className: '', iconSize:[38,38], iconAnchor:[19,19] });
    L.marker(vehiclePos, { icon: vehIcon }).addTo(map).bindPopup('<strong>Your vehicle</strong><br>ABC-1234 Toyota Hi-Ace').openPopup();

    // Destination
    var destIcon = L.divIcon({ html: '<div style="background:#c0392b;color:#fff;width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;box-shadow:0 2px 6px rgba(0,0,0,.3);">📍</div>', className: '', iconSize:[30,30], iconAnchor:[15,15] });
    L.marker(destination, { icon: destIcon }).addTo(map).bindPopup('<strong>Laguna Construction Site</strong><br>Destination');

    // Trail
    L.polyline(trail, { color: '#2d6045', weight: 3, opacity: 0.7, dashArray: '6 4' }).addTo(map);

    // Mock GPS ping updates
    var times = ['10:44 AM','10:46 AM','10:48 AM','10:50 AM'];
    var i = 0;
    setInterval(function() {
        if (times[i]) {
            document.getElementById('lastPing').textContent = times[i]; i++;
        }
    }, 8000);
})();
</script>
