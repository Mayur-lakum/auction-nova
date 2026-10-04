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
    
    // Get watchlist items
    $watchlist_query = "SELECT w.*, a.title, a.current_price as current_bid, a.end_time,
                       (SELECT image_path FROM auction_images WHERE auction_id = a.id AND is_primary = TRUE LIMIT 1) as image
                       FROM watchlist w 
                       JOIN auctions a ON w.auction_id = a.id 
                       WHERE w.user_id = ? 
                       ORDER BY w.created_at DESC";
    
    $watchlist_stmt = $pdo->prepare($watchlist_query);
    $watchlist_stmt->execute([$user_id]);
    $watchlist = $watchlist_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'watchlist' => $watchlist
    ]);
    
} catch (Exception $e) {
    error_log('Error loading watchlist: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error loading watchlist']);
}
?>
