<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/auth.php';

$is_localhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
if (defined('DEV_ADMIN_BYPASS') && DEV_ADMIN_BYPASS && $is_localhost) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = 1;
        $_SESSION['email'] = SITE_EMAIL;
        $_SESSION['username'] = 'Admin';
        $_SESSION['role'] = 'admin';
        $_SESSION['admin_email'] = SITE_EMAIL;
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_login_time'] = time();
    }
}

// Admin authentication check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'super_admin'])) {
    header('Location: login.php');
    exit();
}

header('Location: dashboard.php');
exit();
?>
