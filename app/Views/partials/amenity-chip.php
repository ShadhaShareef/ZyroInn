<?php
$amenity = $amenity ?? '';
$enabled = !empty($enabled);
$icon = $icon ?? '';
$label = $label ?? ucfirst(str_replace('_', ' ', $amenity));
?>
<button type="button" class="amenity-tag <?= $enabled ? 'amenity-tag-enabled' : '' ?>">
  <?php if ($icon): ?><?= $icon ?><?php endif; ?>
  <span><?= htmlspecialchars($label) ?></span>
</button>