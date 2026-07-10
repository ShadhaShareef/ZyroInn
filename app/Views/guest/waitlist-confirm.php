<?php
$booking = $booking ?? [];
$addons = $addons ?? [];
$payments = $payments ?? [];
$upgradeSuggestions = $upgradeSuggestions ?? [];
$booking = array_merge([
  'id' => 0, 'status' => 'waitlisted', 'property_name' => 'ZyroInn Property',
  'address' => '', 'city' => '', 'state' => '', 'room_number' => '',
  'room_type' => 'Comfort Room', 'check_in_date' => '', 'check_out_date' => ''
], $booking);
?>
<?php
$title = 'Booking Waitlisted - ZyroInn';
$badge = 'Waitlist';
include __DIR__ . '/../partials/guest-header.php';
?>

    <main class="main-content-narrow">

      <section class="card text-center" style="border-color:var(--waitlist-color, #D48B2F);">
        <div class="p-6">
          <div style="width:4rem; height:4rem; background:var(--warning-bg); border:1px solid var(--warning); border-radius:9999px; display:flex; align-items:center; justify-content:center; margin:0 auto var(--space-4); color:var(--warning); font-size:1.5rem;">
            ⏳
          </div>
          <h2 class="text-h2 mb-3">Room Not Available</h2>
          <p class="text-small" style="color:var(--neutral-500); margin-bottom:var(--space-5);">
            That room is already booked for your selected dates. Your reservation has been placed on a
            <strong style="color:var(--warning);">waitlist</strong>. If the existing booking moves or cancels,
            you will be notified. Your payment has not been processed.
          </p>

          <div style="background:var(--neutral-50); border:1px solid var(--neutral-100); padding:var(--space-4); border-radius:var(--radius-sm);">
            <span class="text-tiny" style="color:var(--neutral-400); display:block; margin-bottom:0.25rem;">Booking Reference</span>
            <span style="font-weight:700; color:var(--warning); user-select:all;">#BKG-<?= str_pad($booking['id'], 5, '0', STR_PAD_LEFT) ?></span>
          </div>
        </div>
      </section>

      <section class="card">
        <div class="p-6">
          <h3 class="section-subtitle mb-4">Reservation Summary</h3>

          <div class="summary-rows">
            <div class="summary-row">
              <span>Property:</span>
              <span class="summary-row-value"><?= htmlspecialchars($booking['property_name']) ?></span>
            </div>
            <div class="summary-row">
              <span>Address:</span>
              <span class="summary-row-value" style="max-width:15rem;"><?= htmlspecialchars($booking['address'] . ', ' . $booking['city'] . ', ' . $booking['state']) ?></span>
            </div>
            <div class="summary-row">
              <span>Requested Room:</span>
              <span class="summary-row-value">Room <?= htmlspecialchars($booking['room_number']) ?> (<?= htmlspecialchars($booking['room_type']) ?>)</span>
            </div>
            <div class="summary-row">
              <span>Status:</span>
              <span class="status-badge waitlisted">WAITLISTED</span>
            </div>
            <div class="summary-row">
              <span>Stay Dates:</span>
              <span class="summary-row-value"><?= htmlspecialchars($booking['check_in_date']) ?> to <?= htmlspecialchars($booking['check_out_date']) ?></span>
            </div>
          </div>
        </div>
      </section>

      <?php if (!empty($upgradeSuggestions)): ?>
      <section class="card" style="border-color:var(--brand-200);">
        <div class="p-6">
          <h3 class="section-subtitle" style="color:var(--brand-700); margin-bottom:var(--space-3);">Available Alternatives</h3>
          <p class="text-small" style="color:var(--neutral-500); margin-bottom:var(--space-4);">The following rooms are still free for your dates:</p>
          <div class="space-y-3">
            <?php foreach ($upgradeSuggestions as $alt): ?>
              <div style="display:flex; align-items:center; justify-content:space-between; padding:var(--space-4); border:1px solid var(--neutral-100); border-radius:var(--radius-sm);">
                <div>
                  <div class="text-small" style="font-weight:700; color:var(--brand-900);">Room <?= htmlspecialchars($alt['room_number']) ?></div>
                  <div class="text-tiny" style="color:var(--neutral-400);"><?= htmlspecialchars($alt['room_type']) ?> &mdash; $<?= htmlspecialchars(number_format($alt['base_rate'], 2)) ?>/night</div>
                </div>
                <a href="index.php?route=book&room_id=<?= (int)$alt['id'] ?>" class="btn btn-primary btn-sm">Book This</a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
      <?php endif; ?>

      <section class="hidden md:block">
        <a href="index.php?route=bookings" class="btn btn-primary btn-full">View My Bookings</a>
      </section>

    </main>

    <?php include __DIR__ . '/../partials/site-footer.php'; ?>
    <p class="text-small" style="text-align:center; color:var(--neutral-400); margin-top:var(--space-4);">Redirecting to home in <span id="redirect-countdown">8</span> seconds...</p>

    <?php
    $items = $navItems ?? [];
    include __DIR__ . '/../partials/bottom-tab-bar.php';
    ?>
    <?php
    $label = 'View My Bookings';
    $href = 'index.php?route=bookings';
    include __DIR__ . '/../partials/sticky-action-bar.php';
    ?>
  <script>
    (function() {
      var seconds = 8;
      var el = document.getElementById('redirect-countdown');
      var interval = setInterval(function() {
        seconds--;
        if (el) el.textContent = seconds;
        if (seconds <= 0) {
          clearInterval(interval);
          window.location.href = 'index.php?route=home';
        }
      }, 1000);
    })();
  </script>
<?php include __DIR__ . '/../partials/guest-footer.php'; ?>