<?php
$sheetId = $sheetId ?? 'bottom-sheet';
$triggerLabel = $triggerLabel ?? 'Open panel';
$title = $title ?? 'Panel';
$description = $description ?? '';
$closeLabel = $closeLabel ?? 'Close';
$footerActions = $footerActions ?? [];
?>
<div x-data="bottomSheet()" x-init="$watch('open', value => { if (value) { $nextTick(() => $refs.panel.focus()); } })" class="relative">
  <button type="button" @click="openSheet" class="btn btn-primary">
    <?= htmlspecialchars($triggerLabel) ?>
  </button>

  <div x-show="open" x-cloak class="sheet-backdrop" x-ref="backdrop" @click="close" @keydown.escape.window="close">
    <div class="sheet-backdrop-inner"></div>
    <div x-show="open" x-cloak
         x-transition:enter="sheet-enter"
         x-transition:enter-start="sheet-enter-start"
         x-transition:enter-end="sheet-enter-end"
         x-transition:leave="sheet-leave"
         x-transition:leave-start="sheet-leave-start"
         x-transition:leave-end="sheet-leave-end"
         class="sheet-panel"
         @click.stop
         role="dialog" aria-modal="true" aria-labelledby="<?= htmlspecialchars($sheetId) ?>-title" tabindex="-1" x-ref="panel" @keydown.tab.prevent="trapFocus($event)">
      <div class="sheet-header">
        <div>
          <h3 id="<?= htmlspecialchars($sheetId) ?>-title" class="text-h3" style="margin-bottom: 0;"><?= htmlspecialchars($title) ?></h3>
          <?php if ($description): ?>
            <p class="text-small" style="color: var(--neutral-500); margin-top: var(--space-2);"><?= htmlspecialchars($description) ?></p>
          <?php endif; ?>
        </div>
        <button type="button" @click="close" class="sheet-close">
          <span aria-hidden="true">&times;</span>
          <span class="sr-only"><?= htmlspecialchars($closeLabel) ?></span>
        </button>
      </div>

      <div style="margin-top: var(--space-5); max-height: 50vh; overflow-y: auto;">
        <?= $contentHtml ?? '' ?>
      </div>

      <?php if (!empty($footerActions)): ?>
        <div class="sheet-footer">
          <?php foreach ($footerActions as $action):
            $label = htmlspecialchars($action['label'] ?? '');
            $href = htmlspecialchars($action['href'] ?? '');
            $onClick = $action['onClick'] ?? '';
          ?>
            <?php if ($href): ?>
              <a href="<?= $href ?>" class="btn btn-primary btn-full"><?= $label ?></a>
            <?php else: ?>
              <button type="button" <?= $onClick ? 'onclick="'.htmlspecialchars($onClick).'"' : '' ?> class="btn btn-primary btn-full"><?= $label ?></button>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>