<?php
require_once '../includes/functions.php';
require_login();

$page_title = 'My Auctions';
$db = new Database();
$conn = $db->connect();

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query conditions
$where_conditions = ["a.seller_id = ?"];
$params = [$_SESSION['user_id']];

if ($status_filter !== 'all') {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM auctions a WHERE $where_clause";
$count_stmt = $conn->prepare($count_query);
$count_stmt->execute($params);
$total_auctions = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_auctions / $per_page);

// Get auctions
$auctions_query = "SELECT a.*, c.name as category_name,
                   (SELECT image_path FROM auction_images WHERE auction_id = a.id AND is_primary = TRUE LIMIT 1) as primary_image,
                   (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count,
                   (SELECT COUNT(*) FROM watchlist WHERE auction_id = a.id) as watch_count,
                   (SELECT MAX(bid_amount) FROM bids WHERE auction_id = a.id) as highest_bid
                   FROM auctions a 
                   JOIN categories c ON a.category_id = c.id 
                   WHERE $where_clause 
                   ORDER BY a.created_at DESC 
                   LIMIT $per_page OFFSET $offset";

$auctions_stmt = $conn->prepare($auctions_query);
$auctions_stmt->execute($params);
$auctions = $auctions_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "SELECT 
                COUNT(*) as total_auctions,
                SUM(CASE WHEN status = 'active' AND end_time > NOW() THEN 1 ELSE 0 END) as active_auctions,
                SUM(CASE WHEN status = 'ended' THEN 1 ELSE 0 END) as ended_auctions,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_auctions,
                (SELECT COUNT(*) FROM bids b JOIN auctions a ON b.auction_id = a.id WHERE a.seller_id = ?) as total_bids
                FROM auctions WHERE seller_id = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">My Auctions</h2>
            <p class="text-muted mb-0">Manage your auction listings</p>
        </div>
        <a href="create-auction.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Create New Auction
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_auctions']; ?></div>
                <div class="stat-label">Total Auctions</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
                <div class="stat-number"><?php echo $stats['active_auctions']; ?></div>
                <div class="stat-label">Active Auctions</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
                <div class="stat-number"><?php echo $stats['ended_auctions']; ?></div>
                <div class="stat-label">Ended Auctions</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card" style="background: linear-gradient(135deg, #dc3545, #e83e8c);">
                <div class="stat-number"><?php echo $stats['total_bids']; ?></div>
                <div class="stat-label">Total Bids Received</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="btn-group" role="group">
                        <a href="?status=all" class="btn <?php echo $status_filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            All (<?php echo $stats['total_auctions']; ?>)
                        </a>
                        <a href="?status=active" class="btn <?php echo $status_filter === 'active' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            Active (<?php echo $stats['active_auctions']; ?>)
                        </a>
                        <a href="?status=ended" class="btn <?php echo $status_filter === 'ended' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            Ended (<?php echo $stats['ended_auctions']; ?>)
                        </a>
                        <a href="?status=draft" class="btn <?php echo $status_filter === 'draft' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            Draft (<?php echo $stats['draft_auctions']; ?>)
                        </a>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        Showing <?php echo count($auctions); ?> of <?php echo $total_auctions; ?> auctions
                    </small>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($auctions)): ?>
    <!-- No Auctions -->
    <div class="text-center py-5">
        <i class="fas fa-gavel fa-4x text-muted mb-3"></i>
        <h4>No auctions found</h4>
        <p class="text-muted mb-4">
            <?php if ($status_filter === 'all'): ?>
            You haven't created any auctions yet. Start selling your items today!
            <?php else: ?>
            No <?php echo $status_filter; ?> auctions found. Try a different filter.
            <?php endif; ?>
        </p>
        <a href="create-auction.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Create Your First Auction
        </a>
    </div>
    <?php else: ?>

    <!-- Auctions List -->
    <div class="row g-4">
        <?php foreach ($auctions as $auction): ?>
        <div class="col-12">
            <div class="card auction-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <!-- Image -->
                        <div class="col-md-2">
                            <?php if ($auction['primary_image']): ?>
                            <img src="<?php echo auction_image_url($auction['primary_image']); ?>" 
                                 class="img-fluid rounded" style="height: 80px; width: 100%; object-fit: cover;" 
                                 onerror="this.onerror=null;this.src='<?php echo SITE_URL; ?>assets/images/no-image.jpg';"
                                 alt="<?php echo htmlspecialchars($auction['title']); ?>">
                            <?php else: ?>
                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 80px;">
                                <i class="fas fa-image text-muted"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Details -->
                        <div class="col-md-4">
                            <h5 class="card-title mb-1">
                                <a href="<?php echo SITE_URL; ?>auction-details.php?id=<?php echo $auction['id']; ?>" 
                                   class="text-decoration-none">
                                    <?php echo htmlspecialchars($auction['title']); ?>
                                </a>
                            </h5>
                            <p class="text-muted small mb-1">
                                <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($auction['category_name']); ?>
                            </p>
                            <p class="text-muted small mb-0">
                                Created <?php echo time_ago($auction['created_at']); ?>
                            </p>
                        </div>
                        
                        <!-- Status & Stats -->
                        <div class="col-md-3">
                            <div class="mb-2">
                                <?php
                                $status_class = '';
                                $status_icon = '';
                                switch ($auction['status']) {
                                    case 'active':
                                        if (strtotime($auction['end_time']) > time()) {
                                            $status_class = 'success';
                                            $status_icon = 'play-circle';
                                        } else {
                                            $status_class = 'warning';
                                            $status_icon = 'clock';
                                        }
                                        break;
                                    case 'ended':
                                        $status_class = 'danger';
                                        $status_icon = 'stop-circle';
                                        break;
                                    case 'draft':
                                        $status_class = 'secondary';
                                        $status_icon = 'edit';
                                        break;
                                }
                                ?>
                                <span class="badge bg-<?php echo $status_class; ?>">
                                    <i class="fas fa-<?php echo $status_icon; ?> me-1"></i>
                                    <?php echo ucfirst($auction['status']); ?>
                                </span>
                                
                                <?php if ($auction['is_featured']): ?>
                                <span class="badge bg-warning ms-1">
                                    <i class="fas fa-star me-1"></i>Featured
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="small text-muted">
                                <div><i class="fas fa-gavel me-1"></i><?php echo $auction['bid_count']; ?> bids</div>
                                <div><i class="fas fa-heart me-1"></i><?php echo $auction['watch_count']; ?> watching</div>
                                <div><i class="fas fa-eye me-1"></i><?php echo number_format($auction['view_count']); ?> views</div>
                            </div>
                        </div>
                        
                        <!-- Price & Time -->
                        <div class="col-md-2">
                            <div class="text-center">
                                <div class="price-display"><?php echo format_currency($auction['current_price']); ?></div>
                                <small class="text-muted d-block">Current Price</small>
                                
                                <?php if ($auction['status'] === 'active' && strtotime($auction['end_time']) > time()): ?>
                                <div class="countdown-timer mt-2" data-end-time="<?php echo $auction['end_time']; ?>">
                                    <?php echo time_remaining($auction['end_time']); ?>
                                </div>
                                <?php else: ?>
                                <small class="text-muted">
                                    Ended <?php echo time_ago($auction['end_time']); ?>
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="col-md-1">
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" 
                                        data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>auction-details.php?id=<?php echo $auction['id']; ?>">
                                            <i class="fas fa-eye me-2"></i>View Details
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="view-bids.php?id=<?php echo $auction['id']; ?>">
                                            <i class="fas fa-list me-2"></i>View Bids (<?php echo $auction['bid_count']; ?>)
                                        </a>
                                    </li>
                                    <?php if ($auction['status'] === 'draft' || ($auction['status'] === 'active' && $auction['bid_count'] == 0)): ?>
                                    <li>
                                        <a class="dropdown-item" href="edit-auction.php?id=<?php echo $auction['id']; ?>">
                                            <i class="fas fa-edit me-2"></i>Edit
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($auction['status'] === 'active' && strtotime($auction['end_time']) > time()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button type="button" class="dropdown-item text-warning" onclick="endAuction(<?php echo $auction['id']; ?>)">
                                            <i class="fas fa-stop me-2"></i>End Early
                                        </button>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($auction['bid_count'] == 0): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button type="button" class="dropdown-item text-danger" onclick="deleteAuction(<?php echo $auction['id']; ?>)">
                                            <i class="fas fa-trash me-2"></i>Delete
                                        </button>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Auction pagination" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            </li>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                    <?php echo $i; ?>
                </a>
            </li>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>

    <?php endif; ?>
</div>

<!-- Alert Container -->
<div id="alert-container" class="container mt-3"></div>

<!-- CSRF Token -->
<input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

<script>
var siteUrl = '<?php echo SITE_URL; ?>';

$(document).ready(function() {
    // Initialize countdown timers
    updateCountdowns();
    setInterval(updateCountdowns, 1000);
});

function endAuction(auctionId) {
    if (confirm('Are you sure you want to end this auction early? This action cannot be undone.')) {
        $.ajax({
            url: siteUrl + 'api/end-auction.php',
            method: 'POST',
            data: {
                auction_id: auctionId,
                csrf_token: $('input[name="csrf_token"]').val()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'An error occurred while ending the auction.');
            }
        });
    }
}

function deleteAuction(auctionId) {
    if (confirm('Are you sure you want to delete this auction? This action cannot be undone.')) {
        $.ajax({
            url: siteUrl + 'api/delete-auction.php',
            method: 'POST',
            data: {
                auction_id: auctionId,
                csrf_token: $('input[name="csrf_token"]').val()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'An error occurred while deleting the auction.');
            }
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>
