<?php
// Start session
session_start();

// Load configuration
require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/auth.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'], true)) {
    echo '<div class="alert alert-danger">Access denied. Admin privileges required.</div>';
    exit;
}

// Initialize database
$db = Database::getInstance();
$pdo = $db->getConnection();

// Get system statistics
$stats = [
    'total_users' => 0,
    'total_auctions' => 0,
    'active_auctions' => 0,
    'completed_auctions' => 0,
    'total_bids' => 0,
    'total_revenue' => 0
];

// Get user statistics
$user_stats_query = "SELECT COUNT(*) as total_users, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users FROM users";
$user_stmt = $pdo->prepare($user_stats_query);
$user_stmt->execute();
$user_stats = $user_stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_users'] = $user_stats['total_users'];

// Get auction statistics
$auction_stats_query = "SELECT 
    COUNT(*) as total_auctions,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_auctions,
    SUM(CASE WHEN status = 'ended' THEN 1 ELSE 0 END) as completed_auctions
    FROM auctions";
$auction_stmt = $pdo->prepare($auction_stats_query);
$auction_stmt->execute();
$auction_stats = $auction_stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_auctions'] = $auction_stats['total_auctions'];
$stats['active_auctions'] = $auction_stats['active_auctions'];
$stats['completed_auctions'] = $auction_stats['completed_auctions'];

// Get bid statistics
$bid_stats_query = "SELECT COUNT(*) as total_bids, SUM(bid_amount) as total_revenue FROM bids";
$bid_stmt = $pdo->prepare($bid_stats_query);
$bid_stmt->execute();
$bid_stats = $bid_stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_bids'] = $bid_stats['total_bids'];
$stats['total_revenue'] = $bid_stats['total_revenue'] ?: 0;

echo '<div class="row g-3 mb-4">';
echo '<div class="col-md-3">';
echo '<div class="card text-center border-primary">';
echo '<div class="card-body">';
echo '<h3 class="text-primary">' . $stats['total_users'] . '</h3>';
echo '<p class="mb-0">Total Users</p>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '<div class="col-md-3">';
echo '<div class="card text-center border-success">';
echo '<div class="card-body">';
echo '<h3 class="text-success">' . $stats['total_auctions'] . '</h3>';
echo '<p class="mb-0">Total Auctions</p>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '<div class="col-md-3">';
echo '<div class="card text-center border-info">';
echo '<div class="card-body">';
echo '<h3 class="text-info">' . $stats['total_bids'] . '</h3>';
echo '<p class="mb-0">Total Bids</p>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '<div class="col-md-3">';
echo '<div class="card text-center border-warning">';
echo '<div class="card-body">';
echo '<h3 class="text-warning">$' . number_format($stats['total_revenue'], 2) . '</h3>';
echo '<p class="mb-0">Total Revenue</p>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Recent activity
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h6 class="mb-0">Recent Activity</h6>';
echo '</div>';
echo '<div class="card-body">';

// Recent auctions
$recent_auctions_query = "SELECT a.title, u.username as seller, a.created_at 
                         FROM auctions a 
                         JOIN users u ON a.seller_id = u.id 
                         ORDER BY a.created_at DESC LIMIT 5";
$recent_auctions_stmt = $pdo->prepare($recent_auctions_query);
$recent_auctions_stmt->execute();
$recent_auctions = $recent_auctions_stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<h6 class="mb-3">Recent Auctions</h6>';
echo '<ul class="list-group">';
foreach ($recent_auctions as $auction) {
    echo '<li class="list-group-item d-flex justify-content-between">';
    echo '<span>' . htmlspecialchars($auction['title']) . ' by ' . htmlspecialchars($auction['seller']) . '</span>';
    echo '<small class="text-muted">' . date('M j, g:i A', strtotime($auction['created_at'])) . '</small>';
    echo '</li>';
}
echo '</ul>';

echo '</div>';
echo '</div>';
?>
