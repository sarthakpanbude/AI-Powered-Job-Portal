<?php
require_once __DIR__ . '/../config/db.php';

try {
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    if ($schema === false) {
        throw new Exception("Could not read schema.sql file.");
    }

    // Execute the SQL schema queries
    // Using PDO exec for the whole block of SQL queries (PDO supports multi-queries by default in exec)
    $pdo->exec($schema);

    echo "Database schema imported successfully!\n";
} catch (Exception $e) {
    echo "Error importing database: " . $e->getMessage() . "\n";
}
?>
