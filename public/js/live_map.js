/**
 * live_map.js — LVMS GPS Live Map
 *
 * Called as the Google Maps JavaScript API callback (callback=initMap).
 * Reads initial config from window.lvmsMap (embedded by live_map.php).
 *
 * Behaviour:
 *  - Places warehouse marker at origin and destination marker if coords exist
 *  - Polls /gps/{trip_id}/feed every 10 seconds
 *  - Updates vehicle marker and route trail on each poll
 *  - Pans to the vehicle on each poll ONLY while "Follow Vehicle" is on
 *    (default on; a manual drag turns it off automatically, and Recenter
 *    pans once on demand regardless of the toggle state)
 *  - Ticks the GPS status badge and "last ping" age once a second, derived
 *    from the server-computed age_seconds — never from a client Date parse,
 *    since the DB session's timezone and the browser's rarely match
 *  - Stops polling once trip_status reaches a terminal status
 *  - Appends new GPS rows to the table (newest on top), without losing
 *    rows once the 50-point feed window has been seen once
 */

/* jshint esversion: 6 */
/* global google */

var gMap, vehicleMarker, trailPolyline;
var pollIntervalId = null;
var tickIntervalId = null;
var lastRenderedLoggedAt = null; // newest logged_at string already in the table
var followVehicle = true; // "Follow Vehicle" toggle state — on by default
var isEnded = false;

// Age-ticker anchor: the age (seconds) reported by the last successful poll,
// plus the client timestamp it was captured at. Ticking is anchorAge +
// (now - anchorAt), so a stalled/failing poll makes the displayed age (and
// therefore the badge) keep climbing on its own instead of freezing.
var ageAnchorSeconds = null;
var ageAnchorAtMs = null;

var MAX_TABLE_ROWS = 200; // DOM safety cap — the feed itself is capped at 50

// GPS tier → badge class / label. Thresholds come from window.lvmsMap
// (sourced from config/constants.php); the class/label pairs mirror
// GPS_STATUS_BADGES / GPS_STATUS_LABELS in that same file.
function classifyAge(ageSeconds) {
  var cfg = window.lvmsMap;
  if (ageSeconds === null || ageSeconds === undefined) return null;
  if (ageSeconds <= cfg.gpsLiveMaxSeconds) {
    return { badge: "badge-approved", label: "Live" };
  }
  if (ageSeconds <= cfg.gpsDelayedMaxSeconds) {
    return { badge: "badge-pending", label: "Delayed" };
  }
  if (ageSeconds <= cfg.gpsStaleMaxSeconds) {
    return { badge: "badge-rejected", label: "Stale" };
  }
  return { badge: "badge-cancelled", label: "No Signal" };
}

function setGpsBadge(badgeClass, label) {
  var el = document.getElementById("gpsStatusBadge");
  el.className = "badge " + badgeClass;
  el.textContent = label;
}

// "just now" / "5 seconds ago" / "1 minute 30 seconds ago"
function formatDuration(totalSeconds) {
  totalSeconds = Math.max(0, Math.floor(totalSeconds));
  if (totalSeconds < 5) return "just now";

  var minutes = Math.floor(totalSeconds / 60);
  var seconds = totalSeconds % 60;
  var parts = [];
  if (minutes > 0) parts.push(minutes + (minutes === 1 ? " minute" : " minutes"));
  if (seconds > 0 || minutes === 0) {
    parts.push(seconds + (seconds === 1 ? " second" : " seconds"));
  }
  return parts.join(" ") + " ago";
}

// Runs once a second while the trip is active, independent of the 10s poll,
// so the badge can degrade (Live -> Delayed -> Stale -> No Signal) even if
// polling itself has stalled.
function tickAge() {
  if (isEnded || ageAnchorSeconds === null) return;

  var elapsedSeconds = (Date.now() - ageAnchorAtMs) / 1000;
  var currentAge = ageAnchorSeconds + elapsedSeconds;

  document.getElementById("infoLastPing").textContent = formatDuration(currentAge);

  var tier = classifyAge(currentAge);
  if (tier) setGpsBadge(tier.badge, tier.label);
}

