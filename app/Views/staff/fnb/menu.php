<?php
/**
 * menu.php - F&B Menu & Pricing Editor
 */
$title = "Menu & Pricing Editor";
include __DIR__ . '/../../partials/staff-header.php';
?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8" x-data="{ addMenuItemOpen: false }">
  <div>
    <h1 class="text-2xl font-bold text-brand-900 leading-tight">Menu & Pricing Editor</h1>
    <p class="text-xs text-neutral-500 mt-1">Manage food & drink inventory selections, pricing tables, and dining categories.</p>
  </div>
  
  <div>
    <button @click="addMenuItemOpen = true" class="inline-flex items-center justify-center rounded-2xl bg-brand-500 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-brand-600 transition">
      ➕ Add Menu Item
    </button>
  </div>

  <!-- Add Menu Item Modal -->
  <div x-show="addMenuItemOpen" x-cloak class="fixed inset-0 z-50 flex items-end bg-neutral-900/40" @click.self="addMenuItemOpen = false" @keydown.escape.window="addMenuItemOpen = false">
    <div 
        x-show="addMenuItemOpen"
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
          <h3 class="text-xl font-bold text-brand-900">Add Menu Item</h3>
          <p class="text-xs text-neutral-500 mt-1">Create a new item on the hotel menu.</p>
        </div>
        <button type="button" @click="addMenuItemOpen = false" class="rounded-full bg-neutral-100 p-2 text-neutral-500 hover:bg-neutral-200 transition">
          &times;
        </button>
      </div>

      <form action="index.php?route=fnb-menu" method="POST" class="mt-6 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
        <input type="hidden" name="action" value="create_menu_item">

        <div>
          <label for="name" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Item Name</label>
          <input type="text" id="name" name="name" required placeholder="e.g. Classic Margherita Pizza"
                 class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="category" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Category</label>
            <select id="category" name="category" class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
              <option value="starter">Starter</option>
              <option value="main_course">Main Course</option>
              <option value="dessert">Dessert</option>
              <option value="beverage">Beverage</option>
              <option value="other">Other</option>
              <option value="spa">Spa</option>
              <option value="wellness">Wellness</option>
              <option value="fitness">Fitness</option>
              <option value="activity">Activity</option>
              <option value="transport">Transport</option>
            </select>
          </div>
          <div>
            <label for="price" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Price ($)</label>
            <input type="number" id="price" name="price" step="0.01" min="0.00" value="0.00" required
                   class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
          </div>
          <div>
            <label for="duration_minutes" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Duration (min)</label>
            <input type="number" id="duration_minutes" name="duration_minutes" min="0" step="5" placeholder="e.g. 60"
                   class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
          </div>
        </div>

        <div>
          <label for="description" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Description</label>
          <textarea id="description" name="description" rows="2" placeholder="e.g. Fresh tomatoes, garlic, mozzarella, oregano..."
                    class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500"></textarea>
        </div>

        <div>
          <label class="flex items-center gap-2 cursor-pointer mt-2">
            <input type="checkbox" name="available" value="1" checked class="rounded border-neutral-300 text-brand-600 focus:ring-brand-500 h-4 w-4">
            <span class="text-xs font-bold text-neutral-600 uppercase tracking-wider">Item Available for Order</span>
          </label>
        </div>

        <div class="flex gap-3 pt-4 border-t border-neutral-100">
          <button type="button" @click="addMenuItemOpen = false" class="w-1/2 rounded-2xl bg-neutral-100 py-3 text-sm font-bold text-neutral-500 hover:bg-neutral-200 transition">
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

