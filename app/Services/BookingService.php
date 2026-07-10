<?php
namespace App\Services;

use PDO;

class BookingService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function hasOverlap(int $roomId, string $checkIn, string $checkOut, ?int $excludeBookingId = null): bool
    {
        $sql = "
            SELECT id FROM bookings
            WHERE room_id = ?
              AND status IN ('confirmed', 'checked_in')
              AND check_in_date < ?
              AND check_out_date > ?
        ";
        $params = [$roomId, $checkOut, $checkIn];

        if ($excludeBookingId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeBookingId;
        }

        $sql .= " LIMIT 1 FOR UPDATE";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public function findAvailableRooms(int $propertyId, string $checkIn, string $checkOut, ?int $excludeRoomId = null): array
    {
        $sql = "
            SELECT r.*, p.name AS property_name
            FROM rooms r
            JOIN properties p ON r.property_id = p.id
            WHERE r.property_id = ?
              AND r.status = 'available'
              AND r.id NOT IN (
                  SELECT b.room_id FROM bookings b
                  WHERE b.room_id = r.id
                    AND b.status IN ('confirmed', 'checked_in')
                    AND b.check_in_date < ?
                    AND b.check_out_date > ?
              )
        ";
        $params = [$propertyId, $checkOut, $checkIn];

        if ($excludeRoomId !== null) {
            $sql .= " AND r.id != ?";
            $params[] = $excludeRoomId;
        }

        $sql .= " ORDER BY r.base_rate ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getOverbookingConflicts(int $propertyId): array
    {
        $stmt = $this->db->prepare("
            SELECT b1.id as booking_id, b1.room_id, r.room_number, r.room_type,
                   b1.check_in_date, b1.check_out_date,
                   b2.id as conflict_id
            FROM bookings b1
            JOIN bookings b2 ON b1.room_id = b2.room_id AND b1.id != b2.id
                AND b1.check_in_date < b2.check_out_date
                AND b2.check_in_date < b1.check_out_date
            JOIN rooms r ON r.id = b1.room_id
            WHERE b1.property_id = ? AND b1.status IN ('confirmed','checked_in')
              AND b2.status IN ('confirmed','checked_in')
            ORDER BY b1.check_in_date
            LIMIT 5
        ");
        $stmt->execute([$propertyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
