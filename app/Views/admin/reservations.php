<?php
$title = "Central Reservations";
include __DIR__ . '/../partials/admin-header.php';
?>
<div class="space-y-6">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-900">Central Reservation System</h1>
      <p class="text-sm text-neutral-500 mt-1">Cross-property booking view — all reservations across the portfolio.</p>
    </div>
    <div class="flex items-center gap-3">
      <span class="text-sm text-neutral-500"><?= count($bookings ?? []) ?> bookings</span>
    </div>
  </div>

  <!-- Filters -->
  <div class="rounded-3xl border border-neutral-200 bg-white p-4 shadow-sm">
    <form method="GET" action="index.php" class="flex flex-wrap items-end gap-3">
      <input type="hidden" name="route" value="reservations">
      <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">Property</label>
        <select name="property_id" class="rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
          <option value="">All Properties</option>
          <?php foreach (($properties ?? []) as $prop): ?>
            <option value="<?= $prop['id'] ?>" <?= ($propertyFilter ?? '') == $prop['id'] ? 'selected' : '' ?>><?= htmlspecialchars($prop['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">Status</label>
        <select name="status" class="rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
          <option value="">All Statuses</option>
          <option value="pending" <?= ($statusFilter ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="confirmed" <?= ($statusFilter ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
          <option value="checked_in" <?= ($statusFilter ?? '') === 'checked_in' ? 'selected' : '' ?>>Checked In</option>
          <option value="checked_out" <?= ($statusFilter ?? '') === 'checked_out' ? 'selected' : '' ?>>Checked Out</option>
          <option value="cancelled" <?= ($statusFilter ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
          <option value="waitlisted" <?= ($statusFilter ?? '') === 'waitlisted' ? 'selected' : '' ?>>Waitlisted</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">Search</label>
        <input type="text" name="search" placeholder="Guest or room..." value="<?= htmlspecialchars($search ?? '') ?>" class="rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500 w-48">
      </div>
      <button type="submit" class="rounded-2xl bg-brand-500 px-4 py-2 text-sm font-bold text-white hover:bg-brand-600 transition">Filter</button>
      <a href="?route=reservations" class="rounded-2xl border border-neutral-200 px-4 py-2 text-sm font-bold text-neutral-600 hover:bg-neutral-50 transition">Clear</a>
    </form>
  </div>

  <!-- Bookings Table -->
  <div class="rounded-3xl border border-neutral-200 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-neutral-50 border-b border-neutral-200 text-left text-xs font-semibold uppercase tracking-wider text-neutral-500">
            <th class="py-3 px-4">Guest</th>
            <th class="py-3 px-4">Property</th>
            <th class="py-3 px-4">Room</th>
            <th class="py-3 px-4">Check-in</th>
            <th class="py-3 px-4">Check-out</th>
            <th class="py-3 px-4">Source</th>
            <th class="py-3 px-4">Status</th>
            <th class="py-3 px-4 text-right">Commission</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
          <?php $bookings = $bookings ?? []; ?>
          <?php if (empty($bookings)): ?>
            <tr><td colspan="8" class="py-8 text-center text-sm text-neutral-500">No bookings found.</td></tr>
          <?php endif; ?>
          <?php foreach ($bookings as $b): ?>
            <tr class="hover:bg-neutral-50">
              <td class="py-3 px-4">
                <p class="font-semibold text-neutral-800"><?= htmlspecialchars(($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? 'Guest')) ?></p>
                <p class="text-xs text-neutral-500"><?= htmlspecialchars($b['email'] ?? '') ?></p>
              </td>
              <td class="py-3 px-4 font-semibold text-neutral-800"><?= htmlspecialchars($b['property_name'] ?? '') ?></td>
              <td class="py-3 px-4">
                <p class="text-neutral-800"><?= htmlspecialchars($b['room_number'] ?? '') ?></p>
                <p class="text-xs text-neutral-500"><?= htmlspecialchars($b['room_type'] ?? '') ?></p>
              </td>
              <td class="py-3 px-4 text-neutral-600"><?= htmlspecialchars($b['check_in_date'] ?? '') ?></td>
              <td class="py-3 px-4 text-neutral-600"><?= htmlspecialchars($b['check_out_date'] ?? '') ?></td>
              <td class="py-3 px-4">
                <span class="text-xs font-semibold uppercase"><?= htmlspecialchars($b['source'] ?? '') ?></span>
              </td>
              <td class="py-3 px-4">
                <?php
                $sc = $b['status'] ?? '';
                $scClass = match($sc) {
                  'confirmed' => 'bg-emerald-50 text-emerald-700',
                  'checked_in' => 'bg-blue-50 text-blue-700',
                  'checked_out' => 'bg-neutral-100 text-neutral-600',
                  'cancelled' => 'bg-rose-50 text-rose-700',
                  'waitlisted' => 'bg-amber-50 text-amber-700',
                  default => 'bg-neutral-100 text-neutral-600',
                };
                ?>
                <span class="rounded-pill px-2 py-0.5 text-xs font-bold <?= $scClass ?>"><?= htmlspecialchars(str_replace('_', ' ', $sc)) ?></span>
              </td>
              <td class="py-3 px-4 text-right">
                <?php if ($b['commission_percentage'] !== null): ?>
                  <span class="text-xs font-bold text-amber-700"><?= htmlspecialchars($b['commission_percentage']) ?>%</span>
                <?php else: ?>
                  <span class="text-xs text-neutral-400">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../partials/admin-footer.php'; ?>
