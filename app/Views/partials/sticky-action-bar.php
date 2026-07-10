<?php
$label = $label ?? 'Primary Action';
$href = $href ?? '';
$onClick = $onClick ?? '';
?>
<div class="sticky-action-bar">
  <div class="sticky-action-bar-inner">
    <?php if ($href): ?>
      <a href="<?= htmlspecialchars($href) ?>" class="btn btn-primary btn-full"><?= htmlspecialchars($label) ?></a>
    <?php else: ?>
      <button type="button" <?= $onClick ? 'onclick="'.htmlspecialchars($onClick).'"' : '' ?> class="btn btn-primary btn-full"><?= htmlspecialchars($label) ?></button>
    <?php endif; ?>
  </div>
</div>