<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Check if columns already exist, if not, add them
    $pdo->exec("ALTER TABLE students ADD COLUMN IF NOT EXISTS education TEXT DEFAULT NULL");
    $pdo->exec("ALTER TABLE students ADD COLUMN IF NOT EXISTS experience TEXT DEFAULT NULL");
    $pdo->exec("ALTER TABLE students ADD COLUMN IF NOT EXISTS skills TEXT DEFAULT NULL");
    $pdo->exec("ALTER TABLE students ADD COLUMN IF NOT EXISTS portfolio_links TEXT DEFAULT NULL");
    echo "Database migrated successfully!\n";
} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
?>
