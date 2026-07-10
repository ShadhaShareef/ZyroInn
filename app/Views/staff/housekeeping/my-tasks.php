<?php
/**
 * my-tasks.php - Housekeeping My Tasks List
 */
$title = "My Tasks";
include __DIR__ . '/../../partials/staff-header.php';
?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
  <div>
    <h1 class="text-2xl font-bold text-brand-900 leading-tight">My Tasks</h1>
    <p class="text-xs text-neutral-500 mt-1">Review and manage your daily housekeeping operations checklist.</p>
  </div>
</div>

<?php if (empty($myTasks)): ?>
  <div class="text-center py-16 bg-white rounded-3xl border border-neutral-200 shadow-sm max-w-md mx-auto">
    <p class="text-3xl mb-4">🧹</p>
    <p class="text-neutral-700 font-bold text-base mb-1">All Caught Up!</p>
    <p class="text-neutral-500 text-xs px-6">You have no tasks assigned to you right now. Take a break or check the status board.</p>
  </div>
<?php else: ?>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($myTasks as $task):
      $priority = $task['priority'];
      $status = $task['status'];
    ?>
      <div class="bg-white rounded-3xl p-6 shadow-sm border border-neutral-200 hover:border-brand-300 hover:shadow-md transition duration-300 flex flex-col justify-between min-h-[200px]">
        <div>
          <!-- Header info -->
          <div class="flex items-start justify-between gap-2">
            <div>
              <span class="text-xs font-bold text-neutral-400 uppercase tracking-wider">
                <?= !empty($task['room_number']) ? 'Room ' . htmlspecialchars($task['room_number']) : 'General Task' ?>
              </span>
              <h3 class="text-lg font-bold text-brand-900 mt-1">
                <?= htmlspecialchars($task['type']) ?>
              </h3>
            </div>
            
            <!-- Priority Badge -->
            <div>
              <?php
              $status_val = $priority;
              $type = 'task-priority';
              include __DIR__ . '/../../partials/status-badge.php';
              ?>
            </div>
          </div>

          <!-- Description -->
          <p class="text-xs text-neutral-500 mt-4 leading-relaxed bg-neutral-50 p-3 rounded-2xl border border-neutral-100">
            <?= htmlspecialchars($task['description'] ?: 'No additional description provided.') ?>
          </p>
        </div>

        <!-- Footer / Actions -->
        <div class="mt-6 pt-4 border-t border-neutral-100 flex flex-col gap-3">
          <div class="flex items-center justify-between text-xs text-neutral-400">
            <span>Due Date: <?= htmlspecialchars($task['due_date'] ?: 'Today') ?></span>
            <span class="font-bold uppercase tracking-wider px-2 py-0.5 rounded-pill text-[9px] 
              <?= $status === 'open' ? 'bg-neutral-100 text-neutral-600' : '' ?>
              <?= $status === 'in_progress' ? 'bg-amber-100 text-amber-800' : '' ?>
              <?= $status === 'completed' ? 'bg-emerald-100 text-emerald-800' : '' ?>
              <?= $status === 'cancelled' ? 'bg-rose-100 text-rose-800' : '' ?>
            ">
              <?= htmlspecialchars($status) ?>
            </span>
          </div>

          <?php if ($status !== 'completed' && $status !== 'cancelled'): ?>
            <form action="index.php?route=housekeeping-tasks" method="POST" class="mt-2">
              <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
              <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
              <input type="hidden" name="action" value="update_task_status">

              <?php if ($status === 'open'): ?>
                <button type="submit" name="status" value="in_progress" 
                        class="w-full rounded-2xl bg-brand-500 hover:bg-brand-600 text-white font-bold py-2.5 text-xs transition shadow-sm">
                  🚀 Start Work
                </button>
              <?php elseif ($status === 'in_progress'): ?>
                <button type="submit" name="status" value="completed" 
                        class="w-full rounded-2xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2.5 text-xs transition shadow-sm">
                  ✅ Mark Completed
                </button>
              <?php endif; ?>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../../partials/staff-footer.php'; ?>
