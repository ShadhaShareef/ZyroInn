<?php
namespace App\Services;

use PDO;

class AnalyticsService {

    public function getGlobalKPIs(): array {
        try {
            $db = Database::getConnection();

            $properties = (int)$db->query("SELECT COUNT(*) FROM properties WHERE status = 'active'")->fetchColumn();
            $rooms = (int)$db->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
            $occupied = (int)$db->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'")->fetchColumn();

            $totalRevenue = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'completed'")->fetchColumn();

            $totalBookings = (int)$db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
            $activeBookings = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status IN ('confirmed','checked_in')")->fetchColumn();

            $guests = (int)$db->query("SELECT COUNT(*) FROM guests")->fetchColumn();

            $reviewAvg = (float)$db->query("SELECT COALESCE(AVG(rating),0) FROM reviews WHERE moderation_status = 'approved'")->fetchColumn();

            return [
                'total_properties' => $properties,
                'total_rooms' => $rooms,
                'occupancy_rate' => $rooms > 0 ? round(($occupied / $rooms) * 100, 1) : 0,
                'total_revenue' => $totalRevenue,
                'total_bookings' => $totalBookings,
                'active_bookings' => $activeBookings,
                'total_guests' => $guests,
                'avg_rating' => round($reviewAvg, 1),
            ];
        } catch (\PDOException $e) {
            Logger::error('AnalyticsService::getGlobalKPIs failed', ['exception' => $e->getMessage()]);
            return [];
        }
    }

    public function getRevenueOverTime(string $months = '6'): array {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT DATE_FORMAT(paid_at, '%Y-%m') as month, COALESCE(SUM(amount),0) as revenue
                FROM payments
                WHERE status = 'completed' AND paid_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(paid_at, '%Y-%m')
                ORDER BY month ASC
            ");
            $stmt->execute([$months]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            Logger::error('AnalyticsService::getRevenueOverTime failed', ['exception' => $e->getMessage()]);
            return [];
        }
    }

    public function getOccupancyByProperty(): array {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("
                SELECT p.id, p.name,
                    COUNT(r.id) as total_rooms,
                    SUM(CASE WHEN r.status = 'occupied' THEN 1 ELSE 0 END) as occupied_rooms
                FROM properties p
                JOIN rooms r ON r.property_id = p.id
                GROUP BY p.id, p.name
                ORDER BY occupied_rooms DESC
            ");
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            Logger::error('AnalyticsService::getOccupancyByProperty failed', ['exception' => $e->getMessage()]);
            return [];
        }
    }

    public function getBookingSourceBreakdown(): array {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("
                SELECT source, COUNT(*) as count, COALESCE(SUM(commission_percentage),0) as total_commission_pct
                FROM bookings
                GROUP BY source
                ORDER BY count DESC
            ");
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            Logger::error('AnalyticsService::getBookingSourceBreakdown failed', ['exception' => $e->getMessage()]);
            return [];
        }
    }

    public function getTopProperties(int $limit = 5): array {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT p.id, p.name, p.city, p.state,
                    COUNT(DISTINCT b.id) as booking_count,
                    COALESCE(SUM(pay.amount),0) as revenue
                FROM properties p
                LEFT JOIN bookings b ON b.property_id = p.id
                LEFT JOIN payments pay ON pay.booking_id = b.id AND pay.status = 'completed'
                GROUP BY p.id, p.name, p.city, p.state
                ORDER BY revenue DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            Logger::error('AnalyticsService::getTopProperties failed', ['exception' => $e->getMessage()]);
            return [];
        }
    }

    public function getRecentActivity(int $limit = 10): array {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT b.id, b.status, b.created_at, p.name as property_name,
                       g.first_name, g.last_name
                FROM bookings b
                JOIN properties p ON b.property_id = p.id
                LEFT JOIN guests g ON b.guest_id = g.id
                ORDER BY b.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            Logger::error('AnalyticsService::getRecentActivity failed', ['exception' => $e->getMessage()]);
            return [];
        }
    }
}
