<?php
use App\Services\AuthService;
$booking = $booking ?? [];
$groupedServices = $groupedServices ?? [];
$bookingSuccess = $bookingSuccess ?? null;
$bookingError = $bookingError ?? null;
$services = $services ?? [];
$fnbCategories = ['starter', 'main_course', 'dessert', 'beverage'];
$existingOrders = $existingOrders ?? [];
$propertyId = $propertyId ?? 0;

// Build display sections, grouping related categories together
$sectionDefs = [
    'room_service' => ['label' => 'Room Service', 'categories' => ['starter', 'main_course', 'dessert', 'beverage'], 'accent' => '#6C2BD9'],
    'laundry'      => ['label' => 'Laundry',      'categories' => ['laundry'],      'accent' => '#3B82F6'],
    'spa_wellness' => ['label' => 'Spa & Massage', 'categories' => ['spa', 'wellness'], 'accent' => '#10B981'],
    'fitness'      => ['label' => 'Fitness',       'categories' => ['fitness'],      'accent' => '#F59E0B'],
    'activities'   => ['label' => 'Activities',    'categories' => ['activity'],     'accent' => '#EF4444'],
    'transport'    => ['label' => 'Transport',     'categories' => ['transport'],    'accent' => '#14B8A6'],
];

$subLabels = [
    'starter' => 'Starters', 'main_course' => 'Main Course', 'dessert' => 'Desserts', 'beverage' => 'Beverages',
];

$sectionData = [];
foreach ($sectionDefs as $key => $def) {
    $items = [];
    foreach ($def['categories'] as $cat) {
        if (!empty($groupedServices[$cat])) {
            $items = array_merge($items, $groupedServices[$cat]);
        }
    }
    if (!empty($items)) {
        $sectionData[$key] = ['label' => $def['label'], 'accent' => $def['accent'], 'categories' => $def['categories'], 'items' => $items];
    }
}

// Status label map
$statusLabels = ['pending' => 'Received', 'preparing' => 'Preparing', 'delivered' => 'On the way', 'cancelled' => 'Cancelled'];
$statusSteps = ['pending' => 0, 'preparing' => 1, 'delivered' => 2];

