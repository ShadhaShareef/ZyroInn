<?php
$title = "Property Onboarding";
include __DIR__ . '/../partials/admin-header.php';
?>
<div class="space-y-6">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-900">Property Onboarding Queue</h1>
      <p class="text-sm text-neutral-500 mt-1">Review and approve self-registration requests from new property owners.</p>
    </div>
    <div class="flex gap-2">
      <a href="?route=onboarding" class="rounded-pill px-3 py-1.5 text-xs font-bold <?= empty($statusFilter) ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200' ?>">All</a>
      <a href="?route=onboarding&status=pending" class="rounded-pill px-3 py-1.5 text-xs font-bold <?= $statusFilter === 'pending' ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200' ?>">Pending</a>
      <a href="?route=onboarding&status=verified" class="rounded-pill px-3 py-1.5 text-xs font-bold <?= $statusFilter === 'verified' ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200' ?>">Verified</a>
      <a href="?route=onboarding&status=approved" class="rounded-pill px-3 py-1.5 text-xs font-bold <?= $statusFilter === 'approved' ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200' ?>">Approved</a>
      <a href="?route=onboarding&status=rejected" class="rounded-pill px-3 py-1.5 text-xs font-bold <?= $statusFilter === 'rejected' ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200' ?>">Rejected</a>
    </div>
  </div>

  <div class="rounded-3xl border border-neutral-200 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-neutral-50 border-b border-neutral-200 text-left text-xs font-semibold uppercase tracking-wider text-neutral-500">
            <th class="py-3 px-4">Property</th>
            <th class="py-3 px-4">Contact</th>
            <th class="py-3 px-4">Location</th>
            <th class="py-3 px-4">Submitted</th>
            <th class="py-3 px-4">Status</th>
            <th class="py-3 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
          <?php if (empty($requests)): ?>
            <tr><td colspan="6" class="py-8 text-center text-sm text-neutral-500">No onboarding requests found.</td></tr>
          <?php endif; ?>
          <?php foreach ($requests as $req): ?>
            <tr class="hover:bg-neutral-50">
              <td class="py-3 px-4">
                <p class="font-semibold text-neutral-800"><?= htmlspecialchars($req['property_name']) ?></p>
                <?php if ($req['property_code']): ?><p class="text-xs text-neutral-500">Code: <?= htmlspecialchars($req['property_code']) ?></p><?php endif; ?>
              </td>
              <td class="py-3 px-4">
                <p class="text-neutral-800"><?= htmlspecialchars($req['contact_name']) ?></p>
                <p class="text-xs text-neutral-500"><?= htmlspecialchars($req['contact_email']) ?></p>
                <?php if ($req['contact_phone']): ?><p class="text-xs text-neutral-500"><?= htmlspecialchars($req['contact_phone']) ?></p><?php endif; ?>
              </td>
              <td class="py-3 px-4 text-neutral-600">
                <?= htmlspecialchars($req['city'] ?? '') ?><?= $req['state'] ? ', ' . htmlspecialchars($req['state']) : '' ?>
              </td>
              <td class="py-3 px-4 text-neutral-600 text-xs"><?= date('M j, Y', strtotime($req['created_at'])) ?></td>
              <td class="py-3 px-4">
                <?php
                $statusClasses = [
                  'pending' => 'bg-amber-50 text-amber-700',
                  'verified' => 'bg-blue-50 text-blue-700',
                  'approved' => 'bg-emerald-50 text-emerald-700',
                  'rejected' => 'bg-rose-50 text-rose-700',
                  'onboarding' => 'bg-purple-50 text-purple-700',
                ];
                $cls = $statusClasses[$req['status']] ?? 'bg-neutral-100 text-neutral-600';
                ?>
                <span class="rounded-pill px-2.5 py-1 text-xs font-bold <?= $cls ?>"><?= htmlspecialchars(ucfirst($req['status'])) ?></span>
              </td>
              <td class="py-3 px-4 text-right">
                <div x-data="{ open: false, notes: '' }">
                  <button @click="open = true" class="rounded-2xl bg-brand-500 px-3 py-1.5 text-xs font-bold text-white hover:bg-brand-600 transition">Review</button>
                  <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="open = false">
                    <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-xl mx-4">
                      <h3 class="text-lg font-bold text-brand-900">Review: <?= htmlspecialchars($req['property_name']) ?></h3>
                      <div class="mt-3 space-y-2 text-sm text-neutral-600">
                        <p><strong>Contact:</strong> <?= htmlspecialchars($req['contact_name']) ?> (<?= htmlspecialchars($req['contact_email']) ?>)</p>
                        <p><strong>Location:</strong> <?= htmlspecialchars($req['city'] ?? '') ?>, <?= htmlspecialchars($req['state'] ?? '') ?></p>
                        <?php if ($req['description']): ?><p><strong>Description:</strong> <?= htmlspecialchars($req['description']) ?></p><?php endif; ?>
                      </div>
                      <form method="POST" action="index.php" class="mt-4 space-y-3">
                        <input type="hidden" name="action" value="review_onboarding">
                        <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
                        <input type="hidden" name="id" value="<?= $req['id'] ?>">
                        <label class="block text-sm font-semibold text-neutral-700">
                          <span>Review Notes</span>
                          <textarea name="review_notes" x-model="notes" rows="3" class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500"></textarea>
                        </label>
                        <div class="flex flex-wrap gap-2">
                          <button type="submit" name="status" value="verified" class="rounded-2xl bg-blue-500 px-4 py-2 text-sm font-bold text-white hover:bg-blue-600 transition">Mark Verified</button>
                          <button type="submit" name="status" value="approved" class="rounded-2xl bg-emerald-500 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-600 transition">Approve</button>
                          <button type="submit" name="status" value="onboarding" class="rounded-2xl bg-purple-500 px-4 py-2 text-sm font-bold text-white hover:bg-purple-600 transition">Begin Onboarding</button>
                          <button type="submit" name="status" value="rejected" class="rounded-2xl bg-rose-500 px-4 py-2 text-sm font-bold text-white hover:bg-rose-600 transition">Reject</button>
                          <button type="button" @click="open = false" class="rounded-2xl border border-neutral-200 px-4 py-2 text-sm font-bold text-neutral-600 hover:bg-neutral-50 transition">Cancel</button>
                        </div>
                      </form>
                    </div>
                  </div>
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
