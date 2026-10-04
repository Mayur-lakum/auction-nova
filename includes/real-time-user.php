<?php
// Real-time updates for user side
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/functions.php';

$action = $_GET['action'] ?? '';

$db = Database::getInstance();

switch ($action) {
    case 'get_auctions':
        $featured_auctions = $db->fetchAll("
            SELECT a.*, c.name as category_name, u.username as seller_name,
                   (SELECT image_path FROM auction_images WHERE auction_id = a.id AND is_primary = TRUE LIMIT 1) as primary_image,
                   (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count
            FROM auctions a 
            JOIN categories c ON a.category_id = c.id 
            JOIN users u ON a.seller_id = u.id 
            WHERE a.status = 'active' AND a.end_time > NOW() AND a.is_featured = TRUE 
            ORDER BY a.created_at DESC LIMIT 6
        ");
        
        $recent_auctions = $db->fetchAll("
            SELECT a.*, c.name as category_name, u.username as seller_name,
                   (SELECT image_path FROM auction_images WHERE auction_id = a.id AND is_primary = TRUE LIMIT 1) as primary_image,
                   (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count
            FROM auctions a 
            JOIN categories c ON a.category_id = c.id 
            JOIN users u ON a.seller_id = u.id 
            WHERE a.status = 'active' AND a.end_time > NOW() 
            ORDER BY a.created_at DESC LIMIT 8
        ");
        
        echo json_encode([
            'success' => true, 
            'featured' => $featured_auctions,
            'recent' => $recent_auctions
        ]);
        break;
        
    case 'get_stats':
        $stats = [
            'total_users' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'user' AND is_active = TRUE")['count'],
            'active_auctions' => $db->fetch("SELECT COUNT(*) as count FROM auctions WHERE status = 'active' AND end_time > NOW()")['count'],
            'completed_auctions' => $db->fetch("SELECT COUNT(*) as count FROM auctions WHERE status = 'ended'")['count'],
            'total_bids' => $db->fetch("SELECT COUNT(*) as count FROM bids")['count']
        ];
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
