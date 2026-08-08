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
 *  - Stops polling when trip_status becomes 'completed'
 *  - Appends new GPS rows to the table (newest on top)
 */

/* jshint esversion: 6 */
/* global google */

var gMap, vehicleMarker, trailPolyline;
var pollIntervalId = null;
var knownPointCount = 0; // how many GPS log table rows have been rendered

// ── Entry point — called by Google Maps API ───────────────────
function initMap() {
  var cfg = window.lvmsMap;
  var originPt = { lat: cfg.warehouseLat, lng: cfg.warehouseLng };

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

  // ── Start polling (or do one-shot if already completed) ───
  pollFeed(); // immediate first fetch
  if (cfg.tripStatus !== "completed") {
    pollIntervalId = setInterval(pollFeed, 10000);
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
      // Stop polling if the trip has now ended
      if (data.trip_status === "completed" && pollIntervalId) {
        clearInterval(pollIntervalId);
        pollIntervalId = null;
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

      // ── Pan to vehicle ────────────────────────────────
      gMap.panTo(latLng);

      // ── Update info bar ───────────────────────────────
      var pingTime = new Date(latest.logged_at.replace(" ", "T"));
      document.getElementById("infoLastPing").textContent =
        pingTime.toLocaleDateString() + " " + pingTime.toLocaleTimeString();

      document.getElementById("infoSpeed").textContent =
        latest.speed_kph !== null
          ? parseFloat(latest.speed_kph).toFixed(1) + " km/h"
          : "—";

      // ── Update GPS log table ──────────────────────────
      updateLogTable(points);
    })
    .catch(function (err) {
      console.warn("[LVMS live map] Feed error:", err.message);
    });
}

// ── Append new rows to the GPS log table ─────────────────────
function updateLogTable(points) {
  var tbody = document.getElementById("gpsLogBody");

  // Clear the placeholder row on first real data
  if (knownPointCount === 0 && points.length > 0) {
    tbody.innerHTML = "";
  }

  // Slice off only the rows we haven't rendered yet
  // points[0] = newest, so slice from the front
  var newPoints = points.slice(0, points.length - knownPointCount);
  knownPointCount = points.length;

  if (newPoints.length === 0) return;

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
}
