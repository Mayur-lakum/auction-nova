<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login to manage watchlist']);
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
$action = $_POST['action']; // 'add' or 'remove'
$user_id = $_SESSION['user_id'];

if (!$auction_id || !in_array($action, ['add', 'remove'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Check if auction exists
    $auction_query = "SELECT id FROM auctions WHERE id = ? AND status = 'active'";
    $auction_stmt = $conn->prepare($auction_query);
    $auction_stmt->execute([$auction_id]);
    
    if (!$auction_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Auction not found']);
        exit;
    }
    
    if ($action === 'add') {
        // Add to watchlist
        $insert_query = "INSERT IGNORE INTO watchlist (user_id, auction_id) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->execute([$user_id, $auction_id]);
        
        echo json_encode(['success' => true, 'message' => 'Added to watchlist']);
    } else {
        // Remove from watchlist
        $delete_query = "DELETE FROM watchlist WHERE user_id = ? AND auction_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->execute([$user_id, $auction_id]);
        
        echo json_encode(['success' => true, 'message' => 'Removed from watchlist']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
