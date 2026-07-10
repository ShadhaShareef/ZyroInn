<?php
/**
 * stock-waste.php - F&B Stock & Waste Log View
 */
$title = "Stock & Waste Log";
include __DIR__ . '/../../partials/staff-header.php';
?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8" x-data="{ logStockOpen: false }">
  <div>
    <h1 class="text-2xl font-bold text-brand-900 leading-tight">Stock & Waste Log</h1>
    <p class="text-xs text-neutral-500 mt-1">Audit kitchen storage levels. Log daily stock intakes, usage, and kitchen food wastes.</p>
  </div>
  
  <div>
    <button @click="logStockOpen = true" class="inline-flex items-center justify-center rounded-2xl bg-brand-500 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-brand-600 transition">
      🍎 Log Stock/Waste
    </button>
  </div>

  <!-- Log Stock/Waste Modal -->
  <div x-show="logStockOpen" x-cloak class="fixed inset-0 z-50 flex items-end bg-neutral-900/40" @click.self="logStockOpen = false" @keydown.escape.window="logStockOpen = false">
    <div 
        x-show="logStockOpen"
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
          <h3 class="text-xl font-bold text-brand-900">Log Inventory Flow</h3>
          <p class="text-xs text-neutral-500 mt-1">Log stock receipt, kitchen consumption, or spoilage/waste.</p>
        </div>
        <button type="button" @click="logStockOpen = false" class="rounded-full bg-neutral-100 p-2 text-neutral-500 hover:bg-neutral-200 transition">
          &times;
        </button>
      </div>

      <form action="index.php?route=fnb-stock-waste" method="POST" class="mt-6 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
        <input type="hidden" name="action" value="log_stock_waste">

        <div>
          <label for="item_name" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Ingredient / Item Name</label>
          <input type="text" id="item_name" name="item_name" required placeholder="e.g. Fresh Tomatoes, Pizza Cheese, Beef Patties"
                 class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
        </div>

        <div class="grid grid-cols-3 gap-4">
          <div>
            <label for="type" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Log Type</label>
            <select id="type" name="type" class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500 font-semibold text-neutral-700">
              <option value="stock_in">🟢 Stock In</option>
              <option value="stock_out">🟡 Stock Out</option>
              <option value="waste">🔴 Waste / Spoil</option>
            </select>
          </div>
          <div>
            <label for="quantity" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Quantity</label>
            <input type="number" id="quantity" name="quantity" step="0.1" min="0.1" required value="1"
                   class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
          </div>
          <div>
            <label for="unit" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Unit</label>
            <input type="text" id="unit" name="unit" required placeholder="e.g. kg, lbs, pieces, liters"
                   class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
          </div>
        </div>

        <div>
          <label for="reason" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Notes / Reason</label>
          <textarea id="reason" name="reason" rows="2" placeholder="e.g. Received weekly fresh delivery, spoiled in fridge room..."
                    class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500"></textarea>
        </div>

        <div class="flex gap-3 pt-4 border-t border-neutral-100">
          <button type="button" @click="logStockOpen = false" class="w-1/2 rounded-2xl bg-neutral-100 py-3 text-sm font-bold text-neutral-500 hover:bg-neutral-200 transition">
            Cancel
          </button>
          <button type="submit" class="w-1/2 rounded-2xl bg-brand-500 py-3 text-sm font-bold text-white hover:bg-brand-600 transition shadow-sm">
            Log Entry
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Stock/Waste Log Spreadsheet -->
<div class="bg-white rounded-3xl border border-neutral-200 shadow-sm overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
      <thead class="bg-neutral-50 text-xs font-bold uppercase tracking-wider text-neutral-400 border-b border-neutral-200">
        <tr>
          <th class="px-6 py-4">Logged Time</th>
          <th class="px-6 py-4">Ingredient / Item</th>
          <th class="px-6 py-4">Transaction</th>
          <th class="px-6 py-4">Quantity</th>
          <th class="px-6 py-4">Reason / Notes</th>
          <th class="px-6 py-4">Logged By</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-neutral-100">
        <?php if (empty($logs)): ?>
          <tr>
            <td colspan="6" class="px-6 py-12 text-center text-neutral-500">
              No stock & waste inventory entries recorded yet.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($logs as $log): ?>
            <tr class="hover:bg-neutral-50/50 transition">
              <td class="px-6 py-4 text-xs text-neutral-500">
                <?= date('M d, Y H:i', strtotime($log['logged_at'])) ?>
              </td>
              <td class="px-6 py-4 font-bold text-brand-900">
                <?= htmlspecialchars($log['item_name']) ?>
              </td>
              <td class="px-6 py-4">
                <span class="rounded-pill px-2.5 py-0.5 text-[9px] font-bold uppercase tracking-wider
                  <?= $log['type'] === 'stock_in' ? 'bg-emerald-100 text-emerald-800' : '' ?>
                  <?= $log['type'] === 'stock_out' ? 'bg-amber-100 text-amber-800' : '' ?>
                  <?= $log['type'] === 'waste' ? 'bg-rose-100 text-rose-800 border border-rose-200' : '' ?>
                ">
                  <?= htmlspecialchars(str_replace('_', ' ', $log['type'])) ?>
                </span>
              </td>
              <td class="px-6 py-4 font-bold text-neutral-700 text-xs">
                <?= htmlspecialchars($log['quantity']) ?> <?= htmlspecialchars($log['unit']) ?>
              </td>
              <td class="px-6 py-4 text-xs text-neutral-500 max-w-xs truncate" title="<?= htmlspecialchars($log['reason']) ?>">
                <?= htmlspecialchars($log['reason'] ?: 'N/A') ?>
              </td>
              <td class="px-6 py-4 text-xs text-neutral-500">
                <?= htmlspecialchars($log['first_name'] . ' ' . $log['last_name']) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../../partials/staff-footer.php'; ?>
