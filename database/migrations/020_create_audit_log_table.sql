-- 020_create_audit_log_table.sql
-- Audit log for all admin actions across the platform

CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_user_id` BIGINT UNSIGNED NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` BIGINT UNSIGNED NOT NULL,
    `details` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`admin_user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    INDEX idx_audit_admin (`admin_user_id`),
    INDEX idx_audit_entity (`entity_type`, `entity_id`),
    INDEX idx_audit_created (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed mock audit log data (idempotent — only inserts if table is empty)
INSERT INTO audit_log (admin_user_id, action, entity_type, entity_id, details, created_at)
SELECT * FROM (
    SELECT 1, 'property_approved',       'property',  12, '{"previous_status":"pending","new_status":"active","review_notes":"All documents verified, property meets quality standards."}',                                                         '2026-07-11 10:45:00' UNION ALL
    SELECT 1, 'commission_rate_changed',  'commission', 8, '{"previous_rate":"12.0","new_rate":"15.0","reason":"Performance bonus for Q2 volume."}',                                                                                   '2026-07-11 09:30:00' UNION ALL
    SELECT 1, 'dispute_resolved',         'dispute',   23, '{"resolution":"Refund issued to guest (50%)","outcome":"Partial refund — maintenance delay confirmed.","guest_notified":true}',                                            '2026-07-10 16:15:00' UNION ALL
    SELECT 1, 'review_moderated',         'review',    67, '{"previous_status":"pending","new_status":"approved","reason":"Genuine stay verified, no policy violation."}',                                                              '2026-07-10 14:50:00' UNION ALL
    SELECT 1, 'ticket_status_changed',    'ticket',    45, '{"previous_status":"open","new_status":"in_progress","assigned_to":"Marcus Johnson","note":"Assigned to maintenance team lead."}',                                          '2026-07-10 11:20:00' UNION ALL
    SELECT 1, 'property_rejected',        'property',  18, '{"previous_status":"pending","new_status":"rejected","reason":"Incomplete documentation — insurance certificate missing."}',                                                '2026-07-09 15:40:00' UNION ALL
    SELECT 1, 'fraud_resolved',           'fraud',      5, '{"severity":"high","finding":"No fraudulent activity confirmed — payment verified via 3DS.","action_taken":"Flag dismissed after bank confirmation."}',                      '2026-07-09 13:10:00' UNION ALL
    SELECT 1, 'ticket_assigned',          'ticket',    42, '{"previous_assignee":null,"new_assignee":"Priya Patel","priority":"high","note":"Billing team to review charge discrepancy."}',                                            '2026-07-09 10:00:00' UNION ALL
    SELECT 1, 'commission_paid',          'commission',12, '{"amount":"2450.00","booking_id":"1123","period":"June 2026","paid_via":"Bank transfer — reference TRX-8821"}',                                                            '2026-07-08 14:30:00' UNION ALL
    SELECT 1, 'dispute_dismissed',        'dispute',   19, '{"resolution":"Claim dismissed — guest arrived and checked in per system logs.","outcome":"Dismissed with warning to property."}',                                         '2026-07-08 11:45:00' UNION ALL
    SELECT 1, 'property_approved',        'property',  14, '{"previous_status":"verified","new_status":"active","review_notes":"Site inspection passed. Approved for listing."}',                                                      '2026-07-07 09:20:00' UNION ALL
    SELECT 1, 'review_moderated',         'review',    71, '{"previous_status":"pending","new_status":"rejected","reason":"Contains profanity and personal attack — removed per policy 4.2."}',                                        '2026-07-07 08:15:00' UNION ALL
    SELECT 1, 'ticket_status_changed',    'ticket',    38, '{"previous_status":"in_progress","new_status":"resolved","resolution":"Upgrade provided at no charge. Guest compensated with 5000 loyalty points."}',                      '2026-07-06 17:00:00' UNION ALL
    SELECT 1, 'commission_rate_changed',  'commission',15, '{"previous_rate":"10.0","new_rate":"8.0","reason":"Negotiated volume discount — 200+ bookings per quarter."}',                                                            '2026-07-06 14:20:00' UNION ALL
    SELECT 1, 'fraud_dismissed',          'fraud',      3, '{"severity":"medium","finding":"Multiple rapid bookings from same IP — determined to be a travel agency booking on behalf of clients.","action_taken":"No action needed. Whitelisted agency IP range."}', '2026-07-05 11:30:00' UNION ALL
    SELECT 1, 'ticket_assigned',          'ticket',    51, '{"previous_assignee":null,"new_assignee":"Sarah Chen","priority":"normal","note":"Housekeeping team to check lost & found."}',                                             '2026-07-05 09:45:00' UNION ALL
    SELECT 1, 'dispute_resolved',         'dispute',   27, '{"resolution":"Charge reduced from $500 to $200 — normal wear and tear.","outcome":"Partial refund issued. Property warned about excessive claims."}',                      '2026-07-04 15:00:00' UNION ALL
    SELECT 1, 'property_approved',        'property',  21, '{"previous_status":"onboarding","new_status":"active","review_notes":"Fast-track approval — pre-verified partner chain."}',                                               '2026-07-04 10:30:00' UNION ALL
    SELECT 1, 'commission_paid',          'commission',18, '{"amount":"3720.00","booking_id":"1187","period":"June 2026","paid_via":"Wire transfer — reference W-7723"}',                                                             '2026-07-03 13:15:00' UNION ALL
    SELECT 1, 'ticket_status_changed',    'ticket',    47, '{"previous_status":"open","new_status":"resolved","resolution":"Water heater repaired. Guest comped one night as apology."}',                                             '2026-07-03 09:00:00' UNION ALL
    SELECT 1, 'review_deleted',           'review',    82, '{"previous_status":"approved","reason":"Guest requested removal — confirmed they stayed at wrong property. Deleted under mistaken identity policy."}',                      '2026-07-02 16:45:00' UNION ALL
    SELECT 1, 'dispute_dismissed',        'dispute',   14, '{"resolution":"Dismissed — early check-in fee was clearly stated in booking terms.","outcome":"Guest reminded of policy acceptance."}',                                   '2026-07-02 11:30:00' UNION ALL
    SELECT 1, 'fraud_resolved',           'fraud',      8, '{"severity":"critical","finding":"Stolen credit card confirmed — bank notified.","action_taken":"Booking cancelled. Guest blacklisted. Police report filed (ref #CR-2026-4412)."}', '2026-07-01 08:20:00' UNION ALL
    SELECT 1, 'property_rejected',        'property',  24, '{"previous_status":"pending","new_status":"rejected","reason":"Safety inspection failed — fire exits blocked. Reapplication allowed after remediation."}',                  '2026-06-30 14:10:00' UNION ALL
    SELECT 1, 'commission_rate_changed',  'commission',22, '{"previous_rate":"18.0","new_rate":"20.0","reason":"Premium service tier upgrade — includes featured placement and priority support."}',                                   '2026-06-30 10:00:00' UNION ALL
    SELECT 1, 'ticket_status_changed',    'ticket',    55, '{"previous_status":"open","new_status":"resolved","resolution":"Tax was incorrectly applied. Refund of $23.40 issued. Invoice corrected."}',                              '2026-06-29 15:30:00' UNION ALL
    SELECT 1, 'review_moderated',         'review',    89, '{"previous_status":"flagged","new_status":"approved","reason":"Review was flagged by automated system — manually verified as legitimate."}',                              '2026-06-29 09:15:00'
) tmp
WHERE NOT EXISTS (SELECT 1 FROM audit_log LIMIT 1);
