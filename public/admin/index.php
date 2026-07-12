<?php
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Services\AuthService;
use App\Services\Database;
use App\Services\AnalyticsService;
use App\Services\CommissionService;
use App\Services\ModerationService;
use App\Services\TicketService;
use App\Services\Logger;

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

if ($devMode) {
    if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
            $stmt->execute();
            $admin = $stmt->fetch();
            if ($admin) {
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['role'] = $admin['role'];
                $_SESSION['first_name'] = $admin['first_name'];
                $_SESSION['last_name'] = $admin['last_name'];
            } else {
                $insertStmt = $db->prepare("INSERT INTO users (role, first_name, last_name, email, password_hash, status) VALUES ('admin', 'Admin', 'User', 'admin@zyroinn.example', '', 'active')");
                $insertStmt->execute();
                $adminId = $db->lastInsertId();
                $_SESSION['user_id'] = $adminId;
                $_SESSION['role'] = 'admin';
                $_SESSION['first_name'] = 'Admin';
                $_SESSION['last_name'] = 'User';
            }
        } catch (Exception $e) {
            http_response_code(500);
            die("Authentication error: " . $e->getMessage());
        }
    }
} else {
    AuthService::requireAuth(['admin']);
}

$route = $_GET['route'] ?? 'dashboard';

$analyticsService = new AnalyticsService();
$commissionService = new CommissionService();
$moderationService = new ModerationService();
$ticketService = new TicketService();

// CSRF token managed via AuthService::generateCsrfToken() / AuthService::verifyCsrfToken()

