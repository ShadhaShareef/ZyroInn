<?php
if (session_status() === PHP_SESSION_NONE) {
    $env = [];
    $envPath = __DIR__ . '/../../config/env.php';
    if (file_exists($envPath)) { $env = require $envPath; }
    session_init($env);
}
$upcomingBookings = $upcomingBookings ?? [];
$pastBookings = $pastBookings ?? [];
$guestName = trim((($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')));
?>
<?php
$title = 'My Bookings - ZyroInn';
$badge = 'My Bookings';
include __DIR__ . '/../partials/guest-header.php';
?>

    <main class="main-content-narrow">

      <section class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="section-title mb-0">Upcoming Trips</h2>
          <span class="pill pill-brand"><?= count($upcomingBookings) ?> Active</span>
        </div>

        <?php if (empty($upcomingBookings)): ?>
          <div class="empty-state">
            <div class="empty-state-icon" style="font-size:1.5rem; opacity:0.3;">[ ]</div>
            <h3 class="empty-state-title">No upcoming bookings</h3>
            <p class="empty-state-text">Time to explore boutique stays!</p>
            <a href="index.php?route=home" class="btn btn-primary">Find a Room</a>
          </div>
        <?php else: ?>
          <div class="space-y-4">
            <?php foreach ($upcomingBookings as $booking):
              $guestName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
              $stayDates = date('M d, Y', strtotime($booking['check_in_date'])) . ' to ' . date('M d, Y', strtotime($booking['check_out_date']));
              $statusText = ucfirst($booking['status']);
              $room = $booking['room_number'] . ' (' . $booking['room_type'] . ')';
              $price = $booking['property_name'];
              $meta = ['guests' => 'Verified'];

              include __DIR__ . '/../partials/card-booking.php';

              $checkInTs = strtotime($booking['check_in_date']);
              $isWithinWindow = $checkInTs >= strtotime('-1 day') && $checkInTs <= strtotime('+2 days');
              if ($isWithinWindow && $booking['status'] === 'confirmed'): ?>
                <div class="text-right" style="margin-top:-0.25rem;">
                  <a href="index.php?route=pre-arrival" class="btn btn-ghost btn-sm" style="gap:0.25rem;">
                    Complete Pre-Arrival Check-In
                  </a>
                </div>
              <?php endif; ?>
              <?php if (in_array($booking['status'], ['confirmed', 'checked_in'])): ?>
                <div class="text-right" style="margin-top:-0.25rem;">
                  <a href="index.php?route=services&booking_id=<?= (int)$booking['id'] ?>" class="btn btn-ghost btn-sm" style="gap:0.25rem;">
                    Book Services
                  </a>
                </div>
              <?php endif; ?>
              <?php if (in_array($booking['status'], ['confirmed', 'checked_in', 'checked_out'])): ?>
                <div class="text-right" style="margin-top:-0.25rem;">
                  <a href="index.php?route=bill&booking_id=<?= (int)$booking['id'] ?>" class="btn btn-ghost btn-sm" style="gap:0.25rem;">
                    View E-Bill
                  </a>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <section class="space-y-4">
        <h2 class="section-title mb-0">Past & Cancelled</h2>

        <?php if (empty($pastBookings)): ?>
          <div class="empty-state" style="padding:var(--space-6); background:transparent; border-style:dashed;">
            <p class="text-small" style="color:var(--neutral-400); font-style:italic;">No past booking records.</p>
          </div>
        <?php else: ?>
          <div class="space-y-4" style="opacity:0.75;">
            <?php foreach ($pastBookings as $booking):
              $guestName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
              $stayDates = date('M d, Y', strtotime($booking['check_in_date'])) . ' to ' . date('M d, Y', strtotime($booking['check_out_date']));
              $statusText = ucfirst($booking['status']);
              $room = $booking['room_number'] . ' (' . $booking['room_type'] . ')';
              $price = $booking['property_name'];
              $meta = ['guests' => 'History'];
            ?>
              <?php include __DIR__ . '/../partials/card-booking.php'; ?>
              <?php if (in_array($booking['status'], ['checked_out', 'cancelled'])): ?>
                <div class="text-right" style="margin-top:-0.5rem;">
                  <a href="index.php?route=bill&booking_id=<?= (int)$booking['id'] ?>" class="btn btn-ghost btn-sm" style="gap:0.25rem;">
                    View E-Bill
                  </a>
                </div>
              <?php endif; ?>
              <?php if ($booking['status'] === 'checked_out'): ?>
                <div class="text-right" style="margin-top:-0.5rem;">
                  <a href="index.php?route=property&property_id=<?= (int)$booking['property_id'] ?>" class="btn btn-ghost btn-sm" style="gap:0.25rem;">
                    Write a Review
                  </a>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

    </main>

    <?php include __DIR__ . '/../partials/site-footer.php'; ?>
    <?php
    $items = $navItems ?? [];
    include __DIR__ . '/../partials/bottom-tab-bar.php';
    ?>
    <?php
    $label = 'Book a New Stay';
    $href = 'index.php?route=home';
    include __DIR__ . '/../partials/sticky-action-bar.php';
    ?>
<?php include __DIR__ . '/../partials/guest-footer.php'; ?>