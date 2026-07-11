<?php
$route = $route ?? 'dashboard';
$property = $property ?? ['name' => 'Property Console'];
$propertyId = $propertyId ?? 0;
$propertyOptions = $propertyOptions ?? [];
$kpis = $kpis ?? [];
$occupancyTrend = $occupancyTrend ?? [];
$rooms = $rooms ?? [];
$csrfToken = $csrfToken ?? '';
$dateToday = $dateToday ?? date('Y-m-d');

$title = $route === 'inventory' ? 'Rate & Inventory' : 'Owner Dashboard';
include __DIR__ . '/../partials/owner-header.php';
?>

<?php if ($route === 'inventory'): ?>
<div class="rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm" x-data="inventoryCalendar()">
  <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
    <div>
      <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-600">Rate & Inventory</p>
      <h2 class="mt-2 text-2xl font-semibold text-brand-900">Plan room pricing and availability</h2>
      <p class="mt-2 text-sm text-neutral-500">Set a date range, assign a rate, and quickly block or release rooms for maintenance or overbooking protection.</p>
    </div>
    <div class="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm font-semibold text-neutral-600">
      <span x-text="activePropertyLabel"></span>
    </div>
  </div>

  <div class="mt-8 grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
    <form class="rounded-2xl border border-neutral-200 bg-neutral-50 p-4" @submit.prevent="submitRange()">
      <div class="grid gap-4 sm:grid-cols-2">
        <label class="text-sm font-semibold text-neutral-700">
          <span class="mb-2 block">Start date</span>
          <input x-model="form.startDate" type="date" class="w-full rounded-2xl border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-700 outline-none">
        </label>
        <label class="text-sm font-semibold text-neutral-700">
          <span class="mb-2 block">End date</span>
          <input x-model="form.endDate" type="date" class="w-full rounded-2xl border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-700 outline-none">
        </label>
        <label class="text-sm font-semibold text-neutral-700">
          <span class="mb-2 block">Room</span>
          <select x-model="form.roomId" class="w-full rounded-2xl border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-700 outline-none">
            <template x-for="room in rooms" :key="room.id">
              <option :value="room.id" x-text="room.name"></option>
            </template>
          </select>
        </label>
        <label class="text-sm font-semibold text-neutral-700">
          <span class="mb-2 block">Rate ($/night)</span>
          <input x-model="form.rate" type="number" step="0.01" class="w-full rounded-2xl border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-700 outline-none">
        </label>
      </div>

      <label class="mt-4 block text-sm font-semibold text-neutral-700">
        <span class="mb-2 block">Action</span>
        <select x-model="form.status" class="w-full rounded-2xl border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-700 outline-none">
          <option value="available">Open for sale</option>
          <option value="blocked">Block room</option>
        </select>
      </label>

      <button type="submit" class="mt-4 inline-flex items-center justify-center rounded-2xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">
        Apply change
      </button>
      <div x-show="formStatus" x-text="formStatus" class="mt-2 text-sm font-semibold"
           :class="formStatus.startsWith('Error') ? 'text-rose-600' : 'text-emerald-600'"></div>
    </form>

    <div class="space-y-3">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold uppercase tracking-[0.15em] text-neutral-500">Rooms</h3>
        <button type="button" @click="openRoomForm()"
          class="inline-flex items-center gap-1 rounded-pill bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700 transition">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Add Room
        </button>
      </div>
      <template x-if="rooms.length === 0">
        <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-6 text-center">
          <p class="text-sm text-neutral-500">No rooms found for this property.</p>
        </div>
      </template>
      <template x-for="room in rooms" :key="room.id">
        <article class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
          <div class="flex items-center justify-between gap-3">
            <div>
              <p class="text-sm font-semibold text-brand-900" x-text="room.name"></p>
              <p class="mt-1 text-sm text-neutral-500">
                <span x-text="room.displayRate"></span> / night · <span x-text="room.displayStatus"></span>
              </p>
            </div>
            <div class="flex items-center gap-2">
              <button type="button" @click="editRoom(room.id)"
                class="flex h-8 w-8 items-center justify-center rounded-full text-neutral-400 hover:bg-neutral-100 hover:text-brand-600 transition" title="Edit room">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              </button>
              <button type="button" @click="confirmDeleteRoom(room.id, room.name)"
                class="flex h-8 w-8 items-center justify-center rounded-full text-neutral-400 hover:bg-rose-50 hover:text-rose-500 transition" title="Delete room">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
              <button type="button" class="rounded-pill border px-3 py-2 text-sm font-semibold transition"
                      :class="room.overrideStatus === 'blocked' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
                      @click="toggleRoomStatus(room.id)">
                <span x-text="room.overrideStatus === 'blocked' ? 'Unblock' : 'Block'"></span>
              </button>
            </div>
          </div>
        </article>
      </template>
    </div>

    <!-- Room Create/Edit Modal -->
    <div x-show="roomFormOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background: rgba(0,0,0,0.4);" @click.self="roomFormOpen = false">
      <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-5">
          <h3 class="text-lg font-semibold text-brand-900" x-text="roomEditId ? 'Edit Room' : 'Add Room'"></h3>
          <button type="button" @click="roomFormOpen = false" class="text-neutral-400 hover:text-neutral-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="block text-sm font-semibold text-neutral-700 mb-1">Room Number *</label>
            <input type="text" x-model="roomForm.number" placeholder="e.g. 101" maxlength="50"
              class="w-full rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-sm font-semibold text-neutral-700 mb-1">Room Type *</label>
            <input type="text" x-model="roomForm.type" list="room-type-list" placeholder="e.g. Deluxe"
              class="w-full rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-brand-300">
            <datalist id="room-type-list">
              <option value="Standard"><option value="Deluxe"><option value="Suite"><option value="Superior">
              <option value="Executive"><option value="Family"><option value="Dormitory"><option value="Villa">
              <option value="Cottage"><option value="Penthouse"><option value="Studio"><option value="Presidential">
            </datalist>
          </div>
          <div>
            <label class="block text-sm font-semibold text-neutral-700 mb-1">Base Rate ($/night) *</label>
            <input type="number" step="0.01" min="0" x-model="roomForm.rate"
              class="w-full rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-sm font-semibold text-neutral-700 mb-1">Status</label>
            <select x-model="roomForm.status"
              class="w-full rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-brand-300">
              <option value="available">Available</option>
              <option value="occupied">Occupied</option>
              <option value="reserved">Reserved</option>
              <option value="maintenance">Maintenance</option>
              <option value="out_of_service">Out of Service</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold text-neutral-700 mb-1">Max Guests</label>
            <input type="number" min="1" max="50" x-model="roomForm.occupancy"
              class="w-full rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-sm font-semibold text-neutral-700 mb-1">Beds</label>
            <input type="number" min="1" max="20" x-model="roomForm.beds"
              class="w-full rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div class="flex items-center gap-3">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" x-model="roomForm.ac" class="rounded border-neutral-300 text-brand-600 focus:ring-brand-300">
              <span class="text-sm font-semibold text-neutral-700">Air Conditioning</span>
            </label>
          </div>
        </div>
        <div class="mt-4">
          <label class="block text-sm font-semibold text-neutral-700 mb-1">Description</label>
          <textarea x-model="roomForm.description" rows="2" placeholder="Optional notes about this room"
            class="w-full rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-brand-300 resize-none"></textarea>
        </div>
        <div class="flex items-center justify-end gap-3 mt-6">
          <button type="button" @click="roomFormOpen = false"
            class="rounded-pill border border-neutral-200 px-5 py-2.5 text-sm font-semibold text-neutral-600 hover:bg-neutral-50 transition">Cancel</button>
          <button type="button" @click="saveRoom()" :disabled="!roomForm.number || !roomForm.type || !roomForm.rate"
            :class="!roomForm.number || !roomForm.type || !roomForm.rate ? 'opacity-50 cursor-not-allowed' : 'hover:bg-brand-700'"
            class="rounded-pill bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition">
            <span x-text="roomEditId ? 'Save Changes' : 'Add Room'"></span>
          </button>
        </div>
        <p x-show="roomFormError" x-text="roomFormError" class="mt-3 text-sm text-rose-600 font-semibold text-center"></p>
      </div>
    </div>

    <!-- Delete Room Confirmation -->
    <div x-show="deleteRoomId" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background: rgba(0,0,0,0.4);" @click.self="deleteRoomId = null">
      <div class="w-full max-w-sm rounded-3xl bg-white p-6 shadow-2xl text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-rose-500 mb-4">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
        </div>
        <h3 class="text-lg font-semibold text-brand-900">Delete Room?</h3>
        <p class="mt-2 text-sm text-neutral-500">
          Are you sure you want to delete <strong x-text="deleteRoomLabel" class="text-neutral-700"></strong>?<br>
          This cannot be undone.
        </p>
        <div class="flex items-center justify-center gap-3 mt-6">
          <button type="button" @click="deleteRoomId = null"
            class="rounded-pill border border-neutral-200 px-5 py-2.5 text-sm font-semibold text-neutral-600 hover:bg-neutral-50 transition">Cancel</button>
          <button type="button" @click="executeDeleteRoom()"
            class="rounded-pill bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 transition">Delete</button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php else: ?>
