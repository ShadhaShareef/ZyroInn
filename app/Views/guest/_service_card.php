<?php use App\Services\AuthService; ?>
<div class="service-card" x-data="{ open: false }">
  <div class="service-card-body">
    <div class="service-card-row">
      <div style="flex:1; min-width:0; padding-right:var(--space-3);">
        <h4 class="service-card-name"><?= htmlspecialchars($svc['name']) ?></h4>
        <p class="service-card-desc"><?= htmlspecialchars($svc['description'] ?? '') ?></p>
        <div class="service-card-meta">
          <span class="service-card-price">$<?= number_format($svc['price'], 2) ?></span>
          <?php if (!empty($svc['duration_minutes'])): ?><span><?= (int)$svc['duration_minutes'] ?> min</span><?php endif; ?>
          <?php if (!empty($svc['max_capacity']) && (int)$svc['max_capacity'] > 1): ?><span>Up to <?= (int)$svc['max_capacity'] ?> guests</span><?php endif; ?>
        </div>
      </div>
      <button @click="open = !open" class="btn btn-primary btn-sm">Book</button>
    </div>

    <form x-show="open" x-cloak method="POST" action="index.php?route=services&booking_id=<?= (int)$booking['id'] ?>" style="margin-top:var(--space-4); padding-top:var(--space-4); border-top:1px solid var(--neutral-100);">
      <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
      <input type="hidden" name="service_id" value="<?= (int)$svc['id'] ?>">
      <?php $isFnb = $isRoomSvc; ?>
      <?php if (!$isFnb): ?>
      <div class="mb-3">
        <label class="input-label">Date & Time</label>
        <input type="datetime-local" name="scheduled_at" required class="input">
      </div>
      <?php endif; ?>
      <?php if (!empty($svc['max_capacity']) && (int)$svc['max_capacity'] > 1): ?>
        <div class="mb-3">
          <label class="input-label">Guests</label>
          <input type="number" name="quantity" min="1" max="<?= (int)$svc['max_capacity'] ?>" value="1" class="input">
        </div>
      <?php else: ?>
        <input type="hidden" name="quantity" value="1">
      <?php endif; ?>
      <div class="mb-3">
        <label class="input-label">Notes (optional)</label>
        <textarea name="notes" rows="2" placeholder="<?= $isFnb ? 'e.g. Allergies, preferences...' : 'e.g. Any special requests...' ?>" class="input"></textarea>
      </div>
      <button type="submit" class="btn btn-primary btn-full"><?= $isFnb ? 'Add to Order' : 'Confirm Booking' ?> — $<?= number_format($svc['price'], 2) ?></button>
    </form>
  </div>
</div>