<?php
$status = $status ?? '';
$type = $type ?? '';
$statusMap = [
  'room' => [
    'clean' => ['bg' => 'var(--color-room-status-clean)', 'text' => 'var(--color-neutral-50)'],
    'dirty' => ['bg' => 'var(--color-room-status-dirty)', 'text' => 'var(--color-neutral-50)'],
    'inspect' => ['bg' => 'var(--color-room-status-inspect)', 'text' => 'var(--color-neutral-50)'],
    'out_of_order' => ['bg' => 'var(--color-room-status-out_of_order)', 'text' => 'var(--color-neutral-50)'],
  ],
  'booking' => [
    'confirmed' => ['bg' => 'var(--color-brand-500)', 'text' => 'var(--color-neutral-50)'],
    'checked_in' => ['bg' => 'var(--color-info)', 'text' => 'var(--color-neutral-50)'],
    'checked_out' => ['bg' => 'var(--color-neutral-300)', 'text' => 'var(--color-neutral-500)'],
    'cancelled' => ['bg' => 'var(--color-error)', 'text' => 'var(--color-neutral-50)'],
  ],
  'task-priority' => [
    'urgent' => ['bg' => 'var(--color-task-priority-urgent)', 'text' => 'var(--color-neutral-50)'],
    'normal' => ['bg' => 'var(--color-task-priority-normal)', 'text' => 'var(--color-neutral-50)'],
  ],
];
$normalizedType = strtolower($type);
$normalizedStatus = strtolower(str_replace(' ', '_', $status));
$color = $statusMap[$normalizedType][$normalizedStatus] ?? ['bg' => '#EFF0F5', 'text' => '#6E738A'];
?>
<span class="inline-flex items-center rounded-pill px-3 py-1 text-xs font-semibold" style="background-color: <?= $color['bg'] ?>; color: <?= $color['text'] ?>;">
  <?= htmlspecialchars($status) ?: 'Unknown' ?>
</span>
