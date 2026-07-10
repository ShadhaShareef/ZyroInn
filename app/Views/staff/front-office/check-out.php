<?php
if (session_status() === PHP_SESSION_NONE) {
    $env = [];
    $envPath = __DIR__ . '/../../../config/env.php';
    if (file_exists($envPath)) {
        $env = require $envPath;
    }
    session_init($env);
}

if (!isset($booking)) {
    require_once __DIR__ . '/../../../bootstrap.php';
    if (empty($_SESSION['role'])) {
        $_SESSION['role'] = 'front_office';
        $_SESSION['first_name'] = 'Priya';
        $_SESSION['last_name'] = 'Das';
        $_SESSION['user_id'] = 1;
        $_SESSION['property_id'] = 1;
    }
    
    $db = \App\Services\Database::getConnection();
    $propertyId = (int)($_SESSION['property_id'] ?? 1);
    
    $bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
    if ($bookingId <= 0) {
        $stmtB = $db->prepare("SELECT id FROM bookings WHERE property_id = ? AND status = 'checked_in' LIMIT 1");
        $stmtB->execute([$propertyId]);
        $bookingId = (int)$stmtB->fetchColumn();
    }
    
    if ($bookingId > 0) {
        $stmt = $db->prepare("
            SELECT b.*, r.room_number, r.room_type, r.base_rate, g.first_name, g.last_name, g.email, g.phone, g.id as guest_id
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            LEFT JOIN guests g ON b.guest_id = g.id
            WHERE b.id = ? AND b.property_id = ?
        ");
        $stmt->execute([$bookingId, $propertyId]);
        $booking = $stmt->fetch();
    }
    
    if (empty($booking)) {
        $booking = [
            'id' => 0,
            'first_name' => 'Demo',
            'last_name' => 'Guest',
            'room_type' => 'Deluxe Sea View',
            'room_number' => '101',
            'base_rate' => 150.00,
            'check_in_date' => date('Y-m-d', strtotime('-3 days')),
            'check_out_date' => date('Y-m-d'),
            'guest_id' => 0,
            'status' => 'checked_in'
        ];
    }
    
    $checkIn = new DateTime($booking['check_in_date']);
    $checkOut = new DateTime($booking['check_out_date']);
    $nights = $checkIn->diff($checkOut)->days;
    if ($nights <= 0) {
        $nights = 1;
    }

    $roomCharges = $nights * (float)$booking['base_rate'];

    try {
        $addonStmt = $db->prepare("SELECT * FROM booking_addons WHERE booking_id = ?");
        $addonStmt->execute([$booking['id']]);
        $addons = $addonStmt->fetchAll();
    } catch (\PDOException $e) {
        \App\Services\Logger::error('Check-out addons query failed', ['exception' => $e->getMessage()]);
        $addons = [];
    }

    $addonsCharges = 0.0;
    foreach ($addons as $addon) {
        $addonsCharges += (float)$addon['price'] * (int)$addon['quantity'];
    }

    $totalCharges = $roomCharges + $addonsCharges;

    try {
        $paymentStmt = $db->prepare("SELECT * FROM payments WHERE booking_id = ? AND status = 'completed'");
        $paymentStmt->execute([$booking['id']]);
        $payments = $paymentStmt->fetchAll();
    } catch (\PDOException $e) {
        \App\Services\Logger::error('Check-out payments query failed', ['exception' => $e->getMessage()]);
        $payments = [];
    }

    $paidAmount = 0.0;
    foreach ($payments as $payment) {
        $paidAmount += (float)$payment['amount'];
    }

    $outstandingBalance = $totalCharges - $paidAmount;
}

// CSRF token managed via AuthService::generateCsrfToken()

$title = 'Guest Check-Out - Staff Console';
include __DIR__ . '/../../partials/staff-header.php';
?>

      <div class="max-w-3xl mx-auto">
      
      <!-- Back to list link -->
      <div class="mb-6">
        <a href="index.php?route=reservations" class="text-xs font-bold text-brand-600 hover:text-brand-700 transition">
          &larr; Back to Reservations List
        </a>
      </div>

      <!-- Errors/Success notices -->
      <?php if (isset($_GET['error'])): ?>
        <div class="mb-6 rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm font-semibold text-rose-800">
          ❌ <?= htmlspecialchars($_GET['error']) ?>
        </div>
      <?php endif; ?>

      <div class="bg-white rounded-3xl border border-neutral-200 shadow-sm overflow-hidden p-6 md:p-8">
        <div class="border-b border-neutral-100 pb-5 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
          <div>
            <h1 class="text-2xl font-bold text-brand-900 leading-tight">Guest Check-Out & Billing</h1>
            <p class="text-xs text-neutral-500 mt-1">Review bill details, collect payments, and release room for cleaning.</p>
          </div>
          <span class="inline-flex self-start items-center rounded-pill bg-brand-100 px-3 py-1 text-xs font-bold text-brand-800">
            Room <?= htmlspecialchars($booking['room_number']) ?>
          </span>
        </div>

        <!-- Guest & Stay details -->
        <div class="bg-neutral-50 rounded-2xl p-5 mb-8 grid grid-cols-2 md:grid-cols-4 gap-4 border border-neutral-100 text-sm">
          <div>
            <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Guest Name</div>
            <div class="font-bold text-neutral-900 mt-1"><?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?></div>
          </div>
          <div>
            <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Nights Stayed</div>
            <div class="font-semibold text-neutral-700 mt-1"><?= $nights ?> Night<?= $nights > 1 ? 's' : '' ?></div>
          </div>
          <div>
            <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Check-In Date</div>
            <div class="font-semibold text-neutral-700 mt-1"><?= date('M d, Y', strtotime($booking['check_in_date'])) ?></div>
          </div>
          <div>
            <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Check-Out Date</div>
            <div class="font-semibold text-neutral-700 mt-1"><?= date('M d, Y', strtotime($booking['check_out_date'])) ?></div>
          </div>
        </div>

        <!-- Invoice / Billing Sheet -->
        <div class="border border-neutral-200 rounded-3xl overflow-hidden mb-8">
          <div class="bg-neutral-50 px-6 py-4 border-b border-neutral-200 font-bold text-xs uppercase tracking-wider text-neutral-500">
            Folio / Invoice Details
          </div>
          
          <div class="divide-y divide-neutral-100 text-sm">
            <!-- Room Rate Charge -->
            <div class="px-6 py-4 flex justify-between">
              <div>
                <span class="font-semibold text-neutral-800">Room Accommodation</span>
                <p class="text-xs text-neutral-400 mt-0.5"><?= $nights ?> night(s) at $<?= number_format($booking['base_rate'], 2) ?>/night</p>
              </div>
              <span class="font-bold text-neutral-900">$<?= number_format($roomCharges, 2) ?></span>
            </div>

            <!-- Add-ons Charges -->
            <?php if (!empty($addons)): ?>
              <?php foreach ($addons as $addon): 
                $addonTotal = (float)$addon['price'] * (int)$addon['quantity'];
              ?>
                <div class="px-6 py-4 flex justify-between">
                  <div>
                    <span class="font-semibold text-neutral-800"><?= htmlspecialchars($addon['name']) ?></span>
                    <p class="text-xs text-neutral-400 mt-0.5"><?= htmlspecialchars($addon['description']) ?> (Qty: <?= $addon['quantity'] ?>)</p>
                  </div>
                  <span class="font-bold text-neutral-900">$<?= number_format($addonTotal, 2) ?></span>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="px-6 py-4 text-xs text-neutral-400 italic">No add-ons or ancillary services billed.</div>
            <?php endif; ?>

            <!-- Total Billed -->
            <div class="px-6 py-4 flex justify-between bg-neutral-50/50 font-bold">
              <span class="text-neutral-600">Total Billed Charges</span>
              <span class="text-neutral-900 text-lg">$<?= number_format($totalCharges, 2) ?></span>
            </div>

            <!-- Payments Made -->
            <div class="px-6 py-4">
              <span class="font-semibold text-neutral-800">Payments Received</span>
              <?php if (!empty($payments)): ?>
                <div class="mt-2 space-y-2">
                  <?php foreach ($payments as $payment): ?>
                    <div class="flex justify-between text-xs text-emerald-700 bg-emerald-50 rounded-xl px-4 py-2 border border-emerald-100">
                      <span>
                        Paid via <span class="font-bold uppercase"><?= htmlspecialchars($payment['method']) ?></span> 
                        <?= $payment['transaction_reference'] ? '(Ref: ' . htmlspecialchars($payment['transaction_reference']) . ')' : '' ?>
                        on <?= date('M d, H:i', strtotime($payment['paid_at'] ?? $payment['created_at'])) ?>
                      </span>
                      <span class="font-bold">-$<?= number_format($payment['amount'], 2) ?></span>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <p class="text-xs text-neutral-400 italic mt-1">No payments logged yet.</p>
              <?php endif; ?>
            </div>

            <!-- Outstanding Balance -->
            <div class="px-6 py-4 flex justify-between font-bold border-t border-neutral-200 
              <?= $outstandingBalance > 0 ? 'bg-amber-50 text-amber-900' : 'bg-emerald-50 text-emerald-900' ?>">
              <span>Outstanding Balance</span>
              <span class="text-lg">$<?= number_format($outstandingBalance, 2) ?></span>
            </div>
          </div>
        </div>

        <!-- Checkout & Settle Form -->
        <form action="index.php?route=check-out&booking_id=<?= $booking['id'] ?>" method="POST" class="space-y-6">
          <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">

          <?php if ($outstandingBalance > 0): ?>
            <div>
              <h2 class="text-sm font-bold text-neutral-500 uppercase tracking-wider mb-3">Settle Outstanding Bill</h2>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label for="payment_amount" class="block text-xs font-medium text-neutral-500 mb-2">Payment Amount (USD)</label>
                  <input 
                      type="number" 
                      step="0.01" 
                      id="payment_amount" 
                      name="payment_amount" 
                      value="<?= number_format($outstandingBalance, 2, '.', '') ?>" 
                      max="<?= number_format($outstandingBalance, 2, '.', '') ?>" 
                      class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500"
                  >
                </div>
                <div>
                  <label for="payment_method" class="block text-xs font-medium text-neutral-500 mb-2">Payment Method</label>
                  <select id="payment_method" name="payment_method" class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
                    <option value="cash">Cash</option>
                    <option value="card">Credit/Debit Card</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="upi">UPI</option>
                    <option value="wallet">Mobile Wallet</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                <div>
                  <label for="payment_ref" class="block text-xs font-medium text-neutral-500 mb-2">Transaction Reference</label>
                  <input type="text" id="payment_ref" name="payment_ref" placeholder="Ref code, receipt number..." class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
                </div>
              </div>
            </div>
          <?php else: ?>
            <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-5 text-sm font-semibold text-emerald-800 flex items-center gap-2">
              <span>✅</span>
              <span>This booking is fully paid. You can proceed directly to checkout.</span>
            </div>
            <input type="hidden" name="payment_amount" value="0.00">
          <?php endif; ?>

          <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4 text-xs font-semibold text-amber-800">
            💡 **Operations Impact**: Checking out will release Room **<?= htmlspecialchars($booking['room_number']) ?>** back to the available room inventory and automatically create a **Dirty** housekeeping status ticket in `room_status_log`.
          </div>

          <!-- Actions -->
          <div class="flex gap-4 pt-6 border-t border-neutral-100">
            <a href="index.php?route=reservations" class="w-1/2 rounded-2xl bg-neutral-100 py-3.5 text-center text-sm font-bold text-neutral-500 hover:bg-neutral-200 transition">
              Cancel
            </a>
            <button type="submit" class="w-1/2 rounded-2xl bg-brand-500 py-3.5 text-sm font-bold text-white hover:bg-brand-600 transition shadow-sm">
              Confirm Check-Out
            </button>
          </div>
        </form>

      </div>

<?php include __DIR__ . '/../../partials/staff-footer.php'; ?>
