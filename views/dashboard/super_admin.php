<div class="dashboard-grid">

    <!-- Stat cards -->
    <div class="stat-card">
        <div class="stat-label">Total Vehicles</div>
        <div class="stat-value"><?= Helpers::e((string)$stats['total_vehicles']) ?></div>
        <div class="stat-sub">Shared fleet across all companies</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Active Trips</div>
        <div class="stat-value"><?= Helpers::e((string)$stats['active_trips']) ?></div>
        <div class="stat-sub">Currently in progress</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Pending Reservations</div>
        <div class="stat-value"><?= Helpers::e((string)$stats['pending_res']) ?></div>
        <div class="stat-sub">Awaiting admin review</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Total Users</div>
        <div class="stat-value"><?= Helpers::e((string)$stats['total_users']) ?></div>
        <div class="stat-sub">Across all companies</div>
    </div>

</div>

<!-- Quick actions -->
<div class="section-title">Quick Actions</div>
<div class="quick-actions">
    <a href="<?= Helpers::url('/reservations/create') ?>" class="action-card">
        <div class="action-icon action-icon--green">
            <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
        </div>
        <div class="action-label">New Reservation</div>
    </a>
    <a href="<?= Helpers::url('/reservations') ?>" class="action-card">
        <div class="action-icon action-icon--green">
            <svg viewBox="0 0 24 24"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
        </div>
        <div class="action-label">Review Reservations</div>
    </a>
    <a href="<?= Helpers::url('/users') ?>" class="action-card">
        <div class="action-icon action-icon--purple">
            <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        </div>
        <div class="action-label">Manage Users</div>
    </a>
    <a href="<?= Helpers::url('/vehicles') ?>" class="action-card">
        <div class="action-icon action-icon--green">
            <svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z"/></svg>
        </div>
        <div class="action-label">Fleet Management</div>
    </a>
</div>

<!-- Fleet Overview Map -->
<div class="section-title" style="margin-top:32px;margin-bottom:12px;">Fleet Overview Map</div>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<div class="card" style="overflow:hidden;">
    <div id="fleetMap" style="width:100%;height:400px;"></div>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function() {
    var warehouse = [14.680456, 121.028051];

    var vehicles = [
        { plate: 'ABC-1234', name: 'Toyota Hi-Ace',    driver: 'Carlos Mendoza',   status: 'on_trip',   coords: [14.5820, 121.0450] },
        { plate: 'XYZ-5678', name: 'Mitsubishi L300',  driver: 'Roberto Cruz',      status: 'on_trip',   coords: [14.7120, 121.0650] },
        { plate: 'DEF-9012', name: 'Isuzu Elf',        driver: '—',                 status: 'maintenance', coords: null },
        { plate: 'GHI-3456', name: 'Ford Ranger',      driver: '—',                 status: 'available', coords: warehouse },
    ];

    var map = L.map('fleetMap').setView([14.660, 121.040], 11);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Warehouse
    var warehouseIcon = L.divIcon({
        html: '<div style="background:#1a3a2a;color:#e8a245;width:34px;height:34px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:17px;box-shadow:0 2px 6px rgba(0,0,0,.35);">🏭</div>',
        className: '', iconSize: [34,34], iconAnchor: [17,17]
    });
    L.marker(warehouse, {icon: warehouseIcon})
     .addTo(map)
     .bindPopup('<strong>Main Warehouse</strong><br>Dispatch origin');

    // Vehicle markers
    var colors = { on_trip: '#2d6045', available: '#1565a0', maintenance: '#9a6c00' };
    var emojis = { on_trip: '🚐', available: '🚗', maintenance: '🔧' };

    vehicles.forEach(function(v) {
        if (!v.coords) return;
        var color = colors[v.status] || '#555';
        var emoji = emojis[v.status] || '🚗';
        var icon = L.divIcon({
            html: '<div style="background:' + color + ';color:#fff;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;box-shadow:0 2px 8px rgba(0,0,0,.35);border:2px solid rgba(255,255,255,.6);">' + emoji + '</div>',
            className: '', iconSize: [34,34], iconAnchor: [17,17]
        });
        var statusLabel = v.status === 'on_trip' ? 'On Trip' : v.status === 'available' ? 'Available' : 'Maintenance';
        L.marker(v.coords, {icon: icon})
         .addTo(map)
         .bindPopup('<strong>' + v.plate + '</strong> — ' + v.name + '<br>Driver: ' + v.driver + '<br>Status: ' + statusLabel);
    });
})();
</script>
