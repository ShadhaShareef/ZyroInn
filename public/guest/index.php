<?php
// index.php - Guest Surface Front Controller
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Services\AmenityService;
use App\Services\ReviewService;
use App\Services\LoyaltyService;
use App\Services\AuthService;
use App\Services\Database;
use App\Services\Logger;
use App\Services\BookingService;
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

$db = Database::getConnection();
$amenityService = new AmenityService();
$reviewService = new ReviewService();
$loyaltyService = new LoyaltyService();
$bookingService = new BookingService();

// CSRF token initialization
AuthService::generateCsrfToken();

// Dev-mode auto-login
if ($devMode) {
    if (empty($_SESSION['guest_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'guest') {
        $stmt = $db->prepare("SELECT * FROM guests WHERE email = 'maya.patel@example.com' LIMIT 1");
        $stmt->execute();
        $guest = $stmt->fetch();
        if ($guest) {
            $userId = !empty($guest['user_id']) ? (int)$guest['user_id'] : null;
            if (!$userId) {
                $userStmt = $db->prepare("SELECT id FROM users WHERE email = 'maya.patel@example.com' LIMIT 1");
                $userStmt->execute();
                $userId = $userStmt->fetchColumn();
                if (!$userId) {
                    $insertUser = $db->prepare("INSERT INTO users (role, first_name, last_name, email, status) VALUES ('guest', 'Maya', 'Patel', 'maya.patel@example.com', 'active')");
                    $insertUser->execute();
                    $userId = $db->lastInsertId();
                }
                $updateGuest = $db->prepare("UPDATE guests SET user_id = ? WHERE id = ?");
                $updateGuest->execute([$userId, $guest['id']]);
            }
            $_SESSION['user_id'] = $userId;
            $_SESSION['role'] = 'guest';
            $_SESSION['guest_id'] = (int)$guest['id'];
            $_SESSION['first_name'] = $guest['first_name'];
            $_SESSION['last_name'] = $guest['last_name'];
            $_SESSION['email'] = $guest['email'];
        } else {
            die("Error: Seeded guest Maya Patel not found. Run database/seeds/seeds.sql first.");
        }
    }
}

// Ensure guest_id is loaded for authenticated guests
if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'guest' && empty($_SESSION['guest_id'])) {
    $gStmt = $db->prepare("SELECT id FROM guests WHERE user_id = ? LIMIT 1");
    $gStmt->execute([$_SESSION['user_id']]);
    $gid = $gStmt->fetchColumn();
    if ($gid) {
        $_SESSION['guest_id'] = (int)$gid;
    }
}

// Global Nav setup for bottom tab bar
$route = $_GET['route'] ?? 'home';

$navItems = [
    [
        'label' => 'Explore',
        'icon' => 'E',
        'href' => 'index.php?route=home',
        'active' => in_array($route, ['home', 'search', 'property', 'room'])
    ],
    [
        'label' => 'Bookings',
        'icon' => 'B',
        'href' => 'index.php?route=bookings',
        'active' => ($route === 'bookings' || $route === 'book' || $route === 'confirm')
    ],
    [
        'label' => 'Messages',
        'icon' => 'M',
        'href' => 'index.php?route=messages',
        'active' => ($route === 'messages')
    ],
    [
        'label' => 'Profile',
        'icon' => 'P',
        'href' => 'index.php?route=profile',
        'active' => ($route === 'profile')
    ]
];

// ----------------------------------------------------
// ROUTE: Home / Destination Discovery
// ----------------------------------------------------
if ($route === 'home') {
    try {
        // Fetch unique active property cities
        $stmt = $db->query("SELECT DISTINCT city, state FROM properties WHERE status = 'active' ORDER BY city");
        $destinations = $stmt->fetchAll();

        // Fetch featured properties
        $propStmt = $db->query("SELECT * FROM properties WHERE status = 'active' LIMIT 3");
        $properties = $propStmt->fetchAll();
    } catch (\PDOException $e) {
        Logger::error('Home page query failed', ['exception' => $e->getMessage()]);
        $destinations = [];
        $properties = [];
    }

    foreach ($properties as &$property) {
        $propertyId = (int)$property['id'];
        try {
            $property['enabled_amenities'] = $amenityService->getEnabledAmenitiesForProperty($propertyId);
        } catch (\Exception $e) {
            $property['enabled_amenities'] = [];
        }
        try {
            $roomStmt = $db->prepare("SELECT MIN(base_rate) FROM rooms WHERE property_id = ? AND status = 'available'");
            $roomStmt->execute([$propertyId]);
            $property['starting_rate'] = $roomStmt->fetchColumn() ?: 0;
        } catch (\PDOException $e) {
            $property['starting_rate'] = 0;
        }
    }

    include __DIR__ . '/../../app/Views/guest/index.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Search Results
// ----------------------------------------------------
elseif ($route === 'search') {
    // 1. Inputs
    $location = $_GET['location'] ?? '';
    $priceMin = isset($_GET['price_min']) && $_GET['price_min'] !== '' ? (float)$_GET['price_min'] : 0.0;
    $priceMax = isset($_GET['price_max']) && $_GET['price_max'] !== '' ? (float)$_GET['price_max'] : 9999.0;
    $selectedRoomTypes = $_GET['room_type'] ?? [];
    $selectedAmenities = $_GET['amenities'] ?? [];
    $selectedPropertyTypes = $_GET['property_type'] ?? [];
    $sort = $_GET['sort'] ?? 'recommended';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page <= 0) $page = 1;
    $isAjax = isset($_GET['ajax']) && $_GET['ajax'] == 1;

    // Date-based availability
    $checkIn = $_GET['check_in'] ?? '';
    $checkOut = $_GET['check_out'] ?? '';
    $guestsCount = isset($_GET['guests']) ? (int)$_GET['guests'] : 0;

    // 2. Fetch properties
    $propQuery = "SELECT * FROM properties WHERE status = 'active'";
    $propParams = [];
    if (!empty($location)) {
        $propQuery .= " AND city = ?";
        $propParams[] = $location;
    }
    if (!empty($selectedPropertyTypes)) {
        $placeholders = implode(',', array_fill(0, count($selectedPropertyTypes), '?'));
        $propQuery .= " AND property_type IN ($placeholders)";
        $propParams = array_merge($propParams, $selectedPropertyTypes);
    }
    $stmt = $db->prepare($propQuery);
    $stmt->execute($propParams);
    $allProperties = $stmt->fetchAll();

    $filteredProperties = [];

    // 3. Loop and Filter
    foreach ($allProperties as $property) {
        $propertyId = (int)$property['id'];
        $property['enabled_amenities'] = $amenityService->getEnabledAmenitiesForProperty($propertyId);
        
        // Flatten property amenities keys
        $propAmenityKeys = [];
        foreach ($property['enabled_amenities'] as $cat => $items) {
            foreach ($items as $item) {
                $propAmenityKeys[] = $item['key'];
            }
        }

        // Fetch rooms (with optional date overlap filter)
        $roomSql = "SELECT * FROM rooms WHERE property_id = ? AND status = 'available'";
        $roomParams = [$propertyId];

        if (!empty($checkIn) && !empty($checkOut)) {
            $roomSql .= " AND id NOT IN (
                SELECT room_id FROM bookings
                WHERE status IN ('confirmed', 'checked_in', 'waitlisted')
                  AND check_in_date < ?
                  AND check_out_date > ?
            )";
            $roomParams[] = $checkOut;
            $roomParams[] = $checkIn;
        }

        if ($guestsCount > 0) {
            $roomSql .= " AND occupancy >= ?";
            $roomParams[] = $guestsCount;
        }

        $roomStmt = $db->prepare($roomSql);
        $roomStmt->execute($roomParams);
        $rooms = $roomStmt->fetchAll();
        $matchingRooms = [];

        foreach ($rooms as $room) {
            $roomId = (int)$room['id'];
            $room['enabled_amenities'] = $amenityService->getEnabledAmenitiesForRoom($roomId);
            $roomAmenityKeys = array_column($room['enabled_amenities'], 'key');

            // Filter: Price
            if ($room['base_rate'] < $priceMin || $room['base_rate'] > $priceMax) {
                continue;
            }

            // Filter: Room Type (using room-level amenities)
            if (!empty($selectedRoomTypes)) {
                $hasType = false;
                foreach ($selectedRoomTypes as $typeKey) {
                    if (in_array($typeKey, $roomAmenityKeys)) {
                        $hasType = true;
                        break;
                    }
                }
                if (!$hasType) {
                    continue;
                }
            }

            // Filter: Amenities (room must have selected room-level amenities)
            if (!empty($selectedAmenities)) {
                $hasAllAmenities = true;
                foreach ($selectedAmenities as $amenityKey) {
                    // Check if either room has it OR property has it
                    if (!in_array($amenityKey, $roomAmenityKeys) && !in_array($amenityKey, $propAmenityKeys)) {
                        $hasAllAmenities = false;
                        break;
                    }
                }
                if (!$hasAllAmenities) {
                    continue;
                }
            }

            $matchingRooms[] = $room;
        }

        // Only include property if it has rooms matching criteria
        if (!empty($matchingRooms)) {
            $property['rooms'] = $matchingRooms;
            $filteredProperties[] = $property;
        }
    }

    // 4. Sort properties
    if ($sort === 'price_asc') {
        foreach ($filteredProperties as &$prop) {
            usort($prop['rooms'], function($a, $b) {
                return $a['base_rate'] <=> $b['base_rate'];
            });
        }
        unset($prop);
        usort($filteredProperties, function($a, $b) {
            $rateA = !empty($a['rooms']) ? $a['rooms'][0]['base_rate'] : 0;
            $rateB = !empty($b['rooms']) ? $b['rooms'][0]['base_rate'] : 0;
            return $rateA <=> $rateB;
        });
    } elseif ($sort === 'price_desc') {
        foreach ($filteredProperties as &$prop) {
            usort($prop['rooms'], function($a, $b) {
                return $b['base_rate'] <=> $a['base_rate'];
            });
        }
        unset($prop);
        usort($filteredProperties, function($a, $b) {
            $rateA = !empty($a['rooms']) ? $a['rooms'][0]['base_rate'] : 0;
            $rateB = !empty($b['rooms']) ? $b['rooms'][0]['base_rate'] : 0;
            return $rateB <=> $rateA;
        });
    } elseif ($sort === 'occupancy_desc') {
        foreach ($filteredProperties as &$prop) {
            usort($prop['rooms'], function($a, $b) {
                return $b['occupancy'] <=> $a['occupancy'];
            });
        }
        unset($prop);
        usort($filteredProperties, function($a, $b) {
            $occA = !empty($a['rooms']) ? $a['rooms'][0]['occupancy'] : 0;
            $occB = !empty($b['rooms']) ? $b['rooms'][0]['occupancy'] : 0;
            return $occB <=> $occA;
        });
    }

    // 5. Paginate properties (limit 1 for demonstrating infinite scroll with 2 seeded properties)
    $limit = 1;
    $totalProperties = count($filteredProperties);
    $totalPages = ceil($totalProperties / $limit);
    $offset = ($page - 1) * $limit;
    $properties = array_slice($filteredProperties, $offset, $limit);

    // Ensure starting_rate is set on each property for map markers
    $allMapProperties = [];
    foreach ($filteredProperties as $prop) {
        $prop['starting_rate'] = !empty($prop['rooms']) ? $prop['rooms'][0]['base_rate'] : 0;
        $allMapProperties[] = $prop;
    }

    // If AJAX request, render list items and exit
    if ($isAjax) {
        if (empty($properties)) {
            // End of scroll
            exit;
        }
        foreach ($properties as $property) {
            include __DIR__ . '/../../app/Views/guest/search_item_partial.php';
        }
        exit;
    }

    // Google Maps API key from env or config
    $googleMapsApiKey = getenv('GOOGLE_MAPS_API_KEY') ?: '';

    // Pass all properties (not just paginated) for map markers
    $mapProperties = $allMapProperties;

    include __DIR__ . '/../../app/Views/guest/search.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Shareable Property Summary (public, printable)
// ----------------------------------------------------
elseif ($route === 'property-summary') {
    $propertyId = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;
    if ($propertyId <= 0) {
        http_response_code(400);
        die("Missing property_id.");
    }

    $stmt = $db->prepare("SELECT * FROM properties WHERE id = ? AND status = 'active'");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch();

    if (!$property) {
        http_response_code(404);
        die("Property not found.");
    }

    $property['enabled_amenities'] = $amenityService->getEnabledAmenitiesForProperty($propertyId);

    $roomStmt = $db->prepare("SELECT * FROM rooms WHERE property_id = ? AND status = 'available' ORDER BY base_rate ASC");
    $roomStmt->execute([$propertyId]);
    $property['rooms'] = $roomStmt->fetchAll();

    foreach ($property['rooms'] as &$room) {
        $roomId = (int)$room['id'];
        $room['enabled_amenities'] = $amenityService->getEnabledAmenitiesForRoom($roomId);
    }
    unset($room);

    include __DIR__ . '/../../app/Views/guest/property-summary.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Property Detail
// ----------------------------------------------------
elseif ($route === 'property') {
    $propertyId = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;
    if ($propertyId <= 0) {
        header("Location: index.php?route=home");
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM properties WHERE id = ? AND status = 'active'");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch();

    if (!$property) {
        http_response_code(404);
        die("Property not found.");
    }

    $property['enabled_amenities'] = $amenityService->getEnabledAmenitiesForProperty($propertyId);

    // Date passthrough from search
    $checkIn = $_GET['check_in'] ?? '';
    $checkOut = $_GET['check_out'] ?? '';
    $guestsCount = isset($_GET['guests']) ? (int)$_GET['guests'] : 0;

    // Apply date availability filter to rooms
    $roomSql = "SELECT * FROM rooms WHERE property_id = ? AND status = 'available'";
    $roomParams = [$propertyId];
    if (!empty($checkIn) && !empty($checkOut)) {
        $roomSql .= " AND id NOT IN (
            SELECT room_id FROM bookings
            WHERE status IN ('confirmed', 'checked_in', 'waitlisted')
              AND check_in_date < ?
              AND check_out_date > ?
        )";
        $roomParams[] = $checkOut;
        $roomParams[] = $checkIn;
    }
    $roomStmt = $db->prepare($roomSql);
    $roomStmt->execute($roomParams);
    $property['rooms'] = $roomStmt->fetchAll();

    foreach ($property['rooms'] as &$room) {
        $room['enabled_amenities'] = $amenityService->getEnabledAmenitiesForRoom((int)$room['id']);
    }

    // Reviews summary
    $reviewsResult = $reviewService->getReviewsForProperty($propertyId);
    $ratingSummary = $reviewService->getPropertyRatingSummary($propertyId);
    $ratingDistribution = $reviewService->getRatingDistribution($propertyId);

    // Check if current guest can review
    $canReview = false;
    $hasReviewed = false;
    if (!empty($_SESSION['guest_id'])) {
        $guestId = (int)$_SESSION['guest_id'];
        $canReview = $reviewService->canGuestReviewProperty($guestId, $propertyId);
        $hasReviewed = $reviewService->hasGuestReviewedProperty($guestId, $propertyId);
    }

    include __DIR__ . '/../../app/Views/guest/property.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Room Detail
// ----------------------------------------------------
elseif ($route === 'room') {
    $roomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
    if ($roomId <= 0) {
        header("Location: index.php?route=home");
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([$roomId]);
    $room = $stmt->fetch();

    if (!$room) {
        http_response_code(404);
        die("Room not found.");
    }

    $propStmt = $db->prepare("SELECT * FROM properties WHERE id = ?");
    $propStmt->execute([$room['property_id']]);
    $property = $propStmt->fetch();

    $room['enabled_amenities'] = $amenityService->getEnabledAmenitiesForRoom($roomId);

    $checkIn = $_GET['check_in'] ?? '';
    $checkOut = $_GET['check_out'] ?? '';
    $guestsCount = isset($_GET['guests']) ? (int)$_GET['guests'] : 0;

    include __DIR__ . '/../../app/Views/guest/room.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Booking Flow (Dates -> Guests -> Add-ons -> Payment)
// ----------------------------------------------------
elseif ($route === 'book') {
    $roomId = isset($_REQUEST['room_id']) ? (int)$_REQUEST['room_id'] : 0;
    if ($roomId <= 0) {
        header("Location: index.php?route=home");
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([$roomId]);
    $room = $stmt->fetch();

    if (!$room) {
        die("Room not found.");
    }

    $propStmt = $db->prepare("SELECT * FROM properties WHERE id = ?");
    $propStmt->execute([$room['property_id']]);
    $property = $propStmt->fetch();

    $step = $_GET['step'] ?? 'dates';

    if (empty($_SESSION['booking_draft']) || $_SESSION['booking_draft']['room_id'] !== $roomId) {
        $_SESSION['booking_draft'] = [
            'room_id' => $roomId,
            'check_in' => '',
            'check_out' => '',
            'guests_count' => 1,
            'first_name' => $_SESSION['first_name'] ?? '',
            'last_name' => $_SESSION['last_name'] ?? '',
            'email' => $_SESSION['email'] ?? '',
            'phone' => '',
            'addons' => []
        ];
    }

    // Step Processing
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF Token Verification Failed.");
        }

        if ($step === 'dates') {
            $checkIn = $_POST['check_in'] ?? '';
            $checkOut = $_POST['check_out'] ?? '';
            if (empty($checkIn) || empty($checkOut) || strtotime($checkOut) <= strtotime($checkIn)) {
                $error = "Please specify valid dates. Check-out must be after check-in.";
            } else {
                $_SESSION['booking_draft']['check_in'] = $checkIn;
                $_SESSION['booking_draft']['check_out'] = $checkOut;
                header("Location: index.php?route=book&room_id={$roomId}&property_id={$property['id']}&step=guests");
                exit;
            }
        } 
        
        elseif ($step === 'guests') {
            $guestsCount = (int)($_POST['guests_count'] ?? 1);
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if ($guestsCount > $room['occupancy']) {
                $error = "Guests count exceeds room capacity of " . $room['occupancy'] . " occupants.";
            } elseif (empty($firstName) || empty($lastName) || empty($email)) {
                $error = "Please fill in all required contact details.";
            } else {
                $_SESSION['booking_draft']['guests_count'] = $guestsCount;
                $_SESSION['booking_draft']['first_name'] = $firstName;
                $_SESSION['booking_draft']['last_name'] = $lastName;
                $_SESSION['booking_draft']['email'] = $email;
                $_SESSION['booking_draft']['phone'] = $phone;
                header("Location: index.php?route=book&room_id={$roomId}&property_id={$property['id']}&step=addons");
                exit;
            }
        } 
        
        elseif ($step === 'addons') {
            $selectedAddons = $_POST['addons'] ?? [];
            $_SESSION['booking_draft']['addons'] = $selectedAddons;
            header("Location: index.php?route=book&room_id={$roomId}&property_id={$property['id']}&step=payment");
            exit;
        } 
        
        elseif ($step === 'payment') {
            // Validate card details
            $cardholder = trim($_POST['cardholder'] ?? '');
            $cardNumber = preg_replace('/\D/', '', $_POST['cardnumber'] ?? '');
            $expiry = trim($_POST['expiry'] ?? '');
            $cvc = trim($_POST['cvc'] ?? '');

            $cardErrors = [];
            if (empty($cardholder)) {
                $cardErrors[] = 'Cardholder name is required.';
            }
            if (!preg_match('/^\d{13,19}$/', $cardNumber)) {
                $cardErrors[] = 'Invalid card number.';
            } else {
                $sum = 0;
                $alt = false;
                for ($i = strlen($cardNumber) - 1; $i >= 0; $i--) {
                    $d = (int)$cardNumber[$i];
                    if ($alt) { $d *= 2; if ($d > 9) $d -= 9; }
                    $sum += $d;
                    $alt = !$alt;
                }
                if ($sum % 10 !== 0) {
                    $cardErrors[] = 'Card number failed validation.';
                }
            }
            if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
                $cardErrors[] = 'Invalid expiry format (use MM/YY).';
            } else {
                $expParts = explode('/', $expiry);
                $expMonth = (int)$expParts[0];
                $expYear = 2000 + (int)$expParts[1];
                $expTs = mktime(23, 59, 59, $expMonth, 1, $expYear);
                if ($expTs < time()) {
                    $cardErrors[] = 'Card has expired.';
                }
            }
            if (!preg_match('/^\d{3,4}$/', $cvc)) {
                $cardErrors[] = 'Invalid CVC.';
            }

            if (!empty($cardErrors)) {
                $error = implode(' ', $cardErrors);
            } else {
            $draft = $_SESSION['booking_draft'];
            
            $db->beginTransaction();
            try {
                // Find or create guest record
                $guestStmt = $db->prepare("SELECT id, user_id FROM guests WHERE email = ? LIMIT 1");
                $guestStmt->execute([$draft['email']]);
                $guest = $guestStmt->fetch();

                if ($guest) {
                    $guestId = $guest['id'];
                    $userId = $guest['user_id'];
                } else {
                    // Create user
                    $insertUser = $db->prepare("INSERT INTO users (role, first_name, last_name, email, phone, status) VALUES ('guest', ?, ?, ?, ?, 'active')");
                    $insertUser->execute([$draft['first_name'], $draft['last_name'], $draft['email'], $draft['phone']]);
                    $userId = $db->lastInsertId();

                    // Create guest
                    $insertGuest = $db->prepare("INSERT INTO guests (user_id, first_name, last_name, email, phone) VALUES (?, ?, ?, ?, ?)");
                    $insertGuest->execute([$userId, $draft['first_name'], $draft['last_name'], $draft['email'], $draft['phone']]);
                    $guestId = $db->lastInsertId();
                }

                // Log user in
                $_SESSION['user_id'] = $userId;
                $_SESSION['guest_id'] = $guestId;
                $_SESSION['role'] = 'guest';
                $_SESSION['first_name'] = $draft['first_name'];
                $_SESSION['last_name'] = $draft['last_name'];
                $_SESSION['email'] = $draft['email'];

                // Race-condition-safe overlap check using FOR UPDATE (inside transaction)
                $hasOverlap = $bookingService->hasOverlap($roomId, $draft['check_in'], $draft['check_out']);

                $bookingStatus = $hasOverlap ? 'waitlisted' : 'confirmed';

                // Look up available alternate rooms for upgrade suggestion
                $availRooms = [];
                if ($hasOverlap) {
                    $availRooms = $bookingService->findAvailableRooms((int)$room['property_id'], $draft['check_in'], $draft['check_out'], $roomId);
                }

                // Create booking
                $bookingStmt = $db->prepare("
                    INSERT INTO bookings (property_id, room_id, guest_id, check_in_date, check_out_date, status, source)
                    VALUES (?, ?, ?, ?, ?, ?, 'direct')
                ");
                $bookingStmt->execute([
                    $room['property_id'],
                    $roomId,
                    $guestId,
                    $draft['check_in'],
                    $draft['check_out'],
                    $bookingStatus
                ]);
                $bookingId = $db->lastInsertId();

                // Compute nights
                $nights = (strtotime($draft['check_out']) - strtotime($draft['check_in'])) / 86400;
                $nights = max(1, $nights);
                $roomCharges = $nights * (float)$room['base_rate'];

                // Insert Add-ons
                $addonCharges = 0.0;
                $addonStmt = $db->prepare("SELECT * FROM addons WHERE is_active = 1");
                $addonStmt->execute();
                $allAddons = $addonStmt->fetchAll();
                $addonByKey = [];
                foreach ($allAddons as $a) {
                    $addonByKey[$a['key']] = $a;
                }

                foreach ($draft['addons'] as $addonKey) {
                    if (isset($addonByKey[$addonKey])) {
                        $addon = $addonByKey[$addonKey];
                        $quantity = match ($addon['unit']) {
                            'per_guest_night' => $nights * $draft['guests_count'],
                            'per_night' => $nights,
                            'per_guest' => $draft['guests_count'],
                            default => 1,
                        };
                        $price = (float)$addon['price'];
                        
                        $insertAddon = $db->prepare("
                            INSERT INTO booking_addons (booking_id, name, description, quantity, price)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $insertAddon->execute([
                            $bookingId,
                            $addon['name'],
                            $addon['description'],
                            $quantity,
                            $price
                        ]);

                        $addonCharges += ($price * $quantity);
                    }
                }

                $totalAmount = $roomCharges + $addonCharges;

                // Handle Loyalty Points Redemption
                $redeemedPoints = isset($_POST['redeem_points']) ? (int)$_POST['redeem_points'] : 0;
                $loyaltyDiscount = 0.0;
                if ($redeemedPoints > 0 && $guestId) {
                    try {
                        $loyaltyResult = $loyaltyService->redeemPoints($guestId, $redeemedPoints, $bookingId, 'Points redeemed at checkout');
                        $loyaltyDiscount = $loyaltyResult['dollar_value'];
                        $totalAmount = max(0, $totalAmount - $loyaltyDiscount);
                    } catch (Exception $e) {
                        // If redemption fails, continue without discount
                        Logger::error('Loyalty redemption failed', ['exception' => $e->getMessage()]);
                    }
                }

                // Create Payment
                $paymentStmt = $db->prepare("
                    INSERT INTO payments (booking_id, amount, currency, method, status, transaction_reference, paid_at)
                    VALUES (?, ?, 'USD', 'card', 'completed', ?, NOW())
                ");
                $txRef = 'MOCK_TXN_' . strtoupper(bin2hex(random_bytes(4)));
                $paymentStmt->execute([$bookingId, $totalAmount, $txRef]);

                $db->commit();
                
                // Store upgrade suggestions if waitlisted
                if ($hasOverlap) {
                    $_SESSION['upgrade_suggestions'] = $availRooms;
                }
                
                // Clear draft
                unset($_SESSION['booking_draft']);
                
                $confirmRoute = $hasOverlap ? 'waitlist-confirm' : 'confirm';
                header("Location: index.php?route={$confirmRoute}&booking_id={$bookingId}");
                exit;
            } catch (Exception $e) {
                $db->rollBack();
                $error = "Error during checkout: " . $e->getMessage();
            }
        }
    }
    }

    include __DIR__ . '/../../app/Views/guest/book.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Booking Confirmation
// ----------------------------------------------------
elseif ($route === 'confirm') {
    $bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
    if ($bookingId <= 0) {
        header("Location: index.php?route=home");
        exit;
    }

    $stmt = $db->prepare("
        SELECT b.*, r.room_number, r.room_type, r.base_rate, p.name AS property_name, p.address, p.city, p.state, p.country
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN properties p ON b.property_id = p.id
        WHERE b.id = ?
    ");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        die("Booking not found.");
    }

    // Addons
    $addonStmt = $db->prepare("SELECT * FROM booking_addons WHERE booking_id = ?");
    $addonStmt->execute([$bookingId]);
    $addons = $addonStmt->fetchAll();

    // Payments
    $payStmt = $db->prepare("SELECT * FROM payments WHERE booking_id = ?");
    $payStmt->execute([$bookingId]);
    $payments = $payStmt->fetchAll();

    include __DIR__ . '/../../app/Views/guest/confirm.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Waitlist Confirmation (overlap detected)
// ----------------------------------------------------
elseif ($route === 'waitlist-confirm') {
    $bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
    if ($bookingId <= 0) {
        header("Location: index.php?route=home");
        exit;
    }

    $stmt = $db->prepare("
        SELECT b.*, r.room_number, r.room_type, r.base_rate, p.name AS property_name, p.address, p.city, p.state, p.country
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN properties p ON b.property_id = p.id
        WHERE b.id = ?
    ");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        die("Booking not found.");
    }

    // Addons
    $addonStmt = $db->prepare("SELECT * FROM booking_addons WHERE booking_id = ?");
    $addonStmt->execute([$bookingId]);
    $addons = $addonStmt->fetchAll();

    // Payments
    $payStmt = $db->prepare("SELECT * FROM payments WHERE booking_id = ?");
    $payStmt->execute([$bookingId]);
    $payments = $payStmt->fetchAll();

    // Alternative rooms from session
    $upgradeSuggestions = $_SESSION['upgrade_suggestions'] ?? [];
    unset($_SESSION['upgrade_suggestions']);

    include __DIR__ . '/../../app/Views/guest/waitlist-confirm.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: My Bookings (Auth Required)
// ----------------------------------------------------
elseif ($route === 'bookings') {
    AuthService::requireAuth(['guest']);
    $guestId = (int)$_SESSION['guest_id'];

    $stmt = $db->prepare("
        SELECT b.*, r.room_number, r.room_type, p.name AS property_name, p.city, p.state
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN properties p ON b.property_id = p.id
        WHERE b.guest_id = ?
        ORDER BY b.check_in_date DESC
    ");
    $stmt->execute([$guestId]);
    $allBookings = $stmt->fetchAll();

    $upcomingBookings = [];
    $pastBookings = [];
    $today = date('Y-m-d');

    foreach ($allBookings as $booking) {
        if ($booking['check_out_date'] >= $today && $booking['status'] !== 'cancelled') {
            $upcomingBookings[] = $booking;
        } else {
            $pastBookings[] = $booking;
        }
    }

    include __DIR__ . '/../../app/Views/guest/bookings.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Pre-Arrival Check-In
// ----------------------------------------------------
elseif ($route === 'pre-arrival') {
    AuthService::requireAuth(['guest']);
    $guestId = (int)$_SESSION['guest_id'];
    $csrfToken = $_SESSION['csrf_token'] ?? '';

    // Find upcoming booking within check-in window (48h before check-in)
    $stmt = $db->prepare("
        SELECT b.*, r.room_number, r.room_type, p.name AS property_name
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN properties p ON b.property_id = p.id
        WHERE b.guest_id = ? AND b.status = 'confirmed'
          AND b.check_in_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND DATE_ADD(CURDATE(), INTERVAL 2 DAY)
        ORDER BY b.check_in_date ASC
        LIMIT 1
    ");
    $stmt->execute([$guestId]);
    $booking = $stmt->fetch();

    $checkInWindow = false;
    if ($booking) {
        $checkInWindow = strtotime($booking['check_in_date']) <= strtotime('+2 days') &&
                         strtotime($booking['check_in_date']) >= strtotime('-1 day');
    }

    // Fetch existing preferences and requests if any
    $preferences = [];
    $requestKeys = [];
    $customRequest = '';
    if ($booking) {
        $pStmt = $db->prepare("SELECT * FROM booking_preferences WHERE booking_id = ?");
        $pStmt->execute([$booking['id']]);
        $preferences = $pStmt->fetch() ?: [];

        $rStmt = $db->prepare("SELECT * FROM special_requests WHERE booking_id = ?");
        $rStmt->execute([$booking['id']]);
        $allRequests = $rStmt->fetchAll();
        foreach ($allRequests as $r) {
            if ($r['request_type'] === 'other') {
                $customRequest = $r['request_text'];
            } else {
                $requestKeys[] = $r['request_type'];
            }
        }
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $error = 'Security token validation failed. Please try again.';
        } else {
            $bookingId = (int)($_POST['booking_id'] ?? 0);
            $floorPref = $_POST['floor_preference'] ?? 'any';
            $bedType = $_POST['bed_type'] ?? 'any';
            $presetReqs = $_POST['preset_requests'] ?? [];
            $customReqText = trim($_POST['custom_request'] ?? '');

            if ($bookingId <= 0) {
                $error = 'Invalid booking.';
            } else {
                // Upsert preferences
                $upsertPref = $db->prepare("
                    INSERT INTO booking_preferences (booking_id, floor_preference, bed_type)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE floor_preference = VALUES(floor_preference), bed_type = VALUES(bed_type)
                ");
                $upsertPref->execute([$bookingId, $floorPref, $bedType]);

                // Delete old special requests for this booking
                $db->prepare("DELETE FROM special_requests WHERE booking_id = ?")->execute([$bookingId]);

                // Insert preset requests
                $validPresets = ['early_checkin','late_checkout','extra_towels','extra_pillows','crib','quiet_room','high_floor','connecting'];
                $insReq = $db->prepare("INSERT INTO special_requests (booking_id, request_type, request_text) VALUES (?, ?, ?)");
                foreach ($presetReqs as $key) {
                    if (in_array($key, $validPresets, true)) {
                        $insReq->execute([$bookingId, $key, $presetRequests[$key] ?? $key]);
                    }
                }

                // Insert custom request
                if ($customReqText !== '') {
                    $insReq->execute([$bookingId, 'other', $customReqText]);
                }

                // Reload data
                $pStmt = $db->prepare("SELECT * FROM booking_preferences WHERE booking_id = ?");
                $pStmt->execute([$bookingId]);
                $preferences = $pStmt->fetch() ?: [];

                $rStmt = $db->prepare("SELECT * FROM special_requests WHERE booking_id = ?");
                $rStmt->execute([$bookingId]);
                $allRequests = $rStmt->fetchAll();
                $requestKeys = [];
                $customRequest = '';
                foreach ($allRequests as $r) {
                    if ($r['request_type'] === 'other') {
                        $customRequest = $r['request_text'];
                    } else {
                        $requestKeys[] = $r['request_type'];
                    }
                }

                $success = 'Your pre-arrival preferences have been saved! Our front office team will review them.';
            }
        }
    }

    $presetRequests = [
        'early_checkin'  => 'Early check-in',
        'late_checkout'  => 'Late check-out',
        'extra_towels'   => 'Extra towels',
        'extra_pillows'  => 'Extra pillows',
        'crib'           => 'Baby crib / cot',
        'quiet_room'     => 'Quiet room request',
        'high_floor'     => 'High floor (if available)',
        'connecting'     => 'Connecting room (if available)',
    ];

    include __DIR__ . '/../../app/Views/guest/pre-arrival.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Messages (Guest-Staff Concierge)
// ----------------------------------------------------
elseif ($route === 'messages') {
    $guestId = (int)($_SESSION['guest_id'] ?? 0);
    $msgService = new MessageService();
    $conversations = [];
    $activeConversation = null;
    $messages = [];

    // Handle POST: send a new message OR start a new conversation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $guestId > 0) {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            die("CSRF validation failed");
        }
        $message = trim($_POST['message'] ?? '');
        $convId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;

        // Start a new conversation with a booking
        $newBookingId = isset($_POST['new_booking_id']) ? (int)$_POST['new_booking_id'] : 0;
        if ($newBookingId > 0 && $message !== '') {
            // Verify the booking belongs to this guest
            $stmt = $db->prepare("SELECT id FROM bookings WHERE id = ? AND guest_id = ?");
            $stmt->execute([$newBookingId, $guestId]);
            if ($stmt->fetchColumn()) {
                $convId = $msgService->getOrCreateConversation($newBookingId, $guestId, '');
                $msgService->sendMessage($convId, 'guest', $guestId, $message);
            }
        } elseif ($convId > 0 && $message !== '') {
            // Send reply to existing conversation
            $stmt = $db->prepare("SELECT id FROM conversations WHERE id = ? AND guest_id = ?");
            $stmt->execute([$convId, $guestId]);
            if ($stmt->fetchColumn()) {
                $msgService->sendMessage($convId, 'guest', $guestId, $message);
            }
        }
        $redirectConv = $convId > 0 ? $convId : 0;
        header("Location: index.php?route=messages" . ($redirectConv > 0 ? "&conversation_id=$redirectConv" : ""));
        exit;
    }

    if ($guestId > 0) {
        $conversations = $msgService->getConversationsForGuest($guestId);
        $convId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;

        if ($convId > 0) {
            // Find the conversation in the list to verify ownership
            foreach ($conversations as $conv) {
                if ((int)$conv['id'] === $convId) {
                    $activeConversation = $conv;
                    break;
                }
            }
            if ($activeConversation) {
                $messages = $msgService->getMessages($convId);
                $msgService->markAsRead($convId, 'staff');
            }
        } elseif (!empty($conversations)) {
            // Auto-select first conversation with unread messages, or just the first one
            $firstUnread = null;
            $first = null;
            foreach ($conversations as $conv) {
                if ($first === null) $first = $conv;
                if ((int)$conv['unread'] > 0) { $firstUnread = $conv; break; }
            }
            $target = $firstUnread ?? $first;
            if ($target) {
                $convId = (int)$target['id'];
                $activeConversation = $target;
                $messages = $msgService->getMessages($convId);
                $msgService->markAsRead($convId, 'staff');
            }
        }
    }

    // Fetch guest bookings for "new conversation" form
    $guestBookings = [];
    if ($guestId > 0) {
        $bkStmt = $db->prepare("
            SELECT b.id, p.name AS property_name, r.room_number, r.room_type,
                   b.check_in_date, b.check_out_date, b.status
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN properties p ON b.property_id = p.id
            WHERE b.guest_id = ? AND b.status IN ('confirmed','checked_in','checked_out')
            ORDER BY b.check_in_date DESC
        ");
        $bkStmt->execute([$guestId]);
        $guestBookings = $bkStmt->fetchAll();
    }

    include __DIR__ . '/../../app/Views/guest/messages.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Profile (Auth Required)
// ----------------------------------------------------
elseif ($route === 'profile') {
    AuthService::requireAuth(['guest']);
    $guestId = (int)$_SESSION['guest_id'];

    $stmt = $db->prepare("SELECT * FROM guests WHERE id = ?");
    $stmt->execute([$guestId]);
    $guest = $stmt->fetch();

    // Get statistics
    $bookingCountStmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE guest_id = ? AND status = 'checked_out'");
    $bookingCountStmt->execute([$guestId]);
    $completedBookings = $bookingCountStmt->fetchColumn();

    // Get loyalty account with tier info
    $loyalty = $loyaltyService->getAccountWithTier($guestId);

    // Get loyalty transactions
    $loyaltyTransactions = $loyaltyService->getTransactionHistory($guestId, 10);

    $stats = [
        'Total Stays' => $completedBookings,
        'Loyalty Points' => $loyalty['account']['points_balance'],
        'Membership' => $loyalty['tier_label']
    ];

    include __DIR__ . '/../../app/Views/guest/profile.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Switch login helper / debug
// ----------------------------------------------------
elseif ($route === 'login') {
    AuthService::requireAuth(['guest']);
    header("Location: index.php?route=profile");
    exit;
}

// ----------------------------------------------------
// ROUTE: Logout
// ----------------------------------------------------
elseif ($route === 'logout') {
    \App\Services\AuthService::logout();
    header("Location: index.php?route=home");
    exit;
}

// ----------------------------------------------------
// ROUTE: Loyalty History (Auth Required)
// ----------------------------------------------------
elseif ($route === 'loyalty-history') {
    AuthService::requireAuth(['guest']);
    $guestId = (int)$_SESSION['guest_id'];

    $stmt = $db->prepare("SELECT * FROM guests WHERE id = ?");
    $stmt->execute([$guestId]);
    $guest = $stmt->fetch();

    $loyalty = $loyaltyService->getAccountWithTier($guestId);
    $loyaltyTransactions = $loyaltyService->getTransactionHistory($guestId, 100);

    include __DIR__ . '/../../app/Views/guest/loyalty-history.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Reviews - List reviews for a property (AJAX)
// ----------------------------------------------------
elseif ($route === 'review' && ($_GET['action'] ?? '') === 'list') {
    $propertyId = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    
    if ($propertyId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid property ID']);
        exit;
    }

    $result = $reviewService->getReviewsForProperty($propertyId, $page);
    $ratingSummary = $reviewService->getPropertyRatingSummary($propertyId);
    $ratingDistribution = $reviewService->getRatingDistribution($propertyId);

    header('Content-Type: application/json');
    echo json_encode([
        'reviews' => $result['reviews'],
        'pagination' => [
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'total' => $result['total']
        ],
        'summary' => $ratingSummary,
        'distribution' => $ratingDistribution
    ]);
    exit;
}

// ----------------------------------------------------
// ROUTE: Reviews - Create Review (POST)
// ----------------------------------------------------
elseif ($route === 'review' && ($_GET['action'] ?? '') === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    AuthService::requireAuth(['guest']);
    $guestId = (int)$_SESSION['guest_id'];

    if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token validation failed']);
        exit;
    }

    $propertyId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
    $roomId = isset($_POST['room_id']) && $_POST['room_id'] !== '' ? (int)$_POST['room_id'] : null;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');

    if ($propertyId <= 0 || $rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input']);
        exit;
    }

    try {
        $reviewId = $reviewService->createReview($guestId, $propertyId, $roomId, $rating, $title ?: null, $body ?: null);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'review_id' => $reviewId]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ----------------------------------------------------
// ROUTE: Reviews - Update Review (POST)
// ----------------------------------------------------
elseif ($route === 'review' && ($_GET['action'] ?? '') === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    AuthService::requireAuth(['guest']);
    $guestId = (int)$_SESSION['guest_id'];

    if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token validation failed']);
        exit;
    }

    $reviewId = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');

    if ($reviewId <= 0 || $rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input']);
        exit;
    }

    try {
        $success = $reviewService->updateReview($reviewId, $guestId, $rating, $title ?: null, $body ?: null);
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ----------------------------------------------------
// ROUTE: Reviews - Delete Review (POST)
// ----------------------------------------------------
elseif ($route === 'review' && ($_GET['action'] ?? '') === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    AuthService::requireAuth(['guest']);
    $guestId = (int)$_SESSION['guest_id'];

    if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token validation failed']);
        exit;
    }

    $reviewId = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;

    if ($reviewId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid review ID']);
        exit;
    }

    $success = $reviewService->deleteReview($reviewId, $guestId);
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}

// ----------------------------------------------------
// ROUTE: Loyalty - Get Account Info (AJAX)
// ----------------------------------------------------
elseif ($route === 'loyalty' && ($_GET['action'] ?? '') === 'account') {
    AuthService::requireAuth(['guest']);
    $guestId = (int)$_SESSION['guest_id'];

    $account = $loyaltyService->getAccountWithTier($guestId);
    header('Content-Type: application/json');
    echo json_encode($account);
    exit;
}

// ----------------------------------------------------
// ROUTE: Loyalty - Get Transaction History (AJAX)
// ----------------------------------------------------
elseif ($route === 'loyalty' && ($_GET['action'] ?? '') === 'transactions') {
    AuthService::requireAuth(['guest']);
    $guestId = (int)$_SESSION['guest_id'];
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

    $transactions = $loyaltyService->getTransactionHistory($guestId, $limit);
    header('Content-Type: application/json');
    echo json_encode(['transactions' => $transactions]);
    exit;
}

// ----------------------------------------------------
// ROUTE: Loyalty - Redeem Points at Checkout (POST)
// ----------------------------------------------------
elseif ($route === 'loyalty' && ($_GET['action'] ?? '') === 'redeem' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    AuthService::requireAuth(['guest']);
    $guestId = (int)$_SESSION['guest_id'];

    if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token validation failed']);
        exit;
    }

    $bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
    $pointsToRedeem = isset($_POST['points']) ? (int)$_POST['points'] : 0;

    if ($bookingId <= 0 || $pointsToRedeem <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input']);
        exit;
    }

    try {
        $result = $loyaltyService->redeemPoints($guestId, $pointsToRedeem, $bookingId, 'Booking payment');
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'result' => $result]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ----------------------------------------------------
// ROUTE: Loyalty - Get Max Redeemable Points for Amount (AJAX)
// ----------------------------------------------------
elseif ($route === 'loyalty' && ($_GET['action'] ?? '') === 'max_redeem') {
    AuthService::requireAuth(['guest']);
    $guestId = (int)$_SESSION['guest_id'];
    $amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;

    if ($amount <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid amount']);
        exit;
    }

    $account = $loyaltyService->getOrCreateAccount($guestId);
    $maxRedeemable = $loyaltyService->getMaxRedeemablePoints($amount);
    $availablePoints = $account['points_balance'];
    $actualRedeemable = min($maxRedeemable, $availablePoints);

    header('Content-Type: application/json');
    echo json_encode([
        'max_redeemable_points' => $maxRedeemable,
        'available_points' => $availablePoints,
        'actual_redeemable' => $actualRedeemable,
        'dollar_value' => $loyaltyService->getPointsValue($actualRedeemable)
    ]);
    exit;
}

// ----------------------------------------------------
// ROUTE: Book Services (time-slot-based service bookings)
// ----------------------------------------------------
elseif ($route === 'services') {
    AuthService::requireAuth(['guest']);
    $guestId = (int)$_SESSION['guest_id'];
    $bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

    if ($bookingId <= 0) {
        header("Location: index.php?route=bookings&error=Invalid booking.");
        exit;
    }

    // Fetch booking (must belong to this guest)
    $stmt = $db->prepare("
        SELECT b.*, r.room_number, p.id AS property_id, p.name AS property_name
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN properties p ON b.property_id = p.id
        WHERE b.id = ? AND b.guest_id = ?
    ");
    $stmt->execute([$bookingId, $guestId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        header("Location: index.php?route=bookings&error=Booking not found.");
        exit;
    }

    $propertyId = (int)$booking['property_id'];

    // Fetch available services from fnb_menu_items (service categories)
    $serviceStmt = $db->prepare("
        SELECT id, name, description, price, duration_minutes, max_capacity, category
        FROM fnb_menu_items
        WHERE property_id = ? AND available = 1
          AND category IN ('spa', 'wellness', 'fitness', 'activity', 'transport', 'starter', 'main_course', 'dessert', 'beverage')
        ORDER BY FIELD(category, 'starter', 'main_course', 'dessert', 'beverage', 'spa', 'wellness', 'fitness', 'activity', 'transport'), name
    ");
    $serviceStmt->execute([$propertyId]);
    $services = $serviceStmt->fetchAll();

    // Group by category for display
    $groupedServices = [];
    foreach ($services as $s) {
        $groupedServices[$s['category']][] = $s;
    }

    // Handle POST - Book a service
    $bookingSuccess = null;
    $bookingError = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $bookingError = 'Security token validation failed.';
        } else {
            $serviceId = (int)($_POST['service_id'] ?? 0);
            $scheduledAt = trim($_POST['scheduled_at'] ?? '');
            $quantity = max(1, (int)($_POST['quantity'] ?? 1));
            $notes = trim($_POST['notes'] ?? '');

            // Find the selected service
            $selectedService = null;
            foreach ($services as $s) {
                if ((int)$s['id'] === $serviceId) {
                    $selectedService = $s;
                    break;
                }
            }

            if (!$selectedService) {
                $bookingError = 'Invalid service selected.';
            } elseif ($selectedService['max_capacity'] && $quantity > (int)$selectedService['max_capacity']) {
                $bookingError = 'Quantity exceeds maximum capacity of ' . (int)$selectedService['max_capacity'] . '.';
            } else {
                $fnbCategories = ['starter', 'main_course', 'dessert', 'beverage'];
                $isFnb = in_array($selectedService['category'], $fnbCategories);
                $orderType = $isFnb ? 'restaurant' : 'service';

                if (!$isFnb && (empty($scheduledAt) || !strtotime($scheduledAt))) {
                    $bookingError = 'Please provide a valid date and time.';
                } elseif ($isFnb) {
                    $scheduledAt = date('Y-m-d H:i:s');
                    $duration = 0;
                } else {
                    $scheduledAt = str_replace('T', ' ', $scheduledAt);
                    if (strlen($scheduledAt) === 16) $scheduledAt .= ':00';
                    $scheduledTimestamp = strtotime($scheduledAt);
                    $duration = (int)($selectedService['duration_minutes'] ?? 60);
                }

                if (!$bookingError) {
                    $db->beginTransaction();
                    try {
                        $totalPrice = (float)$selectedService['price'] * $quantity;
                        $orderStmt = $db->prepare("
                            INSERT INTO fnb_orders (property_id, booking_id, order_type, scheduled_at, duration_minutes, status, notes, total_amount)
                            VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)
                        ");
                        $orderStmt->execute([$propertyId, $bookingId, $orderType, $scheduledAt, $duration, $notes, $totalPrice]);
                        $orderId = $db->lastInsertId();

                        $itemStmt = $db->prepare("
                            INSERT INTO fnb_order_items (order_id, menu_item_id, quantity, price)
                            VALUES (?, ?, ?, ?)
                        ");
                        $itemStmt->execute([$orderId, $serviceId, $quantity, $selectedService['price']]);

                        $db->commit();
                        if ($isFnb) {
                            $bookingSuccess = htmlspecialchars($selectedService['name']) . ' added to your order.';
                        } else {
                            $bookingSuccess = htmlspecialchars($selectedService['name']) . ' booked for ' . date('M j, Y g:i A', $scheduledTimestamp) . '.';
                        }
                    } catch (Exception $e) {
                        if ($db->inTransaction()) {
                            $db->rollBack();
                        }
                        if (!isset($bookingError)) {
                            $bookingError = 'Error booking service: ' . $e->getMessage();
                        }
                    }
                }
            }
        }
    }

    // Also fetch any existing F&B pending/preparing orders for status display
    $existingOrders = [];
    $orderSt = $db->prepare("
        SELECT id, status, total_amount, notes, created_at
        FROM fnb_orders
        WHERE booking_id = ? AND order_type = 'room_service'
        ORDER BY created_at DESC
    ");
    $orderSt->execute([$bookingId]);
    $existingOrders = $orderSt->fetchAll();
    foreach ($existingOrders as &$eo) {
        $itemSt = $db->prepare("
            SELECT oi.*, mi.name
            FROM fnb_order_items oi
            JOIN fnb_menu_items mi ON oi.menu_item_id = mi.id
            WHERE oi.order_id = ?
        ");
        $itemSt->execute([$eo['id']]);
        $eo['items'] = $itemSt->fetchAll();
    }
    unset($eo);

    include __DIR__ . '/../../app/Views/guest/services.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Place Order (multi-item F&B, AJAX)
// ----------------------------------------------------
elseif ($route === 'place_order' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    AuthService::requireAuth(['guest']);
    $guestId = (int)$_SESSION['guest_id'];

    if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'Security token validation failed.']);
        exit;
    }

    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $propertyId = (int)($_POST['property_id'] ?? 0);
    $rawItems = json_decode($_POST['items'] ?? '[]', true);
    $notes = trim($_POST['notes'] ?? '');

    if ($bookingId <= 0 || $propertyId <= 0 || empty($rawItems)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields.']);
        exit;
    }

    // Verify booking belongs to this guest
    $stmt = $db->prepare("SELECT id FROM bookings WHERE id = ? AND guest_id = ? AND property_id = ?");
    $stmt->execute([$bookingId, $guestId, $propertyId]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Booking not found.']);
        exit;
    }

    // Look up menu items (must be available and belong to this property)
    $placeholders = implode(',', array_fill(0, count($rawItems), '?'));
    $params = $rawItems;
    $params[] = $propertyId;
    $stmt = $db->prepare("
        SELECT id, name, price, category
        FROM fnb_menu_items
        WHERE id IN ($placeholders) AND property_id = ? AND available = 1
          AND category IN ('starter', 'main_course', 'dessert', 'beverage')
    ");
    $stmt->execute($params);
    $dbItems = $stmt->fetchAll();

    if (count($dbItems) !== count($rawItems)) {
        http_response_code(400);
        echo json_encode(['error' => 'Some items are unavailable or invalid.']);
        exit;
    }

    $totalAmount = 0;
    foreach ($dbItems as $mi) {
        $totalAmount += (float)$mi['price'];
    }

    $db->beginTransaction();
    try {
        $orderStmt = $db->prepare("
            INSERT INTO fnb_orders (property_id, booking_id, order_type, scheduled_at, status, notes, total_amount)
            VALUES (?, ?, 'room_service', NOW(), 'pending', ?, ?)
        ");
        $orderStmt->execute([$propertyId, $bookingId, $notes, $totalAmount]);
        $orderId = $db->lastInsertId();

        $itemStmt = $db->prepare("
            INSERT INTO fnb_order_items (order_id, menu_item_id, quantity, price)
            VALUES (?, ?, 1, ?)
        ");
        foreach ($dbItems as $mi) {
            $itemStmt->execute([$orderId, $mi['id'], $mi['price']]);
        }

        $db->commit();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'order_id' => $orderId, 'total' => $totalAmount]);
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to place order: ' . $e->getMessage()]);
    }
    exit;
}

// ----------------------------------------------------
// ROUTE: Group Booking (multi-room, transaction-safe)
// ----------------------------------------------------
elseif ($route === 'group-book') {
    AuthService::requireAuth(['guest']);
    $guestId = (int)$_SESSION['guest_id'];
    $propertyId = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;

    if ($propertyId <= 0) {
        header("Location: index.php?route=home");
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM properties WHERE id = ? AND status = 'active'");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch();

    if (!$property) {
        header("Location: index.php?route=home");
        exit;
    }

    $roomStmt = $db->prepare("SELECT * FROM rooms WHERE property_id = ? AND status = 'available' ORDER BY base_rate ASC");
    $roomStmt->execute([$propertyId]);
    $rooms = $roomStmt->fetchAll();

    $error = null;
    $success = false;
    $bookedRooms = [];
    $failedRooms = [];
    $groupUuid = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $error = 'Security token validation failed.';
        } else {
            $selectedRoomIds = $_POST['room_ids'] ?? [];
            $checkIn = trim($_POST['check_in'] ?? '');
            $checkOut = trim($_POST['check_out'] ?? '');
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if (empty($selectedRoomIds) || !is_array($selectedRoomIds)) {
                $error = 'Please select at least one room.';
            } elseif (empty($checkIn) || empty($checkOut) || strtotime($checkOut) <= strtotime($checkIn)) {
                $error = 'Please provide valid dates. Check-out must be after check-in.';
            } elseif (empty($firstName) || empty($lastName) || empty($email)) {
                $error = 'Please fill in all contact details.';
            } else {
                $validRooms = array_filter($rooms, fn($r) => in_array((string)$r['id'], $selectedRoomIds, true));
                if (empty($validRooms)) {
                    $error = 'Invalid room selection.';
                } else {
                    $groupUuid = sprintf('%s-%s-%s-%s-%s',
                        bin2hex(random_bytes(4)),
                        bin2hex(random_bytes(2)),
                        bin2hex(random_bytes(2)),
                        bin2hex(random_bytes(2)),
                        bin2hex(random_bytes(6))
                    );

                    $db->beginTransaction();
                    try {
                        foreach ($validRooms as $room) {
                            $roomId = (int)$room['id'];

                            // Shared BookingService::hasOverlap() with FOR UPDATE — called for EVERY room
                            $hasOverlap = $bookingService->hasOverlap($roomId, $checkIn, $checkOut);

                            if ($hasOverlap) {
                                $failedRooms[] = $room['room_number'];
                                continue;
                            }

                            $insertStmt = $db->prepare("
                                INSERT INTO bookings (group_id, property_id, room_id, guest_id, check_in_date, check_out_date, status, source)
                                VALUES (?, ?, ?, ?, ?, ?, 'confirmed', 'group_booking')
                            ");
                            $insertStmt->execute([$groupUuid, $propertyId, $roomId, $guestId, $checkIn, $checkOut]);
                            $bookedRooms[] = $room['room_number'];
                        }

                        if (empty($bookedRooms)) {
                            $db->rollBack();
                            $error = empty($failedRooms) ? 'None of the selected rooms are available.' : 'All selected rooms are already booked for those dates.';
                        } else {
                            $db->commit();
                            $success = true;
                        }
                    } catch (Exception $e) {
                        $db->rollBack();
                        $error = 'Error processing group booking: ' . $e->getMessage();
                    }
                }
            }
        }
    }

    include __DIR__ . '/../../app/Views/guest/group-book.php';
    exit;
}

// ----------------------------------------------------
// ROUTE: Send E-Bill via Email (AJAX POST)
// ----------------------------------------------------
elseif ($route === 'send-bill' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    AuthService::requireAuth(['guest']);
    $guestId = (int)$_SESSION['guest_id'];
    $bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;

    if ($bookingId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid booking.']);
        exit;
    }

    $stmt = $db->prepare("
        SELECT b.*, r.room_number, r.room_type, r.base_rate, p.name AS property_name, p.address, p.city, p.state, p.country
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN properties p ON b.property_id = p.id
        WHERE b.id = ? AND b.guest_id = ?
    ");
    $stmt->execute([$bookingId, $guestId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        http_response_code(404);
        echo json_encode(['error' => 'Booking not found.']);
        exit;
    }

    $addonStmt = $db->prepare("SELECT * FROM booking_addons WHERE booking_id = ?");
    $addonStmt->execute([$bookingId]);
    $addons = $addonStmt->fetchAll();

    $payStmt = $db->prepare("SELECT * FROM payments WHERE booking_id = ?");
    $payStmt->execute([$bookingId]);
    $payments = $payStmt->fetchAll();

    // Fetch F&B orders and items
    $fnbStmt = $db->prepare("
        SELECT fo.*, foi.quantity, foi.price AS item_price, fmi.name AS item_name
        FROM fnb_orders fo
        JOIN fnb_order_items foi ON foi.order_id = fo.id
        JOIN fnb_menu_items fmi ON fmi.id = foi.menu_item_id
        WHERE fo.booking_id = ? AND fo.status != 'cancelled'
        ORDER BY fo.created_at
    ");
    $fnbStmt->execute([$bookingId]);
    $fnbItems = $fnbStmt->fetchAll();

    $nights = max(1, (strtotime($booking['check_out_date']) - strtotime($booking['check_in_date'])) / 86400);
    $roomTotal = $nights * (float)$booking['base_rate'];
    $addonTotal = array_sum(array_map(fn($a) => (float)$a['price'] * (int)$a['quantity'], $addons));
    $fnbTotal = array_sum(array_map(fn($i) => (float)$i['item_price'] * (int)$i['quantity'], $fnbItems));
    $grandTotal = $roomTotal + $addonTotal + $fnbTotal;
    $txnRef = !empty($payments) ? $payments[0]['transaction_reference'] : '--';

    $guestEmail = $_SESSION['email'] ?? '';
    if (empty($guestEmail)) {
        http_response_code(400);
        echo json_encode(['error' => 'No email address on file.']);
        exit;
    }

    ob_start();
    include __DIR__ . '/../../app/Views/guest/bill-email.php';
    $htmlBody = ob_get_clean();

    $mailer = new \App\Services\MailService();
    $success = $mailer->send(
        $guestEmail,
        'E-Bill - ZyroInn Booking #BKG-' . str_pad($bookingId, 5, '0', STR_PAD_LEFT),
        $htmlBody
    );

    header('Content-Type: application/json');
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'E-Bill sent to ' . htmlspecialchars($guestEmail)]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send email.']);
    }
    exit;
}

// ----------------------------------------------------
// ROUTE: E-Bill Download
// ----------------------------------------------------
elseif ($route === 'bill') {
    AuthService::requireAuth(['guest']);
    $guestId = (int)$_SESSION['guest_id'];
    $bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

    if ($bookingId <= 0) {
        header("Location: index.php?route=bookings&error=Invalid booking.");
        exit;
    }

    $stmt = $db->prepare("
        SELECT b.*, r.room_number, r.room_type, r.base_rate, p.name AS property_name, p.address, p.city, p.state, p.country
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN properties p ON b.property_id = p.id
        WHERE b.id = ? AND b.guest_id = ?
    ");
    $stmt->execute([$bookingId, $guestId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        header("Location: index.php?route=bookings&error=Bill not found.");
        exit;
    }

    $addonStmt = $db->prepare("SELECT * FROM booking_addons WHERE booking_id = ?");
    $addonStmt->execute([$bookingId]);
    $addons = $addonStmt->fetchAll();

    $payStmt = $db->prepare("SELECT * FROM payments WHERE booking_id = ?");
    $payStmt->execute([$bookingId]);
    $payments = $payStmt->fetchAll();

    // Fetch F&B orders and items for the bill
    $fnbOrders = [];
    $fnbStmt = $db->prepare("
        SELECT fo.*, foi.quantity, foi.price AS item_price, fmi.name AS item_name
        FROM fnb_orders fo
        JOIN fnb_order_items foi ON foi.order_id = fo.id
        JOIN fnb_menu_items fmi ON fmi.id = foi.menu_item_id
        WHERE fo.booking_id = ? AND fo.status != 'cancelled'
        ORDER BY fo.created_at
    ");
    $fnbStmt->execute([$bookingId]);
    $fnbItems = $fnbStmt->fetchAll();

    include __DIR__ . '/../../app/Views/guest/bill.php';
    exit;
}

// 404 Fallback
http_response_code(404);
echo "404 Not Found";
exit;
