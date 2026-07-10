<?php
$name = $name ?? '';
$email = $email ?? '';
$phone = $phone ?? '';
$status = $status ?? '';
$stats = $stats ?? [];
$initials = strtoupper(
  (isset($name[0]) ? $name[0] : 'G') .
  (($pos = strpos($name, ' ')) !== false && isset($name[$pos + 1]) ? $name[$pos + 1] : 'T')
);
?>
<article class="profile-card" tabindex="0">
  <div class="profile-card-body">
    <div class="profile-card-row">
      <div class="profile-card-avatar"><?= $initials ?></div>
      <div class="profile-card-info">
        <h3 class="profile-card-name"><?= htmlspecialchars($name) ?></h3>
        <p class="profile-card-contact"><?= htmlspecialchars($email) ?></p>
        <?php if ($phone): ?>
          <p class="profile-card-contact"><?= htmlspecialchars($phone) ?></p>
        <?php endif; ?>
      </div>
      <?php if ($status): ?>
        <span class="status-badge confirmed"><?= htmlspecialchars($status) ?></span>
      <?php endif; ?>
    </div>
    <?php if (!empty($stats)): ?>
      <div class="profile-card-stats">
        <?php foreach ($stats as $label => $value): ?>
          <div class="profile-card-stat">
            <div class="profile-card-stat-value"><?= htmlspecialchars($value) ?></div>
            <div class="profile-card-stat-label"><?= htmlspecialchars($label) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</article>