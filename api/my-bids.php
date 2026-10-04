<?php
// Start session
session_start();

// Load configuration
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/auth.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in']);
    exit;
}

try {
    // Initialize database
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $user_id = $_SESSION['user_id'];
    
    // Get user's bids
    $bids_query = "SELECT b.*, a.title as auction_title, a.status as auction_status, a.end_time,
                   (SELECT MAX(bid_amount) FROM bids WHERE auction_id = b.auction_id) as highest_bid
                   FROM bids b 
                   JOIN auctions a ON b.auction_id = a.id 
                   WHERE b.bidder_id = ? 
                   ORDER BY b.bid_time DESC";
    
    $bids_stmt = $pdo->prepare($bids_query);
    $bids_stmt->execute([$user_id]);
    $bids = $bids_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Determine if each bid is winning
    foreach ($bids as &$bid) {
        $bid['is_winning'] = ($bid['bid_amount'] == $bid['highest_bid']);
    }
    
    echo json_encode([
        'success' => true,
        'bids' => $bids
    ]);
    
} catch (Exception $e) {
    error_log('Error loading my bids: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error loading bids']);
}
?>
