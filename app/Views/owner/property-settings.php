<?php
$property = $property ?? ['name' => '', 'code' => '', 'property_type' => '', 'address' => '', 'city' => '', 'state' => '', 'country' => '', 'postal_code' => '', 'phone' => '', 'email' => '', 'description' => ''];
$saved = $saved ?? false;
$error = $error ?? '';
$csrfToken = $csrfToken ?? '';

$title = 'Property Settings - ZyroInn';
include __DIR__ . '/../partials/owner-header.php';
?>
    <main class="mx-auto w-full max-w-3xl flex-grow px-4 py-8 pb-24 sm:px-6 lg:px-8">
      <div class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-600">Settings</p>
        <h1 class="mt-1 text-2xl font-semibold text-brand-900">Property Settings</h1>
        <p class="mt-2 text-sm text-neutral-500">Update your property's basic information and type classification.</p>
      </div>

      <?php if ($saved): ?>
        <div class="mb-6 rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-semibold text-emerald-800">
          ✅ Property settings saved successfully.
        </div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="mb-6 rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm font-semibold text-rose-800">
          ❌ <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="index.php?route=property-settings&property_id=<?= (int)$propertyId ?>" class="space-y-8">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

        <!-- Property Type -->
        <div class="bg-white rounded-3xl border border-neutral-200 shadow-sm p-6">
          <h2 class="text-sm font-bold text-neutral-500 uppercase tracking-wider mb-4">Property Classification</h2>
          <div>
            <label for="property_type" class="block text-xs font-medium text-neutral-500 mb-2">Property Type</label>
            <select id="property_type" name="property_type"
                    class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
              <option value="">-- Select Type --</option>
              <option value="resort" <?= ($property['property_type'] ?? '') === 'resort' ? 'selected' : '' ?>>Resort</option>
              <option value="homestay" <?= ($property['property_type'] ?? '') === 'homestay' ? 'selected' : '' ?>>Homestay</option>
              <option value="lodge" <?= ($property['property_type'] ?? '') === 'lodge' ? 'selected' : '' ?>>Lodge</option>
              <option value="budget" <?= ($property['property_type'] ?? '') === 'budget' ? 'selected' : '' ?>>Budget</option>
              <option value="luxury" <?= ($property['property_type'] ?? '') === 'luxury' ? 'selected' : '' ?>>Luxury</option>
            </select>
            <p class="text-[10px] text-neutral-400 mt-1">Classifying your property correctly helps surface the right booking flow for your guests.</p>
          </div>
        </div>

        <!-- Basic Info -->
        <div class="bg-white rounded-3xl border border-neutral-200 shadow-sm p-6">
          <h2 class="text-sm font-bold text-neutral-500 uppercase tracking-wider mb-4">Basic Information</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="name" class="block text-xs font-medium text-neutral-500 mb-2">Property Name *</label>
              <input type="text" id="name" name="name" value="<?= htmlspecialchars($property['name'] ?? '') ?>" required
                     class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
            </div>
            <div>
              <label for="code" class="block text-xs font-medium text-neutral-500 mb-2">Property Code *</label>
              <input type="text" id="code" name="code" value="<?= htmlspecialchars($property['code'] ?? '') ?>" required
                     class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
            </div>
            <div class="md:col-span-2">
              <label for="address" class="block text-xs font-medium text-neutral-500 mb-2">Address</label>
              <input type="text" id="address" name="address" value="<?= htmlspecialchars($property['address'] ?? '') ?>"
                     class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
            </div>
            <div>
              <label for="city" class="block text-xs font-medium text-neutral-500 mb-2">City</label>
              <input type="text" id="city" name="city" value="<?= htmlspecialchars($property['city'] ?? '') ?>"
                     class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
            </div>
            <div>
              <label for="state" class="block text-xs font-medium text-neutral-500 mb-2">State</label>
              <input type="text" id="state" name="state" value="<?= htmlspecialchars($property['state'] ?? '') ?>"
                     class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
            </div>
            <div>
              <label for="country" class="block text-xs font-medium text-neutral-500 mb-2">Country</label>
              <input type="text" id="country" name="country" value="<?= htmlspecialchars($property['country'] ?? '') ?>"
                     class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
            </div>
            <div>
              <label for="postal_code" class="block text-xs font-medium text-neutral-500 mb-2">Postal Code</label>
              <input type="text" id="postal_code" name="postal_code" value="<?= htmlspecialchars($property['postal_code'] ?? '') ?>"
                     class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
            </div>
            <div>
              <label for="phone" class="block text-xs font-medium text-neutral-500 mb-2">Phone</label>
              <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($property['phone'] ?? '') ?>"
                     class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
            </div>
            <div>
              <label for="email" class="block text-xs font-medium text-neutral-500 mb-2">Email</label>
              <input type="email" id="email" name="email" value="<?= htmlspecialchars($property['email'] ?? '') ?>"
                     class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
            </div>
          </div>
        </div>

        <!-- Description -->
        <div class="bg-white rounded-3xl border border-neutral-200 shadow-sm p-6">
          <h2 class="text-sm font-bold text-neutral-500 uppercase tracking-wider mb-4">Description</h2>
          <textarea id="description" name="description" rows="5"
                    class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500"><?= htmlspecialchars($property['description'] ?? '') ?></textarea>
        </div>

        <div class="flex gap-4">
          <a href="index.php?route=dashboard" class="w-1/3 rounded-2xl bg-neutral-100 py-3.5 text-center text-sm font-bold text-neutral-500 hover:bg-neutral-200 transition">
            Cancel
          </a>
          <button type="submit" class="w-2/3 rounded-2xl bg-brand-500 py-3.5 text-sm font-bold text-white hover:bg-brand-600 transition shadow-sm">
            Save Settings
          </button>
        </div>
      </form>
    </main>
<?php include __DIR__ . '/../partials/owner-footer.php'; ?>
