<?php
$route = $route ?? 'staff-scheduling';
$propertyName = $property['name'] ?? 'Property Console';
$propertyOptions = $propertyOptions ?? [];
$propertyId = $propertyId ?? 0;
$scheduleSummary = $scheduleSummary ?? ['on_duty' => 0, 'open_shifts' => 0, 'arrivals' => 0];
$staffUsers = $staffUsers ?? [];
$csrfToken = $csrfToken ?? '';
$dateToday = $dateToday ?? date('Y-m-d');
$title = 'Staff Scheduling - Owner Console';

include __DIR__ . '/../partials/owner-header.php';
?>
    <main class="mx-auto w-full max-w-7xl flex-grow px-4 py-8 pb-24 sm:px-6 lg:px-8 lg:pb-8">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-600">Staff Scheduling</p>
          <h1 class="mt-1 text-2xl font-semibold text-brand-900">Coordinate coverage for the week</h1>
          <p class="mt-2 text-sm text-neutral-500">Keep the team aligned around arrivals, departures, and service peaks.</p>
        </div>
        <div class="rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-semibold text-brand-700">
          <?= $scheduleSummary['on_duty'] ?> on duty today
        </div>
      </div>

      <div class="mt-8 grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">Open shifts</p>
          <p class="mt-2 text-2xl font-semibold text-brand-900"><?= (int)$scheduleSummary['open_shifts'] ?></p>
        </div>
        <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">On-duty today</p>
          <p class="mt-2 text-2xl font-semibold text-brand-900"><?= (int)$scheduleSummary['on_duty'] ?></p>
        </div>
        <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">Upcoming arrivals</p>
          <p class="mt-2 text-2xl font-semibold text-brand-900"><?= (int)$scheduleSummary['arrivals'] ?></p>
        </div>
      </div>

      <section class="mt-8 rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm" x-data="scheduleManager()">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
          <div>
            <h2 class="text-xl font-semibold text-brand-900">
              Schedule for <span x-text="selectedDateDisplay"></span>
            </h2>
            <p class="mt-1 text-sm text-neutral-500">Assign shifts, confirm coverage, and manage availability.</p>
          </div>
          <div class="flex items-center gap-3">
            <input type="date" x-model="selectedDate" @change="loadSchedules()" class="rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
            <button @click="openForm()" class="rounded-pill bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 transition">+ Add shift</button>
          </div>
        </div>

        <div class="mt-6 space-y-3">
          <template x-if="schedules.length === 0">
            <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-6 text-center">
              <p class="text-sm text-neutral-500">No shifts scheduled for this date.</p>
            </div>
          </template>
          <template x-for="s in schedules" :key="s.id">
            <article class="flex flex-col gap-3 rounded-2xl border border-neutral-200 bg-neutral-50 p-4 md:flex-row md:items-center md:justify-between">
              <div class="flex-1">
                <p class="text-sm font-semibold text-brand-900" x-text="s.first_name + ' ' + s.last_name"></p>
                <p class="mt-1 text-sm text-neutral-500" x-text="s.user_role"></p>
                <p class="text-xs text-neutral-400" x-show="s.notes" x-text="'Note: ' + s.notes"></p>
              </div>
              <div class="text-sm font-semibold text-neutral-700" x-text="s.start_time.substr(0,5) + ' – ' + s.end_time.substr(0,5)"></div>
              <span class="rounded-pill px-3 py-1 text-xs font-semibold"
                    :class="s.status === 'confirmed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                    x-text="s.status.charAt(0).toUpperCase() + s.status.slice(1)"></span>
              <div class="flex gap-1">
                <button @click="editShift(s)" class="rounded-2xl border border-neutral-200 bg-white px-3 py-1.5 text-xs font-semibold text-neutral-600 hover:bg-neutral-50 transition">Edit</button>
                <button @click="deleteShift(s.id)" class="rounded-2xl border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition">Delete</button>
              </div>
            </article>
          </template>
        </div>

        <!-- Add/Edit Modal -->
        <div x-show="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="showForm = false">
          <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-xl mx-4">
            <h3 class="text-lg font-bold text-brand-900" x-text="editId ? 'Edit Shift' : 'Add Shift'"></h3>
            <form @submit.prevent="saveShift()" class="mt-4 space-y-3">
              <label class="block text-sm font-semibold text-neutral-700">
                <span>Staff member</span>
                <select x-model="form.user_id" required class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                  <option value="">Select staff...</option>
                  <?php foreach ($staffUsers as $staff): ?>
                    <option value="<?= (int)$staff['id'] ?>"><?= htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name'] . ' (' . $staff['role'] . ')') ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <div class="grid grid-cols-2 gap-3">
                <label class="block text-sm font-semibold text-neutral-700">
                  <span>Start time</span>
                  <input type="time" x-model="form.start_time" required class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                </label>
                <label class="block text-sm font-semibold text-neutral-700">
                  <span>End time</span>
                  <input type="time" x-model="form.end_time" required class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                </label>
              </div>
              <label class="block text-sm font-semibold text-neutral-700">
                <span>Status</span>
                <select x-model="form.status" class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                  <option value="pending">Pending</option>
                  <option value="confirmed">Confirmed</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </label>
              <label class="block text-sm font-semibold text-neutral-700">
                <span>Notes (optional)</span>
                <textarea x-model="form.notes" rows="2" class="mt-1 w-full rounded-2xl border border-neutral-200 px-3 py-2 text-sm outline-none focus:border-brand-500"></textarea>
              </label>
              <div class="flex gap-2">
                <button type="submit" class="rounded-2xl bg-brand-500 px-4 py-2 text-sm font-bold text-white hover:bg-brand-600 transition">Save</button>
                <button type="button" @click="showForm = false" class="rounded-2xl border border-neutral-200 px-4 py-2 text-sm font-bold text-neutral-600 hover:bg-neutral-50 transition">Cancel</button>
              </div>
              <p x-show="formError" x-text="formError" class="text-sm font-semibold text-rose-600"></p>
            </form>
          </div>
        </div>
      </section>
    </main>
