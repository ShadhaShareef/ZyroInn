<?php
$property = $property ?? [];
$propertyId = (int)($property['id'] ?? 0);
$name = htmlspecialchars($property['name'] ?? '');
$city = htmlspecialchars($property['city'] ?? '');
$state = htmlspecialchars($property['state'] ?? '');
$description = htmlspecialchars($property['description'] ?? '');
$startingRate = $property['starting_rate'] ?? 0;
$amenities = $property['enabled_amenities'] ?? [];
$ptypeLabels = ['resort' => 'Resort', 'homestay' => 'Homestay', 'lodge' => 'Lodge', 'budget' => 'Budget', 'luxury' => 'Luxury'];
$ptype = $property['property_type'] ?? '';
$propUrl = 'index.php?route=property&property_id=' . $propertyId;
?>
<article class="card reveal-item" tabindex="0">
  <div class="property-card-image">
    <span class="property-card-image-icon" style="font-size:1.5rem; font-weight:700; opacity:0.4; color:var(--brand-500);">H</span>
    <div class="overlay"></div>
    <div class="property-card-badges">
      <span class="pill" style="background:rgba(255,255,255,0.9); backdrop-filter:blur(4px);"><?= htmlspecialchars(ucfirst($ptypeLabels[$ptype] ?? 'Property')) ?></span>
      <span class="pill" style="background:rgba(255,255,255,0.9); backdrop-filter:blur(4px);"><?= $city ?>, <?= $state ?></span>
    </div>
  </div>
  <div class="property-card-body">
    <h4 class="property-card-title">
      <a href="<?= $propUrl ?>"><?= $name ?></a>
    </h4>
    <p class="property-card-desc"><?= $description ?></p>

    <div class="mt-4">
      <span class="text-tiny" style="color:var(--neutral-400);">Amenities</span>
      <div class="flex flex-wrap gap-1 mt-2">
        <?php
        $flatAmenities = [];
        if (!empty($amenities)) {
          $firstKey = key($amenities);
          if ($firstKey !== null && is_string($firstKey) && is_array(current($amenities))) {
            foreach ($amenities as $cat => $items) { foreach ($items as $item) { $flatAmenities[] = $item; } }
          } else { $flatAmenities = $amenities; }
          $shown = array_slice($flatAmenities, 0, 4);
          foreach ($shown as $item):
            $label = htmlspecialchars($item['label'] ?? '');
          ?>
            <span class="amenity-tag"><?= $label ?></span>
          <?php endforeach;
          if (count($flatAmenities) > 4): ?>
            <span class="amenity-tag">+<?= count($flatAmenities) - 4 ?> more</span>
          <?php endif;
        } ?>
      </div>
    </div>

    <div class="property-card-footer">
      <div>
        <span class="text-tiny" style="color:var(--neutral-400); display:block;">Starting from</span>
        <span class="property-card-price">$<?= htmlspecialchars(number_format($startingRate, 2)) ?> <span class="property-card-price-unit">/ night</span></span>
      </div>
      <a href="<?= $propUrl ?>" class="btn btn-primary btn-sm">View Rooms</a>
    </div>
  </div>
</article>