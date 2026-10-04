<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/functions.php';

$db = Database::getInstance();

echo "<h2>Auction Debug Info</h2>";

// Check draft auctions
$drafts = $db->fetchAll("SELECT id, title, end_time, status FROM auctions WHERE status = 'draft' LIMIT 10");
echo "<h3>Draft Auctions (" . count($drafts) . ")</h3>";
foreach($drafts as $d) {
    echo "ID: {$d['id']}, Title: {$d['title']}, End: {$d['end_time']}, Status: {$d['status']}<br>";
}

// Check active auctions
$active = $db->fetchAll("SELECT id, title, end_time, status FROM auctions WHERE status = 'active' LIMIT 10");
echo "<h3>Active Auctions (" . count($active) . ")</h3>";
foreach($active as $a) {
    echo "ID: {$a['id']}, Title: {$a['title']}, End: {$a['end_time']}, Status: {$a['status']}<br>";
}

// Check what user would see
$user_visible = $db->fetchAll("SELECT id, title, end_time, status FROM auctions WHERE status = 'active' AND end_time > NOW() LIMIT 10");
echo "<h3>User Visible Auctions (" . count($user_visible) . ")</h3>";
foreach($user_visible as $u) {
    echo "ID: {$u['id']}, Title: {$u['title']}, End: {$u['end_time']}, Status: {$u['status']}<br>";
}

echo "<h3>Current Time: " . date('Y-m-d H:i:s') . "</h3>";
?>