function updateFollowUi() {
  var btn = document.getElementById("followToggle");
  var dot = document.getElementById("followDot");
  var label = document.getElementById("followLabel");

  btn.setAttribute("aria-pressed", String(followVehicle));
  if (followVehicle) {
    btn.classList.add("btn-solid");
    btn.classList.remove("btn-outline");
    dot.classList.remove("is-off");
    label.textContent = "Following Vehicle";
  } else {
    btn.classList.remove("btn-solid");
    btn.classList.add("btn-outline");
    dot.classList.add("is-off");
    label.textContent = "Follow Vehicle";
  }
}

// ── Entry point — called by Google Maps API ───────────────────
function initMap() {
  var cfg = window.lvmsMap;
  var originPt = { lat: cfg.warehouseLat, lng: cfg.warehouseLng };

  if (cfg.ageSeconds !== null) {
    ageAnchorSeconds = cfg.ageSeconds;
    ageAnchorAtMs = Date.now();
  }

  // Centre the map on the warehouse initially
  gMap = new google.maps.Map(document.getElementById("tripMap"), {
    zoom: 12,
    center: originPt,
    mapTypeId: "roadmap",
    mapTypeControl: true,
    streetViewControl: false,
    fullscreenControl: true,
  });

  // ── Warehouse marker (trip origin) ────────────────────────
  new google.maps.Marker({
    position: originPt,
    map: gMap,
    title: "Main Warehouse (Trip Origin)",
    zIndex: 10,
    icon: {
      path: google.maps.SymbolPath.CIRCLE,
      scale: 12,
      fillColor: "#1a3a2a",
      fillOpacity: 1,
      strokeColor: "#e8a245",
      strokeWeight: 3,
    },
  }).addListener("click", function () {
    new google.maps.InfoWindow({
      content: "<strong>Main Warehouse</strong><br>Trip origin",
    }).open(gMap, this);
  });

  // ── Destination marker (if reservation has coordinates) ───
  if (cfg.destLat !== null && cfg.destLng !== null) {
    new google.maps.Marker({
      position: { lat: cfg.destLat, lng: cfg.destLng },
      map: gMap,
      title: cfg.destination,
      zIndex: 10,
      icon: {
        path: google.maps.SymbolPath.CIRCLE,
        scale: 10,
        fillColor: "#c0392b",
        fillOpacity: 1,
        strokeColor: "#fff",
        strokeWeight: 2,
      },
    }).addListener("click", function () {
      new google.maps.InfoWindow({
        content: "<strong>" + cfg.destination + "</strong><br>Destination",
      }).open(gMap, this);
    });
  }

  // ── Vehicle marker (positioned on first poll) ─────────────
  vehicleMarker = new google.maps.Marker({
    map: gMap,
    title: cfg.plate + " — " + cfg.vehicleName,
    visible: false,
    zIndex: 20,
    icon: {
      path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
      scale: 7,
      fillColor: "#2d6045",
      fillOpacity: 1,
      strokeColor: "#e8a245",
      strokeWeight: 2,
      rotation: 0,
    },
  });

  // ── Route trail (polyline updated each poll) ───────────────
  trailPolyline = new google.maps.Polyline({
    map: gMap,
    strokeColor: "#2d6045",
    strokeWeight: 3,
    strokeOpacity: 0.7,
    geodesic: true,
  });

  // ── Follow Vehicle toggle + manual Recenter ───────────────
  document.getElementById("followToggle").addEventListener("click", function () {
    followVehicle = !followVehicle;
    updateFollowUi();
    if (followVehicle && vehicleMarker.getVisible()) {
      gMap.panTo(vehicleMarker.getPosition());
    }
  });
  document.getElementById("recenterBtn").addEventListener("click", function () {
    if (vehicleMarker.getVisible()) {
      gMap.panTo(vehicleMarker.getPosition());
    }
  });
  // Only a real user drag turns Follow off — panTo() from a poll does not
  // fire 'dragstart', so this never fights with the automatic pan.
  gMap.addListener("dragstart", function () {
    if (followVehicle) {
      followVehicle = false;
      updateFollowUi();
    }
  });

  // ── Start polling (or do one-shot if already completed) ───
  pollFeed(); // immediate first fetch
  if (cfg.tripTerminalStatuses.indexOf(cfg.tripStatus) === -1) {
    pollIntervalId = setInterval(pollFeed, 10000);
    tickIntervalId = setInterval(tickAge, 1000);
  } else {
    isEnded = true;
  }
}

