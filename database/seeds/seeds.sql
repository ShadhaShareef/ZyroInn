-- seeds.sql
-- Seed data for one 2-room property with sample amenities, users, guests, bookings, operations, and loyalty stubs.

INSERT INTO properties (name, code, address, city, state, country, postal_code, phone, email, time_zone, description, status)
VALUES
('Trial Ocean View Hotel', 'TOVH001', '123 Shoreline Drive', 'Marina Bay', 'Coastal State', 'Countryland', '12345', '+1-555-0100', 'info@trialoceanview.example', 'UTC', 'A small trial property with ocean views and a boutique guest experience.', 'active');

INSERT INTO rooms (property_id, room_number, room_type, occupancy, bed_count, ac, base_rate, status, description)
VALUES
(1, '101', 'Deluxe Sea View', 2, 1, 1, 150.00, 'available', 'Spacious sea-facing room with one king bed.'),
(1, '102', 'Standard Garden View', 2, 2, 1, 110.00, 'available', 'Cozy garden-facing room with two twin beds.');

INSERT INTO amenities (`key`, label, category, description, active)
VALUES
('room_type_deluxe', 'Deluxe Room', 'Room Type', 'Premium room type with enhanced amenities.', 1),
('room_type_standard', 'Standard Room', 'Room Type', 'Standard room category.', 1),
('amenity_wifi', 'Wi-Fi', 'Amenities', 'Complimentary guest Wi-Fi.', 1),
('amenity_breakfast', 'Breakfast Included', 'Amenities', 'Breakfast included with the stay.', 1),
('amenity_spa', 'Spa Access', 'Amenities', 'Access to on-site spa facilities.', 1),
('amenity_pool', 'Pool Access', 'Amenities', 'Access to the outdoor pool area.', 1),
('property_type_boutique', 'Boutique Hotel', 'Property Type', 'A small upscale hotel with personalized service.', 1);

INSERT INTO property_amenities (property_id, amenity_id, enabled)
VALUES
(1, 3, 1),
(1, 4, 1),
(1, 5, 0),
(1, 6, 1);

INSERT INTO room_amenities (room_id, amenity_id, enabled)
VALUES
(1, 1, 1),
(1, 3, 1),
(1, 4, 1),
(2, 2, 1),
(2, 3, 1),
(2, 4, 0);

INSERT INTO users (property_id, role, first_name, last_name, email, password_hash, phone, status)
VALUES
(1, 'front_office', 'Priya', 'Das', 'priya.das@example.com', '', '+1-555-0110', 'active'),
(1, 'housekeeping', 'Leo', 'Mendez', 'leo.mendez@example.com', '', '+1-555-0111', 'active'),
(1, 'maintenance', 'Asha', 'Reddy', 'asha.reddy@example.com', '', '+1-555-0112', 'active'),
(1, 'manager', 'Rohit', 'Sharma', 'rohit.sharma@example.com', '', '+1-555-0113', 'active');

INSERT INTO guests (user_id, first_name, last_name, email, phone, loyalty_member_id, preferences)
VALUES
(NULL, 'Maya', 'Patel', 'maya.patel@example.com', '+1-555-0120', 'LOYALTY-0001', '{"preferred_contact":"email","bed_type":"king"}');

INSERT INTO bookings (property_id, room_id, guest_id, check_in_date, check_out_date, status, source, commission_percentage, notes)
VALUES
(1, 1, 1, '2026-07-10', '2026-07-14', 'confirmed', 'direct', NULL, 'Guest booked directly through website.'),
(1, 2, 1, '2026-07-20', '2026-07-22', 'pending', 'agency', 12.50, 'Agency booking pending confirmation.');

INSERT INTO booking_addons (booking_id, name, description, quantity, price)
VALUES
(1, 'Breakfast Package', 'Daily breakfast for two guests.', 4, 15.00),
(1, 'Spa Treatment', 'One-time spa session.', 1, 80.00);

INSERT INTO payments (booking_id, amount, currency, method, status, transaction_reference, paid_at)
VALUES
(1, 240.00, 'USD', 'card', 'completed', 'TXN1001', '2026-07-01 14:15:00'),
(2, 0.00, 'USD', 'cash', 'pending', NULL, NULL);

INSERT INTO room_status_log (room_id, status, changed_by, changed_at, notes)
VALUES
(1, 'clean', 2, '2026-07-02 08:00:00', 'Room cleaned after previous guest checkout.'),
(2, 'inspect', 2, '2026-07-02 08:30:00', 'Inspecting room for maintenance needs.');

INSERT INTO tasks (assigned_to, created_by, related_room_id, type, priority, status, description, due_date)
VALUES
(2, 1, 2, 'Clean Room', 'urgent', 'open', 'Prepare room 102 for upcoming inspection.', '2026-07-02'),
(3, 1, 1, 'Fix Air Conditioning', 'normal', 'open', 'Inspect the AC unit in room 101.', '2026-07-03');

INSERT INTO vendors (name, contact_name, email, phone, address, active)
VALUES
('Seaside HVAC Services', 'Rahul Kumar', 'rahul.kumar@seasidehvac.example', '+1-555-0130', '200 Service Park, Marina Bay', 1);

INSERT INTO maintenance_orders (room_id, created_by, vendor_id, issue_description, priority, status, scheduled_at)
VALUES
(1, 3, 1, 'AC unit making unusual noise, needs inspection.', 'urgent', 'open', '2026-07-03 09:00:00');

INSERT INTO lost_and_found (item_name, description, found_at, room_id, guest_id, handled_by, status, notes)
VALUES
('Blue scarf', 'Lightweight blue scarf found in room 101.', '2026-07-02 09:15:00', 1, 1, 2, 'found', 'Logged during morning room check.');

INSERT INTO incident_reports (reported_by, room_id, guest_id, incident_type, description, status)
VALUES
(4, 2, 1, 'Unauthorized entry', 'Guest reported a stranger in the corridor near room 102.', 'investigating');

INSERT INTO shift_handover_notes (property_id, created_by, note, shift_date, shift_type)
VALUES
(1, 1, 'Night shift reported no issues; breakfast buffet refilled and ready for morning guests.', '2026-07-02', 'morning');

INSERT INTO reviews (guest_id, property_id, room_id, rating, title, body)
VALUES
(1, 1, 1, 5, 'Lovely stay', 'The view was fantastic and the staff were friendly.');

INSERT INTO loyalty_accounts (guest_id, points_balance, tier)
VALUES
(1, 1500, 'silver');

INSERT INTO loyalty_transactions (loyalty_account_id, booking_id, points_change, transaction_type, description)
VALUES
(1, 1, 1500, 'earn', 'Earned loyalty points for booking 1.');
