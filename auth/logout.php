<?php
require_once '../includes/functions.php';

// Destroy session and redirect
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Start new session for flash message
session_start();
flash_message('success', 'You have been logged out successfully.');

redirect(SITE_URL);
?>
