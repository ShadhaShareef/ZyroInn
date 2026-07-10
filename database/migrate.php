<?php
// migrate.php - Database Migration Runner
require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\Database;

try {
    echo "Starting Database Migration...\n";

    // 1. Connect to MySQL host to ensure database exists
    $pdo = Database::getHostConnection();
    $config = require __DIR__ . '/../config/database.php';
    $dbName = $config['database'];

    echo "Checking if database '{$dbName}' exists...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '{$dbName}' is ready.\n";

    // 2. Re-connect to the specific database
    $pdo = Database::getConnection();

    // 3. Create migrations tracking table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 4. Find all migration files
    $migrationsDir = __DIR__ . '/migrations';
    $files = glob($migrationsDir . '/*.sql');
    sort($files); // Sort files numerically/alphabetically

    // Fetch already run migrations
    $stmt = $pdo->query("SELECT migration FROM migrations");
    $runMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $newMigrationsRun = 0;
    foreach ($files as $file) {
        $filename = basename($file);
        if (in_array($filename, $runMigrations)) {
            echo "Migration '{$filename}' already run. Skipping.\n";
            continue;
        }

        echo "Running migration '{$filename}'...\n";
        $sql = file_get_contents($file);
        
        // Execute SQL (multi-queries might require executing statements individually or using PDO exec)
        $pdo->exec($sql);

        // Record migration
        $insertStmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $insertStmt->execute([$filename]);
        echo "Successfully run '{$filename}'.\n";
        $newMigrationsRun++;
    }

    // 5. Seed data if properties table is empty
    $propCheck = $pdo->query("SELECT COUNT(*) FROM properties");
    $propertyCount = $propCheck->fetchColumn();

    if ($propertyCount == 0) {
        echo "Database is empty. Seeding initial data from seeds.sql...\n";
        $seedsFile = __DIR__ . '/seeds/seeds.sql';
        if (file_exists($seedsFile)) {
            $seedsSql = file_get_contents($seedsFile);
            $pdo->exec($seedsSql);
            echo "Database seeded successfully.\n";

            // Update scopes and icons for the seeded amenities
            echo "Updating seeded amenities with new scopes and icons...\n";
            $pdo->exec("
                UPDATE amenities SET scope = 'room', icon = '💎' WHERE `key` = 'room_type_deluxe';
                UPDATE amenities SET scope = 'room', icon = '🛏️' WHERE `key` = 'room_type_standard';
                UPDATE amenities SET scope = 'property', icon = '📶' WHERE `key` = 'amenity_wifi';
                UPDATE amenities SET scope = 'property', icon = '🥐' WHERE `key` = 'amenity_breakfast';
                UPDATE amenities SET scope = 'property', icon = '💆' WHERE `key` = 'amenity_spa';
                UPDATE amenities SET scope = 'property', icon = '🏊' WHERE `key` = 'amenity_pool';
                UPDATE amenities SET scope = 'property', icon = '🏨' WHERE `key` = 'property_type_boutique';
            ");

            // Rebuild junction tables to map the correct dynamic IDs instead of hardcoded IDs in seeds.sql
            echo "Rebuilding property_amenities and room_amenities mapping using subqueries...\n";
            $pdo->exec("
                -- Disable foreign keys temporarily for truncation
                SET FOREIGN_KEY_CHECKS = 0;
                TRUNCATE TABLE property_amenities;
                TRUNCATE TABLE room_amenities;
                SET FOREIGN_KEY_CHECKS = 1;

                -- Insert correct property_amenities using keys
                INSERT INTO property_amenities (property_id, amenity_id, enabled) VALUES
                (1, (SELECT id FROM amenities WHERE `key` = 'amenity_wifi'), 1),
                (1, (SELECT id FROM amenities WHERE `key` = 'amenity_breakfast'), 1),
                (1, (SELECT id FROM amenities WHERE `key` = 'amenity_spa'), 0),
                (1, (SELECT id FROM amenities WHERE `key` = 'amenity_pool'), 1);

                -- Insert correct room_amenities using keys for Room 101 (Deluxe)
                INSERT INTO room_amenities (room_id, amenity_id, enabled) VALUES
                ((SELECT id FROM rooms WHERE room_number = '101' LIMIT 1), (SELECT id FROM amenities WHERE `key` = 'room_type_deluxe'), 1),
                ((SELECT id FROM rooms WHERE room_number = '101' LIMIT 1), (SELECT id FROM amenities WHERE `key` = 'amenity_wifi'), 1),
                ((SELECT id FROM rooms WHERE room_number = '101' LIMIT 1), (SELECT id FROM amenities WHERE `key` = 'amenity_breakfast'), 1);

                -- Insert correct room_amenities using keys for Room 102 (Standard)
                INSERT INTO room_amenities (room_id, amenity_id, enabled) VALUES
                ((SELECT id FROM rooms WHERE room_number = '102' LIMIT 1), (SELECT id FROM amenities WHERE `key` = 'room_type_standard'), 1),
                ((SELECT id FROM rooms WHERE room_number = '102' LIMIT 1), (SELECT id FROM amenities WHERE `key` = 'amenity_wifi'), 1),
                ((SELECT id FROM rooms WHERE room_number = '102' LIMIT 1), (SELECT id FROM amenities WHERE `key` = 'amenity_breakfast'), 0);
            ");
            echo "Seeded amenities and mappings updated successfully.\n";
        } else {
            echo "Warning: seeds.sql not found at {$seedsFile}.\n";
        }
    } else {
        echo "Properties already exist. Skipping seed data.\n";
    }

    // 6. Hash passwords for all users that have empty or invalid password_hash
    $stmt = $pdo->query("SELECT id, email, role, password_hash FROM users");
    $usersToHash = [];
    foreach ($stmt->fetchAll() as $u) {
        $len = strlen($u['password_hash'] ?? '');
        if ($len === 0 || $len !== 60) {
            $usersToHash[] = $u;
        }
    }
    $passwordMap = [
        'admin' => 'admin123',
        'owner' => 'password123',
        'front_office' => 'password123',
        'housekeeping' => 'password123',
        'maintenance' => 'password123',
        'fnb' => 'password123',
        'manager' => 'password123',
        'guest' => 'password123',
    ];
    foreach ($usersToHash as $user) {
        $plain = $passwordMap[$user['role']] ?? 'password123';
        $hash = password_hash($plain, PASSWORD_BCRYPT, ['cost' => 10]);
        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $updateStmt->execute([$hash, $user['id']]);
        echo "  Hashed password for {$user['email']} (role: {$user['role']})\n";
    }

    echo "Database Migration Completed Successfully. Total new migrations run: {$newMigrationsRun}\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
