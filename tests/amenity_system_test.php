<?php
// amenity_system_test.php - Automated Test Suite for Adaptive Amenity System

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\AmenityService;
use App\Services\Database;

class AmenitySystemTest {
    private AmenityService $service;
    private PDO $db;

    public function __construct() {
        $this->service = new AmenityService();
        $this->db = Database::getConnection();
    }

    public function run() {
        echo "\033[36m====================================================\033[0m\n";
        echo "\033[36mRunning ZyroInn Adaptive Amenity System Tests...\033[0m\n";
        echo "\033[36m====================================================\033[0m\n";

        try {
            $this->testInitialState();
            $this->testPropertyToggling();
            $this->testRoomToggling();
            $this->testInvalidKeyThrowsException();
            $this->testInvalidEntityIdThrowsException();

            echo "\n\033[32;1m🎉 ALL TESTS PASSED SUCCESSFULLY! 🎉\033[0m\n";
            echo "\033[32mThe Adaptive Amenity System is fully functional and ready.\033[0m\n";
        } catch (Exception $e) {
            echo "\n\033[31;1m❌ TEST SUITE FAILED! ❌\033[0m\n";
            echo "\033[31mError: " . $e->getMessage() . "\033[0m\n";
            echo $e->getTraceAsString() . "\n";
            exit(1);
        }
    }

    private function testInitialState() {
        echo "\nRunning Case 1: Verifying Initial Seeded State...\n";
        
        // Property 1 (Trial Ocean View Hotel) has:
        // - Wifi (enabled)
        // - Breakfast (enabled)
        // - Pool (enabled)
        // - Spa (disabled)
        $propertyAmenities = $this->service->getEnabledAmenitiesForProperty(1);
        
        $enabledKeys = $this->flattenGroupedKeys($propertyAmenities);
        $this->assert(in_array('amenity_wifi', $enabledKeys), "Initial: Wifi should be enabled for property 1");
        $this->assert(in_array('amenity_breakfast', $enabledKeys), "Initial: Breakfast should be enabled for property 1");
        $this->assert(in_array('amenity_pool', $enabledKeys), "Initial: Pool should be enabled for property 1");
        $this->assert(!in_array('amenity_spa', $enabledKeys), "Initial: Spa should NOT be enabled for property 1");

        // Room 1 (Deluxe Sea View) has:
        // - Deluxe Room type (enabled)
        // - Wifi (enabled)
        // - Breakfast (enabled)
        $room1Amenities = $this->service->getEnabledAmenitiesForRoom(1);
        $room1Keys = array_column($room1Amenities, 'key');
        $this->assert(in_array('room_type_deluxe', $room1Keys), "Initial: Room 1 should be Deluxe type");
        $this->assert(in_array('amenity_wifi', $room1Keys), "Initial: Room 1 should have Wifi enabled");

        // Room 2 (Standard Garden View) has:
        // - Standard Room type (enabled)
        // - Wifi (enabled)
        // - Breakfast (disabled)
        $room2Amenities = $this->service->getEnabledAmenitiesForRoom(2);
        $room2Keys = array_column($room2Amenities, 'key');
        $this->assert(in_array('room_type_standard', $room2Keys), "Initial: Room 2 should be Standard type");
        $this->assert(!in_array('amenity_breakfast', $room2Keys), "Initial: Room 2 should NOT have breakfast enabled");
        
        echo "\033[32m✓ Case 1 Passed.\033[0m\n";
    }

    private function testPropertyToggling() {
        echo "\nRunning Case 2: Toggling Property-Level Amenities...\n";

        // Enable Spa Access for Property 1
        $this->service->toggleAmenity(1, 'amenity_spa', true);
        $propertyAmenities = $this->service->getEnabledAmenitiesForProperty(1);
        $enabledKeys = $this->flattenGroupedKeys($propertyAmenities);
        $this->assert(in_array('amenity_spa', $enabledKeys), "Property: Spa should now be enabled after toggle true");

        // Disable Wifi for Property 1
        $this->service->toggleAmenity(1, 'amenity_wifi', false);
        $propertyAmenities = $this->service->getEnabledAmenitiesForProperty(1);
        $enabledKeys = $this->flattenGroupedKeys($propertyAmenities);
        $this->assert(!in_array('amenity_wifi', $enabledKeys), "Property: Wifi should now be disabled after toggle false");

        // Reset state back
        $this->service->toggleAmenity(1, 'amenity_spa', false);
        $this->service->toggleAmenity(1, 'amenity_wifi', true);

        echo "\033[32m✓ Case 2 Passed.\033[0m\n";
    }

    private function testRoomToggling() {
        echo "\nRunning Case 3: Toggling Room-Level Amenities...\n";

        // Enable AC for Room 1
        $this->service->toggleAmenity(1, 'amenity_ac', true);
        $roomAmenities = $this->service->getEnabledAmenitiesForRoom(1);
        $keys = array_column($roomAmenities, 'key');
        $this->assert(in_array('amenity_ac', $keys), "Room: AC should now be enabled for Room 1 after toggle true");

        // Toggle room-scoped amenity 'amenity_bathtub' for Room 2
        $this->service->toggleAmenity(2, 'amenity_bathtub', true);
        $room2Amenities = $this->service->getEnabledAmenitiesForRoom(2);
        $room2Keys = array_column($room2Amenities, 'key');
        $this->assert(in_array('amenity_bathtub', $room2Keys), "Room: Bathtub should now be enabled for Room 2");

        // Reset state back
        $this->service->toggleAmenity(1, 'amenity_ac', false);
        $this->service->toggleAmenity(2, 'amenity_bathtub', false);

        echo "\033[32m✓ Case 3 Passed.\033[0m\n";
    }

    private function testInvalidKeyThrowsException() {
        echo "\nRunning Case 4: Testing Invalid Amenity Key handling...\n";
        
        try {
            $this->service->toggleAmenity(1, 'non_existent_amenity_key', true);
            $this->assert(false, "Should have thrown an exception for invalid amenity key.");
        } catch (InvalidArgumentException $e) {
            $this->assert(str_contains($e->getMessage(), "invalid or inactive"), "Exception message should explain key is invalid");
        }
        
        echo "\033[32m✓ Case 4 Passed (Exception correctly thrown).\033[0m\n";
    }

    private function testInvalidEntityIdThrowsException() {
        echo "\nRunning Case 5: Testing Non-Existent Property/Room ID handling...\n";
        
        try {
            // 999999 is a non-existent property
            $this->service->toggleAmenity(999999, 'amenity_gym', true);
            $this->assert(false, "Should have thrown an exception for invalid property ID.");
        } catch (InvalidArgumentException $e) {
            $this->assert(str_contains($e->getMessage(), "not found"), "Exception message should explain property is not found");
        }
        
        echo "\033[32m✓ Case 5 Passed (Exception correctly thrown).\033[0m\n";
    }

    private function assert(bool $condition, string $message) {
        if (!$condition) {
            throw new Exception("Assertion Failed: " . $message);
        }
    }

    private function flattenGroupedKeys(array $grouped): array {
        $keys = [];
        foreach ($grouped as $cat => $items) {
            foreach ($items as $item) {
                $keys[] = $item['key'];
            }
        }
        return $keys;
    }
}

// Instantiate and run tests
$testSuite = new AmenitySystemTest();
$testSuite->run();
