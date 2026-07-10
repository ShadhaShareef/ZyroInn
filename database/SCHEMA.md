# ZyroInn Database Schema Overview

This document describes each table in the ZyroInn schema, its purpose, and how it relates to the rest of the system.

## Core tables

### properties
- Stores each hotel property.
- Supports multi-property operations from day one, even if the client has only one property today.
- Columns: `name`, `code`, `address`, `city`, `state`, `country`, `postal_code`, `phone`, `email`, `time_zone`, `description`, `status`.
- Relationships: has many `rooms`, `property_amenities`, `bookings`.

### rooms
- Stores each room belonging to a property.
- Core room attributes include `room_type`, `occupancy`, `bed_count`, `ac`, `base_rate`, and `status`.
- Relationships: belongs to `properties`; has many `room_amenities`, `bookings`, `room_status_log`, `tasks`, `maintenance_orders`.

### amenities
- Taxonomy table for amenity and classification values.
- Includes amenity `key`, `label`, and `category` to support the adaptive amenity system and avoid hardcoded feature sets.
- Relationships: attached to `properties` via `property_amenities`; attached to `rooms` via `room_amenities`.

### property_amenities
- Junction table linking `properties` and `amenities`.
- Powers property-level toggles in the adaptive amenity system.
- Relationships: belongs to `properties` and `amenities`.

### room_amenities
- Junction table linking `rooms` and `amenities`.
- Powers room-level toggles and avoids hardcoded feature sets.
- Relationships: belongs to `rooms` and `amenities`.

### users
- Central user table for guests and staff.
- Single table has a `role` column with enumerated values: guest, front_office, housekeeping, maintenance, fnb, security, owner, manager, admin.
- Note: a separate roles table could be useful if staff members need multiple roles, but the current plan is single-role enforcement; this keeps schema simple and avoids unnecessary join complexity. If multi-role staff become necessary later, add `roles` + `user_roles` junction tables.
- Relationships: has one `guests` record for account-linked guests; authored records in logs, tasks, maintenance orders, incident reports, shift handover notes, etc.

### guests
- Guest profile data for guests with accounts.
- Guest records can be linked to `users` when the guest has an actual login.
- Columns include `id_proof_path` for secure storage of uploaded identification proofs.
- Relationships: belongs to `users`; has many `bookings`.

### bookings
- Stores reservations for rooms at properties.
- Includes `property_id`, `room_id`, `guest_id`, `status`, and `source` to separate agency bookings from direct bookings.
- Status enum: `pending`, `confirmed`, `checked_in`, `checked_out`, `cancelled`, `waitlisted` (to support basic waiting queues when no rooms are available).
- Source enum: `direct`, `agency`, `walk_in`, `corporate`, `other`.
- Relationships: belongs to `properties`, `rooms`, `guests`; has many `booking_addons` and `payments`.

### booking_addons
- Booking-level add-on items like spa, room service, and extras.
- Relationships: belongs to `bookings`.

### payments
- Payments linked to bookings.
- Includes `status`, `method`, `amount`, and `currency`.
- Relationships: belongs to `bookings`.

## Operations tables

### room_status_log
- Housekeeping source of truth for room status changes.
- Records `room_id`, `status`, `changed_by`, and `changed_at`.
- Relationships: belongs to `rooms`, `users`.

### tasks
- General tasks for staff, optionally linked to a room.
- Includes `assigned_to`, `related_room_id`, `type`, `priority`, and `status`.
- Relationships: assigned to `users`; related to `rooms`.

### vendors
- Stores external vendors used by maintenance and other operations.
- Relationships: referenced by `maintenance_orders`.

### maintenance_orders
- Tracks maintenance work orders, issue descriptions, vendor assignments, and priority.
- Relationships: belongs to `rooms` and optionally `vendors`; created_by references `users`.

### lost_and_found
- Tracks found items and lost property handling.
- Relationships: optionally linked to rooms, guests, and handled by users.

### incident_reports
- Security incident tracking.
- Relationships: optionally linked to rooms, guests, and reported by `users`.

### shift_handover_notes
- Shift-level notes for handover between staff.
- Relationships: authored by `users`.

## Reviews and loyalty tables (stub)

### reviews
- Stores future guest reviews for properties or rooms.
- Relationships: belongs to `guests`, `properties`, and optionally `rooms`.

### loyalty_accounts
- Loyalty account stub for guests.
- Relationships: belongs to `guests`.

### loyalty_transactions
- Transaction history for loyalty points.
- Relationships: belongs to `loyalty_accounts`.

## Adaptive Amenity System tables
- The following tables implement the adaptive amenity system:
  - `amenities`
  - `property_amenities`
  - `room_amenities`

These tables allow the application to drive feature toggles through data joins rather than hardcoded values.

## Platform Admin (Surface D) tables

### onboarding_requests
- Self-registration queue for property owners to submit their property for platform inclusion.
- Workflow: pending -> verified -> approved | rejected (or direct rejected).
- Includes contact info, property details, verification docs (JSON), and review trail.
- Relationships: reviewed_by references `users` (admin).

### subscription_plans
- Available subscription tiers for properties (e.g. Starter, Growth, Enterprise).
- Defines pricing, max properties, max rooms per property, and feature set (JSON).
- Relationships: referenced by `property_subscriptions`.

### property_subscriptions
- Links a property to a subscription plan with billing cycle and status.
- Status enum: active, past_due, cancelled, expired.
- Relationships: belongs to `properties` and `subscription_plans`.

### subscription_invoices
- Billing invoices generated per subscription cycle.
- Tracks amount, period, payment status, and transaction reference.
- Relationships: belongs to `property_subscriptions` and `properties`.

### commission_payouts
- Tracks commission payout lifecycle for agency/bookings with commission_percentage set.
- Extends the existing `bookings.commission_percentage` field with payout tracking.
- Status: pending, approved, paid, cancelled.
- Relationships: belongs to `bookings` and `properties`.

### fraud_flags
- Flag-and-review workflow for suspicious booking/payment activity.
- Types: duplicate_booking, suspicious_payment, identity_concern, chargeback_risk, policy_violation, other.
- Severity: low, medium, high, critical.
- Relationships: optionally linked to `bookings`, `guests`; flagged_by references `users`.

### dispute_resolutions
- Guest/customer dispute tracking for billing, damage, service, policy issues.
- Includes amount_in_dispute for financial disputes.
- Relationships: belongs to `bookings`; optionally linked to `guests` and `users`.

### reviews (moderation columns)
- Extended with `moderation_status` (pending, approved, rejected, flagged), `moderated_by`, `moderated_at`.
- Enables admin content moderation queue without a separate table.

### support_tickets
- Customer support ticket system for property/guest issues.
- Categories: billing, technical, account, property, other.
- Status workflow: open -> in_progress -> waiting_on_customer -> resolved | closed.
- Relationships: optionally linked to `properties`, `guests`; assigned_to references `users`.

### support_ticket_replies
- Threaded replies on support tickets.
- Supports internal (staff-only) notes via is_internal flag.
- Relationships: belongs to `support_tickets`; authored by `users`.
