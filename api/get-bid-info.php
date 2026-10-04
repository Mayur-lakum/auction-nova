<?php
header('Content-Type: application/json');
require_once '../includes/Database.php';

if (!isset($_GET['auction_id']) || !is_numeric($_GET['auction_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid auction ID']);
    exit;
}

$auction_id = (int)$_GET['auction_id'];

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get auction
    $stmt = $conn->prepare("SELECT current_price, bid_increment, min_increment, status, end_time FROM auctions WHERE id = ?");
    $stmt->execute([$auction_id]);
    $auction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$auction) {
        echo json_encode(['success' => false, 'message' => 'Auction not found']);
        exit;
    }
    
    // Get bid count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bids WHERE auction_id = ?");
    $stmt->execute([$auction_id]);
    $bid_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get latest bids
    $stmt = $conn->prepare("SELECT b.bid_amount, b.bid_time, u.username 
                   FROM bids b 
                   LEFT JOIN users u ON b.bidder_id = u.id 
                   WHERE b.auction_id = ? 
                   ORDER BY b.bid_time DESC 
                   LIMIT 10");
    $stmt->execute([$auction_id]);
    $latest_bids = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get winner (if ended / sold)
    $stmt = $conn->prepare("SELECT b.bid_amount, b.bid_time, u.username 
                   FROM bids b 
                   LEFT JOIN users u ON b.bidder_id = u.id 
                   WHERE b.auction_id = ? AND b.is_winning = 1 
                   ORDER BY b.bid_amount DESC, b.bid_time ASC 
                   LIMIT 1");
    $stmt->execute([$auction_id]);
    $winner = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    
    echo json_encode([
        'success' => true,
        'current_price' => $auction['current_price'],
        'min_increment' => ($auction['bid_increment'] ?? ($auction['min_increment'] ?? 10)),
        'bid_count' => $bid_count,
        'latest_bids' => $latest_bids,
        'winner' => $winner,
        'status' => $auction['status'],
        'end_time' => $auction['end_time']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
