<?php
$title = "Guest CRM";
include __DIR__ . '/../partials/admin-header.php';
?>
<div class="space-y-6">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-900">Unified Guest CRM</h1>
      <p class="text-sm text-neutral-500 mt-1">Complete guest profiles across all properties — bookings, spending, loyalty.</p>
    </div>
    <form method="GET" action="index.php" class="flex items-center gap-2">
      <input type="hidden" name="route" value="guests">
      <input type="text" name="search" placeholder="Search by name, email, or phone..." value="<?= htmlspecialchars($search ?? '') ?>" class="rounded-2xl border border-neutral-200 px-4 py-2 text-sm outline-none focus:border-brand-500 w-64">
      <button type="submit" class="rounded-2xl bg-brand-500 px-4 py-2 text-sm font-bold text-white hover:bg-brand-600 transition">Search</button>
      <?php if (($search ?? '') !== ''): ?><a href="?route=guests" class="text-xs text-neutral-500 hover:text-brand-600">Clear</a><?php endif; ?>
    </form>
  </div>

  <div class="rounded-3xl border border-neutral-200 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-neutral-50 border-b border-neutral-200 text-left text-xs font-semibold uppercase tracking-wider text-neutral-500">
            <th class="py-3 px-4">Guest</th>
            <th class="py-3 px-4">Contact</th>
            <th class="py-3 px-4">Total Bookings</th>
            <th class="py-3 px-4">Active Stays</th>
            <th class="py-3 px-4">Total Spent</th>
            <th class="py-3 px-4">Loyalty</th>
            <th class="py-3 px-4">ID Proof</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
          <?php $guests = $guests ?? []; ?>
          <?php if (empty($guests)): ?>
            <tr><td colspan="7" class="py-8 text-center text-sm text-neutral-500">No guests found.</td></tr>
          <?php endif; ?>
          <?php foreach ($guests as $g): ?>
            <tr class="hover:bg-neutral-50">
              <td class="py-3 px-4">
                <p class="font-semibold text-neutral-800"><?= htmlspecialchars($g['first_name'] . ' ' . $g['last_name']) ?></p>
              </td>
              <td class="py-3 px-4">
                <p class="text-neutral-600 text-xs"><?= htmlspecialchars($g['email'] ?? '') ?></p>
                <?php if ($g['phone']): ?><p class="text-neutral-500 text-xs"><?= htmlspecialchars($g['phone']) ?></p><?php endif; ?>
              </td>
              <td class="py-3 px-4">
                <span class="font-bold text-brand-700"><?= (int)$g['total_bookings'] ?></span>
              </td>
              <td class="py-3 px-4">
                <span class="font-semibold text-neutral-600"><?= (int)$g['active_stays'] ?></span>
              </td>
              <td class="py-3 px-4">
                <span class="font-bold text-neutral-800">$<?= number_format((float)$g['total_spent'], 2) ?></span>
              </td>
              <td class="py-3 px-4">
                <?php if ($g['loyalty_tier']): ?>
                  <span class="rounded-pill px-2 py-0.5 text-xs font-bold <?= match($g['loyalty_tier']) { 'platinum' => 'bg-purple-100 text-purple-700', 'gold' => 'bg-amber-100 text-amber-700', 'silver' => 'bg-neutral-100 text-neutral-700', default => 'bg-orange-50 text-orange-700' } ?>">
                    <?= htmlspecialchars(ucfirst($g['loyalty_tier'])) ?>
                  </span>
                  <span class="text-xs text-neutral-500 ml-1"><?= (int)$g['loyalty_points'] ?> pts</span>
                <?php else: ?>
                  <span class="text-xs text-neutral-400">—</span>
                <?php endif; ?>
              </td>
              <td class="py-3 px-4">
                <?php if ($g['id_proof_path']): ?>
                  <span class="text-xs font-semibold text-emerald-600">Uploaded</span>
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
