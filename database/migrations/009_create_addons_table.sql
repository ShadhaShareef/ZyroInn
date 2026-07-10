-- 009_create_addons_table.sql
CREATE TABLE addons (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  unit ENUM('per_stay','per_night','per_guest','per_guest_night','per_transfer') NOT NULL DEFAULT 'per_stay',
  icon VARCHAR(10) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_addons_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO addons (`key`, name, description, price, unit, icon, is_active) VALUES
('breakfast', 'Breakfast Package', 'Daily premium buffet breakfast for all room guests.', 15.00, 'per_guest_night', '🍳', 1),
('spa', 'Full Spa Access', 'Access to standard massage and thermal pools.', 80.00, 'per_stay', '💆‍♀️', 1),
('shuttle', 'Airport Shuttle Transfer', 'Private airport pickup or drop-off service.', 45.00, 'per_transfer', '🚐', 1);
