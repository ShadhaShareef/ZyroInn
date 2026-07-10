<?php
$route = $route ?? 'expenses';
$propertyName = $property['name'] ?? 'Property Console';
$propertyOptions = $propertyOptions ?? [];
$propertyId = $propertyId ?? 0;
$title = 'Expenses - Owner Console';
$expenses = [
    ['title' => 'Housekeeping supplies', 'vendor' => 'North Star Supply', 'amount' => '$412.80', 'date' => 'Today'],
    ['title' => 'Maintenance repair', 'vendor' => 'BluePeak HVAC', 'amount' => '$1,230.00', 'date' => 'Yesterday'],
    ['title' => 'Marketing campaign', 'vendor' => 'Ridge Studio', 'amount' => '$680.00', 'date' => '2 days ago'],
];

include __DIR__ . '/../partials/owner-header.php';
?>
    <main class="mx-auto w-full max-w-7xl flex-grow px-4 py-8 pb-24 sm:px-6 lg:px-8 lg:pb-8">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-600">Expenses</p>
          <h1 class="mt-1 text-2xl font-semibold text-brand-900">Track spending and approvals</h1>
          <p class="mt-2 text-sm text-neutral-500">Review recurring costs, upcoming invoices, and budget health.</p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700">Budget on track</div>
      </div>
      <div class="mt-8 grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">Monthly spend</p>
          <p class="mt-2 text-2xl font-semibold text-brand-900">$8,420</p>
        </div>
        <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">Pending</p>
          <p class="mt-2 text-2xl font-semibold text-brand-900">4</p>
        </div>
        <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">Upcoming bills</p>
          <p class="mt-2 text-2xl font-semibold text-brand-900">$2,150</p>
        </div>
      </div>
      <section class="mt-8 rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl font-semibold text-brand-900">Recent expenses</h2>
            <p class="mt-1 text-sm text-neutral-500">Latest purchases and approvals.</p>
          </div>
          <button class="rounded-pill bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-700">Create expense</button>
        </div>
        <div class="mt-6 space-y-3">
          <?php foreach ($expenses as $expense): ?>
            <article class="flex flex-col gap-3 rounded-2xl border border-neutral-200 bg-neutral-50 p-4 md:flex-row md:items-center md:justify-between">
              <div>
                <p class="text-sm font-semibold text-brand-900"><?= htmlspecialchars($expense['title']) ?></p>
                <p class="mt-1 text-sm text-neutral-500"><?= htmlspecialchars($expense['vendor']) ?></p>
              </div>
              <div class="text-sm text-neutral-600"><?= htmlspecialchars($expense['date']) ?></div>
              <div class="text-sm font-semibold text-brand-700"><?= htmlspecialchars($expense['amount']) ?></div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    </main>
<?php include __DIR__ . '/../partials/owner-footer.php'; ?>