<div class="space-y-6">
  <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
    <div>
      <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-600">Dashboard</p>
      <h2 class="mt-1 text-2xl font-semibold text-brand-900">Performance snapshot for <?= htmlspecialchars($property['name'] ?? 'your property') ?></h2>
      <p class="mt-2 text-sm text-neutral-500">Track occupancy, revenue momentum, and the most important service signals in one place.</p>
    </div>
        <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
          <span class="w-3 h-3 text-emerald-500"><?= svg_icon('bullet') ?></span>
          <span>Live booked occupancy · <?= htmlspecialchars($kpis['occupancy'] ?? 'N/A') ?>%</span>
        </div>
  </div>

  <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <?php
    $kpiCards = [
        ['title' => 'Booked Occupancy', 'value' => ($kpis['occupancy'] ?? 0) . '%', 'change' => ($kpis['occupied_rooms'] ?? 0) . ' of ' . ($kpis['total_rooms'] ?? 0) . ' rooms booked'],
        ['title' => 'Revenue', 'value' => '$' . number_format($kpis['revenue'] ?? 0, 0), 'change' => 'From completed bookings'],
        ['title' => 'ADR', 'value' => '$' . number_format($kpis['adr'] ?? 0, 2), 'change' => 'Avg per booking'],
        ['title' => 'Bookings', 'value' => $kpis['bookings'] ?? 0, 'change' => ($kpis['due_today'] ?? 0) . ' due today'],
    ];
    foreach ($kpiCards as $card):
        $title = $card['title'];
        $value = $card['value'];
        $change = $card['change'];
        include __DIR__ . '/../partials/kpi-tile.php';
    endforeach;
    ?>
  </div>

  <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
    <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.18em] text-neutral-500">Booked occupancy trend</p>
          <h3 class="mt-2 text-xl font-semibold text-brand-900">Last 7 days</h3>
        </div>
        <span class="rounded-pill bg-brand-50 px-3 py-1 text-sm font-semibold text-brand-700">
          <?= htmlspecialchars($kpis['occupancy'] ?? 0) ?>% avg
        </span>
      </div>
      <div class="mt-6 h-32 rounded-2xl border border-dashed border-neutral-200 bg-white p-4">
        <div class="flex h-full items-end gap-2">
          <?php if (!empty($occupancyTrend)): ?>
            <?php foreach ($occupancyTrend as $day):
              $pct = ($day['total_rooms'] ?? 1) > 0 ? round(($day['occupied_cnt'] / $day['total_rooms']) * 100) : 0;
            ?>
              <div class="flex-1 flex flex-col items-center justify-end gap-1">
                <?php if ($pct >= 5): ?>
                  <span class="text-[10px] font-semibold text-neutral-500"><?= $pct ?>%</span>
                <?php endif; ?>
                <div class="w-full rounded-t-2xl <?= $pct > 0 ? 'bg-brand-500' : 'bg-neutral-200' ?>" style="height: <?= max($pct, 4) ?>%"></div>
                <span class="text-[9px] text-neutral-400"><?= date('D', strtotime($day['date'])) ?></span>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="flex w-full h-full items-center justify-center">
              <p class="text-sm text-neutral-400">No occupancy data available yet.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-5 shadow-sm">
      <p class="text-sm font-semibold uppercase tracking-[0.18em] text-neutral-500">Alerts</p>
      <div class="mt-4 space-y-3">
        <?php if (!empty($alerts)): ?>
          <?php $shown = 0; foreach ($alerts as $a): if ($shown >= 2) break; $shown++; ?>
            <div class="rounded-2xl border p-3 <?= $a['priority'] === 'High' ? 'border-rose-200 bg-rose-50' : 'border-amber-200 bg-amber-50' ?>">
              <p class="text-sm font-semibold <?= $a['priority'] === 'High' ? 'text-rose-800' : 'text-amber-800' ?>"><?= htmlspecialchars($a['title']) ?></p>
              <p class="mt-1 text-sm <?= $a['priority'] === 'High' ? 'text-rose-700' : 'text-amber-700' ?>"><?= htmlspecialchars($a['detail']) ?></p>
            </div>
          <?php endforeach; ?>
          <?php if (count($alerts) > 2): ?>
            <a href="<?= BASE_URL ?>/owner/index.php?route=alerts" class="block text-center text-sm font-semibold text-brand-600 hover:text-brand-700">
              + <?= count($alerts) - 2 ?> more alerts
            </a>
          <?php endif; ?>
        <?php else: ?>
          <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-3">
            <p class="text-sm font-semibold text-emerald-800">All clear</p>
            <p class="mt-1 text-sm text-emerald-700">No active alerts for this property.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../partials/owner-footer.php'; ?>

