-- 016_extend_fnb_for_services.sql
-- Extend F&B system to support time-slot-based service bookings (spa, fitness, etc.)

ALTER TABLE fnb_menu_items
  MODIFY COLUMN category ENUM('starter', 'main_course', 'dessert', 'beverage', 'other', 'spa', 'wellness', 'fitness', 'activity', 'transport') NOT NULL DEFAULT 'other',
  ADD COLUMN duration_minutes INT UNSIGNED NULL AFTER price,
  ADD COLUMN max_capacity INT UNSIGNED NULL AFTER duration_minutes;

ALTER TABLE fnb_orders
  MODIFY COLUMN order_type ENUM('room_service', 'restaurant', 'service') NOT NULL,
  ADD COLUMN scheduled_at DATETIME NULL AFTER order_type,
  ADD COLUMN duration_minutes INT UNSIGNED NULL AFTER scheduled_at,
  ADD COLUMN assignee VARCHAR(255) NULL AFTER duration_minutes;

-- Seed service menu items for property 1
INSERT INTO fnb_menu_items (property_id, name, description, price, duration_minutes, max_capacity, category, available) VALUES
(1, 'Swedish Massage', 'Full-body relaxation massage with essential oils. 60 minutes.', 75.00, 60, 1, 'spa', 1),
(1, 'Deep Tissue Massage', 'Targeted muscle relief for deep tension. 60 minutes.', 95.00, 60, 1, 'spa', 1),
(1, 'Facial Treatment', 'Rejuvenating facial with organic products. 45 minutes.', 65.00, 45, 1, 'spa', 1),
(1, 'Manicure & Pedicure', 'Complete nail care and polish. 60 minutes.', 45.00, 60, 2, 'spa', 1),
(1, 'Yoga Session', 'Group yoga class for all skill levels. 60 minutes.', 25.00, 60, 10, 'wellness', 1),
(1, 'Personal Training', 'One-on-one fitness session with a certified trainer. 45 minutes.', 50.00, 45, 1, 'fitness', 1),
(1, 'Cooking Class', 'Learn local cuisine with our head chef. 90 minutes.', 40.00, 90, 8, 'activity', 1),
(1, 'Airport Transfer', 'Private luxury car airport pickup or drop-off.', 55.00, NULL, 4, 'transport', 1);
