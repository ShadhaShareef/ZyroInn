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
        $stmtB = $db->prepare("SELECT id FROM bookings WHERE property_id = ? AND status IN ('pending', 'confirmed') LIMIT 1");
        $stmtB->execute([$propertyId]);
        $bookingId = (int)$stmtB->fetchColumn();
    }
    
    if ($bookingId > 0) {
        $stmt = $db->prepare("
            SELECT b.*, r.room_number, r.room_type, g.first_name, g.last_name, g.email, g.phone, g.id as guest_id, g.id_proof_path
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
            'room_id' => 1,
            'check_in_date' => date('Y-m-d'),
            'check_out_date' => date('Y-m-d', strtotime('+3 days')),
            'id_proof_path' => '',
            'guest_id' => 0,
            'status' => 'pending'
        ];
    }
    
    $roomStmt = $db->prepare("
        SELECT r.*, rsl.status AS housekeeping_status
        FROM rooms r
        LEFT JOIN room_status_log rsl ON rsl.id = (
            SELECT id FROM room_status_log
            WHERE room_id = r.id
            ORDER BY changed_at DESC, id DESC
            LIMIT 1
        )
        WHERE r.property_id = ? 
          AND (
            (r.status = 'available' AND rsl.status = 'clean') 
            OR r.id = ?
          )
        ORDER BY r.room_number ASC
    ");
    $roomStmt->execute([$propertyId, $booking['room_id']]);
    $availableCleanRooms = $roomStmt->fetchAll();
}

// CSRF token managed via AuthService::generateCsrfToken()

$title = 'Guest Check-In - Staff Console';
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
        <div class="border-b border-neutral-100 pb-5 mb-6">
          <h1 class="text-2xl font-bold text-brand-900 leading-tight">Guest Check-In</h1>
          <p class="text-xs text-neutral-500 mt-1">Complete room assignment, secure identification capture, and payment receipt.</p>
        </div>

        <!-- Booking details summary -->
        <div class="bg-brand-50/50 rounded-2xl p-5 mb-8 grid grid-cols-2 md:grid-cols-4 gap-4 border border-brand-100 text-sm">
          <div>
            <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Guest Name</div>
            <div class="font-bold text-brand-900 mt-1"><?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?></div>
          </div>
          <div>
            <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Reserved Room Type</div>
            <div class="font-semibold text-neutral-700 mt-1"><?= htmlspecialchars($booking['room_type']) ?></div>
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

        <!-- Check-in form -->
        <form action="index.php?route=check-in&booking_id=<?= $booking['id'] ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
          <!-- CSRF Token -->
          <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">

          <!-- Room assignment section -->
          <div>
            <h2 class="text-sm font-bold text-neutral-500 uppercase tracking-wider mb-3">1. Select Clean & Available Room</h2>
            <label for="room_id" class="block text-xs font-medium text-neutral-500 mb-2">Room Assignment *</label>
            <select id="room_id" name="room_id" required class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
              <option value="">-- Choose from clean rooms only --</option>
              <?php foreach ($availableCleanRooms as $room): ?>
                <option value="<?= $room['id'] ?>" <?= $room['id'] == $booking['room_id'] ? 'selected' : '' ?>>
                  Room <?= htmlspecialchars($room['room_number']) ?> (<?= htmlspecialchars($room['room_type']) ?>) - <?= htmlspecialchars($room['housekeeping_status']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <p class="text-[10px] text-neutral-400 mt-2">Only rooms currently flagged as **Clean** are shown for assignment.</p>
          </div>

          <hr class="border-neutral-100">

          <!-- ID Proof Upload -->
          <div>
            <h2 class="text-sm font-bold text-neutral-500 uppercase tracking-wider mb-3">2. Identity Document Capture</h2>
            <label class="block text-xs font-medium text-neutral-500 mb-2">ID Proof Document (PDF, JPEG, PNG) *</label>
            
            <?php if (!empty($booking['id_proof_path'])): ?>
              <div class="mb-3 rounded-2xl bg-emerald-50 border border-emerald-100 p-4 text-xs font-semibold text-emerald-800 flex items-center justify-between">
                <span>📎 Document already securely uploaded.</span>
                <a href="index.php?route=view-id&guest_id=<?= $booking['guest_id'] ?>" target="_blank" class="text-brand-600 hover:text-brand-700 underline">View current file</a>
              </div>
            <?php endif; ?>

            <div class="flex items-center justify-center w-full">
              <label for="id_proof" class="flex flex-col items-center justify-center w-full h-32 border-2 border-neutral-200 border-dashed rounded-2xl cursor-pointer bg-neutral-50 hover:bg-neutral-100/50 transition">
                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                  <span class="text-2xl mb-2">📁</span>
                  <p class="text-xs text-neutral-500 font-semibold"><span class="text-brand-500">Click to upload ID file</span> or drag & drop</p>
                  <p class="text-[10px] text-neutral-400 mt-1">PDF, PNG, JPG (saved in secure storage)</p>
                </div>
                <input id="id_proof" name="id_proof" type="file" class="hidden" <?= empty($booking['id_proof_path']) ? 'required' : '' ?>>
              </label>
            </div>
            
            <script>
              // Show filename under upload area when selected
              document.getElementById('id_proof').addEventListener('change', function(e) {
                const fileName = e.target.files[0]?.name;
                if (fileName) {
                  const label = e.target.closest('label');
                  label.querySelector('p.text-neutral-500').innerHTML = `Selected: <span class="font-bold text-brand-600">${fileName}</span>`;
                }
              });
            </script>
          </div>

          <hr class="border-neutral-100">

          <!-- Initial Payment capture -->
          <div>
            <h2 class="text-sm font-bold text-neutral-500 uppercase tracking-wider mb-3">3. Initial Payment (Optional)</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label for="payment_amount" class="block text-xs font-medium text-neutral-500 mb-2">Amount Paid (USD)</label>
                <input type="number" step="0.01" id="payment_amount" name="payment_amount" value="0.00" class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
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
                <input type="text" id="payment_ref" name="payment_ref" placeholder="Txn ID, cash receipt number..." class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex gap-4 pt-6 border-t border-neutral-100">
            <a href="index.php?route=reservations" class="w-1/2 rounded-2xl bg-neutral-100 py-3.5 text-center text-sm font-bold text-neutral-500 hover:bg-neutral-200 transition">
              Cancel
            </a>
            <button type="submit" class="w-1/2 rounded-2xl bg-brand-500 py-3.5 text-sm font-bold text-white hover:bg-brand-600 transition shadow-sm">
              Confirm Check-In
            </button>
          </div>
        </form>

      </div>

<?php include __DIR__ . '/../../partials/staff-footer.php'; ?>