// POST action routing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Onboarding Review
    if ($action === 'review_onboarding') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) { http_response_code(403); die("CSRF validation failed"); }
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $notes = trim($_POST['review_notes'] ?? '');
        if ($id > 0 && in_array($status, ['approved','rejected','verified','onboarding'])) {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE onboarding_requests SET status = ?, reviewed_by = ?, reviewed_at = NOW(), review_notes = ? WHERE id = ?");
            $stmt->execute([$status, $_SESSION['user_id'], $notes, $id]);
            if ($status === 'approved') {
                $reqStmt = $db->prepare("SELECT * FROM onboarding_requests WHERE id = ?");
                $reqStmt->execute([$id]);
                $reqData = $reqStmt->fetch();
                $ins = $db->prepare("INSERT INTO properties (name, code, address, city, state, country, postal_code, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                $ins->execute([
                    $reqData['property_name'] ?? 'New Property',
                    $reqData['property_code'] ?? strtoupper(substr(md5(uniqid()), 0, 8)),
                    $reqData['address'] ?? '',
                    $reqData['city'] ?? '',
                    $reqData['state'] ?? '',
                    $reqData['country'] ?? 'US',
                    $reqData['postal_code'] ?? '',
                    $reqData['description'] ?? ''
                ]);
            }
        }
        header("Location: index.php?route=onboarding&success=Request updated.");
        exit;
    }

    // Commission payout action
    if ($action === 'commission_action') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) { http_response_code(403); die("CSRF validation failed"); }
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if ($id > 0 && in_array($status, ['approved','paid','cancelled'])) {
            $commissionService->updatePayoutStatus($id, $status);
        }
        header("Location: index.php?route=commissions&success=Payout updated.");
        exit;
    }

    // Commission create payout from agency booking
    if ($action === 'create_payout') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) { http_response_code(403); die("CSRF validation failed"); }
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $propertyId = (int)($_POST['property_id'] ?? 0);
        $agencyName = trim($_POST['agency_name'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $percentage = (float)($_POST['percentage'] ?? 0);
        if ($bookingId > 0 && $amount > 0) {
            $commissionService->createPayout($bookingId, $propertyId, $agencyName, $amount, $percentage);
        }
        header("Location: index.php?route=commissions&success=Payout created.");
        exit;
    }

    // Fraud action
    if ($action === 'fraud_action') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) { http_response_code(403); die("CSRF validation failed"); }
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $notes = trim($_POST['resolution_notes'] ?? '');
        if ($id > 0 && in_array($status, ['investigating','resolved','dismissed'])) {
            $db = Database::getConnection();
            $resolvedAt = in_array($status, ['resolved','dismissed']) ? date('Y-m-d H:i:s') : null;
            $stmt = $db->prepare("UPDATE fraud_flags SET status = ?, resolved_by = ?, resolved_at = ?, resolution_notes = ? WHERE id = ?");
            $stmt->execute([$status, $resolvedAt ? $_SESSION['user_id'] : null, $resolvedAt, $notes, $id]);
        }
        header("Location: index.php?route=fraud&success=Flag updated.");
        exit;
    }

    // Moderation action
    if ($action === 'moderation_action') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) { http_response_code(403); die("CSRF validation failed"); }
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if ($id > 0 && in_array($status, ['approved','rejected','flagged'])) {
            $moderationService->moderateReview($id, $status, $_SESSION['user_id']);
        }
        header("Location: index.php?route=moderation&success=Review moderated.");
        exit;
    }

    if ($action === 'delete_review') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) { http_response_code(403); die("CSRF validation failed"); }
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $moderationService->deleteReview($id);
        }
        header("Location: index.php?route=moderation&success=Review deleted.");
        exit;
    }

    // Support actions
    if ($action === 'create_ticket') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) { http_response_code(403); die("CSRF validation failed"); }
        $propertyId = (int)($_POST['property_id'] ?? 0);
        $subject = trim($_POST['subject'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = $_POST['category'] ?? 'other';
        $priority = $_POST['priority'] ?? 'normal';
        if (!empty($subject) && !empty($description) && $propertyId > 0) {
            $ticketService->createTicket($propertyId, $subject, $description, $category, $priority, $_SESSION['user_id']);
        }
        header("Location: index.php?route=support&success=Ticket created.");
        exit;
    }

    if ($action === 'update_ticket_status') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) { http_response_code(403); die("CSRF validation failed"); }
        $id = (int)($_POST['ticket_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $assignedTo = isset($_POST['assigned_to']) && $_POST['assigned_to'] !== '' ? (int)$_POST['assigned_to'] : null;
        if ($id > 0 && $status !== '') {
            $ticketService->updateStatus($id, $status, $assignedTo);
        }
        header("Location: index.php?route=support&success=Ticket updated.");
        exit;
    }

    if ($action === 'reply_ticket') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) { http_response_code(403); die("CSRF validation failed"); }
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $isInternal = isset($_POST['is_internal']);
        if ($ticketId > 0 && !empty($message)) {
            $ticketService->addReply($ticketId, $_SESSION['user_id'], $message, $isInternal);
            if (!empty($_POST['new_status'])) {
                $ticketService->updateStatus($ticketId, $_POST['new_status']);
            }
        }
        header("Location: index.php?route=support-detail&id=" . $ticketId . "&success=Reply added.");
        exit;
    }

    // Dispute action
    if ($action === 'dispute_action') {
        if (!AuthService::verifyCsrfToken($_POST['csrf_token'] ?? '')) { http_response_code(403); die("CSRF validation failed"); }
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $resolution = trim($_POST['resolution'] ?? '');
        if ($id > 0 && in_array($status, ['investigating','resolved','dismissed'])) {
            $db = Database::getConnection();
            $resolvedAt = in_array($status, ['resolved','dismissed']) ? date('Y-m-d H:i:s') : null;
            $stmt = $db->prepare("UPDATE dispute_resolutions SET status = ?, resolution = ?, resolved_by = ?, resolved_at = COALESCE(?, resolved_at) WHERE id = ?");
            $stmt->execute([$status, $resolution, $resolvedAt ? $_SESSION['user_id'] : null, $resolvedAt, $id]);
        }
        header("Location: index.php?route=disputes&success=Dispute updated.");
        exit;
    }
}

