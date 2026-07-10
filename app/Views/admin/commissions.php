<?php
$title = "Commission Management";
include __DIR__ . '/../partials/admin-header.php';
?>
<div class="space-y-8">
  <div>
    <h1 class="text-2xl font-bold text-brand-900">Commission Management</h1>
    <p class="text-sm text-neutral-500 mt-1">Track agency bookings, commissions, and payouts across the portfolio.</p>
  </div>

  <!-- Summary -->
  <div class="grid gap-4 sm:grid-cols-3">
    <div class="rounded-3xl border border-neutral-200 bg-white p-4 shadow-sm">
      <p class="text-xs font-semibold uppercase tracking-[0.15em] text-neutral-500">Agency Bookings</p>
      <p class="mt-2 text-2xl font-bold text-brand-900"><?= (int)$summary['active_agency_bookings'] ?></p>
      <p class="text-xs text-neutral-500"><?= (int)$summary['total_agency_bookings'] ?> total</p>
    </div>
    <div class="rounded-3xl border border-neutral-200 bg-white p-4 shadow-sm">
      <p class="text-xs font-semibold uppercase tracking-[0.15em] text-neutral-500">Total Paid</p>
      <p class="mt-2 text-2xl font-bold text-emerald-700">$<?= number_format((float)$summary['total_paid'], 2) ?></p>
      <p class="text-xs text-neutral-500"><?= (int)$summary['total_payouts'] ?> total payouts</p>
    </div>
    <div class="rounded-3xl border border-neutral-200 bg-white p-4 shadow-sm">
      <p class="text-xs font-semibold uppercase tracking-[0.15em] text-neutral-500">Pending Payouts</p>
      <p class="mt-2 text-2xl font-bold text-amber-700">$<?= number_format((float)$summary['total_pending'], 2) ?></p>
      <p class="text-xs text-neutral-500">Awaiting approval/payment</p>
    </div>
  </div>

  <!-- Agency Bookings Table -->
  <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
    <p class="text-sm font-semibold uppercase tracking-[0.15em] text-neutral-500">Agency Bookings</p>
    <div class="mt-4 overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-neutral-200 text-left text-xs font-semibold uppercase tracking-wider text-neutral-500">
            <th class="py-3 pr-4">Booking</th>
            <th class="py-3 pr-4">Property</th>
            <th class="py-3 pr-4">Guest</th>
            <th class="py-3 pr-4">Commission %</th>
            <th class="py-3 pr-4">Est. Amount</th>
            <th class="py-3 pr-4">Status</th>
            <th class="py-3 pr-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
          <?php if (empty($agencyBookings)): ?>
            <tr><td colspan="7" class="py-6 text-center text-sm text-neutral-500">No agency bookings found.</td></tr>
          <?php endif; ?>
          <?php foreach ($agencyBookings as $ab): ?>
            <tr class="hover:bg-neutral-50">
              <td class="py-3 pr-4">
                <p class="font-semibold text-neutral-800">#<?= $ab['id'] ?></p>
                <p class="text-xs text-neutral-500"><?= htmlspecialchars($ab['check_in_date']) ?> — <?= htmlspecialchars($ab['check_out_date']) ?></p>
              </td>
              <td class="py-3 pr-4 text-neutral-800"><?= htmlspecialchars($ab['property_name'] ?? '') ?></td>
              <td class="py-3 pr-4">
                <p class="text-neutral-800"><?= htmlspecialchars(($ab['first_name'] ?? '') . ' ' . ($ab['last_name'] ?? '')) ?></p>
                <p class="text-xs text-neutral-500"><?= htmlspecialchars($ab['email'] ?? '') ?></p>
              </td>
              <td class="py-3 pr-4">
                <span class="font-bold text-amber-700"><?= htmlspecialchars($ab['commission_percentage']) ?>%</span>
              </td>
              <td class="py-3 pr-4 font-semibold">
                <?php
                $estCommission = (float)$ab['total_paid'] * ((float)$ab['commission_percentage'] / 100);
                ?>
                $<?= number_format($estCommission, 2) ?>
              </td>
              <td class="py-3 pr-4">
                <span class="rounded-pill px-2 py-0.5 text-xs font-bold <?= match($ab['booking_status']) { 'confirmed' => 'bg-emerald-50 text-emerald-700', 'checked_in' => 'bg-blue-50 text-blue-700', 'checked_out' => 'bg-neutral-100 text-neutral-600', 'cancelled' => 'bg-rose-50 text-rose-700', default => 'bg-amber-50 text-amber-700' } ?>">
                  <?= htmlspecialchars(str_replace('_', ' ', $ab['booking_status'])) ?>
                </span>
              </td>
              <td class="py-3 pr-4 text-right">
                <?php if ($ab['booking_status'] !== 'cancelled' && (float)$ab['total_paid'] > 0): ?>
                <div x-data="{ open: false }">
                  <button @click="open = true" class="rounded-2xl bg-brand-500 px-3 py-1 text-xs font-bold text-white hover:bg-brand-600 transition">Create Payout</button>
                  <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="open = false">
                    <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl mx-4">
                      <h3 class="text-lg font-bold text-brand-900">Create Commission Payout</h3>
                      <p class="mt-1 text-sm text-neutral-500">Booking #<?= $ab['id'] ?> — <?= htmlspecialchars($ab['property_name']) ?></p>
                      <form method="POST" action="index.php" class="mt-4 space-y-3">
                        <input type="hidden" name="action" value="create_payout">
                        <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
                        <input type="hidden" name="booking_id" value="<?= $ab['id'] ?>">
                        <input type="hidden" name="property_id" value="<?= $ab['property_id'] ?>">
                        <input type="hidden" name="percentage" value="<?= htmlspecialchars($ab['commission_percentage']) ?>">
                        <label class="block text-sm font-semibold text-neutral-700">
                          <span>Agency Name</span>
                          <input type="text" name="agency_name" required class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                        </label>
                        <label class="block text-sm font-semibold text-neutral-700">
                          <span>Amount</span>
                          <input type="number" step="0.01" name="amount" value="<?= round($estCommission, 2) ?>" required class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                        </label>
                        <div class="flex gap-2">
                          <button type="submit" class="rounded-2xl bg-brand-500 px-4 py-2 text-sm font-bold text-white hover:bg-brand-600 transition">Create</button>
                          <button type="button" @click="open = false" class="rounded-2xl border border-neutral-200 px-4 py-2 text-sm font-bold text-neutral-600 hover:bg-neutral-50 transition">Cancel</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Payouts Table -->
  <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
    <div class="flex items-center justify-between">
      <p class="text-sm font-semibold uppercase tracking-[0.15em] text-neutral-500">Payout History</p>
      <div class="flex gap-1">
        <a href="?route=commissions" class="rounded-pill px-2 py-1 text-xs font-bold <?= $payoutStatus === '' ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-600' ?>">All</a>
        <a href="?route=commissions&payout_status=pending" class="rounded-pill px-2 py-1 text-xs font-bold <?= $payoutStatus === 'pending' ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-600' ?>">Pending</a>
        <a href="?route=commissions&payout_status=paid" class="rounded-pill px-2 py-1 text-xs font-bold <?= $payoutStatus === 'paid' ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-600' ?>">Paid</a>
      </div>
    </div>
    <div class="mt-4 overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-neutral-200 text-left text-xs font-semibold uppercase tracking-wider text-neutral-500">
            <th class="py-3 pr-4">Agency</th>
            <th class="py-3 pr-4">Property</th>
            <th class="py-3 pr-4">Booking</th>
            <th class="py-3 pr-4">Amount</th>
            <th class="py-3 pr-4">%</th>
            <th class="py-3 pr-4">Status</th>
            <th class="py-3 pr-4">Paid At</th>
            <th class="py-3 pr-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
          <?php if (empty($payouts)): ?>
            <tr><td colspan="8" class="py-6 text-center text-sm text-neutral-500">No payouts recorded.</td></tr>
          <?php endif; ?>
          <?php foreach ($payouts as $p): ?>
            <tr class="hover:bg-neutral-50">
              <td class="py-3 pr-4 font-semibold text-neutral-800"><?= htmlspecialchars($p['agency_name']) ?></td>
              <td class="py-3 pr-4 text-neutral-600"><?= htmlspecialchars($p['property_name']) ?></td>
              <td class="py-3 pr-4 text-neutral-600">#<?= $p['booking_id'] ?></td>
              <td class="py-3 pr-4 font-bold">$<?= number_format((float)$p['commission_amount'], 2) ?></td>
              <td class="py-3 pr-4"><?= htmlspecialchars($p['commission_percentage']) ?>%</td>
              <td class="py-3 pr-4">
                <span class="rounded-pill px-2 py-0.5 text-xs font-bold <?= match($p['status']) { 'paid' => 'bg-emerald-50 text-emerald-700', 'approved' => 'bg-blue-50 text-blue-700', 'pending' => 'bg-amber-50 text-amber-700', 'cancelled' => 'bg-rose-50 text-rose-700', default => 'bg-neutral-100 text-neutral-600' } ?>">
                  <?= htmlspecialchars(ucfirst($p['status'])) ?>
                </span>
              </td>
              <td class="py-3 pr-4 text-xs text-neutral-500"><?= $p['paid_at'] ? date('M j, Y', strtotime($p['paid_at'])) : '—' ?></td>
              <td class="py-3 pr-4 text-right">
                <?php if ($p['status'] === 'pending'): ?>
                  <form method="POST" action="index.php" class="inline-flex gap-1">
                    <input type="hidden" name="action" value="commission_action">
                    <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit" name="status" value="approved" class="rounded-2xl bg-blue-500 px-2 py-1 text-xs font-bold text-white hover:bg-blue-600 transition">Approve</button>
                    <button type="submit" name="status" value="paid" class="rounded-2xl bg-emerald-500 px-2 py-1 text-xs font-bold text-white hover:bg-emerald-600 transition">Pay</button>
                    <button type="submit" name="status" value="cancelled" class="rounded-2xl bg-rose-500 px-2 py-1 text-xs font-bold text-white hover:bg-rose-600 transition">Cancel</button>
                  </form>
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
