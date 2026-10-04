<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

// Create live_updates table if missing
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS live_updates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(50) NOT NULL,
            data JSON NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (event_type),
            INDEX (created_at)
        )
    ");
    echo "live_updates table ensured.\n";
} catch (Exception $e) {
    echo "Error creating live_updates table: " . $e->getMessage() . "\n";
}
?>
