<?php
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Services\AmenityService;
use App\Services\AuthService;
use App\Services\Database;
use App\Services\Logger;
use App\Services\OwnerService;

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

$ownerService = new OwnerService();

if ($devMode) {
    if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'owner') {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE role = 'owner' LIMIT 1");
            $stmt->execute();
            $owner = $stmt->fetch();
            if ($owner) {
                $_SESSION['user_id'] = $owner['id'];
                $_SESSION['role'] = $owner['role'];
                $_SESSION['first_name'] = $owner['first_name'];
                $_SESSION['last_name'] = $owner['last_name'];
                $_SESSION['property_id'] = $owner['property_id'] ?? 1;
            } else {
                http_response_code(500);
                die("Error: Seeded owner user not found in database. Run migration first.");
            }
        } catch (Exception $e) {
            http_response_code(500);
            die("Authentication error: " . $e->getMessage());
        }
    }
} else {
    AuthService::requireAuth(['owner']);
}

$route = $_GET['route'] ?? 'dashboard';
$propertyId = isset($_GET['property_id']) ? (int)$_GET['property_id'] : ((int)($_SESSION['property_id'] ?? 0));
$propertyOptions = [];

try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT id, name, status FROM properties WHERE status = 'active' ORDER BY name");
    $propertyOptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $propertyOptions = [];
}

if (!empty($propertyOptions)) {
    $availablePropertyIds = array_map('intval', array_column($propertyOptions, 'id'));
    if ($propertyId <= 0 || !in_array($propertyId, $availablePropertyIds, true)) {
        $propertyId = (int)($propertyOptions[0]['id'] ?? 0);
    }
    $_SESSION['property_id'] = $propertyId;
} elseif ($propertyId <= 0) {
    $propertyId = (int)($_SESSION['property_id'] ?? 1);
}

try {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT * FROM properties WHERE id = ?");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['name' => 'Unknown Property'];
} catch (Exception $e) {
    $property = ['name' => 'Unknown Property'];
}

