-- 007_add_fnb_and_inventory_tables.sql

-- F&B Menu Items
CREATE TABLE fnb_menu_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  price DECIMAL(10, 2) NOT NULL,
  category ENUM('starter', 'main_course', 'dessert', 'beverage', 'other') NOT NULL DEFAULT 'other',
  available TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_fnb_menu_items_property_id (property_id),
  INDEX idx_fnb_menu_items_available (available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- F&B Orders
CREATE TABLE fnb_orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id BIGINT UNSIGNED NOT NULL,
  booking_id BIGINT UNSIGNED NULL, -- NULL for restaurant walk-in
  table_number VARCHAR(50) NULL, -- NULL for room service
  order_type ENUM('room_service', 'restaurant') NOT NULL,
  status ENUM('pending', 'preparing', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
  notes TEXT NULL,
  total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_fnb_orders_property_id (property_id),
  INDEX idx_fnb_orders_booking_id (booking_id),
  INDEX idx_fnb_orders_status (status),
  INDEX idx_fnb_orders_order_type (order_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- F&B Order Items
CREATE TABLE fnb_order_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  menu_item_id BIGINT UNSIGNED NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10, 2) NOT NULL, -- price at ordering time
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_fnb_order_items_order_id (order_id),
  INDEX idx_fnb_order_items_menu_item_id (menu_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- F&B Stock & Waste Log
CREATE TABLE fnb_stock_waste_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id BIGINT UNSIGNED NOT NULL,
  item_name VARCHAR(255) NOT NULL,
  type ENUM('stock_in', 'stock_out', 'waste') NOT NULL,
  quantity DECIMAL(10, 2) NOT NULL,
  unit VARCHAR(50) NOT NULL,
  reason VARCHAR(255) NULL,
  logged_by BIGINT UNSIGNED NULL,
  logged_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_fnb_stock_waste_log_property_id (property_id),
  INDEX idx_fnb_stock_waste_log_logged_by (logged_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Housekeeping Linen & Amenity Inventory
CREATE TABLE linen_amenity_inventory (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id BIGINT UNSIGNED NOT NULL,
  item_name VARCHAR(255) NOT NULL,
  category ENUM('linen', 'amenity', 'cleaning_supply', 'other') NOT NULL DEFAULT 'other',
  quantity INT NOT NULL DEFAULT 0,
  min_required INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_linen_amenity_inventory_property_id (property_id),
  INDEX idx_linen_amenity_inventory_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add Constraints
ALTER TABLE fnb_menu_items
  ADD CONSTRAINT fk_fnb_menu_items_property_id FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE;

ALTER TABLE fnb_orders
  ADD CONSTRAINT fk_fnb_orders_property_id FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_fnb_orders_booking_id FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL;

ALTER TABLE fnb_order_items
  ADD CONSTRAINT fk_fnb_order_items_order_id FOREIGN KEY (order_id) REFERENCES fnb_orders(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_fnb_order_items_menu_item_id FOREIGN KEY (menu_item_id) REFERENCES fnb_menu_items(id) ON DELETE CASCADE;

ALTER TABLE fnb_stock_waste_log
  ADD CONSTRAINT fk_fnb_stock_waste_log_property_id FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_fnb_stock_waste_log_logged_by FOREIGN KEY (logged_by) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE linen_amenity_inventory
  ADD CONSTRAINT fk_linen_amenity_inventory_property_id FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE;

-- Insert F&B Staff User
INSERT INTO users (property_id, role, first_name, last_name, email, password_hash, phone, status)
VALUES
(1, 'fnb', 'Vikram', 'Malhotra', 'vikram.malhotra@example.com', '$2y$10$examplehashfnbstaff', '+1-555-0114', 'active');

-- Seed F&B Menu Items
INSERT INTO fnb_menu_items (property_id, name, description, price, category, available) VALUES
(1, 'Classic Club Sandwich', 'Toasted triple-decker with chicken, bacon, lettuce, tomato, and mayo.', 14.50, 'main_course', 1),
(1, 'Margherita Pizza', 'Fresh mozzarella, tomatoes, and basil on thin crust.', 16.00, 'main_course', 1),
(1, 'Garlic Bread', 'Warm baguettes toasted with garlic butter and parsley.', 6.50, 'starter', 1),
(1, 'Chocolate Brownie', 'Warm chocolate brownie served with vanilla ice cream.', 8.00, 'dessert', 1),
(1, 'Fresh Orange Juice', 'Squeezed fresh daily.', 5.00, 'beverage', 1),
(1, 'Local Craft Beer', 'Refreshing pale ale from a local brewery.', 7.00, 'beverage', 1);

-- Seed Housekeeping Inventory
INSERT INTO linen_amenity_inventory (property_id, item_name, category, quantity, min_required) VALUES
(1, 'King Bed Sheets', 'linen', 45, 30),
(1, 'Pillowcases', 'linen', 80, 60),
(1, 'Bath Towels', 'linen', 12, 40), -- Below min required!
(1, 'Hand Towels', 'linen', 55, 40),
(1, 'Shampoo Bottles (Mini)', 'amenity', 150, 100),
(1, 'Conditioner Bottles (Mini)', 'amenity', 80, 100), -- Below min required!
(1, 'Body Wash (Mini)', 'amenity', 200, 100),
(1, 'All-Purpose Cleaner', 'cleaning_supply', 8, 5);

-- Seed F&B Orders
INSERT INTO fnb_orders (id, property_id, booking_id, table_number, order_type, status, notes, total_amount, created_at) VALUES
(1, 1, 1, NULL, 'room_service', 'preparing', 'Deliver immediately. Guest wants extra ketchup.', 33.50, DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(2, 1, NULL, 'Table 4', 'restaurant', 'pending', 'No ice in drinks.', 21.00, DATE_SUB(NOW(), INTERVAL 10 MINUTE)),
(3, 1, 1, NULL, 'room_service', 'delivered', 'Room 101. Standard checkout addon billing.', 22.50, DATE_SUB(NOW(), INTERVAL 2 HOUR));

-- Seed F&B Order Items
INSERT INTO fnb_order_items (order_id, menu_item_id, quantity, price) VALUES
(1, 1, 1, 14.50), -- Club Sandwich
(1, 2, 1, 16.00), -- Margherita Pizza
(1, 5, 1, 5.00),  -- Orange Juice
(2, 2, 1, 16.00), -- Margherita Pizza
(2, 5, 1, 5.00),  -- Orange Juice
(3, 1, 1, 14.50), -- Club Sandwich
(3, 4, 1, 8.00);  -- Brownie

-- Seed Stock & Waste Log
INSERT INTO fnb_stock_waste_log (property_id, item_name, type, quantity, unit, reason, logged_by, logged_at) VALUES
(1, 'Fresh Oranges', 'stock_in', 25.00, 'kg', 'Weekly fresh juice supply', 4, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'Oranges (Spoiled)', 'waste', 3.50, 'kg', 'Molded in storage', 4, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(1, 'Margherita Dough', 'stock_in', 30.00, 'pieces', 'Prepared by morning prep cook', 4, DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(1, 'Pizza Dough (Dropped)', 'waste', 1.00, 'pieces', 'Dropped on kitchen floor', 4, DATE_SUB(NOW(), INTERVAL 1 HOUR));
