<?php
$title = "Review Moderation";
include __DIR__ . '/../partials/admin-header.php';
?>
<div class="space-y-6">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-900">Review & Content Moderation</h1>
      <p class="text-sm text-neutral-500 mt-1">Approve, reject, or flag guest reviews across all properties.</p>
    </div>
    <div class="flex gap-1 flex-wrap">
      <a href="?route=moderation" class="rounded-pill px-3 py-1.5 text-xs font-bold <?= $statusFilter === '' ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200' ?>">Pending (<?= (int)$stats['pending'] ?>)</a>
      <a href="?route=moderation&status=approved" class="rounded-pill px-3 py-1.5 text-xs font-bold <?= $statusFilter === 'approved' ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200' ?>">Approved (<?= (int)$stats['approved'] ?>)</a>
      <a href="?route=moderation&status=rejected" class="rounded-pill px-3 py-1.5 text-xs font-bold <?= $statusFilter === 'rejected' ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200' ?>">Rejected (<?= (int)$stats['rejected'] ?>)</a>
      <a href="?route=moderation&status=flagged" class="rounded-pill px-3 py-1.5 text-xs font-bold <?= $statusFilter === 'flagged' ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200' ?>">Flagged (<?= (int)$stats['flagged'] ?>)</a>
    </div>
  </div>

  <div class="rounded-3xl border border-neutral-200 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-neutral-50 border-b border-neutral-200 text-left text-xs font-semibold uppercase tracking-wider text-neutral-500">
            <th class="py-3 px-4">Review</th>
            <th class="py-3 px-4">Guest</th>
            <th class="py-3 px-4">Property</th>
            <th class="py-3 px-4">Rating</th>
            <th class="py-3 px-4">Date</th>
            <th class="py-3 px-4">Status</th>
            <th class="py-3 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
          <?php if (empty($reviews)): ?>
            <tr><td colspan="7" class="py-8 text-center text-sm text-neutral-500">No reviews found for this filter.</td></tr>
          <?php endif; ?>
          <?php foreach ($reviews as $rev): ?>
            <tr class="hover:bg-neutral-50">
              <td class="py-3 px-4 max-w-xs">
                <?php if ($rev['title']): ?><p class="font-semibold text-neutral-800"><?= htmlspecialchars($rev['title']) ?></p><?php endif; ?>
                <?php if ($rev['body']): ?><p class="text-xs text-neutral-600 truncate"><?= htmlspecialchars($rev['body']) ?></p><?php endif; ?>
              </td>
              <td class="py-3 px-4">
                <p class="font-semibold text-neutral-800"><?= htmlspecialchars(($rev['first_name'] ?? '') . ' ' . ($rev['last_name'] ?? '')) ?></p>
                <p class="text-xs text-neutral-500"><?= htmlspecialchars($rev['email'] ?? '') ?></p>
              </td>
              <td class="py-3 px-4 text-neutral-600"><?= htmlspecialchars($rev['property_name'] ?? '') ?></td>
              <td class="py-3 px-4">
                <span class="text-lg"><?= str_repeat('⭐', (int)$rev['rating']) ?></span>
              </td>
              <td class="py-3 px-4 text-xs text-neutral-500"><?= date('M j, Y', strtotime($rev['review_date'])) ?></td>
              <td class="py-3 px-4">
                <span class="rounded-pill px-2 py-0.5 text-xs font-bold <?= match($rev['moderation_status']) { 'approved' => 'bg-emerald-50 text-emerald-700', 'rejected' => 'bg-rose-50 text-rose-700', 'flagged' => 'bg-red-50 text-red-700', default => 'bg-amber-50 text-amber-700' } ?>">
                  <?= htmlspecialchars(ucfirst($rev['moderation_status'])) ?>
                </span>
              </td>
              <td class="py-3 px-4 text-right">
                <div class="flex items-center justify-end gap-1">
                  <?php if ($rev['moderation_status'] === 'pending' || $rev['moderation_status'] === 'flagged'): ?>
                    <form method="POST" action="index.php" class="inline">
                      <input type="hidden" name="action" value="moderation_action">
                      <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
                      <input type="hidden" name="id" value="<?= $rev['id'] ?>">
                      <button type="submit" name="status" value="approved" class="rounded-2xl bg-emerald-500 px-2 py-1 text-xs font-bold text-white hover:bg-emerald-600 transition" title="Approve">✓</button>
                      <button type="submit" name="status" value="rejected" class="rounded-2xl bg-rose-500 px-2 py-1 text-xs font-bold text-white hover:bg-rose-600 transition" title="Reject">✕</button>
                      <button type="submit" name="status" value="flagged" class="rounded-2xl bg-red-500 px-2 py-1 text-xs font-bold text-white hover:bg-red-600 transition" title="Flag">⚑</button>
                    </form>
                  <?php endif; ?>
                  <form method="POST" action="index.php" class="inline" onsubmit="return confirm('Delete this review permanently?')">
                    <input type="hidden" name="action" value="delete_review">
                    <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
                    <input type="hidden" name="id" value="<?= $rev['id'] ?>">
                    <button type="submit" class="rounded-2xl bg-neutral-200 px-2 py-1 text-xs font-bold text-neutral-600 hover:bg-neutral-300 transition" title="Delete">🗑</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../partials/admin-footer.php'; ?>
