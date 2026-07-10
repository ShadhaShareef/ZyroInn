-- 001_create_core_tables.sql
CREATE TABLE properties (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  code VARCHAR(100) NOT NULL UNIQUE,
  address VARCHAR(255) NOT NULL,
  city VARCHAR(100) NOT NULL,
  state VARCHAR(100) NOT NULL,
  country VARCHAR(100) NOT NULL,
  postal_code VARCHAR(20) NOT NULL,
  phone VARCHAR(50) NULL,
  email VARCHAR(150) NULL,
  time_zone VARCHAR(100) NOT NULL DEFAULT 'UTC',
  description TEXT NULL,
  status ENUM('active','inactive','closed') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_properties_status (status),
  INDEX idx_properties_city (city),
  INDEX idx_properties_state (state)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rooms (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id BIGINT UNSIGNED NOT NULL,
  room_number VARCHAR(50) NOT NULL,
  room_type VARCHAR(100) NOT NULL,
  occupancy SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  bed_count SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  ac TINYINT(1) NOT NULL DEFAULT 1,
  base_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status ENUM('available','occupied','reserved','maintenance','out_of_service') NOT NULL DEFAULT 'available',
  description TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_rooms_property_room_number (property_id, room_number),
  INDEX idx_rooms_property_id (property_id),
  INDEX idx_rooms_room_type (room_type),
  INDEX idx_rooms_status (status),
  INDEX idx_rooms_ac (ac)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE amenities (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(100) NOT NULL UNIQUE,
  label VARCHAR(150) NOT NULL,
  category VARCHAR(100) NOT NULL,
  description TEXT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_amenities_category (category),
  INDEX idx_amenities_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE property_amenities (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id BIGINT UNSIGNED NOT NULL,
  amenity_id BIGINT UNSIGNED NOT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_property_amenities_property_amenity (property_id, amenity_id),
  INDEX idx_property_amenities_property_id (property_id),
  INDEX idx_property_amenities_amenity_id (amenity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE room_amenities (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_id BIGINT UNSIGNED NOT NULL,
  amenity_id BIGINT UNSIGNED NOT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_room_amenities_room_amenity (room_id, amenity_id),
  INDEX idx_room_amenities_room_id (room_id),
  INDEX idx_room_amenities_amenity_id (amenity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id BIGINT UNSIGNED NULL,
  role ENUM('guest','front_office','housekeeping','maintenance','fnb','security','owner','manager','admin') NOT NULL DEFAULT 'guest',
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NULL,
  phone VARCHAR(50) NULL,
  status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_property_id (property_id),
  INDEX idx_users_role (role),
  INDEX idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE guests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  phone VARCHAR(50) NULL,
  loyalty_member_id VARCHAR(100) NULL,
  preferences JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_guests_email (email),
  INDEX idx_guests_user_id (user_id),
  INDEX idx_guests_loyalty_member_id (loyalty_member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE rooms
  ADD CONSTRAINT fk_rooms_property_id FOREIGN KEY (property_id)
    REFERENCES properties(id) ON DELETE CASCADE;

ALTER TABLE property_amenities
  ADD CONSTRAINT fk_property_amenities_property_id FOREIGN KEY (property_id)
    REFERENCES properties(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_property_amenities_amenity_id FOREIGN KEY (amenity_id)
    REFERENCES amenities(id) ON DELETE CASCADE;

ALTER TABLE room_amenities
  ADD CONSTRAINT fk_room_amenities_room_id FOREIGN KEY (room_id)
    REFERENCES rooms(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_room_amenities_amenity_id FOREIGN KEY (amenity_id)
    REFERENCES amenities(id) ON DELETE CASCADE;

ALTER TABLE users
  ADD CONSTRAINT fk_users_property_id FOREIGN KEY (property_id)
    REFERENCES properties(id) ON DELETE SET NULL;

ALTER TABLE guests
  ADD CONSTRAINT fk_guests_user_id FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE SET NULL;
