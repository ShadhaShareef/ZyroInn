<?php
namespace App\Services;

class MessageService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getOrCreateConversation(int $bookingId, int $guestId, string $subject = ''): int
    {
        $stmt = $this->db->prepare("SELECT id FROM conversations WHERE booking_id = ? AND guest_id = ? AND status = 'open' LIMIT 1");
        $stmt->execute([$bookingId, $guestId]);
        $existing = $stmt->fetchColumn();
        if ($existing) {
            return (int)$existing;
        }
        $stmt = $this->db->prepare("INSERT INTO conversations (booking_id, guest_id, subject) VALUES (?, ?, ?)");
        $stmt->execute([$bookingId, $guestId, $subject]);
        return (int)$this->db->lastInsertId();
    }

    public function sendMessage(int $conversationId, string $senderType, int $senderId, string $message): int
    {
        $stmt = $this->db->prepare("INSERT INTO messages (conversation_id, sender_type, sender_id, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$conversationId, $senderType, $senderId, $message]);
        return (int)$this->db->lastInsertId();
    }

    public function getMessages(int $conversationId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare("SELECT * FROM messages WHERE conversation_id = ? ORDER BY created_at ASC LIMIT ? OFFSET ?");
        $stmt->execute([$conversationId, $limit, $offset]);
        return $stmt->fetchAll();
    }

    public function getConversationsForGuest(int $guestId): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, b.check_in_date, b.check_out_date, r.room_number, r.room_type, p.name AS property_name,
                (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND read_at IS NULL AND sender_type = 'staff') AS unread
            FROM conversations c
            JOIN bookings b ON b.id = c.booking_id
            JOIN rooms r ON r.id = b.room_id
            JOIN properties p ON p.id = b.property_id
            WHERE c.guest_id = ?
            ORDER BY c.updated_at DESC
        ");
        $stmt->execute([$guestId]);
        return $stmt->fetchAll();
    }

    public function getConversationsForStaff(int $propertyId): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, g.first_name, g.last_name, g.email,
                b.check_in_date, b.check_out_date, r.room_number,
                (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND read_at IS NULL AND sender_type = 'guest') AS unread
            FROM conversations c
            JOIN bookings b ON b.id = c.booking_id
            JOIN rooms r ON r.id = b.room_id
            JOIN guests g ON g.id = c.guest_id
            WHERE r.property_id = ?
            ORDER BY c.updated_at DESC
        ");
        $stmt->execute([$propertyId]);
        return $stmt->fetchAll();
    }

    public function markAsRead(int $conversationId, string $senderType): void
    {
        $stmt = $this->db->prepare("UPDATE messages SET read_at = NOW() WHERE conversation_id = ? AND sender_type = ? AND read_at IS NULL");
        $stmt->execute([$conversationId, $senderType]);
    }

    public function getUnreadCount(int $guestId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM messages m
            JOIN conversations c ON c.id = m.conversation_id
            WHERE c.guest_id = ? AND m.sender_type = 'staff' AND m.read_at IS NULL
        ");
        $stmt->execute([$guestId]);
        return (int)$stmt->fetchColumn();
    }

    public function closeConversation(int $conversationId): void
    {
        $stmt = $this->db->prepare("UPDATE conversations SET status = 'closed' WHERE id = ?");
        $stmt->execute([$conversationId]);
    }
}
