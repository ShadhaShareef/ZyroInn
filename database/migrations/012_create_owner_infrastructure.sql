-- 012_create_owner_infrastructure.sql
-- Room rate overrides (date-specific pricing/inventory)
CREATE TABLE IF NOT EXISTS room_rates (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id     BIGINT UNSIGNED NOT NULL,
    date        DATE NOT NULL,
    rate        DECIMAL(10,2) DEFAULT NULL COMMENT 'Per-night price override',
    status      ENUM('available','blocked') DEFAULT NULL COMMENT 'Availability override',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_room_date (room_id, date),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Staff schedules
CREATE TABLE IF NOT EXISTS schedules (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id BIGINT UNSIGNED NOT NULL,
    user_id     BIGINT UNSIGNED NOT NULL,
    date        DATE NOT NULL,
    start_time  TIME NOT NULL,
    end_time    TIME NOT NULL,
    role        VARCHAR(50) DEFAULT NULL COMMENT 'Snapshot of role at schedule time',
    status      ENUM('confirmed','pending','cancelled') DEFAULT 'pending',
    notes       TEXT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_date (date),
    INDEX idx_property_date (property_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
