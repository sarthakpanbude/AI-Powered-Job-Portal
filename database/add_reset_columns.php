<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->exec("ALTER TABLE `users` ADD COLUMN `reset_token` VARCHAR(255) DEFAULT NULL");
    $pdo->exec("ALTER TABLE `users` ADD COLUMN `reset_expiry` DATETIME DEFAULT NULL");
    echo "Columns reset_token and reset_expiry successfully added to users table.\n";
} catch(PDOException $e) {
    echo "Database update note: " . $e->getMessage() . " (columns might already exist).\n";
}
?>
