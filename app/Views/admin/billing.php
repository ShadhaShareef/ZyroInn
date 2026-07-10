<?php
$title = "Billing & Subscriptions";
include __DIR__ . '/../partials/admin-header.php';
?>
<div class="space-y-8">
  <div>
    <h1 class="text-2xl font-bold text-brand-900">Subscription & Billing</h1>
    <p class="text-sm text-neutral-500 mt-1">Manage property subscriptions, plans, and billing history.</p>
  </div>

  <!-- Subscription Plans -->
  <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
    <p class="text-sm font-semibold uppercase tracking-[0.15em] text-neutral-500">Subscription Plans</p>
    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <?php if (empty($plans)): ?>
        <p class="text-sm text-neutral-500 col-span-full">No subscription plans configured. Run the seed migration.</p>
      <?php endif; ?>
      <?php foreach ($plans as $plan): ?>
        <div class="rounded-2xl border border-neutral-200 p-4 <?= $plan['code'] === 'enterprise' ? 'bg-brand-50 border-brand-200' : 'bg-white' ?>">
          <p class="text-lg font-bold text-brand-900"><?= htmlspecialchars($plan['name']) ?></p>
          <p class="mt-1 text-2xl font-bold text-brand-700">$<?= number_format((float)$plan['monthly_price'], 2) ?><span class="text-sm font-normal text-neutral-500">/mo</span></p>
          <p class="mt-2 text-xs text-neutral-500"><?= htmlspecialchars($plan['description'] ?? '') ?></p>
          <div class="mt-3 space-y-1 text-xs text-neutral-600">
            <p>Up to <?= (int)$plan['max_properties'] ?> properties</p>
            <?php if ($plan['max_rooms_per_property']): ?><p>Max <?= (int)$plan['max_rooms_per_property'] ?> rooms/property</p><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Active Subscriptions -->
  <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
    <p class="text-sm font-semibold uppercase tracking-[0.15em] text-neutral-500">Active Subscriptions</p>
    <div class="mt-4 overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-neutral-200 text-left text-xs font-semibold uppercase tracking-wider text-neutral-500">
            <th class="py-3 pr-4">Property</th>
            <th class="py-3 pr-4">Plan</th>
            <th class="py-3 pr-4">Cycle</th>
            <th class="py-3 pr-4">Start</th>
            <th class="py-3 pr-4">Next Billing</th>
            <th class="py-3 pr-4">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
          <?php if (empty($subscriptions)): ?>
            <tr><td colspan="6" class="py-4 text-center text-sm text-neutral-500">No subscriptions yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($subscriptions as $sub): ?>
            <tr class="hover:bg-neutral-50">
              <td class="py-3 pr-4 font-semibold text-neutral-800"><?= htmlspecialchars($sub['property_name']) ?></td>
              <td class="py-3 pr-4"><?= htmlspecialchars($sub['plan_name']) ?> ($<?= number_format((float)$sub['monthly_price'], 2) ?>)</td>
              <td class="py-3 pr-4 text-neutral-600"><?= htmlspecialchars($sub['billing_cycle']) ?></td>
              <td class="py-3 pr-4 text-neutral-600"><?= htmlspecialchars($sub['start_date']) ?></td>
              <td class="py-3 pr-4 text-neutral-600"><?= htmlspecialchars($sub['next_billing_date'] ?? '—') ?></td>
              <td class="py-3 pr-4">
                <span class="rounded-pill px-2 py-0.5 text-xs font-bold <?= match($sub['status']) { 'active' => 'bg-emerald-50 text-emerald-700', 'past_due' => 'bg-rose-50 text-rose-700', 'cancelled' => 'bg-neutral-100 text-neutral-600', default => 'bg-amber-50 text-amber-700' } ?>">
                  <?= htmlspecialchars(str_replace('_', ' ', $sub['status'])) ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent Invoices -->
  <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
    <p class="text-sm font-semibold uppercase tracking-[0.15em] text-neutral-500">Recent Invoices</p>
    <div class="mt-4 overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-neutral-200 text-left text-xs font-semibold uppercase tracking-wider text-neutral-500">
            <th class="py-3 pr-4">Property</th>
            <th class="py-3 pr-4">Amount</th>
            <th class="py-3 pr-4">Period</th>
            <th class="py-3 pr-4">Issued</th>
            <th class="py-3 pr-4">Status</th>
            <th class="py-3 pr-4">Reference</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
          <?php if (empty($invoices)): ?>
            <tr><td colspan="6" class="py-4 text-center text-sm text-neutral-500">No invoices yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($invoices as $inv): ?>
            <tr class="hover:bg-neutral-50">
              <td class="py-3 pr-4 font-semibold text-neutral-800"><?= htmlspecialchars($inv['property_name']) ?></td>
              <td class="py-3 pr-4 font-bold text-neutral-800">$<?= number_format((float)$inv['amount'], 2) ?></td>
              <td class="py-3 pr-4 text-xs text-neutral-600"><?= htmlspecialchars($inv['period_start']) ?> — <?= htmlspecialchars($inv['period_end']) ?></td>
              <td class="py-3 pr-4 text-xs text-neutral-600"><?= date('M j, Y', strtotime($inv['issued_at'])) ?></td>
              <td class="py-3 pr-4">
                <span class="rounded-pill px-2 py-0.5 text-xs font-bold <?= match($inv['status']) { 'paid' => 'bg-emerald-50 text-emerald-700', 'pending' => 'bg-amber-50 text-amber-700', 'overdue' => 'bg-rose-50 text-rose-700', 'cancelled' => 'bg-neutral-100 text-neutral-600', default => 'bg-neutral-100 text-neutral-600' } ?>">
                  <?= htmlspecialchars(ucfirst($inv['status'])) ?>
                </span>
              </td>
              <td class="py-3 pr-4 text-xs text-neutral-500"><?= htmlspecialchars($inv['transaction_reference'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../partials/admin-footer.php'; ?>
