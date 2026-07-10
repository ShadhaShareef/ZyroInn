<?php
/**
 * inventory.php - Housekeeping Linen & Amenity Inventory Log
 */
$title = "Linen & Amenity Inventory";
include __DIR__ . '/../../partials/staff-header.php';
?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8" x-data="{ logItemOpen: false }">
  <div>
    <h1 class="text-2xl font-bold text-brand-900 leading-tight">Linen & Amenity Inventory</h1>
    <p class="text-xs text-neutral-500 mt-1">Track linen counts and guest amenities. Restock warnings are triggered automatically when counts fall below safety thresholds.</p>
  </div>
  
  <div>
    <button @click="logItemOpen = true" class="inline-flex items-center justify-center rounded-2xl bg-brand-500 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-brand-600 transition">
      ➕ Add New Item
    </button>
  </div>

  <!-- Add New Item Modal -->
  <div x-show="logItemOpen" x-cloak class="fixed inset-0 z-50 flex items-end bg-neutral-900/40" @click.self="logItemOpen = false" @keydown.escape.window="logItemOpen = false">
    <div 
        x-show="logItemOpen"
        x-transition:enter="transition duration-300 ease-out transform"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition duration-200 ease-in transform"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="mx-auto w-full max-w-xl rounded-t-3xl bg-white p-6 shadow-2xl focus:outline-none"
    >
      <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
        <div>
          <h3 class="text-xl font-bold text-brand-900">Add Inventory Item</h3>
          <p class="text-xs text-neutral-500 mt-1">Log a new linen or amenity product to track.</p>
        </div>
        <button type="button" @click="logItemOpen = false" class="rounded-full bg-neutral-100 p-2 text-neutral-500 hover:bg-neutral-200 transition">
          &times;
        </button>
      </div>

      <form action="index.php?route=housekeeping-inventory" method="POST" class="mt-6 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
        <input type="hidden" name="action" value="log_inventory_item">

        <div>
          <label for="item_name" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Item Name</label>
          <input type="text" id="item_name" name="item_name" required placeholder="e.g. Bath Towels, Mini Soap Bars"
                 class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
        </div>

        <div class="grid grid-cols-3 gap-4">
          <div>
            <label for="category" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Category</label>
            <select id="category" name="category" class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
              <option value="linen">Linen</option>
              <option value="amenity">Amenity</option>
              <option value="cleaning_supply">Cleaning Supply</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div>
            <label for="quantity" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Initial Qty</label>
            <input type="number" id="quantity" name="quantity" min="0" value="0"
                   class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
          </div>
          <div>
            <label for="min_required" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Min Required</label>
            <input type="number" id="min_required" name="min_required" min="0" value="10"
                   class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
          </div>
        </div>

        <div class="flex gap-3 pt-4 border-t border-neutral-100">
          <button type="button" @click="logItemOpen = false" class="w-1/2 rounded-2xl bg-neutral-100 py-3 text-sm font-bold text-neutral-500 hover:bg-neutral-200 transition">
            Cancel
          </button>
          <button type="submit" class="w-1/2 rounded-2xl bg-brand-500 py-3 text-sm font-bold text-white hover:bg-brand-600 transition shadow-sm">
            Save Item
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Inventory Cards Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6" x-data="{ editingId: null, editingVal: 0 }">
  <?php foreach ($inventory as $item): 
    $belowMin = $item['quantity'] < $item['min_required'];
  ?>
    <div class="relative bg-white rounded-3xl p-6 shadow-sm border transition duration-300 flex flex-col justify-between min-h-[180px]
      <?= $belowMin ? 'border-rose-300 ring-1 ring-rose-100 bg-rose-50/10' : 'border-neutral-200 hover:border-brand-300 hover:shadow-md' ?>
    ">
      
      <div>
        <div class="flex items-start justify-between">
          <span class="rounded-pill px-2.5 py-0.5 text-[9px] font-bold uppercase tracking-wider
            <?= $item['category'] === 'linen' ? 'bg-indigo-100 text-indigo-800' : '' ?>
            <?= $item['category'] === 'amenity' ? 'bg-amber-100 text-amber-800' : '' ?>
            <?= $item['category'] === 'cleaning_supply' ? 'bg-teal-100 text-teal-800' : '' ?>
            <?= $item['category'] === 'other' ? 'bg-neutral-100 text-neutral-800' : '' ?>
          ">
            <?= htmlspecialchars(str_replace('_', ' ', $item['category'])) ?>
          </span>

          <?php if ($belowMin): ?>
            <span class="rounded-pill bg-rose-100 px-2 py-0.5 text-[9px] font-bold text-rose-800 uppercase tracking-widest border border-rose-200">
              🚨 Restock
            </span>
          <?php endif; ?>
        </div>

        <h3 class="text-base font-bold text-brand-900 mt-3 leading-snug">
          <?= htmlspecialchars($item['item_name']) ?>
        </h3>
      </div>

      <!-- Quantity Management -->
      <div class="mt-6 pt-4 border-t border-neutral-100">
        
        <!-- Quick quantity adjuster -->
        <div class="flex items-center justify-between" x-show="editingId !== <?= $item['id'] ?>">
          <div class="text-xs text-neutral-400">
            Current: <span class="font-bold text-neutral-700 text-sm"><?= $item['quantity'] ?></span>
            <span class="mx-1">/</span>
            Min: <span class="text-neutral-500 font-semibold"><?= $item['min_required'] ?></span>
          </div>

          <div class="flex items-center gap-1.5">
            <!-- Minus button -->
            <form action="index.php?route=housekeeping-inventory" method="POST" class="inline">
              <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
              <input type="hidden" name="action" value="update_inventory_qty">
              <input type="hidden" name="id" value="<?= $item['id'] ?>">
              <input type="hidden" name="quantity" value="<?= max(0, $item['quantity'] - 1) ?>">
              <button type="submit" class="h-8 w-8 rounded-xl bg-neutral-100 hover:bg-neutral-200 text-xs font-bold text-neutral-600 flex items-center justify-center transition">
                &minus;
              </button>
            </form>

            <!-- Edit button -->
            <button @click="editingId = <?= $item['id'] ?>; editingVal = <?= $item['quantity'] ?>" class="text-[10px] font-bold text-brand-600 hover:text-brand-700 mx-1">
              Edit
            </button>

            <!-- Plus button -->
            <form action="index.php?route=housekeeping-inventory" method="POST" class="inline">
              <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
              <input type="hidden" name="action" value="update_inventory_qty">
              <input type="hidden" name="id" value="<?= $item['id'] ?>">
              <input type="hidden" name="quantity" value="<?= $item['quantity'] + 1 ?>">
              <button type="submit" class="h-8 w-8 rounded-xl bg-neutral-100 hover:bg-neutral-200 text-xs font-bold text-neutral-600 flex items-center justify-center transition">
                &plus;
              </button>
            </form>
          </div>
        </div>

        <!-- Inline text edit form -->
        <div x-show="editingId === <?= $item['id'] ?>" class="flex items-center justify-between" x-cloak>
          <form action="index.php?route=housekeeping-inventory" method="POST" class="w-full flex items-center gap-2">
            <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
            <input type="hidden" name="action" value="update_inventory_qty">
            <input type="hidden" name="id" value="<?= $item['id'] ?>">
            
            <input type="number" name="quantity" x-model="editingVal" min="0" 
                   class="w-20 rounded-xl border border-neutral-200 p-1.5 text-xs text-center focus:outline-none focus:border-brand-500">
            
            <button type="submit" class="rounded-xl bg-brand-500 px-3 py-1.5 text-[10px] font-bold text-white hover:bg-brand-600 transition">
              Save
            </button>
            <button type="button" @click="editingId = null" class="text-[10px] font-semibold text-neutral-400 hover:text-neutral-500 px-1">
              Cancel
            </button>
          </form>
        </div>

      </div>

    </div>
  <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../../partials/staff-footer.php'; ?>
