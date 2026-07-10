<?php
$room = $room ?? [];
$property = $property ?? [];
$room = array_merge([
  'room_number' => '', 'room_type' => 'Comfort Room', 'description' => 'No additional details provided.',
  'base_rate' => 0, 'occupancy' => 1, 'bed_count' => 1, 'ac' => 0, 'enabled_amenities' => []
], $room);
$property = array_merge([ 'name' => 'Boutique Property', 'id' => 0 ], $property);
?>
<?php
$title = htmlspecialchars($room['room_type'] ?? 'Room') . ' at ' . htmlspecialchars($property['name'] ?? 'Boutique Property') . ' - ZyroInn';
$metaDescription = htmlspecialchars('Book ' . ($room['room_type'] ?? 'this room') . ' at ' . ($property['name'] ?? 'ZyroInn') . '. ' . ($room['description'] ?: 'View amenities, rates, and availability.'));
$badge = 'Room Details';
include __DIR__ . '/../partials/guest-header.php';
?>

    <main class="main-content" style="max-width: 48rem;">

      <section class="card" style="overflow: hidden; padding: 0;">
        <div style="height: 14rem; background: linear-gradient(135deg, var(--brand-100), var(--neutral-50)); display: flex; align-items: center; justify-content: center;">
          <span style="font-size: 4rem; opacity: 0.3; font-weight:700; color:var(--brand-300);">B</span>
          <div style="position: absolute; top: 0.75rem; left: 0.75rem;">
            <span class="pill" style="background: rgba(255,255,255,0.9);">Room <?= htmlspecialchars($room['room_number']) ?></span>
          </div>
        </div>
      </section>

      <section class="card">
        <div style="padding: var(--space-6);">
          <div class="flex items-center justify-between" style="margin-bottom: var(--space-4);">
            <div class="flex items-center gap-2">
              <span class="pill pill-brand"><?= $room['ac'] ? 'AC' : 'Non-AC' ?></span>
            </div>
            <span class="text-h3" style="color: var(--brand-600); margin: 0;">$<?= htmlspecialchars(number_format($room['base_rate'], 2)) ?> <span class="text-small" style="color: var(--neutral-500);">/ night</span></span>
          </div>

          <h1 class="text-h2" style="margin-bottom: var(--space-2);"><?= htmlspecialchars($room['room_type']) ?></h1>
          <p class="text-small" style="color: var(--neutral-500);"><span style="display:inline-flex;align-items:center;justify-content:center;width:1.25rem;height:1.25rem;border-radius:9999px;background:var(--brand-100);color:var(--brand-700);font-weight:700;font-size:0.65rem;margin-right:0.25rem;">H</span> Inside <a href="index.php?route=property&property_id=<?= (int)$property['id'] ?>" style="color: var(--brand-500); font-weight: 600;"><?= htmlspecialchars($property['name']) ?></a></p>

          <hr class="divider">

          <div class="feature-grid">
            <div class="feature-item">
              <span class="feature-icon" style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;border-radius:9999px;background:var(--brand-100);color:var(--brand-700);font-weight:700;font-size:0.8rem;">G</span>
              <div>
                <span class="feature-label">Max Occupancy</span>
                <span class="feature-value"><?= $room['occupancy'] ?> Guests</span>
              </div>
            </div>
            <div class="feature-item">
              <span class="feature-icon" style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;border-radius:9999px;background:var(--brand-100);color:var(--brand-700);font-weight:700;font-size:0.8rem;">B</span>
              <div>
                <span class="feature-label">Bed Config</span>
                <span class="feature-value"><?= $room['bed_count'] ?> Bed(s)</span>
              </div>
            </div>
          </div>

          <hr class="divider">

          <div>
            <h3 class="section-subtitle" style="margin-bottom: var(--space-2);">Description</h3>
            <p class="text-small" style="color: var(--neutral-500);"><?= htmlspecialchars($room['description'] ?: 'No additional details provided.') ?></p>
          </div>

          <hr class="divider">

          <div>
            <h3 class="section-subtitle" style="margin-bottom: var(--space-3);">Room Amenities</h3>
            <?php
            $amenities = $room['enabled_amenities'];
            if (!empty($amenities)):
                include __DIR__ . '/../partials/amenity-badges.php';
            else:
            ?>
              <p class="text-small" style="color: var(--neutral-400); font-style: italic;">No specific room features configured.</p>
            <?php
            endif;
            ?>
          </div>
        </div>
      </section>

      <section class="hidden md:block">
        <?php $dateParamsR = (!empty($checkIn) && !empty($checkOut)) ? '&check_in=' . urlencode($checkIn) . '&check_out=' . urlencode($checkOut) . '&guests=' . (int)($guestsCount ?? 0) : ''; ?>
        <a href="index.php?route=book&room_id=<?= (int)$room['id'] ?><?= $dateParamsR ?>" class="btn btn-primary btn-full">Book This Room</a>
      </section>

    </main>

    <?php include __DIR__ . '/../partials/site-footer.php'; ?>
    <?php
    $items = $navItems ?? [];
    include __DIR__ . '/../partials/bottom-tab-bar.php';
    ?>

    <?php
    $label = 'Book This Room';
    $dateParamsR = (!empty($checkIn) && !empty($checkOut)) ? '&check_in=' . urlencode($checkIn) . '&check_out=' . urlencode($checkOut) . '&guests=' . (int)($guestsCount ?? 0) : '';
    $href = "index.php?route=book&room_id=" . (int)$room['id'] . $dateParamsR;
    include __DIR__ . '/../partials/sticky-action-bar.php';
    ?>

<?php include __DIR__ . '/../partials/guest-footer.php'; ?>