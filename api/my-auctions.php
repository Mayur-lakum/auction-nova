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
    
    // Get user's auctions
    $auctions_query = "SELECT a.*, c.name as category_name,
                      (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count,
                      (SELECT MAX(amount) FROM bids WHERE auction_id = a.id) as highest_bid
                      FROM auctions a 
                      JOIN categories c ON a.category_id = c.id 
                      WHERE a.seller_id = ? 
                      ORDER BY a.created_at DESC";
    
    $auctions_stmt = $pdo->prepare($auctions_query);
    $auctions_stmt->execute([$user_id]);
    $auctions = $auctions_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'auctions' => $auctions
    ]);
    
} catch (Exception $e) {
    error_log('Error loading my auctions: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error loading auctions']);
}
?>
