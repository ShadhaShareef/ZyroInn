<?php
// index.php - Staff Surface Front Controller
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Services\AuthService;
use App\Services\LoyaltyService;
use App\Services\Database;
use App\Services\Logger;
use App\Services\IdEncryption;
use App\Services\MessageService;

$env = [];
$envPath = __DIR__ . '/../../config/env.php';
if (file_exists($envPath)) {
    $env = require $envPath;
}
$devMode = $env['dev_mode'] ?? true;

session_init($env);
Logger::init();
requireEncryptionKey($env);
enforceHttps($env);

if (empty($env['encryption_key']) || strlen($env['encryption_key']) < 16) {
    $env['encryption_key'] = ($devMode ? 'dev-only-key-' . md5($env['app_url'] ?? 'zyroinn') : '');
}

$loyaltyService = new LoyaltyService();

if ($devMode) {
    $db = Database::getConnection();

    if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['front_office', 'housekeeping', 'maintenance', 'fnb', 'manager'])) {
        $loginAs = $_GET['login_as'] ?? 'front_office';
        $roleMap = [
            'front_office' => 'priya.das@example.com',
            'housekeeping' => 'leo.mendez@example.com',
            'maintenance' => 'asha.reddy@example.com',
            'fnb' => 'vikram.malhotra@example.com',
            'manager' => 'rohit.sharma@example.com'
        ];
        $email = $roleMap[$loginAs] ?? 'priya.das@example.com';
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $staff = $stmt->fetch();
            if ($staff) {
                $_SESSION['user_id'] = $staff['id'];
                $_SESSION['role'] = $staff['role'];
                $_SESSION['first_name'] = $staff['first_name'];
                $_SESSION['last_name'] = $staff['last_name'];
                $_SESSION['property_id'] = $staff['property_id'] ?? 1;
            } else {
                http_response_code(500);
                die("Error: Seeded staff user not found in database. Run migration first.");
            }
        } catch (Exception $e) {
            http_response_code(500);
            die("Authentication error: " . $e->getMessage());
        }
    }

    if (isset($_GET['switch_to'])) {
        $target = $_GET['switch_to'];
        if (in_array($target, ['front_office', 'housekeeping', 'maintenance', 'fnb', 'manager'])) {
            unset($_SESSION['user_id']);
            unset($_SESSION['role']);
            $defaultRoutes = [
                'front_office' => 'room-status-board',
                'housekeeping' => 'room-status-board',
                'maintenance' => 'maintenance-queue',
                'fnb' => 'fnb-orders',
                'manager' => 'room-status-board'
            ];
            header("Location: index.php?login_as=" . $target . "&route=" . ($defaultRoutes[$target] ?? 'room-status-board'));
            exit;
        }
    }
} else {
    AuthService::requireAuth(['front_office', 'housekeeping', 'maintenance', 'fnb', 'manager']);
}

$route = $_GET['route'] ?? 'room-status-board';
$db = Database::getConnection();
$propertyId = (int)$_SESSION['property_id'];

// CSRF token is managed through AuthService::generateCsrfToken() / AuthService::verifyCsrfToken()

// ----------------------------------------------------
// ROUTE: Secure View ID
// ----------------------------------------------------
if ($route === 'view-id') {
    $allowedRoles = ['front_office', 'owner', 'admin'];
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, $allowedRoles, true)) {
        http_response_code(403);
        die("Access denied. Only Front Office and Owner roles can view ID documents.");
    }

    $guestId = isset($_GET['guest_id']) ? (int)$_GET['guest_id'] : 0;
    if ($guestId <= 0) {
        http_response_code(400);
        die("Invalid guest ID.");
    }

    // Rate limiting: max 30 ID views per minute per session
    $rateKey = '_id_view_ratelimit';
    $now = time();
    $window = 60;
    $maxViews = 30;
    if (!isset($_SESSION[$rateKey])) {
        $_SESSION[$rateKey] = [];
    }
    $_SESSION[$rateKey] = array_filter($_SESSION[$rateKey], function($ts) use ($now, $window) {
        return $ts > ($now - $window);
    });
    if (count($_SESSION[$rateKey]) >= $maxViews) {
        http_response_code(429);
        die("Rate limit exceeded. Try again later.");
    }
    $_SESSION[$rateKey][] = $now;

    try {
        $stmt = $db->prepare("SELECT g.id_proof_path, u.first_name, u.last_name, u.email
                              FROM guests g
                              LEFT JOIN users u ON u.id = g.user_id
                              WHERE g.id = ?");
        $stmt->execute([$guestId]);
        $row = $stmt->fetch();
        $path = $row ? $row['id_proof_path'] : null;
        $guestName = $row ? trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) : 'Unknown';
    } catch (\PDOException $e) {
        Logger::error('View ID query failed', ['exception' => $e->getMessage()]);
        $path = null;
    }

    if (!$path || !file_exists($path)) {
        http_response_code(404);
        die("ID proof not found.");
    }

    // Decrypt the file before serving
    $encKey = $env['encryption_key'] ?? '';
    if (strlen($encKey) < 16) {
        http_response_code(500);
        die("Encryption key not configured.");
    }

    try {
        $plaintext = IdEncryption::decryptFile($path, $encKey);
    } catch (\RuntimeException $e) {
        Logger::error('View ID decryption failed', ['guest_id' => $guestId, 'exception' => $e->getMessage()]);
        http_response_code(500);
        die("Failed to decrypt ID proof.");
    }

    // Audit log: who viewed which guest's ID
    $viewerName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
    Logger::info('ID document viewed', [
        'viewer_user_id'  => $_SESSION['user_id'] ?? 0,
        'viewer_name'     => $viewerName,
        'viewer_role'     => $_SESSION['role'] ?? '',
        'guest_id'        => $guestId,
        'guest_name'      => $guestName,
        'file'            => basename($path),
    ]);

    // Validate MIME type of decrypted content using finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedMime = finfo_buffer($finfo, $plaintext);
    finfo_close($finfo);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
    if (!in_array($detectedMime, $allowedMimes, true)) {
        Logger::error('View ID MIME mismatch', [
            'guest_id'      => $guestId,
            'detected_mime' => $detectedMime,
        ]);
        http_response_code(500);
        die("Invalid ID document format.");
    }

    header("Content-Type: " . $detectedMime);
    header("Content-Length: " . strlen($plaintext));
    header("Content-Disposition: inline");
    echo $plaintext;
    exit;
}

// ----------------------------------------------------
// ROUTE: Room Status Board
// ----------------------------------------------------
elseif ($route === 'room-status-board') {
    // Fetch all rooms with their latest housekeeping status from room_status_log
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

    // Helper to determine floor
    function getRoomFloor($roomNumber) {
        if (strlen($roomNumber) >= 3 && is_numeric($roomNumber)) {
            return substr($roomNumber, 0, -2);
        }
        return '1';
    }

    // Include the room status board view
    include __DIR__ . '/../../app/Views/staff/housekeeping/room-status-board.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Update Room Status
// ----------------------------------------------------
elseif ($route === 'update-room-status') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        die("Method not allowed");
    }

    if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die("CSRF validation failed");
    }

    $roomId = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

    if ($roomId <= 0 || !in_array($status, ['clean', 'dirty', 'inspect', 'out_of_order'])) {
        die("Invalid parameters");
    }

    // Record the status change
    $stmt = $db->prepare("
        INSERT INTO room_status_log (room_id, status, changed_by, changed_at, notes)
        VALUES (?, ?, ?, NOW(), ?)
    ");
    $stmt->execute([$roomId, $status, $_SESSION['user_id'], $notes]);

    // Update rooms status if it corresponds to out_of_order (maintenance) or clean (available)
    if ($status === 'out_of_order') {
        $stmtUpdate = $db->prepare("UPDATE rooms SET status = 'maintenance' WHERE id = ?");
        $stmtUpdate->execute([$roomId]);
    } elseif ($status === 'clean') {
        // If room is clean, change status back to available if it was maintenance or out_of_service
        $stmtCheck = $db->prepare("SELECT status FROM rooms WHERE id = ?");
        $stmtCheck->execute([$roomId]);
        $currentRoomStatus = $stmtCheck->fetchColumn();
        if (in_array($currentRoomStatus, ['maintenance', 'out_of_service'])) {
            $stmtUpdate = $db->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
            $stmtUpdate->execute([$roomId]);
        }
    }

    header("Location: index.php?route=room-status-board&success=Room status updated.");
    exit;
}

