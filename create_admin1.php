<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

try {
    $db = Database::getInstance();
    $hashed_password = password_hash('admin1password', PASSWORD_DEFAULT);
    
    $db->execute(
        'INSERT INTO users (username, email, password, first_name, last_name, role, is_verified, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        ['admin1', 'admin1@gmail.com', $hashed_password, 'Admin', 'User', 'admin', TRUE, TRUE]
    );
    
    echo "Admin user created successfully!\n";
    echo "Email: admin1@gmail.com\n";
    echo "Password: admin1password\n";
    echo "Login at: /admin/login.php\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
