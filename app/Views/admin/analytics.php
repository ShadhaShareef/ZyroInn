<?php
$title = "Global Analytics";
include __DIR__ . '/../partials/admin-header.php';
?>
<div class="space-y-8">
  <div>
    <h1 class="text-2xl font-bold text-brand-900">Global Analytics</h1>
    <p class="text-sm text-neutral-500 mt-1">Cross-portfolio performance metrics and trends.</p>
  </div>

  <!-- KPI Grid -->
  <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
      <p class="text-xs font-semibold uppercase tracking-[0.15em] text-neutral-500">Properties</p>
      <p class="mt-2 text-3xl font-bold text-brand-900"><?= (int)$kpis['total_properties'] ?></p>
      <p class="mt-1 text-xs text-neutral-500">Active</p>
    </div>
    <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
      <p class="text-xs font-semibold uppercase tracking-[0.15em] text-neutral-500">Total Rooms</p>
      <p class="mt-2 text-3xl font-bold text-brand-900"><?= (int)$kpis['total_rooms'] ?></p>
      <p class="mt-1 text-xs text-neutral-500"><?= (int)$kpis['occupancy_rate'] ?>% occupied</p>
    </div>
    <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
      <p class="text-xs font-semibold uppercase tracking-[0.15em] text-neutral-500">Total Revenue</p>
      <p class="mt-2 text-3xl font-bold text-emerald-700">$<?= number_format((float)$kpis['total_revenue'], 0) ?></p>
      <p class="mt-1 text-xs text-neutral-500">All time</p>
    </div>
    <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
      <p class="text-xs font-semibold uppercase tracking-[0.15em] text-neutral-500">Avg Rating</p>
      <p class="mt-2 text-3xl font-bold text-brand-900"><?= htmlspecialchars($kpis['avg_rating']) ?></p>
      <p class="mt-1 text-xs text-neutral-500"><?= (int)$kpis['total_bookings'] ?> bookings, <?= (int)$kpis['total_guests'] ?> guests</p>
    </div>
  </div>

  <!-- Revenue Trend 12 months -->
  <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
    <p class="text-sm font-semibold uppercase tracking-[0.15em] text-neutral-500">Revenue Trend</p>
    <h3 class="mt-1 text-lg font-semibold text-brand-900">Last 12 months</h3>
    <div class="mt-4 h-48 flex items-end gap-2">
      <?php
      $maxRev = 0;
      foreach ($revenueTrend as $r) { $maxRev = max($maxRev, (float)$r['revenue']); }
      if ($maxRev <= 0) $maxRev = 1;
      foreach ($revenueTrend as $r):
        $pct = ((float)$r['revenue'] / $maxRev) * 100;
      ?>
        <div class="flex-1 flex flex-col items-center gap-1">
          <span class="text-[10px] font-semibold text-neutral-500">$<?= number_format((float)$r['revenue'], 0) ?></span>
          <div class="w-full rounded-t-2xl bg-gradient-to-t from-brand-600 to-brand-400" style="height: <?= $pct ?>%"></div>
          <span class="text-[10px] text-neutral-400"><?= htmlspecialchars($r['month'] ?? '') ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Occupancy by Property -->
  <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
    <p class="text-sm font-semibold uppercase tracking-[0.15em] text-neutral-500">Occupancy by Property</p>
    <div class="mt-4 space-y-3">
      <?php foreach ($occupancyByProperty as $occ):
        $occRate = (int)$occ['total_rooms'] > 0 ? round(((int)$occ['occupied_rooms'] / (int)$occ['total_rooms']) * 100, 1) : 0;
      ?>
        <div class="flex items-center gap-3">
          <span class="w-48 text-sm font-semibold text-neutral-700 truncate"><?= htmlspecialchars($occ['name']) ?></span>
          <div class="flex-1 h-4 rounded-pill bg-neutral-100 overflow-hidden">
            <div class="h-full rounded-pill <?= $occRate > 80 ? 'bg-emerald-500' : ($occRate > 50 ? 'bg-amber-500' : 'bg-rose-500') ?>" style="width: <?= $occRate ?>%"></div>
          </div>
          <span class="text-sm font-bold text-neutral-700 w-20 text-right"><?= $occRate ?>%</span>
          <span class="text-xs text-neutral-500 w-24 text-right"><?= (int)$occ['occupied_rooms'] ?>/<?= (int)$occ['total_rooms'] ?> rooms</span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Top Properties -->
  <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
    <p class="text-sm font-semibold uppercase tracking-[0.15em] text-neutral-500">Top Properties</p>
    <h3 class="mt-1 text-lg font-semibold text-brand-900">By total revenue</h3>
    <div class="mt-4 overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-neutral-200 text-left text-xs font-semibold uppercase tracking-wider text-neutral-500">
            <th class="py-3 pr-4">#</th>
            <th class="py-3 pr-4">Property</th>
            <th class="py-3 pr-4">Location</th>
            <th class="py-3 pr-4">Bookings</th>
            <th class="py-3 pr-4 text-right">Revenue</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
          <?php foreach ($topProperties as $i => $prop): ?>
            <tr class="hover:bg-neutral-50">
              <td class="py-3 pr-4 font-bold text-brand-700"><?= $i + 1 ?></td>
              <td class="py-3 pr-4 font-semibold text-neutral-800"><?= htmlspecialchars($prop['name']) ?></td>
              <td class="py-3 pr-4 text-neutral-600"><?= htmlspecialchars($prop['city'] ?? '') ?>, <?= htmlspecialchars($prop['state'] ?? '') ?></td>
              <td class="py-3 pr-4"><?= (int)$prop['booking_count'] ?></td>
              <td class="py-3 pr-4 text-right font-bold text-emerald-700">$<?= number_format((float)$prop['revenue'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Booking Source Breakdown -->
  <div class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm">
    <p class="text-sm font-semibold uppercase tracking-[0.15em] text-neutral-500">Booking Sources</p>
    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
      <?php $totalSrc = array_sum(array_column($sourceBreakdown, 'count')); ?>
      <?php foreach ($sourceBreakdown as $src):
        $pct = $totalSrc > 0 ? round(((int)$src['count'] / $totalSrc) * 100, 1) : 0;
      ?>
        <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-4 text-center">
          <p class="text-2xl font-bold text-brand-700"><?= $pct ?>%</p>
          <p class="text-sm font-semibold text-neutral-700"><?= htmlspecialchars(ucfirst($src['source'] ?? 'Unknown')) ?></p>
          <p class="text-xs text-neutral-500"><?= (int)$src['count'] ?> bookings</p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../partials/admin-footer.php'; ?>
