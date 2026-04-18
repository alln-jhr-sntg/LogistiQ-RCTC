<div class="page-header">
    <div class="page-header-left"><h2>New Reservation</h2><p>Submit a vehicle request for your department</p></div>
    <a href="<?= Helpers::url('/reservations') ?>" class="btn btn-outline">← Back</a>
</div>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<form method="POST" action="<?= Helpers::url('/reservations/create') ?>">
<div class="form-card">
    <div class="form-section-title">Trip Details</div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Trip Purpose <span class="required">*</span></label>
            <select class="form-select" name="purpose_id" id="purposeSelect" required>
                <option value="">— Select Purpose —</option>
                <option value="1" data-max="2" data-requires-project="1">Site Visit</option>
                <option value="2" data-max="" data-requires-project="1">Material Delivery</option>
                <option value="3" data-max="" data-requires-project="0">Client Meeting</option>
                <option value="4" data-max="5" data-requires-project="1">Equipment Transport</option>
            </select>
            <div id="purposeHint" style="display:none;margin-top:8px;padding:8px 12px;background:#fef9e7;border:1px solid #f5d76e;border-radius:var(--radius-sm);font-size:13px;color:#7a5200;"></div>
        </div>
        <div class="form-group" id="projectField" style="display:none;">
            <label class="form-label">Project <span class="required">*</span></label>
            <select class="form-select" name="project_id">
                <option value="">— Select Project —</option>
                <option value="1">REMIX Tower Phase 1</option>
                <option value="2">Ideal Home Subdivision</option>
                <option value="3">TenBuild Commercial Hub</option>
            </select>
            <p class="form-hint">Required for this trip purpose</p>
        </div>
    </div>
    <div class="form-group">
        <label class="form-label">Destination Name <span class="required">*</span></label>
        <input type="text" class="form-input" name="destination" placeholder="e.g. Laguna Construction Site" required>
        <p class="form-hint">Origin is always the company warehouse. Pin the location on the map below.</p>
    </div>
    <div class="form-row">
        <div class="form-group"><label class="form-label">Destination Latitude</label><input type="text" class="form-input" name="destination_lat" id="destLat" placeholder="Click map to set" readonly style="background:var(--clr-surface-2);"></div>
        <div class="form-group"><label class="form-label">Destination Longitude</label><input type="text" class="form-input" name="destination_lng" id="destLng" placeholder="Click map to set" readonly style="background:var(--clr-surface-2);"></div>
    </div>
    <div style="margin-bottom:16px;">
        <div style="font-size:13px;color:var(--clr-text-3);margin-bottom:8px;">📍 Click anywhere on the map to pin the destination. Drag the marker to adjust.</div>
        <div id="destinationMap" style="height:320px;border-radius:var(--radius-md);border:1.5px solid var(--clr-border);overflow:hidden;"></div>
    </div>
    <div class="form-group"><label class="form-label">Trip Details</label><textarea class="form-textarea" name="trip_details" placeholder="Describe the purpose of this trip…"></textarea></div>
    <div class="form-row">
        <div class="form-group"><label class="form-label">Departure Date & Time <span class="required">*</span></label><input type="datetime-local" class="form-input" name="departure_datetime" required></div>
        <div class="form-group"><label class="form-label">Estimated Return <span class="required">*</span></label><input type="datetime-local" class="form-input" name="return_datetime" required></div>
    </div>
    <div class="form-section-title">Passengers & Cargo</div>
    <div class="form-row-3 form-row">
        <div class="form-group"><label class="form-label">Passenger Count <span class="required">*</span></label><input type="number" class="form-input" name="passenger_count" min="1" value="1" required></div>
        <div class="form-group"><label class="form-label">Cargo Weight (kg)</label><input type="number" class="form-input" name="cargo_weight_kg" min="0" step="0.01" value="0"></div>
        <div class="form-group"><label class="form-label">Cargo Description</label><input type="text" class="form-input" name="cargo_description" placeholder="e.g. Cement bags"></div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-solid">Submit Reservation</button>
        <a href="<?= Helpers::url('/reservations') ?>" class="btn btn-outline">Cancel</a>
    </div>
</div>
</form>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function(){
    var ps=document.getElementById('purposeSelect'),pf=document.getElementById('projectField'),hint=document.getElementById('purposeHint');
    function toggle(){
        var opt=ps.options[ps.selectedIndex],req=opt&&opt.getAttribute('data-requires-project')==='1',max=opt?opt.getAttribute('data-max'):'';
        pf.style.display=req?'block':'none';
        var sel=pf.querySelector('select');if(sel)sel.required=req;
        if(max){hint.style.display='block';hint.style.background='#fef9e7';hint.style.color='#7a5200';hint.innerHTML='⚠️ This purpose is limited to <strong>'+max+' reservation(s) per project</strong>.';}
        else if(ps.value){hint.style.display='block';hint.style.background='#edf7f1';hint.style.color='#1b5e35';hint.innerHTML='✓ No reservation limit for this purpose.';}
        else{hint.style.display='none';}
    }
    ps.addEventListener('change',toggle);toggle();
    var warehouse=[14.680456,121.028051];
    var map=L.map('destinationMap').setView(warehouse,12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap contributors'}).addTo(map);
    var whIcon=L.divIcon({html:'<div style="background:#1a3a2a;color:#e8a245;width:30px;height:30px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:15px;box-shadow:0 2px 6px rgba(0,0,0,.3);">🏭</div>',className:'',iconSize:[30,30],iconAnchor:[15,15]});
    L.marker(warehouse,{icon:whIcon,interactive:false}).addTo(map).bindTooltip('Warehouse (Origin)');
    var destMarker=null;
    var destIcon=L.divIcon({html:'<div style="background:#c0392b;color:#fff;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;box-shadow:0 2px 8px rgba(0,0,0,.35);border:2px solid #fff;">📍</div>',className:'',iconSize:[32,32],iconAnchor:[16,16]});
    function setCoords(lat,lng){document.getElementById('destLat').value=lat.toFixed(8);document.getElementById('destLng').value=lng.toFixed(8);}
    function placeMarker(latlng){if(destMarker){destMarker.setLatLng(latlng);}else{destMarker=L.marker(latlng,{icon:destIcon,draggable:true}).addTo(map);destMarker.on('dragend',function(e){var p=e.target.getLatLng();setCoords(p.lat,p.lng);});}setCoords(latlng.lat,latlng.lng);}
    map.on('click',function(e){placeMarker(e.latlng);});
})();
</script>