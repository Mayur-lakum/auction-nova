<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/functions.php';

// Admin authentication check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'super_admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';

$db = Database::getInstance();

switch ($action) {
    case 'get_stats':
        $stats = [
            'total_users' => $db->fetch("SELECT COUNT(*) as count FROM users")['count'],
            'total_auctions' => $db->fetch("SELECT COUNT(*) as count FROM auctions")['count'],
            'active_auctions' => $db->fetch("SELECT COUNT(*) as count FROM auctions WHERE status = 'active' AND end_time > NOW()")['count'],
            'total_bids' => $db->fetch("SELECT COUNT(*) as count FROM bids")['count'],
            'pending_reports' => $db->fetch("SELECT COUNT(*) as count FROM reports WHERE status = 'pending'")['count'] ?? 0
        ];
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;
        
    case 'get_recent_auctions':
        $recent_auctions = $db->fetchAll("
            SELECT a.*, u.username, c.name as category_name,
                   (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count
            FROM auctions a 
            JOIN users u ON a.seller_id = u.id 
            LEFT JOIN categories c ON a.category_id = c.id 
            ORDER BY a.created_at DESC 
            LIMIT 5
        ");
        echo json_encode(['success' => true, 'auctions' => $recent_auctions]);
        break;
        
    case 'get_recent_users':
        $recent_users = $db->fetchAll("
            SELECT * FROM users 
            WHERE role = 'user' 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        echo json_encode(['success' => true, 'users' => $recent_users]);
        break;
        
    case 'broadcast_update':
        // This would trigger WebSocket or push notification
        $update_type = $_GET['type'] ?? '';
        $data = $_GET['data'] ?? '';
        
        // Store update in a temporary table for polling
        $db->query("INSERT INTO live_updates (type, data, created_at) VALUES (?, ?, NOW())", [$update_type, $data]);
        
        echo json_encode(['success' => true, 'message' => 'Update broadcasted']);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
