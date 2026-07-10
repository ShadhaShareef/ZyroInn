<?php
$drawerId = $drawerId ?? 'owner-console-drawer';
$title = $title ?? 'Owner Console';
$menuItems = $menuItems ?? [];
$actions = $actions ?? [];
$initialOpen = isset($initialOpen) ? (bool)$initialOpen : true;
?>
<div class="hidden md:block">
  <div x-data="{ open: <?= $initialOpen ? 'true' : 'false' ?> }" class="rounded-2xl border border-neutral-200 bg-neutral-100 p-4">
    <div class="flex items-center justify-between gap-3">
      <div>
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-neutral-500">Admin panel</p>
        <h2 class="mt-2 text-lg font-semibold text-brand-900"><?= htmlspecialchars($title) ?></h2>
      </div>
      <button type="button" @click="open = !open" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-neutral-300 bg-neutral-50 text-neutral-500 transition hover:border-brand-300 hover:text-brand-600">
        <span x-text="open ? '−' : '+'"></span>
      </button>
    </div>

    <div x-show="open" x-transition class="mt-4 space-y-3">
      <?php if (!empty($menuItems)): ?>
        <div class="space-y-2">
          <?php foreach ($menuItems as $item):
            $label = htmlspecialchars($item['label'] ?? '');
            $href = htmlspecialchars($item['href'] ?? '#');
            $active = !empty($item['active']);
            $itemClasses = $active
              ? 'bg-brand-50 text-brand-700'
              : 'text-neutral-700 hover:bg-neutral-100';
          ?>
            <a href="<?= $href ?>" class="flex items-center justify-between rounded-2xl border border-transparent px-4 py-3 <?= $itemClasses ?>">
              <span><?= $label ?></span>
              <?php if ($active): ?><span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Active</span><?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($actions)): ?>
        <div class="grid gap-2">
          <?php foreach ($actions as $action):
            $label = htmlspecialchars($action['label'] ?? '');
            $href = htmlspecialchars($action['href'] ?? '#');
          ?>
            <a href="<?= $href ?>" class="rounded-2xl bg-brand-500 px-4 py-3 text-center text-sm font-semibold text-neutral-50 hover:bg-brand-600"><?= $label ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
