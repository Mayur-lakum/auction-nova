<?php
require_once 'includes/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Check if any auctions exist
$check = $conn->query("SELECT COUNT(*) as count FROM auctions WHERE status = 'active' AND end_time > NOW()");
$result = $check->fetch(PDO::FETCH_ASSOC);

if ($result['count'] == 0) {
    echo "No active auctions found. Creating a test auction...";
    
    // Create a test auction
    $end_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $stmt = $conn->prepare("
        INSERT INTO auctions (title, description, seller_id, category_id, starting_price, current_price, bid_increment, end_time, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
    ");
    
    $stmt->execute([
        'Test Auction Item',
        'This is a test auction for bidding functionality.',
        1, // seller_id
        1, // category_id  
        100.00, // starting_price
        100.00, // current_price
        10.00,  // bid_increment
        $end_time
    ]);
    
    $auction_id = $conn->lastInsertId();
    echo "Created test auction with ID: $auction_id";
    echo "<br><a href='auction-details.php?id=$auction_id'>Go to auction details</a>";
} else {
    echo "Found {$result['count']} active auctions.";
    
    // Show first active auction
    $stmt = $conn->query("SELECT id, title FROM auctions WHERE status = 'active' AND end_time > NOW() LIMIT 1");
    $auction = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<br><a href='auction-details.php?id={$auction['id']}'>Go to: {$auction['title']}</a>";
}
?>