<!-- Menu Items List -->
<div class="bg-white rounded-3xl border border-neutral-200 shadow-sm overflow-hidden" x-data="{ editingItem: null, editModalOpen: false }">
  <div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
      <thead class="bg-neutral-50 text-xs font-bold uppercase tracking-wider text-neutral-400 border-b border-neutral-200">
        <tr>
          <th class="px-6 py-4">Item Name</th>
          <th class="px-6 py-4">Category</th>
          <th class="px-6 py-4">Price</th>
          <th class="px-6 py-4">Availability</th>
          <th class="px-6 py-4 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-neutral-100">
        <?php if (empty($menuItems)): ?>
          <tr>
            <td colspan="5" class="px-6 py-12 text-center text-neutral-500">
              No menu items created.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($menuItems as $item): ?>
            <tr class="hover:bg-neutral-50/50 transition">
              <td class="px-6 py-4">
                <div class="font-bold text-brand-900"><?= htmlspecialchars($item['name']) ?></div>
                <div class="text-xs text-neutral-500 mt-0.5 max-w-sm truncate" title="<?= htmlspecialchars($item['description']) ?>">
                  <?= htmlspecialchars($item['description']) ?>
                </div>
              </td>
              <td class="px-6 py-4 text-xs font-semibold text-neutral-600 uppercase tracking-wider">
                <?= htmlspecialchars(str_replace('_', ' ', $item['category'])) ?>
              </td>
              <td class="px-6 py-4 font-bold text-neutral-700">
                $<?= number_format($item['price'], 2) ?>
              </td>
              <td class="px-6 py-4">
                <span class="rounded-pill px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider
                  <?= $item['available'] ? 'bg-emerald-100 text-emerald-800' : 'bg-neutral-200 text-neutral-600' ?>
                ">
                  <?= $item['available'] ? 'Available' : 'Unavailable' ?>
                </span>
              </td>
              <td class="px-6 py-4 text-right flex justify-end gap-2 text-xs">
                <button @click="editingItem = { 
                          id: <?= $item['id'] ?>, 
                          name: '<?= htmlspecialchars($item['name']) ?>', 
                          description: '<?= htmlspecialchars($item['description'] ?? '') ?>', 
                          price: <?= $item['price'] ?>, 
                          category: '<?= htmlspecialchars($item['category']) ?>', 
                          available: <?= $item['available'] ?>,
                          duration_minutes: <?= json_encode($item['duration_minutes'] ?? null) ?>
                        }; editModalOpen = true" 
                        class="rounded-xl border border-neutral-200 hover:border-brand-500 px-3 py-1.5 font-semibold text-brand-600 hover:bg-brand-50 transition">
                  Edit
                </button>
                <form action="index.php?route=fnb-menu" method="POST" onsubmit="return confirm('Delete this menu item?');" class="inline">
                  <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
                  <input type="hidden" name="action" value="delete_menu_item">
                  <input type="hidden" name="id" value="<?= $item['id'] ?>">
                  <button type="submit" class="rounded-xl border border-red-200 hover:border-red-500 px-3 py-1.5 font-semibold text-red-600 hover:bg-red-50 transition">
                    Delete
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Edit Menu Item Modal -->
  <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 flex items-end bg-neutral-900/40" @click.self="editModalOpen = false" @keydown.escape.window="editModalOpen = false">
    <div 
        x-show="editModalOpen"
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
          <h3 class="text-xl font-bold text-brand-900">Edit Menu Item</h3>
          <p class="text-xs text-neutral-500 mt-1">Modify pricing and availability settings.</p>
        </div>
        <button type="button" @click="editModalOpen = false" class="rounded-full bg-neutral-100 p-2 text-neutral-500 hover:bg-neutral-200 transition">
          &times;
        </button>
      </div>

      <form action="index.php?route=fnb-menu" method="POST" class="mt-6 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
        <input type="hidden" name="action" value="update_menu_item">
        <input type="hidden" name="id" :value="editingItem ? editingItem.id : 0">

        <div>
          <label for="edit_name" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Item Name</label>
          <input type="text" id="edit_name" name="name" required :value="editingItem ? editingItem.name : ''"
                 class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="edit_category" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Category</label>
            <select id="edit_category" name="category" class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500" x-model="editingItem ? editingItem.category : 'starter'">
              <option value="starter">Starter</option>
              <option value="main_course">Main Course</option>
              <option value="dessert">Dessert</option>
              <option value="beverage">Beverage</option>
              <option value="other">Other</option>
              <option value="spa">Spa</option>
              <option value="wellness">Wellness</option>
              <option value="fitness">Fitness</option>
              <option value="activity">Activity</option>
              <option value="transport">Transport</option>
            </select>
          </div>
          <div>
            <label for="edit_price" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Price ($)</label>
            <input type="number" id="edit_price" name="price" step="0.01" min="0.00" required :value="editingItem ? editingItem.price : 0.00"
                   class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500 font-semibold text-neutral-700">
          </div>
          <div>
            <label for="edit_duration_minutes" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Duration (min)</label>
            <input type="number" id="edit_duration_minutes" name="duration_minutes" min="0" step="5" :value="editingItem ? editingItem.duration_minutes : ''"
                   class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
          </div>
        </div>

        <div>
          <label for="edit_description" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Description</label>
          <textarea id="edit_description" name="description" rows="2" :value="editingItem ? editingItem.description : ''"
                    class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500"></textarea>
        </div>

        <div>
          <label class="flex items-center gap-2 cursor-pointer mt-2">
            <input type="checkbox" name="available" value="1" :checked="editingItem && editingItem.available" class="rounded border-neutral-300 text-brand-600 focus:ring-brand-500 h-4 w-4">
            <span class="text-xs font-bold text-neutral-600 uppercase tracking-wider">Item Available for Order</span>
          </label>
        </div>

        <div class="flex gap-3 pt-4 border-t border-neutral-100">
          <button type="button" @click="editModalOpen = false" class="w-1/2 rounded-2xl bg-neutral-100 py-3 text-sm font-bold text-neutral-500 hover:bg-neutral-200 transition">
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
