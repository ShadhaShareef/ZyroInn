<?php
$route = $route ?? 'alerts';
$propertyName = $property['name'] ?? 'Property Console';
$propertyOptions = $propertyOptions ?? [];
$propertyId = $propertyId ?? 0;
$alerts = $alerts ?? [];
$title = 'Alerts - Owner Console';
$highCount = count(array_filter($alerts, fn($a) => $a['priority'] === 'High'));

include __DIR__ . '/../partials/owner-header.php';
?>
    <main class="mx-auto w-full max-w-7xl flex-grow px-4 py-8 pb-24 sm:px-6 lg:px-8 lg:pb-8">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-600">Alerts</p>
          <h1 class="mt-1 text-2xl font-semibold text-brand-900">Stay ahead of property issues</h1>
          <p class="mt-2 text-sm text-neutral-500">Monitor occupancy dips, booking conflicts, and maintenance needs in one feed.</p>
        </div>
        <div class="flex items-center gap-2 rounded-2xl border px-4 py-3 text-sm font-semibold <?= $highCount > 0 ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' ?>">
          <span>●</span>
          <span><?= $highCount ?> high-priority · <?= count($alerts) ?> total</span>
        </div>
      </div>
      <div class="mt-8 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <section class="rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-semibold text-brand-900">All alerts</h2>
              <p class="mt-1 text-sm text-neutral-500">Real-time detections based on current data.</p>
            </div>
            <a href="<?= BASE_URL ?>/owner/index.php?route=dashboard" class="rounded-pill bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-100 transition">Dashboard</a>
          </div>
          <div class="mt-6 space-y-3">
            <?php if (empty($alerts)): ?>
              <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-center">
                <p class="text-lg font-semibold text-emerald-800">All clear</p>
                <p class="mt-1 text-sm text-emerald-700">No alerts currently need your attention.</p>
              </div>
            <?php else: ?>
              <?php foreach ($alerts as $item): ?>
                <article class="rounded-2xl border p-4 <?= $item['priority'] === 'High' ? 'border-rose-200 bg-rose-50' : 'border-neutral-200 bg-neutral-50' ?>">
                  <div class="flex items-start justify-between gap-3">
                    <div>
                      <p class="text-sm font-semibold <?= $item['priority'] === 'High' ? 'text-rose-900' : 'text-brand-900' ?>"><?= htmlspecialchars($item['title']) ?></p>
                      <p class="mt-1 text-sm <?= $item['priority'] === 'High' ? 'text-rose-700' : 'text-neutral-500' ?>"><?= htmlspecialchars($item['detail']) ?></p>
                    </div>
                    <span class="rounded-pill bg-white px-3 py-1 text-xs font-semibold <?= $item['priority'] === 'High' ? 'text-rose-600' : 'text-neutral-600' ?>"><?= htmlspecialchars($item['priority']) ?></span>
                  </div>
                  <div class="mt-3 text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400"><?= htmlspecialchars($item['time']) ?></div>
                </article>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </section>
        <aside class="rounded-3xl border border-neutral-200 bg-neutral-50 p-6 shadow-sm">
          <p class="text-sm font-semibold uppercase tracking-[0.18em] text-neutral-500">Snapshot</p>
          <div class="mt-4 space-y-3">
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">Total alerts</p>
              <p class="mt-2 text-2xl font-semibold text-brand-900"><?= count($alerts) ?></p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">High priority</p>
              <p class="mt-2 text-2xl font-semibold <?= $highCount > 0 ? 'text-rose-600' : 'text-emerald-600' ?>"><?= $highCount ?></p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">Alert types</p>
              <div class="mt-2 space-y-1">
                <?php
                $typeCounts = [];
                foreach ($alerts as $a) {
                    $typeCounts[$a['type']] = ($typeCounts[$a['type']] ?? 0) + 1;
                }
                ?>
                <?php foreach ($typeCounts as $type => $count): ?>
                  <div class="flex items-center justify-between text-sm">
                    <span class="text-neutral-600"><?= htmlspecialchars(ucfirst($type)) ?></span>
                    <span class="font-semibold text-brand-700"><?= $count ?></span>
                  </div>
                <?php endforeach; ?>
                <?php if (empty($typeCounts)): ?>
                  <p class="text-sm text-neutral-500">—</p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </main>
<?php include __DIR__ . '/../partials/owner-footer.php'; ?>