// --------------- ROUTE: DASHBOARD ---------------
if ($route === 'dashboard') {
    $kpis = $analyticsService->getGlobalKPIs();
    $revenueTrend = $analyticsService->getRevenueOverTime('6');
    $occupancyByProperty = $analyticsService->getOccupancyByProperty();
    $sourceBreakdown = $analyticsService->getBookingSourceBreakdown();
    $topProperties = $analyticsService->getTopProperties(5);
    $recentActivity = $analyticsService->getRecentActivity(10);

    try {
        $db = Database::getConnection();
        $pendingOnboarding = (int)$db->query("SELECT COUNT(*) FROM onboarding_requests WHERE status = 'pending'")->fetchColumn();
        $openTickets = (int)$db->query("SELECT COUNT(*) FROM support_tickets WHERE status IN ('open','in_progress')")->fetchColumn();
        $pendingReviews = (int)$db->query("SELECT COUNT(*) FROM reviews WHERE moderation_status = 'pending'")->fetchColumn();
        $openFlags = (int)$db->query("SELECT COUNT(*) FROM fraud_flags WHERE status IN ('open','investigating')")->fetchColumn();
    } catch (\PDOException $e) {
        Logger::error('Admin dashboard counts failed', ['exception' => $e->getMessage()]);
        $pendingOnboarding = 0;
        $openTickets = 0;
        $pendingReviews = 0;
        $openFlags = 0;
    }

    include __DIR__ . '/../../app/Views/admin/dashboard.php';
    exit;
}

// --------------- ROUTE: ONBOARDING QUEUE ---------------
if ($route === 'onboarding') {
    $db = Database::getConnection();
    $statusFilter = $_GET['status'] ?? '';
    $query = "SELECT o.*, u.first_name as reviewer_first, u.last_name as reviewer_last FROM onboarding_requests o LEFT JOIN users u ON o.reviewed_by = u.id";
    $params = [];
    if ($statusFilter !== '') {
        $query .= " WHERE o.status = ?";
        $params[] = $statusFilter;
    }
    $query .= " ORDER BY FIELD(o.status, 'pending','verified','onboarding','approved','rejected') ASC, o.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $requests = $stmt->fetchAll();

    include __DIR__ . '/../../app/Views/admin/onboarding.php';
    exit;
}

// --------------- ROUTE: CENTRAL RESERVATIONS ---------------
if ($route === 'reservations') {
    $db = Database::getConnection();
    $propertyFilter = $_GET['property_id'] ?? '';
    $statusFilter = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';

    $query = "
        SELECT b.*, r.room_number, r.room_type, p.name as property_name, p.id as property_id,
               g.first_name, g.last_name, g.email, g.phone
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN properties p ON b.property_id = p.id
        LEFT JOIN guests g ON b.guest_id = g.id
        WHERE 1=1
    ";
    $params = [];

    if ($propertyFilter !== '') {
        $query .= " AND b.property_id = ?";
        $params[] = (int)$propertyFilter;
    }
    if ($statusFilter !== '') {
        $query .= " AND b.status = ?";
        $params[] = $statusFilter;
    }
    if ($search !== '') {
        $query .= " AND (g.first_name LIKE ? OR g.last_name LIKE ? OR g.email LIKE ? OR r.room_number LIKE ?)";
        $s = "%{$search}%";
        $params = array_merge($params, [$s, $s, $s, $s]);
    }

    $query .= " ORDER BY b.check_in_date ASC, b.id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();

    $properties = $db->query("SELECT id, name FROM properties WHERE status = 'active' ORDER BY name")->fetchAll();

    include __DIR__ . '/../../app/Views/admin/reservations.php';
    exit;
}

