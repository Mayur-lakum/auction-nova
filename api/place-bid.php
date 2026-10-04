<?php
header('Content-Type: application/json');
require_once '../includes/Database.php';
require_once '../includes/live_events.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Debug logging
error_log("BID API: Raw input: " . $json);
error_log("BID API: Parsed data: " . print_r($data, true));

if (!$data || !isset($data['auction_id']) || !isset($data['bid_amount'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data', 'debug' => $data]);
    exit;
}

$auction_id = (int)$data['auction_id'];
$bid_amount = (float)$data['bid_amount'];
$user_id = $_SESSION['user_id'];

error_log("BID API: Processing auction_id=$auction_id, bid_amount=$bid_amount, user_id=$user_id");

if ($auction_id <= 0 || $bid_amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid auction or bid amount']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Get auction
    $stmt = $conn->prepare("SELECT * FROM auctions WHERE id = ? AND status = 'active' AND end_time > NOW()");
    $stmt->execute([$auction_id]);
    $auction = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("BID API: Auction found: " . ($auction ? 'YES' : 'NO'));

    if (!$auction) {
        echo json_encode(['success' => false, 'message' => 'Auction not found or has ended']);
        exit;
    }

    if ($auction['seller_id'] == $user_id) {
        echo json_encode(['success' => false, 'message' => 'You cannot bid on your own auction']);
        exit;
    }

    $min_increment = $auction['bid_increment'] ?? $auction['min_increment'] ?? 10;
    $min_bid = $auction['current_price'] + $min_increment;

    error_log("BID API: Current price={$auction['current_price']}, min_increment=$min_increment, min_bid=$min_bid");

    if ($bid_amount < $min_bid) {
        echo json_encode(['success' => false, 'message' => "Minimum bid is $" . number_format($min_bid, 2)]);
        exit;
    }

    // Place bid
    $conn->beginTransaction();
    
    $stmt = $conn->prepare("INSERT INTO bids (auction_id, bidder_id, bid_amount, bid_time) VALUES (?, ?, ?, NOW())");
    $result1 = $stmt->execute([$auction_id, $user_id, $bid_amount]);
    error_log("BID API: Insert bid result: " . ($result1 ? 'SUCCESS' : 'FAILED'));
    
    $stmt = $conn->prepare("UPDATE auctions SET current_price = ? WHERE id = ?");
    $result2 = $stmt->execute([$bid_amount, $auction_id]);
    error_log("BID API: Update auction result: " . ($result2 ? 'SUCCESS' : 'FAILED'));
    
    $conn->commit();
    error_log("BID API: Transaction committed");

    // Get username
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $username = $user['username'] ?? 'User' . $user_id;

    echo json_encode([
        'success' => true,
        'message' => 'Bid placed successfully!',
        'new_price' => $bid_amount,
        'bid_amount' => $bid_amount,
        'username' => $username,
        'timestamp' => date('Y-m-d H:i:s'),
        'debug' => [
            'old_price' => $auction['current_price'],
            'new_price' => $bid_amount,
            'auction_id' => $auction_id
        ]
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("BID API ERROR: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to place bid', 'error' => $e->getMessage()]);
}
?>
