<?php
// Function to check if user is logged in
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']);
    }
}

// Function to redirect if not logged in
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . SITE_URL . 'auth/login.php');
        exit();
    }
}

// Function to get current user ID
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
