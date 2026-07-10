<?php
/**
 * calendar.php - Preventive Maintenance Calendar View
 */
$title = "Preventive Maintenance Calendar";
include __DIR__ . '/../../partials/staff-header.php';

// Calculate current month days
$year = isset($_GET['year']) ? (int)$_GET['year'] : 2026;
$month = isset($_GET['month']) ? (int)$_GET['month'] : 7; // default July

$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDayOfMonth);
$monthName = date('F', $firstDayOfMonth);
$dayOfWeek = date('w', $firstDayOfMonth); // 0 (Sun) to 6 (Sat)

// Group scheduled orders by day of month
$groupedOrders = [];
foreach ($scheduledOrders as $ord) {
    $time = strtotime($ord['scheduled_at']);
    $ordYear = (int)date('Y', $time);
    $ordMonth = (int)date('m', $time);
    if ($ordYear === $year && $ordMonth === $month) {
        $day = (int)date('j', $time);
        $groupedOrders[$day][] = $ord;
    }
}
?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8" x-data="{ schedulePMOpen: false }">
  <div>
    <h1 class="text-2xl font-bold text-brand-900 leading-tight">Preventive Maintenance Calendar</h1>
    <p class="text-xs text-neutral-500 mt-1">Plan and log scheduled inspections, filters, cleanups, and service events.</p>
  </div>
  
  <div class="flex gap-2">
    <button @click="schedulePMOpen = true" class="inline-flex items-center justify-center rounded-2xl bg-brand-500 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-brand-600 transition">
      📅 Schedule PM Event
    </button>
  </div>

  <!-- Schedule PM Event Modal -->
  <div x-show="schedulePMOpen" x-cloak class="fixed inset-0 z-50 flex items-end bg-neutral-900/40" @click.self="schedulePMOpen = false" @keydown.escape.window="schedulePMOpen = false">
    <div 
        x-show="schedulePMOpen"
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
          <h3 class="text-xl font-bold text-brand-900">Schedule PM Event</h3>
          <p class="text-xs text-neutral-500 mt-1">Create a scheduled preventive maintenance order.</p>
        </div>
        <button type="button" @click="schedulePMOpen = false" class="rounded-full bg-neutral-100 p-2 text-neutral-500 hover:bg-neutral-200 transition">
          &times;
        </button>
      </div>

      <form action="index.php?route=preventive-maintenance" method="POST" class="mt-6 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
        <input type="hidden" name="action" value="schedule_preventive_maintenance">

        <div>
          <label for="room_id" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Select Room / Location</label>
          <select id="room_id" name="room_id" required class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
            <option value="">-- Choose Room --</option>
            <?php foreach ($allRooms as $r): ?>
              <option value="<?= $r['id'] ?>">Room <?= htmlspecialchars($r['room_number']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="vendor_id" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Assign Vendor (Optional)</label>
            <select id="vendor_id" name="vendor_id" class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
              <option value="">-- Internal Staff / Unassigned --</option>
              <?php foreach ($vendors as $v): ?>
                <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="scheduled_at" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Scheduled Date & Time</label>
            <input type="datetime-local" id="scheduled_at" name="scheduled_at" required value="<?= date('Y-m-d\T09:00', strtotime('next Monday')) ?>"
                   class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500">
          </div>
        </div>

        <div>
          <label for="description" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">PM Job Description</label>
          <textarea id="description" name="description" rows="3" required placeholder="e.g. Quarterly deep clean of AC filters, inspect plumbing fixtures, change smoke detector batteries..."
                    class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500"></textarea>
        </div>

        <div class="flex gap-3 pt-4 border-t border-neutral-100">
          <button type="button" @click="schedulePMOpen = false" class="w-1/2 rounded-2xl bg-neutral-100 py-3 text-sm font-bold text-neutral-500 hover:bg-neutral-200 transition">
            Cancel
          </button>
          <button type="submit" class="w-1/2 rounded-2xl bg-brand-500 py-3 text-sm font-bold text-white hover:bg-brand-600 transition shadow-sm">
            Schedule Event
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
  
  <!-- Calendar Grid -->
  <div class="lg:col-span-2 bg-white rounded-3xl p-6 shadow-sm border border-neutral-200" x-data="{ selectedDay: <?= (int)date('j') ?> }">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-bold text-brand-900"><?= $monthName ?> <?= $year ?></h2>
      
      <div class="flex items-center gap-2">
        <a href="index.php?route=preventive-maintenance&year=<?= $month === 1 ? $year - 1 : $year ?>&month=<?= $month === 1 ? 12 : $month - 1 ?>" 
           class="h-8 w-8 rounded-xl bg-neutral-100 hover:bg-neutral-200 flex items-center justify-center text-xs font-bold text-neutral-600 transition">
          &larr;
        </a>
        <a href="index.php?route=preventive-maintenance&year=<?= $month === 12 ? $year + 1 : $year ?>&month=<?= $month === 12 ? 1 : $month + 1 ?>" 
           class="h-8 w-8 rounded-xl bg-neutral-100 hover:bg-neutral-200 flex items-center justify-center text-xs font-bold text-neutral-600 transition">
          &rarr;
        </a>
      </div>
    </div>

    <!-- Days headers -->
    <div class="grid grid-cols-7 gap-2 text-center text-xs font-bold uppercase tracking-wider text-neutral-400 mb-2">
      <div>Sun</div>
      <div>Mon</div>
      <div>Tue</div>
      <div>Wed</div>
      <div>Thu</div>
      <div>Fri</div>
      <div>Sat</div>
    </div>

    <!-- Calendar days grid -->
    <div class="grid grid-cols-7 gap-2">
      <?php 
      // Pad empty slots before 1st of month
      for ($i = 0; $i < $dayOfWeek; $i++): 
      ?>
        <div class="aspect-square bg-neutral-50/50 rounded-2xl border border-neutral-100/50"></div>
      <?php endfor; ?>

      <?php 
      // Render month days
      for ($day = 1; $day <= $daysInMonth; $day++): 
        $hasPM = !empty($groupedOrders[$day]);
        $isSelected = ($day === (int)date('j') && $month === (int)date('n') && $year === (int)date('Y'));
      ?>
        <button 
            @click="selectedDay = <?= $day ?>; $dispatch('day-selected', { day: <?= $day ?> })"
            :class="selectedDay === <?= $day ?> ? 'border-brand-500 ring-2 ring-brand-100 bg-brand-50/20' : 'border-neutral-200 hover:border-brand-300'"
            class="aspect-square bg-white rounded-2xl p-2 border flex flex-col justify-between items-start transition duration-200"
        >
          <span class="text-xs font-bold <?= $isSelected ? 'bg-brand-500 text-white rounded-full h-5 w-5 flex items-center justify-center -m-0.5' : 'text-neutral-500' ?>">
            <?= $day ?>
          </span>

          <?php if ($hasPM): ?>
            <span class="h-2 w-2 rounded-full bg-brand-500 self-end mt-2" title="<?= count($groupedOrders[$day]) ?> Event(s)"></span>
          <?php endif; ?>
        </button>
      <?php endfor; ?>
    </div>
  </div>

  <!-- Day Scheduled Events List Panel -->
  <div x-data="{ selectedDay: <?= (int)date('j') ?> }" @day-selected.window="selectedDay = $event.detail.day">
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-neutral-200">
      <h3 class="text-lg font-bold text-brand-900 border-b border-neutral-100 pb-3 mb-4">
        PM Events for <?= $monthName ?> <span x-text="selectedDay"></span>, <?= $year ?>
      </h3>

      <div class="space-y-4">
        <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
          <div x-show="selectedDay === <?= $day ?>" class="space-y-3">
            <?php if (empty($groupedOrders[$day])): ?>
              <p class="text-xs text-neutral-400 italic">No PM events scheduled for this date.</p>
            <?php else: ?>
              <?php foreach ($groupedOrders[$day] as $ord): ?>
                <div class="p-4 rounded-2xl border border-neutral-200 bg-neutral-50/50 space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-brand-900">Room <?= htmlspecialchars($ord['room_number']) ?></span>
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-widest">
                      <?= date('H:i', strtotime($ord['scheduled_at'])) ?>
                    </span>
                  </div>
                  <p class="text-xs text-neutral-600 font-medium leading-relaxed">
                    <?= htmlspecialchars($ord['issue_description']) ?>
                  </p>
                  <?php if ($ord['vendor_name']): ?>
                    <div class="text-[10px] text-neutral-500 font-semibold flex items-center gap-1">
                      <span>👤 Vendor:</span>
                      <span class="text-brand-600"><?= htmlspecialchars($ord['vendor_name']) ?></span>
                    </div>
                  <?php endif; ?>
                  
                  <div class="pt-2 flex justify-end">
                    <a href="index.php?route=maintenance-detail&id=<?= $ord['id'] ?>" class="text-[10px] font-bold text-brand-500 hover:text-brand-600">
                      View Work Order &rarr;
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        <?php endfor; ?>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../../partials/staff-footer.php'; ?>
