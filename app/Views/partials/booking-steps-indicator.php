<?php
$currentStep = $currentStep ?? 1;
$steps = [
    1 => ['label' => 'Dates'],
    2 => ['label' => 'Guests'],
    3 => ['label' => 'Add-ons'],
    4 => ['label' => 'Payment']
];
?>
<div class="booking-steps">
  <div class="booking-steps-track">
    <div class="booking-steps-progress" style="width: <?= (($currentStep - 1) / 3) * 100 ?>%;"></div>

    <?php foreach ($steps as $num => $bs):
      $isCompleted = $num < $currentStep;
      $isActive = $num === $currentStep;

      $circleClass = 'booking-step-circle';
      $circleClass .= $isCompleted ? ' booking-step-completed' : ($isActive ? ' booking-step-active' : '');
      $labelClass = 'booking-step-label';
      $labelClass .= $isCompleted ? ' booking-step-label-completed' : ($isActive ? ' booking-step-label-active' : ' booking-step-label-pending');
    ?>
      <div class="booking-step">
        <div class="<?= $circleClass ?>">
          <?php if ($isCompleted): ?>
            ✓
          <?php else: ?>
            <span><?= $num ?></span>
          <?php endif; ?>
        </div>
        <span class="<?= $labelClass ?>"><?= htmlspecialchars($bs['label']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>