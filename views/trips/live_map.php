<?php
$isCompleted = $trip['trip_status'] === 'completed';
$badgeClass  = match($trip['trip_status']) {
    'in_progress' => 'badge-in-progress',
    'completed'   => 'badge-completed',
    'incident'    => 'badge-rejected',
    'cancelled'   => 'badge-cancelled',
    default       => 'badge-pending',
};
?>

<div class="page-header">
    <div class="page-header-left">
        <p>
            <?= Helpers::e($trip['driver_first_name'] . ' ' . $trip['driver_last_name']) ?> &mdash;
            <?= Helpers::e($trip['plate_number'] . ' ' . $trip['vehicle_brand'] . ' ' . $trip['vehicle_model']) ?> &mdash;
            <span class="badge <?= $badgeClass ?>"><?= ucwords(str_replace('_', ' ', $trip['trip_status'])) ?></span>
        </p>
    </div>
    <a href="<?= Helpers::url('/trips/' . (int) $trip['trip_id']) ?>" class="btn btn-outline">← Back to Trip</a>
</div>

<!-- Info bar — updated by live_map.js -->
<div style="display:flex; gap:24px; margin-bottom:20px; flex-wrap:wrap;">
    <div>
        <div class="detail-field-label">Destination</div>
        <div style="font-size:14px; font-weight:500;"><?= Helpers::e($trip['destination']) ?></div>
    </div>
    <div>
        <div class="detail-field-label">Last GPS Ping</div>
        <div id="infoLastPing" style="font-size:14px; font-weight:500;">
            <?= $isCompleted ? '— (trip ended)' : 'Loading…' ?>
        </div>
    </div>
    <div>
        <div class="detail-field-label">Speed</div>
        <div id="infoSpeed" style="font-size:14px; font-weight:500;">
            <?= $isCompleted ? '—' : 'Loading…' ?>
        </div>
    </div>
    <div>
        <div class="detail-field-label">Warehouse Origin</div>
        <div style="font-size:14px; font-weight:500;">
            <?= $warehouse_lat !== 0.0
                ? number_format($warehouse_lat, 6) . ', ' . number_format($warehouse_lng, 6)
                : 'Not configured' ?>
        </div>
    </div>
</div>

<!-- Google Map -->
<div class="card" style="overflow:hidden;">
    <div id="tripMap" style="width:100%; height:520px;"></div>
</div>

<!-- Recent GPS pings table — populated by live_map.js -->
<div style="margin-top:24px;">
    <div class="section-title" style="margin-bottom:12px;">Recent GPS Pings</div>
    <div class="card">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Latitude</th>
                        <th>Longitude</th>
                        <th>Speed (km/h)</th>
                        <th>Heading</th>
                        <th>Accuracy (m)</th>
                    </tr>
                </thead>
                <tbody id="gpsLogBody">
                    <tr>
                        <td colspan="6" class="td-muted" style="text-align:center; padding:16px;">
                            <?= $isCompleted ? 'Trip completed — GPS tracking ended.' : 'Waiting for GPS data…' ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Config object for live_map.js — PHP embeds all required values -->
<script>
window.lvmsMap = {
    tripId:       <?= (int) $trip['trip_id'] ?>,
    tripStatus:   '<?= $trip['trip_status'] ?>',
    warehouseLat: <?= json_encode($warehouse_lat) ?>,
    warehouseLng: <?= json_encode($warehouse_lng) ?>,
    destLat:      <?= $trip['destination_lat'] !== null ? json_encode((float) $trip['destination_lat']) : 'null' ?>,
    destLng:      <?= $trip['destination_lng'] !== null ? json_encode((float) $trip['destination_lng']) : 'null' ?>,
    plate:        <?= json_encode($trip['plate_number']) ?>,
    vehicleName:  <?= json_encode($trip['vehicle_brand'] . ' ' . $trip['vehicle_model']) ?>,
    driverName:   <?= json_encode($trip['driver_first_name'] . ' ' . $trip['driver_last_name']) ?>,
    destination:  <?= json_encode($trip['destination']) ?>,
    feedUrl:      '<?= Helpers::url('/gps/' . (int) $trip['trip_id'] . '/feed') ?>',
};
</script>

<!-- live_map.js must load before the Google Maps callback fires -->
<script src="<?= APP_BASE ?>/public/js/live_map.js"></script>
<script
    src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&callback=initMap"
    loading="async" defer>
</script>
