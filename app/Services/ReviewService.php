<?php
namespace App\Services;

use App\Services\Database;
use InvalidArgumentException;
use Exception;

class ReviewService {

    /**
     * Check if a guest is eligible to review a property (has completed stay).
     *
     * @param int $guestId
     * @param int $propertyId
     * @return bool
     */
    public function canGuestReviewProperty(int $guestId, int $propertyId): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT 1 FROM bookings 
            WHERE guest_id = :guest_id 
              AND property_id = :property_id 
              AND status = 'checked_out'
            LIMIT 1
        ");
        $stmt->execute(['guest_id' => $guestId, 'property_id' => $propertyId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Check if a guest has already reviewed a property.
     *
     * @param int $guestId
     * @param int $propertyId
     * @return bool
     */
    public function hasGuestReviewedProperty(int $guestId, int $propertyId): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT 1 FROM reviews 
            WHERE guest_id = :guest_id 
              AND property_id = :property_id
            LIMIT 1
        ");
        $stmt->execute(['guest_id' => $guestId, 'property_id' => $propertyId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Create a new review.
     *
     * @param int $guestId
     * @param int $propertyId
     * @param int|null $roomId
     * @param int $rating (1-5)
     * @param string|null $title
     * @param string|null $body
     * @return int Review ID
     * @throws Exception
     */
    public function createReview(int $guestId, int $propertyId, ?int $roomId, int $rating, ?string $title, ?string $body): int {
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Rating must be between 1 and 5');
        }

        if (!$this->canGuestReviewProperty($guestId, $propertyId)) {
            throw new Exception('You can only review properties where you have completed a stay.');
        }

        if ($this->hasGuestReviewedProperty($guestId, $propertyId)) {
            throw new Exception('You have already reviewed this property.');
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO reviews (guest_id, property_id, room_id, rating, title, body)
            VALUES (:guest_id, :property_id, :room_id, :rating, :title, :body)
        ");
        $stmt->execute([
            'guest_id' => $guestId,
            'property_id' => $propertyId,
            'room_id' => $roomId,
            'rating' => $rating,
            'title' => $title,
            'body' => $body
        ]);

        return (int)$db->lastInsertId();
    }

    /**
     * Get reviews for a property with pagination.
     *
     * @param int $propertyId
     * @param int $page
     * @param int $limit
     * @return array ['reviews' => [], 'total' => int, 'page' => int, 'totalPages' => int]
     */
    public function getReviewsForProperty(int $propertyId, int $page = 1, int $limit = 10): array {
        $db = Database::getConnection();

        $countStmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE property_id = :property_id");
        $countStmt->execute(['property_id' => $propertyId]);
        $total = (int)$countStmt->fetchColumn();

        $totalPages = max(1, (int)ceil($total / $limit));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $limit;

        $stmt = $db->prepare("
            SELECT r.*, g.first_name, g.last_name
            FROM reviews r
            JOIN guests g ON r.guest_id = g.id
            WHERE r.property_id = :property_id
            ORDER BY r.review_date DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':property_id', $propertyId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return [
            'reviews' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages
        ];
    }

    /**
     * Get review by ID.
     *
     * @param int $reviewId
     * @return array|null
     */
    public function getReviewById(int $reviewId): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT r.*, g.first_name, g.last_name, g.email
            FROM reviews r
            JOIN guests g ON r.guest_id = g.id
            WHERE r.id = :review_id
        ");
        $stmt->execute(['review_id' => $reviewId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Update a review (only by the author).
     *
     * @param int $reviewId
     * @param int $guestId
     * @param int $rating
     * @param string|null $title
     * @param string|null $body
     * @return bool
     * @throws Exception
     */
    public function updateReview(int $reviewId, int $guestId, int $rating, ?string $title, ?string $body): bool {
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Rating must be between 1 and 5');
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE reviews 
            SET rating = :rating, title = :title, body = :body, updated_at = CURRENT_TIMESTAMP
            WHERE id = :review_id AND guest_id = :guest_id
        ");
        return $stmt->execute([
            'review_id' => $reviewId,
            'guest_id' => $guestId,
            'rating' => $rating,
            'title' => $title,
            'body' => $body
        ]);
    }

    /**
     * Delete a review (only by the author or admin).
     *
     * @param int $reviewId
     * @param int $guestId
     * @return bool
     */
    public function deleteReview(int $reviewId, int $guestId): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM reviews WHERE id = :review_id AND guest_id = :guest_id");
        return $stmt->execute(['review_id' => $reviewId, 'guest_id' => $guestId]);
    }

    /**
     * Get average rating for a property.
     *
     * @param int $propertyId
     * @return array ['average' => float, 'count' => int]
     */
    public function getPropertyRatingSummary(int $propertyId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT AVG(rating) as average, COUNT(*) as count
            FROM reviews
            WHERE property_id = :property_id
        ");
        $stmt->execute(['property_id' => $propertyId]);
        $result = $stmt->fetch();
        
        return [
            'average' => $result['average'] ? round((float)$result['average'], 1) : 0,
            'count' => (int)$result['count']
        ];
    }

    /**
     * Get rating distribution for a property (1-5 stars).
     *
     * @param int $propertyId
     * @return array
     */
    public function getRatingDistribution(int $propertyId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT rating, COUNT(*) as count
            FROM reviews
            WHERE property_id = :property_id
            GROUP BY rating
            ORDER BY rating DESC
        ");
        $stmt->execute(['property_id' => $propertyId]);
        $results = $stmt->fetchAll();

        $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        foreach ($results as $row) {
            $distribution[(int)$row['rating']] = (int)$row['count'];
        }

        return $distribution;
    }

    /**
     * Get reviews written by a guest.
     *
     * @param int $guestId
     * @return array
     */
    public function getReviewsByGuest(int $guestId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT r.*, p.name as property_name, p.city, p.state
            FROM reviews r
            JOIN properties p ON r.property_id = p.id
            WHERE r.guest_id = :guest_id
            ORDER BY r.review_date DESC
        ");
        $stmt->execute(['guest_id' => $guestId]);
        return $stmt->fetchAll();
    }
}