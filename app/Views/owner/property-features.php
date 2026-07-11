<?php
$title = 'Property Features';
include __DIR__ . '/../partials/owner-header.php';

$propertyType = $property['property_type'] ?? '';
$typeIcons = [
    'resort' => svg_icon('palm', 'w-7 h-7'),
    'homestay' => svg_icon('home', 'w-7 h-7'),
    'lodge' => svg_icon('mountain', 'w-7 h-7'),
    'budget' => svg_icon('wallet', 'w-7 h-7'),
    'luxury' => svg_icon('crown', 'w-7 h-7'),
    'boutique' => svg_icon('building', 'w-7 h-7'),
];
$propertyTypeIcon = $typeIcons[$propertyType] ?? svg_icon('building', 'w-7 h-7');
$propertyTypeLabel = $propertyType ? ucfirst($propertyType) : 'Not set';

$totalAmenities = 0;
$enabledAmenities = 0;
foreach ($groupedAmenities as $cat) {
    foreach ($cat as $a) {
        $totalAmenities++;
        if ($a['enabled']) $enabledAmenities++;
    }
}

$categoryIcons = [
    'amenities' => svg_icon('star', 'w-5 h-5'),
    'food' => svg_icon('utensils', 'w-5 h-5'),
    'services' => svg_icon('concierge', 'w-5 h-5'),
    'recreation' => svg_icon('target', 'w-5 h-5'),
    'technology' => svg_icon('computer', 'w-5 h-5'),
    'accessibility' => svg_icon('accessibility', 'w-5 h-5'),
    'safety' => svg_icon('shield', 'w-5 h-5'),
    'transportation' => svg_icon('truck', 'w-5 h-5'),
];

