-- 003_create_operations_tables.sql
CREATE TABLE room_status_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_id BIGINT UNSIGNED NOT NULL,
  status ENUM('clean','dirty','inspect','out_of_order') NOT NULL,
  changed_by BIGINT UNSIGNED NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_room_status_log_room_id (room_id),
  INDEX idx_room_status_log_changed_by (changed_by),
  INDEX idx_room_status_log_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tasks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  assigned_to BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  related_room_id BIGINT UNSIGNED NULL,
  type VARCHAR(150) NOT NULL,
  priority ENUM('urgent','normal') NOT NULL DEFAULT 'normal',
  status ENUM('open','in_progress','completed','cancelled') NOT NULL DEFAULT 'open',
  description TEXT NULL,
  due_date DATE NULL,
  completed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tasks_assigned_to (assigned_to),
  INDEX idx_tasks_created_by (created_by),
  INDEX idx_tasks_related_room_id (related_room_id),
  INDEX idx_tasks_status (status),
  INDEX idx_tasks_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vendors (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  contact_name VARCHAR(150) NULL,
  email VARCHAR(150) NULL,
  phone VARCHAR(50) NULL,
  address VARCHAR(255) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_vendors_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE maintenance_orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_id BIGINT UNSIGNED NOT NULL,
  created_by BIGINT UNSIGNED NULL,
  vendor_id BIGINT UNSIGNED NULL,
  issue_description TEXT NOT NULL,
  priority ENUM('urgent','normal') NOT NULL DEFAULT 'normal',
  status ENUM('open','in_progress','resolved','cancelled') NOT NULL DEFAULT 'open',
  scheduled_at DATETIME NULL,
  resolved_at DATETIME NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_maintenance_orders_room_id (room_id),
  INDEX idx_maintenance_orders_created_by (created_by),
  INDEX idx_maintenance_orders_vendor_id (vendor_id),
  INDEX idx_maintenance_orders_status (status),
  INDEX idx_maintenance_orders_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lost_and_found (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_name VARCHAR(200) NOT NULL,
  description TEXT NULL,
  found_at DATETIME NOT NULL,
  room_id BIGINT UNSIGNED NULL,
  guest_id BIGINT UNSIGNED NULL,
  handled_by BIGINT UNSIGNED NULL,
  status ENUM('found','claimed','discarded') NOT NULL DEFAULT 'found',
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_lost_and_found_room_id (room_id),
  INDEX idx_lost_and_found_guest_id (guest_id),
  INDEX idx_lost_and_found_handled_by (handled_by),
  INDEX idx_lost_and_found_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE incident_reports (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reported_by BIGINT UNSIGNED NULL,
  room_id BIGINT UNSIGNED NULL,
  guest_id BIGINT UNSIGNED NULL,
  incident_type VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  status ENUM('open','investigating','resolved','closed') NOT NULL DEFAULT 'open',
  resolved_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_incident_reports_reported_by (reported_by),
  INDEX idx_incident_reports_room_id (room_id),
  INDEX idx_incident_reports_guest_id (guest_id),
  INDEX idx_incident_reports_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE shift_handover_notes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id BIGINT UNSIGNED NOT NULL,
  created_by BIGINT UNSIGNED NULL,
  note TEXT NOT NULL,
  shift_date DATE NOT NULL,
  shift_type ENUM('morning','afternoon','night') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_shift_handover_notes_property_id (property_id),
  INDEX idx_shift_handover_notes_created_by (created_by),
  INDEX idx_shift_handover_notes_shift_date (shift_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE room_status_log
  ADD CONSTRAINT fk_room_status_log_room_id FOREIGN KEY (room_id)
    REFERENCES rooms(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_room_status_log_changed_by FOREIGN KEY (changed_by)
    REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE tasks
  ADD CONSTRAINT fk_tasks_assigned_to FOREIGN KEY (assigned_to)
    REFERENCES users(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_tasks_created_by FOREIGN KEY (created_by)
    REFERENCES users(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_tasks_related_room_id FOREIGN KEY (related_room_id)
    REFERENCES rooms(id) ON DELETE SET NULL;

ALTER TABLE maintenance_orders
  ADD CONSTRAINT fk_maintenance_orders_room_id FOREIGN KEY (room_id)
    REFERENCES rooms(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_maintenance_orders_created_by FOREIGN KEY (created_by)
    REFERENCES users(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_maintenance_orders_vendor_id FOREIGN KEY (vendor_id)
    REFERENCES vendors(id) ON DELETE SET NULL;

ALTER TABLE lost_and_found
  ADD CONSTRAINT fk_lost_and_found_room_id FOREIGN KEY (room_id)
    REFERENCES rooms(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_lost_and_found_guest_id FOREIGN KEY (guest_id)
    REFERENCES guests(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_lost_and_found_handled_by FOREIGN KEY (handled_by)
    REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE incident_reports
  ADD CONSTRAINT fk_incident_reports_reported_by FOREIGN KEY (reported_by)
    REFERENCES users(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_incident_reports_room_id FOREIGN KEY (room_id)
    REFERENCES rooms(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_incident_reports_guest_id FOREIGN KEY (guest_id)
    REFERENCES guests(id) ON DELETE SET NULL;

ALTER TABLE shift_handover_notes
  ADD CONSTRAINT fk_shift_handover_notes_property_id FOREIGN KEY (property_id)
    REFERENCES properties(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_shift_handover_notes_created_by FOREIGN KEY (created_by)
    REFERENCES users(id) ON DELETE SET NULL;
