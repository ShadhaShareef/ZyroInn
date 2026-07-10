<?php
/**
 * orders.php - F&B Unified Order Queue View
 */
$title = "F&B Order Queue";
include __DIR__ . '/../../partials/staff-header.php';
?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8" x-data="{ placeOrderOpen: false }">
  <div>
    <h1 class="text-2xl font-bold text-brand-900 leading-tight">Food & Beverage Order Queue</h1>
    <p class="text-xs text-neutral-500 mt-1">Manage orders, room service, and service bookings.</p>
  </div>
  
  <div>
    <button @click="placeOrderOpen = true" class="inline-flex items-center justify-center rounded-2xl bg-brand-500 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-brand-600 transition">
      🍽️ Create Order
    </button>
  </div>

  <!-- Create Order Modal -->
  <div x-show="placeOrderOpen" x-cloak class="fixed inset-0 z-50 flex items-end bg-neutral-900/40" @click.self="placeOrderOpen = false" @keydown.escape.window="placeOrderOpen = false">
    <div 
        x-show="placeOrderOpen"
        x-transition:enter="transition duration-300 ease-out transform"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition duration-200 ease-in transform"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="mx-auto w-full max-w-xl rounded-t-3xl bg-white p-6 shadow-2xl focus:outline-none max-h-[85vh] overflow-y-auto"
    >
      <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
        <div>
          <h3 class="text-xl font-bold text-brand-900">Create Order</h3>
          <p class="text-xs text-neutral-500 mt-1">Record a new dining, room service, or service booking.</p>
        </div>
        <button type="button" @click="placeOrderOpen = false" class="rounded-full bg-neutral-100 p-2 text-neutral-500 hover:bg-neutral-200 transition">
          &times;
        </button>
      </div>

      <form action="index.php?route=fnb-orders" method="POST" class="mt-6 space-y-4" x-data="{ type: 'restaurant' }">
        <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
        <input type="hidden" name="action" value="create_fnb_order">

        <div>
          <label class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Order Type</label>
          <div class="grid grid-cols-3 gap-3">
            <label class="flex items-center justify-center p-3 rounded-2xl border text-sm font-semibold cursor-pointer hover:border-brand-300 transition">
              <input type="radio" name="order_type" value="restaurant" x-model="type" class="mr-2 accent-brand-500">
              <span>Restaurant</span>
            </label>
            <label class="flex items-center justify-center p-3 rounded-2xl border text-sm font-semibold cursor-pointer hover:border-brand-300 transition">
              <input type="radio" name="order_type" value="room_service" x-model="type" class="mr-2 accent-brand-500">
              <span>Room Service</span>
            </label>
            <label class="flex items-center justify-center p-3 rounded-2xl border text-sm font-semibold cursor-pointer hover:border-brand-300 transition">
              <input type="radio" name="order_type" value="service" x-model="type" class="mr-2 accent-brand-500">
              <span>Service</span>
            </label>
          </div>
        </div>

        <!-- Dynamic inputs based on type -->
        <div x-show="type === 'room_service' || type === 'service'">
          <label for="booking_id" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Linked Booking / Room</label>
          <select id="booking_id" name="booking_id" :required="type === 'room_service'" class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
            <option value="">-- Select Booking --</option>
            <?php foreach ($activeBookings as $bk): ?>
              <option value="<?= $bk['id'] ?>">Room <?= htmlspecialchars($bk['room_number']) ?> (<?= htmlspecialchars($bk['first_name'] . ' ' . $bk['last_name']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div x-show="type === 'restaurant'">
          <label for="table_number" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Table Number</label>
          <input type="text" id="table_number" name="table_number" :required="type === 'restaurant'" placeholder="e.g. Table 5, Bar Counter"
                 class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
        </div>

        <div x-show="type === 'service'">
          <label for="scheduled_at" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Scheduled Date & Time</label>
          <input type="datetime-local" id="scheduled_at" name="scheduled_at" :required="type === 'service'"
                 class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
        </div>

        <!-- Menu items selections -->
        <div>
          <label class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Items / Services</label>
          <div class="space-y-2 border border-neutral-100 rounded-2xl p-4 bg-neutral-50 max-h-48 overflow-y-auto">
            <?php if (empty($availableMenu)): ?>
              <p class="text-xs text-neutral-400 italic">No available items. Go to Menu Editor to add some.</p>
            <?php else: ?>
              <?php foreach ($availableMenu as $item): 
                $isService = in_array($item['category'], ['spa', 'wellness', 'fitness', 'activity', 'transport']);
              ?>
                <div class="flex items-center justify-between text-xs font-semibold py-1.5 border-b border-neutral-200/50 last:border-b-0" x-show="
                  type === 'service' ? <?= json_encode($isService) ?> :
                  type !== 'service' ? <?= json_encode(!$isService) ?> : true
                ">
                  <div class="text-neutral-700">
                    <?= htmlspecialchars($item['name']) ?>
                    <span class="text-brand-600 ml-1">$<?= number_format($item['price'], 2) ?></span>
                    <?php if (!empty($item['duration_minutes'])): ?>
                      <span class="text-neutral-400 ml-1">(<?= (int)$item['duration_minutes'] ?>min)</span>
                    <?php endif; ?>
                  </div>
                  <input type="number" name="items[<?= $item['id'] ?>]" min="0" value="0" placeholder="0"
                         class="w-16 rounded-xl border border-neutral-200 p-1 text-center focus:outline-none focus:border-brand-500">
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <div>
          <label for="notes" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Notes</label>
          <textarea id="notes" name="notes" rows="2" placeholder="e.g. Allergies, special requests, delivery instructions..."
                    class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500"></textarea>
        </div>

        <div class="flex gap-3 pt-4 border-t border-neutral-100">
          <button type="button" @click="placeOrderOpen = false" class="w-1/2 rounded-2xl bg-neutral-100 py-3 text-sm font-bold text-neutral-500 hover:bg-neutral-200 transition">
            Cancel
          </button>
          <button type="submit" class="w-1/2 rounded-2xl bg-brand-500 py-3 text-sm font-bold text-white hover:bg-brand-600 transition shadow-sm">
            Place Order
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Toolbar: Filter Order Queue -->
<div class="bg-white p-4 rounded-3xl border border-neutral-200 shadow-sm mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
  <div class="flex items-center gap-2 flex-wrap">
    <span class="text-xs font-bold text-neutral-400 uppercase tracking-widest mr-2">Filter Type:</span>
    <a href="index.php?route=fnb-orders&status=<?= htmlspecialchars($statusFilter) ?>" 
       class="px-4 py-2 rounded-pill text-xs font-semibold <?= $typeFilter === '' ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-500 hover:bg-neutral-200' ?> transition">
      All
    </a>
    <a href="index.php?route=fnb-orders&order_type=room_service&status=<?= htmlspecialchars($statusFilter) ?>" 
       class="px-4 py-2 rounded-pill text-xs font-semibold <?= $typeFilter === 'room_service' ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-500 hover:bg-neutral-200' ?> transition">
      Room Service
    </a>
    <a href="index.php?route=fnb-orders&order_type=restaurant&status=<?= htmlspecialchars($statusFilter) ?>" 
       class="px-4 py-2 rounded-pill text-xs font-semibold <?= $typeFilter === 'restaurant' ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-500 hover:bg-neutral-200' ?> transition">
      Restaurant
    </a>
    <a href="index.php?route=fnb-orders&order_type=service&status=<?= htmlspecialchars($statusFilter) ?>" 
       class="px-4 py-2 rounded-pill text-xs font-semibold <?= $typeFilter === 'service' ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-500 hover:bg-neutral-200' ?> transition">
      Services
    </a>
  </div>

  <div class="flex items-center gap-2 flex-wrap border-t md:border-t-0 pt-3 md:pt-0">
    <span class="text-xs font-bold text-neutral-400 uppercase tracking-widest mr-2">Status:</span>
    <a href="index.php?route=fnb-orders&order_type=<?= htmlspecialchars($typeFilter) ?>" 
       class="px-3 py-1.5 rounded-xl text-xs font-semibold <?= $statusFilter === '' ? 'border-brand-500 text-brand-700 bg-brand-50' : 'border-neutral-200 text-neutral-500 bg-white hover:border-neutral-300' ?> border transition">
      All
    </a>
    <a href="index.php?route=fnb-orders&order_type=<?= htmlspecialchars($typeFilter) ?>&status=pending" 
       class="px-3 py-1.5 rounded-xl text-xs font-semibold <?= $statusFilter === 'pending' ? 'border-brand-500 text-brand-700 bg-brand-50' : 'border-neutral-200 text-neutral-500 bg-white hover:border-neutral-300' ?> border transition">
      Pending
    </a>
    <a href="index.php?route=fnb-orders&order_type=<?= htmlspecialchars($typeFilter) ?>&status=preparing" 
       class="px-3 py-1.5 rounded-xl text-xs font-semibold <?= $statusFilter === 'preparing' ? 'border-brand-500 text-brand-700 bg-brand-50' : 'border-neutral-200 text-neutral-500 bg-white hover:border-neutral-300' ?> border transition">
      Preparing
    </a>
    <a href="index.php?route=fnb-orders&order_type=<?= htmlspecialchars($typeFilter) ?>&status=delivered" 
       class="px-3 py-1.5 rounded-xl text-xs font-semibold <?= $statusFilter === 'delivered' ? 'border-brand-500 text-brand-700 bg-brand-50' : 'border-neutral-200 text-neutral-500 bg-white hover:border-neutral-300' ?> border transition">
      Delivered
    </a>
  </div>
</div>

<!-- Order Cards Grid -->
<?php if (empty($orders)): ?>
  <div class="text-center py-16 bg-white rounded-3xl border border-neutral-200 shadow-sm max-w-md mx-auto">
    <p class="text-3xl mb-4">🍽️</p>
    <p class="text-neutral-700 font-bold text-base mb-1">No Orders Found</p>
    <p class="text-neutral-500 text-xs px-6">There are no orders matching your selected filters in the queue.</p>
  </div>
<?php else: ?>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($orders as $order): 
      $status = $order['status'];
      $type = $order['order_type'];
    ?>
      <?php
        $isService = $type === 'service';
        $serviceLabel = $isService ? '💆 Service Booking' : ($type === 'room_service' ? '🛌 Room ' . htmlspecialchars($order['room_number']) : '🍽️ ' . htmlspecialchars($order['table_number']));
        $statusLabel = match ($status) {
          'pending' => $isService ? 'Confirmed' : 'Pending',
          'preparing' => $isService ? 'In Progress' : 'Preparing',
          'delivered' => 'Completed',
          default => ucfirst($status)
        };
      ?>
      <div class="bg-white rounded-3xl p-6 shadow-sm border border-neutral-200 hover:border-brand-300 hover:shadow-md transition duration-300 flex flex-col justify-between min-h-[260px]">
        <div>
          <!-- Header -->
          <div class="flex items-start justify-between border-b border-neutral-100 pb-3">
            <div>
              <span class="text-xs font-bold text-neutral-400 uppercase tracking-wider">Order ID: #<?= $order['id'] ?></span>
              <h3 class="text-base font-bold text-brand-900 mt-0.5">
                <?= $serviceLabel ?>
              </h3>
              <?php if ($isService && !empty($order['scheduled_at'])): ?>
                <p class="text-xs text-neutral-500 mt-1">📅 <?= date('M j, Y g:i A', strtotime($order['scheduled_at'])) ?></p>
              <?php endif; ?>
            </div>
            <span class="rounded-pill px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider
              <?= $status === 'pending' ? ($isService ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-rose-100 text-rose-800 border border-rose-200') : '' ?>
              <?= $status === 'preparing' ? 'bg-amber-100 text-amber-800 border border-amber-200' : '' ?>
              <?= $status === 'delivered' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : '' ?>
              <?= $status === 'cancelled' ? 'bg-neutral-200 text-neutral-600' : '' ?>
            ">
              <?= $statusLabel ?>
            </span>
          </div>

          <!-- Items list -->
          <div class="mt-4 space-y-2">
            <span class="block text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Items Ordered</span>
            <ul class="text-xs font-medium text-neutral-600 space-y-1">
              <?php foreach ($order['items'] as $item): ?>
                <li class="flex justify-between">
                  <span><?= (int)$item['quantity'] ?>x <?= htmlspecialchars($item['item_name']) ?></span>
                  <span class="text-neutral-400">$<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <!-- Scheduled time for services -->
          <?php if ($isService && !empty($order['duration_minutes'])): ?>
            <div class="mt-3 text-[11px] text-neutral-500 font-medium">
              ⏱ Duration: <?= (int)$order['duration_minutes'] ?> minutes
            </div>
          <?php endif; ?>

          <!-- Instruction notes -->
          <?php if (!empty($order['notes'])): ?>
            <div class="mt-4 bg-neutral-50 rounded-xl p-3 border border-neutral-100 text-[11px] text-neutral-500 font-medium italic leading-normal">
              📝 <?= htmlspecialchars($order['notes']) ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="mt-6 pt-4 border-t border-neutral-100 space-y-3">
          <div class="flex items-center justify-between text-xs font-bold">
            <span class="text-neutral-400 uppercase tracking-wider">Total amount</span>
            <span class="text-brand-900 text-sm">$<?= number_format($order['total_amount'], 2) ?></span>
          </div>

          <!-- Status advance form -->
          <?php if ($status !== 'delivered' && $status !== 'cancelled'): ?>
            <form action="index.php?route=fnb-orders" method="POST">
              <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
              <input type="hidden" name="action" value="update_order_status">
              <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

              <?php if ($status === 'pending'): ?>
                <?php if ($isService): ?>
                  <button type="submit" name="status" value="preparing" 
                          class="w-full rounded-2xl bg-brand-500 hover:bg-brand-600 text-white font-bold py-2 text-xs transition shadow-sm">
                    ▶️ Start Service
                  </button>
                <?php else: ?>
                  <button type="submit" name="status" value="preparing" 
                          class="w-full rounded-2xl bg-brand-500 hover:bg-brand-600 text-white font-bold py-2 text-xs transition shadow-sm">
                    👨‍🍳 Start Preparing
                  </button>
                <?php endif; ?>
              <?php elseif ($status === 'preparing'): ?>
                <button type="submit" name="status" value="delivered" 
                        class="w-full rounded-2xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2 text-xs transition shadow-sm">
                  <?= $isService ? '✅ Mark Completed' : '🚚 Mark Delivered' ?>
                </button>
              <?php endif; ?>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../../partials/staff-footer.php'; ?>