// ---------- AJAX: GET ROOMS WITH RATES ----------
if ($route === 'api-rooms') {
    header('Content-Type: application/json');
    $date = $_GET['date'] ?? date('Y-m-d');
    try {
        $rooms = $ownerService->getRoomsWithRates($propertyId, $date);
        echo json_encode(['success' => true, 'rooms' => $rooms]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ---------- AJAX: GET SCHEDULES FOR DATE ----------
if ($route === 'api-schedules') {
    header('Content-Type: application/json');
    $date = $_GET['date'] ?? date('Y-m-d');
    try {
        $schedules = $ownerService->getSchedules($propertyId, $date);
        echo json_encode(['success' => true, 'schedules' => $schedules]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ---------- AJAX: GET STAFF USERS ----------
if ($route === 'api-staff') {
    header('Content-Type: application/json');
    try {
        $staff = $ownerService->getStaffUsers($propertyId);
        echo json_encode(['success' => true, 'staff' => $staff]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ---------- POST: BULK RATE APPLY ----------
if ($route === 'apply-rates' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !AuthService::verifyCsrfToken($input['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
            exit;
        }

        $roomId = (int)($input['room_id'] ?? 0);
        $startDate = $input['start_date'] ?? '';
        $endDate = $input['end_date'] ?? '';
        $rate = isset($input['rate']) && $input['rate'] !== '' ? (float)$input['rate'] : null;
        $status = $input['status'] ?? null;

        if ($roomId <= 0 || !$startDate || !$endDate) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }

        $ownerService->applyBulkRateOverride($roomId, $startDate, $endDate, $rate, $status === 'blocked' ? 'blocked' : 'available');
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ---------- POST: TOGGLE ROOM STATUS (quick block/unblock) ----------
if ($route === 'toggle-room-status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !AuthService::verifyCsrfToken($input['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
            exit;
        }

        $roomId = (int)($input['room_id'] ?? 0);
        $date = $input['date'] ?? date('Y-m-d');
        $newStatus = $input['status'] ?? 'available';

        if ($roomId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid room']);
            exit;
        }

        $roomStatus = $newStatus === 'blocked' ? 'blocked' : null;
        $ownerService->applyRateOverride($roomId, $date, null, $roomStatus);
        echo json_encode(['success' => true, 'status' => $roomStatus ?? 'available']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ---------- ROOM CRUD ----------
if ($route === 'create-room' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !AuthService::verifyCsrfToken($input['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
            exit;
        }
        $data = [
            'room_number' => trim($input['room_number'] ?? ''),
            'room_type' => trim($input['room_type'] ?? ''),
            'base_rate' => (float)($input['base_rate'] ?? 0),
            'occupancy' => (int)($input['occupancy'] ?? 1),
            'bed_count' => (int)($input['bed_count'] ?? 1),
            'ac' => !empty($input['ac']) ? 1 : 0,
            'status' => $input['status'] ?? 'available',
            'description' => trim($input['description'] ?? ''),
        ];
        if (empty($data['room_number']) || empty($data['room_type'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Room number and type are required.']);
            exit;
        }
        $result = $ownerService->createRoom($propertyId, $data);
        echo json_encode(['success' => true, 'room' => $result]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($route === 'update-room' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !AuthService::verifyCsrfToken($input['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
            exit;
        }
        $roomId = (int)($input['id'] ?? 0);
        if ($roomId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid room ID.']);
            exit;
        }
        $data = [];
        foreach (['room_number','room_type','base_rate','occupancy','bed_count','ac','status','description'] as $f) {
            if (isset($input[$f])) $data[$f] = $input[$f];
        }
        $ownerService->updateRoom($roomId, $data);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($route === 'delete-room' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !AuthService::verifyCsrfToken($input['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
            exit;
        }
        $roomId = (int)($input['id'] ?? 0);
        if ($roomId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid room ID.']);
            exit;
        }
        $ownerService->deleteRoom($roomId);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ---------- POST: SCHEDULE UPSERT ----------
if ($route === 'schedule-upsert' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !AuthService::verifyCsrfToken($input['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
            exit;
        }

        $scheduleId = isset($input['id']) ? (int)$input['id'] : null;
        $userId = (int)($input['user_id'] ?? 0);
        $date = $input['date'] ?? date('Y-m-d');
        $startTime = $input['start_time'] ?? '09:00';
        $endTime = $input['end_time'] ?? '17:00';
        $status = $input['status'] ?? 'pending';
        $notes = $input['notes'] ?? null;

        if ($userId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid staff member']);
            exit;
        }

        $id = $ownerService->upsertSchedule($propertyId, $userId, $date, $startTime, $endTime, $status, $notes, $scheduleId);
        echo json_encode(['success' => true, 'id' => $id]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ---------- POST: SCHEDULE DELETE ----------
if ($route === 'schedule-delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !AuthService::verifyCsrfToken($input['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
            exit;
        }

        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid schedule ID']);
            exit;
        }

        $deleted = $ownerService->deleteSchedule($id, $propertyId);
        echo json_encode(['success' => $deleted]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ---------- AJAX: EXPENSES ----------
if ($route === 'api-expenses' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    try {
        $expenses = $ownerService->getExpenses($propertyId);
        $summary = $ownerService->getExpenseSummary($propertyId);
        echo json_encode(['success' => true, 'expenses' => $expenses, 'summary' => $summary]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($route === 'expense-create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !AuthService::verifyCsrfToken($input['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
            exit;
        }
        $id = $ownerService->createExpense($propertyId, $input);
        echo json_encode(['success' => true, 'id' => $id]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($route === 'expense-update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !AuthService::verifyCsrfToken($input['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
            exit;
        }
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) { throw new Exception('Invalid expense ID'); }
        $ownerService->updateExpense($id, $propertyId, $input);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($route === 'expense-delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !AuthService::verifyCsrfToken($input['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
            exit;
        }
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) { throw new Exception('Invalid expense ID'); }
        $deleted = $ownerService->deleteExpense($id, $propertyId);
        echo json_encode(['success' => $deleted]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ---------- EXPORT: REPORT CSV ----------
if ($route === 'export-report') {
    $reportType = $_GET['type'] ?? 'occupancy';
    $csv = [];

    if ($reportType === 'occupancy') {
        $data = $ownerService->getOccupancyReport($propertyId, 30);
        $csv[] = ['Date', 'Occupied Rooms', 'Total Rooms', 'Occupancy %'];
        foreach ($data as $row) {
            $pct = ($row['total_rooms'] ?? 1) > 0 ? round(($row['occupied_cnt'] / $row['total_rooms']) * 100, 1) : 0;
            $csv[] = [$row['date'], (int)$row['occupied_cnt'], (int)$row['total_rooms'], $pct . '%'];
        }
    } elseif ($reportType === 'revenue') {
        $data = $ownerService->getRevenueReport($propertyId);
        $csv[] = ['Month', 'Bookings', 'Revenue'];
        foreach ($data as $row) {
            $csv[] = [$row['month'], (int)$row['bookings'], number_format((float)$row['revenue'], 2)];
        }
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $reportType . '-report-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    foreach ($csv as $line) { fputcsv($out, $line); }
    fclose($out);
    exit;
}

// ---------- VIEW ROUTES ----------
if ($route === 'dashboard' || $route === 'inventory') {
    $kpis = $ownerService->getKpis($propertyId);
    $occupancyTrend = $ownerService->getOccupancyTrend($propertyId, 7);
    $alerts = $ownerService->getAlerts($propertyId);
    $rooms = $ownerService->getRoomsWithRates($propertyId);
    $csrfToken = AuthService::generateCsrfToken();
    $dateToday = date('Y-m-d');

    extract([
        'route'           => $route,
        'property'        => $property,
        'propertyId'      => $propertyId,
        'propertyOptions' => $propertyOptions,
        'kpis'            => $kpis,
        'occupancyTrend'  => $occupancyTrend,
        'alerts'          => $alerts,
        'rooms'           => $rooms,
        'csrfToken'       => $csrfToken,
        'dateToday'       => $dateToday,
    ], EXTR_SKIP);

    $viewFile = __DIR__ . '/../../app/Views/owner/console.php';
    include $viewFile;
    exit;
}

if ($route === 'alerts') {
    $alerts = $ownerService->getAlerts($propertyId);
    extract([
        'route'           => $route,
        'property'        => $property,
        'propertyId'      => $propertyId,
        'propertyOptions' => $propertyOptions,
        'alerts'          => $alerts,
    ], EXTR_SKIP);
    include __DIR__ . '/../../app/Views/owner/alerts.php';
    exit;
}

if ($route === 'staff-scheduling') {
    $scheduleSummary = $ownerService->getScheduleSummary($propertyId);
    $staffUsers = $ownerService->getStaffUsers($propertyId);
    $csrfToken = AuthService::generateCsrfToken();
    $dateToday = date('Y-m-d');
    extract([
        'route'           => $route,
        'property'        => $property,
        'propertyId'      => $propertyId,
        'propertyOptions' => $propertyOptions,
        'scheduleSummary' => $scheduleSummary,
        'staffUsers'      => $staffUsers,
        'csrfToken'       => $csrfToken,
        'dateToday'       => $dateToday,
    ], EXTR_SKIP);
    include __DIR__ . '/../../app/Views/owner/staff-scheduling.php';
    exit;
}

if ($route === 'expenses') {
    $expenses = $ownerService->getExpenses($propertyId);
    $summary = $ownerService->getExpenseSummary($propertyId);
    $csrfToken = AuthService::generateCsrfToken();
    extract([
        'route'           => $route,
        'property'        => $property,
        'propertyId'      => $propertyId,
        'propertyOptions' => $propertyOptions,
        'expenses'        => $expenses,
        'expenseSummary'  => $summary,
        'csrfToken'       => $csrfToken,
    ], EXTR_SKIP);
    include __DIR__ . '/../../app/Views/owner/expenses.php';
    exit;
}

if ($route === 'reports') {
    $occupancyData = $ownerService->getOccupancyReport($propertyId, 30);
    $revenueData = $ownerService->getRevenueReport($propertyId);
    extract([
        'route'           => $route,
        'property'        => $property,
        'propertyId'      => $propertyId,
        'propertyOptions' => $propertyOptions,
        'occupancyData'   => $occupancyData,
        'revenueData'     => $revenueData,
    ], EXTR_SKIP);
    include __DIR__ . '/../../app/Views/owner/reports.php';
    exit;
}

if ($route === 'create-amenity') {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (!isset($input['csrf_token']) || !AuthService::verifyCsrfToken($input['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid or missing CSRF token']);
        exit;
    }
    $label = trim($input['label'] ?? '');
    $category = trim($input['category'] ?? '');
    $icon = trim($input['icon'] ?? '');
    $description = trim($input['description'] ?? '');
    if (empty($label) || empty($category)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Label and category are required.']);
        exit;
    }
    try {
        $amenityService = new AmenityService();
        $result = $amenityService->createAmenity($propertyId, $label, $category, $icon, $description);
        echo json_encode(['success' => true, 'amenity' => $result]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($route === 'delete-amenity') {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (!isset($input['csrf_token']) || !AuthService::verifyCsrfToken($input['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid or missing CSRF token']);
        exit;
    }
    $amenityKey = trim($input['amenity_key'] ?? '');
    if (empty($amenityKey)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Amenity key is required.']);
        exit;
    }
    try {
        $amenityService = new AmenityService();
        $amenityService->deleteAmenity($amenityKey);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($route === 'property-features') {
    $amenityService = new AmenityService();
    $groupedAmenities = $amenityService->getAmenitiesWithStatusForProperty($propertyId);
    $csrfToken = AuthService::generateCsrfToken();
    extract([
        'route'           => $route,
        'property'        => $property,
        'propertyId'      => $propertyId,
        'propertyOptions' => $propertyOptions,
        'groupedAmenities'=> $groupedAmenities,
        'csrfToken'       => $csrfToken,
    ], EXTR_SKIP);
    include __DIR__ . '/../../app/Views/owner/property-features.php';
    exit;
}

if ($route === 'toggle-feature') {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (!isset($input['csrf_token']) || !AuthService::verifyCsrfToken($input['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid or missing CSRF token']);
        exit;
    }
    $propertyId = isset($input['property_id']) ? (int)$input['property_id'] : null;
    $amenityKey = isset($input['amenity_key']) ? (string)$input['amenity_key'] : null;
    $enabled = isset($input['enabled']) ? (bool)$input['enabled'] : null;
    if ($propertyId === null || empty($amenityKey) || $enabled === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        exit;
    }
    if ($propertyId !== (int)$_SESSION['property_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized property action']);
        exit;
    }
    try {
        $amenityService = new AmenityService();
        $result = $amenityService->toggleAmenity($propertyId, $amenityKey, $enabled);
        echo json_encode(['success' => $result]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($route === 'property-settings') {
    $saved = false;
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die('CSRF validation failed');
        }

        $updateData = [];
        foreach (['name', 'code', 'property_type', 'address', 'city', 'state', 'country', 'postal_code', 'phone', 'email', 'description'] as $field) {
            if (isset($_POST[$field])) {
                $updateData[$field] = trim($_POST[$field]);
            }
        }

        if (!empty($updateData['name']) && !empty($updateData['code'])) {
            try {
                $ownerService->updateProperty($propertyId, $updateData);
                $saved = true;
                // Refresh property data
                $stmt = $db->prepare("SELECT * FROM properties WHERE id = ?");
                $stmt->execute([$propertyId]);
                $property = $stmt->fetch(PDO::FETCH_ASSOC) ?: $property;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'Property name and code are required.';
        }
    }

    $csrfToken = AuthService::generateCsrfToken();
    include __DIR__ . '/../../app/Views/owner/property-settings.php';
    exit;
}

if ($route === 'logout') {
    AuthService::logout();
    header("Location: " . BASE_URL . "/auth/login.php?surface=owner");
    exit;
}

http_response_code(404);
echo "404 Not Found";
exit;
