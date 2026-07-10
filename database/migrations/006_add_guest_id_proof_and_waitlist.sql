-- 006_add_guest_id_proof_and_waitlist.sql
-- Add id_proof_path to guests table for secure ID capture
ALTER TABLE guests ADD COLUMN id_proof_path VARCHAR(255) NULL;

-- Update bookings.status ENUM to support waitlist
ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','confirmed','checked_in','checked_out','cancelled','waitlisted') NOT NULL DEFAULT 'pending';
