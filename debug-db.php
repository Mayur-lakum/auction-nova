<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

echo "<h2>Latest 5 auctions</h2>";
$stmt = $pdo->query("SELECT id, title, status, end_time, created_at FROM auctions ORDER BY created_at DESC LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}

echo "<h2>Active/live auctions (status='active' AND end_time > NOW())</h2>";
$stmt = $pdo->prepare("SELECT id, title, status, end_time, created_at FROM auctions WHERE status = 'active' AND end_time > NOW() ORDER BY created_at DESC");
$stmt->execute();
$count = $stmt->rowCount();
echo "<p>Count: $count</p>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}

echo "<h2>Current time</h2>";
echo "<p>" . date('Y-m-d H:i:s') . "</p>";
?>