// Build menu items JSON for Alpine lookups (F&B items only)
$fnbItems = array_values(array_filter($services, function($s) {
    return in_array($s['category'], ['starter', 'main_course', 'dessert', 'beverage']);
}));
$fnbItemsJson = htmlspecialchars(json_encode($fnbItems), ENT_QUOTES, 'UTF-8');
?>
<div id="fnb-items-data" style="display:none;"><?= $fnbItemsJson ?></div>
<?php
$title = 'Book Services - ZyroInn';
$badge = 'Services';
include __DIR__ . '/../partials/guest-header.php';
?>

    <main class="main-content-narrow">

      <section class="card">
        <div class="p-4">
          <p class="text-tiny" style="color:var(--neutral-400);">Booking at</p>
          <h2 class="text-h3 mt-1"><?= htmlspecialchars($booking['property_name'] ?? '') ?></h2>
          <div class="flex items-center gap-4 mt-2">
            <span class="text-small" style="color:var(--neutral-500);">Room <?= htmlspecialchars($booking['room_number'] ?? '') ?></span>
            <span class="text-small" style="color:var(--neutral-500);"><?= date('M d', strtotime($booking['check_in_date'])) ?> – <?= date('M d', strtotime($booking['check_out_date'])) ?></span>
          </div>
        </div>
      </section>

      <?php if ($bookingSuccess): ?>
        <div class="alert alert-success"><?= $bookingSuccess ?></div>
      <?php endif; ?>

      <?php if ($bookingError): ?>
        <div class="alert alert-error"><?= htmlspecialchars($bookingError) ?></div>
      <?php endif; ?>

      <?php if (!empty($existingOrders)): ?>
        <?php foreach ($existingOrders as $eo): ?>
          <?php
            $step = $statusSteps[$eo['status']] ?? 0;
            $label = $statusLabels[$eo['status']] ?? $eo['status'];
          ?>
          <section class="card order-status-card" style="border-left: 4px solid <?= $step >= 1 ? '#6C2BD9' : ($step >= 2 ? '#10B981' : '#F59E0B') ?>;">
            <div class="p-4">
              <div class="flex items-center justify-between">
                <h3 class="text-h4 mb-0">Order #<?= (int)$eo['id'] ?></h3>
                <span class="status-badge status-<?= htmlspecialchars($eo['status']) ?>"><?= htmlspecialchars($label) ?></span>
              </div>
              <div class="order-status-track">
                <?php $stati = ['pending' => 'Received', 'preparing' => 'Preparing', 'delivered' => 'On the way']; $idx = 0; ?>
                <?php foreach ($stati as $skey => $slabel): ?>
                  <div class="status-step <?= $idx <= $step ? 'status-step--active' : '' ?>">
                    <div class="status-step-dot"></div>
                    <span class="status-step-label"><?= htmlspecialchars($slabel) ?></span>
                  </div>
                  <?php $idx++; ?>
                <?php endforeach; ?>
              </div>
              <?php if (!empty($eo['items'])): ?>
                <div class="text-small mt-2" style="color:var(--neutral-500);">
                  <?php foreach ($eo['items'] as $oi): ?>
                    <div><?= htmlspecialchars($oi['name']) ?> × <?= (int)$oi['quantity'] ?></div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              <p class="text-tiny mt-2" style="color:var(--neutral-400);">Total: $<?= number_format((float)$eo['total_amount'], 2) ?></p>
            </div>
          </section>
        <?php endforeach; ?>
      <?php endif; ?>

      <?php if (empty($sectionData)): ?>
        <div class="empty-state">
          <div class="empty-state-icon"></div>
          <h3 class="empty-state-title">No Services Available</h3>
          <p class="empty-state-text">This property has not added any bookable services yet.</p>
        </div>
      <?php else: ?>

      <div x-data="{
        orderItems: [],
        orderNotes: '',
        isPlacing: false,
        showReview: false,
        showConfirmation: false,
        placedOrderId: null,
        addedItemId: null,
        activeSection: null,
        get trayCount() { return this.orderItems.length; },
        get trayTotal() { return this.orderItems.reduce(function(s, i) { return s + parseFloat(i.price); }, 0).toFixed(2); },
        isAdded(id) { return this.orderItems.some(function(i) { return i.id === id; }); },
        getMenuItems() {
          var el = document.getElementById('fnb-items-data');
          if (!el) return [];
          try { return JSON.parse(el.textContent); } catch(e) { return []; }
        },
        addItem(id) {
          if (this.isAdded(id)) return;
          var items = this.getMenuItems();
          var found = null;
          for (var i = 0; i < items.length; i++) { if (Number(items[i].id) === Number(id)) { found = items[i]; break; } }
          if (!found) return;
          this.orderItems.push({ id: Number(found.id), name: found.name, price: parseFloat(found.price) });
          this.addedItemId = id;
          var self = this;
          setTimeout(function() { self.addedItemId = null; }, 2000);
        },
        removeItem(index) { this.orderItems.splice(index, 1); },
        openReview() { this.showReview = true; document.body.style.overflow = 'hidden'; },
        closeReview() { this.showReview = false; document.body.style.overflow = ''; },
        placeOrder() {
          if (this.orderItems.length === 0 || this.isPlacing) return;
          this.isPlacing = true;
          var self = this;
          var formData = new FormData();
          formData.append('csrf_token', '<?= AuthService::generateCsrfToken() ?>');
          formData.append('booking_id', '<?= (int)$booking['id'] ?>');
          formData.append('property_id', '<?= (int)$propertyId ?>');
          formData.append('items', JSON.stringify(this.orderItems.map(function(i) { return i.id; })));
          formData.append('notes', this.orderNotes);
          fetch('index.php?route=place_order', { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
              if (data.success) {
                self.placedOrderId = data.order_id;
                self.showReview = false;
                self.showConfirmation = true;
                document.body.style.overflow = 'hidden';
              } else {
                alert(data.error || 'Failed to place order.');
                self.isPlacing = false;
              }
            })
            .catch(function() { alert('Network error. Please try again.'); self.isPlacing = false; });
        },
        dismissConfirmation() {
          this.showConfirmation = false;
          this.orderItems = [];
          this.orderNotes = '';
          this.placedOrderId = null;
          document.body.style.overflow = '';
        }
      }">
        <div class="services-sections">
          <?php foreach ($sectionData as $sk => $sec): ?>
            <?php $isRoomSvc = ($sk === 'room_service'); ?>
            <div class="services-section">
              <button @click="activeSection = activeSection === '<?= $sk ?>' ? null : '<?= $sk ?>'" class="services-section-header" style="--section-accent: <?= $sec['accent'] ?>">
                <span class="services-section-label"><?= $sec['label'] ?></span>
                <span class="services-section-meta">
                  <span class="services-section-count"><?= count($sec['items']) ?> <?= count($sec['items']) === 1 ? 'item' : 'items' ?></span>
                  <span class="services-section-arrow" :class="{ 'is-open': activeSection === '<?= $sk ?>' }">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M6 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </span>
                </span>
              </button>

              <div x-show="activeSection === '<?= $sk ?>'" x-cloak x-transition.duration.200ms class="services-section-body">
                <div class="services-items">
                  <?php if ($isRoomSvc): ?>
                    <?php $hasItems = false; ?>
                    <?php foreach ($sec['categories'] as $cat): ?>
                      <?php if (!empty($groupedServices[$cat])): $hasItems = true; ?>
                        <h4 class="svc-category-heading"><?= htmlspecialchars($subLabels[$cat] ?? ucfirst($cat)) ?></h4>
                        <?php foreach ($groupedServices[$cat] as $svc): ?>
                          <div class="service-card room-service-card">
                            <div class="service-card-body">
                              <div class="service-card-row">
                                <div style="flex:1; min-width:0; padding-right:var(--space-3);">
                                  <h4 class="service-card-name"><?= htmlspecialchars($svc['name']) ?></h4>
                                  <p class="service-card-desc"><?= htmlspecialchars($svc['description'] ?? '') ?></p>
                                  <div class="service-card-meta">
                                    <span class="service-card-price">$<?= number_format((float)$svc['price'], 2) ?></span>
                                  </div>
                                </div>
                                <button @click="addItem(<?= (int)$svc['id'] ?>)"
                                        :disabled="isAdded(<?= (int)$svc['id'] ?>)"
                                        :class="isAdded(<?= (int)$svc['id'] ?>) ? 'btn-success btn-sm' : 'btn-primary btn-sm'"
                                        class="btn btn-sm">
                                  <span x-show="!isAdded(<?= (int)$svc['id'] ?>)">Add</span>
                                  <span x-show="isAdded(<?= (int)$svc['id'] ?>)">Added ✓</span>
                                </button>
                              </div>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (!$hasItems): ?>
                      <div class="empty-state" style="padding:var(--space-6) 0;">
                        <h3 class="empty-state-title">Browse the Menu</h3>
                        <p class="empty-state-text">No food & beverage items available right now. Check back later or explore other services.</p>
                      </div>
                    <?php else: ?>
                      <div class="tray-prompt" x-show="trayCount === 0" x-cloak>
                        <p>Browse the menu and tap <strong>Add</strong> to start your order. Items will appear in the tray at the bottom.</p>
                      </div>
                    <?php endif; ?>
                  <?php else: ?>
                    <?php foreach ($sec['items'] as $svc): ?>
                      <?php include __DIR__ . '/_service_card.php'; ?>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Step 1: Persistent order tray (always visible) -->
        <div class="order-tray" @click="openReview()" role="button" tabindex="0" aria-label="View order">
          <div class="order-tray-inner">
            <span class="order-tray-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            </span>
            <span class="order-tray-label">
              <span x-text="trayCount"></span> item<span x-show="trayCount !== 1">s</span>
            </span>
            <span class="order-tray-divider" x-show="trayCount > 0">·</span>
            <span class="order-tray-total" x-show="trayCount > 0">$<span x-text="trayTotal"></span></span>
            <span class="order-tray-placeholder" x-show="trayCount === 0">Tap Add to start your order</span>
            <span class="order-tray-arrow">→</span>
          </div>
          <div class="order-tray-badge" x-show="trayCount > 0" x-text="trayCount"></div>
        </div>

        <!-- Step 2: Transient add-confirmation toast -->
        <div class="order-toast" x-show="addedItemId !== null" x-cloak x-transition.duration.200ms>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          Added to order
        </div>

        <!-- Step 4: Order review bottom sheet -->
        <div class="sheet-backdrop" x-show="showReview" x-cloak x-transition:enter="sheet-enter" x-transition:enter-start="sheet-enter-start" x-transition:enter-end="sheet-enter-end" x-transition:leave="sheet-leave" x-transition:leave-start="sheet-leave-start" x-transition:leave-end="sheet-leave-end" @keydown.escape.window="closeReview()">
          <div class="sheet-backdrop-inner" @click="closeReview()"></div>
          <div class="sheet-panel review-panel" @click.stop role="dialog" aria-modal="true" aria-label="Review your order">
            <div class="sheet-header">
              <div>
                <h3 class="text-h3" style="margin-bottom:0;">Your Order</h3>
                <p class="review-room-confirm">Room <?= htmlspecialchars($booking['room_number'] ?? '') ?></p>
              </div>
              <button type="button" @click="closeReview()" class="sheet-close"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
            </div>

            <div class="review-body">
              <div class="review-items" x-show="orderItems.length > 0">
                <template x-for="(item, index) in orderItems" :key="item.id">
                  <div class="review-item">
                    <div class="review-item-info">
                      <span class="review-item-name" x-text="item.name"></span>
                      <span class="review-item-price" x-text="'$' + parseFloat(item.price).toFixed(2)"></span>
                    </div>
                    <button type="button" class="review-item-remove" @click="removeItem(index)" aria-label="Remove item">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                  </div>
                </template>
              </div>

              <div class="review-empty" x-show="orderItems.length === 0">
                <div class="review-empty-icon">
                  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
                </div>
                <p>Your tray is empty.</p>
                <p class="review-empty-hint">Browse the menu and tap <strong>Add</strong> to add items.</p>
              </div>

              <div class="review-summary" x-show="orderItems.length > 0">
                <div class="review-summary-row">
                  <span class="review-summary-label">Items</span>
                  <span class="review-summary-value" x-text="trayCount"></span>
                </div>
                <div class="review-summary-row review-summary-total">
                  <span class="review-summary-label">Total</span>
                  <span class="review-summary-value">$<span x-text="trayTotal"></span></span>
                </div>
              </div>

              <div class="review-notes">
                <label class="input-label" for="order-notes">Special Requests</label>
                <textarea id="order-notes" x-model="orderNotes" rows="2" placeholder="e.g. No onions, extra napkins..." class="input"></textarea>
              </div>
            </div>

            <div class="review-footer">
              <button type="button" class="btn btn-primary btn-full" @click="placeOrder()" :disabled="orderItems.length === 0 || isPlacing" x-text="isPlacing ? 'Placing Order...' : 'Place Order — $' + trayTotal"></button>
            </div>
          </div>
        </div>

        <!-- Step 5: Order confirmation overlay -->
        <div class="sheet-backdrop" x-show="showConfirmation" x-cloak x-transition:enter="sheet-enter" x-transition:enter-start="sheet-enter-start" x-transition:enter-end="sheet-enter-end">
          <div class="sheet-backdrop-inner"></div>
          <div class="sheet-panel confirmation-panel" @click.stop role="dialog" aria-modal="true" aria-label="Order confirmed">
            <div class="confirmation-icon">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#2EBF8F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <h3 class="text-h2 confirmation-title">Order Placed!</h3>
            <p class="confirmation-subtitle">Order #<strong x-text="placedOrderId"></strong></p>
            <p class="confirmation-detail">Delivering to <strong>Room <?= htmlspecialchars($booking['room_number'] ?? '') ?></strong></p>
            <p class="confirmation-detail">Your items are being prepared.</p>
            <div style="margin-top:var(--space-6); display:flex; flex-direction:column; gap:var(--space-3);">
              <button type="button" class="btn btn-primary btn-full" @click="dismissConfirmation()">Continue Browsing</button>
              <a href="index.php?route=bookings" class="btn btn-ghost btn-full">Back to My Bookings</a>
            </div>
          </div>
        </div>
      </div>

        <div class="text-center pt-2">
          <a href="index.php?route=bookings" class="btn btn-ghost btn-sm">← Back to My Bookings</a>
        </div>

      <?php endif; ?>

    </main>

    <?php include __DIR__ . '/../partials/site-footer.php'; ?>
    <?php
    $items = $navItems ?? [];
    include __DIR__ . '/../partials/bottom-tab-bar.php';
    ?>
<?php include __DIR__ . '/../partials/guest-footer.php'; ?>