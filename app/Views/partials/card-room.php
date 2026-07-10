<?php
$room = $room ?? [];
$property = $property ?? [];
$roomId = (int)($room['id'] ?? 0);
$roomNumber = htmlspecialchars($room['room_number'] ?? '');
$roomType = htmlspecialchars($room['room_type'] ?? '');
$baseRate = $room['base_rate'] ?? 0;
$occupancy = $room['occupancy'] ?? 1;
$bedCount = $room['bed_count'] ?? 1;
$amenities = $room['enabled_amenities'] ?? [];
$propertyId = (int)($property['id'] ?? 0);
$propertyName = htmlspecialchars($property['name'] ?? '');
$dateParams = '';
if (!empty($checkIn) && !empty($checkOut)) {
    $dateParams = '&check_in=' . urlencode($checkIn) . '&check_out=' . urlencode($checkOut) . '&guests=' . (int)($guestsCount ?? 0);
}
?>
<article class="room-card" tabindex="0">
  <div class="room-card-body">
    <div class="room-card-header">
      <span class="room-card-number">Room <?= $roomNumber ?></span>
      <span class="room-card-rate">$<?= htmlspecialchars(number_format($baseRate, 2)) ?> <span class="room-card-rate-unit">/ night</span></span>
    </div>

    <?php if ($propertyName && $propertyId): ?>
      <p class="text-small" style="color:var(--neutral-500); margin-bottom:var(--space-3);">
        <a href="index.php?route=property&property_id=<?= $propertyId ?>" style="color:var(--brand-500); font-weight:600;"><?= $propertyName ?></a>
      </p>
    <?php endif; ?>

    <h4 class="text-h4 mb-3"><?= $roomType ?></h4>

    <div class="feature-grid mb-4">
      <div class="feature-item">
        <div class="feature-icon" style="background:var(--brand-100); color:var(--brand-700); border-radius:var(--radius-sm); width:2rem; height:2rem; display:flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight:700;">G</div>
        <div>
          <span class="feature-label">Max Occupancy</span>
          <span class="feature-value"><?= $occupancy ?> guests</span>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-icon" style="background:var(--brand-100); color:var(--brand-700); border-radius:var(--radius-sm); width:2rem; height:2rem; display:flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight:700;">B</div>
        <div>
          <span class="feature-label">Bed Config</span>
          <span class="feature-value"><?= $bedCount ?> bed<?= $bedCount > 1 ? 's' : '' ?></span>
        </div>
      </div>
    </div>

    <?php if (!empty($amenities)): ?>
      <div class="mb-4">
        <span class="text-tiny" style="color:var(--neutral-400);">In-Room Amenities</span>
        <div class="flex flex-wrap gap-1 mt-2">
          <?php
          $shown = array_slice($amenities, 0, 4);
          foreach ($shown as $item):
            $label = htmlspecialchars($item['label'] ?? '');
          ?>
            <span class="amenity-tag"><?= $label ?></span>
          <?php endforeach;
          if (count($amenities) > 4): ?>
            <span class="amenity-tag">+<?= count($amenities) - 4 ?> more</span>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="room-card-actions">
      <a href="index.php?route=room&room_id=<?= $roomId ?>" class="btn btn-secondary btn-sm">Details</a>
      <a href="index.php?route=book&room_id=<?= $roomId ?>&property_id=<?= $propertyId ?><?= $dateParams ?>" class="btn btn-primary btn-sm">Book Now</a>
    </div>
  </div>
</article>