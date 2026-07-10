<?php
/**
 * detail.php - Work Order Detail & Management View
 */
$title = "Work Order #" . $order['id'];
include __DIR__ . '/../../partials/staff-header.php';
?>

<div class="mb-6">
  <a href="index.php?route=maintenance-queue" class="text-xs font-semibold text-brand-600 hover:text-brand-700 flex items-center gap-1">
    &larr; Back to Issue Queue
  </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
  <!-- Work Order Main Details Card -->
  <div class="lg:col-span-2 space-y-6">
    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-neutral-200">
      <div class="flex items-start justify-between flex-wrap gap-4 border-b border-neutral-100 pb-4">
        <div>
          <span class="text-xs font-bold text-neutral-400 uppercase tracking-wider">Work Order ID: #<?= $order['id'] ?></span>
          <h2 class="text-2xl font-bold text-brand-900 mt-1">Room <?= htmlspecialchars($order['room_number']) ?> (<?= htmlspecialchars($order['room_type']) ?>)</h2>
        </div>

        <div class="flex items-center gap-2">
          <!-- Priority badge -->
          <?php
          $status_val = $order['priority'];
          $type = 'task-priority';
          include __DIR__ . '/../../partials/status-badge.php';
          ?>
          <!-- Status Badge -->
          <span class="rounded-pill px-3 py-1 text-xs font-bold uppercase tracking-wider
            <?= $order['status'] === 'open' ? 'bg-rose-100 text-rose-800' : '' ?>
            <?= $order['status'] === 'in_progress' ? 'bg-amber-100 text-amber-800' : '' ?>
            <?= $order['status'] === 'resolved' ? 'bg-emerald-100 text-emerald-800' : '' ?>
            <?= $order['status'] === 'cancelled' ? 'bg-neutral-200 text-neutral-600' : '' ?>
          ">
            <?= htmlspecialchars($order['status']) ?>
          </span>
        </div>
      </div>

      <div class="mt-6">
        <h4 class="text-xs font-bold text-neutral-400 uppercase tracking-widest mb-2">Reported Issue</h4>
        <div class="bg-neutral-50 rounded-2xl p-4 border border-neutral-100 text-sm text-neutral-700 leading-relaxed font-medium">
          <?= nl2br(htmlspecialchars($order['issue_description'])) ?>
        </div>
      </div>

      <div class="mt-6 grid grid-cols-2 gap-4 text-xs text-neutral-500 border-t border-neutral-100 pt-4">
        <div>
          <span class="block text-neutral-400 font-bold uppercase tracking-wider">Reported By</span>
          <span class="font-semibold text-neutral-700"><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></span>
        </div>
        <div>
          <span class="block text-neutral-400 font-bold uppercase tracking-wider">Date Reported</span>
          <span class="font-semibold text-neutral-700"><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></span>
        </div>
      </div>
    </div>

    <!-- Resolution History / Notes -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-neutral-200">
      <h3 class="text-lg font-bold text-brand-900 mb-4">Resolution History & Progress Notes</h3>
      <?php if (empty($order['notes'])): ?>
        <p class="text-xs text-neutral-400 italic">No notes logged on this work order yet. Update the work order to add notes.</p>
      <?php else: ?>
        <div class="bg-neutral-50 rounded-2xl p-4 border border-neutral-100 text-xs text-neutral-700 font-medium whitespace-pre-wrap leading-relaxed">
          <?= htmlspecialchars($order['notes']) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($order['resolved_at'])): ?>
        <div class="mt-4 text-xs text-emerald-700 font-bold flex items-center gap-1 bg-emerald-50 border border-emerald-100 rounded-xl p-3">
          <span>✅</span>
          <span>Resolved on <?= date('M d, Y H:i', strtotime($order['resolved_at'])) ?></span>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Work Order Action Form Panel -->
  <div>
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-neutral-200 sticky top-24">
      <h3 class="text-lg font-bold text-brand-900 border-b border-neutral-100 pb-3 mb-4">Update Work Order</h3>
      
      <form action="index.php?route=maintenance-detail&id=<?= $order['id'] ?>" method="POST" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
        <input type="hidden" name="action" value="update_maintenance_order">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

        <!-- Status -->
        <div>
          <label for="status" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Work Order Status</label>
          <select id="status" name="status" class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500 font-semibold text-neutral-700">
            <option value="open" <?= $order['status'] === 'open' ? 'selected' : '' ?>>🔴 Open</option>
            <option value="in_progress" <?= $order['status'] === 'in_progress' ? 'selected' : '' ?>>🟡 In Progress</option>
            <option value="resolved" <?= $order['status'] === 'resolved' ? 'selected' : '' ?>>🟢 Resolved / Fixed</option>
            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>⚪ Cancelled</option>
          </select>
        </div>

        <!-- Vendor -->
        <div>
          <label for="vendor_id" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Assign Vendor</label>
          <select id="vendor_id" name="vendor_id" class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
            <option value="">-- Internal Staff / Unassigned --</option>
            <?php foreach ($vendors as $v): ?>
              <option value="<?= $v['id'] ?>" <?= $order['vendor_id'] == $v['id'] ? 'selected' : '' ?>><?= htmlspecialchars($v['name']) ?> (<?= htmlspecialchars($v['contact_name']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Scheduled At -->
        <div>
          <label for="scheduled_at" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Schedule Maintenance</label>
          <input type="datetime-local" id="scheduled_at" name="scheduled_at" 
                 value="<?= $order['scheduled_at'] ? date('Y-m-d\TH:i', strtotime($order['scheduled_at'])) : '' ?>"
                 class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
        </div>

        <!-- Progress Notes -->
        <div>
          <label for="notes" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Add Progress/Resolution Notes</label>
          <textarea id="notes" name="notes" rows="4" placeholder="e.g. Vendor called. Parts ordered. Will complete by Tuesday."
                    class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500"><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="w-full rounded-2xl bg-brand-500 hover:bg-brand-600 text-white font-bold py-3 text-sm transition shadow-sm">
          Save Changes
        </button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../partials/staff-footer.php'; ?>
