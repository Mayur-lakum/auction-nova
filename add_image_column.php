<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

// Add image column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE auctions ADD COLUMN image VARCHAR(255) NULL AFTER description");
    echo "Image column added to auctions table.\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Image column already exists.\n";
    } else {
        echo "Error adding image column: " . $e->getMessage() . "\n";
    }
}
?>