<script>
  document.addEventListener('alpine:init', () => {
    Alpine.data('inventoryCalendar', () => ({
      activeProperty: <?= (int)($propertyId ?? 0) ?>,
      formStatus: '',
      rooms: <?= json_encode(array_map(function($r) {
          $rate = $r['override_rate'] ?? $r['base_rate'];
          $ovStatus = $r['override_status'] ?? $r['room_status'];
          return [
              'id'             => (int)$r['id'],
              'name'           => $r['room_number'] . ' - ' . $r['room_type'],
              'baseRate'       => (float)$r['base_rate'],
              'overrideRate'   => $r['override_rate'] !== null ? (float)$r['override_rate'] : null,
              'overrideStatus' => $r['override_status'],
              'roomStatus'     => $r['room_status'],
              'displayRate'    => $r['override_rate'] !== null ? '$' . number_format((float)$r['override_rate'], 2) : 'Base $' . number_format((float)$r['base_rate'], 2),
              'displayStatus'  => $r['override_status'] === 'blocked' ? 'Blocked' : ucfirst($r['room_status']),
          ];
      }, $rooms)) ?>,
      form: {
        startDate: '<?= $dateToday ?>',
        endDate: '<?= date('Y-m-d', strtotime('+3 days')) ?>',
        roomId: <?= !empty($rooms) ? (int)$rooms[0]['id'] : 0 ?>,
        rate: <?= !empty($rooms) ? (float)($rooms[0]['override_rate'] ?? $rooms[0]['base_rate']) : 0 ?>,
        status: 'available',
      },
      get activePropertyLabel() {
        const match = <?= json_encode($propertyOptions) ?>.find((option) => Number(option.id) === Number(this.activeProperty));
        return match ? match.name : 'Property';
      },
      submitRange() {
        this.formStatus = '';
        const csrf = '<?= $csrfToken ?>';
        fetch('<?= BASE_URL ?>/owner/index.php?route=apply-rates', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            csrf_token: csrf,
            room_id: this.form.roomId,
            start_date: this.form.startDate,
            end_date: this.form.endDate,
            rate: this.form.status === 'blocked' ? '' : this.form.rate,
            status: this.form.status,
          }),
        })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            this.formStatus = 'Rates applied successfully!';
            this.refreshRooms();
          } else {
            this.formStatus = 'Error: ' + (data.error || 'Unknown');
          }
        })
        .catch(e => { this.formStatus = 'Error: ' + e.message; });
      },
      toggleRoomStatus(roomId) {
        const csrf = '<?= $csrfToken ?>';
        const targetRoom = this.rooms.find(r => r.id === roomId);
        if (!targetRoom) return;
        const newStatus = targetRoom.overrideStatus === 'blocked' ? 'available' : 'blocked';
        fetch('<?= BASE_URL ?>/owner/index.php?route=toggle-room-status', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            csrf_token: csrf,
            room_id: roomId,
            date: '<?= $dateToday ?>',
            status: newStatus,
          }),
        })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            this.refreshRooms();
          }
        });
      },
      refreshRooms() {
        fetch('<?= BASE_URL ?>/owner/index.php?route=api-rooms&date=<?= $dateToday ?>')
          .then(r => r.json())
          .then(data => {
            if (data.success && data.rooms) {
              this.rooms = data.rooms.map(r => ({
                id: r.id,
                name: r.room_number + ' - ' + r.room_type,
                baseRate: r.base_rate,
                overrideRate: r.override_rate,
                overrideStatus: r.override_status,
                roomStatus: r.room_status,
                displayRate: r.override_rate ? '$' + parseFloat(r.override_rate).toFixed(2) : 'Base $' + parseFloat(r.base_rate).toFixed(2),
                displayStatus: r.override_status === 'blocked' ? 'Blocked' : r.room_status.charAt(0).toUpperCase() + r.room_status.slice(1),
              }));
            }
          });
      },

      // Room CRUD
      roomFormOpen: false,
      roomEditId: null,
      roomForm: { number: '', type: '', rate: '', status: 'available', occupancy: 2, beds: 1, ac: true, description: '' },
      roomFormError: '',

      openRoomForm() {
        this.roomEditId = null;
        this.roomForm = { number: '', type: '', rate: '', status: 'available', occupancy: 2, beds: 1, ac: true, description: '' };
        this.roomFormError = '';
        this.roomFormOpen = true;
      },

      editRoom(id) {
        const r = this.rooms.find(x => x.id === id);
        if (!r) return;
        const parts = r.name.split(' - ');
        this.roomEditId = id;
        this.roomForm = {
          number: parts[0] || '',
          type: parts[1] || '',
          rate: r.overrideRate || r.baseRate || '',
          status: r.roomStatus || 'available',
          occupancy: 2, beds: 1, ac: true, description: ''
        };
        this.roomFormError = '';
        this.roomFormOpen = true;
      },

      saveRoom() {
        if (!this.roomForm.number || !this.roomForm.type || !this.roomForm.rate) return;
        const self = this;
        const csrf = '<?= $csrfToken ?>';
        const isEdit = !!this.roomEditId;
        const url = '<?= BASE_URL ?>/owner/index.php?route=' + (isEdit ? 'update-room' : 'create-room');
        const body = {
          csrf_token: csrf,
          room_number: this.roomForm.number,
          room_type: this.roomForm.type,
          base_rate: this.roomForm.rate,
          status: this.roomForm.status,
          occupancy: this.roomForm.occupancy,
          bed_count: this.roomForm.beds,
          ac: this.roomForm.ac ? 1 : 0,
          description: this.roomForm.description,
        };
        if (isEdit) body.id = this.roomEditId;
        fetch(url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(body),
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (!data.success) throw new Error(data.error || 'Save failed');
          self.roomFormOpen = false;
          self.refreshRooms();
        })
        .catch(function(err) { self.roomFormError = err.message; });
      },

      deleteRoomId: null,
      deleteRoomLabel: '',

      confirmDeleteRoom(id, label) {
        this.deleteRoomId = id;
        this.deleteRoomLabel = label;
      },

      executeDeleteRoom() {
        if (!this.deleteRoomId) return;
        const self = this;
        fetch('<?= BASE_URL ?>/owner/index.php?route=delete-room', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: this.deleteRoomId, csrf_token: '<?= $csrfToken ?>' }),
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (!data.success) throw new Error(data.error || 'Delete failed');
          self.deleteRoomId = null;
          self.refreshRooms();
        })
        .catch(function(err) { alert('Delete failed: ' + err.message); });
      },
    }));
  });
</script>