$existingCategories = array_keys($groupedAmenities);
?>
<div x-data="amenityManager()" @keydown.escape.window="createOpen = false; deleteKey = null" class="space-y-6">
  <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
    <div>
      <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-600">Property Features</p>
      <h1 class="mt-1 text-2xl font-semibold text-brand-900">Amenities & Services</h1>
      <p class="mt-2 text-sm text-neutral-500">
        Manage the amenities available at your property. Click any chip to toggle it, or add new ones.
      </p>
    </div>
    <button type="button" @click="openCreateModal()"
      class="inline-flex items-center gap-2 rounded-pill bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 transition shadow-sm">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      Add Amenity
    </button>
  </div>

  <!-- Property Type Card -->
  <div class="rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
      <div class="flex items-center gap-4">
        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 border border-brand-100">
          <?= $propertyTypeIcon ?>
        </div>
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">Property Type</p>
          <p class="mt-1 text-lg font-semibold text-brand-900"><?= htmlspecialchars($property['name'] ?? '') ?></p>
          <span class="mt-1 inline-flex items-center gap-1.5 rounded-pill bg-brand-50 px-3 py-1 text-sm font-semibold text-brand-700 border border-brand-100">
            <?= $propertyTypeIcon ?>
            <?= $propertyTypeLabel ?>
          </span>
        </div>
      </div>
      <div class="flex items-center gap-4">
        <div class="text-center">
          <p class="text-2xl font-bold text-brand-900"><?= $totalAmenities ?></p>
          <p class="text-xs text-neutral-500">Total</p>
        </div>
        <div class="w-px h-8 bg-neutral-200"></div>
        <div class="text-center">
          <p class="text-2xl font-bold text-emerald-600" x-text="enabledCount"><?= $enabledAmenities ?></p>
          <p class="text-xs text-neutral-500">Enabled</p>
        </div>
        <div class="w-px h-8 bg-neutral-200"></div>
        <div class="text-center">
          <p class="text-2xl font-bold text-neutral-400" x-text="totalCount - enabledCount"><?= $totalAmenities - $enabledAmenities ?></p>
          <p class="text-xs text-neutral-500">Disabled</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Amenity Category Cards -->
  <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
    <?php foreach ($groupedAmenities as $categoryName => $amenities):
      $catEnabled = count(array_filter($amenities, fn($a) => $a['enabled']));
      $catTotal = count($amenities);
      $catLower = strtolower($categoryName);
      $catIcon = $categoryIcons[$catLower] ?? $categoryIcons[$categoryName] ?? svg_icon('clipboard', 'w-5 h-5');
    ?>
      <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-2">
            <span class="text-brand-500"><?= $catIcon ?></span>
            <h3 class="text-sm font-bold uppercase tracking-[0.15em] text-neutral-500"><?= htmlspecialchars($categoryName) ?></h3>
          </div>
          <span class="text-xs font-semibold text-neutral-400"><?= $catEnabled ?>/<?= $catTotal ?></span>
        </div>
        <div class="flex flex-wrap gap-2">
          <?php foreach ($amenities as $item): ?>
            <?php
            $amenityKey = $item['key'];
            $enabled = $item['enabled'];
            $icon = $item['icon'] ?? '';
            $label = $item['label'];
            ?>
            <div class="relative group amenity-toggle-wrapper"
                 data-key="<?= htmlspecialchars($amenityKey) ?>"
                 data-enabled="<?= $enabled ? 'true' : 'false' ?>">
              <button type="button"
                class="inline-flex items-center gap-1.5 rounded-pill px-3 py-1.5 text-sm font-semibold transition border <?= $enabled
                  ? 'bg-brand-50 text-brand-700 border-brand-100 hover:bg-brand-100'
                  : 'bg-neutral-100 text-neutral-500 border-neutral-200 hover:bg-brand-50 hover:text-brand-700 hover:border-brand-100' ?>">
                <?php if ($icon): ?><span><?= $icon ?></span><?php endif; ?>
                <span><?= htmlspecialchars($label) ?></span>
              </button>
              <button type="button"
                data-amenity-key="<?= htmlspecialchars($amenityKey, ENT_QUOTES) ?>"
                data-amenity-label="<?= htmlspecialchars($label, ENT_QUOTES) ?>"
                @click="confirmDelete($event.currentTarget.dataset.amenityKey, $event.currentTarget.dataset.amenityLabel)"
                class="absolute -top-1.5 -right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-rose-400 text-white opacity-0 group-hover:opacity-100 transition hover:bg-rose-500 shadow-sm"
                title="Delete amenity">
                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Create Amenity Modal -->
  <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background: rgba(0,0,0,0.4);" @click.self="createOpen = false">
    <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl">
      <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg font-semibold text-brand-900">Add New Amenity</h3>
        <button type="button" @click="createOpen = false" class="text-neutral-400 hover:text-neutral-600 transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-semibold text-neutral-700 mb-1">Name *</label>
          <input type="text" x-model="newName" placeholder="e.g. Rooftop Bar" maxlength="100"
            class="w-full rounded-pill border border-neutral-200 bg-neutral-50 px-4 py-2.5 text-sm text-neutral-800 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-transparent transition">
        </div>
        <div>
          <label class="block text-sm font-semibold text-neutral-700 mb-1">Category *</label>
          <input type="text" x-model="newCategory" list="category-list" placeholder="e.g. Food & Beverage"
            class="w-full rounded-pill border border-neutral-200 bg-neutral-50 px-4 py-2.5 text-sm text-neutral-800 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-transparent transition">
          <datalist id="category-list">
            <?php foreach ($existingCategories as $ec): ?>
              <option value="<?= htmlspecialchars($ec) ?>">
            <?php endforeach; ?>
            <option value="Food">
            <option value="Services">
            <option value="Recreation">
            <option value="Technology">
            <option value="Transportation">
            <option value="Safety">
            <option value="Accessibility">
          </datalist>
        </div>
        <div>
          <label class="block text-sm font-semibold text-neutral-700 mb-2">Icon</label>
          <div class="flex flex-wrap gap-2">
            <template x-for="ic in amenityIcons" :key="ic.icon">
              <button type="button" @click="newIcon = ic.icon"
                :class="newIcon === ic.icon ? 'ring-2 ring-brand-500 bg-brand-50 border-brand-300' : 'border-neutral-200 bg-neutral-50 hover:bg-neutral-100'"
                class="flex h-9 w-9 items-center justify-center rounded-xl border text-base transition">
                <span x-text="ic.icon"></span>
              </button>
            </template>
          </div>
        </div>
        <div>
          <label class="block text-sm font-semibold text-neutral-700 mb-1">Description</label>
          <textarea x-model="newDescription" rows="2" placeholder="Optional description"
            class="w-full rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-2.5 text-sm text-neutral-800 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-transparent transition resize-none"></textarea>
        </div>
      </div>
      <div class="flex items-center justify-end gap-3 mt-6">
        <button type="button" @click="createOpen = false"
          class="rounded-pill border border-neutral-200 px-5 py-2.5 text-sm font-semibold text-neutral-600 hover:bg-neutral-50 transition">Cancel</button>
        <button type="button" @click="createAmenity()" :disabled="!newName.trim() || !newCategory.trim()"
          :class="!newName.trim() || !newCategory.trim() ? 'opacity-50 cursor-not-allowed' : 'hover:bg-brand-700'"
          class="rounded-pill bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition shadow-sm">
          <span x-show="!creating">Create Amenity</span>
          <span x-show="creating" class="inline-flex items-center gap-2">
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            Creating...
          </span>
        </button>
      </div>
      <p x-show="createError" x-text="createError" class="mt-3 text-sm text-rose-600 font-semibold text-center"></p>
    </div>
  </div>

  <!-- Delete Confirmation -->
  <div x-show="deleteKey" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background: rgba(0,0,0,0.4);" @click.self="deleteKey = null">
    <div class="w-full max-w-sm rounded-3xl bg-white p-6 shadow-2xl text-center">
      <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-rose-500 mb-4">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
      </div>
      <h3 class="text-lg font-semibold text-brand-900">Delete Amenity?</h3>
      <p class="mt-2 text-sm text-neutral-500">
        Are you sure you want to delete <strong x-text="deleteLabel" class="text-neutral-700"></strong>?<br>
        This action cannot be undone.
      </p>
      <div class="flex items-center justify-center gap-3 mt-6">
        <button type="button" @click="deleteKey = null"
          class="rounded-pill border border-neutral-200 px-5 py-2.5 text-sm font-semibold text-neutral-600 hover:bg-neutral-50 transition">Cancel</button>
        <button type="button" @click="executeDelete()" :disabled="deleting"
          :class="deleting ? 'opacity-50 cursor-not-allowed' : 'hover:bg-rose-700'"
          class="rounded-pill bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white transition shadow-sm">
          <span x-show="!deleting">Delete</span>
          <span x-show="deleting" class="inline-flex items-center gap-2">
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            Deleting...
          </span>
        </button>
      </div>
    </div>
  </div>

  <!-- Toast container -->
  <div class="fixed bottom-5 right-5 z-50 flex flex-col gap-2">
    <template x-for="toast in toasts" :key="toast.id">
      <div
        x-show="toast.show"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="translate-y-2 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        :class="toast.type === 'success' ? 'bg-emerald-500 text-white border-emerald-600' : 'bg-rose-500 text-white border-rose-600'"
        class="flex items-center gap-3 rounded-2xl px-5 py-3 shadow-lg border text-sm font-semibold transition"
      >
        <span x-show="toast.type === 'success'" class="w-5 h-5"><?= svg_icon('check', 'w-5 h-5') ?></span>
        <span x-show="toast.type !== 'success'" class="w-5 h-5"><?= svg_icon('xmark', 'w-5 h-5') ?></span>
        <span x-text="toast.message"></span>
      </div>
    </template>
  </div>
