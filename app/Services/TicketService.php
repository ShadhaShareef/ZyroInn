<?php
namespace App\Services;

use PDO;

class TicketService {

    public function getTickets(string $statusFilter = '', string $priorityFilter = ''): array {
        $db = Database::getConnection();
        $query = "
            SELECT t.*, p.name as property_name,
                   u.first_name as assignee_first, u.last_name as assignee_last,
                   g.first_name as guest_first, g.last_name as guest_last
            FROM support_tickets t
            LEFT JOIN properties p ON t.property_id = p.id
            LEFT JOIN users u ON t.assigned_to = u.id
            LEFT JOIN guests g ON t.guest_id = g.id
        ";
        $conditions = [];
        $params = [];

        if ($statusFilter !== '') {
            $conditions[] = "t.status = ?";
            $params[] = $statusFilter;
        }
        if ($priorityFilter !== '') {
            $conditions[] = "t.priority = ?";
            $params[] = $priorityFilter;
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }

        $query .= " ORDER BY FIELD(t.status, 'open','in_progress','waiting_on_customer','resolved','closed') ASC, FIELD(t.priority, 'urgent','high','normal','low') ASC, t.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getTicketById(int $ticketId): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT t.*, p.name as property_name,
                   u.first_name as assignee_first, u.last_name as assignee_last,
                   g.first_name as guest_first, g.last_name as guest_last, g.email as guest_email
            FROM support_tickets t
            LEFT JOIN properties p ON t.property_id = p.id
            LEFT JOIN users u ON t.assigned_to = u.id
            LEFT JOIN guests g ON t.guest_id = g.id
            WHERE t.id = ?
        ");
        $stmt->execute([$ticketId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getReplies(int $ticketId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT r.*, u.first_name, u.last_name
            FROM support_ticket_replies r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.ticket_id = ?
            ORDER BY r.created_at ASC
        ");
        $stmt->execute([$ticketId]);
        return $stmt->fetchAll();
    }

    public function createTicket(int $propertyId, string $subject, string $description, string $category = 'other', string $priority = 'normal', ?int $submittedBy = null, ?int $guestId = null): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO support_tickets (property_id, guest_id, submitted_by, subject, description, category, priority)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$propertyId, $guestId, $submittedBy, $subject, $description, $category, $priority]);
        return (int)$db->lastInsertId();
    }

    public function addReply(int $ticketId, int $userId, string $message, bool $isInternal = false): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO support_ticket_replies (ticket_id, user_id, message, is_internal)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$ticketId, $userId, $message, $isInternal ? 1 : 0]);
        return (int)$db->lastInsertId();
    }

    public function updateStatus(int $ticketId, string $status, ?int $assignedTo = null): bool {
        $db = Database::getConnection();
        $resolvedAt = $status === 'resolved' || $status === 'closed' ? date('Y-m-d H:i:s') : null;
        if ($assignedTo !== null) {
            $stmt = $db->prepare("UPDATE support_tickets SET status = ?, assigned_to = ?, resolved_at = COALESCE(?, resolved_at) WHERE id = ?");
            return $stmt->execute([$status, $assignedTo, $resolvedAt, $ticketId]);
        }
        $stmt = $db->prepare("UPDATE support_tickets SET status = ?, resolved_at = COALESCE(?, resolved_at) WHERE id = ?");
        return $stmt->execute([$status, $resolvedAt, $ticketId]);
    }

    public function getTicketStats(): array {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
            FROM support_tickets
        ");
        return $stmt->fetch();
    }
}
