<?php
$badgeClass = match($trip['trip_status']) {
    'in_progress' => 'badge-in-progress',
    'completed'   => 'badge-completed',
    'incident'    => 'badge-rejected',
    'cancelled'   => 'badge-cancelled',
    default       => 'badge-pending',
};

// gps_status already accounts for terminal trip statuses (completed AND
// cancelled) via Helpers::gpsStatus() / TRIP_TERMINAL_STATUSES, so it's the
// single source of truth for "has tracking ended" — not a local re-check.
$isEnded = $gps_status['key'] === GPS_STATUS_ENDED;
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

<!-- Info bar — GPS Status and Last GPS Ping are ticked/updated by live_map.js -->
<div class="info-bar">
    <div>
        <div class="detail-field-label">Destination</div>
        <div class="detail-field-value"><?= Helpers::e($trip['destination']) ?></div>
    </div>
    <div>
        <div class="detail-field-label">GPS Status</div>
        <div class="detail-field-value">
            <span id="gpsStatusBadge" class="badge <?= $gps_status['badge'] ?>"><?= Helpers::e($gps_status['label']) ?></span>
        </div>
    </div>
    <div>
        <div class="detail-field-label">Last GPS Ping</div>
        <div id="infoLastPing" class="detail-field-value">
            <?php if ($isEnded): ?>
                — (trip ended)
            <?php elseif ($age_seconds === null): ?>
                Waiting for first ping…
            <?php else: ?>
                Loading…
            <?php endif; ?>
        </div>
    </div>
    <div>
        <div class="detail-field-label">Speed</div>
        <div id="infoSpeed" class="detail-field-value">
            <?= $isEnded ? '—' : 'Loading…' ?>
        </div>
    </div>
    <div>
        <div class="detail-field-label">Warehouse Origin</div>
        <div class="detail-field-value">
            <?php if ($warehouse_lat !== 0.0): ?>
                <?php if ($warehouse_address !== ''): ?>
                    <?= Helpers::e($warehouse_address) ?><br>
                <?php endif; ?>
                <?= number_format($warehouse_lat, 6) . ', ' . number_format($warehouse_lng, 6) ?>
            <?php else: ?>
                Not configured
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Map toolbar — Follow Vehicle toggle + manual Recenter -->
<div class="filter-bar">
    <button type="button" id="followToggle" class="btn btn-solid btn-sm" aria-pressed="true">
        <span class="follow-dot" id="followDot"></span>
        <span id="followLabel">Following Vehicle</span>
    </button>
    <div class="filter-bar-actions">
        <button type="button" id="recenterBtn" class="btn btn-outline btn-sm">Recenter</button>
    </div>
</div>

<!-- Google Map -->
<div id="tripMap" class="map-panel map-panel--live"></div>

<!-- Recent GPS pings table — populated by live_map.js. Collapsed by default,
     same pattern as the Disqualified Vehicles list in reservations/review.php,
     so it doesn't dominate the page under the map. -->
<details class="detail-card card--spaced">
    <summary class="detail-card-title">Recent GPS Pings (<span id="gpsPingCount">0</span>)</summary>
    <div class="table-wrap">
        <table class="data-table data-table--compact">
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
                    <td colspan="6" class="td-muted td-empty">
                        <?= $isEnded ? 'Trip ended — GPS tracking stopped.' : 'Waiting for GPS data…' ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</details>

<!-- Config object for live_map.js — PHP embeds all required values. GPS
     thresholds and terminal statuses come from config/constants.php so
     the client-side classifier can never drift from the server's. -->
<script>
window.lvmsMap = {
    tripId:              <?= (int) $trip['trip_id'] ?>,
    tripStatus:           '<?= $trip['trip_status'] ?>',
    tripTerminalStatuses: <?= json_encode(TRIP_TERMINAL_STATUSES) ?>,
    warehouseLat:         <?= json_encode($warehouse_lat) ?>,
    warehouseLng:         <?= json_encode($warehouse_lng) ?>,
    destLat:              <?= $trip['destination_lat'] !== null ? json_encode((float) $trip['destination_lat']) : 'null' ?>,
    destLng:              <?= $trip['destination_lng'] !== null ? json_encode((float) $trip['destination_lng']) : 'null' ?>,
    plate:                <?= json_encode($trip['plate_number']) ?>,
    vehicleName:          <?= json_encode($trip['vehicle_brand'] . ' ' . $trip['vehicle_model']) ?>,
    driverName:           <?= json_encode($trip['driver_first_name'] . ' ' . $trip['driver_last_name']) ?>,
    destination:          <?= json_encode($trip['destination']) ?>,
    feedUrl:              '<?= Helpers::url('/gps/' . (int) $trip['trip_id'] . '/feed') ?>',
    ageSeconds:           <?= $age_seconds !== null ? (int) $age_seconds : 'null' ?>,
    gpsLiveMaxSeconds:    <?= (int) GPS_LIVE_MAX_SECONDS ?>,
    gpsDelayedMaxSeconds: <?= (int) GPS_DELAYED_MAX_SECONDS ?>,
    gpsStaleMaxSeconds:   <?= (int) GPS_STALE_MAX_SECONDS ?>,
};
</script>

<!-- live_map.js must load before the Google Maps callback fires. Cache-busted
     like every other public/ asset — see Helpers::assetUrl() docblock. -->
<script src="<?= Helpers::assetUrl('/js/live_map.js') ?>"></script>
<script
    src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&callback=initMap"
    loading="async" defer>
</script>
