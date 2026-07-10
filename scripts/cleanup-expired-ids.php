<?php
/**
 * Cleanup expired guest ID proof files.
 *
 * Deletes encrypted ID files from storage/ids/ where the corresponding
 * guest record no longer exists, OR the file is older than the retention
 * period (default 90 days).
 *
 * Usage (dry-run):
 *   php scripts/cleanup-expired-ids.php --dry-run
 *
 * Usage (live):
 *   php scripts/cleanup-expired-ids.php
 *
 * Recommended cron schedule: daily at 3 AM
 *   0 3 * * * /usr/bin/php /path/to/ZyroInn/scripts/cleanup-expired-ids.php
 */

$retentionDays = 90;
$dryRun = in_array('--dry-run', $argv ?? [], true);
$idsDir = __DIR__ . '/../storage/ids/';

require_once __DIR__ . '/../app/bootstrap.php';

$env = [];
$envPath = __DIR__ . '/../config/env.php';
if (file_exists($envPath)) {
    $env = require $envPath;
}

$db = App\Services\Database::getConnection();
$deleted = 0;
$errors = 0;
$now = time();
$cutoff = $now - ($retentionDays * 86400);

$files = glob($idsDir . 'guest_*');
if (empty($files)) {
    echo "No ID files found in $idsDir\n";
    exit(0);
}

foreach ($files as $filePath) {
    $filename = basename($filePath);

    // Extract guest_id from filename pattern: guest_NNNNNNN_timestamp.ext
    if (!preg_match('/^guest_(\d+)_/', $filename, $m)) {
        continue;
    }
    $guestId = (int)$m[1];

    // Check if the guest record still exists
    $stmt = $db->prepare("SELECT id, id_proof_path FROM guests WHERE id = ?");
    $stmt->execute([$guestId]);
    $guest = $stmt->fetch();

    $filemtime = filemtime($filePath);

    if (!$guest) {
        // Orphaned file — guest record deleted
        echo ($dryRun ? '[DRY-RUN] Would delete' : '[DELETE]') . " orphaned file: $filename (guest #$guestId not found)\n";
        if (!$dryRun) {
            unlink($filePath) ? $deleted++ : $errors++;
        } else {
            $deleted++;
        }
    } elseif ($filemtime > 0 && $filemtime < $cutoff) {
        // Expired file — older than retention period
        echo ($dryRun ? '[DRY-RUN] Would delete' : '[DELETE]') . " expired file: $filename (last modified " . date('Y-m-d', $filemtime) . ")\n";
        if (!$dryRun) {
            if ($guest['id_proof_path'] === $filePath) {
                $stmt = $db->prepare("UPDATE guests SET id_proof_path = NULL WHERE id = ?");
                $stmt->execute([$guestId]);
            }
            unlink($filePath) ? $deleted++ : $errors++;
        } else {
            $deleted++;
        }
    }
}

$mode = $dryRun ? 'Dry-run' : 'Cleanup';
echo "$mode complete: $deleted files processed, $errors errors.\n";
exit($errors > 0 ? 1 : 0);
