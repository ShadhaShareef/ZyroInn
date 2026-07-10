<?php
$amenities = $amenities ?? [];

$flatAmenities = [];
if (!empty($amenities)) {
    $firstKey = key($amenities);
    if ($firstKey !== null && is_string($firstKey) && is_array(current($amenities))) {
        foreach ($amenities as $category => $items) {
            foreach ($items as $item) {
                $flatAmenities[] = $item;
            }
        }
    } else {
        $flatAmenities = $amenities;
    }
}
?>

<?php if (!empty($flatAmenities)): ?>
  <div class="flex flex-wrap gap-1.5">
    <?php foreach ($flatAmenities as $item):
      if (isset($item['enabled']) && !$item['enabled']) continue;
      $label = htmlspecialchars($item['label'] ?? '');
      $icon = $item['icon'] ?? '';
    ?>
      <span class="amenity-tag amenity-tag-enabled">
        <?php if ($icon): ?><?= htmlspecialchars($icon) ?><?php endif; ?>
        <?= $label ?>
      </span>
    <?php endforeach; ?>
  </div>
<?php endif; ?>