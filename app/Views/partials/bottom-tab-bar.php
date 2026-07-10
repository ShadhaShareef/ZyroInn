<?php
$items = $items ?? [];
$items = array_slice($items, 0, 5);
?>
<nav aria-label="Bottom tab bar" class="tab-bar">
  <div class="tab-bar-inner">
    <?php foreach ($items as $item):
      $label = htmlspecialchars($item['label'] ?? '');
      $icon = $item['icon'] ?? '';
      $href = htmlspecialchars($item['href'] ?? '#');
      $active = !empty($item['active']);
      $ariaCurrent = $active ? ' aria-current="page"' : '';
    ?>
      <a href="<?= $href ?>" class="tab-bar-item <?= $active ? 'tab-bar-item-active' : 'tab-bar-item-inactive' ?>"<?= $ariaCurrent ?>>
        <span class="tab-bar-icon"><?= $icon ?></span>
        <span class="tab-bar-label"><?= $label ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</nav>