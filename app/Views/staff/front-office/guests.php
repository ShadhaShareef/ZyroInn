<?php
/**
 * guests.php - Front Office Guests List View
 */
$title = "Guest Directory";
include __DIR__ . '/../../partials/staff-header.php';
?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
  <div>
    <h1 class="text-2xl font-bold text-brand-900 leading-tight">Guest Directory</h1>
    <p class="text-xs text-neutral-500 mt-1">Review guest profiles, loyalty tiers, stay histories, and custom preferences.</p>
  </div>
</div>

<!-- Search Bar -->
<div class="bg-white p-4 rounded-3xl border border-neutral-200 shadow-sm mb-8">
  <form method="GET" action="index.php" class="flex items-center gap-2 w-full md:max-w-md">
    <input type="hidden" name="route" value="guests">
    <div class="relative w-full">
      <input 
          type="text" 
          name="search" 
          placeholder="Search by name, email, or phone number..." 
          value="<?= htmlspecialchars($search ?? '') ?>" 
          class="w-full rounded-2xl border border-neutral-200 bg-neutral-50 pl-10 pr-4 py-3 text-xs text-neutral-900 focus:outline-none focus:border-brand-500 transition"
      >
      <span class="absolute left-3.5 top-3.5 text-neutral-400 text-sm">🔍</span>
    </div>
    <button type="submit" class="rounded-2xl bg-brand-500 hover:bg-brand-600 px-5 py-3 text-xs font-bold text-white shadow-sm transition">
      Search
    </button>
  </form>
</div>

<!-- Guests Directory Grid -->
<?php $guests = $guests ?? []; ?>
<?php if (empty($guests)): ?>
  <div class="text-center py-16 bg-white rounded-3xl border border-neutral-200 shadow-sm max-w-md mx-auto">
    <p class="text-3xl mb-4">👥</p>
    <p class="text-neutral-700 font-bold text-base mb-1">No Guests Found</p>
    <p class="text-neutral-500 text-xs px-6">No guest records matched your search query. Try typing another name or clearing the filter.</p>
  </div>
<?php else: ?>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($guests as $guest): 
      $preferences = !empty($guest['preferences']) ? json_decode($guest['preferences'], true) : [];
    ?>
      <div class="bg-white rounded-3xl p-6 shadow-sm border border-neutral-200 hover:border-brand-300 hover:shadow-md transition duration-300 flex flex-col justify-between min-h-[220px]">
        <div>
          <!-- Header -->
          <div class="flex items-start justify-between border-b border-neutral-100 pb-3">
            <div>
              <h3 class="text-lg font-bold text-brand-900 leading-tight">
                <?= htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']) ?>
              </h3>
              <p class="text-[10px] text-neutral-400 mt-1 uppercase tracking-wider font-bold">
                ID: #<?= $guest['id'] ?>
              </p>
            </div>
            
            <?php if (!empty($guest['loyalty_member_id'])): ?>
              <span class="rounded-pill bg-brand-50 px-2.5 py-0.5 text-[9px] font-bold text-brand-700 uppercase tracking-widest border border-brand-100">
                ⭐ Loyalty Member
              </span>
            <?php endif; ?>
          </div>

          <!-- Contact details -->
          <div class="mt-4 space-y-1.5 text-xs text-neutral-500 font-medium">
            <div class="flex items-center gap-1.5">
              <span>✉️</span>
              <span class="truncate"><?= htmlspecialchars($guest['email']) ?></span>
            </div>
            <div class="flex items-center gap-1.5">
              <span>📞</span>
              <span><?= htmlspecialchars($guest['phone'] ?: 'No phone number') ?></span>
            </div>
          </div>

          <!-- Preferences -->
          <?php if (!empty($preferences)): ?>
            <div class="mt-4 bg-brand-50/30 rounded-xl p-3 border border-brand-100/50 text-[11px] text-brand-800 leading-relaxed font-semibold">
              💭 Preferences: 
              <?php foreach ($preferences as $key => $val): ?>
                <span class="text-neutral-500 font-medium capitalize ml-1"><?= htmlspecialchars(str_replace('_', ' ', $key)) ?>:</span>
                <span class="text-brand-900"><?= htmlspecialchars($val) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="mt-6 pt-3 border-t border-neutral-100 flex justify-between items-center text-xs">
          <span class="text-neutral-400 font-medium">ID proof:</span>
          <?php if ($guest['id_proof_path']): ?>
            <a href="index.php?route=view-id&guest_id=<?= $guest['id'] ?>" target="_blank" class="font-bold text-brand-500 hover:text-brand-600">
              📁 View Uploaded ID
            </a>
          <?php else: ?>
            <span class="text-rose-500 font-bold">⚠️ Required at Check-in</span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../../partials/staff-footer.php'; ?>
