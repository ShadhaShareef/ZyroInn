<?php
/**
 * Concurrency test: two simultaneous booking attempts on the same room/dates.
 * Only one should succeed (confirmed); the other must detect the overlap (waitlisted).
 *
 * Runs two child PHP processes that race using FOR UPDATE locking,
 * then verifies exactly one confirmed booking resulted.
 */

// Bootstrap for main process DB access
require_once __DIR__ . '/../app/bootstrap.php';
use App\Services\Database;

$db = Database::getConnection();
$tmpDir = sys_get_temp_dir() . '/bkg_test_' . bin2hex(random_bytes(4));
mkdir($tmpDir, 0700, true);

$roomId = 9991;
$propertyId = 1;
$checkIn = '2026-08-01';
$checkOut = '2026-08-04';
$guestId = 1;

// ---------- SETUP ----------
echo "=== Overlap Concurrency Test ===\n\n";

// Clean any leftover test data
$db->exec("DELETE FROM payments WHERE booking_id IN (SELECT id FROM bookings WHERE room_id = $roomId)");
$db->exec("DELETE FROM booking_addons WHERE booking_id IN (SELECT id FROM bookings WHERE room_id = $roomId)");
$db->exec("DELETE FROM bookings WHERE room_id = $roomId");

// Create a temporary test room if it doesn't exist
$stmt = $db->prepare("SELECT id FROM rooms WHERE id = ?");
$stmt->execute([$roomId]);
if (!$stmt->fetch()) {
    // Check if there's a room we can use for testing — use room 1
    $roomId = 1;
    $stmt = $db->prepare("SELECT id, property_id FROM rooms WHERE id = ?");
    $stmt->execute([$roomId]);
    $room = $stmt->fetch();
    if (!$room) {
        die("No room available for testing.\n");
    }
    $propertyId = (int)$room['property_id'];
}

// Clean any existing bookings for this room on our test dates
$existing = $db->prepare("SELECT id FROM bookings WHERE room_id = ? AND check_in_date < ? AND check_out_date > ?");
$existing->execute([$roomId, $checkOut, $checkIn]);
foreach ($existing as $row) {
    $bid = $row['id'];
    $db->exec("DELETE FROM payments WHERE booking_id = $bid");
    $db->exec("DELETE FROM booking_addons WHERE booking_id = $bid");
    $db->exec("DELETE FROM bookings WHERE id = $bid");
}

echo "Room: #{$roomId}, Dates: {$checkIn} – {$checkOut}\n\n";

// ---------- CREATE CHILD SCRIPT ----------
$childScript = $tmpDir . '/child.php';
$childCode = <<<'PHP'
<?php
require_once '<BOOT_PATH>';

use App\Services\Database;
use App\Services\BookingService;

$tmpDir = '<TMPDIR>';
$pid = getmypid();

// Wait for go signal
$goFile = $tmpDir . '/GO';
$start = microtime(true);
while (!file_exists($goFile)) {
    usleep(10000); // 10ms
    if (microtime(true) - $start > 10) {
        file_put_contents($tmpDir . '/result_' . $pid . '.json', json_encode(['pid' => $pid, 'status' => 'timeout', 'error' => 'Go signal never arrived']));
        exit(1);
    }
}

$db = Database::getConnection();
$bookingService = new BookingService();

$roomId = <ROOMID>;
$checkIn = '<CHECKIN>';
$checkOut = '<CHECKOUT>';
$guestId = <GUESTID>;

try {
    $db->beginTransaction();

    // This is the critical path — FOR UPDATE lock inside hasOverlap prevents race
    $hasOverlap = $bookingService->hasOverlap($roomId, $checkIn, $checkOut);

    if ($hasOverlap) {
        $db->rollBack();
        $result = ['pid' => $pid, 'status' => 'waitlisted', 'reason' => 'Overlap detected by FOR UPDATE lock'];
    } else {
        // Insert booking
        $stmt = $db->prepare("INSERT INTO bookings (property_id, room_id, guest_id, check_in_date, check_out_date, status, source) VALUES (?, ?, ?, ?, ?, 'confirmed', 'concurrency_test')");
        $stmt->execute([1, $roomId, $guestId, $checkIn, $checkOut]);
        $bookingId = $db->lastInsertId();
        $db->commit();
        $result = ['pid' => $pid, 'status' => 'confirmed', 'booking_id' => $bookingId];
    }
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    $result = ['pid' => $pid, 'status' => 'error', 'error' => $e->getMessage()];
}

