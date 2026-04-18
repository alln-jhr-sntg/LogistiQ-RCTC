<div class="page-header"><div class="page-header-left"><h2><?= isset($project) && $project ? 'Edit Project' : 'New Project' ?></h2></div><a href="<?= Helpers::url('/projects') ?>" class="btn btn-outline">← Back</a></div>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<form method="POST" action="<?= isset($project) && $project ? Helpers::url('/projects/1/edit') : Helpers::url('/projects/create') ?>"><div class="form-card">
<div class="form-section-title">Project Details</div>
<div class="form-row"><div class="form-group"><label class="form-label">Project Name <span class="required">*</span></label><input type="text" class="form-input" name="project_name" required></div><div class="form-group"><label class="form-label">Project Code</label><input type="text" class="form-input" name="project_code" placeholder="e.g. RMX-2025-001"></div></div>
<div class="form-group"><label class="form-label">Company <span class="required">*</span></label><select class="form-select" name="company_id" required><option value="">— Select Company —</option><option value="1">Remix Construction and Trading Corporation</option><option value="2">Ideal Home</option><option value="3">TenBuild</option></select></div>
<div class="form-row"><div class="form-group"><label class="form-label">Start Date</label><input type="date" class="form-input" name="start_date"></div><div class="form-group"><label class="form-label">End Date</label><input type="date" class="form-input" name="end_date"></div></div>
<div class="form-section-title">Project Site Location</div>
<div class="form-group"><label class="form-label">Location Name</label><input type="text" class="form-input" name="location" placeholder="e.g. Ortigas, Pasig City"><p class="form-hint">Type the name then pin the exact location on the map below.</p></div>
<div class="form-row"><div class="form-group"><label class="form-label">Site Latitude</label><input type="text" class="form-input" name="location_lat" id="siteLat" placeholder="Click map to set" readonly style="background:var(--clr-surface-2);"></div><div class="form-group"><label class="form-label">Site Longitude</label><input type="text" class="form-input" name="location_lng" id="siteLng" placeholder="Click map to set" readonly style="background:var(--clr-surface-2);"></div></div>
<div style="margin-bottom:8px;"><div style="font-size:13px;color:var(--clr-text-3);margin-bottom:8px;">📍 Click anywhere on the map to pin the project site.</div><div id="projectMap" style="height:320px;border-radius:var(--radius-md);border:1.5px solid var(--clr-border);overflow:hidden;"></div></div>
<div class="form-group"><label style="display:flex;align-items:center;gap:8px;font-size:14px;color:var(--clr-text-2);cursor:pointer;"><input type="checkbox" name="is_active" value="1" checked> Project is active</label></div>
<div class="form-actions"><button type="submit" class="btn btn-solid"><?= isset($project) && $project ? 'Save Changes' : 'Create Project' ?></button><a href="<?= Helpers::url('/projects') ?>" class="btn btn-outline">Cancel</a></div>
</div></form>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function(){
    var warehouse=[14.680456,121.028051];
    var map=L.map('projectMap').setView(warehouse,11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap contributors'}).addTo(map);
    var whIcon=L.divIcon({html:'<div style="background:#1a3a2a;color:#e8a245;width:30px;height:30px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:15px;">🏭</div>',className:'',iconSize:[30,30],iconAnchor:[15,15]});
    L.marker(warehouse,{icon:whIcon,interactive:false}).addTo(map).bindTooltip('Warehouse');
    var siteMarker=null;
    var siteIcon=L.divIcon({html:'<div style="background:#1565a0;color:#fff;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;border:2px solid #fff;">🏗️</div>',className:'',iconSize:[32,32],iconAnchor:[16,16]});
    function setCoords(lat,lng){document.getElementById('siteLat').value=lat.toFixed(8);document.getElementById('siteLng').value=lng.toFixed(8);}
    function placeMarker(latlng){if(siteMarker){siteMarker.setLatLng(latlng);}else{siteMarker=L.marker(latlng,{icon:siteIcon,draggable:true}).addTo(map);siteMarker.on('dragend',function(e){var p=e.target.getLatLng();setCoords(p.lat,p.lng);});}setCoords(latlng.lat,latlng.lng);}
    map.on('click',function(e){placeMarker(e.latlng);});
})();
</script>