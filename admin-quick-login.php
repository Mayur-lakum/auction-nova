<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

session_start();

// Direct admin login without password
$email = 'admin@auctionnova.com';
$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT id, username, email, password, role, is_active, is_verified FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // Refresh session id to avoid session fixation and ensure session updates
    if (function_exists('session_regenerate_id')) {
        @session_regenerate_id(true);
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['is_verified'] = $user['is_verified'];

    // Admin dashboard gate expects this session variable
    $_SESSION['admin_email'] = $user['email'];
    $_SESSION['is_admin'] = true;
    $_SESSION['admin_login_time'] = time();

    $update_query = "UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->execute([$user['id']]);

    redirect(SITE_URL . 'admin/dashboard.php');
} else {
    die('Admin user not found. Create admin first.');
}
?>