file_put_contents($tmpDir . '/result_' . $pid . '.json', json_encode($result));
echo json_encode($result) . "\n";
PHP;

// Replace placeholders
$childCode = str_replace(
    ['<BOOT_PATH>', '<TMPDIR>', '<ROOMID>', '<CHECKIN>', '<CHECKOUT>', '<GUESTID>'],
    [__DIR__ . '/../app/bootstrap.php', $tmpDir, $roomId, $checkIn, $checkOut, $guestId],
    $childCode
);
file_put_contents($childScript, $childCode);

// ---------- SPAWN CHILDREN ----------
$children = [];
for ($i = 0; $i < 2; $i++) {
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $proc = proc_open(PHP_BINARY . ' ' . escapeshellarg($childScript), $descriptors, $pipes, null, null, ['bypass_shell' => true]);
    if (!is_resource($proc)) {
        echo "Failed to spawn child process $i\n";
        exit(1);
    }
    $children[] = ['proc' => $proc, 'pipes' => $pipes, 'pid' => 'child_' . $i];
    // Give each child a brief moment to start and reach the GO wait
    usleep(200000); // 200ms
}

echo "Spawning 2 concurrent booking attempts...\n";

// Signal both children to go at the same time
file_put_contents($tmpDir . '/GO', 'go');

// ---------- COLLECT RESULTS ----------
$results = [];
foreach ($children as $i => $child) {
    $output = stream_get_contents($child['pipes'][1]);
    $errors = stream_get_contents($child['pipes'][2]);
    fclose($child['pipes'][0]);
    fclose($child['pipes'][1]);
    fclose($child['pipes'][2]);
    $exitCode = proc_close($child['proc']);

    $resultFile = $tmpDir . '/result_*';
    $result = json_decode($output, true) ?: ['pid' => 'unknown', 'status' => 'unknown'];
    $results[] = $result;

    $statusLabel = $result['status'] ?? 'error';
    echo "  Child $i: $statusLabel" . (isset($result['booking_id']) ? ' (booking #' . $result['booking_id'] . ')' : '') . "\n";
    if (!empty($result['error'])) {
        echo "    Error: {$result['error']}\n";
    }
}

echo "\n";

// ---------- VERIFY ----------
// Count confirmed bookings for this room/dates
$verify = $db->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND check_in_date = ? AND check_out_date = ? AND status = 'confirmed'");
$verify->execute([$roomId, $checkIn, $checkOut]);
$confirmedCount = (int)$verify->fetchColumn();

$waitlistedStmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND check_in_date = ? AND check_out_date = ? AND status = 'waitlisted'");
$waitlistedStmt->execute([$roomId, $checkIn, $checkOut]);
$waitlistedCount = (int)$waitlistedStmt->fetchColumn();

echo "Result: {$confirmedCount} confirmed, {$waitlistedCount} waitlisted\n";
echo "Expected: 1 confirmed, 0 or 1 waitlisted (second attempt should detect overlap)\n\n";

if ($confirmedCount === 1) {
    echo "PASS: Only one confirmed booking exists — FOR UPDATE lock prevented race condition.\n";
} else {
    echo "FAIL: Expected 1 confirmed booking, got {$confirmedCount}.\n";
}

// ---------- CLEANUP ----------
$db->exec("DELETE FROM bookings WHERE room_id = $roomId AND check_in_date = '$checkIn' AND check_out_date = '$checkOut'");
// Clean temp files
array_map('unlink', glob($tmpDir . '/*'));
rmdir($tmpDir);

echo "\n=== Test Complete ===\n";
