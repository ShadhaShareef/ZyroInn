-- 004_create_reviews_loyalty_tables.sql
CREATE TABLE reviews (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  guest_id BIGINT UNSIGNED NOT NULL,
  property_id BIGINT UNSIGNED NOT NULL,
  room_id BIGINT UNSIGNED NULL,
  rating TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
  title VARCHAR(255) NULL,
  body TEXT NULL,
  review_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_reviews_guest_id (guest_id),
  INDEX idx_reviews_property_id (property_id),
  INDEX idx_reviews_room_id (room_id),
  INDEX idx_reviews_review_date (review_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE loyalty_accounts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  guest_id BIGINT UNSIGNED NOT NULL,
  points_balance BIGINT UNSIGNED NOT NULL DEFAULT 0,
  tier ENUM('bronze','silver','gold','platinum') NOT NULL DEFAULT 'bronze',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_loyalty_accounts_guest_id (guest_id),
  INDEX idx_loyalty_accounts_guest_id (guest_id),
  INDEX idx_loyalty_accounts_tier (tier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE loyalty_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  loyalty_account_id BIGINT UNSIGNED NOT NULL,
  booking_id BIGINT UNSIGNED NULL,
  points_change INT NOT NULL,
  transaction_type ENUM('earn','redeem','adjustment') NOT NULL,
  description VARCHAR(255) NULL,
  occurred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_loyalty_transactions_loyalty_account_id (loyalty_account_id),
  INDEX idx_loyalty_transactions_booking_id (booking_id),
  INDEX idx_loyalty_transactions_transaction_type (transaction_type),
  INDEX idx_loyalty_transactions_occurred_at (occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE reviews
  ADD CONSTRAINT fk_reviews_guest_id FOREIGN KEY (guest_id)
    REFERENCES guests(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_reviews_property_id FOREIGN KEY (property_id)
    REFERENCES properties(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_reviews_room_id FOREIGN KEY (room_id)
    REFERENCES rooms(id) ON DELETE SET NULL;

ALTER TABLE loyalty_accounts
  ADD CONSTRAINT fk_loyalty_accounts_guest_id FOREIGN KEY (guest_id)
    REFERENCES guests(id) ON DELETE CASCADE;

ALTER TABLE loyalty_transactions
  ADD CONSTRAINT fk_loyalty_transactions_loyalty_account_id FOREIGN KEY (loyalty_account_id)
    REFERENCES loyalty_accounts(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_loyalty_transactions_booking_id FOREIGN KEY (booking_id)
    REFERENCES bookings(id) ON DELETE SET NULL;