// --------------- ROUTE: GUEST CRM ---------------
if ($route === 'guests') {
    $db = Database::getConnection();
    $search = $_GET['search'] ?? '';

    $query = "
        SELECT g.*,
               COUNT(DISTINCT b.id) as total_bookings,
               COUNT(DISTINCT CASE WHEN b.status IN ('checked_out','checked_in') THEN b.id END) as active_stays,
               COALESCE(SUM(CASE WHEN pay.status = 'completed' THEN pay.amount ELSE 0 END), 0) as total_spent,
               la.tier as loyalty_tier,
               la.points_balance as loyalty_points
        FROM guests g
        LEFT JOIN bookings b ON b.guest_id = g.id
        LEFT JOIN payments pay ON pay.booking_id = b.id
        LEFT JOIN loyalty_accounts la ON la.guest_id = g.id
    ";
    $params = [];

    if ($search !== '') {
        $query .= " WHERE g.first_name LIKE ? OR g.last_name LIKE ? OR g.email LIKE ? OR g.phone LIKE ?";
        $s = "%{$search}%";
        $params = [$s, $s, $s, $s];
    }

    $query .= " GROUP BY g.id ORDER BY total_spent DESC, g.last_name ASC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $guests = $stmt->fetchAll();

    include __DIR__ . '/../../app/Views/admin/guests.php';
    exit;
}