</div>

<script>
  document.addEventListener('alpine:init', () => {
    console.log('[amenityManager] alpine:init fired');
    Alpine.data('amenityManager', () => ({
      totalCount: <?= $totalAmenities ?>,
      enabledCount: <?= $enabledAmenities ?>,

      // Toast
      toasts: [],
      showToast(message, type) {
        if (!type) type = 'success';
        const id = Date.now();
        this.toasts.push({ id, message, type, show: true });
        setTimeout(() => {
          const idx = this.toasts.findIndex(t => t.id === id);
          if (idx !== -1) this.toasts[idx].show = false;
        }, 3000);
      },

      // Create
      createOpen: false,
      newName: '',
      newCategory: '',
      newIcon: '',
      newDescription: '',
      creating: false,
      createError: '',

      openCreateModal() {
        this.newName = '';
        this.newCategory = '';
        this.newIcon = '';
        this.newDescription = '';
        this.createError = '';
        this.createOpen = true;
        console.log('[amenityManager] openCreateModal');
      },

      createAmenity() {
        const name = this.newName.trim();
        const category = this.newCategory.trim();
        if (!name || !category) return;
        this.creating = true;
        this.createError = '';
        const self = this;
        fetch('<?= BASE_URL ?>/owner/index.php?route=create-amenity', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            label: name, category, icon: this.newIcon,
            description: this.newDescription.trim(),
            csrf_token: '<?= $csrfToken ?>'
          })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (!data.success) throw new Error(data.error || 'Server error');
          self.createOpen = false;
          self.showToast('"' + name + '" created.', 'success');
          setTimeout(function() { location.reload(); }, 800);
        })
        .catch(function(err) {
          self.createError = err.message;
          self.showToast('Failed: ' + err.message, 'error');
        })
        .finally(function() { self.creating = false; });
      },

      // Delete
      deleteKey: null,
      deleteLabel: '',
      deleting: false,

      confirmDelete(key, label) {
        this.deleteKey = key;
        this.deleteLabel = label;
      },

      executeDelete() {
        if (!this.deleteKey) return;
        this.deleting = true;
        const self = this;
        fetch('<?= BASE_URL ?>/owner/index.php?route=delete-amenity', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            amenity_key: this.deleteKey,
            csrf_token: '<?= $csrfToken ?>'
          })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (!data.success) throw new Error(data.error || 'Server error');
          self.showToast('"' + self.deleteLabel + '" deleted.', 'success');
          self.deleteKey = null;
          self.deleteLabel = '';
          setTimeout(function() { location.reload(); }, 800);
        })
        .catch(function(err) {
          self.showToast('Failed: ' + err.message, 'error');
        })
        .finally(function() { self.deleting = false; });
      },

      amenityIcons: [
        { icon: '🏊', label: 'Pool' },
        { icon: '📶', label: 'WiFi' },
        { icon: '🥐', label: 'Breakfast' },
        { icon: '💆', label: 'Spa' },
        { icon: '🏋️', label: 'Gym' },
        { icon: '🛁', label: 'Bathtub' },
        { icon: '🔥', label: 'Hot Water' },
        { icon: '🌱', label: 'Turf/Garden' },
        { icon: '🛝', label: 'Play Area' },
        { icon: '🧘', label: 'Yoga' },
        { icon: '🍳', label: 'Kitchen' },
        { icon: '🍽️', label: 'Dining' },
        { icon: '☕', label: 'Coffee' },
        { icon: '🍸', label: 'Bar' },
        { icon: '🧺', label: 'Laundry' },
        { icon: '🐾', label: 'Pet Area' },
        { icon: '♿', label: 'Accessible' },
        { icon: '🌿', label: 'Garden' },
        { icon: '📺', label: 'Media/TV' },
        { icon: '🔒', label: 'Security' },
        { icon: '🚗', label: 'Parking' },
        { icon: '🎮', label: 'Gaming' },
        { icon: '📞', label: 'Concierge' },
        { icon: '🔔', label: 'Bell Service' },
        { icon: '🎯', label: 'Activities' },
        { icon: '🏕️', label: 'Camping' },
        { icon: '🎳', label: 'Bowling' },
        { icon: '❄️', label: 'AC/Cooling' },
      ],
    }));
  });

  // Toggle chip behavior
  document.addEventListener('DOMContentLoaded', function() {
    console.log('[property-features] DOMContentLoaded');
    var wrappers = document.querySelectorAll('.amenity-toggle-wrapper');
    console.log('[property-features] found wrappers:', wrappers.length);
    wrappers.forEach(function(wrapper) {
      var button = wrapper.querySelector('button:first-child');
      var key = wrapper.getAttribute('data-key');
      if (!button) { console.warn('[property-features] no button for', key); return; }
      button.addEventListener('click', function() {
        var isEnabled = wrapper.getAttribute('data-enabled') === 'true';
        var next = !isEnabled;
        wrapper.setAttribute('data-enabled', next ? 'true' : 'false');

        var enabledClass = 'inline-flex items-center gap-1.5 rounded-pill px-3 py-1.5 text-sm font-semibold transition border bg-brand-50 text-brand-700 border-brand-100 hover:bg-brand-100';
        var disabledClass = 'inline-flex items-center gap-1.5 rounded-pill px-3 py-1.5 text-sm font-semibold transition border bg-neutral-100 text-neutral-500 border-neutral-200 hover:bg-brand-50 hover:text-brand-700 hover:border-brand-100';
        button.className = next ? enabledClass : disabledClass;

        var el = document.querySelector('[x-data="amenityManager()"]');
        var pageData = el ? Alpine.$data(el) : null;

        fetch('<?= BASE_URL ?>/owner/index.php?route=toggle-feature', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            property_id: <?= $propertyId ?>,
            amenity_key: key,
            enabled: next,
            csrf_token: '<?= $csrfToken ?>'
          })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (!data.success) throw new Error(data.error || 'Server error');
          var label = button.querySelector('span:last-child').textContent || key;
          if (pageData) {
            pageData.enabledCount += next ? 1 : -1;
            pageData.showToast('"' + label + '" updated.', 'success');
          }
        })
        .catch(function(err) {
          wrapper.setAttribute('data-enabled', isEnabled ? 'true' : 'false');
          button.className = isEnabled ? enabledClass : disabledClass;
          if (pageData) pageData.showToast('Failed: ' + err.message, 'error');
        });
      });
    });
  });
</script>
<style>
  [x-cloak] { display: none !important; }
</style>
<?php include __DIR__ . '/../partials/owner-footer.php'; ?>
