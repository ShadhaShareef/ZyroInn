<?php
if (session_status() === PHP_SESSION_NONE) {
    $env = [];
    $envPath = __DIR__ . '/../../../config/env.php';
    if (file_exists($envPath)) {
        $env = require $envPath;
    }
    session_init($env);
}

if (!isset($bookings)) {
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
    
    $search = '';
    $statusFilter = '';
    
    try {
        $stmt = $db->prepare("
            SELECT b.*, r.room_number, r.room_type, g.first_name, g.last_name, g.email, g.phone, g.id_proof_path,
                   bp.floor_preference, bp.bed_type,
                   GROUP_CONCAT(DISTINCT CONCAT(sr.request_type, ':', sr.request_text) SEPARATOR '||') AS special_requests_concat
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            LEFT JOIN guests g ON b.guest_id = g.id
            LEFT JOIN booking_preferences bp ON bp.booking_id = b.id
            LEFT JOIN special_requests sr ON sr.booking_id = b.id
            WHERE b.property_id = ?
            GROUP BY b.id, r.id, g.id, bp.id
            ORDER BY b.check_in_date ASC, b.id DESC
        ");
        $stmt->execute([$propertyId]);
        $bookings = $stmt->fetchAll();

        foreach ($bookings as &$b) {
            $b['special_requests'] = [];
            if (!empty($b['special_requests_concat'])) {
                $parts = explode('||', $b['special_requests_concat']);
                foreach ($parts as $part) {
                    $colonPos = strpos($part, ':');
                    if ($colonPos !== false) {
                        $b['special_requests'][] = [
                            'type' => substr($part, 0, $colonPos),
                            'text' => substr($part, $colonPos + 1),
                        ];
                    }
                }
            }
        }
        unset($b);
    } catch (\PDOException $e) {
        \App\Services\Logger::error('Reservations fallback query failed', ['exception' => $e->getMessage()]);
        $bookings = [];
    }
    
    try {
        $roomStmt = $db->prepare("SELECT id, room_number, room_type FROM rooms WHERE property_id = ? ORDER BY room_number");
        $roomStmt->execute([$propertyId]);
        $allRooms = $roomStmt->fetchAll();
    } catch (\PDOException $e) {
        \App\Services\Logger::error('Reservations rooms fallback query failed', ['exception' => $e->getMessage()]);
        $allRooms = [];
    }
}

// CSRF token managed via AuthService::generateCsrfToken()
?>
<?php
$title = "Reservations & Desk";
$bodyData = 'x-data="{ newBookingOpen: false, confirmWaitlistOpen: false, activeWaitlistBooking: { id: 0, guestName: \'\' } }"';
include __DIR__ . '/../../partials/staff-header.php';
?>

      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl font-bold text-brand-900 leading-tight">Reservations & Front Desk</h1>
          <p class="text-xs text-neutral-500 mt-1">Manage check-ins, check-outs, bookings, and guest waitlists.</p>
        </div>

        <div class="flex items-center gap-2">
          <a href="<?= rtrim(BASE_URL, '/') ?>/guest/index.php?route=property-summary&property_id=<?= (int)$propertyId ?>"
             target="_blank"
             class="inline-flex items-center justify-center rounded-2xl bg-neutral-100 hover:bg-neutral-200 px-4 py-3 text-sm font-bold text-neutral-600 transition">
            📄 Property Summary
          </a>
          <button 
              @click="newBookingOpen = true" 
              class="inline-flex items-center justify-center rounded-2xl bg-brand-500 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-brand-600 transition"
          >
            ➕ New Walk-In Booking
          </button>
        </div>
      </div>

      <!-- Search & Filters Toolbar -->
      <div class="bg-white p-4 rounded-3xl border border-neutral-200 shadow-sm mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <!-- Search Form -->
<?php $search = $search ?? ''; $statusFilter = $statusFilter ?? ''; ?>
        <form method="GET" action="index.php" class="flex items-center gap-2 w-full md:max-w-md">
          <input type="hidden" name="route" value="reservations">
          <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
          <div class="relative w-full">
            <input 
                type="text" 
                name="search" 
                placeholder="Search by guest name, email, or room number..." 
                value="<?= htmlspecialchars($search) ?>"
                class="w-full rounded-2xl border border-neutral-200 pl-4 pr-10 py-2.5 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
            >
            <?php if ($search !== ''): ?>
              <a href="index.php?route=reservations&status=<?= htmlspecialchars($statusFilter) ?>" class="absolute inset-y-0 right-3 flex items-center text-neutral-400 hover:text-neutral-600">
                &times;
              </a>
            <?php endif; ?>
          </div>
          <button type="submit" class="rounded-2xl bg-neutral-100 hover:bg-neutral-200 px-4 py-2.5 text-sm font-bold transition">
            Search
          </button>
        </form>

        <!-- Status Filter Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
          <?php
          $statuses = [
              '' => 'All Bookings',
              'pending' => 'Pending',
              'confirmed' => 'Confirmed',
              'checked_in' => 'Checked In',
              'checked_out' => 'Checked Out',
              'waitlisted' => 'Waitlist ⏳',
              'cancelled' => 'Cancelled'
          ];
          foreach ($statuses as $val => $label):
          ?>
            <a 
                href="index.php?route=reservations&status=<?= htmlspecialchars($val) ?>&search=<?= htmlspecialchars($search) ?>" 
                class="px-4 py-2 rounded-pill text-xs font-semibold whitespace-nowrap transition 
                  <?= $statusFilter === $val ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-500 hover:bg-neutral-200' ?>"
            >
              <?= $label ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Reservations List Table -->
      <div class="bg-white rounded-3xl border border-neutral-200 shadow-sm overflow-hidden">
        <?php if (empty($bookings)): ?>
          <div class="text-center py-16">
            <span class="text-4xl">📭</span>
            <p class="text-neutral-500 font-semibold mt-4">No reservations match the search criteria.</p>
          </div>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-neutral-50 border-b border-neutral-200 text-xs font-bold text-neutral-500 uppercase tracking-wider">
                  <th class="p-6">Guest Info</th>
                  <th class="p-6">Room Assigned</th>
                  <th class="p-6">Dates & Length</th>
                  <th class="p-6">Status</th>
                  <th class="p-6">Identity Verification</th>
                  <th class="p-6">Preferences &amp; Requests</th>
                  <th class="p-6 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-neutral-100 text-sm">
                <?php foreach ($bookings as $b): 
                  $checkInDate = new DateTime($b['check_in_date']);
                  $checkOutDate = new DateTime($b['check_out_date']);
                  $nights = $checkInDate->diff($checkOutDate)->days;
                  if ($nights <= 0) $nights = 1;
                ?>
                  <tr class="hover:bg-neutral-50/50 transition">
                    <!-- Guest info -->
                    <td class="p-6">
                      <div class="font-bold text-neutral-900"><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></div>
                      <div class="text-xs text-neutral-400 mt-0.5"><?= htmlspecialchars($b['email']) ?></div>
                      <div class="text-xs text-neutral-400 mt-0.5"><?= htmlspecialchars($b['phone']) ?></div>
                    </td>
                    
                    <!-- Assigned room -->
                    <td class="p-6">
                      <?php if ($b['status'] === 'waitlisted'): ?>
                        <span class="text-xs font-semibold text-neutral-400 italic">No room (Waitlisted)</span>
                      <?php else: ?>
                        <div class="font-bold text-brand-900">Room <?= htmlspecialchars($b['room_number']) ?></div>
                        <div class="text-xs text-neutral-400 mt-0.5"><?= htmlspecialchars($b['room_type']) ?></div>
                      <?php endif; ?>
                    </td>

                    <!-- Dates -->
                    <td class="p-6">
                      <div class="font-semibold text-neutral-700"><?= $checkInDate->format('M d, Y') ?> &rarr; <?= $checkOutDate->format('M d, Y') ?></div>
                      <div class="text-xs text-neutral-400 mt-0.5"><?= $nights ?> Night<?= $nights > 1 ? 's' : '' ?></div>
                    </td>

                    <!-- Status badge -->
                    <td class="p-6">
                      <?php
                      $status = $b['status'];
                      $type = 'booking';
                      include __DIR__ . '/../../partials/status-badge.php';
                      ?>
                    </td>

                    <!-- ID proof verification status -->
                    <td class="p-6">
                      <?php if (!empty($b['id_proof_path'])): ?>
                        <a 
                            href="index.php?route=view-id&guest_id=<?= $b['guest_id'] ?>" 
                            target="_blank"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:underline"
                        >
                          📎 View Captured ID
                        </a>
                      <?php else: ?>
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600">
                          ⚠️ ID Capture Required
                        </span>
                      <?php endif; ?>
                    </td>

                    <!-- Preferences & Requests -->
                    <td class="p-6">
                      <?php
                      $hasPref = !empty($b['floor_preference']) || !empty($b['bed_type']);
                      $hasReqs = !empty($b['special_requests']);
                      if ($hasPref || $hasReqs):
                      ?>
                        <div class="space-y-1 max-w-[200px]">
                          <?php if ($hasPref): ?>
                            <div class="flex flex-wrap gap-1">
                              <?php if (!empty($b['floor_preference']) && $b['floor_preference'] !== 'any'): ?>
                                <span class="rounded-pill bg-neutral-100 px-2 py-0.5 text-[10px] font-semibold text-neutral-600">Floor: <?= htmlspecialchars($b['floor_preference']) ?></span>
                              <?php endif; ?>
                              <?php if (!empty($b['bed_type']) && $b['bed_type'] !== 'any'): ?>
                                <span class="rounded-pill bg-neutral-100 px-2 py-0.5 text-[10px] font-semibold text-neutral-600">Bed: <?= htmlspecialchars($b['bed_type']) ?></span>
                              <?php endif; ?>
                            </div>
                          <?php endif; ?>
                          <?php if ($hasReqs): ?>
                            <button type="button"
                                    onclick="alert(<?= htmlspecialchars(json_encode(implode("\n", array_map(fn($r) => '• ' . $r['text'], $b['special_requests'])))) ?>)"
                                    class="text-[10px] font-semibold text-brand-600 hover:text-brand-700 underline">
                              <?= count($b['special_requests']) ?> request<?= count($b['special_requests']) > 1 ? 's' : '' ?>
                            </button>
                          <?php endif; ?>
                        </div>
                      <?php else: ?>
                        <span class="text-xs text-neutral-400">—</span>
                      <?php endif; ?>
                    </td>

                    <!-- Actions -->
                    <td class="p-6 text-right space-x-1 whitespace-nowrap">
                      <!-- Confirm Waitlist Action -->
                      <?php if ($b['status'] === 'waitlisted'): ?>
                        <button 
                            @click="activeWaitlistBooking = { id: <?= $b['id'] ?>, guestName: '<?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?>' }; confirmWaitlistOpen = true"
                            class="rounded-xl bg-emerald-500 hover:bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition"
                        >
                          Confirm & Assign Room
                        </button>
                      <?php endif; ?>

                      <!-- Check In Action -->
                      <?php if (in_array($b['status'], ['pending', 'confirmed'])): ?>
                        <a 
                            href="index.php?route=check-in&booking_id=<?= $b['id'] ?>" 
                            class="rounded-xl bg-brand-500 hover:bg-brand-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition"
                        >
                          Check-In
                        </a>
                      <?php endif; ?>

                      <!-- Check Out / Express Check-Out Action -->
                      <?php if ($b['status'] === 'checked_in'):
                        $nights = max(1, (strtotime($b['check_out_date']) - strtotime($b['check_in_date'])) / 86400);
                        $estRoomCharges = $nights * (float)$b['base_rate'];
                        $totalCharges = $estRoomCharges + (float)$b['total_addon_charges'];
                        $balance = $totalCharges - (float)$b['total_paid'];
                      ?>
                        <?php if ($balance <= 0): ?>
                          <a href="index.php?route=express-check-out&booking_id=<?= $b['id'] ?>"
                             class="rounded-xl bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition"
                             onclick="return confirm('Express check-out: close out this booking now? An e-bill will be generated and emailed to the guest.');">
                            Express Check-Out
                          </a>
                        <?php else: ?>
                          <a href="index.php?route=check-out&booking_id=<?= $b['id'] ?>"
                             class="rounded-xl bg-neutral-700 hover:bg-neutral-800 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition">
                            Check-Out
                          </a>
                        <?php endif; ?>
                      <?php endif; ?>

                      <!-- Cancel Reservation Action -->
                      <?php if (in_array($b['status'], ['pending', 'confirmed', 'waitlisted'])): ?>
                        <form action="index.php?route=reservations" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                          <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
                          <input type="hidden" name="action" value="cancel_booking">
                          <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                          <button type="submit" class="rounded-xl bg-rose-50 hover:bg-rose-100 px-3 py-1.5 text-xs font-bold text-rose-600 transition">
                            Cancel
                          </button>
                        </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-neutral-200 py-6 mt-12">
      <div class="mx-auto max-w-7xl px-4 text-center text-xs text-neutral-500">
        &copy; 2026 ZyroInn Hotel Operations Platform. All rights reserved. (Trial Environment)
      </div>
    </footer>
  </div>

  <!-- Create New Reservation Drawer/Modal (Alpine.js) -->
  <div x-show="newBookingOpen" x-cloak class="fixed inset-0 z-50 flex items-end bg-neutral-900/40" @click.self="newBookingOpen = false" @keydown.escape.window="newBookingOpen = false">
    
    <div 
        x-show="newBookingOpen"
        x-transition:enter="transition duration-300 ease-out transform"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition duration-200 ease-in transform"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="mx-auto w-full max-w-2xl rounded-t-3xl bg-white p-6 shadow-2xl focus:outline-none overflow-y-auto max-h-[90vh]"
    >
      <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
        <div>
          <h3 class="text-xl font-bold text-brand-900">Create New Walk-In Booking</h3>
          <p class="text-xs text-neutral-500 mt-1">Book directly at front desk or add to waitlist.</p>
        </div>
        <button type="button" @click="newBookingOpen = false" class="rounded-full bg-neutral-100 p-2 text-neutral-500 hover:bg-neutral-200 transition">
          <span>&times;</span>
        </button>
      </div>

      <form action="index.php?route=reservations" method="POST" class="mt-6 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
        <input type="hidden" name="action" value="create_booking">

        <!-- Guest Details -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="first_name" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">First Name *</label>
            <input type="text" id="first_name" name="first_name" required class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
          </div>
          <div>
            <label for="last_name" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Last Name *</label>
            <input type="text" id="last_name" name="last_name" required class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="email" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Email Address *</label>
            <input type="email" id="email" name="email" required class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
          </div>
          <div>
            <label for="phone" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Phone Number</label>
            <input type="text" id="phone" name="phone" class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
          </div>
        </div>

        <!-- Dates -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="check_in_date" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Check-in Date *</label>
            <input type="date" id="check_in_date" name="check_in_date" required class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
          </div>
          <div>
            <label for="check_out_date" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Check-out Date *</label>
            <input type="date" id="check_out_date" name="check_out_date" required class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
          </div>
        </div>

        <!-- Room Assignment -->
        <div>
          <label for="room_id" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Assign Room *</label>
          <select id="room_id" name="room_id" required class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
            <option value="">Select a room...</option>
            <?php foreach ($allRooms as $room): ?>
              <option value="<?= $room['id'] ?>">Room <?= htmlspecialchars($room['room_number']) ?> - <?= htmlspecialchars($room['room_type']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Booking Status (Direct confirm or Waitlist) -->
        <div>
          <label class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Initial Booking Status</label>
          <div class="grid grid-cols-3 gap-3">
            <label class="flex items-center justify-center p-3 rounded-2xl border border-neutral-200 text-sm font-semibold cursor-pointer hover:border-brand-500 transition">
              <input type="radio" name="status" value="confirmed" checked class="mr-2">
              <span>Confirmed</span>
            </label>
            <label class="flex items-center justify-center p-3 rounded-2xl border border-neutral-200 text-sm font-semibold cursor-pointer hover:border-brand-500 transition">
              <input type="radio" name="status" value="pending" class="mr-2">
              <span>Pending</span>
            </label>
            <label class="flex items-center justify-center p-3 rounded-2xl border border-neutral-200 text-sm font-semibold cursor-pointer hover:border-brand-500 transition text-amber-600">
              <input type="radio" name="status" value="waitlisted" class="mr-2">
              <span>Waitlist ⏳</span>
            </label>
          </div>
        </div>

        <!-- Notes -->
        <div>
          <label for="notes" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Booking Notes</label>
          <textarea id="notes" name="notes" rows="3" placeholder="Add specific preferences or payment details..." class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500"></textarea>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-3 pt-4 border-t border-neutral-100">
          <button type="button" @click="newBookingOpen = false" class="w-1/2 rounded-2xl bg-neutral-100 py-3 text-sm font-bold text-neutral-500 hover:bg-neutral-200 transition">
            Cancel
          </button>
          <button type="submit" class="w-1/2 rounded-2xl bg-brand-500 py-3 text-sm font-bold text-white hover:bg-brand-600 transition shadow-sm">
            Create Booking
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Confirm Waitlisted Booking Modal (Alpine.js) -->
  <div x-show="confirmWaitlistOpen" x-cloak class="fixed inset-0 z-50 flex items-end bg-neutral-900/40" @click.self="confirmWaitlistOpen = false" @keydown.escape.window="confirmWaitlistOpen = false">
    
    <div 
        x-show="confirmWaitlistOpen"
        x-transition:enter="transition duration-300 ease-out transform"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition duration-200 ease-in transform"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="mx-auto w-full max-w-lg rounded-t-3xl bg-white p-6 shadow-2xl focus:outline-none"
    >
      <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
        <div>
          <h3 class="text-xl font-bold text-brand-900">Confirm Waitlist Booking</h3>
          <p class="text-xs text-neutral-500 mt-1">Assign a room for guest <span class="font-semibold text-brand-700" x-text="activeWaitlistBooking.guestName"></span>.</p>
        </div>
        <button type="button" @click="confirmWaitlistOpen = false" class="rounded-full bg-neutral-100 p-2 text-neutral-500 hover:bg-neutral-200 transition">
          <span>&times;</span>
        </button>
      </div>

      <form action="index.php?route=reservations" method="POST" class="mt-6 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
        <input type="hidden" name="action" value="confirm_waitlist">
        <input type="hidden" name="booking_id" :value="activeWaitlistBooking.id">

        <!-- Available clean room select -->
        <div>
          <label for="assign_room_id" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Available Rooms</label>
          <select id="assign_room_id" name="room_id" required class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
            <option value="">Select a room to assign...</option>
            <?php foreach ($allRooms as $room): ?>
              <option value="<?= $room['id'] ?>">Room <?= htmlspecialchars($room['room_number']) ?> - <?= htmlspecialchars($room['room_type']) ?></option>
            <?php endforeach; ?>
          </select>
          <p class="text-[10px] text-neutral-400 mt-2">Note: To avoid check-in issues, it is recommended to assign a clean and vacant room.</p>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-3 pt-4 border-t border-neutral-100">
          <button type="button" @click="confirmWaitlistOpen = false" class="w-1/2 rounded-2xl bg-neutral-100 py-3 text-sm font-bold text-neutral-500 hover:bg-neutral-200 transition">
            Cancel
          </button>
          <button type="submit" class="w-1/2 rounded-2xl bg-brand-500 py-3 text-sm font-bold text-white hover:bg-brand-600 transition shadow-sm">
            Confirm & Assign Room
          </button>
        </div>
      </form>
    </div>
  </div>

<?php include __DIR__ . '/../../partials/staff-footer.php'; ?>
