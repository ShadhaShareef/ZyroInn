-- 017_add_group_id_to_bookings.sql
ALTER TABLE bookings ADD COLUMN group_id VARCHAR(36) NULL AFTER id, ADD INDEX idx_bookings_group_id (group_id);