// Smooth the trail with Chaikin's corner-cutting
function chaikinSmooth(points, iterations) {
  if (points.length < 3) return points;

  var smoothed = points;
  for (var iter = 0; iter < iterations; iter++) {
    var next = [smoothed[0]];
    for (var i = 0; i < smoothed.length - 1; i++) {
      var p0 = smoothed[i];
      var p1 = smoothed[i + 1];
      next.push({
        lat: 0.75 * p0.lat + 0.25 * p1.lat,
        lng: 0.75 * p0.lng + 0.25 * p1.lng,
      });
      next.push({
        lat: 0.25 * p0.lat + 0.75 * p1.lat,
        lng: 0.25 * p0.lng + 0.75 * p1.lng,
      });
    }
    next.push(smoothed[smoothed.length - 1]);
    smoothed = next;
  }
  return smoothed;
}

// ── Fetch GPS feed and update the map ────────────────────────
function pollFeed() {
  var cfg = window.lvmsMap;

  fetch(cfg.feedUrl)
    .then(function (response) {
      if (!response.ok) {
        throw new Error("Feed returned HTTP " + response.status);
      }
      return response.json();
    })
    .then(function (data) {
      // GPS status badge always reflects the server's tier for this poll —
      // the 1s ticker (tickAge) then advances it locally between polls.
      setGpsBadge(data.gps_badge, data.gps_label);

      if (data.age_seconds !== null) {
        ageAnchorSeconds = data.age_seconds;
        ageAnchorAtMs = Date.now();
        tickAge();
      } else {
        document.getElementById("infoLastPing").textContent = "Waiting for first ping…";
      }

      // Stop polling once the trip reaches ANY terminal status, not just
      // 'completed' — a cancelled trip was previously polled forever.
      if (cfg.tripTerminalStatuses.indexOf(data.trip_status) !== -1) {
        isEnded = true;
        if (pollIntervalId) {
          clearInterval(pollIntervalId);
          pollIntervalId = null;
        }
        if (tickIntervalId) {
          clearInterval(tickIntervalId);
          tickIntervalId = null;
        }
        document.getElementById("infoLastPing").textContent = "— (trip ended)";
        document.getElementById("infoSpeed").textContent = "— (trip ended)";
      }

      var points = data.points || [];
      if (points.length === 0) return;

      var latest = points[0]; // newest-first from the feed

      // ── Move vehicle marker ───────────────────────────
      var latLng = {
        lat: parseFloat(latest.latitude),
        lng: parseFloat(latest.longitude),
      };
      vehicleMarker.setPosition(latLng);
      vehicleMarker.setVisible(true);

      // Rotate arrow to match heading if available
      if (latest.heading_degrees !== null) {
        vehicleMarker.setIcon(
          Object.assign({}, vehicleMarker.getIcon(), {
            rotation: parseInt(latest.heading_degrees, 10),
          }),
        );
      }

      // ── Update route trail ────────────────────────────
      // Points are newest-first; reverse to get chronological order
      // for the polyline path. Prepend warehouse as the trip origin.
      var path = [{ lat: cfg.warehouseLat, lng: cfg.warehouseLng }];
      var chronological = points.slice().reverse();
      chronological.forEach(function (p) {
        path.push({
          lat: parseFloat(p.latitude),
          lng: parseFloat(p.longitude),
        });
      });
      trailPolyline.setPath(chaikinSmooth(path, 2));

      // ── Pan to vehicle — only while Follow Vehicle is on ──
      if (followVehicle) {
        gMap.panTo(latLng);
      }

      // ── Update info bar ───────────────────────────────
      if (!isEnded) {
        // Same "parse as browser-local" convention as the table's Time
        // column below — logged_at is Asia/Manila wall-clock with no
        // timezone suffix, so this is only exact when the viewer is also
        // on PH time. The relative "X ago" text above (from age_seconds)
        // is the trustworthy value; this is a supplementary absolute time.
        var pingTime = new Date(latest.logged_at.replace(" ", "T"));
        document.getElementById("infoLastPing").title =
          pingTime.toLocaleDateString() + " " + pingTime.toLocaleTimeString();

        document.getElementById("infoSpeed").textContent =
          latest.speed_kph !== null
            ? parseFloat(latest.speed_kph).toFixed(1) + " km/h"
            : "—";
      }

      // ── Update GPS log table ──────────────────────────
      updateLogTable(points);
    })
    .catch(function (err) {
      console.warn("[LVMS live map] Feed error:", err.message);
    });
}

