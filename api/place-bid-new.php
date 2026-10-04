<?php
// Start session
session_start();

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/auth.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to place a bid']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and validate input
$auctionId = isset($_POST['auction_id']) ? (int)$_POST['auction_id'] : 0;
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;

if ($auctionId <= 0 || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid auction ID or bid amount']);
    exit;
}

try {
    // Initialize database
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get auction details
    $auctionQuery = "SELECT a.*, u.username as seller_name FROM auctions a 
                    JOIN users u ON a.seller_id = u.id 
                    WHERE a.id = ? AND a.status = 'active' AND a.end_time > NOW()";
    $auctionStmt = $pdo->prepare($auctionQuery);
    $auctionStmt->execute([$auctionId]);
    $auction = $auctionStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$auction) {
        echo json_encode(['success' => false, 'message' => 'Auction not found or not active']);
        exit;
    }
    
    // Check if user is not the seller
    if ($auction['seller_id'] == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot bid on your own auction']);
        exit;
    }
    
    // Get current highest bid
    $bidQuery = "SELECT MAX(amount) as current_bid FROM bids WHERE auction_id = ?";
    $bidStmt = $pdo->prepare($bidQuery);
    $bidStmt->execute([$auctionId]);
    $currentBid = $bidStmt->fetch(PDO::FETCH_ASSOC)['current_bid'] ?: $auction['starting_price'];
    
    // Validate bid amount
    $minBid = $currentBid + 1;
    if ($amount < $minBid) {
        echo json_encode([
            'success' => false, 
            'message' => "Minimum bid is $" . number_format($minBid, 2)
        ]);
        exit;
    }
    
    // Place the bid
    $insertBidQuery = "INSERT INTO bids (auction_id, user_id, amount, created_at) 
                       VALUES (?, ?, ?, NOW())";
    $insertBidStmt = $pdo->prepare($insertBidQuery);
    $insertBidStmt->execute([$auctionId, $_SESSION['user_id'], $amount]);
    
    // Update auction current price
    $updateAuctionQuery = "UPDATE auctions SET current_price = ? WHERE id = ?";
    $updateAuctionStmt = $pdo->prepare($updateAuctionQuery);
    $updateAuctionStmt->execute([$amount, $auctionId]);
    
    // Get user info for notification
    $userQuery = "SELECT username FROM users WHERE id = ?";
    $userStmt = $pdo->prepare($userQuery);
    $userStmt->execute([$_SESSION['user_id']]);
    $username = $userStmt->fetch(PDO::FETCH_ASSOC)['username'];
    
    echo json_encode([
        'success' => true,
        'message' => "Bid of $" . number_format($amount, 2) . " placed successfully!",
        'new_bid' => $amount,
        'username' => $username
    ]);
    
} catch (Exception $e) {
    error_log('Error placing bid: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while placing your bid']);
}
?>
