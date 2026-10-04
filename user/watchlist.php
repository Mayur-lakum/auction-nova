<?php
// Start session and check authentication
session_start();
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/auth.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = 'My Watchlist';

// Initialize database
$db = Database::getInstance();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get total count
$count_query = "SELECT COUNT(*) as total FROM watchlist w 
                JOIN auctions a ON w.auction_id = a.id 
                WHERE w.user_id = ?";
$count_stmt = $conn->prepare($count_query);
$count_stmt->execute([$user_id]);
$total_items = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_items / $per_page);

// Get watchlist items
$watchlist_query = "SELECT w.*, a.*, c.name as category_name, u.username as seller_name,
                    (SELECT image_path FROM auction_images WHERE auction_id = a.id AND is_primary = TRUE LIMIT 1) as primary_image,
                    (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count,
                    (SELECT COUNT(*) FROM watchlist WHERE auction_id = a.id) as watch_count
                    FROM watchlist w 
                    JOIN auctions a ON w.auction_id = a.id 
                    JOIN categories c ON a.category_id = c.id 
                    JOIN users u ON a.seller_id = u.id 
                    WHERE w.user_id = ? 
                    ORDER BY w.created_at DESC 
                    LIMIT $per_page OFFSET $offset";

$watchlist_stmt = $conn->prepare($watchlist_query);
$watchlist_stmt->execute([$user_id]);
$watchlist_items = $watchlist_stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="fas fa-heart me-2 text-danger"></i>My Watchlist
            </h2>
            <p class="text-muted mb-0">Keep track of auctions you're interested in</p>
        </div>
        <a href="<?php echo SITE_URL; ?>auctions/" class="btn btn-primary">
            <i class="fas fa-search me-2"></i>Browse Auctions
        </a>
    </div>

    <?php if (empty($watchlist_items)): ?>
    <!-- Empty Watchlist -->
    <div class="text-center py-5">
        <i class="fas fa-heart fa-4x text-muted mb-4"></i>
        <h3>Your watchlist is empty</h3>
        <p class="text-muted mb-4">
            Start adding auctions to your watchlist to keep track of items you're interested in.<br>
            You'll get notifications when auctions are ending soon!
        </p>
        <a href="<?php echo SITE_URL; ?>auctions/" class="btn btn-primary btn-lg">
            <i class="fas fa-search me-2"></i>Browse Auctions
        </a>
    </div>
    <?php else: ?>

    <!-- Watchlist Stats -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4 class="fw-bold"><?php echo $total_items; ?></h4>
                    <small>Total Items</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4 class="fw-bold">
                        <?php
                        $active_count = 0;
                        foreach ($watchlist_items as $item) {
                            if (is_auction_active($item)) $active_count++;
                        }
                        echo $active_count;
                        ?>
                    </h4>
                    <small>Active Auctions</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h4 class="fw-bold">
                        <?php
                        $ending_soon = 0;
                        foreach ($watchlist_items as $item) {
                            if (is_auction_active($item) && strtotime($item['end_time']) - time() < 86400) {
                                $ending_soon++;
                            }
                        }
                        echo $ending_soon;
                        ?>
                    </h4>
                    <small>Ending Soon</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4 class="fw-bold">
                        <?php
                        $categories = array_unique(array_column($watchlist_items, 'category_name'));
                        echo count($categories);
                        ?>
                    </h4>
                    <small>Categories</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Watchlist Items -->
    <div class="row g-4">
        <?php foreach ($watchlist_items as $item): ?>
        <div class="col-lg-4 col-md-6">
            <div class="card auction-card h-100">
                <div class="position-relative">
                    <?php if ($item['primary_image']): ?>
                    <img src="<?php echo auction_image_url($item['primary_image']); ?>" 
                         class="card-img-top" alt="<?php echo htmlspecialchars($item['title']); ?>"
                         onerror="this.onerror=null;this.src='<?php echo SITE_URL; ?>assets/images/no-image.jpg';">
                    <?php else: ?>
                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center">
                        <i class="fas fa-image fa-3x text-muted"></i>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Status Badge -->
                    <?php if (is_auction_active($item)): ?>
                        <?php if (strtotime($item['end_time']) - time() < 86400): ?>
                        <span class="badge bg-warning position-absolute top-0 end-0 m-2">
                            <i class="fas fa-clock me-1"></i>Ending Soon
                        </span>
                        <?php else: ?>
                        <span class="badge bg-success position-absolute top-0 end-0 m-2">
                            <i class="fas fa-play-circle me-1"></i>Active
                        </span>
                        <?php endif; ?>
                    <?php else: ?>
                    <span class="badge bg-danger position-absolute top-0 end-0 m-2">
                        <i class="fas fa-stop-circle me-1"></i>Ended
                    </span>
                    <?php endif; ?>
                    
                    <!-- Countdown Timer -->
                    <?php if (is_auction_active($item)): ?>
                    <div class="countdown-timer position-absolute bottom-0 start-0 m-2" 
                         data-end-time="<?php echo $item['end_time']; ?>">
                        <?php echo time_remaining($item['end_time']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Remove from Watchlist -->
                    <button class="btn btn-danger btn-sm position-absolute top-0 start-0 m-2" 
                            onclick="removeFromWatchlist(<?php echo $item['auction_id']; ?>)"
                            title="Remove from Watchlist">
                        <i class="fas fa-heart"></i>
                    </button>
                </div>
                
                <div class="card-body">
                    <h5 class="card-title">
                        <?php echo htmlspecialchars(substr($item['title'], 0, 50)); ?>
                        <?php if (strlen($item['title']) > 50): ?>...<?php endif; ?>
                    </h5>
                    
                    <p class="card-text text-muted small mb-2">
                        <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($item['category_name']); ?>
                        <span class="ms-2">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($item['seller_name']); ?>
                        </span>
                    </p>
                    
                    <p class="card-text small text-muted">
                        <?php echo htmlspecialchars(substr($item['description'], 0, 80)); ?>
                        <?php if (strlen($item['description']) > 80): ?>...<?php endif; ?>
                    </p>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div class="price-display"><?php echo format_currency($item['current_price']); ?></div>
                            <small class="text-muted">
                                <?php echo $item['bid_count']; ?> bids • 
                                <?php echo $item['watch_count']; ?> watching
                            </small>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block">Starting at</small>
                            <small class="starting-price"><?php echo format_currency($item['starting_price']); ?></small>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            Added to watchlist <?php echo time_ago($item['created_at']); ?>
                        </small>
                    </div>
                </div>
                
                <div class="card-footer bg-transparent">
                    <div class="d-flex gap-2">
                        <a href="<?php echo SITE_URL; ?>auction-details.php?id=<?php echo $item['auction_id']; ?>" 
                           class="btn btn-primary flex-fill">
                            <i class="fas fa-eye me-1"></i>View Details
                        </a>
                        
                        <?php if (is_auction_active($item) && can_bid($item, $user_id)): ?>
                        <a href="<?php echo SITE_URL; ?>auction-details.php?id=<?php echo $item['auction_id']; ?>#bid-section" 
                           class="btn btn-success">
                            <i class="fas fa-gavel"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Watchlist pagination" class="mt-5">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page - 1; ?>">
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
                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page + 1; ?>">
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

function removeFromWatchlist(auctionId) {
    if (confirm('Remove this item from your watchlist?')) {
        $.ajax({
            url: siteUrl + 'api/toggle-watchlist.php',
            method: 'POST',
            data: {
                auction_id: auctionId,
                action: 'remove',
                csrf_token: $('input[name="csrf_token"]').val()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'An error occurred while removing the item.');
            }
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>
