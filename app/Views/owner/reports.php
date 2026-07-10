<?php
$route = $route ?? 'reports';
$propertyName = $property['name'] ?? 'Property Console';
$propertyOptions = $propertyOptions ?? [];
$propertyId = $propertyId ?? 0;
$title = 'Reports - Owner Console';
$reports = [
    ['name' => 'Occupancy report', 'summary' => '92% average occupancy over the last 30 days', 'status' => 'Ready'],
    ['name' => 'Revenue report', 'summary' => '$18,740 generated with a 12% increase vs last month', 'status' => 'Ready'],
    ['name' => 'Guest review summary', 'summary' => '4.8 average rating and 14 new reviews', 'status' => 'Queued'],
];

include __DIR__ . '/../partials/owner-header.php';
?>
    <main class="mx-auto w-full max-w-7xl flex-grow px-4 py-8 pb-24 sm:px-6 lg:px-8 lg:pb-8">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-600">Reports</p>
          <h1 class="mt-1 text-2xl font-semibold text-brand-900">Export-ready summaries</h1>
          <p class="mt-2 text-sm text-neutral-500">Pull the metrics that matter for owners, lenders, and partners.</p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">Last export 2 hrs ago</div>
      </div>
      <div class="mt-8 grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">Export format</p>
          <p class="mt-2 text-lg font-semibold text-brand-900">PDF + CSV</p>
        </div>
        <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">Last generated</p>
          <p class="mt-2 text-lg font-semibold text-brand-900">Today, 09:30</p>
        </div>
        <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">Recipients</p>
          <p class="mt-2 text-lg font-semibold text-brand-900">Owner + Finance</p>
        </div>
      </div>
      <section class="mt-8 rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl font-semibold text-brand-900">Available reports</h2>
            <p class="mt-1 text-sm text-neutral-500">Useful snapshots for daily operations.</p>
          </div>
          <button class="rounded-pill bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-700">Export all</button>
        </div>
        <div class="mt-6 space-y-3">
          <?php foreach ($reports as $report): ?>
            <article class="flex flex-col gap-3 rounded-2xl border border-neutral-200 bg-neutral-50 p-4 md:flex-row md:items-center md:justify-between">
              <div>
                <p class="text-sm font-semibold text-brand-900"><?= htmlspecialchars($report['name']) ?></p>
                <p class="mt-1 text-sm text-neutral-500"><?= htmlspecialchars($report['summary']) ?></p>
              </div>
              <span class="rounded-pill bg-white px-3 py-1 text-sm font-semibold text-neutral-600"><?= htmlspecialchars($report['status']) ?></span>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    </main>
<?php include __DIR__ . '/../partials/owner-footer.php'; ?>