// ── Append new rows to the GPS log table ─────────────────────
// The feed always returns only the latest 50 points (a moving window, not
// cumulative history), so new rows are identified by logged_at being newer
// than the newest row already rendered — not by array length, which stays
// pinned at 50 forever once a trip has more than 50 pings.
function updateLogTable(points) {
  var tbody = document.getElementById("gpsLogBody");
  if (points.length === 0) return;

  var newPoints;
  if (lastRenderedLoggedAt === null) {
    // First render — clear the placeholder row and show the initial window.
    tbody.innerHTML = "";
    newPoints = points;
  } else {
    // points is newest-first, so the new ones are a prefix of the array.
    newPoints = [];
    for (var i = 0; i < points.length; i++) {
      if (points[i].logged_at > lastRenderedLoggedAt) {
        newPoints.push(points[i]);
      } else {
        break;
      }
    }
  }

  if (newPoints.length === 0) return;
  lastRenderedLoggedAt = points[0].logged_at;

  var fragment = document.createDocumentFragment();
  newPoints.forEach(function (p) {
    var time = new Date(p.logged_at.replace(" ", "T"));
    var timeStr = time.toLocaleTimeString();
    var tr = document.createElement("tr");

    tr.innerHTML =
      "<td>" +
      timeStr +
      "</td>" +
      "<td>" +
      parseFloat(p.latitude).toFixed(8) +
      "</td>" +
      "<td>" +
      parseFloat(p.longitude).toFixed(8) +
      "</td>" +
      "<td>" +
      (p.speed_kph !== null ? parseFloat(p.speed_kph).toFixed(1) : "—") +
      "</td>" +
      "<td>" +
      (p.heading_degrees !== null
        ? parseInt(p.heading_degrees, 10) + "°"
        : "—") +
      "</td>" +
      "<td>" +
      (p.accuracy_meters !== null
        ? parseFloat(p.accuracy_meters).toFixed(1)
        : "—") +
      "</td>";

    fragment.appendChild(tr);
  });

  // Prepend so newest is always at top
  tbody.insertBefore(fragment, tbody.firstChild);

  // DOM safety cap — the feed window is 50, but this table accumulates
  // across many polls, so a long-running trip needs a ceiling.
  while (tbody.children.length > MAX_TABLE_ROWS) {
    tbody.removeChild(tbody.lastElementChild);
  }

  var countEl = document.getElementById("gpsPingCount");
  if (countEl) countEl.textContent = String(tbody.children.length);
}
