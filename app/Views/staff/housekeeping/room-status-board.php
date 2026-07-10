<?php
if (session_status() === PHP_SESSION_NONE) {
    $env = [];
    $envPath = __DIR__ . '/../../../config/env.php';
    if (file_exists($envPath)) {
        $env = require $envPath;
    }
    session_init($env);
}

if (!isset($rooms)) {
    require_once __DIR__ . '/../../../bootstrap.php';
    if (empty($_SESSION['role'])) {
        $_SESSION['role'] = 'housekeeping';
        $_SESSION['first_name'] = 'Leo';
        $_SESSION['last_name'] = 'Mendez';
        $_SESSION['user_id'] = 2;
        $_SESSION['property_id'] = 1;
    }
    
    $db = \App\Services\Database::getConnection();
    $propertyId = (int)($_SESSION['property_id'] ?? 1);
    try {
        $stmt = $db->prepare("
            SELECT r.*, rsl.status AS housekeeping_status, rsl.notes, rsl.changed_at, u.first_name AS changer_first, u.last_name AS changer_last
            FROM rooms r
            LEFT JOIN room_status_log rsl ON rsl.id = (
                SELECT id FROM room_status_log
                WHERE room_id = r.id
                ORDER BY changed_at DESC, id DESC
                LIMIT 1
            )
            LEFT JOIN users u ON rsl.changed_by = u.id
            WHERE r.property_id = ?
            ORDER BY r.room_number ASC
        ");
        $stmt->execute([$propertyId]);
        $rooms = $stmt->fetchAll();
    } catch (\PDOException $e) {
        \App\Services\Logger::error('Room status board query failed', ['exception' => $e->getMessage()]);
        $rooms = [];
    }
}

if (!function_exists('getRoomFloor')) {
    function getRoomFloor($roomNumber) {
        if (strlen($roomNumber) >= 3 && is_numeric($roomNumber)) {
            return substr($roomNumber, 0, -2);
        }
        return '1';
    }
}

// CSRF token managed via AuthService::generateCsrfToken()
?>
<?php
$title = "Room Status Board";
$bodyData = 'x-data="{ selectedFloor: \'all\', bottomSheetOpen: false, activeRoom: { id: 0, number: \'\', status: \'\', notes: \'\' } }"';
include __DIR__ . '/../../partials/staff-header.php';
?>

      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl font-bold text-brand-900 leading-tight">Room Status Board</h1>
          <p class="text-xs text-neutral-500 mt-1">Real-time housekeeping grid of all rooms for the property.</p>
        </div>

        <!-- Floor Filtering Navigation -->
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-xs font-bold text-neutral-400 uppercase tracking-widest">Filter by Floor:</span>
          <button 
              @click="selectedFloor = 'all'" 
              :class="selectedFloor === 'all' ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-500 hover:bg-neutral-200'"
              class="px-4 py-2 rounded-pill text-xs font-semibold transition"
          >
            All Floors
          </button>
          <?php
          // Dynamically extract unique floors
          $floors = [];
          foreach ($rooms as $room) {
              $floor = getRoomFloor($room['room_number']);
              $floors[$floor] = true;
          }
          $floors = array_keys($floors);
          sort($floors);
          foreach ($floors as $floor):
          ?>
            <button 
                @click="selectedFloor = '<?= $floor ?>'" 
                :class="selectedFloor === '<?= $floor ?>' ? 'bg-brand-500 text-white' : 'bg-neutral-100 text-neutral-500 hover:bg-neutral-200'"
                class="px-4 py-2 rounded-pill text-xs font-semibold transition"
            >
              Floor <?= htmlspecialchars($floor) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- KPI Summary Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <?php
        $totalRoomsCount = count($rooms);
        $cleanCount = 0;
        $dirtyCount = 0;
        $inspectCount = 0;
        $oooCount = 0;
        foreach ($rooms as $r) {
            $hkStatus = $r['housekeeping_status'] ?? 'dirty';
            if ($hkStatus === 'clean') $cleanCount++;
            elseif ($hkStatus === 'dirty') $dirtyCount++;
            elseif ($hkStatus === 'inspect') $inspectCount++;
            elseif ($hkStatus === 'out_of_order') $oooCount++;
        }
        ?>
        <div class="bg-white p-4 rounded-2xl border border-neutral-200 shadow-sm">
          <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Total Rooms</div>
          <div class="text-2xl font-bold text-brand-900 mt-1"><?= $totalRoomsCount ?></div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-neutral-200 shadow-sm">
          <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Clean / Ready</div>
          <div class="text-2xl font-bold text-emerald-600 mt-1"><?= $cleanCount ?></div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-neutral-200 shadow-sm">
          <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Dirty / Cleanup</div>
          <div class="text-2xl font-bold text-amber-600 mt-1"><?= $dirtyCount ?></div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-neutral-200 shadow-sm">
          <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Out Of Order</div>
          <div class="text-2xl font-bold text-rose-600 mt-1"><?= $oooCount ?></div>
        </div>
      </div>

      <!-- Room Cards Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php foreach ($rooms as $room): 
          $floor = getRoomFloor($room['room_number']);
          $hkStatus = $room['housekeeping_status'] ?? 'dirty';
          $roomStatus = $room['status']; // available, occupied, reserved, maintenance, out_of_service
        ?>
          <div 
              x-show="selectedFloor === 'all' || selectedFloor === '<?= $floor ?>'"
              @click="activeRoom = { 
                  id: <?= $room['id'] ?>, 
                  number: '<?= htmlspecialchars($room['room_number']) ?>', 
                  status: '<?= $hkStatus ?>', 
                  notes: '<?= htmlspecialchars($room['notes'] ?? '') ?>' 
              }; bottomSheetOpen = true"
              class="group cursor-pointer bg-white rounded-3xl p-6 shadow-sm border border-neutral-200 hover:border-brand-300 hover:shadow-md transition duration-300 flex flex-col justify-between min-h-[160px] relative overflow-hidden"
          >
            <!-- Background highlight on hover -->
            <div class="absolute inset-0 bg-gradient-to-br from-brand-50/0 to-brand-50/20 opacity-0 group-hover:opacity-100 transition duration-300"></div>

            <div class="relative z-10 flex items-start justify-between">
              <div>
                <span class="text-xs font-bold text-neutral-400 uppercase tracking-wider">Floor <?= htmlspecialchars($floor) ?></span>
                <h3 class="text-3xl font-extrabold text-brand-900 mt-1"><?= htmlspecialchars($room['room_number']) ?></h3>
                <p class="text-xs text-neutral-500 font-semibold mt-1"><?= htmlspecialchars($room['room_type']) ?></p>
              </div>

              <!-- Housekeeping Status Badge -->
              <div>
                <?php
                $status = $hkStatus;
                $type = 'room';
                include __DIR__ . '/../../partials/status-badge.php';
                ?>
              </div>
            </div>

            <!-- Room Front Office / Occupancy Status -->
            <div class="relative z-10 mt-6 pt-4 border-t border-neutral-100 flex items-center justify-between">
              <span class="text-xs font-medium text-neutral-500">Occupancy:</span>
              <span class="rounded-pill px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider
                <?= $roomStatus === 'available' ? 'bg-emerald-100 text-emerald-800' : '' ?>
                <?= $roomStatus === 'occupied' ? 'bg-indigo-100 text-indigo-800' : '' ?>
                <?= $roomStatus === 'reserved' ? 'bg-blue-100 text-blue-800' : '' ?>
                <?= $roomStatus === 'maintenance' ? 'bg-rose-100 text-rose-800' : '' ?>
                <?= $roomStatus === 'out_of_service' ? 'bg-neutral-200 text-neutral-800' : '' ?>
              ">
                <?= htmlspecialchars(str_replace('_', ' ', $roomStatus)) ?>
              </span>
            </div>

            <!-- Last Changed Info (Tiny Footer) -->
            <?php if (!empty($room['changed_at'])): ?>
              <div class="relative z-10 text-[10px] text-neutral-400 mt-2 text-right">
                Updated by <?= htmlspecialchars($room['changer_first'] . ' ' . substr($room['changer_last'], 0, 1)) ?>. on <?= date('M d, H:i', strtotime($room['changed_at'])) ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

  <!-- Housekeeping Status Update Bottom Sheet (Alpine.js) -->
  <div x-show="bottomSheetOpen" x-cloak class="fixed inset-0 z-50 flex items-end bg-neutral-900/40" @click.self="bottomSheetOpen = false" @keydown.escape.window="bottomSheetOpen = false">
    
    <div 
        x-show="bottomSheetOpen"
        x-transition:enter="transition duration-300 ease-out transform"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition duration-200 ease-in transform"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="mx-auto w-full max-w-xl rounded-t-3xl bg-white p-6 shadow-2xl focus:outline-none"
        role="dialog" 
        aria-modal="true"
    >
      <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
        <div>
          <h3 class="text-xl font-bold text-brand-900">Update Room <span x-text="activeRoom.number"></span></h3>
          <p class="text-xs text-neutral-500 mt-1">Change the housekeeping status for room status log.</p>
        </div>
        <button type="button" @click="bottomSheetOpen = false" class="rounded-full bg-neutral-100 p-2 text-neutral-500 hover:bg-neutral-200 transition">
          <span>&times;</span>
        </button>
      </div>

      <form action="index.php?route=update-room-status" method="POST" class="mt-6 space-y-4">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?= AuthService::generateCsrfToken() ?>">
        <!-- Room ID -->
        <input type="hidden" name="room_id" :value="activeRoom.id">

        <!-- Status Select -->
        <div>
          <label class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Housekeeping Status</label>
          <div class="grid grid-cols-2 gap-3">
            <label :class="activeRoom.status === 'clean' ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-neutral-200 bg-white text-neutral-500'" class="flex items-center justify-center p-3 rounded-2xl border text-sm font-semibold cursor-pointer hover:border-brand-300 transition">
              <input type="radio" name="status" value="clean" x-model="activeRoom.status" class="sr-only">
              <span>🟢 Clean / Ready</span>
            </label>
            <label :class="activeRoom.status === 'dirty' ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-neutral-200 bg-white text-neutral-500'" class="flex items-center justify-center p-3 rounded-2xl border text-sm font-semibold cursor-pointer hover:border-brand-300 transition">
              <input type="radio" name="status" value="dirty" x-model="activeRoom.status" class="sr-only">
              <span>🟠 Dirty / Cleanup</span>
            </label>
            <label :class="activeRoom.status === 'inspect' ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-neutral-200 bg-white text-neutral-500'" class="flex items-center justify-center p-3 rounded-2xl border text-sm font-semibold cursor-pointer hover:border-brand-300 transition">
              <input type="radio" name="status" value="inspect" x-model="activeRoom.status" class="sr-only">
              <span>🟣 Inspect</span>
            </label>
            <label :class="activeRoom.status === 'out_of_order' ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-neutral-200 bg-white text-neutral-500'" class="flex items-center justify-center p-3 rounded-2xl border text-sm font-semibold cursor-pointer hover:border-brand-300 transition">
              <input type="radio" name="status" value="out_of_order" x-model="activeRoom.status" class="sr-only">
              <span>🔴 Out Of Order</span>
            </label>
          </div>
        </div>

        <!-- Notes Textarea -->
        <div>
          <label for="notes" class="block text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Notes / Description</label>
          <textarea 
              id="notes" 
              name="notes" 
              rows="3" 
              x-model="activeRoom.notes"
              placeholder="e.g. Cleared trash, changed bedsheets, AC checked..."
              class="w-full rounded-2xl border border-neutral-200 p-3 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
          ></textarea>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-3 pt-4 border-t border-neutral-100">
          <button type="button" @click="bottomSheetOpen = false" class="w-1/2 rounded-2xl bg-neutral-100 py-3 text-sm font-bold text-neutral-500 hover:bg-neutral-200 transition">
            Cancel
          </button>
          <button type="submit" class="w-1/2 rounded-2xl bg-brand-500 py-3 text-sm font-bold text-white hover:bg-brand-600 transition shadow-sm">
            Save Status
          </button>
        </div>
      </form>
    </div>
  </div>

<?php include __DIR__ . '/../../partials/staff-footer.php'; ?>
