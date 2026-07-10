<?php
namespace App\Services;

use PDO;

class ModerationService {

    public function getPendingReviews(): array {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT r.*, g.first_name, g.last_name, g.email, p.name as property_name
            FROM reviews r
            JOIN guests g ON r.guest_id = g.id
            JOIN properties p ON r.property_id = p.id
            WHERE r.moderation_status = 'pending'
            ORDER BY r.review_date DESC
        ");
        return $stmt->fetchAll();
    }

    public function getAllReviews(string $statusFilter = ''): array {
        $db = Database::getConnection();
        $query = "
            SELECT r.*, g.first_name, g.last_name, g.email, p.name as property_name
            FROM reviews r
            JOIN guests g ON r.guest_id = g.id
            JOIN properties p ON r.property_id = p.id
        ";
        $params = [];
        if ($statusFilter !== '') {
            $query .= " WHERE r.moderation_status = ?";
            $params[] = $statusFilter;
        }
        $query .= " ORDER BY r.review_date DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function moderateReview(int $reviewId, string $status, int $moderatedBy): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE reviews
            SET moderation_status = ?, moderated_by = ?, moderated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$status, $moderatedBy, $reviewId]);
    }

    public function deleteReview(int $reviewId): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
        return $stmt->execute([$reviewId]);
    }

    public function getModerationStats(): array {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN moderation_status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN moderation_status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN moderation_status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN moderation_status = 'flagged' THEN 1 ELSE 0 END) as flagged
            FROM reviews
        ");
        return $stmt->fetch();
    }
}
