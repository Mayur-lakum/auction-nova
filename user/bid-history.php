<?php
require_once '../includes/functions.php';
require_login();

$page_title = 'Bid History';
$db = new Database();
$conn = $db->connect();

$user_id = $_SESSION['user_id'];
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get total count
$count_query = "SELECT COUNT(*) as total FROM bids WHERE bidder_id = ?";
$count_stmt = $conn->prepare($count_query);
$count_stmt->execute([$user_id]);
$total_bids = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_bids / $per_page);

// Get bid history
$bids_query = "SELECT b.*, a.title, a.id as auction_id, a.end_time, a.status, a.seller_id,
               (SELECT image_path FROM auction_images WHERE auction_id = a.id AND is_primary = TRUE LIMIT 1) as primary_image,
               (SELECT MAX(bid_amount) FROM bids WHERE auction_id = a.id) as highest_bid
               FROM bids b 
               JOIN auctions a ON b.auction_id = a.id 
               WHERE b.bidder_id = ? 
               ORDER BY b.bid_time DESC 
               LIMIT $per_page OFFSET $offset";
$bids_stmt = $conn->prepare($bids_query);
$bids_stmt->execute([$user_id]);
$bids = $bids_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "SELECT 
                COUNT(*) as total_bids,
                COUNT(CASE WHEN is_winning = TRUE AND a.status = 'active' AND a.end_time > NOW() THEN 1 END) as winning_bids,
                COUNT(CASE WHEN a.status = 'ended' AND is_winning = TRUE THEN 1 END) as won_auctions,
                SUM(bid_amount) as total_bid_amount
                FROM bids b
                JOIN auctions a ON b.auction_id = a.id
                WHERE b.bidder_id = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute([$user_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="fas fa-history me-2"></i>Bid History
            </h2>
            <p class="text-muted mb-0">Track all your bidding activity</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_bids']); ?></div>
                <div class="stat-label">Total Bids</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
                <div class="stat-number"><?php echo number_format($stats['winning_bids']); ?></div>
                <div class="stat-label">Currently Winning</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
                <div class="stat-number"><?php echo number_format($stats['won_auctions']); ?></div>
                <div class="stat-label">Auctions Won</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card" style="background: linear-gradient(135deg, #dc3545, #e83e8c);">
                <div class="stat-number">₹<?php echo number_format($stats['total_bid_amount']); ?></div>
                <div class="stat-label">Total Bid Amount</div>
            </div>
        </div>
    </div>

    <?php if (empty($bids)): ?>
    <!-- No Bids -->
    <div class="text-center py-5">
        <i class="fas fa-gavel fa-4x text-muted mb-4"></i>
        <h3>No bids placed yet</h3>
        <p class="text-muted mb-4">
            Start bidding on auctions to see your activity here.
        </p>
        <a href="<?php echo SITE_URL; ?>auctions/" class="btn btn-primary">
            Browse Auctions
        </a>
    </div>
    <?php else: ?>

    <!-- Bid History Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Your Bid History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Auction</th>
                            <th>Your Bid</th>
                            <th>Highest Bid</th>
                            <th>Status</th>
                            <th>Bid Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bids as $bid): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if ($bid['primary_image']): ?>
                                    <img src="<?php echo auction_image_url($bid['primary_image']); ?>" 
                                         class="user-avatar me-3" alt="Auction"
                                         onerror="this.onerror=null;this.src='<?php echo SITE_URL; ?>assets/images/no-image.jpg';">
                                    <?php else: ?>
                                    <div class="user-avatar me-3 bg-light d-flex align-items-center justify-content-center">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold">
                                            <?php echo htmlspecialchars(substr($bid['title'], 0, 30)) . (strlen($bid['title']) > 30 ? '...' : ''); ?>
                                        </div>
                                        <small class="text-muted">
                                            <?php if ($bid['status'] === 'active' && strtotime($bid['end_time']) > time()): ?>
                                            <span class="countdown-timer" data-end-time="<?php echo $bid['end_time']; ?>">
                                                <?php echo time_remaining($bid['end_time']); ?>
                                            </span>
                                            <?php else: ?>
                                            Auction ended
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-bold"><?php echo format_currency($bid['bid_amount']); ?></span>
                            </td>
                            <td>
                                <span class="fw-bold text-success"><?php echo format_currency($bid['highest_bid']); ?></span>
                            </td>
                            <td>
                                <?php if ($bid['is_winning'] && $bid['status'] === 'active' && strtotime($bid['end_time']) > time()): ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-trophy me-1"></i>Winning
                                </span>
                                <?php elseif ($bid['status'] === 'ended' && $bid['is_winning']): ?>
                                <span class="badge bg-primary">
                                    <i class="fas fa-crown me-1"></i>Won
                                </span>
                                <?php elseif ($bid['status'] === 'ended'): ?>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-times me-1"></i>Lost
                                </span>
                                <?php elseif ($bid['status'] === 'active'): ?>
                                <span class="badge bg-warning">
                                    <i class="fas fa-exclamation me-1"></i>Outbid
                                </span>
                                <?php else: ?>
                                <span class="badge bg-secondary"><?php echo ucfirst($bid['status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?php echo date('M j, Y', strtotime($bid['bid_time'])); ?></div>
                                <small class="text-muted"><?php echo date('g:i A', strtotime($bid['bid_time'])); ?></small>
                            </td>
                            <td>
                                <a href="<?php echo SITE_URL; ?>auction-details.php?id=<?php echo $bid['auction_id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i>View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Bid history pagination" class="mt-4">
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

<script>
$(document).ready(function() {
    // Initialize countdown timers
    updateCountdowns();
    setInterval(updateCountdowns, 1000);
});
</script>

<?php include '../includes/footer.php'; ?>
