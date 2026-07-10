<?php
namespace App\Services;

use PDO;

class CommissionService {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAgencyBookings(string $statusFilter = '', string $search = ''): array {
        $query = "
            SELECT b.id, b.commission_percentage, b.source, b.status as booking_status,
                   b.check_in_date, b.check_out_date, b.created_at,
                   p.name as property_name, p.id as property_id,
                   g.first_name, g.last_name, g.email,
                   COALESCE(pay.amount, 0) as paid_amount,
                   (SELECT COALESCE(SUM(amount),0) FROM payments WHERE booking_id = b.id AND status = 'completed') as total_paid
            FROM bookings b
            JOIN properties p ON b.property_id = p.id
            LEFT JOIN guests g ON b.guest_id = g.id
            LEFT JOIN payments pay ON pay.booking_id = b.id AND pay.status = 'completed'
            WHERE b.source = 'agency'
        ";
        $params = [];

        if ($statusFilter !== '') {
            $query .= " AND b.status = ?";
            $params[] = $statusFilter;
        }

        if ($search !== '') {
            $query .= " AND (g.first_name LIKE ? OR g.last_name LIKE ? OR p.name LIKE ?)";
            $s = "%{$search}%";
            $params = array_merge($params, [$s, $s, $s]);
        }

        $query .= " ORDER BY b.created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getCommissionPayouts(string $status = ''): array {
        $query = "
            SELECT cp.*, p.name as property_name, b.check_in_date
            FROM commission_payouts cp
            JOIN properties p ON cp.property_id = p.id
            JOIN bookings b ON cp.booking_id = b.id
        ";
        $params = [];
        if ($status !== '') {
            $query .= " WHERE cp.status = ?";
            $params[] = $status;
        }
        $query .= " ORDER BY cp.created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getCommissionSummary(): array {
        $stmt = $this->db->query("
            SELECT
                COUNT(*) as total_agency_bookings,
                COUNT(CASE WHEN b.status NOT IN ('cancelled') THEN 1 END) as active_agency_bookings,
                COALESCE(SUM(CASE WHEN b.status NOT IN ('cancelled') THEN b.commission_percentage ELSE 0 END), 0) as total_commission_pct
            FROM bookings b
            WHERE b.source = 'agency'
        ");
        $agency = $stmt->fetch();

        $stmt2 = $this->db->query("
            SELECT
                COUNT(*) as total_payouts,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END), 0) as total_paid,
                COALESCE(SUM(CASE WHEN status IN ('pending','approved') THEN commission_amount ELSE 0 END), 0) as total_pending
            FROM commission_payouts
        ");
        $payouts = $stmt2->fetch();

        return array_merge($agency, $payouts);
    }

    public function createPayout(int $bookingId, int $propertyId, string $agencyName, float $amount, float $percentage): int {
        $stmt = $this->db->prepare("
            INSERT INTO commission_payouts (booking_id, property_id, agency_name, commission_amount, commission_percentage, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$bookingId, $propertyId, $agencyName, $amount, $percentage]);
        return (int)$this->db->lastInsertId();
    }

    public function updatePayoutStatus(int $payoutId, string $status): bool {
        $paidAt = $status === 'paid' ? date('Y-m-d H:i:s') : null;
        $stmt = $this->db->prepare("UPDATE commission_payouts SET status = ?, paid_at = COALESCE(?, paid_at) WHERE id = ?");
        return $stmt->execute([$status, $paidAt, $payoutId]);
    }
}
