<?php
$title = "Platform Dashboard";
include __DIR__ . '/../partials/admin-header.php';
?>
<div class="space-y-8">
  <!-- KPI Cards -->
  <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
      <p class="text-xs font-semibold uppercase tracking-[0.15em] text-neutral-500">Properties</p>
      <p class="mt-2 text-3xl font-bold text-brand-900"><?= (int)$kpis['total_properties'] ?></p>
      <p class="mt-1 text-xs text-neutral-500"><?= (int)$kpis['total_rooms'] ?> rooms across portfolio</p>
    </div>
    <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
      <p class="text-xs font-semibold uppercase tracking-[0.15em] text-neutral-500">Occupancy</p>
      <p class="mt-2 text-3xl font-bold text-brand-900"><?= htmlspecialchars($kpis['occupancy_rate']) ?>%</p>
      <p class="mt-1 text-xs text-neutral-500"><?= number_format($kpis['total_revenue'], 2) ?> total revenue</p>
    </div>
    <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
      <p class="text-xs font-semibold uppercase tracking-[0.15em] text-neutral-500">Bookings</p>
      <p class="mt-2 text-3xl font-bold text-brand-900"><?= (int)$kpis['total_bookings'] ?></p>
      <p class="mt-1 text-xs text-neutral-500"><?= (int)$kpis['active_bookings'] ?> active</p>
    </div>
    <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
      <p class="text-xs font-semibold uppercase tracking-[0.15em] text-neutral-500">Avg Rating</p>
      <p class="mt-2 text-3xl font-bold text-brand-900"><?= htmlspecialchars($kpis['avg_rating']) ?></p>
      <p class="mt-1 text-xs text-neutral-500"><?= (int)$kpis['total_guests'] ?> total guests</p>
    </div>
  </div>

  <!-- Alert Badges -->
  <div class="flex flex-wrap gap-3">
    <?php if ($pendingOnboarding > 0): ?>
      <a href="index.php?route=onboarding" class="rounded-pill bg-amber-50 border border-amber-200 px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-100 transition">📋 <?= $pendingOnboarding ?> pending onboarding</a>
    <?php endif; ?>
    <?php if ($openTickets > 0): ?>
      <a href="index.php?route=support" class="rounded-pill bg-rose-50 border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-800 hover:bg-rose-100 transition">🎫 <?= $openTickets ?> open tickets</a>
    <?php endif; ?>
    <?php if ($pendingReviews > 0): ?>
      <a href="index.php?route=moderation" class="rounded-pill bg-blue-50 border border-blue-200 px-4 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-100 transition">✏️ <?= $pendingReviews ?> reviews to moderate</a>
    <?php endif; ?>
    <?php if ($openFlags > 0): ?>
      <a href="index.php?route=fraud" class="rounded-pill bg-red-50 border border-red-200 px-4 py-2 text-sm font-semibold text-red-800 hover:bg-red-100 transition">🚨 <?= $openFlags ?> active fraud flags</a>
    <?php endif; ?>
  </div>

  <!-- Revenue Trend + Source Breakdown -->
  <div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
      <p class="text-sm font-semibold uppercase tracking-[0.15em] text-neutral-500">Revenue Trend</p>
      <h3 class="mt-1 text-lg font-semibold text-brand-900">Last 6 months</h3>
      <div class="mt-4 h-40 flex items-end gap-2">
        <?php
        $maxRev = 0;
        foreach ($revenueTrend as $r) { $maxRev = max($maxRev, (float)$r['revenue']); }
        if ($maxRev <= 0) $maxRev = 1;
        foreach ($revenueTrend as $r):
          $pct = ((float)$r['revenue'] / $maxRev) * 100;
        ?>
          <div class="flex-1 flex flex-col items-center gap-1">
            <span class="text-[10px] font-semibold text-neutral-500">$<?= number_format((float)$r['revenue'], 0) ?></span>
            <div class="w-full rounded-t-2xl bg-brand-500" style="height: <?= $pct ?>%"></div>
            <span class="text-[10px] text-neutral-400"><?= htmlspecialchars($r['month'] ?? '') ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
      <p class="text-sm font-semibold uppercase tracking-[0.15em] text-neutral-500">Booking Sources</p>
      <h3 class="mt-1 text-lg font-semibold text-brand-900">Acquisition breakdown</h3>
      <div class="mt-4 space-y-3">
        <?php $totalSrc = array_sum(array_column($sourceBreakdown, 'count')); ?>
        <?php foreach ($sourceBreakdown as $src):
          $pct = $totalSrc > 0 ? round(((int)$src['count'] / $totalSrc) * 100, 1) : 0;
        ?>
          <div class="flex items-center gap-3">
            <span class="w-24 text-sm font-semibold text-neutral-700"><?= htmlspecialchars(ucfirst($src['source'] ?? 'Unknown')) ?></span>
            <div class="flex-1 h-3 rounded-pill bg-neutral-100 overflow-hidden">
              <div class="h-full rounded-pill bg-brand-500" style="width: <?= $pct ?>%"></div>
            </div>
            <span class="text-sm font-semibold text-neutral-600 w-12 text-right"><?= $pct ?>%</span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Top Properties + Recent Activity -->
  <div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
      <p class="text-sm font-semibold uppercase tracking-[0.15em] text-neutral-500">Top Properties</p>
      <h3 class="mt-1 text-lg font-semibold text-brand-900">By revenue</h3>
      <div class="mt-4 divide-y divide-neutral-100">
        <?php foreach ($topProperties as $i => $prop): ?>
          <div class="flex items-center justify-between py-3 <?= $i === 0 ? 'pt-0' : '' ?>">
            <div class="flex items-center gap-3">
              <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-50 text-xs font-bold text-brand-700"><?= $i + 1 ?></span>
              <div>
                <p class="text-sm font-semibold text-neutral-800"><?= htmlspecialchars($prop['name']) ?></p>
                <p class="text-xs text-neutral-500"><?= htmlspecialchars($prop['city'] ?? '') ?>, <?= htmlspecialchars($prop['state'] ?? '') ?></p>
              </div>
            </div>
            <div class="text-right">
              <p class="text-sm font-bold text-brand-700">$<?= number_format((float)$prop['revenue'], 0) ?></p>
              <p class="text-xs text-neutral-500"><?= (int)$prop['booking_count'] ?> bookings</p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
      <p class="text-sm font-semibold uppercase tracking-[0.15em] text-neutral-500">Recent Activity</p>
      <h3 class="mt-1 text-lg font-semibold text-brand-900">Latest bookings</h3>
      <div class="mt-4 divide-y divide-neutral-100">
        <?php foreach ($recentActivity as $act): ?>
          <div class="flex items-center justify-between py-3">
            <div>
              <p class="text-sm font-semibold text-neutral-800"><?= htmlspecialchars(($act['first_name'] ?? '') . ' ' . ($act['last_name'] ?? 'Guest')) ?></p>
              <p class="text-xs text-neutral-500"><?= htmlspecialchars($act['property_name'] ?? '') ?></p>
            </div>
            <div class="text-right">
              <span class="rounded-pill px-2 py-0.5 text-[10px] font-bold uppercase <?php
                $s = $act['status'] ?? '';
                if ($s === 'confirmed') echo 'bg-emerald-50 text-emerald-700';
                elseif ($s === 'checked_in') echo 'bg-blue-50 text-blue-700';
                elseif ($s === 'checked_out') echo 'bg-neutral-100 text-neutral-600';
                elseif ($s === 'cancelled') echo 'bg-rose-50 text-rose-700';
                else echo 'bg-amber-50 text-amber-700';
              ?>"><?= htmlspecialchars(str_replace('_', ' ', $s)) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Occupancy by Property Table -->
  <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
    <p class="text-sm font-semibold uppercase tracking-[0.15em] text-neutral-500">Occupancy by Property</p>
    <div class="mt-4 overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-neutral-200 text-left text-xs font-semibold uppercase tracking-wider text-neutral-500">
            <th class="py-3 pr-4">Property</th>
            <th class="py-3 pr-4">Total Rooms</th>
            <th class="py-3 pr-4">Occupied</th>
            <th class="py-3 pr-4">Rate</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
          <?php foreach ($occupancyByProperty as $occ): ?>
            <tr class="hover:bg-neutral-50">
              <td class="py-3 pr-4 font-semibold text-neutral-800"><?= htmlspecialchars($occ['name']) ?></td>
              <td class="py-3 pr-4 text-neutral-600"><?= (int)$occ['total_rooms'] ?></td>
              <td class="py-3 pr-4 text-neutral-600"><?= (int)$occ['occupied_rooms'] ?></td>
              <td class="py-3 pr-4">
                <?php $occRate = (int)$occ['total_rooms'] > 0 ? round(((int)$occ['occupied_rooms'] / (int)$occ['total_rooms']) * 100, 1) : 0; ?>
                <span class="rounded-pill px-2 py-0.5 text-xs font-bold <?= $occRate > 80 ? 'bg-emerald-50 text-emerald-700' : ($occRate > 50 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') ?>"><?= $occRate ?>%</span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../partials/admin-footer.php'; ?>
