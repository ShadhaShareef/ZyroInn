<?php
$guest = $guest ?? [];
$guest = array_merge([
  'first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '',
  'preferences' => '{}', 'loyalty_member_id' => '',
], $guest);
$loyalty = $loyalty ?? [];
$loyaltyTransactions = $loyaltyTransactions ?? [];
?>
<?php
$title = 'My Profile - ZyroInn';
$badge = 'Guest Profile';
include __DIR__ . '/../partials/guest-header.php';
?>

    <main class="main-content-narrow">

      <?php
      $name = $guest['first_name'] . ' ' . $guest['last_name'];
      $email = $guest['email'];
      $phone = $guest['phone'];
      $status = 'Verified Member';
      include __DIR__ . '/../partials/card-guest-profile.php';
      ?>

      <section class="card">
        <div class="p-6">
          <h3 class="section-subtitle mb-4">Preferences & Info</h3>

          <?php $preferences = json_decode($guest['preferences'] ?? '{}', true); ?>
          <div class="summary-rows">
            <div class="summary-row">
              <span>Loyalty Member ID:</span>
              <span class="summary-row-value"><?= htmlspecialchars($guest['loyalty_member_id'] ?: 'N/A') ?></span>
            </div>
            <div class="summary-row">
              <span>Preferred Contact:</span>
              <span class="summary-row-value"><?= htmlspecialchars(ucfirst($preferences['preferred_contact'] ?? 'Email')) ?></span>
            </div>
            <div class="summary-row">
              <span>Preferred Bed Config:</span>
              <span class="summary-row-value"><?= htmlspecialchars(ucfirst($preferences['bed_type'] ?? 'No Preference')) ?></span>
            </div>
            <div class="summary-row">
              <span>Account Status:</span>
              <span class="status-badge confirmed">ACTIVE</span>
            </div>
          </div>
        </div>
      </section>

      <section class="card">
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="section-subtitle mb-0">Loyalty Rewards</h3>
            <span class="pill pill-brand"><?= htmlspecialchars(ucfirst($loyalty['tier'] ?? 'bronze')) ?></span>
          </div>

          <div class="loyalty-card">
            <div class="flex items-baseline justify-between gap-4">
              <div>
                <p class="text-tiny" style="color:rgba(255,255,255,0.8);">Points Balance</p>
                <p class="text-h2" style="color:#fff;"><?= number_format($loyalty['account']['points_balance'] ?? 0) ?></p>
                <p class="text-tiny" style="color:rgba(255,255,255,0.7);">
                  Worth $<?= number_format(($loyalty['account']['points_balance'] ?? 0) / 100, 2) ?> in stays
                </p>
              </div>
              <div class="text-right">
                <p class="text-tiny" style="color:rgba(255,255,255,0.8);">Next Tier</p>
                <p class="text-small" style="font-weight:700; color:#fff;"><?= $loyalty['points_to_next_tier'] ?? 0 ?> pts to go</p>
                <div style="width:8rem; height:0.5rem; background:rgba(255,255,255,0.2); border-radius:9999px; margin-top:0.5rem; overflow:hidden;">
                  <div class="h-full" style="background:#fff; border-radius:9999px; width:<?= $loyalty['progress_percent'] ?? 0 ?>%;"></div>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-5">
            <h4 class="section-subtitle mb-3"><?= htmlspecialchars(ucfirst($loyalty['tier_label'] ?? 'Bronze')) ?> Perks</h4>
            <div class="flex flex-wrap gap-2">
              <?php foreach (($loyalty['tier_perks'] ?? []) as $perk): ?>
                <span class="pill pill-brand">✓ <?= htmlspecialchars($perk) ?></span>
              <?php endforeach; ?>
            </div>
          </div>

          <hr class="divider">

          <div>
            <h4 class="section-subtitle mb-3">Recent Activity</h4>
            <?php if (empty($loyaltyTransactions)): ?>
              <div class="empty-state" style="padding:var(--space-6);">
                <p class="empty-state-text">No loyalty activity yet. Complete a stay to start earning points!</p>
              </div>
            <?php else: ?>
              <div class="space-y-2" style="max-height:16rem; overflow-y:auto;">
                <?php foreach ($loyaltyTransactions as $txn):
                  $isEarn = $txn['transaction_type'] === 'earn';
                  $points = (int)$txn['points_change'];
                  $sign = $isEarn ? '+' : '-';
                  $color = $isEarn ? 'var(--success)' : 'var(--error)';
                ?>
                  <div class="txn-row">
                    <div style="flex:1;">
                      <p class="txn-row-label"><?= htmlspecialchars($txn['description'] ?? 'Loyalty transaction') ?></p>
                      <p class="txn-row-meta">
                        <?= date('M j, Y', strtotime($txn['occurred_at'])) ?>
                        <?php if (!empty($txn['property_name'])): ?>• <?= htmlspecialchars($txn['property_name']) ?><?php endif; ?>
                      </p>
                    </div>
                    <span class="txn-row-pts" style="color:<?= $color ?>;"><?= $sign ?><?= number_format(abs($points)) ?> pts</span>
                  </div>
                <?php endforeach; ?>
              </div>
              <a href="index.php?route=loyalty-history" class="btn btn-ghost btn-sm mt-3">View full history →</a>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <section class="hidden md:block">
        <a href="index.php?route=logout" class="btn btn-secondary btn-full">Log Out</a>
      </section>

    </main>

    <?php include __DIR__ . '/../partials/site-footer.php'; ?>
    <?php
    $items = $navItems ?? [];
    include __DIR__ . '/../partials/bottom-tab-bar.php';
    ?>
    <?php
    $label = 'Log Out';
    $href = 'index.php?route=logout';
    include __DIR__ . '/../partials/sticky-action-bar.php';
    ?>
<?php include __DIR__ . '/../partials/guest-footer.php'; ?>