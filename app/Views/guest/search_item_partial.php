<?php
$propId = (int)$property['id'];
$name = htmlspecialchars($property['name'] ?? '');
$city = htmlspecialchars($property['city'] ?? '');
$state = htmlspecialchars($property['state'] ?? '');
$description = htmlspecialchars($property['description'] ?? '');
$startingRate = $property['starting_rate'] ?? (!empty($property['rooms']) ? $property['rooms'][0]['base_rate'] : 0);
$ptypeLabels = ['resort' => 'Resort', 'homestay' => 'Homestay', 'lodge' => 'Lodge', 'budget' => 'Budget', 'luxury' => 'Luxury'];
$ptype = $property['property_type'] ?? '';
$dateParams = '';
if (!empty($checkIn) && !empty($checkOut)) {
    $dateParams = '&check_in=' . urlencode($checkIn) . '&check_out=' . urlencode($checkOut) . '&guests=' . (int)($guestsCount ?? 0);
}
$propUrl = 'index.php?route=property&property_id=' . $propId . $dateParams;
$amenities = $property['enabled_amenities'] ?? [];
$flatAmenities = [];
if (!empty($amenities)) {
  $firstKey = key($amenities);
  if ($firstKey !== null && is_string($firstKey) && is_array(current($amenities))) {
    foreach ($amenities as $cat => $items) { foreach ($items as $item) { $flatAmenities[] = $item; } }
  } else { $flatAmenities = $amenities; }
}
?>
<article class="property-large" tabindex="0">
  <a href="<?= $propUrl ?>" class="property-large-image" style="background:linear-gradient(135deg,var(--brand-100),var(--brand-200)); display:flex; align-items:center; justify-content:center; font-size:2rem; font-weight:700; color:var(--brand-500); opacity:0.4;">
    H
  </a>
  <div class="property-large-body">
    <div style="display:flex; align-items:center; gap:var(--space-2); margin-bottom:var(--space-2);">
      <span class="pill pill-brand"><?= htmlspecialchars(ucfirst($ptypeLabels[$ptype] ?? 'Property')) ?></span>
      <span class="text-small" style="color:var(--neutral-400);"><?= $city ?>, <?= $state ?></span>
    </div>
    <h4 class="text-h4">
      <a href="<?= $propUrl ?>" style="color:var(--brand-900);"><?= $name ?></a>
    </h4>
    <p class="property-card-desc mt-2" style="margin-top:var(--space-2);"><?= $description ?></p>

    <?php if (!empty($flatAmenities)): ?>
    <div style="display:flex; flex-wrap:wrap; gap:var(--space-1); margin-top:var(--space-3);">
      <?php foreach (array_slice($flatAmenities, 0, 5) as $item):
        $label = htmlspecialchars($item['label'] ?? '');
      ?>
        <span class="amenity-tag"><?= $label ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div style="display:flex; align-items:center; justify-content:space-between; margin-top:var(--space-4); padding-top:var(--space-3); border-top:1px solid var(--neutral-100);">
      <div>
        <span class="property-large-price">$<?= number_format($startingRate, 2) ?> <span class="property-large-price-unit">/ night</span></span>
      </div>
      <a href="<?= $propUrl ?>" class="btn btn-primary btn-sm">View Details</a>
    </div>
  </div>
</article>