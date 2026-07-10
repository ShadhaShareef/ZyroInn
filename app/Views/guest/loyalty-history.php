<?php
$guest = $guest ?? [];
$loyalty = $loyalty ?? [];
$loyaltyTransactions = $loyaltyTransactions ?? [];
?>
<?php
$title = 'Loyalty History - ZyroInn';
$badge = 'Loyalty History';
include __DIR__ . '/../partials/guest-header.php';
?>

    <main class="main-content-narrow">

      <div class="loyalty-card">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-tiny" style="color:rgba(255,255,255,0.8);">Current Balance</p>
            <p class="text-h2" style="color:#fff;"><?= number_format($loyalty['account']['points_balance'] ?? 0) ?></p>
          </div>
          <div class="text-right">
            <p class="text-tiny" style="color:rgba(255,255,255,0.8);">Tier</p>
            <p class="text-h4" style="color:#fff; margin:0;"><?= htmlspecialchars(ucfirst($loyalty['tier'] ?? 'bronze')) ?></p>
          </div>
        </div>
      </div>

      <section class="card">
        <div class="p-6">
          <h3 class="section-subtitle mb-4">Transaction History</h3>

          <?php if (empty($loyaltyTransactions)): ?>
            <p class="text-small" style="color:var(--neutral-500); text-align:center; padding:var(--space-8);">No loyalty activity yet.</p>
          <?php else: ?>
            <div class="space-y-2">
              <?php foreach ($loyaltyTransactions as $txn):
                $isEarn = $txn['transaction_type'] === 'earn';
                $points = (int)$txn['points_change'];
                $sign = $isEarn ? '+' : '-';
                $color = $isEarn ? 'var(--success)' : 'var(--error)';
              ?>
                <div class="txn-row">
                  <div style="flex:1;">
                    <p class="txn-row-label"><?= htmlspecialchars($txn['description'] ?? 'Transaction') ?></p>
                    <p class="txn-row-meta">
                      <?= date('M j, Y g:i A', strtotime($txn['occurred_at'])) ?>
                      <?php if (!empty($txn['property_name'])): ?>• <?= htmlspecialchars($txn['property_name']) ?><?php endif; ?>
                      • <span class="uppercase"><?= htmlspecialchars($txn['transaction_type']) ?></span>
                    </p>
                  </div>
                  <span class="txn-row-pts" style="color:<?= $color ?>;"><?= $sign ?><?= number_format(abs($points)) ?> pts</span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>

    </main>

    <?php include __DIR__ . '/../partials/site-footer.php'; ?>
    <?php
    $items = $navItems ?? [];
    include __DIR__ . '/../partials/bottom-tab-bar.php';
    ?>
<?php include __DIR__ . '/../partials/guest-footer.php'; ?>