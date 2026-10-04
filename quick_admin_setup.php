<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

// Create admin1 user
$db = Database::getInstance();
$hashed_password = password_hash('admin1password', PASSWORD_DEFAULT);

try {
    $db->execute(
        'INSERT INTO users (username, email, password, first_name, last_name, role, is_verified, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        ['admin1', 'admin1@gmail.com', $hashed_password, 'Admin', 'User', 'admin', TRUE, TRUE]
    );
    
    echo "<h2>Admin User Created Successfully!</h2>";
    echo "<p><strong>Email:</strong> admin1@gmail.com</p>";
    echo "<p><strong>Password:</strong> admin1password</p>";
    echo "<p><strong>Login URL:</strong> <a href='/admin/login.php'>/admin/login.php</a></p>";
    echo "<p><a href='/admin/login.php'>Go to Admin Login</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
