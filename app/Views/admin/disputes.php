<?php
$title = "Dispute Resolution";
include __DIR__ . '/../partials/admin-header.php';
?>
<div class="space-y-6">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-900">Dispute Resolution</h1>
      <p class="text-sm text-neutral-500 mt-1">Manage guest disputes related to billing, damage, service, or policy.</p>
    </div>
    <div class="flex gap-1">
      <?php foreach (['' => 'All', 'open' => 'Open', 'investigating' => 'Investigating', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed'] as $val => $label): ?>
        <a href="?route=disputes&status=<?= $val ?>" class="rounded-pill px-3 py-1.5 text-xs font-bold <?= $statusFilter === $val ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="rounded-3xl border border-neutral-200 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-neutral-50 border-b border-neutral-200 text-left text-xs font-semibold uppercase tracking-wider text-neutral-500">
            <th class="py-3 px-4">Type</th>
            <th class="py-3 px-4">Guest</th>
            <th class="py-3 px-4">Booking</th>
            <th class="py-3 px-4">Property</th>
            <th class="py-3 px-4">Amount</th>
            <th class="py-3 px-4">Description</th>
            <th class="py-3 px-4">Created</th>
            <th class="py-3 px-4">Status</th>
            <th class="py-3 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
          <?php if (empty($disputes)): ?>
            <tr><td colspan="9" class="py-8 text-center text-sm text-neutral-500">No disputes found.</td></tr>
          <?php endif; ?>
          <?php foreach ($disputes as $d): ?>
            <tr class="hover:bg-neutral-50">
              <td class="py-3 px-4">
                <span class="rounded-pill px-2 py-0.5 text-xs font-bold <?= match($d['dispute_type']) { 'billing' => 'bg-amber-50 text-amber-700', 'damage' => 'bg-red-50 text-red-700', 'service' => 'bg-blue-50 text-blue-700', 'policy' => 'bg-purple-50 text-purple-700', default => 'bg-neutral-100 text-neutral-600' } ?>">
                  <?= htmlspecialchars(ucfirst($d['dispute_type'])) ?>
                </span>
              </td>
              <td class="py-3 px-4">
                <p class="font-semibold text-neutral-800"><?= htmlspecialchars(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? '')) ?></p>
                <p class="text-xs text-neutral-500"><?= htmlspecialchars($d['guest_email'] ?? '') ?></p>
              </td>
              <td class="py-3 px-4">
                <p class="text-neutral-800">#<?= $d['booking_id'] ?></p>
                <p class="text-xs text-neutral-500"><?= htmlspecialchars($d['check_in_date'] ?? '') ?> — <?= htmlspecialchars($d['check_out_date'] ?? '') ?></p>
              </td>
              <td class="py-3 px-4 text-neutral-600"><?= htmlspecialchars($d['property_name'] ?? '') ?></td>
              <td class="py-3 px-4 font-bold text-neutral-800">
                <?= $d['amount_in_dispute'] ? '$' . number_format((float)$d['amount_in_dispute'], 2) : '—' ?>
              </td>
              <td class="py-3 px-4 max-w-xs truncate text-neutral-600"><?= htmlspecialchars($d['description']) ?></td>
              <td class="py-3 px-4 text-xs text-neutral-500"><?= date('M j, Y', strtotime($d['created_at'])) ?></td>
              <td class="py-3 px-4">
                <span class="rounded-pill px-2 py-0.5 text-xs font-bold <?= match($d['status']) { 'open' => 'bg-rose-50 text-rose-700', 'investigating' => 'bg-blue-50 text-blue-700', 'resolved' => 'bg-emerald-50 text-emerald-700', 'dismissed' => 'bg-neutral-100 text-neutral-600', default => 'bg-neutral-100 text-neutral-600' } ?>">
                  <?= htmlspecialchars(ucfirst($d['status'])) ?>
                </span>
              </td>
              <td class="py-3 px-4 text-right">
                <?php if (in_array($d['status'], ['open', 'investigating'])): ?>
                  <div x-data="{ open: false, resolution: '' }">
                    <button @click="open = true" class="rounded-2xl bg-brand-500 px-3 py-1.5 text-xs font-bold text-white hover:bg-brand-600 transition">Resolve</button>
                    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="open = false">
                      <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-xl mx-4">
                        <h3 class="text-lg font-bold text-brand-900">Resolve Dispute</h3>
                        <p class="mt-1 text-sm text-neutral-600"><?= htmlspecialchars(ucfirst($d['dispute_type'])) ?> — Booking #<?= $d['booking_id'] ?></p>
                        <p class="mt-2 p-3 rounded-2xl bg-neutral-50 text-sm text-neutral-700"><?= htmlspecialchars($d['description']) ?></p>
                        <form method="POST" action="index.php" class="mt-4 space-y-3">
                          <input type="hidden" name="action" value="dispute_action">
                          <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
                          <input type="hidden" name="id" value="<?= $d['id'] ?>">
                          <label class="block text-sm font-semibold text-neutral-700">
                            <span>Resolution</span>
                            <textarea name="resolution" x-model="resolution" rows="3" class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500"></textarea>
                          </label>
                          <div class="flex flex-wrap gap-2">
                            <button type="submit" name="status" value="investigating" class="rounded-2xl bg-blue-500 px-4 py-2 text-sm font-bold text-white hover:bg-blue-600 transition">Investigate</button>
                            <button type="submit" name="status" value="resolved" class="rounded-2xl bg-emerald-500 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-600 transition">Resolve</button>
                            <button type="submit" name="status" value="dismissed" class="rounded-2xl bg-neutral-500 px-4 py-2 text-sm font-bold text-white hover:bg-neutral-600 transition">Dismiss</button>
                            <button type="button" @click="open = false" class="rounded-2xl border border-neutral-200 px-4 py-2 text-sm font-bold text-neutral-600 hover:bg-neutral-50 transition">Cancel</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                <?php elseif ($d['resolution']): ?>
                  <span class="text-xs text-neutral-500 cursor-help" title="<?= htmlspecialchars($d['resolution']) ?>">View Resolution</span>
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
