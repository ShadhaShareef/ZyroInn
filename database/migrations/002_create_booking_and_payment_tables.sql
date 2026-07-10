-- 002_create_booking_and_payment_tables.sql
CREATE TABLE bookings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id BIGINT UNSIGNED NOT NULL,
  room_id BIGINT UNSIGNED NOT NULL,
  guest_id BIGINT UNSIGNED NULL,
  check_in_date DATE NOT NULL,
  check_out_date DATE NOT NULL,
  status ENUM('pending','confirmed','checked_in','checked_out','cancelled') NOT NULL DEFAULT 'pending',
  source ENUM('direct','agency','walk_in','corporate','other') NOT NULL DEFAULT 'direct',
  commission_percentage DECIMAL(5,2) NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_bookings_property_id (property_id),
  INDEX idx_bookings_room_id (room_id),
  INDEX idx_bookings_guest_id (guest_id),
  INDEX idx_bookings_status (status),
  INDEX idx_bookings_source (source),
  INDEX idx_bookings_check_in_date (check_in_date),
  INDEX idx_bookings_check_out_date (check_out_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE booking_addons (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_booking_addons_booking_id (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'USD',
  method ENUM('cash','card','bank_transfer','upi','wallet','other') NOT NULL DEFAULT 'cash',
  status ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  transaction_reference VARCHAR(255) NULL,
  paid_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_payments_booking_id (booking_id),
  INDEX idx_payments_status (status),
  INDEX idx_payments_method (method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE bookings
  ADD CONSTRAINT fk_bookings_property_id FOREIGN KEY (property_id)
    REFERENCES properties(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_bookings_room_id FOREIGN KEY (room_id)
    REFERENCES rooms(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_bookings_guest_id FOREIGN KEY (guest_id)
    REFERENCES guests(id) ON DELETE SET NULL;

ALTER TABLE booking_addons
  ADD CONSTRAINT fk_booking_addons_booking_id FOREIGN KEY (booking_id)
    REFERENCES bookings(id) ON DELETE CASCADE;

ALTER TABLE payments
  ADD CONSTRAINT fk_payments_booking_id FOREIGN KEY (booking_id)
    REFERENCES bookings(id) ON DELETE CASCADE;
