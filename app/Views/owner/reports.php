<?php
$route = $route ?? 'reports';
$propertyName = $property['name'] ?? 'Property Console';
$propertyOptions = $propertyOptions ?? [];
$propertyId = $propertyId ?? 0;
$occupancyData = $occupancyData ?? [];
$revenueData = $revenueData ?? [];
$title = 'Reports';

$avgOccupancy = 0;
if (!empty($occupancyData)) {
    $totalPct = 0;
    foreach ($occupancyData as $r) {
        $pct = ($r['total_rooms'] ?? 1) > 0 ? (($r['occupied_cnt'] ?? 0) / $r['total_rooms']) * 100 : 0;
        $totalPct += $pct;
    }
    $avgOccupancy = round($totalPct / count($occupancyData), 1);
}

$totalRevenue = 0;
$totalBookings = 0;
if (!empty($revenueData)) {
    foreach ($revenueData as $r) {
        $totalRevenue += (float)($r['revenue'] ?? 0);
        $totalBookings += (int)($r['bookings'] ?? 0);
    }
}

include __DIR__ . '/../partials/owner-header.php';
?>
<div class="space-y-6">
  <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
    <div>
      <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-600">Reports</p>
      <h1 class="mt-1 text-2xl font-semibold text-brand-900">Export-ready summaries</h1>
      <p class="mt-2 text-sm text-neutral-500">Pull the metrics that matter for owners, lenders, and partners.</p>
    </div>
  </div>

  <div class="grid gap-4 md:grid-cols-3">
    <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
      <p class="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">Avg Occupancy (30d)</p>
      <p class="mt-2 text-2xl font-semibold text-brand-900"><?= $avgOccupancy ?>%</p>
    </div>
    <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
      <p class="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">Total Revenue</p>
      <p class="mt-2 text-2xl font-semibold text-brand-900">$<?= number_format($totalRevenue, 0) ?></p>
    </div>
    <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
      <p class="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">Total Bookings</p>
      <p class="mt-2 text-2xl font-semibold text-brand-900"><?= $totalBookings ?></p>
    </div>
  </div>

  <div class="grid gap-6 lg:grid-cols-2">
    <!-- Occupancy Report Card -->
    <div class="rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h2 class="text-lg font-semibold text-brand-900">Occupancy Report</h2>
          <p class="text-sm text-neutral-500">Last 30 days</p>
        </div>
        <a href="<?= BASE_URL ?>/owner/index.php?route=export-report&type=occupancy&property_id=<?= $propertyId ?>"
           class="inline-flex items-center gap-2 rounded-pill bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-100 transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Export CSV
        </a>
      </div>
      <?php
      $hasOccData = !empty($occupancyData);
      $maxOcc = 0;
      if ($hasOccData) {
          foreach ($occupancyData as $r) { $occ = ($r['total_rooms'] ?? 1) > 0 ? (($r['occupied_cnt'] ?? 0) / $r['total_rooms']) * 100 : 0; $maxOcc = max($maxOcc, $occ); }
      }
      $showBars = $hasOccData;
      ?>
      <?php if ($showBars): ?>
      <?php $maxOcc = $maxOcc > 0 ? $maxOcc : 1; ?>
      <div class="h-40 flex items-end gap-px overflow-x-auto px-1">
        <?php foreach ($occupancyData as $r):
          $pct = ($r['total_rooms'] ?? 1) > 0 ? (($r['occupied_cnt'] ?? 0) / $r['total_rooms']) * 100 : 0;
          $barH = ($pct / $maxOcc) * 100;
        ?>
          <div class="flex flex-col items-center justify-end gap-0.5 min-w-[22px] flex-shrink-0 h-full">
            <?php if ($pct >= 5): ?>
              <span class="text-[8px] font-semibold text-neutral-500 leading-tight"><?= round($pct) ?>%</span>
            <?php endif; ?>
            <div class="w-3/5 rounded-t-sm <?= $pct > 0 ? 'bg-brand-500' : 'bg-neutral-200' ?>" style="height: <?= max($barH, 4) ?>%"></div>
            <span class="text-[7px] text-neutral-400 leading-tight"><?= date('j', strtotime($r['date'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="h-40 flex items-center justify-center rounded-2xl bg-neutral-50 border border-dashed border-neutral-200">
        <p class="text-sm text-neutral-400">No occupancy data available yet.</p>
      </div>
      <?php endif; ?>
    </div>

    <!-- Revenue Report Card -->
    <div class="rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h2 class="text-lg font-semibold text-brand-900">Revenue Report</h2>
          <p class="text-sm text-neutral-500">Monthly breakdown</p>
        </div>
        <a href="<?= BASE_URL ?>/owner/index.php?route=export-report&type=revenue&property_id=<?= $propertyId ?>"
           class="inline-flex items-center gap-2 rounded-pill bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-100 transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Export CSV
        </a>
      </div>
      <?php if (!empty($revenueData)): ?>
      <div class="space-y-3">
        <?php foreach ($revenueData as $r): ?>
          <div class="flex items-center justify-between py-2 border-b border-neutral-100 last:border-0">
            <span class="text-sm font-semibold text-neutral-700"><?= htmlspecialchars($r['month']) ?></span>
            <div class="text-right">
              <p class="text-sm font-bold text-brand-700">$<?= number_format((float)($r['revenue'] ?? 0), 0) ?></p>
              <p class="text-xs text-neutral-500"><?= (int)($r['bookings'] ?? 0) ?> bookings</p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="h-32 flex items-center justify-center rounded-2xl bg-neutral-50 border border-dashed border-neutral-200">
        <p class="text-sm text-neutral-400">No revenue data available yet.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../partials/owner-footer.php'; ?>
