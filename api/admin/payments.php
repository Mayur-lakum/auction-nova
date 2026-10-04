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
echo '<h6 class="mb-2">Payments / Escrow / Payouts</h6>';
echo '<div class="text-muted">Payment, escrow, payout tables are not implemented in the current schema. Add payment tables to enable transaction management.</div>';
echo '</div>';
?>
