<?php
if (session_status() === PHP_SESSION_NONE) {
    $env = [];
    $envPath = __DIR__ . '/../../config/env.php';
    if (file_exists($envPath)) $env = require $envPath;
    session_init($env);
}
$step = $step ?? 'dates';
$room = $room ?? [];
$property = $property ?? [];
$propertyId = $property['id'] ?? ($_GET['property_id'] ?? 0);
$room = array_merge([ 'occupancy' => 1, 'room_number' => '', 'room_type' => 'Comfort Room', 'base_rate' => 0 ], $room);
$roomId = $roomId ?? 0;
$draft = $_SESSION['booking_draft'] ?? [];
$draft = array_merge([
  'check_in' => '', 'check_out' => '', 'guests_count' => 1,
  'first_name' => $_SESSION['first_name'] ?? '', 'last_name' => $_SESSION['last_name'] ?? '',
  'email' => $_SESSION['email'] ?? '', 'phone' => '', 'addons' => []
], $draft);
$error = $error ?? '';
$stepMap = ['dates' => 1, 'guests' => 2, 'addons' => 3, 'payment' => 4];
$currentStep = $stepMap[$step] ?? 1;

$addons = [];
try {
  $addonStmt = ($db ?? null)?->prepare("SELECT * FROM addons WHERE is_active = 1 ORDER BY id");
  if ($addonStmt) { $addonStmt->execute(); $addons = $addonStmt->fetchAll(); }
} catch (Exception $e) { $addons = []; }

