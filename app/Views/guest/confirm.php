<?php
$booking = $booking ?? [];
$addons = $addons ?? [];
$payments = $payments ?? [];
$booking = array_merge([
  'id' => 0, 'property_name' => 'ZyroInn Property', 'address' => '',
  'city' => '', 'state' => '', 'room_number' => '', 'room_type' => 'Comfort Room',
  'check_in_date' => '', 'check_out_date' => ''
], $booking);
?>
<?php
$title = 'Booking Confirmed - ZyroInn';
$badge = 'Confirmation';
include __DIR__ . '/../partials/guest-header.php';
?>

    <main class="main-content-narrow">

      <section class="card text-center">
        <div class="p-6">
          <div style="width:4rem; height:4rem; background:var(--confirmation-bg); border:1px solid var(--confirmation-color); border-radius:9999px; display:flex; align-items:center; justify-content:center; margin:0 auto var(--space-4); color:var(--confirmation-color); font-size:1.5rem;">
            ✓
          </div>
          <h2 class="text-h2 mb-3">Booking Confirmed!</h2>
          <p class="text-small" style="color:var(--neutral-500); margin-bottom:var(--space-5);">
            Thank you for choosing ZyroInn. Your booking has been registered and confirmed. A verification receipt has been compiled.
          </p>

          <div style="background:var(--neutral-50); border:1px solid var(--neutral-100); padding:var(--space-4); border-radius:var(--radius-sm);">
            <span class="text-tiny" style="color:var(--neutral-400); display:block; margin-bottom:0.25rem;">Booking Reference</span>
            <span style="font-weight:700; color:var(--brand-900); user-select:all;">#BKG-<?= str_pad($booking['id'], 5, '0', STR_PAD_LEFT) ?></span>
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
              <span>Room:</span>
              <span class="summary-row-value">Room <?= htmlspecialchars($booking['room_number']) ?> (<?= htmlspecialchars($booking['room_type']) ?>)</span>
            </div>
            <div class="summary-row">
              <span>Stay Dates:</span>
              <span class="summary-row-value"><?= htmlspecialchars($booking['check_in_date']) ?> to <?= htmlspecialchars($booking['check_out_date']) ?></span>
            </div>

            <?php if (!empty($addons)): ?>
              <div class="summary-row" style="flex-direction:column; align-items:flex-start; gap:var(--space-1);">
                <span>Add-ons Selected:</span>
                <ul style="padding-left:1.25rem; font-size:0.8125rem; color:var(--neutral-500);">
                  <?php foreach ($addons as $addon): ?>
                    <li><?= htmlspecialchars($addon['name']) ?> (x<?= htmlspecialchars($addon['quantity']) ?>)</li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <?php
            $totalAmount = 0.0;
            $txnRef = 'Pending';
            if (!empty($payments)) {
                $totalAmount = $payments[0]['amount'];
                $txnRef = $payments[0]['transaction_reference'];
            }
            ?>
            <div class="summary-row">
              <span>Payment status:</span>
              <span class="status-badge confirmed">PAID</span>
            </div>
            <div class="summary-row">
              <span>Payment Reference:</span>
              <span class="summary-row-value" style="user-select:all;"><?= htmlspecialchars($txnRef) ?></span>
            </div>

            <hr class="divider">

            <div class="summary-row" style="font-weight:700; color:var(--brand-900);">
              <span>Total Paid</span>
              <span>$<?= htmlspecialchars(number_format($totalAmount, 2)) ?></span>
            </div>
          </div>
        </div>
      </section>

      <section class="hidden md:block">
        <a href="index.php?route=bookings" class="btn btn-primary btn-full">View My Bookings</a>
      </section>

    </main>

    <p class="text-small" style="text-align:center; color:var(--neutral-400); margin-top:var(--space-4);">Redirecting to home in <span id="redirect-countdown">8</span> seconds...</p>

    <?php include __DIR__ . '/../partials/site-footer.php'; ?>
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