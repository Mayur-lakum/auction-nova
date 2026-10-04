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
    
    // Check if user owns the auction and it has no bids
    $auction_query = "SELECT a.seller_id, a.status, 
                      (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count
                      FROM auctions a WHERE a.id = ?";
    $auction_stmt = $conn->prepare($auction_query);
    $auction_stmt->execute([$auction_id]);
    $auction = $auction_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$auction) {
        echo json_encode(['success' => false, 'message' => 'Auction not found']);
        exit;
    }
    
    if ($auction['seller_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'You can only delete your own auctions']);
        exit;
    }
    
    if ($auction['bid_count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete auction with existing bids']);
        exit;
    }
    
    // Get auction images to delete
    $images_query = "SELECT image_path FROM auction_images WHERE auction_id = ?";
    $images_stmt = $conn->prepare($images_query);
    $images_stmt->execute([$auction_id]);
    $images = $images_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Delete auction (cascade will handle related records)
    $delete_query = "DELETE FROM auctions WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->execute([$auction_id]);
    
    // Delete image files
    foreach ($images as $image) {
        $image_path = '../uploads/' . $image['image_path'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Auction deleted successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to delete auction']);
}
?>
