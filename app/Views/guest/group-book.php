<?php
$property = $property ?? [];
$rooms = $rooms ?? [];
$error = $error ?? null;
$success = $success ?? false;
$bookedRooms = $bookedRooms ?? [];
$failedRooms = $failedRooms ?? [];
$groupUuid = $groupUuid ?? '';
?>
<?php
$title = 'Group Booking - ZyroInn';
$badge = 'Group Booking';
include __DIR__ . '/../partials/guest-header.php';
?>

    <main class="main-content" style="max-width: 36rem;">

      <section class="card">
        <div style="padding: var(--space-4);">
          <p class="text-tiny" style="color: var(--neutral-400);">Group booking at</p>
          <h2 class="text-h3" style="margin-top: 0.25rem;"><?= htmlspecialchars($property['name'] ?? '') ?></h2>
          <p class="text-small" style="color: var(--neutral-500); margin-top: 0.25rem;"><?= htmlspecialchars($property['city'] ?? '') ?>, <?= htmlspecialchars($property['state'] ?? '') ?></p>
        </div>
      </section>

      <?php if ($success): ?>
        <div class="alert alert-success">
          <p style="font-weight: 700; margin-bottom: var(--space-2);">Group booking confirmed</p>
          <p>Rooms: <?= implode(', ', $bookedRooms) ?></p>
          <p class="text-tiny" style="font-weight: 500; color: var(--neutral-500); margin-top: var(--space-1);">Group reference: <code style="user-select: all; background: #fff; padding: 0.125rem 0.5rem; border-radius: 0.25rem; border: 1px solid var(--neutral-200);"><?= htmlspecialchars($groupUuid) ?></code></p>
          <?php if (!empty($failedRooms)): ?>
            <p class="text-tiny" style="color: var(--waitlist-color, #D48B2F); margin-top: var(--space-2);">Room(s) <?= implode(', ', $failedRooms) ?> were unavailable and not booked.</p>
          <?php endif; ?>
        </div>
        <a href="index.php?route=bookings" class="btn btn-primary btn-full">View My Bookings</a>
      <?php else: ?>

      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="index.php?route=group-book&property_id=<?= (int)$property['id'] ?>" style="display:flex; flex-direction:column; gap:var(--space-6);">
        <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">

        <section class="card">
          <div style="padding: var(--space-4);">
            <h3 class="section-subtitle" style="margin-bottom: var(--space-4);">Stay Dates</h3>
            <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:var(--space-3);">
              <div>
                <label class="input-label" style="margin-bottom: 0.25rem;">Check-in</label>
                <input type="date" name="check_in" value="<?= htmlspecialchars($_POST['check_in'] ?? '') ?>" required class="input">
              </div>
              <div>
                <label class="input-label" style="margin-bottom: 0.25rem;">Check-out</label>
                <input type="date" name="check_out" value="<?= htmlspecialchars($_POST['check_out'] ?? '') ?>" required class="input">
              </div>
            </div>
          </div>
        </section>

        <section class="card">
          <div style="padding: var(--space-4);">
            <h3 class="section-subtitle" style="margin-bottom: var(--space-4);">Select Rooms</h3>
            <?php if (empty($rooms)): ?>
              <p class="text-small" style="color: var(--neutral-400); font-style: italic; text-align: center; padding: var(--space-4);">No available rooms at this property.</p>
            <?php else: ?>
              <?php foreach ($rooms as $room): ?>
                <label class="custom-checkbox" style="margin-bottom: var(--space-3); padding: var(--space-3); border: 1px solid var(--neutral-200); border-radius: var(--radius-sm); cursor: pointer;">
                  <input type="checkbox" name="room_ids[]" value="<?= $room['id'] ?>" class="sr-only"
                    <?= in_array((string)$room['id'], $_POST['room_ids'] ?? []) ? 'checked' : '' ?>>
                  <span class="custom-checkbox-indicator" style="margin-top: 0.125rem;"></span>
                  <div style="flex: 1; min-width: 0;">
                    <p class="text-small" style="font-weight: 700; color: var(--brand-900);">Room <?= htmlspecialchars($room['room_number']) ?></p>
                    <p class="text-small" style="color: var(--neutral-500);"><?= htmlspecialchars($room['room_type'] ?? '') ?> · Up to <?= (int)$room['occupancy'] ?> guests</p>
                    <p class="text-small" style="font-weight: 700; color: var(--brand-600); margin-top: 0.25rem;">$<?= number_format($room['base_rate'], 2) ?>/night</p>
                  </div>
                </label>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </section>

        <section class="card">
          <div style="padding: var(--space-4);">
            <h3 class="section-subtitle" style="margin-bottom: var(--space-3);">Contact Details</h3>
            <p class="text-tiny" style="color: var(--neutral-400); margin-bottom: var(--space-3);">Primary contact for all bookings in this group.</p>
            <div class="grid-cols-2 grid gap-3">
              <div>
                <label class="input-label">First Name</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? $_SESSION['first_name'] ?? '') ?>" required class="input">
              </div>
              <div>
                <label class="input-label">Last Name</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? $_SESSION['last_name'] ?? '') ?>" required class="input">
              </div>
            </div>
            <div style="margin-top: var(--space-3);">
              <label class="input-label">Email</label>
              <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $_SESSION['email'] ?? '') ?>" required class="input">
            </div>
          </div>
        </section>

        <button type="submit" class="btn btn-primary btn-full">Confirm Group Booking</button>
      </form>

      <div style="text-align: center;">
        <a href="index.php?route=property&property_id=<?= (int)$property['id'] ?>" class="btn btn-ghost btn-sm">← Back to property</a>
      </div>

      <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../partials/site-footer.php'; ?>
    <?php $items = $navItems ?? []; include __DIR__ . '/../partials/bottom-tab-bar.php'; ?>
    <?php $label = 'Back to property'; $href = 'index.php?route=property&property_id=' . (int)$property['id']; include __DIR__ . '/../partials/sticky-action-bar.php'; ?>
<?php include __DIR__ . '/../partials/guest-footer.php'; ?>