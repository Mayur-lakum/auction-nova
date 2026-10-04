<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/auth.php';

if (!is_logged_in() || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'], true)) {
    echo '<div class="alert alert-danger">Access denied.</div>';
    exit;
}

echo '<div class="p-3">';
echo '<h6 class="mb-2">KYC / Seller Verification</h6>';
echo '<div class="text-muted">KYC tables/doc uploads are not implemented in the current schema. Add KYC tables to enable verification queue.</div>';
echo '</div>';
?>
