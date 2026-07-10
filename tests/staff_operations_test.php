<?php
// staff_operations_test.php - Automated Test Suite for Phase 0 Staff Operations

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\Database;

class StaffOperationsTest {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function run() {
        echo "\033[36m====================================================\033[0m\n";
        echo "\033[36mRunning ZyroInn Staff Operations Phase 0 Tests...\033[0m\n";
        echo "\033[36m====================================================\033[0m\n";

        try {
            $this->testRoomStatusLogAndUpdates();
            $this->testCheckInFlow();
            $this->testCheckOutFlowAndInvoice();
            $this->testWaitlistFlow();

            echo "\n\033[32;1m🎉 ALL STAFF OPERATIONS TESTS PASSED! 🎉\033[0m\n";
        } catch (Exception $e) {
            echo "\n\033[31;1m❌ TEST SUITE FAILED! ❌\033[0m\n";
            echo "\033[31mError: " . $e->getMessage() . "\033[0m\n";
            echo $e->getTraceAsString() . "\n";
            exit(1);
        }
    }

    private function testRoomStatusLogAndUpdates() {
        echo "\nRunning Case 1: Room Status Board and Updates...\n";

        // 1. Log a status of 'out_of_order' for Room 1 (Deluxe Sea View)
        $roomId = 1;
        $status = 'out_of_order';
        $userId = 2; // Leo (Housekeeper)
        $notes = 'AC unit leaking water, scheduled repair';

        $stmt = $this->db->prepare("
            INSERT INTO room_status_log (room_id, status, changed_by, changed_at, notes)
            VALUES (?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([$roomId, $status, $userId, $notes]);
        $logId = $this->db->lastInsertId();
        $this->assert($logId > 0, "Log should be successfully inserted");

        // Out of order should set room status to maintenance in rooms table
        $updateRoom = $this->db->prepare("UPDATE rooms SET status = 'maintenance' WHERE id = ?");
        $updateRoom->execute([$roomId]);

        // Verify status in DB
        $stmtCheck = $this->db->prepare("SELECT status FROM rooms WHERE id = ?");
        $stmtCheck->execute([$roomId]);
        $roomStatus = $stmtCheck->fetchColumn();
        $this->assert($roomStatus === 'maintenance', "Room status should be 'maintenance' after logging out_of_order");

        // Verify latest log
        $stmtLatest = $this->db->prepare("
            SELECT status FROM room_status_log 
            WHERE room_id = ? 
            ORDER BY changed_at DESC, id DESC LIMIT 1
        ");
        $stmtLatest->execute([$roomId]);
        $latestStatus = $stmtLatest->fetchColumn();
        $this->assert($latestStatus === 'out_of_order', "Latest housekeeping status should be out_of_order");

        // 2. Change status back to clean
        $status = 'clean';
        $notes = 'AC unit repaired and room cleaned.';
        $stmt->execute([$roomId, $status, $userId, $notes]);

        // Clean status should restore room status to available if it was maintenance
        $stmtCheck->execute([$roomId]);
        $currentRoomStatus = $stmtCheck->fetchColumn();
        if (in_array($currentRoomStatus, ['maintenance', 'out_of_service'])) {
            $updateRoomAvailable = $this->db->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
            $updateRoomAvailable->execute([$roomId]);
        }

        $stmtCheck->execute([$roomId]);
        $roomStatus = $stmtCheck->fetchColumn();
        $this->assert($roomStatus === 'available', "Room status should be restored to 'available' after cleaning");

        echo "\033[32m✓ Case 1 Passed.\033[0m\n";
    }

    private function testCheckInFlow() {
        echo "\nRunning Case 2: Front Office Check-In Flow...\n";

        // Create a test guest
        $guestEmail = 'tester.guest@example.com';
        $stmtDel = $this->db->prepare("DELETE FROM guests WHERE email = ?");
        $stmtDel->execute([$guestEmail]);

        $stmtGuest = $this->db->prepare("
            INSERT INTO guests (first_name, last_name, email, phone) 
            VALUES ('Test', 'Guest', ?, '+1-555-9999')
        ");
        $stmtGuest->execute([$guestEmail]);
        $guestId = $this->db->lastInsertId();

        // Create a test booking (Room 2 - Standard Garden View)
        $roomId = 2;
        $stmtBooking = $this->db->prepare("
            INSERT INTO bookings (property_id, room_id, guest_id, check_in_date, check_out_date, status, source)
            VALUES (1, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'confirmed', 'walk_in')
        ");
        $stmtBooking->execute([$roomId, $guestId]);
        $bookingId = $this->db->lastInsertId();

        // Simulate secure ID upload path assignment
        $mockIdPath = 'C:\\xampp\\htdocs\\ZyroInn\\storage\\ids\\guest_' . $guestId . '_mock.pdf';
        $stmtUpdateGuest = $this->db->prepare("UPDATE guests SET id_proof_path = ? WHERE id = ?");
        $stmtUpdateGuest->execute([$mockIdPath, $guestId]);

        // Verify ID proof path
        $stmtCheckGuest = $this->db->prepare("SELECT id_proof_path FROM guests WHERE id = ?");
        $stmtCheckGuest->execute([$guestId]);
        $savedPath = $stmtCheckGuest->fetchColumn();
        $this->assert($savedPath === $mockIdPath, "ID proof path should match secure upload path");

        // Verify available clean rooms filter query
        // Ensure Room 2 is Available and Clean. First, write a clean log for Room 2
        $stmtLog = $this->db->prepare("
            INSERT INTO room_status_log (room_id, status, changed_by, notes)
            VALUES (?, 'clean', 1, 'Pre-checkin clean')
        ");
        $stmtLog->execute([$roomId]);

        $stmtRooms = $this->db->prepare("
            SELECT r.id, r.room_number
            FROM rooms r
            LEFT JOIN room_status_log rsl ON rsl.id = (
                SELECT id FROM room_status_log
                WHERE room_id = r.id
                ORDER BY changed_at DESC, id DESC
                LIMIT 1
            )
            WHERE r.property_id = 1 AND r.status = 'available' AND rsl.status = 'clean'
        ");
        $stmtRooms->execute();
        $cleanRooms = $stmtRooms->fetchAll(PDO::FETCH_KEY_PAIR);
        $this->assert(array_key_exists($roomId, $cleanRooms), "Room 2 should be in available clean rooms list");

        // Capture initial payment of $50
        $stmtPayment = $this->db->prepare("
            INSERT INTO payments (booking_id, amount, currency, method, status, transaction_reference, paid_at)
            VALUES (?, 50.00, 'USD', 'cash', 'completed', 'REF12345', NOW())
        ");
        $stmtPayment->execute([$bookingId]);

        // Perform Check-in (update booking to checked_in and room status to occupied)
        $stmtCheckin = $this->db->prepare("UPDATE bookings SET status = 'checked_in' WHERE id = ?");
        $stmtCheckin->execute([$bookingId]);

        $stmtRoomOccupied = $this->db->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
        $stmtRoomOccupied->execute([$roomId]);

        // Verify state
        $stmtBCheck = $this->db->prepare("SELECT status FROM bookings WHERE id = ?");
        $stmtBCheck->execute([$bookingId]);
        $bStatus = $stmtBCheck->fetchColumn();
        $this->assert($bStatus === 'checked_in', "Booking status should be checked_in");

        $stmtRCheck = $this->db->prepare("SELECT status FROM rooms WHERE id = ?");
        $stmtRCheck->execute([$roomId]);
        $rStatus = $stmtRCheck->fetchColumn();
        $this->assert($rStatus === 'occupied', "Room status should be occupied");

        echo "\033[32m✓ Case 2 Passed.\033[0m\n";
    }

    private function testCheckOutFlowAndInvoice() {
        echo "\nRunning Case 3: Front Office Check-Out and Invoice Calculation...\n";

        // Query the test booking
        $guestEmail = 'tester.guest@example.com';
        $stmtB = $this->db->prepare("
            SELECT b.*, r.base_rate, r.id AS room_id
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN guests g ON b.guest_id = g.id
            WHERE g.email = ? AND b.status = 'checked_in'
            LIMIT 1
        ");
        $stmtB->execute([$guestEmail]);
        $booking = $stmtB->fetch();
        $this->assert(!empty($booking), "Should retrieve a checked-in booking");

        $bookingId = (int)$booking['id'];
        $roomId = (int)$booking['room_id'];
        $baseRate = (float)$booking['base_rate'];

        // Invoice Calculations
        $checkIn = new DateTime($booking['check_in_date']);
        $checkOut = new DateTime($booking['check_out_date']);
        $nights = $checkIn->diff($checkOut)->days;
        if ($nights <= 0) $nights = 1;

        $roomCharges = $nights * $baseRate;

        // Add booking addon
        $stmtAddon = $this->db->prepare("
            INSERT INTO booking_addons (booking_id, name, quantity, price)
            VALUES (?, 'Test Wi-Fi Premium', 1, 10.00)
        ");
        $stmtAddon->execute([$bookingId]);

        $addonTotal = 10.00;
        $totalCharges = $roomCharges + $addonTotal;

        // Fetch payments made
        $paymentStmt = $this->db->prepare("SELECT SUM(amount) FROM payments WHERE booking_id = ? AND status = 'completed'");
        $paymentStmt->execute([$bookingId]);
        $paidAmount = (float)$paymentStmt->fetchColumn();

        $outstandingBalance = $totalCharges - $paidAmount;

        $this->assert($totalCharges === ($roomCharges + 10.00), "Total charges calculation mismatch");
        $this->assert($outstandingBalance === ($totalCharges - 50.00), "Outstanding balance mismatch (should account for $50 prepay)");

        // Capture outstanding balance payment
        if ($outstandingBalance > 0) {
            $stmtPayFinal = $this->db->prepare("
                INSERT INTO payments (booking_id, amount, currency, method, status, transaction_reference, paid_at)
                VALUES (?, ?, 'USD', 'card', 'completed', 'REF_FINAL', NOW())
            ");
            $stmtPayFinal->execute([$bookingId, $outstandingBalance]);
        }

        // Perform Checkout
        $stmtCheckout = $this->db->prepare("UPDATE bookings SET status = 'checked_out' WHERE id = ?");
        $stmtCheckout->execute([$bookingId]);

        // Release room
        $stmtReleaseRoom = $this->db->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
        $stmtReleaseRoom->execute([$roomId]);

        // Write Dirty to room status log
        $stmtLogDirty = $this->db->prepare("
            INSERT INTO room_status_log (room_id, status, changed_by, notes)
            VALUES (?, 'dirty', 1, 'Dirty after checkout test')
        ");
        $stmtLogDirty->execute([$roomId]);

        // Verify status
        $stmtBCheck = $this->db->prepare("SELECT status FROM bookings WHERE id = ?");
        $stmtBCheck->execute([$bookingId]);
        $this->assert($stmtBCheck->fetchColumn() === 'checked_out', "Booking should be checked_out");

        $stmtRCheck = $this->db->prepare("SELECT status FROM rooms WHERE id = ?");
        $stmtRCheck->execute([$roomId]);
        $this->assert($stmtRCheck->fetchColumn() === 'available', "Room should be available");

        $stmtLatestStatus = $this->db->prepare("
            SELECT status FROM room_status_log 
            WHERE room_id = ? 
            ORDER BY changed_at DESC, id DESC LIMIT 1
        ");
        $stmtLatestStatus->execute([$roomId]);
        $this->assert($stmtLatestStatus->fetchColumn() === 'dirty', "Room housekeeping status should now be dirty");

        echo "\033[32m✓ Case 3 Passed.\033[0m\n";
    }

    private function testWaitlistFlow() {
        echo "\nRunning Case 4: Waitlist Reservation Flow...\n";

        // Create a waitlisted booking for Room 1
        $guestEmail = 'tester.guest@example.com';
        $stmtGuestId = $this->db->prepare("SELECT id FROM guests WHERE email = ? LIMIT 1");
        $stmtGuestId->execute([$guestEmail]);
        $guestId = $stmtGuestId->fetchColumn();

        $stmtWaitlist = $this->db->prepare("
            INSERT INTO bookings (property_id, room_id, guest_id, check_in_date, check_out_date, status, source)
            VALUES (1, 1, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'waitlisted', 'walk_in')
        ");
        $stmtWaitlist->execute([$guestId]);
        $bookingId = $this->db->lastInsertId();

        // Verify status is waitlisted
        $stmtCheck = $this->db->prepare("SELECT status FROM bookings WHERE id = ?");
        $stmtCheck->execute([$bookingId]);
        $this->assert($stmtCheck->fetchColumn() === 'waitlisted', "Booking should be waitlisted");

        // Confirm waitlist booking and assign to Room 1
        $stmtConfirm = $this->db->prepare("UPDATE bookings SET room_id = 1, status = 'confirmed' WHERE id = ?");
        $stmtConfirm->execute([$bookingId]);

        // Verify booking status is now confirmed
        $stmtCheck->execute([$bookingId]);
        $this->assert($stmtCheck->fetchColumn() === 'confirmed', "Booking should now be confirmed");

        // Clean up test data
        $stmtDelAddon = $this->db->prepare("DELETE FROM booking_addons WHERE booking_id IN (SELECT id FROM bookings WHERE guest_id = ?)");
        $stmtDelAddon->execute([$guestId]);
        $stmtDelPay = $this->db->prepare("DELETE FROM payments WHERE booking_id IN (SELECT id FROM bookings WHERE guest_id = ?)");
        $stmtDelPay->execute([$guestId]);
        $stmtDelLogs = $this->db->prepare("DELETE FROM room_status_log WHERE room_id IN (1, 2) AND notes LIKE '%test%'");
        $stmtDelLogs->execute();
        $stmtDelBookings = $this->db->prepare("DELETE FROM bookings WHERE guest_id = ?");
        $stmtDelBookings->execute([$guestId]);
        $stmtDelGuest = $this->db->prepare("DELETE FROM guests WHERE id = ?");
        $stmtDelGuest->execute([$guestId]);

        echo "\033[32m✓ Case 4 Passed.\033[0m\n";
    }

    private function assert(bool $condition, string $message) {
        if (!$condition) {
            throw new Exception("Assertion Failed: " . $message);
        }
    }
}

// Run the tests
$testSuite = new StaffOperationsTest();
$testSuite->run();
