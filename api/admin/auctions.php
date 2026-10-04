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

// Handle actions
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        displayAuctions();
        break;
    case 'edit':
        editAuction();
        break;
    case 'delete':
        deleteAuction();
        break;
    default:
        displayAuctions();
}

function displayAuctions() {
    global $pdo;
    
    $query = "SELECT a.*, c.name as category_name, u.username as seller_name,
              (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count,
              (SELECT MAX(bid_amount) FROM bids WHERE auction_id = a.id) as highest_bid
              FROM auctions a 
              JOIN categories c ON a.category_id = c.id 
              JOIN users u ON a.seller_id = u.id 
              ORDER BY a.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<div class="card">';
    echo '<div class="card-header d-flex justify-content-between align-items-center">';
    echo '<h6 class="mb-0">All Auctions</h6>';
    echo '<button class="btn btn-sm btn-primary" onclick="showAddAuctionModal()">Add Auction</button>';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>ID</th><th>Title</th><th>Seller</th><th>Category</th><th>Status</th><th>Bids</th><th>Highest Bid</th><th>End Time</th><th>Actions</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($auctions as $auction) {
        $status_badge = getStatusBadge($auction['status']);
        
        echo '<tr>';
        echo '<td>' . $auction['id'] . '</td>';
        echo '<td>' . htmlspecialchars($auction['title']) . '</td>';
        echo '<td>' . htmlspecialchars($auction['seller_name']) . '</td>';
        echo '<td>' . htmlspecialchars($auction['category_name']) . '</td>';
        echo '<td>' . $status_badge . '</td>';
        echo '<td>' . $auction['bid_count'] . '</td>';
        echo '<td>$' . number_format($auction['highest_bid'] ?: $auction['starting_price'], 2) . '</td>';
        echo '<td>' . date('M j, g:i A', strtotime($auction['end_time'])) . '</td>';
        echo '<td>';
        echo '<div class="btn-group btn-group-sm">';
        echo '<button class="btn btn-outline-primary" onclick="editAuction(' . $auction['id'] . ')">Edit</button>';
        echo '<button class="btn btn-outline-danger" onclick="deleteAuction(' . $auction['id'] . ')">Delete</button>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

function getStatusBadge($status) {
    switch ($status) {
        case 'active':
            return '<span class="badge bg-success">Active</span>';
        case 'draft':
            return '<span class="badge bg-info">Draft</span>';
        case 'ended':
            return '<span class="badge bg-secondary">Ended</span>';
        case 'cancelled':
            return '<span class="badge bg-danger">Cancelled</span>';
        default:
            return '<span class="badge bg-secondary">Unknown</span>';
    }
}

function editAuction() {
    echo '<div class="alert alert-info">Edit auction functionality coming soon...</div>';
}

function deleteAuction() {
    echo '<div class="alert alert-info">Delete auction functionality coming soon...</div>';
}
?>
