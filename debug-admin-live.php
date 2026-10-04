<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

$sql = "SELECT a.id, a.title, a.current_price, a.end_time, a.image, (SELECT COUNT(*) FROM bids b WHERE b.auction_id=a.id) AS bid_count FROM auctions a WHERE a.status='active' AND a.end_time > NOW() ORDER BY a.created_at DESC LIMIT 10";

echo "<h2>Admin live auctions query</h2>";
echo "<p>SQL: $sql</p>";

$live_auctions = $db->fetchAll($sql);
echo "<p>Count: " . count($live_auctions) . "</p>";

foreach ($live_auctions as $a) {
    echo "<pre>" . print_r($a, true) . "</pre>";
}
?>
