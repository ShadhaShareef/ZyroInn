<?php
namespace App\Services;

use App\Services\Database;
use InvalidArgumentException;
use PDO;

class LoyaltyService {

    const POINTS_PER_DOLLAR = 10; // 10 points per $1 spent
    const POINTS_PER_DOLLAR_REDEMPTION = 100; // 100 points = $1

    const TIERS = [
        'bronze' => ['min' => 0, 'max' => 4999, 'perks' => ['Basic rewards']],
        'silver' => ['min' => 5000, 'max' => 19999, 'perks' => ['Priority check-in', 'Late checkout']],
        'gold' => ['min' => 20000, 'max' => 49999, 'perks' => ['Room upgrade (subject to availability)', 'Welcome amenity', 'Priority support']],
        'platinum' => ['min' => 50000, 'max' => PHP_INT_MAX, 'perks' => ['Suite upgrade (subject to availability)', 'Complimentary breakfast', 'Dedicated concierge', 'Early check-in/late checkout guaranteed']]
    ];

    /**
     * Get or create loyalty account for a guest.
     *
     * @param int $guestId
     * @return array
     */
    public function getOrCreateAccount(int $guestId): array {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("SELECT * FROM loyalty_accounts WHERE guest_id = ?");
        $stmt->execute([$guestId]);
        $account = $stmt->fetch();

        if (!$account) {
            $db->beginTransaction();
            try {
                $insertStmt = $db->prepare("
                    INSERT INTO loyalty_accounts (guest_id, points_balance, tier)
                    VALUES (?, 0, 'bronze')
                ");
                $insertStmt->execute([$guestId]);
                $accountId = $db->lastInsertId();
                
                $db->commit();
                
                $account = [
                    'id' => $accountId,
                    'guest_id' => $guestId,
                    'points_balance' => 0,
                    'tier' => 'bronze',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
        }

        return $account;
    }

    /**
     * Get loyalty account with tier info.
     *
     * @param int $guestId
     * @return array
     */
    public function getAccountWithTier(int $guestId): array {
        $account = $this->getOrCreateAccount($guestId);
        $tierInfo = self::TIERS[$account['tier']] ?? self::TIERS['bronze'];
        
        return [
            'account' => $account,
            'tier' => $account['tier'],
            'tier_label' => ucfirst($account['tier']),
            'tier_perks' => $tierInfo['perks'],
            'points_to_next_tier' => $this->getPointsToNextTier($account['points_balance'], $account['tier']),
            'progress_percent' => $this->getTierProgress($account['points_balance'], $account['tier'])
        ];
    }

    /**
     * Calculate points to next tier.
     */
    private function getPointsToNextTier(int $points, string $currentTier): int {
        $tierOrder = ['bronze', 'silver', 'gold', 'platinum'];
        $currentIndex = array_search($currentTier, $tierOrder);
        
        if ($currentIndex === false || $currentIndex === count($tierOrder) - 1) {
            return 0; // Already at max tier
        }
        
        $nextTier = $tierOrder[$currentIndex + 1];
        $nextTierMin = self::TIERS[$nextTier]['min'];
        
        return max(0, $nextTierMin - $points);
    }

    /**
     * Calculate tier progress percentage.
     */
    private function getTierProgress(int $points, string $currentTier): int {
        $tierOrder = ['bronze', 'silver', 'gold', 'platinum'];
        $currentIndex = array_search($currentTier, $tierOrder);
        
        if ($currentIndex === false || $currentIndex === count($tierOrder) - 1) {
            return 100; // Already at max tier
        }
        
        $currentTierMin = self::TIERS[$currentTier]['min'];
        $nextTier = $tierOrder[$currentIndex + 1];
        $nextTierMin = self::TIERS[$nextTier]['min'];
        
        if ($nextTierMin <= $currentTierMin) return 100;
        
        $progress = (($points - $currentTierMin) / ($nextTierMin - $currentTierMin)) * 100;
        return min(100, max(0, (int)$progress));
    }

    /**
     * Earn points for a completed stay.
     *
     * @param int $bookingId
     * @param float $amountSpent
     * @param string $description
     * @return array ['points_earned' => int, 'new_balance' => int, 'new_tier' => string]
     */
    public function earnPoints(int $bookingId, float $amountSpent, string $description = 'Stay completed'): array {
        $db = Database::getConnection();
        
        // Get guest_id from booking
        $stmt = $db->prepare("SELECT guest_id, property_id FROM bookings WHERE id = ?");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            throw new InvalidArgumentException("Booking not found");
        }

        $guestId = (int)$booking['guest_id'];
        $pointsEarned = (int)floor($amountSpent * self::POINTS_PER_DOLLAR);

        $db->beginTransaction();
        try {
            $account = $this->getOrCreateAccount($guestId);
            $newBalance = $account['points_balance'] + $pointsEarned;
            $newTier = $this->calculateTier($newBalance);

            // Update account
            $updateStmt = $db->prepare("
                UPDATE loyalty_accounts 
                SET points_balance = ?, tier = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $updateStmt->execute([$newBalance, $newTier, $account['id']]);

            // Record transaction
            $txnStmt = $db->prepare("
                INSERT INTO loyalty_transactions (loyalty_account_id, booking_id, points_change, transaction_type, description)
                VALUES (?, ?, ?, 'earn', ?)
            ");
            $txnStmt->execute([$account['id'], $bookingId, $pointsEarned, $description]);

            $db->commit();

            return [
                'points_earned' => $pointsEarned,
                'new_balance' => $newBalance,
                'new_tier' => $newTier,
                'tier_changed' => $newTier !== $account['tier']
            ];
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Redeem points at checkout.
     *
     * @param int $guestId
     * @param int $pointsToRedeem
     * @param int $bookingId
     * @param string $description
     * @return array ['points_redeemed' => int, 'dollar_value' => float, 'new_balance' => int]
     */
    public function redeemPoints(int $guestId, int $pointsToRedeem, int $bookingId, string $description = 'Booking payment'): array {
        if ($pointsToRedeem <= 0) {
            throw new InvalidArgumentException("Points to redeem must be positive");
        }

        $db = Database::getConnection();
        $account = $this->getOrCreateAccount($guestId);

        if ($account['points_balance'] < $pointsToRedeem) {
            throw new InvalidArgumentException("Insufficient points balance");
        }

        $dollarValue = $pointsToRedeem / self::POINTS_PER_DOLLAR_REDEMPTION;
        $newBalance = $account['points_balance'] - $pointsToRedeem;
        $newTier = $this->calculateTier($newBalance);

        $db->beginTransaction();
        try {
            $updateStmt = $db->prepare("
                UPDATE loyalty_accounts 
                SET points_balance = ?, tier = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $updateStmt->execute([$newBalance, $newTier, $account['id']]);

            $txnStmt = $db->prepare("
                INSERT INTO loyalty_transactions (loyalty_account_id, booking_id, points_change, transaction_type, description)
                VALUES (?, ?, ?, 'redeem', ?)
            ");
            $txnStmt->execute([$account['id'], $bookingId, -$pointsToRedeem, $description]);

            $db->commit();

            return [
                'points_redeemed' => $pointsToRedeem,
                'dollar_value' => $dollarValue,
                'new_balance' => $newBalance,
                'new_tier' => $newTier
            ];
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Calculate tier based on points balance.
     */
    private function calculateTier(int $points): string {
        if ($points >= self::TIERS['platinum']['min']) return 'platinum';
        if ($points >= self::TIERS['gold']['min']) return 'gold';
        if ($points >= self::TIERS['silver']['min']) return 'silver';
        return 'bronze';
    }

    /**
     * Get transaction history for a guest.
     *
     * @param int $guestId
     * @param int $limit
     * @return array
     */
    public function getTransactionHistory(int $guestId, int $limit = 50): array {
        $db = Database::getConnection();
        $account = $this->getOrCreateAccount($guestId);

        $stmt = $db->prepare("
            SELECT lt.*, b.check_in_date, b.check_out_date, p.name as property_name
            FROM loyalty_transactions lt
            LEFT JOIN bookings b ON lt.booking_id = b.id
            LEFT JOIN properties p ON b.property_id = p.id
            WHERE lt.loyalty_account_id = ?
            ORDER BY lt.occurred_at DESC
            LIMIT ?
        ");
        $stmt->execute([$account['id'], $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Calculate maximum points redeemable for an amount.
     *
     * @param float $amount
     * @return int
     */
    public function getMaxRedeemablePoints(float $amount): int {
        return (int)floor($amount * self::POINTS_PER_DOLLAR_REDEMPTION);
    }

    /**
     * Get dollar value of points.
     *
     * @param int $points
     * @return float
     */
    public function getPointsValue(int $points): float {
        return $points / self::POINTS_PER_DOLLAR_REDEMPTION;
    }

    /**
     * Check if guest has completed a stay at a property (for review eligibility).
     *
     * @param int $guestId
     * @param int $propertyId
     * @return bool
     */
    public function hasCompletedStayAtProperty(int $guestId, int $propertyId): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT 1 FROM bookings
            WHERE guest_id = ? 
              AND property_id = ?
              AND status = 'checked_out'
            LIMIT 1
        ");
        $stmt->execute([$guestId, $propertyId]);
        return (bool)$stmt->fetch();
    }

    /**
     * Check if guest has already reviewed a property.
     *
     * @param int $guestId
     * @param int $propertyId
     * @return bool
     */
    public function hasReviewedProperty(int $guestId, int $propertyId): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT 1 FROM reviews
            WHERE guest_id = ? AND property_id = ?
            LIMIT 1
        ");
        $stmt->execute([$guestId, $propertyId]);
        return (bool)$stmt->fetch();
    }

    /**
     * Get loyalty account ID for a guest (useful for linking).
     */
    public function getLoyaltyAccountId(int $guestId): ?int {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM loyalty_accounts WHERE guest_id = ?");
        $stmt->execute([$guestId]);
        return $stmt->fetchColumn() ? (int)$stmt->fetchColumn() : null;
    }
}