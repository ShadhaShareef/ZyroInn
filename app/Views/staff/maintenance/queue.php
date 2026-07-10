<?php
/**
 * queue.php - Maintenance Issue Queue View
 */
$title = "Maintenance Issue Queue";
include __DIR__ . '/../../partials/staff-header.php';
?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8" x-data="{ logIssueOpen: false }">
  <div>
    <h1 class="text-2xl font-bold text-brand-900 leading-tight">Maintenance Issue Queue</h1>
    <p class="text-xs text-neutral-500 mt-1">Review active maintenance work orders, assign external vendors, and track repairs.</p>
  </div>
  
  <div>
    <button @click="logIssueOpen = true" class="inline-flex items-center justify-center rounded-2xl bg-brand-500 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-brand-600 transition">
      ➕ Log New Issue
    </button>
  </div>

  <!-- Log Maintenance Issue Modal -->
  <div x-show="logIssueOpen" x-cloak class="fixed inset-0 z-50 flex items-end bg-neutral-900/40" @click.self="logIssueOpen = false" @keydown.escape.window="logIssueOpen = false">
    <div 
        x-show="logIssueOpen"
        x-transition:enter="transition duration-300 ease-out transform"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition duration-200 ease-in transform"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="mx-auto w-full max-w-xl rounded-t-3xl bg-white p-6 shadow-2xl focus:outline-none"
    >
      <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
        <div>
          <h3 class="text-xl font-bold text-brand-900">Log Maintenance Issue</h3>
          <p class="text-xs text-neutral-500 mt-1">Report a room or facility repair requirement.</p>
        </div>
        <button type="button" @click="logIssueOpen = false" class="rounded-full bg-neutral-100 p-2 text-neutral-500 hover:bg-neutral-200 transition">
          &times;
        </button>
      </div>

      <form action="index.php?route=maintenance-queue" method="POST" class="mt-6 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
        <input type="hidden" name="action" value="create_maintenance_order">

        <div>
          <label for="room_id" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Select Room / Location</label>
          <select id="room_id" name="room_id" required class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
            <option value="">-- Choose Room --</option>
            <?php foreach ($allRooms as $r): ?>
              <option value="<?= $r['id'] ?>">Room <?= htmlspecialchars($r['room_number']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label for="priority" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Priority Level</label>
          <div class="grid grid-cols-2 gap-3">
            <label class="flex items-center justify-center p-3 rounded-2xl border text-sm font-semibold cursor-pointer hover:border-brand-300 transition">
              <input type="radio" name="priority" value="normal" checked class="mr-2 accent-brand-500">
              <span>Standard (Normal)</span>
            </label>
            <label class="flex items-center justify-center p-3 rounded-2xl border text-sm font-semibold cursor-pointer hover:border-brand-300 transition text-rose-600 border-rose-100">
              <input type="radio" name="priority" value="urgent" class="mr-2 accent-brand-500">
              <span class="font-bold">⚠️ Urgent (Out-of-Order)</span>
            </label>
          </div>
        </div>

        <div>
          <label for="issue_description" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Issue Description</label>
          <textarea id="issue_description" name="issue_description" rows="3" required placeholder="e.g. AC unit is blowing warm air, faucet in bathroom is leaking..."
                    class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500"></textarea>
        </div>

        <div class="flex gap-3 pt-4 border-t border-neutral-100">
          <button type="button" @click="logIssueOpen = false" class="w-1/2 rounded-2xl bg-neutral-100 py-3 text-sm font-bold text-neutral-500 hover:bg-neutral-200 transition">
            Cancel
          </button>
          <button type="submit" class="w-1/2 rounded-2xl bg-brand-500 py-3 text-sm font-bold text-white hover:bg-brand-600 transition shadow-sm">
            Save Issue
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Maintenance Order List -->
<?php if (empty($orders)): ?>
  <div class="text-center py-16 bg-white rounded-3xl border border-neutral-200 shadow-sm max-w-md mx-auto">
    <p class="text-3xl mb-4">🔧</p>
    <p class="text-neutral-700 font-bold text-base mb-1">Queue Empty</p>
    <p class="text-neutral-500 text-xs px-6">There are no logged maintenance issues. All facilities are working perfectly!</p>
  </div>
<?php else: ?>
  <div class="bg-white rounded-3xl border border-neutral-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead class="bg-neutral-50 text-xs font-bold uppercase tracking-wider text-neutral-400 border-b border-neutral-200">
          <tr>
            <th class="px-6 py-4">Room</th>
            <th class="px-6 py-4">Issue Description</th>
            <th class="px-6 py-4">Priority</th>
            <th class="px-6 py-4">Assigned Vendor</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4 text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
          <?php foreach ($orders as $order): 
            $priority = $order['priority'];
            $status = $order['status'];
          ?>
            <tr class="hover:bg-neutral-50/50 transition">
              <td class="px-6 py-4 font-bold text-brand-900">
                Room <?= htmlspecialchars($order['room_number']) ?>
              </td>
              <td class="px-6 py-4">
                <div class="text-neutral-700 font-medium max-w-xs sm:max-w-md truncate" title="<?= htmlspecialchars($order['issue_description']) ?>">
                  <?= htmlspecialchars($order['issue_description']) ?>
                </div>
                <div class="text-[10px] text-neutral-400 mt-0.5">
                  Logged by <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?> on <?= date('M d, H:i', strtotime($order['created_at'])) ?>
                </div>
              </td>
              <td class="px-6 py-4">
                <?php
                $status_val = $priority;
                $type = 'task-priority';
                include __DIR__ . '/../../partials/status-badge.php';
                ?>
              </td>
              <td class="px-6 py-4 text-xs font-semibold text-neutral-600">
                <?= $order['vendor_name'] ? htmlspecialchars($order['vendor_name']) : '⚡ Internal Staff' ?>
              </td>
              <td class="px-6 py-4">
                <span class="rounded-pill px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider
                  <?= $status === 'open' ? 'bg-rose-100 text-rose-800' : '' ?>
                  <?= $status === 'in_progress' ? 'bg-amber-100 text-amber-800' : '' ?>
                  <?= $status === 'resolved' ? 'bg-emerald-100 text-emerald-800' : '' ?>
                  <?= $status === 'cancelled' ? 'bg-neutral-200 text-neutral-600' : '' ?>
                ">
                  <?= htmlspecialchars($status) ?>
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <a href="index.php?route=maintenance-detail&id=<?= $order['id'] ?>" 
                   class="rounded-xl border border-neutral-200 hover:border-brand-500 px-3 py-1.5 font-semibold text-brand-600 hover:bg-brand-50 transition">
                  Manage &rarr;
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../../partials/staff-footer.php'; ?>
