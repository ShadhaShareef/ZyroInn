<?php
$booking = $booking ?? [];
$groupedServices = $groupedServices ?? [];
$bookingSuccess = $bookingSuccess ?? null;
$bookingError = $bookingError ?? null;
$services = $services ?? [];
?>
<?php
$title = 'Book Services - ZyroInn';
$badge = 'Services';
include __DIR__ . '/../partials/guest-header.php';
?>

    <main class="main-content-narrow">

      <section class="card">
        <div class="p-4">
          <p class="text-tiny" style="color:var(--neutral-400);">Booking at</p>
          <h2 class="text-h3 mt-1"><?= htmlspecialchars($booking['property_name'] ?? '') ?></h2>
          <div class="flex items-center gap-4 mt-2">
            <span class="text-small" style="color:var(--neutral-500);">Room <?= htmlspecialchars($booking['room_number'] ?? '') ?></span>
            <span class="text-small" style="color:var(--neutral-500);"><?= date('M d', strtotime($booking['check_in_date'])) ?> – <?= date('M d', strtotime($booking['check_out_date'])) ?></span>
          </div>
        </div>
      </section>

      <?php if ($bookingSuccess): ?>
        <div class="alert alert-success"><?= $bookingSuccess ?></div>
      <?php endif; ?>

      <?php if ($bookingError): ?>
        <div class="alert alert-error"><?= htmlspecialchars($bookingError) ?></div>
      <?php endif; ?>

      <?php if (empty($groupedServices)): ?>
        <div class="empty-state">
          <div class="empty-state-icon"></div>
          <h3 class="empty-state-title">No Services Available</h3>
          <p class="empty-state-text">This property has not added any bookable services yet.</p>
        </div>
      <?php else:
        $categoryLabels = [
          'starter' => 'Starters', 'main_course' => 'Main Course', 'dessert' => 'Desserts', 'beverage' => 'Beverages',
          'spa' => 'Spa & Massage', 'wellness' => 'Wellness', 'fitness' => 'Fitness', 'activity' => 'Activities', 'transport' => 'Transport'
        ];
        $fnbCategories = ['starter', 'main_course', 'dessert', 'beverage'];
      ?>
        <?php foreach ($groupedServices as $category => $catServices): ?>
          <section class="space-y-3">
            <h3 class="text-h3 mb-0">
              <span class="flex items-center gap-2">
                <?= $categoryLabels[$category] ?? ucfirst($category) ?>
              </span>
            </h3>
            <div class="space-y-3">
              <?php foreach ($catServices as $svc): ?>
                <div class="service-card" x-data="{ open: false }">
                  <div class="service-card-body">
                    <div class="service-card-row">
                      <div style="flex:1; min-width:0; padding-right:var(--space-3);">
                        <h4 class="service-card-name"><?= htmlspecialchars($svc['name']) ?></h4>
                        <p class="service-card-desc"><?= htmlspecialchars($svc['description'] ?? '') ?></p>
                        <div class="service-card-meta">
                          <span class="service-card-price">$<?= number_format($svc['price'], 2) ?></span>
                          <?php if (!empty($svc['duration_minutes'])): ?><span><?= (int)$svc['duration_minutes'] ?> min</span><?php endif; ?>
                          <?php if (!empty($svc['max_capacity']) && (int)$svc['max_capacity'] > 1): ?><span>Up to <?= (int)$svc['max_capacity'] ?> guests</span><?php endif; ?>
                        </div>
                      </div>
                      <button @click="open = !open" class="btn btn-primary btn-sm">Book</button>
                    </div>

                    <form x-show="open" x-cloak method="POST" action="index.php?route=services&booking_id=<?= (int)$booking['id'] ?>" style="margin-top:var(--space-4); padding-top:var(--space-4); border-top:1px solid var(--neutral-100);">
                      <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
                      <input type="hidden" name="service_id" value="<?= (int)$svc['id'] ?>">
                      <?php $isFnb = in_array($category, $fnbCategories); ?>
                      <?php if (!$isFnb): ?>
                      <div class="mb-3">
                        <label class="input-label">Date & Time</label>
                        <input type="datetime-local" name="scheduled_at" required class="input">
                      </div>
                      <?php endif; ?>
                      <?php if (!empty($svc['max_capacity']) && (int)$svc['max_capacity'] > 1): ?>
                        <div class="mb-3">
                          <label class="input-label">Guests</label>
                          <input type="number" name="quantity" min="1" max="<?= (int)$svc['max_capacity'] ?>" value="1" class="input">
                        </div>
                      <?php else: ?>
                        <input type="hidden" name="quantity" value="1">
                      <?php endif; ?>
                      <div class="mb-3">
                        <label class="input-label">Notes (optional)</label>
                        <textarea name="notes" rows="2" placeholder="<?= $isFnb ? 'e.g. Allergies, preferences...' : 'e.g. Any special requests...' ?>" class="input"></textarea>
                      </div>
                      <button type="submit" class="btn btn-primary btn-full"><?= $isFnb ? 'Add to Order' : 'Confirm Booking' ?> — $<?= number_format($svc['price'], 2) ?></button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endforeach; ?>
      <?php endif; ?>

      <div class="text-center pt-2">
        <a href="index.php?route=bookings" class="btn btn-ghost btn-sm">← Back to My Bookings</a>
      </div>

    </main>

    <?php include __DIR__ . '/../partials/site-footer.php'; ?>
    <?php
    $items = $navItems ?? [];
    include __DIR__ . '/../partials/bottom-tab-bar.php';
    ?>
    <?php
    $label = 'Back to My Bookings';
    $href = 'index.php?route=bookings';
    include __DIR__ . '/../partials/sticky-action-bar.php';
    ?>
<?php include __DIR__ . '/../partials/guest-footer.php'; ?>