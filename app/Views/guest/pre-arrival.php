<?php
$booking = $booking ?? [];
$preferences = $preferences ?? [];
$requests = $requests ?? [];
$error = $error ?? '';
$success = $success ?? '';
$checkInWindow = $checkInWindow ?? false;

$title = 'Pre-Arrival Check-In - ZyroInn';
$metaDescription = 'Complete your pre-arrival check-in online. Select room preferences and submit special requests before your stay.';
$badge = 'Pre-Arrival';
include __DIR__ . '/../partials/guest-header.php';

$presetRequests = [
    'early_checkin' => 'Early check-in', 'late_checkout' => 'Late check-out',
    'extra_towels'  => 'Extra towels', 'extra_pillows' => 'Extra pillows',
    'crib'          => 'Baby crib / cot', 'quiet_room'    => 'Quiet room request',
    'high_floor'    => 'High floor (if available)', 'connecting'    => 'Connecting room (if available)',
];
?>
    <main class="main-content-narrow" style="max-width:40rem;">

      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if (!$booking): ?>
        <div class="empty-state" style="margin-top:var(--space-10);">
          <div class="empty-state-icon">✈️</div>
          <h1 class="empty-state-title">No upcoming stays</h1>
          <p class="empty-state-text">You don't have any upcoming bookings within the check-in window.</p>
          <a href="index.php?route=bookings" class="btn btn-primary">View My Bookings</a>
        </div>
      <?php elseif (!$checkInWindow): ?>
        <div class="empty-state" style="margin-top:var(--space-10);">
          <div class="empty-state-icon">⏳</div>
          <h1 class="empty-state-title">Check-in not yet available</h1>
          <p class="empty-state-text">
            Online check-in for <strong><?= htmlspecialchars($booking['property_name'] ?? '') ?></strong> opens 48 hours before your check-in date of <strong><?= date('M d, Y', strtotime($booking['check_in_date'])) ?></strong>.
          </p>
          <a href="index.php?route=bookings" class="btn btn-primary">Back to Bookings</a>
        </div>
      <?php else: ?>

      <div class="card">
        <div class="p-6">
          <h1 class="text-h2 mb-1">Pre-Arrival Check-In</h1>
          <p class="text-small" style="color:var(--neutral-500);">Let us know your preferences so we can prepare your room before you arrive.</p>

          <div class="mt-5" style="background:var(--brand-50); border:1px solid var(--brand-200); border-radius:var(--radius-sm); padding:var(--space-4); display:grid; grid-template-columns:repeat(2,1fr); gap:var(--space-4);">
            <div>
              <span class="text-tiny" style="color:var(--neutral-400);">Property</span>
              <p class="text-small" style="font-weight:700; color:var(--brand-900); margin-top:0.25rem;"><?= htmlspecialchars($booking['property_name'] ?? '') ?></p>
            </div>
            <div>
              <span class="text-tiny" style="color:var(--neutral-400);">Room</span>
              <p class="text-small" style="font-weight:600; color:var(--neutral-500); margin-top:0.25rem;"><?= htmlspecialchars($booking['room_type'] ?? '') ?> (<?= htmlspecialchars($booking['room_number'] ?? '') ?>)</p>
            </div>
            <div>
              <span class="text-tiny" style="color:var(--neutral-400);">Check-In</span>
              <p class="text-small" style="font-weight:600; color:var(--neutral-500); margin-top:0.25rem;"><?= date('M d, Y', strtotime($booking['check_in_date'])) ?></p>
            </div>
            <div>
              <span class="text-tiny" style="color:var(--neutral-400);">Check-Out</span>
              <p class="text-small" style="font-weight:600; color:var(--neutral-500); margin-top:0.25rem;"><?= date('M d, Y', strtotime($booking['check_out_date'])) ?></p>
            </div>
          </div>

          <form action="index.php?route=pre-arrival" method="POST" style="margin-top:var(--space-8);">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>">

            <fieldset>
              <legend class="text-h3">Room Preferences</legend>
              <p class="text-small" style="color:var(--neutral-500); margin-top:0.25rem;">These help us assign the best possible room for your stay.</p>
              <div class="mt-4 grid-2 gap-4">
                <div>
                  <label for="floor_preference" class="input-label">Floor preference</label>
                  <select id="floor_preference" name="floor_preference" class="input">
                    <option value="any" <?= ($preferences['floor_preference'] ?? 'any') === 'any' ? 'selected' : '' ?>>No preference</option>
                    <option value="low" <?= ($preferences['floor_preference'] ?? '') === 'low' ? 'selected' : '' ?>>Low floor (easier access)</option>
                    <option value="mid" <?= ($preferences['floor_preference'] ?? '') === 'mid' ? 'selected' : '' ?>>Mid floor</option>
                    <option value="high" <?= ($preferences['floor_preference'] ?? '') === 'high' ? 'selected' : '' ?>>High floor (better view)</option>
                  </select>
                </div>
                <div>
                  <label for="bed_type" class="input-label">Bed type</label>
                  <select id="bed_type" name="bed_type" class="input">
                    <option value="any" <?= ($preferences['bed_type'] ?? 'any') === 'any' ? 'selected' : '' ?>>No preference</option>
                    <option value="twin" <?= ($preferences['bed_type'] ?? '') === 'twin' ? 'selected' : '' ?>>Twin beds</option>
                    <option value="queen" <?= ($preferences['bed_type'] ?? '') === 'queen' ? 'selected' : '' ?>>Queen bed</option>
                    <option value="king" <?= ($preferences['bed_type'] ?? '') === 'king' ? 'selected' : '' ?>>King bed</option>
                  </select>
                </div>
              </div>
            </fieldset>

            <hr class="divider">

            <fieldset>
              <legend class="text-h3">Special Requests</legend>
              <p class="text-small" style="color:var(--neutral-500); margin-top:0.25rem;">Select from common options or write your own.</p>
              <div class="mt-4 grid-cols-1 gap-2">
                <?php foreach ($presetRequests as $key => $label): ?>
                  <label class="custom-checkbox">
                    <input type="checkbox" name="preset_requests[]" value="<?= $key ?>" class="sr-only"
                           <?= in_array($key, $requests) ? 'checked' : '' ?>>
                    <span class="custom-checkbox-indicator"></span>
                    <span class="text-small" style="color:var(--neutral-500);"><?= htmlspecialchars($label) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
              <div class="mt-4">
                <label for="custom_request" class="input-label">Custom request (optional)</label>
                <textarea id="custom_request" name="custom_request" rows="3" placeholder="Anything else we should know? Dietary restrictions, anniversary celebration, accessibility needs..."
                          class="input"><?= htmlspecialchars($customRequest ?? '') ?></textarea>
              </div>
            </fieldset>

            <div class="flex gap-4 mt-6" style="padding-top:var(--space-5); border-top:1px solid var(--neutral-100);">
              <a href="index.php?route=bookings" class="btn btn-secondary flex-1">Cancel</a>
              <button type="submit" class="btn btn-primary flex-1">Save Preferences</button>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <div style="padding-bottom:6rem;"></div>

    </main>
<?php include __DIR__ . '/../partials/guest-footer.php'; ?>