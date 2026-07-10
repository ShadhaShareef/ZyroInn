-- seeds_admin.sql
-- Seed data for Platform Admin (Surface D) tables

-- Admin user (created dynamically by front controller if not found, but seed explicitly)
INSERT INTO users (role, first_name, last_name, email, password_hash, status)
SELECT 'admin', 'Admin', 'User', 'admin@zyroinn.example', '', 'active'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE role = 'admin' LIMIT 1);

INSERT INTO users (role, first_name, last_name, email, password_hash, phone, status)
SELECT 'owner', 'Suresh', 'Patel', 'suresh.patel@example.com', '', '+1-555-0140', 'active'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE role = 'owner' LIMIT 1);

-- Subscription Plans
INSERT INTO subscription_plans (name, code, description, monthly_price, max_properties, max_rooms_per_property, features, is_active) VALUES
('Starter', 'starter', 'Best for single-property owners just getting started.', 29.00, 1, 10, '{"analytics": false, "commission_tools": false, "priority_support": false}', 1),
('Growth', 'growth', 'For growing businesses with up to 3 properties.', 79.00, 3, 25, '{"analytics": true, "commission_tools": false, "priority_support": false}', 1),
('Enterprise', 'enterprise', 'Full platform access with unlimited properties.', 199.00, 999, 999, '{"analytics": true, "commission_tools": true, "priority_support": true}', 1);

-- Property Subscription for property 1
INSERT INTO property_subscriptions (property_id, plan_id, status, billing_cycle, start_date, next_billing_date)
SELECT 1, id, 'active', 'monthly', '2026-01-01', DATE_ADD('2026-07-01', INTERVAL 1 MONTH)
FROM subscription_plans WHERE code = 'enterprise';

-- Subscription Invoices for property 1
INSERT INTO subscription_invoices (subscription_id, property_id, amount, currency, status, period_start, period_end, issued_at, paid_at, transaction_reference)
SELECT ps.id, 1, sp.monthly_price, 'USD', 'paid', '2026-06-01', '2026-06-30', '2026-06-01 00:00:00', '2026-06-01 00:00:00', 'INV-001-2026-06'
FROM property_subscriptions ps
JOIN subscription_plans sp ON ps.plan_id = sp.id
WHERE ps.property_id = 1
LIMIT 1;

INSERT INTO subscription_invoices (subscription_id, property_id, amount, currency, status, period_start, period_end, issued_at, paid_at, transaction_reference)
SELECT ps.id, 1, sp.monthly_price, 'USD', 'paid', '2026-05-01', '2026-05-31', '2026-05-01 00:00:00', '2026-05-01 00:00:00', 'INV-001-2026-05'
FROM property_subscriptions ps
JOIN subscription_plans sp ON ps.plan_id = sp.id
WHERE ps.property_id = 1
LIMIT 1;

INSERT INTO subscription_invoices (subscription_id, property_id, amount, currency, status, period_start, period_end, issued_at, transaction_reference)
SELECT ps.id, 1, sp.monthly_price, 'USD', 'pending', '2026-07-01', '2026-07-31', '2026-07-01 00:00:00', 'INV-001-2026-07'
FROM property_subscriptions ps
JOIN subscription_plans sp ON ps.plan_id = sp.id
WHERE ps.property_id = 1
LIMIT 1;

