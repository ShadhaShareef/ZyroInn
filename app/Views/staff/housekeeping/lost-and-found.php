<?php
/**
 * lost-and-found.php - Housekeeping Lost & Found Log
 */
$title = "Lost & Found Log";
include __DIR__ . '/../../partials/staff-header.php';
?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8" x-data="{ logModalOpen: false }">
  <div>
    <h1 class="text-2xl font-bold text-brand-900 leading-tight">Lost & Found Log</h1>
    <p class="text-xs text-neutral-500 mt-1">Record found items, update statuses, and match items with guest profiles.</p>
  </div>
  
  <div>
    <button @click="logModalOpen = true" class="inline-flex items-center justify-center rounded-2xl bg-brand-500 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-brand-600 transition">
      ➕ Log Found Item
    </button>
  </div>

  <!-- Log Found Item Bottom Sheet Modal (Alpine.js) -->
  <div x-show="logModalOpen" x-cloak class="fixed inset-0 z-50 flex items-end bg-neutral-900/40" @click.self="logModalOpen = false" @keydown.escape.window="logModalOpen = false">
    <div 
        x-show="logModalOpen"
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
          <h3 class="text-xl font-bold text-brand-900">Log Found Item</h3>
          <p class="text-xs text-neutral-500 mt-1">Record a new found property item.</p>
        </div>
        <button type="button" @click="logModalOpen = false" class="rounded-full bg-neutral-100 p-2 text-neutral-500 hover:bg-neutral-200 transition">
          &times;
        </button>
      </div>

      <form action="index.php?route=lost-and-found" method="POST" class="mt-6 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
        <input type="hidden" name="action" value="create_lost_found">

        <div>
          <label for="item_name" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Item Name</label>
          <input type="text" id="item_name" name="item_name" required placeholder="e.g. Leather Wallet, iPhone 14"
                 class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
        </div>

        <div>
          <label for="description" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Description</label>
          <textarea id="description" name="description" rows="2" placeholder="e.g. Brown leather, contains several cards but no cash..."
                    class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="room_id" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Room Found In</label>
            <select id="room_id" name="room_id" class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
              <option value="">Lobby / Public Area</option>
              <?php foreach ($allRooms as $r): ?>
                <option value="<?= $r['id'] ?>">Room <?= htmlspecialchars($r['room_number']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="found_at" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Date & Time Found</label>
            <input type="datetime-local" id="found_at" name="found_at" value="<?= date('Y-m-d\TH:i') ?>"
                   class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
          </div>
        </div>

        <div>
          <label for="notes" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Location Notes / Details</label>
          <textarea id="notes" name="notes" rows="2" placeholder="e.g. Found under the bedside table on the left side."
                    class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500"></textarea>
        </div>

        <div class="flex gap-3 pt-4 border-t border-neutral-100">
          <button type="button" @click="logModalOpen = false" class="w-1/2 rounded-2xl bg-neutral-100 py-3 text-sm font-bold text-neutral-500 hover:bg-neutral-200 transition">
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

<div class="bg-white rounded-3xl border border-neutral-200 shadow-sm overflow-hidden" x-data="{ activeItem: null, updateModalOpen: false }">
  <div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
      <thead class="bg-neutral-50 text-xs font-bold uppercase tracking-wider text-neutral-400 border-b border-neutral-200">
        <tr>
          <th class="px-6 py-4">Item Details</th>
          <th class="px-6 py-4">Found Location</th>
          <th class="px-6 py-4">Found Date</th>
          <th class="px-6 py-4">Handled By</th>
          <th class="px-6 py-4">Status</th>
          <th class="px-6 py-4 text-right">Action</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-neutral-100">
        <?php if (empty($items)): ?>
          <tr>
            <td colspan="6" class="px-6 py-12 text-center text-neutral-500">
              No lost and found entries logged yet.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($items as $item): ?>
            <tr class="hover:bg-neutral-50/50 transition">
              <td class="px-6 py-4">
                <div class="font-bold text-brand-900"><?= htmlspecialchars($item['item_name']) ?></div>
                <div class="text-xs text-neutral-500 mt-0.5 max-w-xs truncate" title="<?= htmlspecialchars($item['description']) ?>">
                  <?= htmlspecialchars($item['description']) ?>
                </div>
              </td>
              <td class="px-6 py-4 text-xs font-semibold text-neutral-600">
                <?= $item['room_number'] ? 'Room ' . htmlspecialchars($item['room_number']) : 'Lobby / Public Area' ?>
              </td>
              <td class="px-6 py-4 text-xs text-neutral-500">
                <?= date('M d, Y H:i', strtotime($item['found_at'])) ?>
              </td>
              <td class="px-6 py-4 text-xs text-neutral-500">
                <?= htmlspecialchars($item['first_name'] . ' ' . $item['last_name']) ?>
              </td>
              <td class="px-6 py-4">
                <span class="rounded-pill px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider
                  <?= $item['status'] === 'found' ? 'bg-amber-100 text-amber-800' : '' ?>
                  <?= $item['status'] === 'claimed' ? 'bg-emerald-100 text-emerald-800' : '' ?>
                  <?= $item['status'] === 'discarded' ? 'bg-neutral-200 text-neutral-600' : '' ?>
                ">
                  <?= htmlspecialchars($item['status']) ?>
                </span>
              </td>
              <td class="px-6 py-4 text-right text-xs">
                <?php if ($item['status'] === 'found'): ?>
                  <button @click="activeItem = { id: <?= $item['id'] ?>, name: '<?= htmlspecialchars($item['item_name']) ?>', notes: '<?= htmlspecialchars($item['notes'] ?? '') ?>' }; updateModalOpen = true" 
                          class="rounded-xl border border-neutral-200 hover:border-brand-500 px-3 py-1.5 font-semibold text-brand-600 hover:bg-brand-50 transition">
                    Update Status
                  </button>
                <?php else: ?>
                  <span class="text-neutral-400 font-medium">Logged</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Update Status Bottom Sheet -->
  <div x-show="updateModalOpen" x-cloak class="fixed inset-0 z-50 flex items-end bg-neutral-900/40" @click.self="updateModalOpen = false">
    <div 
        x-show="updateModalOpen"
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
          <h3 class="text-xl font-bold text-brand-900">Resolve Lost Item</h3>
          <p class="text-xs text-neutral-500 mt-1">Update status for: <span class="font-extrabold" x-text="activeItem ? activeItem.name : ''"></span></p>
        </div>
        <button type="button" @click="updateModalOpen = false" class="rounded-full bg-neutral-100 p-2 text-neutral-500 hover:bg-neutral-200 transition">
          &times;
        </button>
      </div>

      <form action="index.php?route=lost-and-found" method="POST" class="mt-6 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
        <input type="hidden" name="action" value="update_lost_found_status">
        <input type="hidden" name="id" :value="activeItem ? activeItem.id : 0">

        <div>
          <label class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Resolution Status</label>
          <div class="grid grid-cols-2 gap-3">
            <label class="flex items-center justify-center p-3 rounded-2xl border text-sm font-semibold cursor-pointer hover:border-brand-300 transition">
              <input type="radio" name="status" value="claimed" checked class="mr-2 accent-brand-500">
              <span>🎉 Claimed by Guest</span>
            </label>
            <label class="flex items-center justify-center p-3 rounded-2xl border text-sm font-semibold cursor-pointer hover:border-brand-300 transition">
              <input type="radio" name="status" value="discarded" class="mr-2 accent-brand-500">
              <span>🗑️ Discarded</span>
            </label>
          </div>
        </div>

        <div>
          <label for="notes" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Resolution Notes</label>
          <textarea id="notes" name="notes" rows="3" required placeholder="e.g. Returned to guest Maya Patel. Verified identity."
                    class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500"></textarea>
        </div>

        <div class="flex gap-3 pt-4 border-t border-neutral-100">
          <button type="button" @click="updateModalOpen = false" class="w-1/2 rounded-2xl bg-neutral-100 py-3 text-sm font-bold text-neutral-500 hover:bg-neutral-200 transition">
            Cancel
          </button>
          <button type="submit" class="w-1/2 rounded-2xl bg-brand-500 py-3 text-sm font-bold text-white hover:bg-brand-600 transition shadow-sm">
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../partials/staff-footer.php'; ?>
