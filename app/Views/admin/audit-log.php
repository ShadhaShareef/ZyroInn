<?php
$title = "Audit Log";
include __DIR__ . '/../partials/admin-header.php';


function actionBadgeClass(string $action): string {
    if (str_contains($action, 'approved') || str_contains($action, 'paid') || $action === 'dispute_resolved' || $action === 'fraud_resolved') return 'bg-emerald-50 text-emerald-700';
    if (str_contains($action, 'rejected') || str_contains($action, 'dismissed') || str_contains($action, 'deleted')) return 'bg-rose-50 text-rose-700';
    if (str_contains($action, 'changed') || str_contains($action, 'moderated')) return 'bg-blue-50 text-blue-700';
    if (str_contains($action, 'assigned')) return 'bg-amber-50 text-amber-700';
    return 'bg-neutral-100 text-neutral-600';
}
?>
<div class="space-y-6">
  <div>
    <h1 class="text-2xl font-bold text-brand-900">Audit Log</h1>
    <p class="text-sm text-neutral-500 mt-1">Review all admin actions across the platform — approvals, commissions, disputes, moderation, and support.</p>
  </div>

  <!-- Filters -->
  <div x-data="{ filtersOpen: false }" class="rounded-3xl border border-neutral-200 bg-white p-4 shadow-sm">
    <div class="flex items-center justify-between">
      <p class="text-sm font-semibold uppercase tracking-[0.15em] text-neutral-500">Filters</p>
      <div class="flex items-center gap-2">
        <?php if ($filterAdmin !== '' || $filterAction !== '' || $filterEntity !== '' || $filterDateFrom !== '' || $filterDateTo !== ''): ?>
          <a href="?route=audit-log" class="text-xs font-semibold text-rose-600 hover:text-rose-700 transition">Clear filters</a>
        <?php endif; ?>
        <button @click="filtersOpen = !filtersOpen" class="rounded-pill bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-600 hover:bg-neutral-200 transition md:hidden">
          <span x-text="filtersOpen ? 'Hide' : 'Show'"></span>
        </button>
      </div>
    </div>
    <form method="GET" action="index.php" class="mt-3">
      <input type="hidden" name="route" value="audit-log">
      <div class="flex flex-wrap items-end gap-3" :class="filtersOpen ? '' : 'hidden md:flex'">
        <label class="flex flex-col gap-1">
          <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-500">Admin</span>
          <select name="admin" class="rounded-2xl border border-neutral-200 px-3 py-1.5 text-sm outline-none focus:border-brand-500 min-w-[140px] bg-white">
            <option value="">All admins</option>
            <?php foreach ($adminNames as $name): ?>
              <option value="<?= htmlspecialchars($name) ?>" <?= $filterAdmin === $name ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="flex flex-col gap-1">
          <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-500">Action</span>
          <select name="action" class="rounded-2xl border border-neutral-200 px-3 py-1.5 text-sm outline-none focus:border-brand-500 min-w-[180px] bg-white">
            <option value="">All actions</option>
            <?php foreach ($actionLabels as $key => $label): ?>
              <option value="<?= htmlspecialchars($key) ?>" <?= $filterAction === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="flex flex-col gap-1">
          <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-500">Entity</span>
          <select name="entity" class="rounded-2xl border border-neutral-200 px-3 py-1.5 text-sm outline-none focus:border-brand-500 min-w-[140px] bg-white">
            <option value="">All entities</option>
            <?php foreach ($entityTypeLabels as $key => $label): ?>
              <option value="<?= htmlspecialchars($key) ?>" <?= $filterEntity === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="flex flex-col gap-1">
          <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-500">From</span>
          <input type="date" name="date_from" value="<?= htmlspecialchars($filterDateFrom) ?>" class="rounded-2xl border border-neutral-200 px-3 py-1.5 text-sm outline-none focus:border-brand-500 bg-white">
        </label>
        <label class="flex flex-col gap-1">
          <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-500">To</span>
          <input type="date" name="date_to" value="<?= htmlspecialchars($filterDateTo) ?>" class="rounded-2xl border border-neutral-200 px-3 py-1.5 text-sm outline-none focus:border-brand-500 bg-white">
        </label>
        <button type="submit" class="rounded-2xl bg-brand-500 px-4 py-1.5 text-sm font-bold text-white hover:bg-brand-600 transition">Apply</button>
      </div>
    </form>
  </div>

  <!-- Results count -->
  <p class="text-xs text-neutral-500">
    Showing <?= count($entries) ?> of <?= $totalEntries ?> entries
    <?php if ($filterAdmin !== '' || $filterAction !== '' || $filterEntity !== '' || $filterDateFrom !== '' || $filterDateTo !== ''): ?>
      (filtered)
    <?php endif; ?>
  </p>

  <!-- Log Table -->
  <div class="rounded-3xl border border-neutral-200 bg-white shadow-sm overflow-hidden">
    <?php if (empty($entries) && $totalEntries === 0): ?>
      <div class="flex flex-col items-center justify-center py-16 px-4 text-center">
        <span class="w-12 h-12 text-neutral-300 mb-3"><?= svg_icon('clock', 'w-12 h-12') ?></span>
        <p class="text-base font-semibold text-neutral-700">No admin actions recorded yet</p>
        <p class="mt-1 text-sm text-neutral-500">Admin actions will appear here as they are performed across the platform.</p>
      </div>
    <?php elseif (empty($entries)): ?>
      <div class="flex flex-col items-center justify-center py-16 px-4 text-center">
        <span class="w-12 h-12 text-neutral-300 mb-3"><?= svg_icon('clock', 'w-12 h-12') ?></span>
        <p class="text-base font-semibold text-neutral-700">No matching entries</p>
        <p class="mt-1 text-sm text-neutral-500">Try adjusting your filters to see more results.</p>
      </div>
    <?php else: ?>
      <div class="divide-y divide-neutral-100">
        <?php foreach ($entries as $entry):
          $detailsRaw = json_decode($entry['details'], true) ?? [];
          $actionLabel = $actionLabels[$entry['action']] ?? str_replace('_', ' ', $entry['action']);
          $entityLabel = $entityTypeLabels[$entry['entity_type']] ?? $entry['entity_type'];
        ?>
          <div x-data="{ open: false }" class="transition">
            <!-- Row -->
            <div class="flex items-center gap-4 px-5 py-3.5 hover:bg-neutral-50 cursor-pointer" @click="open = !open" role="button" tabindex="0" @keydown.enter="open = !open" :aria-expanded="open">
              <!-- Timestamp -->
              <div class="w-28 flex-shrink-0 hidden sm:block">
                <p class="text-xs text-neutral-600"><?= date('M j, Y', strtotime($entry['created_at'])) ?></p>
                <p class="text-[10px] text-neutral-400"><?= date('g:i A', strtotime($entry['created_at'])) ?></p>
              </div>
              <div class="sm:hidden flex-shrink-0">
                <p class="text-xs text-neutral-600"><?= date('M j', strtotime($entry['created_at'])) ?></p>
              </div>
              <!-- Admin -->
              <p class="w-28 flex-shrink-0 text-sm font-semibold text-neutral-800 truncate hidden sm:block"><?= htmlspecialchars($entry['admin_user_name']) ?></p>
              <!-- Action badge -->
              <div class="flex-1 min-w-0">
                <span class="rounded-pill px-2 py-0.5 text-[11px] font-bold <?= actionBadgeClass($entry['action']) ?>"><?= htmlspecialchars($actionLabel) ?></span>
              </div>
              <!-- Entity reference -->
              <div class="w-48 flex-shrink-0 hidden md:block">
                <p class="text-sm text-neutral-700 truncate font-medium"><?= htmlspecialchars($entry['entity_label']) ?></p>
                <p class="text-[10px] text-neutral-400"><?= htmlspecialchars($entityLabel) ?> #<?= (int)$entry['entity_id'] ?></p>
              </div>
              <!-- Expand indicator -->
              <span class="flex-shrink-0 text-neutral-400 transition" :class="open ? 'rotate-180' : ''">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </span>
            </div>
            <!-- Expanded Detail -->
            <div x-show="open" x-cloak class="border-t border-neutral-100 bg-neutral-50 px-5 py-4">
              <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 max-w-2xl">
                <?php foreach ($detailsRaw as $key => $value): ?>
                  <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-neutral-500"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) ?></p>
                    <p class="mt-0.5 text-sm text-neutral-800"><?= htmlspecialchars(is_bool($value) ? ($value ? 'Yes' : 'No') : (is_string($value) ? $value : json_encode($value))) ?></p>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <div class="flex items-center justify-between border-t border-neutral-200 px-5 py-3">
        <p class="text-xs text-neutral-500">Page <?= $page ?> of <?= $totalPages ?></p>
        <div class="flex items-center gap-1">
          <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="rounded-pill px-3 py-1 text-xs font-bold text-neutral-600 hover:bg-neutral-100 transition bg-white border border-neutral-200">Previous</a>
          <?php endif; ?>
          <?php
          $startPage = max(1, $page - 2);
          $endPage = min($totalPages, $page + 2);
          for ($i = $startPage; $i <= $endPage; $i++):
          ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="rounded-pill px-3 py-1 text-xs font-bold transition <?= $i === $page ? 'bg-brand-500 text-white' : 'text-neutral-600 hover:bg-neutral-100 bg-white border border-neutral-200' ?>"><?= $i ?></a>
          <?php endfor; ?>
          <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="rounded-pill px-3 py-1 text-xs font-bold text-neutral-600 hover:bg-neutral-100 transition bg-white border border-neutral-200">Next</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../partials/admin-footer.php'; ?>
