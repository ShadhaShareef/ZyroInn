<?php
$title = "Ticket #" . $ticket['id'];
include __DIR__ . '/../partials/admin-header.php';
?>
<div class="max-w-4xl space-y-6">
  <a href="?route=support" class="text-sm font-semibold text-brand-600 hover:text-brand-700 transition">&larr; Back to Tickets</a>

  <!-- Ticket Header -->
  <div class="rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
      <div class="flex-1">
        <div class="flex items-center gap-3">
          <h1 class="text-xl font-bold text-brand-900"><?= htmlspecialchars($ticket['subject']) ?></h1>
          <span class="rounded-pill px-2 py-0.5 text-xs font-bold <?= match($ticket['status']) { 'open' => 'bg-rose-50 text-rose-700', 'in_progress' => 'bg-blue-50 text-blue-700', 'waiting_on_customer' => 'bg-amber-50 text-amber-700', 'resolved' => 'bg-emerald-50 text-emerald-700', 'closed' => 'bg-neutral-100 text-neutral-600', default => 'bg-neutral-100 text-neutral-600' } ?>">
            <?= htmlspecialchars(str_replace('_', ' ', $ticket['status'])) ?>
          </span>
        </div>
        <div class="mt-3 flex flex-wrap gap-4 text-sm text-neutral-600">
          <span><strong>Property:</strong> <?= htmlspecialchars($ticket['property_name'] ?? '—') ?></span>
          <span><strong>Category:</strong> <?= htmlspecialchars(ucfirst($ticket['category'])) ?></span>
          <span><strong>Priority:</strong> <span class="rounded-pill px-2 py-0.5 text-xs font-bold <?= match($ticket['priority']) { 'urgent' => 'bg-red-100 text-red-700', 'high' => 'bg-orange-100 text-orange-700', 'normal' => 'bg-blue-50 text-blue-700', default => 'bg-neutral-100 text-neutral-600' } ?>"><?= htmlspecialchars(ucfirst($ticket['priority'])) ?></span></span>
          <span><strong>Assignee:</strong> <?= $ticket['assignee_first'] ? htmlspecialchars($ticket['assignee_first'] . ' ' . $ticket['assignee_last']) : 'Unassigned' ?></span>
          <span><strong>Created:</strong> <?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?></span>
          <?php if ($ticket['resolved_at']): ?><span><strong>Resolved:</strong> <?= date('M j, Y g:i A', strtotime($ticket['resolved_at'])) ?></span><?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Description -->
    <div class="mt-4 rounded-2xl bg-neutral-50 p-4">
      <p class="text-sm text-neutral-700"><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>
    </div>
  </div>

  <!-- Replies -->
  <div class="space-y-4">
    <?php foreach ($replies as $reply): ?>
      <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm <?= $reply['is_internal'] ? 'border-amber-200 bg-amber-50/30' : '' ?>">
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-2">
            <span class="font-semibold text-sm text-neutral-800"><?= htmlspecialchars($reply['first_name'] ?? 'System') ?> <?= htmlspecialchars($reply['last_name'] ?? '') ?></span>
            <?php if ($reply['is_internal']): ?><span class="rounded-pill bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">Internal Note</span><?php endif; ?>
          </div>
          <span class="text-xs text-neutral-500"><?= date('M j, Y g:i A', strtotime($reply['created_at'])) ?></span>
        </div>
        <p class="text-sm text-neutral-700"><?= nl2br(htmlspecialchars($reply['message'])) ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Reply Form -->
  <div class="rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm">
    <h3 class="text-lg font-bold text-brand-900">Add Reply</h3>
    <form method="POST" action="index.php" class="mt-4 space-y-3">
      <input type="hidden" name="action" value="reply_ticket">
      <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
      <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">

      <label class="block text-sm font-semibold text-neutral-700">
        <span>Message</span>
        <textarea name="message" required rows="4" class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500" placeholder="Write your reply..."></textarea>
      </label>

      <div class="flex flex-wrap items-center gap-4">
        <label class="flex items-center gap-2 text-sm text-neutral-600">
          <input type="checkbox" name="is_internal" value="1" class="rounded border-neutral-300 text-brand-500 focus:ring-brand-500">
          Internal note (staff only)
        </label>

        <label class="flex items-center gap-2 text-sm text-neutral-600">
          <span>Update status:</span>
          <select name="new_status" class="rounded-2xl border border-neutral-200 px-3 py-1.5 text-sm outline-none focus:border-brand-500">
            <option value="">Keep current</option>
            <option value="in_progress">In Progress</option>
            <option value="waiting_on_customer">Waiting on Customer</option>
            <option value="resolved">Resolved</option>
            <option value="closed">Closed</option>
          </select>
        </label>

        <label class="flex items-center gap-2 text-sm text-neutral-600">
          <span>Assign to:</span>
          <select name="assigned_to" class="rounded-2xl border border-neutral-200 px-3 py-1.5 text-sm outline-none focus:border-brand-500">
            <option value="">Unassigned</option>
            <?php foreach ($adminUsers as $au): ?>
              <option value="<?= $au['id'] ?>" <?= $ticket['assigned_to'] == $au['id'] ? 'selected' : '' ?>><?= htmlspecialchars($au['first_name'] . ' ' . $au['last_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>

      <div class="flex gap-2">
        <button type="submit" class="rounded-2xl bg-brand-500 px-4 py-2 text-sm font-bold text-white hover:bg-brand-600 transition">Post Reply</button>
        <form method="POST" action="index.php" class="inline">
          <input type="hidden" name="action" value="update_ticket_status">
          <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
          <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
          <input type="hidden" name="status" value="resolved">
          <button type="submit" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-bold text-emerald-700 hover:bg-emerald-100 transition">Mark Resolved</button>
        </form>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../partials/admin-footer.php'; ?>
