<?php
// Simple test file to check if admin directory is accessible
echo "<h1>Admin Directory Test</h1>";
echo "<p>If you can see this, the admin directory is accessible.</p>";
echo "<p>Current directory: " . __DIR__ . "</p>";
echo "<p>PHP version: " . phpversion() . "</p>";

// Test database connection
try {
    require_once '../includes/functions.php';
    echo "<p style='color: green;'>✅ Functions included successfully</p>";
    
    $db = new Database();
    $conn = $db->connect();
    if ($conn) {
        echo "<p style='color: green;'>✅ Database connection successful</p>";
    } else {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.php'>Try Admin Dashboard</a></p>";
echo "<p><a href='../index.php'>Back to Main Site</a></p>";
?>