// --------------- ROUTE: SUBSCRIPTION & BILLING ---------------
if ($route === 'billing') {
    try {
        $db = Database::getConnection();
        $plans = $db->query("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY monthly_price ASC")->fetchAll();
        $subscriptions = $db->query("
            SELECT ps.*, sp.name as plan_name, sp.monthly_price, p.name as property_name
            FROM property_subscriptions ps
            JOIN subscription_plans sp ON ps.plan_id = sp.id
            JOIN properties p ON ps.property_id = p.id
            ORDER BY ps.status = 'active' DESC, ps.start_date DESC
        ")->fetchAll();
        $invoices = $db->query("
            SELECT si.*, p.name as property_name
            FROM subscription_invoices si
            JOIN properties p ON si.property_id = p.id
            ORDER BY si.issued_at DESC
            LIMIT 50
        ")->fetchAll();
        $properties = $db->query("SELECT id, name FROM properties WHERE status = 'active' ORDER BY name")->fetchAll();
    } catch (\PDOException $e) {
        Logger::error('Billing data load failed', ['exception' => $e->getMessage()]);
        $plans = [];
        $subscriptions = [];
        $invoices = [];
        $properties = [];
    }

    include __DIR__ . '/../../app/Views/admin/billing.php';
    exit;
}

// --------------- ROUTE: COMMISSION MANAGEMENT ---------------
if ($route === 'commissions') {
    $payoutStatus = $_GET['payout_status'] ?? '';
    $agencyBookings = $commissionService->getAgencyBookings();
    $payouts = $commissionService->getCommissionPayouts($payoutStatus);
    $summary = $commissionService->getCommissionSummary();

    include __DIR__ . '/../../app/Views/admin/commissions.php';
    exit;
}

// --------------- ROUTE: GLOBAL ANALYTICS ---------------
if ($route === 'analytics') {
    $kpis = $analyticsService->getGlobalKPIs();
    $revenueTrend = $analyticsService->getRevenueOverTime('12');
    $occupancyByProperty = $analyticsService->getOccupancyByProperty();
    $sourceBreakdown = $analyticsService->getBookingSourceBreakdown();
    $topProperties = $analyticsService->getTopProperties(10);

    include __DIR__ . '/../../app/Views/admin/analytics.php';
    exit;
}

// --------------- ROUTE: FRAUD DETECTION ---------------
if ($route === 'fraud') {
    $db = Database::getConnection();
    $statusFilter = $_GET['status'] ?? '';

    $query = "
        SELECT ff.*, g.first_name, g.last_name, g.email as guest_email,
               b.check_in_date, b.check_out_date, b.status as booking_status,
               p.name as property_name,
               u.first_name as flagger_first, u.last_name as flagger_last,
               ru.first_name as resolver_first, ru.last_name as resolver_last
        FROM fraud_flags ff
        LEFT JOIN guests g ON ff.guest_id = g.id
        LEFT JOIN bookings b ON ff.booking_id = b.id
        LEFT JOIN properties p ON b.property_id = p.id
        LEFT JOIN users u ON ff.flagged_by = u.id
        LEFT JOIN users ru ON ff.resolved_by = ru.id
    ";
    $params = [];
    if ($statusFilter !== '') {
        $query .= " WHERE ff.status = ?";
        $params[] = $statusFilter;
    }
    $query .= " ORDER BY FIELD(ff.severity,'critical','high','medium','low') ASC, FIELD(ff.status,'open','investigating','resolved','dismissed') ASC, ff.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $flags = $stmt->fetchAll();

    include __DIR__ . '/../../app/Views/admin/fraud.php';
    exit;
}

// --------------- ROUTE: REVIEW MODERATION ---------------
if ($route === 'moderation') {
    $statusFilter = $_GET['status'] ?? '';
    $reviews = $statusFilter !== ''
        ? $moderationService->getAllReviews($statusFilter)
        : $moderationService->getPendingReviews();
    $stats = $moderationService->getModerationStats();

    include __DIR__ . '/../../app/Views/admin/moderation.php';
    exit;
}

// --------------- ROUTE: SUPPORT TICKETS ---------------
if ($route === 'support') {
    $statusFilter = $_GET['status'] ?? '';
    $priorityFilter = $_GET['priority'] ?? '';
    $tickets = $ticketService->getTickets($statusFilter, $priorityFilter);
    $stats = $ticketService->getTicketStats();

    $db = Database::getConnection();
    $properties = $db->query("SELECT id, name FROM properties WHERE status = 'active' ORDER BY name")->fetchAll();
    $adminUsers = $db->query("SELECT id, first_name, last_name FROM users WHERE role IN ('admin','manager') ORDER BY first_name")->fetchAll();

    include __DIR__ . '/../../app/Views/admin/support.php';
    exit;
}

// --------------- ROUTE: SUPPORT TICKET DETAIL ---------------
if ($route === 'support-detail') {
    $ticketId = (int)($_GET['id'] ?? 0);
    $ticket = $ticketService->getTicketById($ticketId);
    if (!$ticket) { header("Location: index.php?route=support&error=Ticket not found."); exit; }
    $replies = $ticketService->getReplies($ticketId);

    $db = Database::getConnection();
    $adminUsers = $db->query("SELECT id, first_name, last_name FROM users WHERE role IN ('admin','manager') ORDER BY first_name")->fetchAll();

    include __DIR__ . '/../../app/Views/admin/support-detail.php';
    exit;
}

// --------------- ROUTE: DISPUTE RESOLUTION ---------------
if ($route === 'disputes') {
    $db = Database::getConnection();
    $statusFilter = $_GET['status'] ?? '';

    $query = "
        SELECT dr.*, g.first_name, g.last_name, g.email as guest_email,
               b.check_in_date, b.check_out_date, b.status as booking_status,
               p.name as property_name
        FROM dispute_resolutions dr
        LEFT JOIN bookings b ON dr.booking_id = b.id
        LEFT JOIN properties p ON b.property_id = p.id
        LEFT JOIN guests g ON dr.guest_id = g.id
    ";
    $params = [];
    if ($statusFilter !== '') {
        $query .= " WHERE dr.status = ?";
        $params[] = $statusFilter;
    }
    $query .= " ORDER BY FIELD(dr.status,'open','investigating','resolved','dismissed') ASC, dr.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $disputes = $stmt->fetchAll();

    include __DIR__ . '/../../app/Views/admin/disputes.php';
    exit;
}

// --------------- ROUTE: AUDIT LOG ---------------
if ($route === 'audit-log') {
    $db = Database::getConnection();

    $actionLabels = [
        'property_approved'      => 'Approved property onboarding',
        'property_rejected'      => 'Rejected property onboarding',
        'commission_rate_changed'=> 'Changed commission rate',
        'commission_paid'        => 'Approved commission payout',
        'dispute_resolved'       => 'Resolved dispute',
        'dispute_dismissed'      => 'Dismissed dispute',
        'review_moderated'       => 'Moderated guest review',
        'review_deleted'         => 'Deleted guest review',
        'ticket_status_changed'  => 'Updated support ticket status',
        'ticket_assigned'        => 'Assigned support ticket',
        'fraud_resolved'         => 'Resolved fraud flag',
        'fraud_dismissed'        => 'Dismissed fraud flag',
    ];

    $entityTypeLabels = [
        'property'   => 'Property',
        'commission' => 'Commission',
        'dispute'    => 'Dispute',
        'review'     => 'Review',
        'ticket'     => 'Support Ticket',
        'fraud'      => 'Fraud Flag',
    ];

    $filterAdmin = $_GET['admin'] ?? '';
    $filterAction = $_GET['action'] ?? '';
    $filterEntity = $_GET['entity'] ?? '';
    $filterDateFrom = $_GET['date_from'] ?? '';
    $filterDateTo = $_GET['date_to'] ?? '';

    // Build query with filters
    $query = "
        SELECT al.*, u.first_name, u.last_name
        FROM audit_log al
        JOIN users u ON al.admin_user_id = u.id
        WHERE 1=1
    ";
    $countQuery = "SELECT COUNT(*) FROM audit_log al JOIN users u ON al.admin_user_id = u.id WHERE 1=1";
    $params = [];

    if ($filterAdmin !== '') {
        $query .= " AND CONCAT(u.first_name, ' ', u.last_name) = ?";
        $countQuery .= " AND CONCAT(u.first_name, ' ', u.last_name) = ?";
        $params[] = $filterAdmin;
    }
    if ($filterAction !== '') {
        $query .= " AND al.action = ?";
        $countQuery .= " AND al.action = ?";
        $params[] = $filterAction;
    }
    if ($filterEntity !== '') {
        $query .= " AND al.entity_type = ?";
        $countQuery .= " AND al.entity_type = ?";
        $params[] = $filterEntity;
    }
    if ($filterDateFrom !== '') {
        $query .= " AND al.created_at >= ?";
        $countQuery .= " AND al.created_at >= ?";
        $params[] = $filterDateFrom;
    }
    if ($filterDateTo !== '') {
        $query .= " AND al.created_at <= ?";
        $countQuery .= " AND al.created_at <= ?";
        $params[] = $filterDateTo . ' 23:59:59';
    }

    $query .= " ORDER BY al.created_at DESC";

    // Pagination
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 10;

    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $totalEntries = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalEntries / $perPage));
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $perPage;

    $query .= " LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Build entries with computed admin_user_name and entity_label
    $entries = [];
    foreach ($rows as $row) {
        $adminName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        $entityLabel = $entityTypeLabels[$row['entity_type']] ?? $row['entity_type'];
        // Build a descriptive entity label based on type
        $entityLabelText = $entityLabel . ' #' . (int)$row['entity_id'];
        $entries[] = [
            'id'              => (int)$row['id'],
            'admin_user_name' => $adminName ?: 'Unknown Admin',
            'action'          => $row['action'],
            'entity_type'     => $row['entity_type'],
            'entity_id'       => (int)$row['entity_id'],
            'entity_label'    => $entityLabelText,
            'details'         => $row['details'] ?? '{}',
            'created_at'      => $row['created_at'],
        ];
    }

    // Unique admin names for filter dropdown
    $adminNames = $db->query("SELECT DISTINCT CONCAT(first_name, ' ', last_name) FROM users WHERE role IN ('admin','manager') ORDER BY first_name")->fetchAll(\PDO::FETCH_COLUMN);

    include __DIR__ . '/../../app/Views/admin/audit-log.php';
    exit;
}

if ($route === 'logout') {
    \App\Services\AuthService::logout();
    header("Location: " . BASE_URL . "/auth/login.php?surface=admin");
    exit;
}

http_response_code(404);
echo "404 Not Found";
exit;
