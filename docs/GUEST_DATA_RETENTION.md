# Guest Data Retention Policy

> **Status:** Open Decision — no policy has been set. This document captures the options
> and implementation gaps so the project owner can decide.

## Data categories affected

| Category | Storage location | Current behaviour |
|---|---|---|
| ID documents | `storage/ids/` as encrypted files | Stored indefinitely; deleted only when the file record is manually removed |
| Payment records | `payments` table | Stored indefinitely; no purge mechanism |
| Guest PII (name, email, phone, address) | `guests` table | Stored indefinitely; no purge mechanism |
| ID proof path | `guests.id_proof_path` column | Points to encrypted file; never cleaned up |

## Questions to decide

### 1. Retention period
- How long after a guest's last stay should their ID document be retained?
  - Suggested options: 1 year, 3 years, 5 years, or duration of legal requirement in your jurisdiction
  - After the period expires, the file should be securely deleted (shredded, not just unlinked)
  - The `guests.id_proof_path` column should be set to `NULL` upon deletion

- How long after a booking should payment records be retained?
  - Suggested: 7 years (common tax/accounting requirement), or shorter if you only keep aggregated data

### 2. Access control
- Who can view ID documents?
  - Currently: any authenticated staff member via the `view-id` route
  - Consider: only front-office staff with explicit check-in/check-out permissions
  - Consider: audit log every time an ID document is viewed

### 3. Deletion workflow
- Should deletion be automatic (cron job) or manual (admin action)?
- Should the guest be able to request deletion of their data (right-to-erasure / GDPR)?
  - If yes, you need a "Delete my data" feature on the guest profile page

### 4. Encryption key management
- The encryption key is currently stored in `config/env.php` (`encryption_key`)
- In production this should be moved to an environment variable or a secrets manager
- Key rotation policy needs to be defined

## Recommended next steps

1. Choose retention periods for ID documents and payment records
2. Decide on an access control model for ID document viewing
3. Implement a cron-based cleanup script that deletes expired ID files and nullifies paths
4. Optionally implement a right-to-erasure flow for guests
5. Move the encryption key out of `env.php` into a production secrets manager

This policy is OPTIONAL for v1 but should be resolved before any public launch with real guest data.
