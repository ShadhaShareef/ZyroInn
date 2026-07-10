<?php
$title = $title ?? '';
$value = $value ?? '';
$change = $change ?? '';
$sparkline = $sparkline ?? '';
?>
<article class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
  <div>
    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-neutral-500"><?= htmlspecialchars($title) ?></p>
    <p class="mt-3 text-3xl font-semibold text-brand-900"><?= htmlspecialchars($value) ?></p>
    <?php if ($change): ?>
      <p class="mt-2 text-sm font-semibold text-brand-600"><?= htmlspecialchars($change) ?></p>
    <?php endif; ?>
  </div>
  <?php if ($sparkline): ?>
    <div class="mt-4 h-10 rounded-2xl bg-neutral-100 p-2">
      <div class="h-full rounded-2xl bg-brand-500" style="width: <?= htmlspecialchars($sparkline) ?>;"></div>
    </div>
  <?php endif; ?>
</article>