// ----------------------------------------------------
// ROUTE: Reservations List & Create & Action
// ----------------------------------------------------
elseif ($route === 'reservations') {
    // List bookings
    $search = $_GET['search'] ?? '';
    $statusFilter = $_GET['status'] ?? '';

    $query = "
        SELECT b.*, r.room_number, r.room_type, g.first_name, g.last_name, g.email, g.phone, g.id_proof_path,
               bp.floor_preference, bp.bed_type,
               COALESCE(p_total.total_paid, 0) AS total_paid,
               COALESCE(ba_total.total_addons, 0) AS total_addon_charges,
               GROUP_CONCAT(DISTINCT CONCAT(sr.request_type, ':', sr.request_text) SEPARATOR '||') AS special_requests_concat
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        LEFT JOIN guests g ON b.guest_id = g.id
        LEFT JOIN booking_preferences bp ON bp.booking_id = b.id
        LEFT JOIN special_requests sr ON sr.booking_id = b.id
        LEFT JOIN (
            SELECT booking_id, SUM(amount) AS total_paid
            FROM payments
            WHERE status = 'completed'
            GROUP BY booking_id
        ) p_total ON p_total.booking_id = b.id
        LEFT JOIN (
            SELECT booking_id, SUM(price * quantity) AS total_addons
            FROM booking_addons
            GROUP BY booking_id
        ) ba_total ON ba_total.booking_id = b.id
        WHERE b.property_id = ?
    ";
    $params = [$propertyId];

    if ($search !== '') {
        $query .= " AND (g.first_name LIKE ? OR g.last_name LIKE ? OR g.email LIKE ? OR r.room_number LIKE ?)";
        $searchParam = "%{$search}%";
        array_push($params, $searchParam, $searchParam, $searchParam, $searchParam);
    }

    if ($statusFilter !== '') {
        $query .= " AND b.status = ?";
        $params[] = $statusFilter;
    }

    $query .= " GROUP BY b.id, r.id, g.id, bp.id ORDER BY b.check_in_date ASC, b.id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();

    // Parse concatenated special requests into array
    foreach ($bookings as &$b) {
        $b['special_requests'] = [];
        if (!empty($b['special_requests_concat'])) {
            $parts = explode('||', $b['special_requests_concat']);
            foreach ($parts as $part) {
                $colonPos = strpos($part, ':');
                if ($colonPos !== false) {
                    $b['special_requests'][] = [
                        'type' => substr($part, 0, $colonPos),
                        'text' => substr($part, $colonPos + 1),
                    ];
                }
            }
        }
    }
    unset($b);

    // Get all rooms for creation form assignment
    $roomStmt = $db->prepare("SELECT id, room_number, room_type FROM rooms WHERE property_id = ? ORDER BY room_number");
    $roomStmt->execute([$propertyId]);
    $allRooms = $roomStmt->fetchAll();

    // Handle Creating New Booking
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_booking') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $checkIn = $_POST['check_in_date'] ?? '';
        $checkOut = $_POST['check_out_date'] ?? '';
        $roomId = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
        $status = $_POST['status'] ?? 'pending';
        $notes = trim($_POST['notes'] ?? '');

        if (empty($firstName) || empty($lastName) || empty($email) || empty($checkIn) || empty($checkOut) || $roomId <= 0) {
            header("Location: index.php?route=reservations&error=Missing required fields.");
            exit;
        }

        $db->beginTransaction();
        try {
            // Find or create guest
            $guestStmt = $db->prepare("SELECT id FROM guests WHERE email = ? LIMIT 1");
            $guestStmt->execute([$email]);
            $guestId = $guestStmt->fetchColumn();

            if (!$guestId) {
                $insertGuest = $db->prepare("INSERT INTO guests (first_name, last_name, email, phone) VALUES (?, ?, ?, ?)");
                $insertGuest->execute([$firstName, $lastName, $email, $phone]);
                $guestId = $db->lastInsertId();
            }

            // Insert Booking
            $insertBooking = $db->prepare("
                INSERT INTO bookings (property_id, room_id, guest_id, check_in_date, check_out_date, status, source, notes)
                VALUES (?, ?, ?, ?, ?, ?, 'walk_in', ?)
            ");
            $insertBooking->execute([$propertyId, $roomId, $guestId, $checkIn, $checkOut, $status, $notes]);

            $db->commit();
            header("Location: index.php?route=reservations&success=Booking created successfully.");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            header("Location: index.php?route=reservations&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    // Handle Cancelling a Reservation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }

        $bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
        
        $db->beginTransaction();
        try {
            // Get booking details
            $bookingQuery = $db->prepare("SELECT room_id, status FROM bookings WHERE id = ? AND property_id = ?");
            $bookingQuery->execute([$bookingId, $propertyId]);
            $booking = $bookingQuery->fetch();

            if ($booking) {
                // Update booking status
                $updateBooking = $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
                $updateBooking->execute([$bookingId]);

                // Release room if it was occupied/reserved
                if (in_array($booking['status'], ['confirmed', 'checked_in'])) {
                    $updateRoom = $db->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
                    $updateRoom->execute([$booking['room_id']]);
                }
            }

            $db->commit();
            header("Location: index.php?route=reservations&success=Booking cancelled successfully.");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            header("Location: index.php?route=reservations&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    // Handle Confirming a Waitlisted booking
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_waitlist') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }

        $bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
        $assignRoomId = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;

        if ($bookingId <= 0 || $assignRoomId <= 0) {
            header("Location: index.php?route=reservations&error=Must assign a room.");
            exit;
        }

        $db->beginTransaction();
        try {
            // Update booking status and room ID
            $stmt = $db->prepare("UPDATE bookings SET room_id = ?, status = 'confirmed' WHERE id = ? AND property_id = ?");
            $stmt->execute([$assignRoomId, $bookingId, $propertyId]);

            $db->commit();
            header("Location: index.php?route=reservations&success=Booking confirmed from waitlist.");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            header("Location: index.php?route=reservations&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    // Render reservations view
    include __DIR__ . '/../../app/Views/staff/front-office/reservations.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Check-In Flow
// ----------------------------------------------------
elseif ($route === 'check-in') {
    $bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

    if ($bookingId <= 0) {
        header("Location: index.php?route=reservations&error=Invalid check-in request.");
        exit;
    }

    // Fetch booking details
    $stmt = $db->prepare("
        SELECT b.*, r.room_number, r.room_type, g.first_name, g.last_name, g.email, g.phone, g.id as guest_id, g.id_proof_path
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        LEFT JOIN guests g ON b.guest_id = g.id
        WHERE b.id = ? AND b.property_id = ?
    ");
    $stmt->execute([$bookingId, $propertyId]);
    $booking = $stmt->fetch();

    if (!$booking || in_array($booking['status'], ['checked_in', 'checked_out', 'cancelled'])) {
        header("Location: index.php?route=reservations&error=Booking not eligible for check-in.");
        exit;
    }

    // Fetch all available clean rooms (rooms where rooms.status = 'available' AND latest housekeeping status is 'clean')
    // Note: We also include the currently assigned room in the dropdown list even if it is not flagged clean/available, so that the current selection is visible.
    $roomStmt = $db->prepare("
        SELECT r.*, rsl.status AS housekeeping_status
        FROM rooms r
        LEFT JOIN room_status_log rsl ON rsl.id = (
            SELECT id FROM room_status_log
            WHERE room_id = r.id
            ORDER BY changed_at DESC, id DESC
            LIMIT 1
        )
        WHERE r.property_id = ? 
          AND (
            (r.status = 'available' AND rsl.status = 'clean') 
            OR r.id = ?
          )
        ORDER BY r.room_number ASC
    ");
    $roomStmt->execute([$propertyId, $booking['room_id']]);
    $availableCleanRooms = $roomStmt->fetchAll();

    // Handle Form Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }

        $assignedRoomId = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
        $paymentAmount = isset($_POST['payment_amount']) ? (float)$_POST['payment_amount'] : 0.0;
        $paymentMethod = $_POST['payment_method'] ?? 'cash';
        $paymentRef = trim($_POST['payment_ref'] ?? '');

        if ($assignedRoomId <= 0) {
            header("Location: index.php?route=check-in&booking_id={$bookingId}&error=You must assign a room.");
            exit;
        }

        $db->beginTransaction();
        try {
            // 1. Process ID proof file upload — validate, then encrypt before writing to disk
            $idProofPath = $booking['id_proof_path'];
            if (isset($_FILES['id_proof']) && $_FILES['id_proof']['error'] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['id_proof']['tmp_name'];
                $originalName = basename($_FILES['id_proof']['name']);
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];

                // Validate file extension
                if (!in_array($ext, $allowedExts, true)) {
                    throw new Exception("Invalid ID proof file type: .$ext. Allowed: " . implode(', ', $allowedExts));
                }

                // Enforce file size cap (10 MB)
                $maxBytes = 10 * 1024 * 1024;
                if ($_FILES['id_proof']['size'] > $maxBytes) {
                    throw new Exception("ID proof file exceeds the 10 MB maximum size.");
                }

                // Validate MIME type of upload content
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $detectedMime = finfo_file($finfo, $tmpName);
                finfo_close($finfo);
                $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
                if (!in_array($detectedMime, $allowedMimes, true)) {
                    throw new Exception("Invalid ID proof content type: $detectedMime. Only images and PDFs are accepted.");
                }

                $newFilename = 'guest_' . $booking['guest_id'] . '_' . time() . '.' . $ext;
                $targetDir = __DIR__ . '/../../storage/ids/';
                $targetFile = $targetDir . $newFilename;

                $encKey = $env['encryption_key'] ?? '';
                if (strlen($encKey) < 16) {
                    throw new Exception("Encryption key not configured for ID document storage.");
                }

                IdEncryption::encryptFile($tmpName, $targetFile, $encKey);
                $idProofPath = $targetFile;

                $updateGuest = $db->prepare("UPDATE guests SET id_proof_path = ? WHERE id = ?");
                $updateGuest->execute([$idProofPath, $booking['guest_id']]);
            }

            // 2. Perform payment transaction if any payment was submitted
            if ($paymentAmount > 0) {
                $insertPayment = $db->prepare("
                    INSERT INTO payments (booking_id, amount, currency, method, status, transaction_reference, paid_at)
                    VALUES (?, ?, 'USD', ?, 'completed', ?, NOW())
                ");
                $insertPayment->execute([$bookingId, $paymentAmount, $paymentMethod, $paymentRef]);
            }

            // 3. Complete check-in: update booking status & room assignment
            $updateBooking = $db->prepare("
                UPDATE bookings 
                SET room_id = ?, status = 'checked_in' 
                WHERE id = ?
            ");
            $updateBooking->execute([$assignedRoomId, $bookingId]);

            // 4. Update the room status in the rooms table to occupied
            $updateRoom = $db->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
            $updateRoom->execute([$assignedRoomId]);

            $db->commit();
            header("Location: index.php?route=reservations&success=Guest checked in successfully.");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            header("Location: index.php?route=check-in&booking_id={$bookingId}&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    // Render check-in view
    include __DIR__ . '/../../app/Views/staff/front-office/check-in.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Express Check-Out (zero balance — one click)
// ----------------------------------------------------
elseif ($route === 'express-check-out') {
    $bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

    if ($bookingId <= 0) {
        header("Location: index.php?route=reservations&error=Invalid check-out request.");
        exit;
    }

    // Fetch booking details
    $stmt = $db->prepare("
        SELECT b.*, r.room_number, r.room_type, r.base_rate, g.first_name, g.last_name, g.email, g.phone, g.id as guest_id
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        LEFT JOIN guests g ON b.guest_id = g.id
        WHERE b.id = ? AND b.property_id = ?
    ");
    $stmt->execute([$bookingId, $propertyId]);
    $booking = $stmt->fetch();

    if (!$booking || $booking['status'] !== 'checked_in') {
        header("Location: index.php?route=reservations&error=Booking not checked in.");
        exit;
    }

    // Invoice Calculations
    $checkIn = new DateTime($booking['check_in_date']);
    $checkOut = new DateTime($booking['check_out_date']);
    $nights = $checkIn->diff($checkOut)->days;
    if ($nights <= 0) {
        $nights = 1;
    }

    $roomCharges = $nights * (float)$booking['base_rate'];

    // Get booking addons
    $addonStmt = $db->prepare("SELECT * FROM booking_addons WHERE booking_id = ?");
    $addonStmt->execute([$bookingId]);
    $addons = $addonStmt->fetchAll();

    $addonsCharges = 0.0;
    foreach ($addons as $addon) {
        $addonsCharges += (float)$addon['price'] * (int)$addon['quantity'];
    }

    $totalCharges = $roomCharges + $addonsCharges;

    // Fetch payments
    $paymentStmt = $db->prepare("SELECT * FROM payments WHERE booking_id = ? AND status = 'completed'");
    $paymentStmt->execute([$bookingId]);
    $payments = $paymentStmt->fetchAll();

    $paidAmount = 0.0;
    foreach ($payments as $payment) {
        $paidAmount += (float)$payment['amount'];
    }

    $outstandingBalance = $totalCharges - $paidAmount;

    // Only allow express if balance is truly zero
    if ($outstandingBalance > 0) {
        header("Location: index.php?route=check-out&booking_id={$bookingId}&error=Booking has an outstanding balance. Use the standard check-out flow.");
        exit;
    }

    // Generate e-bill
    $billService = new \App\Services\BillService();
    try {
        $billResult = $billService->generateAndSave($bookingId);
    } catch (Exception $e) {
        Logger::error('Express check-out bill generation failed', ['booking_id' => $bookingId, 'exception' => $e->getMessage()]);
        $billResult = null;
    }

    $db->beginTransaction();
    try {
        // 1. Complete check-out: update booking status
        $updateBooking = $db->prepare("UPDATE bookings SET status = 'checked_out' WHERE id = ?");
        $updateBooking->execute([$bookingId]);

        // 2. Free the room
        $updateRoom = $db->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
        $updateRoom->execute([$booking['room_id']]);

        // 3. Log housekeeping
        $logHousekeeping = $db->prepare("
            INSERT INTO room_status_log (room_id, status, changed_by, changed_at, notes)
            VALUES (?, 'dirty', ?, NOW(), ?)
        ");
        $checkoutNote = "Dirty after express checkout (Booking #" . $bookingId . ")";
        $logHousekeeping->execute([$booking['room_id'], $_SESSION['user_id'], $checkoutNote]);

        // 4. Award loyalty points
        try {
            $loyaltyService->earnPoints($bookingId, $totalCharges, "Stay completed at " . $booking['room_type']);
        } catch (Exception $e) {
            Logger::error('Loyalty points accrual failed', ['booking_id' => $bookingId, 'exception' => $e->getMessage()]);
        }

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        header("Location: index.php?route=reservations&error=" . urlencode($e->getMessage()));
        exit;
    }

    // Send e-bill via email (outside transaction — non-critical)
    if ($billResult && !empty($booking['email'])) {
        try {
            $mailer = new \App\Services\MailService();
            $guestName = trim(($booking['first_name'] ?? '') . ' ' . ($booking['last_name'] ?? ''));
            $mailer->send(
                $booking['email'],
                'Your E-Bill from ' . ($env['app_name'] ?? 'ZyroInn'),
                $billResult['html']
            );
        } catch (Exception $e) {
            Logger::error('Express check-out email failed', ['booking_id' => $bookingId, 'exception' => $e->getMessage()]);
        }
    }

    header("Location: index.php?route=reservations&success=Express check-out complete. E-bill generated" . ($booking['email'] ? ' and emailed to ' . htmlspecialchars($booking['email']) : '') . ".");
    exit;
}

// ----------------------------------------------------
// ROUTE: Check-Out Flow
// ----------------------------------------------------
elseif ($route === 'check-out') {
    $bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

    if ($bookingId <= 0) {
        header("Location: index.php?route=reservations&error=Invalid check-out request.");
        exit;
    }

    // Fetch booking details
    $stmt = $db->prepare("
        SELECT b.*, r.room_number, r.room_type, r.base_rate, g.first_name, g.last_name, g.email, g.phone, g.id as guest_id
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        LEFT JOIN guests g ON b.guest_id = g.id
        WHERE b.id = ? AND b.property_id = ?
    ");
    $stmt->execute([$bookingId, $propertyId]);
    $booking = $stmt->fetch();

    if (!$booking || $booking['status'] !== 'checked_in') {
        header("Location: index.php?route=reservations&error=Booking not checked in.");
        exit;
    }

    // Invoice Calculations
    $checkIn = new DateTime($booking['check_in_date']);
    $checkOut = new DateTime($booking['check_out_date']);
    $nights = $checkIn->diff($checkOut)->days;
    if ($nights <= 0) {
        $nights = 1; // Minimum 1 night charge
    }

    $roomCharges = $nights * (float)$booking['base_rate'];

    // Get booking addons
    $addonStmt = $db->prepare("SELECT * FROM booking_addons WHERE booking_id = ?");
    $addonStmt->execute([$bookingId]);
    $addons = $addonStmt->fetchAll();

    $addonsCharges = 0.0;
    foreach ($addons as $addon) {
        $addonsCharges += (float)$addon['price'] * (int)$addon['quantity'];
    }

    $totalCharges = $roomCharges + $addonsCharges;

    // Fetch payments
    $paymentStmt = $db->prepare("SELECT * FROM payments WHERE booking_id = ? AND status = 'completed'");
    $paymentStmt->execute([$bookingId]);
    $payments = $paymentStmt->fetchAll();

    $paidAmount = 0.0;
    foreach ($payments as $payment) {
        $paidAmount += (float)$payment['amount'];
    }

    $outstandingBalance = $totalCharges - $paidAmount;

    // Handle Form Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }

        $paymentAmount = isset($_POST['payment_amount']) ? (float)$_POST['payment_amount'] : 0.0;
        $paymentMethod = $_POST['payment_method'] ?? 'cash';
        $paymentRef = trim($_POST['payment_ref'] ?? '');

        $db->beginTransaction();
        try {
            // 1. Process payment if payment entered
            if ($paymentAmount > 0) {
                $insertPayment = $db->prepare("
                    INSERT INTO payments (booking_id, amount, currency, method, status, transaction_reference, paid_at)
                    VALUES (?, ?, 'USD', ?, 'completed', ?, NOW())
                ");
                $insertPayment->execute([$bookingId, $paymentAmount, $paymentMethod, $paymentRef]);
            }

            // 2. Complete check-out: update booking status
            $updateBooking = $db->prepare("UPDATE bookings SET status = 'checked_out' WHERE id = ?");
            $updateBooking->execute([$bookingId]);

            // 3. Free the room: update rooms status to available
            $updateRoom = $db->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
            $updateRoom->execute([$booking['room_id']]);

            // 4. Log housekeeping status change to DIRTY
            $logHousekeeping = $db->prepare("
                INSERT INTO room_status_log (room_id, status, changed_by, changed_at, notes)
                VALUES (?, 'dirty', ?, NOW(), ?)
            ");
            $checkoutNote = "Dirty after guest checkout (Booking #" . $bookingId . ")";
            $logHousekeeping->execute([$booking['room_id'], $_SESSION['user_id'], $checkoutNote]);

            // 5. Award loyalty points for completed stay
            try {
                $loyaltyService->earnPoints($bookingId, $totalCharges, "Stay completed at " . $booking['room_type']);
            } catch (Exception $e) {
                // Log loyalty error but don't fail the checkout
                Logger::error('Loyalty points accrual failed', ['booking_id' => $bookingId, 'exception' => $e->getMessage()]);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            header("Location: index.php?route=check-out&booking_id={$bookingId}&error=" . urlencode($e->getMessage()));
            exit;
        }

        // Generate e-bill + email (outside transaction — non-critical)
        $billService = new \App\Services\BillService();
        try {
            $billResult = $billService->generateAndSave($bookingId);
            if (!empty($booking['email'])) {
                $mailer = new \App\Services\MailService();
                $guestName = trim(($booking['first_name'] ?? '') . ' ' . ($booking['last_name'] ?? ''));
                $mailer->send(
                    $booking['email'],
                    'Your E-Bill from ' . ($env['app_name'] ?? 'ZyroInn'),
                    $billResult['html']
                );
            }
        } catch (Exception $e) {
            Logger::error('Standard check-out bill/email failed', ['booking_id' => $bookingId, 'exception' => $e->getMessage()]);
        }

        header("Location: index.php?route=reservations&success=Guest checked out successfully. E-bill generated" . (!empty($booking['email']) ? ' and emailed.' : '') . ".");
        exit;
    }

    // Render check-out view
    include __DIR__ . '/../../app/Views/staff/front-office/check-out.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Housekeeping Tasks
// ----------------------------------------------------
elseif ($route === 'housekeeping-tasks') {
    // Handle status update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_task_status') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }
        $taskId = (int)($_POST['task_id'] ?? 0);
        $status = $_POST['status'] ?? 'open';
        $completedAt = ($status === 'completed') ? date('Y-m-d H:i:s') : null;
        
        $stmt = $db->prepare("UPDATE tasks SET status = ?, completed_at = ? WHERE id = ? AND assigned_to = ?");
        $stmt->execute([$status, $completedAt, $taskId, $_SESSION['user_id']]);
        
        header("Location: index.php?route=housekeeping-tasks&success=Task status updated.");
        exit;
    }

    // Fetch tasks assigned to the current user
    $stmt = $db->prepare("
        SELECT t.*, r.room_number
        FROM tasks t
        LEFT JOIN rooms r ON t.related_room_id = r.id
        WHERE t.assigned_to = ?
        ORDER BY CASE t.status WHEN 'open' THEN 1 WHEN 'in_progress' THEN 2 WHEN 'completed' THEN 3 ELSE 4 END ASC, t.due_date ASC, t.id DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $myTasks = $stmt->fetchAll();

    include __DIR__ . '/../../app/Views/staff/housekeeping/my-tasks.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Lost & Found
// ----------------------------------------------------
elseif ($route === 'lost-and-found') {
    // Handle logging new found item
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_lost_found') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }
        $itemName = trim($_POST['item_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $foundAt = !empty($_POST['found_at']) ? $_POST['found_at'] : date('Y-m-d H:i:s');
        $roomId = isset($_POST['room_id']) && $_POST['room_id'] !== '' ? (int)$_POST['room_id'] : null;
        $notes = trim($_POST['notes'] ?? '');

        if (empty($itemName)) {
            header("Location: index.php?route=lost-and-found&error=Item name is required.");
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO lost_and_found (item_name, description, found_at, room_id, handled_by, status, notes)
            VALUES (?, ?, ?, ?, ?, 'found', ?)
        ");
        $stmt->execute([$itemName, $description, $foundAt, $roomId, $_SESSION['user_id'], $notes]);
        header("Location: index.php?route=lost-and-found&success=Found item logged successfully.");
        exit;
    }

    // Handle status change
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_lost_found_status') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }
        $itemId = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'found';
        $notes = trim($_POST['notes'] ?? '');

        $stmt = $db->prepare("UPDATE lost_and_found SET status = ?, notes = CONCAT(COALESCE(notes, ''), '\n[', NOW(), '] ', ?) WHERE id = ?");
        $stmt->execute([$status, $notes, $itemId]);
        header("Location: index.php?route=lost-and-found&success=Item status updated.");
        exit;
    }

    // Fetch lost and found items
    $stmt = $db->prepare("
        SELECT lf.*, r.room_number, u.first_name, u.last_name
        FROM lost_and_found lf
        LEFT JOIN rooms r ON lf.room_id = r.id
        LEFT JOIN users u ON lf.handled_by = u.id
        ORDER BY lf.status = 'found' DESC, lf.found_at DESC, lf.id DESC
    ");
    $stmt->execute();
    $items = $stmt->fetchAll();

    // Get rooms for select dropdown
    $roomStmt = $db->prepare("SELECT id, room_number FROM rooms WHERE property_id = ? ORDER BY room_number");
    $roomStmt->execute([$propertyId]);
    $allRooms = $roomStmt->fetchAll();

    include __DIR__ . '/../../app/Views/staff/housekeeping/lost-and-found.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Linen & Amenity Inventory
// ----------------------------------------------------
elseif ($route === 'housekeeping-inventory') {
    // Handle quantity adjust
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_inventory_qty') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }
        $inventoryId = (int)($_POST['id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 0);

        $stmt = $db->prepare("UPDATE linen_amenity_inventory SET quantity = ? WHERE id = ? AND property_id = ?");
        $stmt->execute([$qty, $inventoryId, $propertyId]);
        header("Location: index.php?route=housekeeping-inventory&success=Inventory quantity updated.");
        exit;
    }

    // Handle create new inventory item
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'log_inventory_item') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }
        $itemName = trim($_POST['item_name'] ?? '');
        $category = $_POST['category'] ?? 'other';
        $qty = (int)($_POST['quantity'] ?? 0);
        $minRequired = (int)($_POST['min_required'] ?? 0);

        if (empty($itemName)) {
            header("Location: index.php?route=housekeeping-inventory&error=Item name is required.");
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO linen_amenity_inventory (property_id, item_name, category, quantity, min_required)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$propertyId, $itemName, $category, $qty, $minRequired]);
        header("Location: index.php?route=housekeeping-inventory&success=Inventory item added.");
        exit;
    }

    // Fetch inventory items
    $stmt = $db->prepare("SELECT * FROM linen_amenity_inventory WHERE property_id = ? ORDER BY category, item_name");
    $stmt->execute([$propertyId]);
    $inventory = $stmt->fetchAll();

    include __DIR__ . '/../../app/Views/staff/housekeeping/inventory.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Maintenance Issue Queue
// ----------------------------------------------------
elseif ($route === 'maintenance-queue') {
    // Handle logging new issue
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_maintenance_order') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }
        $roomId = (int)($_POST['room_id'] ?? 0);
        $description = trim($_POST['issue_description'] ?? '');
        $priority = $_POST['priority'] ?? 'normal';

        if ($roomId <= 0 || empty($description)) {
            header("Location: index.php?route=maintenance-queue&error=Room and description are required.");
            exit;
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                INSERT INTO maintenance_orders (room_id, created_by, issue_description, priority, status)
                VALUES (?, ?, ?, ?, 'open')
            ");
            $stmt->execute([$roomId, $_SESSION['user_id'], $description, $priority]);

            // If urgent, automatically flag the room status as out_of_order
            if ($priority === 'urgent') {
                $logStmt = $db->prepare("
                    INSERT INTO room_status_log (room_id, status, changed_by, notes)
                    VALUES (?, 'out_of_order', ?, 'Auto-flagged due to urgent maintenance log.')
                ");
                $logStmt->execute([$roomId, $_SESSION['user_id']]);

                $roomStmt = $db->prepare("UPDATE rooms SET status = 'maintenance' WHERE id = ?");
                $roomStmt->execute([$roomId]);
            }

            $db->commit();
            header("Location: index.php?route=maintenance-queue&success=Maintenance order logged.");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            header("Location: index.php?route=maintenance-queue&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    // Fetch maintenance orders
    $stmt = $db->prepare("
        SELECT mo.*, r.room_number, v.name AS vendor_name, u.first_name, u.last_name
        FROM maintenance_orders mo
        JOIN rooms r ON mo.room_id = r.id
        LEFT JOIN vendors v ON mo.vendor_id = v.id
        LEFT JOIN users u ON mo.created_by = u.id
        WHERE r.property_id = ?
        ORDER BY CASE mo.status WHEN 'open' THEN 1 WHEN 'in_progress' THEN 2 WHEN 'resolved' THEN 3 ELSE 4 END ASC, mo.priority = 'urgent' DESC, mo.created_at DESC
    ");
    $stmt->execute([$propertyId]);
    $orders = $stmt->fetchAll();

    // Get rooms for logging dropdown
    $roomStmt = $db->prepare("SELECT id, room_number FROM rooms WHERE property_id = ? ORDER BY room_number");
    $roomStmt->execute([$propertyId]);
    $allRooms = $roomStmt->fetchAll();

    include __DIR__ . '/../../app/Views/staff/maintenance/queue.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Work Order Detail Page
// ----------------------------------------------------
elseif ($route === 'maintenance-detail') {
    $orderId = (int)($_GET['id'] ?? 0);

    // Handle updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_maintenance_order') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }
        $vendorId = isset($_POST['vendor_id']) && $_POST['vendor_id'] !== '' ? (int)$_POST['vendor_id'] : null;
        $status = $_POST['status'] ?? 'open';
        $notes = trim($_POST['notes'] ?? '');
        $scheduledAt = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null;
        $resolvedAt = ($status === 'resolved') ? date('Y-m-d H:i:s') : null;

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                UPDATE maintenance_orders 
                SET vendor_id = ?, status = ?, notes = ?, scheduled_at = ?, resolved_at = ?
                WHERE id = ?
            ");
            $stmt->execute([$vendorId, $status, $notes, $scheduledAt, $resolvedAt, $orderId]);

            // If resolving work order, reset room status back to available
            if ($status === 'resolved') {
                $roomStmt = $db->prepare("SELECT room_id FROM maintenance_orders WHERE id = ?");
                $roomStmt->execute([$orderId]);
                $roomId = (int)$roomStmt->fetchColumn();

                // Log room as pending inspection
                $logStmt = $db->prepare("
                    INSERT INTO room_status_log (room_id, status, changed_by, notes)
                    VALUES (?, 'inspect', ?, 'Maintenance resolved. Pending inspection.')
                ");
                $logStmt->execute([$roomId, $_SESSION['user_id']]);

                // Set room status available
                $roomUpdate = $db->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
                $roomUpdate->execute([$roomId]);
            }

            $db->commit();
            header("Location: index.php?route=maintenance-detail&id={$orderId}&success=Work order updated.");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            header("Location: index.php?route=maintenance-detail&id={$orderId}&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    // Fetch work order details
    $stmt = $db->prepare("
        SELECT mo.*, r.room_number, r.room_type, u.first_name, u.last_name
        FROM maintenance_orders mo
        JOIN rooms r ON mo.room_id = r.id
        LEFT JOIN users u ON mo.created_by = u.id
        WHERE mo.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        header("Location: index.php?route=maintenance-queue&error=Work order not found.");
        exit;
    }

    // Get active vendors for assignment
    $vendorStmt = $db->prepare("SELECT id, name, contact_name FROM vendors WHERE active = 1 ORDER BY name");
    $vendorStmt->execute();
    $vendors = $vendorStmt->fetchAll();

    include __DIR__ . '/../../app/Views/staff/maintenance/detail.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Preventive Maintenance Calendar
// ----------------------------------------------------
elseif ($route === 'preventive-maintenance') {
    // Handle scheduling
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'schedule_preventive_maintenance') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }
        $roomId = (int)($_POST['room_id'] ?? 0);
        $vendorId = isset($_POST['vendor_id']) && $_POST['vendor_id'] !== '' ? (int)$_POST['vendor_id'] : null;
        $description = trim($_POST['description'] ?? '');
        $scheduledAt = $_POST['scheduled_at'] ?? '';

        if ($roomId <= 0 || empty($description) || empty($scheduledAt)) {
            header("Location: index.php?route=preventive-maintenance&error=Missing required details.");
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO maintenance_orders (room_id, created_by, vendor_id, issue_description, priority, status, scheduled_at)
            VALUES (?, ?, ?, ?, 'normal', 'open', ?)
        ");
        $stmt->execute([$roomId, $_SESSION['user_id'], $vendorId, $description, $scheduledAt]);
        header("Location: index.php?route=preventive-maintenance&success=Preventive maintenance scheduled successfully.");
        exit;
    }

    // Fetch scheduled orders
    $stmt = $db->prepare("
        SELECT mo.*, r.room_number, v.name AS vendor_name
        FROM maintenance_orders mo
        JOIN rooms r ON mo.room_id = r.id
        LEFT JOIN vendors v ON mo.vendor_id = v.id
        WHERE r.property_id = ? AND mo.scheduled_at IS NOT NULL
        ORDER BY mo.scheduled_at ASC
    ");
    $stmt->execute([$propertyId]);
    $scheduledOrders = $stmt->fetchAll();

    // Rooms
    $roomStmt = $db->prepare("SELECT id, room_number FROM rooms WHERE property_id = ? ORDER BY room_number");
    $roomStmt->execute([$propertyId]);
    $allRooms = $roomStmt->fetchAll();

    // Active vendors
    $vendorStmt = $db->prepare("SELECT id, name FROM vendors WHERE active = 1 ORDER BY name");
    $vendorStmt->execute();
    $vendors = $vendorStmt->fetchAll();

    include __DIR__ . '/../../app/Views/staff/maintenance/calendar.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Vendor Directory (CRUD)
// ----------------------------------------------------
elseif ($route === 'vendors') {
    // Handle create vendor
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_vendor') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }
        $name = trim($_POST['name'] ?? '');
        $contactName = trim($_POST['contact_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($name)) {
            header("Location: index.php?route=vendors&error=Vendor name is required.");
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO vendors (name, contact_name, email, phone, address, active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$name, $contactName, $email, $phone, $address]);
        header("Location: index.php?route=vendors&success=Vendor added.");
        exit;
    }

    // Handle update vendor
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_vendor') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }
        $vendorId = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $contactName = trim($_POST['contact_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;

        if ($vendorId <= 0 || empty($name)) {
            header("Location: index.php?route=vendors&error=Invalid vendor update.");
            exit;
        }

        $stmt = $db->prepare("
            UPDATE vendors 
            SET name = ?, contact_name = ?, email = ?, phone = ?, address = ?, active = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $contactName, $email, $phone, $address, $active, $vendorId]);
        header("Location: index.php?route=vendors&success=Vendor updated.");
        exit;
    }

    // Fetch vendors
    $stmt = $db->prepare("SELECT * FROM vendors ORDER BY name");
    $stmt->execute();
    $vendors = $stmt->fetchAll();

    include __DIR__ . '/../../app/Views/staff/maintenance/vendors.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: F&B Order Queue
// ----------------------------------------------------
elseif ($route === 'fnb-orders') {
    // Handle order creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_fnb_order') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }
        $orderType = $_POST['order_type'] ?? 'restaurant';
        $bookingId = isset($_POST['booking_id']) && $_POST['booking_id'] !== '' ? (int)$_POST['booking_id'] : null;
        $tableNumber = isset($_POST['table_number']) ? trim($_POST['table_number']) : null;
        $rawScheduled = isset($_POST['scheduled_at']) && $_POST['scheduled_at'] !== '' ? trim($_POST['scheduled_at']) : null;
        $scheduledAt = $rawScheduled;
        if ($scheduledAt !== null) {
            $scheduledAt = str_replace('T', ' ', $scheduledAt);
            if (strlen($scheduledAt) === 16) $scheduledAt .= ':00';
        }
        $notes = trim($_POST['notes'] ?? '');
        $items = $_POST['items'] ?? []; // array of menu_item_id => quantity

        if ($orderType === 'room_service' && empty($bookingId)) {
            header("Location: index.php?route=fnb-orders&error=Booking is required for room service.");
            exit;
        }
        if ($orderType === 'restaurant' && empty($tableNumber)) {
            header("Location: index.php?route=fnb-orders&error=Table number is required for restaurant order.");
            exit;
        }
        if ($orderType === 'service' && empty($scheduledAt)) {
            header("Location: index.php?route=fnb-orders&error=Scheduled time is required for service bookings.");
            exit;
        }

        $db->beginTransaction();
        try {
            // Capacity check for service orders (inside transaction with FOR UPDATE)
            if ($orderType === 'service' && $scheduledAt !== null) {
                foreach ($items as $itemId => $qty) {
                    $qty = (int)$qty;
                    if ($qty <= 0) continue;

                    $svcStmt = $db->prepare("
                        SELECT max_capacity, duration_minutes, name FROM fnb_menu_items
                        WHERE id = ? AND property_id = ? AND category IN ('spa','wellness','fitness','activity','transport')
                        FOR UPDATE
                    ");
                    $svcStmt->execute([$itemId, $propertyId]);
                    $svcItem = $svcStmt->fetch();

                    if (!$svcItem) continue; // not a capacity-limited service item
                    $maxCap = (int)$svcItem['max_capacity'];
                    if ($maxCap <= 0) continue;

                    $svcDuration = (int)$svcItem['duration_minutes'] ?: 60;
                    $svcEnd = date('Y-m-d H:i:s', strtotime($scheduledAt) + $svcDuration * 60);

                    $capStmt = $db->prepare("
                        SELECT COALESCE(SUM(foi.quantity), 0)
                        FROM fnb_orders fo
                        JOIN fnb_order_items foi ON foi.order_id = fo.id
                        WHERE fo.property_id = ?
                          AND fo.order_type = 'service'
                          AND fo.status != 'cancelled'
                          AND fo.scheduled_at < ?
                          AND DATE_ADD(fo.scheduled_at, INTERVAL COALESCE(fo.duration_minutes, 60) MINUTE) > ?
                          AND foi.menu_item_id = ?
                    ");
                    $capStmt->execute([$propertyId, $svcEnd, $scheduledAt, $itemId]);
                    $booked = (int)$capStmt->fetchColumn();

                    if (($booked + $qty) > $maxCap) {
                        $db->rollBack();
                        header("Location: index.php?route=fnb-orders&error=" . urlencode($svcItem['name'] . ' is fully booked at this time slot.'));
                        exit;
                    }
                }
            }

            $stmt = $db->prepare("
                INSERT INTO fnb_orders (property_id, booking_id, table_number, order_type, scheduled_at, status, notes, total_amount)
                VALUES (?, ?, ?, ?, ?, 'pending', ?, 0.00)
            ");
            $stmt->execute([$propertyId, $bookingId, $tableNumber, $orderType, $scheduledAt, $notes]);
            $orderId = $db->lastInsertId();

            $totalAmount = 0.00;
            foreach ($items as $itemId => $qty) {
                $qty = (int)$qty;
                if ($qty <= 0) continue;

                $priceStmt = $db->prepare("SELECT price FROM fnb_menu_items WHERE id = ? AND property_id = ?");
                $priceStmt->execute([$itemId, $propertyId]);
                $price = (float)$priceStmt->fetchColumn();

                $itemStmt = $db->prepare("
                    INSERT INTO fnb_order_items (order_id, menu_item_id, quantity, price)
                    VALUES (?, ?, ?, ?)
                ");
                $itemStmt->execute([$orderId, $itemId, $qty, $price]);
                $totalAmount += $price * $qty;
            }

            $updateStmt = $db->prepare("UPDATE fnb_orders SET total_amount = ? WHERE id = ?");
            $updateStmt->execute([$totalAmount, $orderId]);

            $db->commit();
            header("Location: index.php?route=fnb-orders&success=F&B order placed successfully.");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            header("Location: index.php?route=fnb-orders&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    // Handle order status change
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order_status') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }
        $orderId = (int)($_POST['order_id'] ?? 0);
        $status = $_POST['status'] ?? 'pending';

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE fnb_orders SET status = ? WHERE id = ? AND property_id = ?");
            $stmt->execute([$status, $orderId, $propertyId]);

            // Add room service or service booking cost to booking addons if delivered
            if ($status === 'delivered') {
                $orderStmt = $db->prepare("SELECT booking_id, total_amount, notes, order_type FROM fnb_orders WHERE id = ?");
                $orderStmt->execute([$orderId]);
                $fnbOrder = $orderStmt->fetch();

                if ($fnbOrder && $fnbOrder['booking_id']) {
                    $prefix = $fnbOrder['order_type'] === 'service' ? 'Service Booking' : 'Room Service';
                    $addonName = $prefix . " (Order #" . $orderId . ")";
                    
                    $checkStmt = $db->prepare("SELECT 1 FROM booking_addons WHERE booking_id = ? AND name = ?");
                    $checkStmt->execute([$fnbOrder['booking_id'], $addonName]);
                    
                    if (!$checkStmt->fetch()) {
                        $addonStmt = $db->prepare("
                            INSERT INTO booking_addons (booking_id, name, description, quantity, price)
                            VALUES (?, ?, ?, 1, ?)
                        ");
                        $addonStmt->execute([$fnbOrder['booking_id'], $addonName, $fnbOrder['notes'], $fnbOrder['total_amount']]);
                    }
                }
            }

            $db->commit();
            header("Location: index.php?route=fnb-orders&success=Order status updated.");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            header("Location: index.php?route=fnb-orders&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    // Fetch order list with filters
    $typeFilter = $_GET['order_type'] ?? '';
    $statusFilter = $_GET['status'] ?? '';

    $query = "
        SELECT fo.*, r.room_number, g.first_name, g.last_name
        FROM fnb_orders fo
        LEFT JOIN bookings b ON fo.booking_id = b.id
        LEFT JOIN rooms r ON b.room_id = r.id
        LEFT JOIN guests g ON b.guest_id = g.id
        WHERE fo.property_id = ?
    ";
    $params = [$propertyId];

    if ($typeFilter !== '') {
        $query .= " AND fo.order_type = ?";
        $params[] = $typeFilter;
    }
    if ($statusFilter !== '') {
        $query .= " AND fo.status = ?";
        $params[] = $statusFilter;
    }

    $query .= " ORDER BY CASE fo.status WHEN 'pending' THEN 1 WHEN 'preparing' THEN 2 WHEN 'delivered' THEN 3 ELSE 4 END ASC, fo.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    // Fetch items for each order
    foreach ($orders as &$order) {
        $itemStmt = $db->prepare("
            SELECT foi.*, fmi.name AS item_name
            FROM fnb_order_items foi
            JOIN fnb_menu_items fmi ON foi.menu_item_id = fmi.id
            WHERE foi.order_id = ?
        ");
        $itemStmt->execute([$order['id']]);
        $order['items'] = $itemStmt->fetchAll();
    }
    unset($order);

    // Fetch checked-in bookings for Room Service order creator dropdown
    $bookingStmt = $db->prepare("
        SELECT b.id, r.room_number, g.first_name, g.last_name
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN guests g ON b.guest_id = g.id
        WHERE b.property_id = ? AND b.status = 'checked_in'
        ORDER BY r.room_number
    ");
    $bookingStmt->execute([$propertyId]);
    $activeBookings = $bookingStmt->fetchAll();

    // Fetch available menu items
    $menuStmt = $db->prepare("SELECT id, name, price, category FROM fnb_menu_items WHERE property_id = ? AND available = 1 ORDER BY category, name");
    $menuStmt->execute([$propertyId]);
    $availableMenu = $menuStmt->fetchAll();

    include __DIR__ . '/../../app/Views/staff/fnb/orders.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: F&B Menu Editor
// ----------------------------------------------------
elseif ($route === 'fnb-menu') {
    // Handle create item
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_menu_item') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0.00);
        $category = $_POST['category'] ?? 'other';
        $available = isset($_POST['available']) ? 1 : 0;
        $durationMinutes = isset($_POST['duration_minutes']) && $_POST['duration_minutes'] !== '' ? (int)$_POST['duration_minutes'] : null;

        if (empty($name) || $price < 0) {
            header("Location: index.php?route=fnb-menu&error=Valid name and price are required.");
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO fnb_menu_items (property_id, name, description, price, duration_minutes, category, available)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$propertyId, $name, $description, $price, $durationMinutes, $category, $available]);
        header("Location: index.php?route=fnb-menu&success=Menu item added.");
        exit;
    }

    // Handle update item
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_menu_item') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }
        $itemId = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0.00);
        $category = $_POST['category'] ?? 'other';
        $available = isset($_POST['available']) ? 1 : 0;
        $durationMinutes = isset($_POST['duration_minutes']) && $_POST['duration_minutes'] !== '' ? (int)$_POST['duration_minutes'] : null;

        if ($itemId <= 0 || empty($name) || $price < 0) {
            header("Location: index.php?route=fnb-menu&error=Valid details required.");
            exit;
        }

        $stmt = $db->prepare("
            UPDATE fnb_menu_items 
            SET name = ?, description = ?, price = ?, duration_minutes = ?, category = ?, available = ?
            WHERE id = ? AND property_id = ?
        ");
        $stmt->execute([$name, $description, $price, $durationMinutes, $category, $available, $itemId, $propertyId]);
        header("Location: index.php?route=fnb-menu&success=Menu item updated.");
        exit;
    }

    // Handle delete item
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_menu_item') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }
        $itemId = (int)($_POST['id'] ?? 0);

        $stmt = $db->prepare("DELETE FROM fnb_menu_items WHERE id = ? AND property_id = ?");
        $stmt->execute([$itemId, $propertyId]);
        header("Location: index.php?route=fnb-menu&success=Menu item deleted.");
        exit;
    }

    // Fetch menu items
    $stmt = $db->prepare("SELECT * FROM fnb_menu_items WHERE property_id = ? ORDER BY category, name");
    $stmt->execute([$propertyId]);
    $menuItems = $stmt->fetchAll();

    include __DIR__ . '/../../app/Views/staff/fnb/menu.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: F&B Stock & Waste Log
// ----------------------------------------------------
elseif ($route === 'fnb-stock-waste') {
    // Handle logging
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'log_stock_waste') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }
        $itemName = trim($_POST['item_name'] ?? '');
        $type = $_POST['type'] ?? 'stock_in';
        $quantity = (float)($_POST['quantity'] ?? 0);
        $unit = trim($_POST['unit'] ?? '');
        $reason = trim($_POST['reason'] ?? '');

        if (empty($itemName) || $quantity <= 0 || empty($unit)) {
            header("Location: index.php?route=fnb-stock-waste&error=Valid details required.");
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO fnb_stock_waste_log (property_id, item_name, type, quantity, unit, reason, logged_by, logged_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$propertyId, $itemName, $type, $quantity, $unit, $reason, $_SESSION['user_id']]);
        header("Location: index.php?route=fnb-stock-waste&success=Inventory change logged.");
        exit;
    }

    // Fetch log entries
    $stmt = $db->prepare("
        SELECT swl.*, u.first_name, u.last_name
        FROM fnb_stock_waste_log swl
        LEFT JOIN users u ON swl.logged_by = u.id
        WHERE swl.property_id = ?
        ORDER BY swl.logged_at DESC, swl.id DESC
    ");
    $stmt->execute([$propertyId]);
    $logs = $stmt->fetchAll();

    include __DIR__ . '/../../app/Views/staff/fnb/stock-waste.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Front Office Guests Profiles List
// ----------------------------------------------------
elseif ($route === 'guests') {
    $search = $_GET['search'] ?? '';
    
    $query = "SELECT * FROM guests";
    $params = [];
    
    if ($search !== '') {
        $query .= " WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?";
        $searchParam = "%{$search}%";
        $params = [$searchParam, $searchParam, $searchParam, $searchParam];
    }
    
    $query .= " ORDER BY last_name ASC, first_name ASC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $guests = $stmt->fetchAll();

    include __DIR__ . '/../../app/Views/staff/front-office/guests.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Front Office More Actions & Dashboard
// ----------------------------------------------------
elseif ($route === 'more') {
    // Handle shift handover
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_handover_note') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }
        $note = trim($_POST['note'] ?? '');
        $shiftType = $_POST['shift_type'] ?? 'morning';

        if (empty($note)) {
            header("Location: index.php?route=more&error=Note details required.");
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO shift_handover_notes (property_id, created_by, note, shift_date, shift_type)
            VALUES (?, ?, ?, CURDATE(), ?)
        ");
        $stmt->execute([$propertyId, $_SESSION['user_id'], $note, $shiftType]);
        header("Location: index.php?route=more&success=Shift handover note logged.");
        exit;
    }

    // Handle incident reporting
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_incident_report') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }
        $type = trim($_POST['incident_type'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($type) || empty($description)) {
            header("Location: index.php?route=more&error=Incident type and description are required.");
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO incident_reports (reported_by, incident_type, description, status)
            VALUES (?, ?, ?, 'open')
        ");
        $stmt->execute([$_SESSION['user_id'], $type, $description]);
        header("Location: index.php?route=more&success=Security incident report filed.");
        exit;
    }

    // Fetch shift handover notes
    $handoverStmt = $db->prepare("
        SELECT shn.*, u.first_name, u.last_name
        FROM shift_handover_notes shn
        LEFT JOIN users u ON shn.created_by = u.id
        WHERE shn.property_id = ?
        ORDER BY shn.shift_date DESC, shn.created_at DESC
    ");
    $handoverStmt->execute([$propertyId]);
    $handoverNotes = $handoverStmt->fetchAll();

    // Fetch incident reports
    $incidentStmt = $db->prepare("
        SELECT ir.*, u.first_name, u.last_name, r.room_number
        FROM incident_reports ir
        LEFT JOIN users u ON ir.reported_by = u.id
        LEFT JOIN rooms r ON ir.room_id = r.id
        ORDER BY ir.created_at DESC
    ");
    $incidentStmt->execute();
    $incidentReports = $incidentStmt->fetchAll();

    include __DIR__ . '/../../app/Views/staff/front-office/more.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Guest Messaging (Concierge)
// ----------------------------------------------------
elseif ($route === 'messaging') {
    $msgService = new MessageService();
    $conversations = [];
    $activeConversation = null;
    $messages = [];

    // Handle POST: staff reply
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }
        $convId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
        $message = trim($_POST['message'] ?? '');
        if ($convId > 0 && $message !== '') {
            $msgService->sendMessage($convId, 'staff', (int)$_SESSION['user_id'], $message);
        }
        header("Location: index.php?route=messaging" . ($convId > 0 ? "&conversation_id=$convId" : ""));
        exit;
    }

    $conversations = $msgService->getConversationsForStaff($propertyId);
    $convId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;

    if ($convId > 0) {
        foreach ($conversations as $conv) {
            if ((int)$conv['id'] === $convId) {
                $activeConversation = $conv;
                break;
            }
        }
        if ($activeConversation) {
            $messages = $msgService->getMessages($convId);
            $msgService->markAsRead($convId, 'guest');
        }
    }

    $pageTitle = 'Guest Messages';
    include __DIR__ . '/../../app/Views/staff/front-office/messaging.php';
    exit;
}

// ROUTE: Logout
// ----------------------------------------------------
elseif ($route === 'logout') {
    \App\Services\AuthService::logout();
    header("Location: " . BASE_URL . "/auth/login.php?surface=staff");
    exit;
}

// 404 Fallback
http_response_code(404);
echo "404 Not Found";
exit;
