<?php
$title = $title ?? '';
$due = $due ?? '';
$priority = $priority ?? '';
$assignee = $assignee ?? '';
$details = $details ?? '';
?>
<article class="rounded-xl border border-neutral-200 bg-neutral-50 p-4 shadow-sm">
  <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
    <div>
      <p class="text-sm font-semibold uppercase tracking-[0.18em] text-neutral-500">Task</p>
      <h3 class="mt-2 text-lg font-semibold text-brand-900"><?= htmlspecialchars($title) ?></h3>
      <?php if ($assignee): ?><p class="mt-1 text-sm text-neutral-500">Assigned to <?= htmlspecialchars($assignee) ?></p><?php endif; ?>
    </div>
    <?php if ($priority): ?>
      <span class="rounded-pill bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700"><?= htmlspecialchars($priority) ?></span>
    <?php endif; ?>
  </div>

  <div class="mt-4 grid gap-2 sm:grid-cols-2 text-sm text-neutral-500">
    <?php if ($due): ?><div class="rounded-lg bg-neutral-100 px-3 py-2">Due <?= htmlspecialchars($due) ?></div><?php endif; ?>
    <?php if ($details): ?><div class="rounded-lg bg-neutral-100 px-3 py-2"><?= htmlspecialchars($details) ?></div><?php endif; ?>
  </div>
</article>
