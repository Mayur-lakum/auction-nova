<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

$db = Database::getInstance();

// Create live_updates table for real-time synchronization
$create_table = "
CREATE TABLE IF NOT EXISTS live_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    data TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at),
    INDEX idx_type (type)
)";

try {
    $db->query($create_table);
    echo "Live updates table created successfully!<br>";
    
    // Test the table
    $db->query("INSERT INTO live_updates (type, data) VALUES ('test', 'System initialized')");
    echo "Test record inserted!<br>";
    
    // Clean up old records (older than 5 minutes)
    $db->query("DELETE FROM live_updates WHERE created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    echo "Old records cleaned up!<br>";
    
    echo "<script>setTimeout(() => window.location.href='dashboard.php', 2000);</script>";
    echo "Redirecting to dashboard...";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
