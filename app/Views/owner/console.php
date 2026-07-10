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

$title = "Owner / Manager Console - ZyroInn";
$bodyData = 'x-data="{
  activeProperty: ' . (int)($propertyId ?? 0) . ',
  currentRoute: \'' . htmlspecialchars($route ?? 'dashboard') . '\',
  switchProperty(value) {
    const url = new URL(window.location.href);
    url.searchParams.set(\'property_id\', value);
    url.searchParams.set(\'route\', this.currentRoute);
    window.location.href = url.toString();
  }
}"';
include __DIR__ . '/../partials/owner-header.php';
?>
    <main class="mx-auto w-full max-w-7xl flex-grow px-4 py-8 pb-24 sm:px-6 lg:px-8 lg:pb-8">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-600">Owner / Manager Console</p>
          <h1 class="mt-1 text-2xl font-semibold text-brand-900">Run operations from one control center</h1>
          <p class="mt-2 text-sm text-neutral-500">Track occupancy, pricing, and key property activity without leaving the portal.</p>
        </div>
        <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
          <span>●</span>
          <span>Live booked occupancy · <?= htmlspecialchars($kpis['occupancy'] ?? 'N/A') ?>%</span>
        </div>
      </div>

      <div class="mt-6 grid gap-8 xl:grid-cols-[280px_minmax(0,1fr)]">
        <aside class="xl:col-span-1">
          <?php
          $title = $property['name'] ?? 'Property Console';
          $menuItems = [
              ['label' => 'Dashboard', 'href' => BASE_URL . '/owner/index.php?route=dashboard', 'active' => $route === 'dashboard'],
              ['label' => 'Rate & Inventory', 'href' => BASE_URL . '/owner/index.php?route=inventory', 'active' => $route === 'inventory'],
              ['label' => 'Property Features', 'href' => BASE_URL . '/owner/index.php?route=property-features', 'active' => $route === 'property-features'],
              ['label' => 'Property Settings', 'href' => BASE_URL . '/owner/index.php?route=property-settings', 'active' => $route === 'property-settings'],
              ['label' => 'Alerts', 'href' => BASE_URL . '/owner/index.php?route=alerts', 'active' => $route === 'alerts'],
              ['label' => 'Staff Scheduling', 'href' => BASE_URL . '/owner/index.php?route=staff-scheduling', 'active' => $route === 'staff-scheduling'],
              ['label' => 'Expenses', 'href' => BASE_URL . '/owner/index.php?route=expenses', 'active' => $route === 'expenses'],
              ['label' => 'Reports', 'href' => BASE_URL . '/owner/index.php?route=reports', 'active' => $route === 'reports'],
          ];
          $actions = [
              ['label' => 'Quick Insight', 'href' => BASE_URL . '/owner/index.php?route=dashboard'],
          ];
          $initialOpen = true;
          include __DIR__ . '/../partials/collapsible-drawer.php';
          ?>
        </aside>

        <section class="xl:col-span-1">
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
                  <template x-for="room in rooms" :key="room.id">
                    <article class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
                      <div class="flex items-center justify-between gap-3">
                        <div>
                          <p class="text-sm font-semibold text-brand-900" x-text="room.name"></p>
                          <p class="mt-1 text-sm text-neutral-500">
                            <span x-text="room.displayRate"></span> / night · <span x-text="room.displayStatus"></span>
                          </p>
                        </div>
                        <button type="button" class="rounded-pill border px-3 py-2 text-sm font-semibold transition"
                                :class="room.overrideStatus === 'blocked' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
                                @click="toggleRoomStatus(room.id)">
                          <span x-text="room.overrideStatus === 'blocked' ? 'Unblock' : 'Block'"></span>
                        </button>
                      </div>
                    </article>
                  </template>
                </div>
              </div>
            </div>
          <?php elseif (in_array($route, ['expenses', 'reports'], true)): ?>
            <div class="rounded-3xl border border-neutral-200 bg-white p-8 shadow-sm">
              <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-600">Coming soon</p>
              <h2 class="mt-3 text-2xl font-semibold text-brand-900">
                <?= htmlspecialchars(ucwords(str_replace('-', ' ', $route))) ?>
              </h2>
              <p class="mt-3 max-w-2xl text-sm leading-6 text-neutral-500">
                This phase focuses on the core operating console. Expense tracking and report export are staged for the next sprint.
              </p>
            </div>
          <?php else: ?>
            <div class="rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm">
              <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                  <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-600">Dashboard</p>
                  <h2 class="mt-2 text-2xl font-semibold text-brand-900">Performance snapshot for <?= htmlspecialchars($property['name'] ?? 'your property') ?></h2>
                  <p class="mt-2 text-sm text-neutral-500">Track occupancy, revenue momentum, and the most important service signals in one place.</p>
                </div>
                <div class="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm font-semibold text-neutral-600">
                  Updated live
                </div>
              </div>

              <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <?php
                $kpiCards = [
                    ['title' => 'Booked Occupancy', 'value' => ($kpis['occupancy'] ?? 0) . '%', 'change' => ($kpis['occupied_rooms'] ?? 0) . ' of ' . ($kpis['total_rooms'] ?? 0) . ' rooms booked', 'sparkline' => $kpis['occupancy'] ?? '0'],
                    ['title' => 'Revenue', 'value' => '$' . number_format($kpis['revenue'] ?? 0, 0), 'change' => 'From completed bookings', 'sparkline' => '—'],
                    ['title' => 'ADR', 'value' => '$' . number_format($kpis['adr'] ?? 0, 2), 'change' => 'Avg per booking', 'sparkline' => '—'],
                    ['title' => 'Bookings', 'value' => $kpis['bookings'] ?? 0, 'change' => ($kpis['due_today'] ?? 0) . ' due today', 'sparkline' => $kpis['bookings'] ?? '0'],
                ];
                foreach ($kpiCards as $card):
                    $title = $card['title'];
                    $value = $card['value'];
                    $change = $card['change'];
                    $sparkline = $card['sparkline'];
                    include __DIR__ . '/../partials/kpi-tile.php';
                endforeach;
                ?>
              </div>

              <div class="mt-8 grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
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
                            <span class="text-[10px] font-semibold text-neutral-500"><?= $pct ?>%</span>
                            <div class="w-full rounded-t-2xl bg-brand-500" style="height: <?= max($pct, 2) ?>%"></div>
                            <span class="text-[9px] text-neutral-400"><?= date('D', strtotime($day['date'])) ?></span>
                          </div>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <?php for ($i = 0; $i < 7; $i++): ?>
                          <div class="flex-1 rounded-t-2xl bg-neutral-200" style="height: 5%"></div>
                        <?php endfor; ?>
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
        </section>
      </div>
    </main>
  <?php include __DIR__ . '/../partials/owner-footer.php'; ?>

  <?php if ($route !== 'dashboard'): ?>
  <?php
  $label = 'Back to Dashboard';
  $href = BASE_URL . '/owner/index.php?route=dashboard';
  include __DIR__ . '/../partials/sticky-action-bar.php';
  ?>
  <?php endif; ?>

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
          const rootData = document.body.__x ? document.body.__x.$data : null;
          const currentPropertyId = rootData ? Number(rootData.activeProperty) : Number(this.activeProperty);
          const match = <?= json_encode($propertyOptions) ?>.find((option) => Number(option.id) === currentPropertyId);
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
      }));
    });
  </script>
