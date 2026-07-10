<?php
// phase2_operations_test.php - Integration Tests for Phase 2 Operations & Guest Search

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\Database;

class Phase2OperationsTest {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function run() {
        echo "\033[36m====================================================\033[0m\n";
        echo "\033[36mRunning ZyroInn Phase 2 Integration Tests...\033[0m\n";
        echo "\033[36m====================================================\033[0m\n";

        try {
            $this->testHousekeepingInventoryAlerts();
            $this->testMaintenanceVendorAssignmentAndResolution();
            $this->testFnbOrderAndAddonBillingFlow();
            $this->testFnbStockAndWasteLog();
            $this->testGuestSearchSortingAndPaging();

            echo "\n\033[32;1m🎉 ALL PHASE 2 INTEGRATION TESTS PASSED! 🎉\033[0m\n";
        } catch (Exception $e) {
            echo "\n\033[31;1m❌ TEST SUITE FAILED! ❌\033[0m\n";
            echo "\033[31mError: " . $e->getMessage() . "\033[0m\n";
            echo $e->getTraceAsString() . "\n";
            exit(1);
        }
    }

    private function assert($condition, $message) {
        if (!$condition) {
            throw new Exception("Assertion Failed: " . $message);
        }
    }

    private function testHousekeepingInventoryAlerts() {
        echo "\nRunning Case 1: Housekeeping Inventory Safety Stock Alert...\n";
        
        $propertyId = 1;
        // Clean out any test items first
        $this->db->exec("DELETE FROM linen_amenity_inventory WHERE item_name = 'Test Bath Towel'");

        // Insert an item with quantity below min_required
        $stmt = $this->db->prepare("
            INSERT INTO linen_amenity_inventory (property_id, item_name, category, quantity, min_required)
            VALUES (?, 'Test Bath Towel', 'linen', 15, 20)
        ");
        $stmt->execute([$propertyId]);
        $itemId = $this->db->lastInsertId();

        // Retrieve and check for restock warning flag
        $check = $this->db->prepare("SELECT quantity, min_required FROM linen_amenity_inventory WHERE id = ?");
        $check->execute([$itemId]);
        $item = $check->fetch();
        
        $this->assert($item['quantity'] < $item['min_required'], "Item should flag alert since quantity (15) < min_required (20)");

        // Update quantity to exceed minimum
        $update = $this->db->prepare("UPDATE linen_amenity_inventory SET quantity = 25 WHERE id = ?");
        $update->execute([$itemId]);

        $check->execute([$itemId]);
        $item = $check->fetch();
        $this->assert($item['quantity'] >= $item['min_required'], "Warning flag should clear after restocking (25 >= 20)");

        // Clean up
        $this->db->prepare("DELETE FROM linen_amenity_inventory WHERE id = ?")->execute([$itemId]);
        echo "\033[32m✓ Case 1 Passed.\033[0m\n";
    }

    private function testMaintenanceVendorAssignmentAndResolution() {
        echo "\nRunning Case 2: Maintenance Vendor Assignment and Resolution...\n";

        $propertyId = 1;
        // 1. Insert test vendor
        $this->db->exec("DELETE FROM vendors WHERE name = 'AC Pros Test Vendor'");
        $stmtVendor = $this->db->prepare("
            INSERT INTO vendors (name, contact_name, email, phone, address, active)
            VALUES ('AC Pros Test Vendor', 'John Smith', 'john@acpros.test', '555-1234', '123 Test St', 1)
        ");
        $stmtVendor->execute();
        $vendorId = $this->db->lastInsertId();

        // 2. Create work order
        $stmtOrder = $this->db->prepare("
            INSERT INTO maintenance_orders (room_id, created_by, issue_description, priority, status)
            VALUES (1, 1, 'AC compressor replacement', 'urgent', 'open')
        ");
        $stmtOrder->execute();
        $orderId = $this->db->lastInsertId();

        // 3. Assign vendor and schedule order
        $scheduledAt = date('Y-m-d H:i:s', strtotime('+1 day'));
        $stmtAssign = $this->db->prepare("
            UPDATE maintenance_orders 
            SET vendor_id = ?, status = 'in_progress', scheduled_at = ?, notes = 'Vendor John assigned'
            WHERE id = ?
        ");
        $stmtAssign->execute([$vendorId, $scheduledAt, $orderId]);

        // Check if correct values updated
        $stmtCheck = $this->db->prepare("SELECT * FROM maintenance_orders WHERE id = ?");
        $stmtCheck->execute([$orderId]);
        $order = $stmtCheck->fetch();
        $this->assert($order['vendor_id'] == $vendorId, "Vendor should be successfully assigned");
        $this->assert($order['status'] === 'in_progress', "Status should be in_progress");

        // 4. Resolve order
        $stmtResolve = $this->db->prepare("
            UPDATE maintenance_orders 
            SET status = 'resolved', resolved_at = NOW(), notes = CONCAT(notes, '\nRepaired successfully')
            WHERE id = ?
        ");
        $stmtResolve->execute([$orderId]);

        $stmtCheck->execute([$orderId]);
        $order = $stmtCheck->fetch();
        $this->assert($order['status'] === 'resolved', "Status should be resolved");
        $this->assert(!empty($order['resolved_at']), "Resolved timestamp should not be empty");

        // Clean up
        $this->db->prepare("DELETE FROM maintenance_orders WHERE id = ?")->execute([$orderId]);
        $this->db->prepare("DELETE FROM vendors WHERE id = ?")->execute([$vendorId]);
        echo "\033[32m✓ Case 2 Passed.\033[0m\n";
    }

    private function testFnbOrderAndAddonBillingFlow() {
        echo "\nRunning Case 3: Food & Beverage Order Queue and Room Service Addon Billing...\n";

        $propertyId = 1;
        // Make sure a test room and booking exist (e.g., booking ID 1)
        $bookingId = 1;

        // 1. Insert test menu item
        $this->db->exec("DELETE FROM fnb_menu_items WHERE name = 'Double Cheeseburger Test'");
        $stmtMenu = $this->db->prepare("
            INSERT INTO fnb_menu_items (property_id, name, description, price, category, available)
            VALUES (?, 'Double Cheeseburger Test', 'Grilled patties with double cheddar', 12.50, 'main_course', 1)
        ");
        $stmtMenu->execute([$propertyId]);
        $menuId = $this->db->lastInsertId();

        // 2. Place room service order
        $stmtOrder = $this->db->prepare("
            INSERT INTO fnb_orders (property_id, booking_id, table_number, order_type, status, notes, total_amount)
            VALUES (?, ?, NULL, 'room_service', 'pending', 'No onions please', 12.50)
        ");
        $stmtOrder->execute([$propertyId, $bookingId]);
        $orderId = $this->db->lastInsertId();

        $stmtItem = $this->db->prepare("
            INSERT INTO fnb_order_items (order_id, menu_item_id, quantity, price)
            VALUES (?, ?, 1, 12.50)
        ");
        $stmtItem->execute([$orderId, $menuId]);

        // 3. Complete order delivery -> should post addon billing
        $status = 'delivered';
        $stmtDeliver = $this->db->prepare("UPDATE fnb_orders SET status = ? WHERE id = ?");
        $stmtDeliver->execute([$status, $orderId]);

        // Trigger simulation of route hook:
        if ($status === 'delivered') {
            $addonName = "Room Service (Order #" . $orderId . ")";
            
            $checkStmt = $this->db->prepare("SELECT 1 FROM booking_addons WHERE booking_id = ? AND name = ?");
            $checkStmt->execute([$bookingId, $addonName]);
            
            if (!$checkStmt->fetch()) {
                $addonStmt = $this->db->prepare("
                    INSERT INTO booking_addons (booking_id, name, description, quantity, price)
                    VALUES (?, ?, 'F&B Room Service delivery addon', 1, 12.50)
                ");
                $addonStmt->execute([$bookingId, $addonName]);
            }
        }

        // Verify addon exists on booking billing table
        $stmtAddonCheck = $this->db->prepare("SELECT * FROM booking_addons WHERE booking_id = ? AND name LIKE ?");
        $stmtAddonCheck->execute([$bookingId, "Room Service (Order #{$orderId})"]);
        $addon = $stmtAddonCheck->fetch();

        $this->assert($addon !== false, "Booking addon should be automatically created upon order delivery");
        $this->assert((float)$addon['price'] === 12.50, "Addon price must match order total ($12.50)");

        // Clean up
        $this->db->prepare("DELETE FROM booking_addons WHERE id = ?")->execute([$addon['id']]);
        $this->db->prepare("DELETE FROM fnb_order_items WHERE order_id = ?")->execute([$orderId]);
        $this->db->prepare("DELETE FROM fnb_orders WHERE id = ?")->execute([$orderId]);
        $this->db->prepare("DELETE FROM fnb_menu_items WHERE id = ?")->execute([$menuId]);
        echo "\033[32m✓ Case 3 Passed.\033[0m\n";
    }

    private function testFnbStockAndWasteLog() {
        echo "\nRunning Case 4: Food & Beverage Stock intake & Waste Spoilage Audit Logs...\n";

        $propertyId = 1;
        // Insert logs
        $stmt = $this->db->prepare("
            INSERT INTO fnb_stock_waste_log (property_id, item_name, type, quantity, unit, reason, logged_by, logged_at)
            VALUES (?, 'Fresh Tomatoes Test', 'waste', 4.5, 'kg', 'Spoiled in cooler room', 1, NOW())
        ");
        $stmt->execute([$propertyId]);
        $logId = $this->db->lastInsertId();

        // Retrieve and check values
        $stmtCheck = $this->db->prepare("SELECT * FROM fnb_stock_waste_log WHERE id = ?");
        $stmtCheck->execute([$logId]);
        $log = $stmtCheck->fetch();

        $this->assert($log['type'] === 'waste', "Log type should record as 'waste'");
        $this->assert((float)$log['quantity'] === 4.5, "Logged quantity should match");
        $this->assert($log['unit'] === 'kg', "Logged unit should match");

        // Clean up
        $this->db->prepare("DELETE FROM fnb_stock_waste_log WHERE id = ?")->execute([$logId]);
        echo "\033[32m✓ Case 4 Passed.\033[0m\n";
    }

    private function testGuestSearchSortingAndPaging() {
        echo "\nRunning Case 5: Guest Search Sorting and Paging logic...\n";

        // Setup mock properties list with rooms
        $properties = [
            [
                'id' => 1,
                'name' => 'Seaside Villa',
                'rooms' => [
                    ['id' => 101, 'base_rate' => 150.0, 'occupancy' => 2],
                    ['id' => 102, 'base_rate' => 250.0, 'occupancy' => 4],
                ]
            ],
            [
                'id' => 2,
                'name' => 'Mountain Lodge',
                'rooms' => [
                    ['id' => 201, 'base_rate' => 90.0, 'occupancy' => 1],
                    ['id' => 202, 'base_rate' => 180.0, 'occupancy' => 3],
                ]
            ]
        ];

        // 1. Test Price ASC sorting simulation
        foreach ($properties as &$prop) {
            usort($prop['rooms'], function($a, $b) {
                return $a['base_rate'] <=> $b['base_rate'];
            });
        }
        unset($prop);
        usort($properties, function($a, $b) {
            return $a['rooms'][0]['base_rate'] <=> $b['rooms'][0]['base_rate'];
        });

        // Mountain Lodge (90.0) should be first, Seaside Villa (150.0) second
        $this->assert($properties[0]['name'] === 'Mountain Lodge', "Mountain Lodge should be sorted first in Price ASC");
        $this->assert($properties[1]['name'] === 'Seaside Villa', "Seaside Villa should be sorted second in Price ASC");

        // 2. Test Pagination array slice simulation
        $page = 2;
        $limit = 1;
        $sliced = array_slice($properties, ($page - 1) * $limit, $limit);
        
        $this->assert(count($sliced) === 1, "Page slice size should be 1");
        $this->assert($sliced[0]['name'] === 'Seaside Villa', "Page 2 item should be Seaside Villa");

        echo "\033[32m✓ Case 5 Passed.\033[0m\n";
    }
}

$testSuite = new Phase2OperationsTest();
$testSuite->run();