-- Onboarding Requests (sample pending/approved/rejected)
INSERT INTO onboarding_requests (property_name, property_code, contact_name, contact_email, contact_phone, address, city, state, country, postal_code, description, status, review_notes) VALUES
('Sunset Beach Resort', 'SBR001', 'Carlos Mendez', 'carlos.mendez@example.com', '+1-555-0201', '1 Ocean Boulevard', 'Miami', 'FL', 'US', '33101', 'A boutique beachfront resort with 15 rooms looking to join the ZyroInn platform.', 'pending', NULL),
('Mountain View Lodge', 'MVL001', 'Sarah Chen', 'sarah.chen@example.com', '+1-555-0202', '42 Pine Road', 'Denver', 'CO', 'US', '80201', 'Family-run mountain lodge with 8 cabins. Wants integrated booking system.', 'verified', 'Documents verified. Need to review property photos.'),
('Downtown Urban Suites', 'DUS001', 'James Wilson', 'james.wilson@example.com', '+1-555-0203', '88 City Center Plaza', 'New York', 'NY', 'US', '10001', 'Modern urban apartment-style hotel with 22 units in Manhattan.', 'approved', 'Approved for Enterprise plan. Onboarding in progress.'),
('Riverside Inn', NULL, 'Priya Sharma', 'priya.sharma@example.com', '+1-555-0204', '12 River Road', 'Austin', 'TX', 'US', '73301', 'Charming inn along the Colorado River. 6 rooms total.', 'rejected', 'Property does not meet minimum room requirements for platform.'),
('Countryside Barn Stay', 'CBS001', 'Tom Baker', 'tom.baker@example.com', '+1-555-0205', '7 Farm Lane', 'Nashville', 'TN', 'US', '37201', 'Unique barn conversion with 4 luxury suites. Agritourism experience.', 'onboarding', 'Onboarding started. Setting up room inventory.');

-- Commission Payouts (linking to seed booking #2 which is agency, 12.50%)
INSERT INTO commission_payouts (booking_id, property_id, agency_name, commission_amount, commission_percentage, status, notes)
SELECT 2, 1, 'Global Travel Agency Inc.', (b.commission_percentage / 100) * 110.00, b.commission_percentage, 'pending', 'Commission for agency booking #2 - Standard Garden View'
FROM bookings b WHERE b.id = 2;

-- Fraud Flags
INSERT INTO fraud_flags (booking_id, guest_id, flag_type, severity, description, flagged_by, status, resolution_notes) VALUES
(NULL, 1, 'duplicate_booking', 'medium', 'Guest Maya Patel has two overlapping bookings in different properties.', 1, 'investigating', 'Checking time overlap in booking records.'),
(2, 1, 'suspicious_payment', 'low', 'Booking #2 has $0 payment with pending status for 2+ months.', NULL, 'open', NULL);

-- Dispute Resolutions
INSERT INTO dispute_resolutions (booking_id, guest_id, reported_by, dispute_type, description, amount_in_dispute, status, resolution) VALUES
(1, 1, 3, 'billing', 'Guest claims they were overcharged for breakfast - charged for 4 days but only had breakfast on 3 days.', 15.00, 'investigating', NULL),
(2, 1, NULL, 'service', 'Guest reported that the Garden View room did not match the description on the website.', NULL, 'open', NULL);

-- Support Tickets
INSERT INTO support_tickets (property_id, guest_id, submitted_by, subject, description, category, priority, status, assigned_to) VALUES
(1, 1, NULL, 'Cannot access Wi-Fi in room 102', 'Guest Maya Patel reports that the Wi-Fi network is not visible from room 102. Has tried restarting device.', 'technical', 'high', 'open', 1),
(1, NULL, 1, 'Billing question - Invoice #INV-001-2026-06', 'Property owner requesting clarification on the latest subscription invoice.', 'billing', 'normal', 'in_progress', 1),
(1, NULL, 2, 'Spam booking detection needed', 'We received 3 identical booking requests from the same email in 5 minutes. Please investigate.', 'other', 'urgent', 'open', NULL);

-- Support Ticket Replies
INSERT INTO support_ticket_replies (ticket_id, user_id, message, is_internal) VALUES
(2, 1, 'Let me review the invoice details and get back to the owner.', 0),
(2, 1, 'This is a recurring monthly charge for the Enterprise plan. Please send the standard invoice breakdown.', 1),
(3, 1, 'Investigating the duplicate requests. Will block the suspicious email.', 0),
(3, 1, 'IP analysis suggests automated bot activity. Flagging for fraud team review.', 1);

-- Set existing reviews to pending moderation (seed already has a review)
UPDATE reviews SET moderation_status = 'pending' WHERE moderation_status = 'pending' AND id = 1;
