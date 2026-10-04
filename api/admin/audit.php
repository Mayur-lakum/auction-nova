<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/auth.php';

if (!is_logged_in() || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'], true)) {
    echo '<div class="alert alert-danger">Access denied.</div>';
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

$stmt = $pdo->prepare("SELECT al.*, u.username FROM admin_logs al JOIN users u ON u.id = al.admin_id ORDER BY al.created_at DESC LIMIT 50");
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<div class="p-3">';
echo '<h6 class="mb-3">Audit Logs (last 50)</h6>';
if (!$logs) {
    echo '<div class="text-muted">No logs yet.</div>';
} else {
    echo '<div class="table-responsive">';
    echo '<table class="table table-sm table-dark table-striped align-middle">';
    echo '<thead><tr><th>Time</th><th>Admin</th><th>Action</th><th>Target</th><th>Description</th></tr></thead><tbody>';
    foreach ($logs as $l) {
        $target = htmlspecialchars(($l['target_type'] ?? '') . ':' . ($l['target_id'] ?? ''));
        echo '<tr>';
        echo '<td>' . htmlspecialchars($l['created_at']) . '</td>';
        echo '<td>' . htmlspecialchars($l['username']) . '</td>';
        echo '<td>' . htmlspecialchars($l['action']) . '</td>';
        echo '<td>' . $target . '</td>';
        echo '<td>' . htmlspecialchars($l['description'] ?? '') . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
}

echo '</div>';
?>
