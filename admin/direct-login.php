<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Database.php';

// Direct admin login - bypass password for testing
$db = Database::getInstance();
$user = $db->fetch("SELECT * FROM users WHERE email = 'admin@auctionnova.com' AND is_active = 1");

if ($user && in_array($user['role'], ['admin', 'super_admin'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['admin_email'] = $user['email'];
    $_SESSION['is_admin'] = true;
    $_SESSION['admin_login_time'] = time();
    
    header('Location: ' . SITE_URL . 'admin/dashboard.php');
    exit();
} else {
    echo "Admin user not found. Creating one...";
    
    // Create admin user if not exists
    $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
    $db->execute(
        "INSERT INTO users (username, email, password, first_name, last_name, role, is_verified, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        ['admin', 'admin@auctionnova.com', $hashed_password, 'Admin', 'User', 'super_admin', TRUE, TRUE]
    );
    
    echo "Admin user created. Refreshing...";
    echo "<script>setTimeout(() => window.location.href = 'direct-login.php', 1000);</script>";
}
?>
