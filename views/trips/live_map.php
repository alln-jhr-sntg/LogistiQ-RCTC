<div class="page-header">
    <div class="page-header-left">
        <h2>Live Map — RES-2025-00003</h2>
        <p>Carlos Mendoza &mdash; ABC-1234 Toyota Hi-Ace &mdash; <span class="badge badge-in-progress">In Progress</span></p>
    </div>
    <a href="<?= Helpers::url('/trips/1') ?>" class="btn btn-outline">← Back to Trip</a>
</div>

<div style="display:flex;gap:24px;margin-bottom:20px;flex-wrap:wrap;">
    <div><div class="detail-field-label">Destination</div><div style="font-size:14px;font-weight:500;">Laguna Construction Site</div></div>
    <div><div class="detail-field-label">Last GPS Ping</div><div style="font-size:14px;font-weight:500;">Dec 12, 2025 10:42 AM</div></div>
    <div><div class="detail-field-label">Speed</div><div style="font-size:14px;font-weight:500;">62 km/h</div></div>
    <div><div class="detail-field-label">Warehouse Origin</div><div style="font-size:14px;font-weight:500;">14.680456, 121.028051</div></div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<div class="card" style="overflow:hidden;">
    <div id="tripMap" style="width:100%;height:520px;"></div>
</div>

<div style="margin-top:24px;">
    <div class="section-title" style="margin-bottom:12px;">Recent GPS Pings</div>
    <div class="card">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>Time</th><th>Latitude</th><th>Longitude</th><th>Speed (km/h)</th><th>Heading</th><th>Accuracy (m)</th></tr>
                </thead>
                <tbody>
                    <tr><td>10:42 AM</td><td>14.58200000</td><td>121.04500000</td><td>62</td><td>185°</td><td>4.2</td></tr>
                    <tr><td>10:40 AM</td><td>14.59500000</td><td>121.04200000</td><td>55</td><td>182°</td><td>3.8</td></tr>
                    <tr><td>10:38 AM</td><td>14.61200000</td><td>121.03800000</td><td>70</td><td>178°</td><td>5.1</td></tr>
                    <tr><td>10:35 AM</td><td>14.63800000</td><td>121.03200000</td><td>45</td><td>190°</td><td>4.5</td></tr>
                    <tr><td>10:33 AM</td><td>14.65800000</td><td>121.02900000</td><td>30</td><td>185°</td><td>3.2</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function() {
    var warehouse = [14.680456, 121.028051];
    var vehicle   = [14.5820, 121.0450];
    var trail = [[14.680456,121.028051],[14.6580,121.0290],[14.6380,121.0320],[14.6120,121.0380],[14.5950,121.0420],[14.5820,121.0450]];

    var map = L.map('tripMap').setView([14.630, 121.036], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors' }).addTo(map);

    var whIcon = L.divIcon({ html: '<div style="background:#1a3a2a;color:#e8a245;width:32px;height:32px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:16px;box-shadow:0 2px 6px rgba(0,0,0,.3);">🏭</div>', className: '', iconSize: [32,32], iconAnchor: [16,16] });
    L.marker(warehouse, {icon: whIcon}).addTo(map).bindPopup('<strong>Main Warehouse</strong><br>Trip origin').openPopup();

    var vehIcon = L.divIcon({ html: '<div style="background:#2d6045;color:#fff;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 2px 8px rgba(0,0,0,.4);border:3px solid #e8a245;">🚐</div>', className: '', iconSize: [36,36], iconAnchor: [18,18] });
    L.marker(vehicle, {icon: vehIcon}).addTo(map).bindPopup('<strong>ABC-1234 — Toyota Hi-Ace</strong><br>Driver: Carlos Mendoza<br>Speed: 62 km/h');

    var destIcon = L.divIcon({ html: '<div style="background:#c0392b;color:#fff;width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;box-shadow:0 2px 6px rgba(0,0,0,.3);">📍</div>', className: '', iconSize: [30,30], iconAnchor: [15,15] });
    L.marker([14.2, 121.15], {icon: destIcon}).addTo(map).bindPopup('<strong>Laguna Construction Site</strong><br>Destination');

    L.polyline(trail, {color: '#2d6045', weight: 3, opacity: 0.7, dashArray: '6 4'}).addTo(map);
})();
</script>