<?php include __DIR__ . '/../partials/owner-footer.php'; ?>

  <script>
    document.addEventListener('alpine:init', () => {
      Alpine.data('scheduleManager', () => ({
        selectedDate: '<?= $dateToday ?>',
        schedules: [],
        showForm: false,
        editId: null,
        form: { user_id: '', start_time: '09:00', end_time: '17:00', status: 'confirmed', notes: '' },
        formError: '',

        get selectedDateDisplay() {
          const d = new Date(this.selectedDate + 'T12:00:00');
          return d.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric', year: 'numeric' });
        },

        init() {
          this.loadSchedules();
        },

        loadSchedules() {
          fetch('<?= BASE_URL ?>/owner/index.php?route=api-schedules&date=' + this.selectedDate)
            .then(r => r.json())
            .then(data => { if (data.success) this.schedules = data.schedules; });
        },

        openForm(data) {
          this.editId = null;
          this.form = { user_id: '', start_time: '09:00', end_time: '17:00', status: 'confirmed', notes: '' };
          this.formError = '';
          this.showForm = true;
        },

        editShift(s) {
          this.editId = s.id;
          this.form = {
            user_id: s.user_id,
            start_time: s.start_time.substr(0,5),
            end_time: s.end_time.substr(0,5),
            status: s.status,
            notes: s.notes || '',
          };
          this.formError = '';
          this.showForm = true;
        },

        saveShift() {
          this.formError = '';
          if (!this.form.user_id) { this.formError = 'Select a staff member.'; return; }
          const csrf = '<?= $csrfToken ?>';
          fetch('<?= BASE_URL ?>/owner/index.php?route=schedule-upsert', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              csrf_token: csrf,
              id: this.editId,
              user_id: this.form.user_id,
              date: this.selectedDate,
              start_time: this.form.start_time,
              end_time: this.form.end_time,
              status: this.form.status,
              notes: this.form.notes || null,
            }),
          })
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              this.showForm = false;
              this.loadSchedules();
            } else {
              this.formError = data.error || 'Save failed';
            }
          })
          .catch(e => { this.formError = e.message; });
        },

        deleteShift(id) {
          if (!confirm('Delete this shift?')) return;
          const csrf = '<?= $csrfToken ?>';
          fetch('<?= BASE_URL ?>/owner/index.php?route=schedule-delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ csrf_token: csrf, id: id }),
          })
          .then(r => r.json())
          .then(data => { if (data.success) this.loadSchedules(); });
        },
      }));
    });
  </script>
