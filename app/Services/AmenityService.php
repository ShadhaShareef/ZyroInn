<?php
namespace App\Services;

use App\Services\Database;
use InvalidArgumentException;
use PDO;

class AmenityService {

    /**
     * Get all enabled amenities for a property, grouped by category.
     *
     * @param int $propertyId
     * @return array Grouped list of enabled amenities
     */
    public function getEnabledAmenitiesForProperty(int $propertyId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT a.*, pa.enabled
            FROM amenities a
            JOIN property_amenities pa ON a.id = pa.amenity_id
            WHERE pa.property_id = :property_id 
              AND pa.enabled = 1 
              AND a.active = 1
            ORDER BY a.category, a.label
        ");
        $stmt->execute(['property_id' => $propertyId]);
        $results = $stmt->fetchAll();

        $grouped = [];
        foreach ($results as $row) {
            $grouped[$row['category']][] = $row;
        }
        return $grouped;
    }

    /**
     * Get all enabled amenities for a room.
     *
     * @param int $roomId
     * @return array Flat list of enabled room amenities
     */
    public function getEnabledAmenitiesForRoom(int $roomId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT a.*, ra.enabled
            FROM amenities a
            JOIN room_amenities ra ON a.id = ra.amenity_id
            WHERE ra.room_id = :room_id 
              AND ra.enabled = 1 
              AND a.active = 1
            ORDER BY a.category, a.label
        ");
        $stmt->execute(['room_id' => $roomId]);
        return $stmt->fetchAll();
    }

    /**
     * Toggle an amenity enabled status for a property or room.
     *
     * @param int $id The property_id or room_id
     * @param string $amenityKey The key of the amenity
     * @param bool $enabled The toggled status
     * @return bool
     * @throws InvalidArgumentException
     */
    public function toggleAmenity(int $id, string $amenityKey, bool $enabled): bool {
        $db = Database::getConnection();

        // 1. Lookup the amenity scope to see if it is property-level or room-level
        $stmt = $db->prepare("SELECT id, scope FROM amenities WHERE `key` = :key AND active = 1");
        $stmt->execute(['key' => $amenityKey]);
        $amenity = $stmt->fetch();

        if (!$amenity) {
            throw new InvalidArgumentException("Amenity key '{$amenityKey}' is invalid or inactive.");
        }

        $amenityId = (int)$amenity['id'];
        $scope = $amenity['scope'];
        $enabledVal = $enabled ? 1 : 0;

        if ($scope === 'property') {
            // Check if property exists
            $check = $db->prepare("SELECT 1 FROM properties WHERE id = ?");
            $check->execute([$id]);
            if (!$check->fetch()) {
                throw new InvalidArgumentException("Property with ID {$id} not found.");
            }

            // Perform upsert (INSERT ON DUPLICATE KEY UPDATE)
            $toggleStmt = $db->prepare("
                INSERT INTO property_amenities (property_id, amenity_id, enabled)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE enabled = ?
            ");
            return $toggleStmt->execute([
                $id,
                $amenityId,
                $enabledVal,
                $enabledVal
            ]);
        } elseif ($scope === 'room') {
            // Check if room exists
            $check = $db->prepare("SELECT 1 FROM rooms WHERE id = ?");
            $check->execute([$id]);
            if (!$check->fetch()) {
                throw new InvalidArgumentException("Room with ID {$id} not found.");
            }

            // Perform upsert (INSERT ON DUPLICATE KEY UPDATE)
            $toggleStmt = $db->prepare("
                INSERT INTO room_amenities (room_id, amenity_id, enabled)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE enabled = ?
            ");
            return $toggleStmt->execute([
                $id,
                $amenityId,
                $enabledVal,
                $enabledVal
            ]);
        }

        return false;
    }

    /**
     * Get the full taxonomy of active amenities grouped by category for filter UI.
     *
     * @return array Grouped active amenities
     */
    public function getFilterableAmenities(): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM amenities
            WHERE active = 1
            ORDER BY category, label
        ");
        $stmt->execute();
        $results = $stmt->fetchAll();

        $grouped = [];
        foreach ($results as $row) {
            $grouped[$row['category']][] = $row;
        }
        return $grouped;
    }

    /**
     * Get all property-level amenities along with their current status for a property.
     * Useful for config check-list screens.
     *
     * @param int $propertyId
     * @return array Grouped list of all property amenities and their current state
     */
    public function getAmenitiesWithStatusForProperty(int $propertyId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT a.*, COALESCE(pa.enabled, 0) as enabled
            FROM amenities a
            LEFT JOIN property_amenities pa 
              ON a.id = pa.amenity_id AND pa.property_id = :property_id
            WHERE a.scope = 'property' 
              AND a.active = 1
            ORDER BY a.category, a.label
        ");
        $stmt->execute(['property_id' => $propertyId]);
        $results = $stmt->fetchAll();

        $grouped = [];
        foreach ($results as $row) {
            $grouped[$row['category']][] = $row;
        }
        return $grouped;
    }

    public function createAmenity(int $propertyId, string $label, string $category, string $icon = '', string $description = ''): array
    {
        $db = Database::getConnection();
        $key = $this->generateAmenityKey($label);

        $stmt = $db->prepare("SELECT id FROM amenities WHERE `key` = ?");
        $stmt->execute([$key]);
        if ($stmt->fetch()) {
            $i = 1;
            while (true) {
                $tryKey = $key . '_' . $i;
                $stmt->execute([$tryKey]);
                if (!$stmt->fetch()) { $key = $tryKey; break; }
                $i++;
            }
        }

        $stmt = $db->prepare("INSERT INTO amenities (`key`, label, category, description, scope, icon, active) VALUES (?, ?, ?, ?, 'property', ?, 1)");
        $stmt->execute([$key, $label, $category, $description, $icon]);
        $amenityId = (int)$db->lastInsertId();

        $stmt = $db->prepare("INSERT INTO property_amenities (property_id, amenity_id, enabled) VALUES (?, ?, 1)");
        $stmt->execute([$propertyId, $amenityId]);

        return ['id' => $amenityId, 'key' => $key, 'label' => $label, 'category' => $category, 'icon' => $icon, 'enabled' => true];
    }

    public function deleteAmenity(string $amenityKey): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM amenities WHERE `key` = ?");
        $stmt->execute([$amenityKey]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new InvalidArgumentException("Amenity key '{$amenityKey}' not found.");
        }
        $amenityId = (int)$row['id'];
        $stmt = $db->prepare("DELETE FROM property_amenities WHERE amenity_id = ?");
        $stmt->execute([$amenityId]);
        $stmt = $db->prepare("UPDATE amenities SET active = 0 WHERE id = ?");
        $stmt->execute([$amenityId]);
    }

    private function generateAmenityKey(string $label): string
    {
        $key = strtolower(trim($label));
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);
        $key = trim($key, '_');
        $key = preg_replace('/_+/', '_', $key);
        return $key;
    }
}
