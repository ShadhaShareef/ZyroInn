-- 005_add_scope_and_new_amenities.sql

-- Add columns scope and icon to amenities table
ALTER TABLE amenities ADD COLUMN scope ENUM('property', 'room') NOT NULL DEFAULT 'property';
ALTER TABLE amenities ADD COLUMN icon VARCHAR(50) NULL;

-- Update the existing amenities seeded in 001/seeds
UPDATE amenities SET scope = 'room', icon = '💎' WHERE `key` = 'room_type_deluxe';
UPDATE amenities SET scope = 'room', icon = '🛏️' WHERE `key` = 'room_type_standard';
UPDATE amenities SET scope = 'property', icon = '📶' WHERE `key` = 'amenity_wifi';
UPDATE amenities SET scope = 'property', icon = '🥐' WHERE `key` = 'amenity_breakfast';
UPDATE amenities SET scope = 'property', icon = '💆' WHERE `key` = 'amenity_spa';
UPDATE amenities SET scope = 'property', icon = '🏊' WHERE `key` = 'amenity_pool';
UPDATE amenities SET scope = 'property', icon = '🏨' WHERE `key` = 'property_type_boutique';

-- Insert new property-level amenities
INSERT INTO amenities (`key`, label, category, description, scope, icon, active) VALUES
('amenity_gym', 'Gym', 'Amenities', 'On-site fitness center and gym equipment.', 'property', '🏋️', 1),
('amenity_jacuzzi', 'Jacuzzi', 'Amenities', 'Luxury jacuzzi hot tub facilities.', 'property', '🛁', 1),
('amenity_turf', 'Turf', 'Amenities', 'Outdoor sports turf and recreation area.', 'property', '🌱', 1),
('amenity_play_area', 'Play Area', 'Amenities', 'Dedicated children play area.', 'property', '🛝', 1),
('amenity_hot_water_24hr', '24hr Hot Water', 'Amenities', 'Continuous 24-hour hot water supply.', 'property', '🔥', 1);

-- Insert new room-level amenities
INSERT INTO amenities (`key`, label, category, description, scope, icon, active) VALUES
('amenity_ac', 'AC', 'Amenities', 'Air conditioning in room.', 'room', '❄️', 1),
('amenity_non_ac', 'Non-AC', 'Amenities', 'Non-air conditioned room.', 'room', '💨', 1),
('amenity_bathtub', 'Bathtub', 'Amenities', 'Private bathtub in the bathroom.', 'room', '🛁', 1),
('room_type_pillar_cottage', 'Pillar Cottage', 'Room Type', 'Traditional pillar cottage style room.', 'room', '🛖', 1);

-- Seed an Owner user
-- Password is 'owner123'
INSERT INTO users (property_id, role, first_name, last_name, email, password_hash, phone, status)
VALUES
(NULL, 'owner', 'Suresh', 'Patel', 'suresh.owner@example.com', '$2y$10$JehY68O1QGLr2pu3d0p9POLkvQd4RL/gafVAwjV7te8pL2GwLLmSW', '+1-555-0199', 'active');
