<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

echo "<h2>All Auctions</h2>";
$stmt = $pdo->query("SELECT id, title, status, end_time, created_at FROM auctions ORDER BY created_at DESC LIMIT 10");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}

echo "<h2>Active Auctions (should be live)</h2>";
$stmt = $pdo->prepare("SELECT id, title, status, end_time, created_at FROM auctions WHERE status = 'active' AND end_time > NOW() ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}
?>