function addonUnitLabel(string $unit): string {
  return match ($unit) { 'per_guest_night' => '/ guest / night', 'per_night' => '/ night', 'per_guest' => '/ guest', 'per_transfer' => '/ transfer', default => '/ stay' };
}
function addonQuantity(string $unit, int $nights, int $guestsCount): int {
  return match ($unit) { 'per_guest_night' => $nights * $guestsCount, 'per_night' => $nights, 'per_guest' => $guestsCount, 'per_transfer' => 1, default => 1 };
}
?>
<?php
$title = 'Book ' . htmlspecialchars($room['room_type'] ?? 'Room') . ' at ' . htmlspecialchars($property['name'] ?? '') . ' - ZyroInn';
$metaDescription = 'Complete your booking for ' . htmlspecialchars($room['room_type'] ?? '') . ' at ' . htmlspecialchars($property['name'] ?? '') . ' on ZyroInn. Secure checkout with loyalty rewards.';
$badge = 'Checkout';
include __DIR__ . '/../partials/guest-header.php';
?>

    <main class="main-content">

      <?php include __DIR__ . '/../partials/booking-steps-indicator.php'; ?>

      <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <div style="display:flex; flex-direction:column; gap:var(--space-6);">
        <div>
          <form id="booking-form" action="index.php?route=book&room_id=<?= $roomId ?><?= $propertyId ? '&property_id=' . $propertyId : '' ?>&step=<?= htmlspecialchars($step) ?>" method="POST" class="card">
            <div style="padding: var(--space-6);">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

              <?php if ($step === 'dates'): ?>
                <div style="display:flex; flex-direction:column; gap:var(--space-4);">
                  <div>
                    <h3 class="text-h3">Select Stay Dates</h3>
                    <p class="text-small" style="color: var(--neutral-500);">Pick check-in and check-out dates for your boutique stay.</p>
                  </div>
                  <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:var(--space-4);">
                    <div>
                      <label for="check_in" class="input-label">Check-In Date</label>
                      <input type="date" name="check_in" id="check_in" required
                             min="<?= date('Y-m-d') ?>"
                             value="<?= htmlspecialchars($draft['check_in'] ?: date('Y-m-d')) ?>" class="input">
                    </div>
                    <div>
                      <label for="check_out" class="input-label">Check-Out Date</label>
                      <input type="date" name="check_out" id="check_out" required
                             min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                             value="<?= htmlspecialchars($draft['check_out'] ?: date('Y-m-d', strtotime('+2 days'))) ?>" class="input">
                    </div>
                  </div>
                </div>

              <?php elseif ($step === 'guests'): ?>
                <div style="display:flex; flex-direction:column; gap:var(--space-4);">
                  <div>
                    <h3 class="text-h3">Occupancy &amp; Guest Details</h3>
                    <p class="text-small" style="color: var(--neutral-500);">Specify details for the primary booking contact.</p>
                  </div>
                  <div>
                    <label for="guests_count" class="input-label">Number of Guests</label>
                    <select name="guests_count" id="guests_count" required class="input">
                      <?php for ($i = 1; $i <= (int)$room['occupancy']; $i++): ?>
                        <option value="<?= $i ?>" <?= $draft['guests_count'] == $i ? 'selected' : '' ?>><?= $i ?> Guest(s)</option>
                      <?php endfor; ?>
                    </select>
                    <p class="text-tiny" style="color: var(--neutral-400); margin-top: 0.25rem;">Maximum allowed occupancy: <?= htmlspecialchars($room['occupancy']) ?> guests.</p>
                  </div>
                  <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:var(--space-4);">
                    <div>
                      <label for="first_name" class="input-label">First Name</label>
                      <input type="text" name="first_name" id="first_name" required
                             value="<?= htmlspecialchars($draft['first_name']) ?>" class="input">
                    </div>
                    <div>
                      <label for="last_name" class="input-label">Last Name</label>
                      <input type="text" name="last_name" id="last_name" required
                             value="<?= htmlspecialchars($draft['last_name']) ?>" class="input">
                    </div>
                  </div>
                  <div>
                    <label for="email" class="input-label">Email Address</label>
                    <input type="email" name="email" id="email" required
                           value="<?= htmlspecialchars($draft['email']) ?>" class="input">
                  </div>
                  <div>
                    <label for="phone" class="input-label">Phone Number</label>
                    <input type="tel" name="phone" id="phone"
                           value="<?= htmlspecialchars($draft['phone']) ?>" class="input">
                  </div>
                </div>

              <?php elseif ($step === 'addons'): ?>
                <div style="display:flex; flex-direction:column; gap:var(--space-4);">
                  <div>
                    <h3 class="text-h3">Optional Add-ons</h3>
                    <p class="text-small" style="color: var(--neutral-500);">Enhance your experience with custom booking extras.</p>
                  </div>
                  <?php if (empty($addons)): ?>
                    <p class="text-small" style="color: var(--neutral-400); font-style:italic;">No add-ons available for this property.</p>
                  <?php else: ?>
                    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:var(--space-4);">
                      <?php foreach ($addons as $i => $addon): ?>
                        <label class="addon-card" style="position:relative;">
                          <input type="checkbox" name="addons[]" value="<?= htmlspecialchars($addon['key']) ?>" class="sr-only"
                                 <?= in_array($addon['key'], $draft['addons']) ? 'checked' : '' ?>>
                          <div class="addon-card-body" style="flex-direction:column; gap:var(--space-3); padding:var(--space-5);">
                            <?php if ($i === 0): ?>
                              <span style="position:absolute; top:-0.5rem; right:var(--space-3); background:var(--brand-500); color:#fff; font-size:0.625rem; font-weight:700; padding:0.125rem 0.625rem; border-radius:var(--radius-pill); text-transform:uppercase; letter-spacing:0.04em;">Popular</span>
                            <?php endif; ?>
                            <div style="display:flex; align-items:center; justify-content:space-between;">
                              <span style="font-size:1.125rem; font-weight:700; color:var(--brand-900);"><?= htmlspecialchars($addon['name']) ?></span>
                              <span style="font-size:1.25rem; font-weight:700; color:var(--brand-600);">
                                $<?= number_format($addon['price'], 2) ?>
                                <small style="font-size:0.625rem; font-weight:500; color:var(--neutral-400);"><?= addonUnitLabel($addon['unit']) ?></small>
                              </span>
                            </div>
                            <p style="font-size:0.8125rem; color:var(--neutral-500); line-height:1.5; margin:0;">
                              <?= htmlspecialchars($addon['description']) ?>
                            </p>
                            <div style="display:flex; align-items:center; gap:var(--space-2);">
                              <span class="addon-check" style="width:1.25rem; height:1.25rem; border-radius:0.25rem; border:2px solid var(--neutral-300); background:var(--neutral-50); display:flex; align-items:center; justify-content:center; font-size:0.625rem; font-weight:700; color:transparent; transition:all var(--transition-ease);">✓</span>
                            </div>
                          </div>
                        </label>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>

              <?php elseif ($step === 'payment'): ?>
                <div style="display:flex; flex-direction:column; gap:var(--space-4);">
                  <div>
                    <h3 class="text-h3">Secure Payment</h3>
                    <p class="text-small" style="color: var(--neutral-500);">Finalize your stay by entering credit card details.</p>
                  </div>

                  <div style="padding: var(--space-4); background: var(--brand-50); border: 1px solid var(--brand-200); border-radius: var(--radius-sm); font-size: 0.8125rem; color: var(--brand-900);">
                    <p style="font-weight: 700; margin-bottom: var(--space-2);">Integration Point Note:</p>
                    <p style="font-size: 0.6875rem; line-height: 1.6;">
                      This step currently processes a mock validation logic in the guest front controller. To wire in a live checkout payment gateway (such as Stripe API), replace this HTML mockup with Stripe Elements container JS libraries.
                    </p>
                  </div>

                  <div style="display:flex; flex-direction:column; gap:var(--space-3);">
                    <div>
                      <label for="cardholder" class="input-label">Cardholder Name</label>
                      <input type="text" name="cardholder" id="cardholder" required placeholder="Maya Patel" class="input">
                    </div>
                    <div>
                      <label for="cardnumber" class="input-label">Card Number</label>
                      <input type="text" name="cardnumber" id="cardnumber" required placeholder="4111 2222 3333 4444" class="input">
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:var(--space-4);">
                      <div>
                        <label for="expiry" class="input-label">Expiration Date</label>
                        <input type="text" name="expiry" id="expiry" required placeholder="MM/YY" class="input">
                      </div>
                      <div>
                        <label for="cvc" class="input-label">CVC</label>
                        <input type="text" name="cvc" id="cvc" required placeholder="123" class="input">
                      </div>
                    </div>
                  </div>
                </div>
              <?php endif; ?>

              <div style="margin-top: var(--space-6); padding-top: var(--space-5); border-top: 1px solid var(--neutral-100); align-items:center; justify-content:space-between; display:none;" class="booking-form-actions">
                <?php if ($currentStep > 1):
                  $prevStepMap = [2 => 'dates', 3 => 'guests', 4 => 'addons'];
                  $prevStep = $prevStepMap[$currentStep] ?? 'dates';
                ?>
                  <a href="index.php?route=book&room_id=<?= $roomId ?><?= $propertyId ? '&property_id=' . $propertyId : '' ?>&step=<?= $prevStep ?>" class="btn btn-secondary">Back</a>
                <?php else: ?>
                  <div></div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">
                  <?php if ($step === 'payment'): ?>Confirm &amp; Pay<?php else: ?>Continue<?php endif; ?>
                </button>
              </div>
            </div>
          </form>
        </div>

        <div style="display:flex; flex-direction:column; gap:var(--space-4);">
          <div class="card">
            <div style="padding: var(--space-5);">
              <span class="pill pill-brand" style="margin-bottom: var(--space-3);">Booking Summary</span>
              <h4 class="text-h3" style="margin-bottom: var(--space-1);"><?= htmlspecialchars($property['name']) ?></h4>
              <p class="text-small" style="color: var(--neutral-500);">Room type: <?= htmlspecialchars($room['room_type']) ?></p>
              <p class="text-small" style="color: var(--neutral-500);">Room rate: $<?= htmlspecialchars(number_format($room['base_rate'], 2)) ?> / night</p>

              <?php if (!empty($draft['check_in']) && !empty($draft['check_out'])):
                $nights = (strtotime($draft['check_out']) - strtotime($draft['check_in'])) / 86400;
                $nights = max(1, $nights);
              ?>
                <hr class="divider">

                <div style="display: flex; flex-direction: column; gap: var(--space-1);">
                  <div class="summary-row">
                    <span>Dates:</span>
                    <span class="summary-row-value"><?= htmlspecialchars($draft['check_in']) ?> to <?= htmlspecialchars($draft['check_out']) ?></span>
                  </div>
                  <div class="summary-row">
                    <span>Total Nights:</span>
                    <span class="summary-row-value"><?= $nights ?> night(s)</span>
                  </div>
                  <div class="summary-row">
                    <span>Occupants:</span>
                    <span class="summary-row-value"><?= htmlspecialchars($draft['guests_count'] ?? 1) ?> Guest(s)</span>
                  </div>
                </div>

                <hr class="divider">

                <div class="summary-rows">
                  <div class="summary-row">
                    <span>Room Charge (<?= $nights ?> nights)</span>
                    <span>$<?= htmlspecialchars(number_format($nights * $room['base_rate'], 2)) ?></span>
                  </div>

                  <?php
                  $addonTally = 0.0;
                  if (!empty($draft['addons'])):
                    $selectedAddons = array_filter($addons, function($a) use ($draft) {
                      return in_array($a['key'], $draft['addons']);
                    });
                  ?>
                    <div style="padding-top: var(--space-1); border-top: 1px solid var(--neutral-100);">
                      <?php foreach ($selectedAddons as $addon):
                        $qty = addonQuantity($addon['unit'], $nights, $draft['guests_count'] ?? 1);
                        $cost = $addon['price'] * $qty;
                        $addonTally += $cost;
                      ?>
                        <div class="summary-row">
                          <span><?= htmlspecialchars($addon['name']) ?> (x<?= $qty ?>)</span>
                          <span>$<?= htmlspecialchars(number_format($cost, 2)) ?></span>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>

                  <hr class="divider">
                  <div class="summary-row" style="font-weight: 700; color: var(--brand-900);">
                    <span>Total Cost</span>
                    <span id="total-amount" data-total="<?= htmlspecialchars(number_format(($nights * $room['base_rate']) + $addonTally, 2, '.', '')) ?>">$<?= htmlspecialchars(number_format(($nights * $room['base_rate']) + $addonTally, 2)) ?></span>
                  </div>
                </div>

                <?php if ($step === 'payment' && !empty($_SESSION['guest_id'])):
                  $loyaltyData = null;
                  try {
                    $loyaltyService = new \App\Services\LoyaltyService();
                    $loyaltyData = $loyaltyService->getAccountWithTier((int)$_SESSION['guest_id']);
                  } catch (Exception $e) {}
                ?>
                  <?php if ($loyaltyData && $loyaltyData['account']['points_balance'] > 0): ?>
                    <hr class="divider">
                    <div x-data="loyaltyRedemption(<?= $loyaltyData['account']['points_balance'] ?>, <?= ($nights * $room['base_rate']) + $addonTally ?>)">
                      <h4 class="section-subtitle" style="color: var(--brand-500); margin-bottom: var(--space-2);">Loyalty Rewards</h4>
                      <p class="text-tiny" style="color: var(--neutral-500); margin-bottom: var(--space-2);">You have <strong style="color: var(--brand-900);"><?= number_format($loyaltyData['account']['points_balance']) ?></strong> points available.</p>
                      <div style="display:flex; align-items:center; gap:var(--space-2); margin-bottom: var(--space-2);">
                        <input type="hidden" name="redeem_points" x-model="redeemPoints" value="0">
                        <input type="number" x-model="redeemPoints" min="0" max="<?= $loyaltyData['account']['points_balance'] ?>" step="100" placeholder="Points to redeem" class="input" style="flex: 1;">
                        <button type="button" @click="applyRedemption()" :disabled="applied"
                                class="btn btn-primary btn-sm" :class="applied ? 'opacity-50 cursor-not-allowed' : ''">
                          <span x-text="applied ? 'Applied' : 'Apply'"></span>
                        </button>
                      </div>
                      <p x-show="dollarValue > 0" class="text-tiny" style="color: var(--success); font-weight: 600;" x-cloak>
                        -$<span x-text="dollarValue.toFixed(2)"></span> discount applied
                      </p>
                      <p x-show="redeemError" x-text="redeemError" class="text-tiny" style="color: var(--error); margin-top: 0.25rem;" x-cloak></p>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    </main>

    <?php include __DIR__ . '/../partials/site-footer.php'; ?>
    <?php
    $items = $navItems ?? [];
    include __DIR__ . '/../partials/bottom-tab-bar.php';
    ?>

    <?php
    $labelMap = [
      'dates' => 'Continue to Guests', 'guests' => 'Continue to Add-ons',
      'addons' => 'Continue to Payment', 'payment' => 'Confirm & Pay'
    ];
    $label = $labelMap[$step] ?? 'Continue';
    $href = '';
    $onClick = "document.getElementById('booking-form').submit()";
    include __DIR__ . '/../partials/sticky-action-bar.php';
    ?>

<?php include __DIR__ . '/../partials/guest-footer.php'; ?>