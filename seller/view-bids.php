<?php
require_once '../includes/functions.php';
require_login();

$page_title = 'View Bids';
$auction_id = $_GET['id'] ?? 0;

if (!$auction_id || !is_numeric($auction_id)) {
    header('Location: my-auctions.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Get auction details and verify ownership
$auction_query = "SELECT a.*, c.name as category_name,
                  (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count
                  FROM auctions a 
                  JOIN categories c ON a.category_id = c.id 
                  WHERE a.id = ? AND a.seller_id = ?";
$auction_stmt = $conn->prepare($auction_query);
$auction_stmt->execute([$auction_id, $_SESSION['user_id']]);
$auction = $auction_stmt->fetch(PDO::FETCH_ASSOC);

if (!$auction) {
    header('Location: my-auctions.php');
    exit;
}

// Get all bids for this auction
$bids_query = "SELECT b.*, u.username, u.first_name, u.last_name, u.email
               FROM bids b 
               JOIN users u ON b.bidder_id = u.id 
               WHERE b.auction_id = ? 
               ORDER BY b.bid_amount DESC, b.bid_time ASC";
$bids_stmt = $conn->prepare($bids_query);
$bids_stmt->execute([$auction_id]);
$bids = $bids_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get bid statistics
$stats_query = "SELECT 
                COUNT(*) as total_bids,
                COUNT(DISTINCT bidder_id) as unique_bidders,
                MIN(bid_amount) as lowest_bid,
                MAX(bid_amount) as highest_bid,
                AVG(bid_amount) as average_bid
                FROM bids WHERE auction_id = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute([$auction_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="fas fa-list me-2"></i>Bids for "<?php echo htmlspecialchars(substr($auction['title'], 0, 50)) . (strlen($auction['title']) > 50 ? '...' : ''); ?>"
            </h2>
            <p class="text-muted mb-0">View all bids received for this auction</p>
        </div>
        <div class="d-flex gap-2">
            <a href="my-auctions.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to My Auctions
            </a>
            <a href="<?php echo SITE_URL; ?>auction-details.php?id=<?php echo $auction_id; ?>" class="btn btn-primary">
                <i class="fas fa-eye me-2"></i>View Auction
            </a>
        </div>
    </div>

    <!-- Auction Info -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($auction['title']); ?></h5>
                    <div class="d-flex align-items-center text-muted mb-2">
                        <span class="me-3">
                            <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($auction['category_name']); ?>
                        </span>
                        <span class="me-3">
                            <i class="fas fa-clock me-1"></i>
                            <?php if (is_auction_active($auction)): ?>
                            <span class="countdown-timer" data-end-time="<?php echo $auction['end_time']; ?>">
                                <?php echo time_remaining($auction['end_time']); ?>
                            </span>
                            <?php else: ?>
                            Ended <?php echo time_ago($auction['end_time']); ?>
                            <?php endif; ?>
                        </span>
                        <span>
                            <i class="fas fa-eye me-1"></i><?php echo number_format($auction['view_count']); ?> views
                        </span>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-<?php echo is_auction_active($auction) ? 'success' : 'secondary'; ?> me-2">
                            <?php echo is_auction_active($auction) ? 'Active' : 'Ended'; ?>
                        </span>
                        <?php if ($auction['is_featured']): ?>
                        <span class="badge bg-warning">Featured</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="mb-2">
                        <small class="text-muted">Current Price</small>
                        <div class="h4 fw-bold text-success mb-0"><?php echo format_currency($auction['current_price']); ?></div>
                    </div>
                    <div>
                        <small class="text-muted">Starting Price: <?php echo format_currency($auction['starting_price']); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bid Statistics -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_bids']); ?></div>
                <div class="stat-label">Total Bids</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
                <div class="stat-number"><?php echo number_format($stats['unique_bidders']); ?></div>
                <div class="stat-label">Unique Bidders</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
                <div class="stat-number">₹<?php echo number_format($stats['highest_bid']); ?></div>
                <div class="stat-label">Highest Bid</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card" style="background: linear-gradient(135deg, #dc3545, #e83e8c);">
                <div class="stat-number">₹<?php echo number_format($stats['average_bid']); ?></div>
                <div class="stat-label">Average Bid</div>
            </div>
        </div>
    </div>

    <?php if (empty($bids)): ?>
    <!-- No Bids -->
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-gavel fa-4x text-muted mb-4"></i>
            <h4>No bids received yet</h4>
            <p class="text-muted">This auction hasn't received any bids yet. Share it to get more visibility!</p>
        </div>
    </div>
    <?php else: ?>

    <!-- Bids Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">All Bids (<?php echo count($bids); ?>)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Rank</th>
                            <th>Bidder</th>
                            <th>Bid Amount</th>
                            <th>Bid Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bids as $index => $bid): ?>
                        <tr class="<?php echo $bid['is_winning'] ? 'table-success' : ''; ?>">
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if ($index === 0): ?>
                                    <i class="fas fa-trophy text-warning me-2"></i>
                                    <?php elseif ($index === 1): ?>
                                    <i class="fas fa-medal text-secondary me-2"></i>
                                    <?php elseif ($index === 2): ?>
                                    <i class="fas fa-award text-warning me-2"></i>
                                    <?php else: ?>
                                    <span class="me-2"><?php echo $index + 1; ?></span>
                                    <?php endif; ?>
                                    <?php if ($bid['is_winning']): ?>
                                    <span class="badge bg-success small">Winning</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($bid['first_name'] . ' ' . $bid['last_name']); ?></div>
                                    <small class="text-muted">@<?php echo htmlspecialchars($bid['username']); ?></small>
                                </div>
                            </td>
                            <td>
                                <span class="fw-bold fs-5 <?php echo $bid['is_winning'] ? 'text-success' : ''; ?>">
                                    <?php echo format_currency($bid['bid_amount']); ?>
                                </span>
                            </td>
                            <td>
                                <div><?php echo date('M j, Y', strtotime($bid['bid_time'])); ?></div>
                                <small class="text-muted"><?php echo date('g:i:s A', strtotime($bid['bid_time'])); ?></small>
                            </td>
                            <td>
                                <?php if ($bid['is_winning']): ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-crown me-1"></i>Winning Bid
                                </span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Outbid</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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
