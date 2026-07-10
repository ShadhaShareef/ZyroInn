<?php
$location = $location ?? '';
$properties = $properties ?? [];
$googleMapsApiKey = $googleMapsApiKey ?? '';
$title = 'Search Boutique Stays' . (!empty($location) ? ' in ' . htmlspecialchars($location) : '') . ' - ZyroInn';
$metaDescription = (empty($location) ? 'Search and compare boutique hotels' : 'Find boutique stays in ' . htmlspecialchars($location)) . ' on ZyroInn. Filter by price, amenities, and room type.';
$badge = 'Search';
include __DIR__ . '/../partials/guest-header.php';
?>

    <main class="main-content">

      <div style="display:flex; flex-direction:column; gap:var(--space-4);">
        <div class="search-toolbar">
          <div>
            <p class="section-subtitle mb-0">Search Results</p>
            <h1 class="section-title mb-0"><?= !empty($location) ? 'Boutique Hotels in ' . htmlspecialchars($location) : 'Browse All Boutique Hotels' ?></h1>
          </div>
          <div class="search-toolbar-right">
            <button type="button" id="map-view-toggle" onclick="window.toggleMapView()" class="btn btn-ghost btn-sm">
              <span id="map-view-label">Map View</span>
            </button>
            <select id="sort-select" onchange="window.applySort(this.value)" class="input" style="width:auto; min-width:130px;">
              <option value="recommended" <?= ($sort ?? 'recommended') === 'recommended' ? 'selected' : '' ?>>Recommended</option>
              <option value="price_asc" <?= ($sort ?? 'recommended') === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
              <option value="price_desc" <?= ($sort ?? 'recommended') === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
              <option value="occupancy_desc" <?= ($sort ?? 'recommended') === 'occupancy_desc' ? 'selected' : '' ?>>Occupancy: High to Low</option>
            </select>
            <?php include __DIR__ . '/../partials/search-filter-sheet.php'; ?>
          </div>
        </div>

        <div id="map-container" class="hidden" style="height:400px; border-radius:var(--radius-lg); overflow:hidden; border:1px solid var(--neutral-200); box-shadow:var(--shadow-card);">
          <div id="map" class="w-full h-full"></div>
        </div>

        <div id="properties-container" style="display:flex; flex-direction:column; gap:var(--space-8);">
          <?php if (empty($properties)): ?>
            <div class="empty-state">
              <div style="font-size:2rem; opacity:0.3; margin-bottom:var(--space-2);">[ ]</div>
              <h3 class="empty-state-title">No Matching Stays Found</h3>
              <p class="empty-state-text">Try loosening your price filters or selecting fewer amenities.</p>
              <a href="index.php?route=home" class="btn btn-primary">Reset Search</a>
            </div>
          <?php else: ?>
            <?php foreach ($properties as $property): ?>
              <?php include __DIR__ . '/search_item_partial.php'; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <?php if (!empty($properties) && ($totalPages ?? 1) > ($page ?? 1)): ?>
          <div id="load-more-container" style="text-align:center;">
            <button type="button" id="load-more-btn" @click="loadMore()" x-data="loadMore()" class="btn btn-primary">
              <span x-show="loading" class="spinner" style="width:1rem; height:1rem; border-color:rgba(255,255,255,0.3); border-top-color:#fff;"></span>
              <span x-text="loading ? 'Loading...' : 'Load More Stays'"></span>
            </button>
          </div>
        <?php endif; ?>
      </div>

    </main>

    <?php include __DIR__ . '/../partials/site-footer.php'; ?>
    <?php $items = $navItems ?? []; include __DIR__ . '/../partials/bottom-tab-bar.php'; ?>
    <?php $label = 'Filter Stays'; $href = ''; $onClick = "window.openFilterSheet()"; include __DIR__ . '/../partials/sticky-action-bar.php'; ?>

  <script>
    window.GOOGLE_MAPS_API_KEY = '<?= htmlspecialchars($googleMapsApiKey) ?>';
    window.mapMarkers = <?= json_encode(array_map(function($p) {
      return ['id' => (int)$p['id'], 'name' => $p['name'], 'lat' => (float)($p['latitude'] ?? 0), 'lng' => (float)($p['longitude'] ?? 0), 'rate' => (float)($p['starting_rate'] ?? 0), 'city' => $p['city'] ?? ''];
    }, $mapProperties ?? [])) ?>;

    function loadGoogleMaps() {
      if (typeof google !== 'undefined' && google.maps) { initMap(); return; }
      if (window._gmapsLoading) return;
      window._gmapsLoading = true;
      const s = document.createElement('script');
      s.src = 'https://maps.googleapis.com/maps/api/js?key=' + window.GOOGLE_MAPS_API_KEY + '&callback=initMap&libraries=marker';
      s.async = true; s.defer = true;
      document.head.appendChild(s);
    }
    window.initMap = function() {
      const c = document.getElementById('map');
      if (!c || window._mapInitialized) return;
      window._mapInitialized = true;
      const bounds = new google.maps.LatLngBounds();
      const map = new google.maps.Map(c, { zoom: 4, center: {lat:39.8283,lng:-98.5795}, mapId:'zyroinn_map', styles:[{featureType:'poi',elementType:'labels',stylers:[{visibility:'off'}]}] });
      window._map = map;
      window._markers = [];
      window.mapMarkers.forEach(function(p) {
        if (!p.lat || !p.lng) return;
        const pos = {lat:p.lat,lng:p.lng};
        const m = new google.maps.Marker({position:pos,map:map,title:p.name,animation:google.maps.Animation.DROP});
        const info = `<div style="font-family:Inter,sans-serif;padding:4px;max-width:200px;"><strong style="color:#6C2BD9;">${p.name}</strong><br><span style="font-size:12px;color:#6E738A;">${p.city}</span><br><span style="font-size:14px;font-weight:700;color:#471C89;">From $${p.rate.toFixed(2)}</span><br><a href="index.php?route=property&property_id=${p.id}" style="font-size:11px;color:#6C2BD9;text-decoration:none;font-weight:600;">View Details →</a></div>`;
        const iw = new google.maps.InfoWindow({content:info});
        m.addListener('click',function(){iw.open(map,m);});
        bounds.extend(pos);
        window._markers.push(m);
      });
      if (window.mapMarkers.length > 0) { map.fitBounds(bounds); if (window.mapMarkers.length===1) map.setZoom(14); }
    };
    window.toggleMapView = function() {
      const c=document.getElementById('map-container'), pc=document.getElementById('properties-container'), lm=document.getElementById('load-more-container'), l=document.getElementById('map-view-label');
      const h=c.classList.contains('hidden');
      c.classList.toggle('hidden'); pc.classList.toggle('hidden'); if(lm)lm.classList.toggle('hidden');
      l.textContent=h?'List View':'Map View';
      if(h)loadGoogleMaps();
    };
    window.openFilterSheet = function() {
  const sheet = document.querySelector('[x-data^="bottomSheet"]');
  if (sheet && sheet.__x) { sheet.__x.$data.openSheet(); }
};
    window.applySort = function(v) { const p=new URLSearchParams(window.location.search);p.set('sort',v);window.location.search=p.toString(); };
    window.applyFilters = function() {
      const f=document.getElementById('filter-form'), fd=new FormData(f), p=new URLSearchParams();
      p.set('route','search');
      for(let[k,v]of fd.entries()){if(v)p.append(k,v);}
      const ls=document.getElementById('location-select');
      if(ls&&ls.value)p.set('location',ls.value);else{const u=new URLSearchParams(window.location.search);if(u.get('location'))p.set('location',u.get('location'));}
      const u=new URLSearchParams(window.location.search);if(u.get('sort'))p.set('sort',u.get('sort'));
      window.location.search=p.toString();
    };
    window.clearFilters = function() { window.location.href='index.php?route=search'; };
  </script>

<?php include __DIR__ . '/../partials/guest-footer.php'; ?>