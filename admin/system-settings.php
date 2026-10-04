<?php
// System Settings - BLANK
session_start();
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Admin authentication check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'super_admin'])) {
    header('Location: login.php');
    exit();
}

$page_title = 'System Settings';
require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1>System Settings</h1>
            <p>System settings page is currently blank. Ready for new design.</p>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
