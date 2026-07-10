<?php
$title = "Support Tickets";
include __DIR__ . '/../partials/admin-header.php';
?>
<div class="space-y-6">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-900">Support Ticketing</h1>
      <p class="text-sm text-neutral-500 mt-1">Manage support tickets across all properties.</p>
    </div>
    <div x-data="{ createOpen: false }">
      <button @click="createOpen = true" class="rounded-2xl bg-brand-500 px-4 py-2 text-sm font-bold text-white hover:bg-brand-600 transition">+ New Ticket</button>
      <div x-show="createOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="createOpen = false">
        <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-xl mx-4">
          <h3 class="text-lg font-bold text-brand-900">Create Support Ticket</h3>
          <form method="POST" action="index.php" class="mt-4 space-y-3">
            <input type="hidden" name="action" value="create_ticket">
            <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
            <label class="block text-sm font-semibold text-neutral-700">
              <span>Property</span>
              <select name="property_id" required class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                <option value="">Select property...</option>
                <?php foreach ($properties as $prop): ?>
                  <option value="<?= $prop['id'] ?>"><?= htmlspecialchars($prop['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label class="block text-sm font-semibold text-neutral-700">
              <span>Subject</span>
              <input type="text" name="subject" required class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
            </label>
            <label class="block text-sm font-semibold text-neutral-700">
              <span>Description</span>
              <textarea name="description" required rows="4" class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500"></textarea>
            </label>
            <div class="grid grid-cols-2 gap-3">
              <label class="block text-sm font-semibold text-neutral-700">
                <span>Category</span>
                <select name="category" class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                  <option value="other">Other</option>
                  <option value="billing">Billing</option>
                  <option value="technical">Technical</option>
                  <option value="account">Account</option>
                  <option value="property">Property</option>
                </select>
              </label>
              <label class="block text-sm font-semibold text-neutral-700">
                <span>Priority</span>
                <select name="priority" class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                  <option value="normal">Normal</option>
                  <option value="low">Low</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </label>
            </div>
            <div class="flex gap-2">
              <button type="submit" class="rounded-2xl bg-brand-500 px-4 py-2 text-sm font-bold text-white hover:bg-brand-600 transition">Create Ticket</button>
              <button type="button" @click="createOpen = false" class="rounded-2xl border border-neutral-200 px-4 py-2 text-sm font-bold text-neutral-600 hover:bg-neutral-50 transition">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Stats bar -->
  <div class="flex flex-wrap gap-3">
    <span class="rounded-pill bg-rose-50 border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-700">Open: <?= (int)$stats['open'] ?></span>
    <span class="rounded-pill bg-blue-50 border border-blue-200 px-3 py-1.5 text-xs font-bold text-blue-700">In Progress: <?= (int)$stats['in_progress'] ?></span>
    <span class="rounded-pill bg-emerald-50 border border-emerald-200 px-3 py-1.5 text-xs font-bold text-emerald-700">Resolved: <?= (int)$stats['resolved'] ?></span>
    <span class="rounded-pill bg-neutral-100 border border-neutral-200 px-3 py-1.5 text-xs font-bold text-neutral-600">Total: <?= (int)$stats['total'] ?></span>
  </div>

  <!-- Filters -->
  <div class="rounded-3xl border border-neutral-200 bg-white p-4 shadow-sm">
    <form method="GET" action="index.php" class="flex flex-wrap items-end gap-3">
      <input type="hidden" name="route" value="support">
      <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">Status</label>
        <select name="status" class="rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
          <option value="">All Statuses</option>
          <option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Open</option>
          <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
          <option value="waiting_on_customer" <?= $statusFilter === 'waiting_on_customer' ? 'selected' : '' ?>>Waiting on Customer</option>
          <option value="resolved" <?= $statusFilter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
          <option value="closed" <?= $statusFilter === 'closed' ? 'selected' : '' ?>>Closed</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">Priority</label>
        <select name="priority" class="rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
          <option value="">All Priorities</option>
          <option value="urgent" <?= $priorityFilter === 'urgent' ? 'selected' : '' ?>>Urgent</option>
          <option value="high" <?= $priorityFilter === 'high' ? 'selected' : '' ?>>High</option>
          <option value="normal" <?= $priorityFilter === 'normal' ? 'selected' : '' ?>>Normal</option>
          <option value="low" <?= $priorityFilter === 'low' ? 'selected' : '' ?>>Low</option>
        </select>
      </div>
      <button type="submit" class="rounded-2xl bg-brand-500 px-4 py-2 text-sm font-bold text-white hover:bg-brand-600 transition">Filter</button>
      <a href="?route=support" class="rounded-2xl border border-neutral-200 px-4 py-2 text-sm font-bold text-neutral-600 hover:bg-neutral-50 transition">Clear</a>
    </form>
  </div>

  <!-- Tickets Table -->
  <div class="rounded-3xl border border-neutral-200 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-neutral-50 border-b border-neutral-200 text-left text-xs font-semibold uppercase tracking-wider text-neutral-500">
            <th class="py-3 px-4">ID</th>
            <th class="py-3 px-4">Subject</th>
            <th class="py-3 px-4">Property</th>
            <th class="py-3 px-4">Category</th>
            <th class="py-3 px-4">Priority</th>
            <th class="py-3 px-4">Assignee</th>
            <th class="py-3 px-4">Created</th>
            <th class="py-3 px-4">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
          <?php if (empty($tickets)): ?>
            <tr><td colspan="8" class="py-8 text-center text-sm text-neutral-500">No tickets found.</td></tr>
          <?php endif; ?>
          <?php foreach ($tickets as $t): ?>
            <tr class="hover:bg-neutral-50">
              <td class="py-3 px-4 font-bold text-neutral-800">#<?= $t['id'] ?></td>
              <td class="py-3 px-4">
                <a href="?route=support-detail&id=<?= $t['id'] ?>" class="font-semibold text-brand-700 hover:text-brand-800 transition"><?= htmlspecialchars($t['subject']) ?></a>
              </td>
              <td class="py-3 px-4 text-neutral-600"><?= htmlspecialchars($t['property_name'] ?? '—') ?></td>
              <td class="py-3 px-4 text-xs uppercase text-neutral-600"><?= htmlspecialchars($t['category']) ?></td>
              <td class="py-3 px-4">
                <span class="rounded-pill px-2 py-0.5 text-xs font-bold <?= match($t['priority']) { 'urgent' => 'bg-red-100 text-red-700', 'high' => 'bg-orange-100 text-orange-700', 'normal' => 'bg-blue-50 text-blue-700', default => 'bg-neutral-100 text-neutral-600' } ?>">
                  <?= htmlspecialchars(ucfirst($t['priority'])) ?>
                </span>
              </td>
              <td class="py-3 px-4 text-xs text-neutral-600">
                <?= $t['assignee_first'] ? htmlspecialchars($t['assignee_first'] . ' ' . $t['assignee_last']) : '—' ?>
              </td>
              <td class="py-3 px-4 text-xs text-neutral-500"><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
              <td class="py-3 px-4">
                <span class="rounded-pill px-2 py-0.5 text-xs font-bold <?= match($t['status']) { 'open' => 'bg-rose-50 text-rose-700', 'in_progress' => 'bg-blue-50 text-blue-700', 'waiting_on_customer' => 'bg-amber-50 text-amber-700', 'resolved' => 'bg-emerald-50 text-emerald-700', 'closed' => 'bg-neutral-100 text-neutral-600', default => 'bg-neutral-100 text-neutral-600' } ?>">
                  <?= htmlspecialchars(str_replace('_', ' ', $t['status'])) ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../partials/admin-footer.php'; ?>
