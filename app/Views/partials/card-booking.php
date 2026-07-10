<?php
$guestName = $guestName ?? '';
$stayDates = $stayDates ?? '';
$status = $status ?? '';
$room = $room ?? '';
$price = $price ?? '';
$meta = $meta ?? [];

$statusKey = strtolower(str_replace(' ', '_', $status));
$statusClasses = ['confirmed' => 'confirmed', 'checked_in' => 'checked_in', 'checked_out' => 'checked_out', 'cancelled' => 'cancelled', 'waitlisted' => 'waitlisted'];
$sc = $statusClasses[$statusKey] ?? 'pending';
?>
<article class="booking-card" tabindex="0">
  <div class="booking-card-body">
    <div class="booking-card-header">
      <div style="min-width:0; flex:1;">
        <span class="text-tiny" style="color:var(--neutral-400); display:block; margin-bottom:0.25rem;">Booking</span>
        <h4 class="booking-card-name"><?= htmlspecialchars($guestName) ?></h4>
        <p class="booking-card-dates"><?= htmlspecialchars($stayDates) ?></p>
      </div>
      <span class="status-badge <?= $sc ?>"><?= htmlspecialchars(ucfirst($status ?: 'Pending')) ?></span>
    </div>

    <div class="booking-card-pills">
      <?php if ($room): ?>
        <span class="pill"><?= htmlspecialchars($room) ?></span>
      <?php endif; ?>
      <?php if ($price): ?>
        <span class="pill"><?= htmlspecialchars($price) ?></span>
      <?php endif; ?>
      <?php if (!empty($meta['guests'])): ?>
        <span class="pill"><?= htmlspecialchars($meta['guests']) ?></span>
      <?php endif; ?>
    </div>
  </div>
</article>