<?php
/**
 * vendors.php - Maintenance Vendor Directory View
 */
$title = "Vendor Directory";
include __DIR__ . '/../../partials/staff-header.php';
?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8" x-data="{ addVendorOpen: false }">
  <div>
    <h1 class="text-2xl font-bold text-brand-900 leading-tight">Vendor Directory</h1>
    <p class="text-xs text-neutral-500 mt-1">Manage external contractors, HVAC maintenance teams, and cleaning companies.</p>
  </div>
  
  <div>
    <button @click="addVendorOpen = true" class="inline-flex items-center justify-center rounded-2xl bg-brand-500 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-brand-600 transition">
      ➕ Add Vendor
    </button>
  </div>

  <!-- Add Vendor Modal -->
  <div x-show="addVendorOpen" x-cloak class="fixed inset-0 z-50 flex items-end bg-neutral-900/40" @click.self="addVendorOpen = false" @keydown.escape.window="addVendorOpen = false">
    <div 
        x-show="addVendorOpen"
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
          <h3 class="text-xl font-bold text-brand-900">Add New Vendor</h3>
          <p class="text-xs text-neutral-500 mt-1">Register a new external operations vendor.</p>
        </div>
        <button type="button" @click="addVendorOpen = false" class="rounded-full bg-neutral-100 p-2 text-neutral-500 hover:bg-neutral-200 transition">
          &times;
        </button>
      </div>

      <form action="index.php?route=vendors" method="POST" class="mt-6 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
        <input type="hidden" name="action" value="create_vendor">

        <div>
          <label for="name" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Company / Vendor Name</label>
          <input type="text" id="name" name="name" required placeholder="e.g. Seaside HVAC Services, City Plumbing Ltd"
                 class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="contact_name" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Contact Person</label>
            <input type="text" id="contact_name" name="contact_name" placeholder="e.g. Rahul Kumar"
                   class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
          </div>
          <div>
            <label for="phone" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Phone Number</label>
            <input type="text" id="phone" name="phone" placeholder="e.g. +1-555-0130"
                   class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
          </div>
        </div>

        <div>
          <label for="email" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Email Address</label>
          <input type="email" id="email" name="email" placeholder="e.g. info@seasidehvac.example"
                 class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
        </div>

        <div>
          <label for="address" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Office Address</label>
          <input type="text" id="address" name="address" placeholder="e.g. 200 Service Park, Marina Bay"
                 class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
        </div>

        <div class="flex gap-3 pt-4 border-t border-neutral-100">
          <button type="button" @click="addVendorOpen = false" class="w-1/2 rounded-2xl bg-neutral-100 py-3 text-sm font-bold text-neutral-500 hover:bg-neutral-200 transition">
            Cancel
          </button>
          <button type="submit" class="w-1/2 rounded-2xl bg-brand-500 py-3 text-sm font-bold text-white hover:bg-brand-600 transition shadow-sm">
            Save Vendor
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Vendors List Directory -->
<div class="bg-white rounded-3xl border border-neutral-200 shadow-sm overflow-hidden" x-data="{ editingVendor: null, editModalOpen: false }">
  <div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
      <thead class="bg-neutral-50 text-xs font-bold uppercase tracking-wider text-neutral-400 border-b border-neutral-200">
        <tr>
          <th class="px-6 py-4">Company Name</th>
          <th class="px-6 py-4">Contact Person</th>
          <th class="px-6 py-4">Email Address</th>
          <th class="px-6 py-4">Phone Number</th>
          <th class="px-6 py-4">Address</th>
          <th class="px-6 py-4">Status</th>
          <th class="px-6 py-4 text-right">Action</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-neutral-100">
        <?php if (empty($vendors)): ?>
          <tr>
            <td colspan="7" class="px-6 py-12 text-center text-neutral-500">
              No vendors registered in the system.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($vendors as $v): ?>
            <tr class="hover:bg-neutral-50/50 transition">
              <td class="px-6 py-4 font-bold text-brand-900">
                <?= htmlspecialchars($v['name']) ?>
              </td>
              <td class="px-6 py-4 text-xs font-semibold text-neutral-600">
                <?= htmlspecialchars($v['contact_name'] ?: 'N/A') ?>
              </td>
              <td class="px-6 py-4 text-xs text-neutral-500">
                <?= htmlspecialchars($v['email'] ?: 'N/A') ?>
              </td>
              <td class="px-6 py-4 text-xs text-neutral-500">
                <?= htmlspecialchars($v['phone'] ?: 'N/A') ?>
              </td>
              <td class="px-6 py-4 text-xs text-neutral-500">
                <?= htmlspecialchars($v['address'] ?: 'N/A') ?>
              </td>
              <td class="px-6 py-4">
                <span class="rounded-pill px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider
                  <?= $v['active'] ? 'bg-emerald-100 text-emerald-800' : 'bg-neutral-200 text-neutral-600' ?>
                ">
                  <?= $v['active'] ? 'Active' : 'Inactive' ?>
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <button @click="editingVendor = { 
                          id: <?= $v['id'] ?>, 
                          name: '<?= htmlspecialchars($v['name']) ?>', 
                          contact_name: '<?= htmlspecialchars($v['contact_name'] ?? '') ?>', 
                          email: '<?= htmlspecialchars($v['email'] ?? '') ?>', 
                          phone: '<?= htmlspecialchars($v['phone'] ?? '') ?>', 
                          address: '<?= htmlspecialchars($v['address'] ?? '') ?>',
                          active: <?= $v['active'] ?>
                        }; editModalOpen = true" 
                        class="rounded-xl border border-neutral-200 hover:border-brand-500 px-3 py-1.5 font-semibold text-brand-600 hover:bg-brand-50 transition">
                  Edit
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Edit Vendor Modal -->
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
          <h3 class="text-xl font-bold text-brand-900">Edit Vendor details</h3>
          <p class="text-xs text-neutral-500 mt-1">Update details for registered contractor.</p>
        </div>
        <button type="button" @click="editModalOpen = false" class="rounded-full bg-neutral-100 p-2 text-neutral-500 hover:bg-neutral-200 transition">
          &times;
        </button>
      </div>

      <form action="index.php?route=vendors" method="POST" class="mt-6 space-y-4" x-ref="editForm">
        <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
        <input type="hidden" name="action" value="update_vendor">
        <input type="hidden" name="id" :value="editingVendor ? editingVendor.id : 0">

        <div>
          <label for="edit_name" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Company / Vendor Name</label>
          <input type="text" id="edit_name" name="name" required :value="editingVendor ? editingVendor.name : ''"
                 class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="edit_contact_name" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Contact Person</label>
            <input type="text" id="edit_contact_name" name="contact_name" :value="editingVendor ? editingVendor.contact_name : ''"
                   class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
          </div>
          <div>
            <label for="edit_phone" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Phone Number</label>
            <input type="text" id="edit_phone" name="phone" :value="editingVendor ? editingVendor.phone : ''"
                   class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
          </div>
        </div>

        <div>
          <label for="edit_email" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Email Address</label>
          <input type="email" id="edit_email" name="email" :value="editingVendor ? editingVendor.email : ''"
                 class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
        </div>

        <div>
          <label for="edit_address" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Office Address</label>
          <input type="text" id="edit_address" name="address" :value="editingVendor ? editingVendor.address : ''"
                 class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
        </div>

        <div>
          <label class="flex items-center gap-2 cursor-pointer mt-2">
            <input type="checkbox" name="active" value="1" :checked="editingVendor && editingVendor.active" class="rounded border-neutral-300 text-brand-600 focus:ring-brand-500 h-4 w-4">
            <span class="text-xs font-bold text-neutral-600 uppercase tracking-wider">Vendor Active</span>
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
