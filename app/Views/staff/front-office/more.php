<?php
/**
 * more.php - Front Office More Actions & Logs Dashboard
 */
$title = "More Actions";
include __DIR__ . '/../../partials/staff-header.php';
?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
  <div>
    <h1 class="text-2xl font-bold text-brand-900 leading-tight">Operations Logs & More</h1>
    <p class="text-xs text-neutral-500 mt-1">Review operations handover logs, file security incident reports, and access other staff functions.</p>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8" x-data="{ handoverOpen: false, incidentOpen: false }">
  
  <!-- Shift Handover Notes Card -->
  <div class="bg-white rounded-3xl p-6 shadow-sm border border-neutral-200 flex flex-col justify-between">
    <div>
      <div class="flex items-center justify-between border-b border-neutral-100 pb-3 mb-4">
        <h3 class="text-lg font-bold text-brand-900">🔄 Shift Handover Notes</h3>
        <button @click="handoverOpen = true" class="text-xs font-bold text-brand-500 hover:text-brand-600">➕ Log Handover</button>
      </div>

      <div class="space-y-4 max-h-96 overflow-y-auto pr-1">
        <?php $handoverNotes = $handoverNotes ?? []; ?>
        <?php if (empty($handoverNotes)): ?>
          <p class="text-xs text-neutral-400 italic">No handover notes logged for this shift cycle.</p>
        <?php else: ?>
          <?php foreach ($handoverNotes as $note): ?>
            <div class="p-4 rounded-2xl border border-neutral-200 bg-neutral-50/50 space-y-2">
              <div class="flex items-center justify-between text-xs font-bold">
                <span class="text-brand-900 capitalize"><?= htmlspecialchars($note['shift_type']) ?> Shift</span>
                <span class="text-neutral-400 font-semibold"><?= date('M d, Y', strtotime($note['shift_date'])) ?></span>
              </div>
              <p class="text-xs text-neutral-600 font-medium leading-relaxed">
                <?= nl2br(htmlspecialchars($note['note'])) ?>
              </p>
              <div class="text-[10px] text-neutral-400 font-medium text-right border-t border-neutral-100/50 pt-1.5">
                Logged by <?= htmlspecialchars($note['first_name'] . ' ' . $note['last_name']) ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Handover Log Modal -->
    <div x-show="handoverOpen" x-cloak class="fixed inset-0 z-50 flex items-end bg-neutral-900/40" @click.self="handoverOpen = false">
      <div x-show="handoverOpen" x-transition:enter="transition duration-300 ease-out transform" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
           class="mx-auto w-full max-w-xl rounded-t-3xl bg-white p-6 shadow-2xl focus:outline-none">
        <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
          <h3 class="text-lg font-bold text-brand-900">Add Shift Handover Note</h3>
          <button @click="handoverOpen = false" class="text-neutral-500 hover:text-neutral-700">&times;</button>
        </div>
        <form action="index.php?route=more" method="POST" class="mt-4 space-y-4">
          <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
          <input type="hidden" name="action" value="create_handover_note">

          <div>
            <label for="shift_type" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Shift Type</label>
            <select id="shift_type" name="shift_type" class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500 font-semibold text-neutral-700">
              <option value="morning">Morning Shift</option>
              <option value="afternoon">Afternoon Shift</option>
              <option value="night">Night Shift</option>
            </select>
          </div>

          <div>
            <label for="note" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Handover Instructions / Notes</label>
            <textarea id="note" name="note" rows="4" required placeholder="Describe any waitlisted guests, pending room issues, or cashier status..."
                      class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500"></textarea>
          </div>

          <div class="flex gap-3 pt-4 border-t border-neutral-100">
            <button type="button" @click="handoverOpen = false" class="w-1/2 rounded-2xl bg-neutral-100 py-3 text-sm font-bold text-neutral-500 transition">Cancel</button>
            <button type="submit" class="w-1/2 rounded-2xl bg-brand-500 py-3 text-sm font-bold text-white transition shadow-sm">Save Note</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Incident Reports Card -->
  <div class="bg-white rounded-3xl p-6 shadow-sm border border-neutral-200 flex flex-col justify-between">
    <div>
      <div class="flex items-center justify-between border-b border-neutral-100 pb-3 mb-4">
        <h3 class="text-lg font-bold text-brand-900">⚠️ Security Incident Logs</h3>
        <button @click="incidentOpen = true" class="text-xs font-bold text-brand-500 hover:text-brand-600">➕ File Report</button>
      </div>

      <div class="space-y-4 max-h-96 overflow-y-auto pr-1">
        <?php if (empty($incidentReports)): ?>
          <p class="text-xs text-neutral-400 italic">No security incidents logged. Facility is secure.</p>
        <?php else: ?>
          <?php foreach ($incidentReports as $report): ?>
            <div class="p-4 rounded-2xl border border-neutral-200 bg-neutral-50/50 space-y-2">
              <div class="flex items-center justify-between text-xs font-bold">
                <span class="text-rose-600"><?= htmlspecialchars($report['incident_type']) ?></span>
                <span class="rounded-pill bg-rose-50 border border-rose-100 px-2 py-0.5 text-[9px] text-rose-800 uppercase tracking-wider">
                  <?= htmlspecialchars($report['status']) ?>
                </span>
              </div>
              <p class="text-xs text-neutral-600 font-medium leading-relaxed">
                <?= nl2br(htmlspecialchars($report['description'])) ?>
              </p>
              <div class="text-[10px] text-neutral-400 font-medium text-right border-t border-neutral-100/50 pt-1.5 flex justify-between">
                <span>Date: <?= date('M d, H:i', strtotime($report['created_at'])) ?></span>
                <span>Filed by <?= htmlspecialchars($report['first_name'] . ' ' . $report['last_name']) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Security Incident Modal -->
    <div x-show="incidentOpen" x-cloak class="fixed inset-0 z-50 flex items-end bg-neutral-900/40" @click.self="incidentOpen = false">
      <div x-show="incidentOpen" x-transition:enter="transition duration-300 ease-out transform" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
           class="mx-auto w-full max-w-xl rounded-t-3xl bg-white p-6 shadow-2xl focus:outline-none">
        <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
          <h3 class="text-lg font-bold text-brand-900">File Security Incident Report</h3>
          <button @click="incidentOpen = false" class="text-neutral-500 hover:text-neutral-700">&times;</button>
        </div>
        <form action="index.php?route=more" method="POST" class="mt-4 space-y-4">
          <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
          <input type="hidden" name="action" value="create_incident_report">

          <div>
            <label for="incident_type" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Incident Type</label>
            <input type="text" id="incident_type" name="incident_type" required placeholder="e.g. Unauthorized entry, Noise complaint, Property Damage"
                   class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
          </div>

          <div>
            <label for="description" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Detailed Description</label>
            <textarea id="description" name="description" rows="4" required placeholder="Provide a detailed timeline of events, rooms or guests involved, and actions taken..."
                      class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500"></textarea>
          </div>

          <div class="flex gap-3 pt-4 border-t border-neutral-100">
            <button type="button" @click="incidentOpen = false" class="w-1/2 rounded-2xl bg-neutral-100 py-3 text-sm font-bold text-neutral-500 transition">Cancel</button>
            <button type="submit" class="w-1/2 rounded-2xl bg-brand-500 py-3 text-sm font-bold text-white transition shadow-sm">File Report</button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../../partials/staff-footer.php'; ?>
