/**
 * location_picker.js — LVMS shared location-picker map
 *
 * Called as the Google Maps JavaScript API callback (callback=initLocationPicker).
 * Reads config from window.lvmsLocationPicker (embedded per-view before this
 * script loads). Used by:
 *   - views/projects/create_edit.php
 *   - views/reservations/create.php
 *   - views/reservations/edit.php
 *   - views/reservations/detail.php (read-only)
 *
 * Config shape (window.lvmsLocationPicker):
 *   mapId          - id of the map container div
 *   latInputId     - id of the input that receives the picked latitude
 *   lngInputId     - id of the input that receives the picked longitude
 *   addressInputId - id of the text input to auto-fill via reverse geocoding
 *                    (optional — omit to skip reverse geocoding entirely)
 *   lat, lng       - existing coordinates to show on load, or null
 *   editable       - true = click/drag to set a marker; false = read-only
 *   defaultCenter  - {lat, lng} used when there is no existing marker
 *   defaultZoom    - zoom level used when there is no existing marker
 *   markerTitle    - title shown on the marker
 *   warehouseLat, warehouseLng - optional fixed reference marker (non-interactive)
 *
 * All functions are declared at top level (not nested inside initLocationPicker)
 * so sibling callbacks — e.g. a "project selected" change handler that needs to
 * reposition the marker — can call them. Declaring a shared function inside one
 * callback and calling it from another fails silently; see live_map.js history.
 */

/* jshint esversion: 6 */
/* global google */

var lpMap = null;
var lpMarker = null;
var lpGeocoder = null;
var lpConfig = null;

// ── Entry point — called by Google Maps API ───────────────────
function initLocationPicker() {
  lpConfig = window.lvmsLocationPicker;
  if (!lpConfig) return;

  var hasExisting = lpConfig.lat !== null && lpConfig.lng !== null;
  var center = hasExisting ? { lat: lpConfig.lat, lng: lpConfig.lng } : lpConfig.defaultCenter;
  var zoom = hasExisting ? 14 : lpConfig.defaultZoom;

  lpMap = new google.maps.Map(document.getElementById(lpConfig.mapId), {
    center: center,
    zoom: zoom,
    mapTypeId: "roadmap",
    mapTypeControl: false,
    streetViewControl: false,
    fullscreenControl: true,
    scrollwheel: !!lpConfig.editable,
  });

  lpGeocoder = new google.maps.Geocoder();

  if (lpConfig.warehouseLat != null && lpConfig.warehouseLng != null) {
    new google.maps.Marker({
      position: { lat: lpConfig.warehouseLat, lng: lpConfig.warehouseLng },
      map: lpMap,
      title: "Main Warehouse",
      clickable: false,
      zIndex: 5,
      icon: {
        path: google.maps.SymbolPath.CIRCLE,
        scale: 10,
        fillColor: "#1a3a2a",
        fillOpacity: 1,
        strokeColor: "#e8a245",
        strokeWeight: 3,
      },
    });
  }

  if (hasExisting) {
    placeMarker(lpConfig.lat, lpConfig.lng, false);
  }

  if (lpConfig.editable) {
    lpMap.addListener("click", function (e) {
      placeMarker(e.latLng.lat(), e.latLng.lng(), true);
    });
  }
}

// ── Drop or move the pin, sync hidden inputs, optionally reverse geocode ──
function placeMarker(lat, lng, shouldGeocode) {
  var pos = { lat: lat, lng: lng };

  if (lpMarker) {
    lpMarker.setPosition(pos);
  } else {
    lpMarker = new google.maps.Marker({
      position: pos,
      map: lpMap,
      draggable: !!lpConfig.editable,
      title: lpConfig.markerTitle || "Location",
    });
    if (lpConfig.editable) {
      lpMarker.addListener("dragend", function (e) {
        placeMarker(e.latLng.lat(), e.latLng.lng(), true);
      });
    }
  }

  setCoordFields(lat, lng);
  if (shouldGeocode) {
    reverseGeocode(lat, lng);
  }
}

// ── Fill the lat/lng hidden (or visible) inputs ────────────────
function setCoordFields(lat, lng) {
  if (lpConfig.latInputId) {
    var latEl = document.getElementById(lpConfig.latInputId);
    if (latEl) latEl.value = lat.toFixed(8);
  }
  if (lpConfig.lngInputId) {
    var lngEl = document.getElementById(lpConfig.lngInputId);
    if (lngEl) lngEl.value = lng.toFixed(8);
  }
}

// ── Reverse geocode a point into the address field ─────────────
// Failures (including rate limiting) are logged and otherwise ignored —
// the address field is left untouched and form submission is never blocked.
function reverseGeocode(lat, lng) {
  if (!lpConfig.addressInputId || !lpGeocoder) return;
  var addressEl = document.getElementById(lpConfig.addressInputId);
  if (!addressEl) return;

  lpGeocoder.geocode({ location: { lat: lat, lng: lng } }, function (results, status) {
    if (status !== "OK" || !results || !results[0]) {
      console.warn("[LVMS location picker] Reverse geocode failed:", status);
      return;
    }
    addressEl.value = results[0].formatted_address;
  });
}

// ── Programmatic repositioning (e.g. project select prefill) ───
// Does not reverse geocode — callers that already know a label for the
// point (a project's saved location name) pass it in themselves.
function setLocationPickerPosition(lat, lng, zoom) {
  if (!lpMap) return;
  placeMarker(lat, lng, false);
  lpMap.panTo({ lat: lat, lng: lng });
  if (zoom) lpMap.setZoom(zoom);
}
