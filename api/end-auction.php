<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login to perform this action']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$auction_id = (int)$_POST['auction_id'];
$user_id = $_SESSION['user_id'];

if (!$auction_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid auction ID']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Check if user owns the auction
    $auction_query = "SELECT seller_id, status, end_time FROM auctions WHERE id = ?";
    $auction_stmt = $conn->prepare($auction_query);
    $auction_stmt->execute([$auction_id]);
    $auction = $auction_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$auction) {
        echo json_encode(['success' => false, 'message' => 'Auction not found']);
        exit;
    }
    
    if ($auction['seller_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'You can only end your own auctions']);
        exit;
    }
    
    if ($auction['status'] !== 'active') {
        echo json_encode(['success' => false, 'message' => 'This auction is not active']);
        exit;
    }
    
    // End the auction
    $update_query = "UPDATE auctions SET status = 'ended', end_time = NOW() WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->execute([$auction_id]);
    
    // Notify all bidders
    $bidders_query = "SELECT DISTINCT bidder_id FROM bids WHERE auction_id = ?";
    $bidders_stmt = $conn->prepare($bidders_query);
    $bidders_stmt->execute([$auction_id]);
    $bidders = $bidders_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($bidders as $bidder) {
        send_notification(
            $bidder['bidder_id'],
            'auction_ended',
            'Auction ended early',
            'An auction you were bidding on has been ended early by the seller.',
            $auction_id
        );
    }
    
    echo json_encode(['success' => true, 'message' => 'Auction ended successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to end auction']);
}
?>
