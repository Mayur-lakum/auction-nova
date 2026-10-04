<?php
/**
 * Admin Access Control
 * Include this file at the top of all admin pages to ensure secure access
 */

function verify_admin_session() {
    require_admin_login();
}

function require_admin_login() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
 
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
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
        log_access_attempt('not_logged_in');
        redirect_to_admin_login();
        exit();
    }
    
    // Check if user has admin role
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'], true)) {
        log_access_attempt('insufficient_role');
        session_destroy();
        redirect_to_admin_login('unauthorized');
        exit();
    }
    
    // Check session timeout (30 minutes)
    if (isset($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time']) > 1800) {
        session_destroy();
        redirect_to_admin_login('timeout');
        exit();
    }
    
    // Refresh session timeout
    $_SESSION['admin_login_time'] = time();
    
    // Verify session integrity
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        log_access_attempt('invalid_session');
        session_destroy();
        redirect_to_admin_login('invalid');
        exit();
    }
}

function redirect_to_admin_login($reason = '') {
    $url = SITE_URL . 'admin/login.php';
    if ($reason) {
        $url .= '?error=' . urlencode($reason);
    }
    header('Location: ' . $url);
    exit();
}

function log_access_attempt($reason) {
    // admin_logs.admin_id is NOT NULL, so skip logging when nobody is logged in yet
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    try {
        $db = Database::getInstance();
        $db->query(
            "INSERT INTO admin_logs (admin_id, action, target_type, target_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)",
            [
                $_SESSION['user_id'],
                'access_denied',
                'system',
                null,
                "Admin access denied: $reason",
                $_SERVER['REMOTE_ADDR'] ?? ''
            ]
        );
    } catch (Exception $e) {
        error_log("Failed to log access attempt: " . $e->getMessage());
    }
}

function verify_admin_csrf($token) {
    if (!isset($_SESSION['admin_csrf_token']) || $token !== $_SESSION['admin_csrf_token']) {
        log_access_attempt('csrf_invalid');
        session_destroy();
        redirect_to_admin_login('csrf');
        exit();
    }
}

function generate_admin_csrf() {
    if (!isset($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf_token'];
}

// IP Restriction (Optional - uncomment to enable)
function check_admin_ip() {
    $allowed_ips = ['127.0.0.1', '::1']; // Add your admin IPs here
    $current_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    if (!in_array($current_ip, $allowed_ips)) {
        log_access_attempt('ip_restricted');
        session_destroy();
        redirect_to_admin_login('ip');
        exit();
    }
}

// 2FA Check (Optional - implement as needed)
function check_admin_2fa() {
    // Implementation for 2FA verification
    // This would check if admin has enabled 2FA and verify the code
}

// Usage: Include this at the top of admin pages
// require_once '../admin/access-control.php';
// require_admin_login();
?>