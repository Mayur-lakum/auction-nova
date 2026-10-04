<?php
require_once '../includes/functions.php';
require_login();
require_admin();

$page_title = 'Manage Auctions';
$db = new Database();
$conn = $db->connect();

// Handle auction actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        flash_message('error', 'Invalid request');
    } else {
        $auction_id = (int)$_POST['auction_id'];
        $action = $_POST['action'];
        
        switch ($action) {
            case 'feature':
                $query = "UPDATE auctions SET is_featured = TRUE WHERE id = ?";
                $stmt = $conn->prepare($query);
                if ($stmt->execute([$auction_id])) {
                    log_admin_action($_SESSION['user_id'], 'feature_auction', 'auction', $auction_id, 'Auction featured');
                    flash_message('success', 'Auction featured successfully');
                }
                break;
                
            case 'unfeature':
                $query = "UPDATE auctions SET is_featured = FALSE WHERE id = ?";
                $stmt = $conn->prepare($query);
                if ($stmt->execute([$auction_id])) {
                    log_admin_action($_SESSION['user_id'], 'unfeature_auction', 'auction', $auction_id, 'Auction unfeatured');
                    flash_message('success', 'Auction unfeatured successfully');
                }
                break;
                
            case 'end':
                $query = "UPDATE auctions SET status = 'ended', end_time = NOW() WHERE id = ?";
                $stmt = $conn->prepare($query);
                if ($stmt->execute([$auction_id])) {
                    log_admin_action($_SESSION['user_id'], 'end_auction', 'auction', $auction_id, 'Auction ended by admin');
                    flash_message('success', 'Auction ended successfully');
                }
                break;
        }
        
        redirect($_SERVER['REQUEST_URI']);
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';
$category_filter = $_GET['category'] ?? 'all';
$featured_filter = $_GET['featured'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query conditions
$where_conditions = ["1=1"];
$params = [];

if ($search) {
    $where_conditions[] = "(a.title LIKE ? OR a.description LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
}

if ($status_filter !== 'all') {
    if ($status_filter === 'active') {
        $where_conditions[] = "a.status = 'active' AND a.end_time > NOW()";
    } else {
        $where_conditions[] = "a.status = ?";
        $params[] = $status_filter;
    }
}

if ($category_filter !== 'all') {
    $where_conditions[] = "a.category_id = ?";
    $params[] = $category_filter;
}

if ($featured_filter === 'featured') {
    $where_conditions[] = "a.is_featured = TRUE";
} elseif ($featured_filter === 'not_featured') {
    $where_conditions[] = "a.is_featured = FALSE";
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM auctions a WHERE $where_clause";
$count_stmt = $conn->prepare($count_query);
$count_stmt->execute($params);
$total_auctions = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_auctions / $per_page);

// Get auctions
$auctions_query = "SELECT a.*, c.name as category_name, u.username as seller_name,
                   (SELECT image_path FROM auction_images WHERE auction_id = a.id AND is_primary = TRUE LIMIT 1) as primary_image,
                   (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count,
                   (SELECT COUNT(*) FROM watchlist WHERE auction_id = a.id) as watch_count
                   FROM auctions a 
                   JOIN categories c ON a.category_id = c.id 
                   JOIN users u ON a.seller_id = u.id 
                   WHERE $where_clause 
                   ORDER BY a.created_at DESC 
                   LIMIT $per_page OFFSET $offset";

$auctions_stmt = $conn->prepare($auctions_query);
$auctions_stmt->execute($params);
$auctions = $auctions_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$categories_query = "SELECT * FROM categories WHERE is_active = TRUE ORDER BY name";
$categories_stmt = $conn->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "SELECT 
                COUNT(*) as total_auctions,
                SUM(CASE WHEN status = 'active' AND end_time > NOW() THEN 1 ELSE 0 END) as active_auctions,
                SUM(CASE WHEN status = 'ended' THEN 1 ELSE 0 END) as ended_auctions,
                SUM(CASE WHEN is_featured = TRUE THEN 1 ELSE 0 END) as featured_auctions
                FROM auctions";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="fas fa-gavel me-2"></i>Manage Auctions
            </h2>
            <p class="text-muted mb-0">View and manage all auctions on the platform</p>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_auctions']); ?></div>
                <div class="stat-label">Total Auctions</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
                <div class="stat-number"><?php echo number_format($stats['active_auctions']); ?></div>
                <div class="stat-label">Active Auctions</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
                <div class="stat-number"><?php echo number_format($stats['featured_auctions']); ?></div>
                <div class="stat-label">Featured Auctions</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card" style="background: linear-gradient(135deg, #dc3545, #e83e8c);">
                <div class="stat-number"><?php echo number_format($stats['ended_auctions']); ?></div>
                <div class="stat-label">Ended Auctions</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search Auctions</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by title or description">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="ended" <?php echo $status_filter === 'ended' ? 'selected' : ''; ?>>Ended</option>
                        <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category">
                        <option value="all" <?php echo $category_filter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" 
                                <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Featured</label>
                    <select class="form-select" name="featured">
                        <option value="all" <?php echo $featured_filter === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="featured" <?php echo $featured_filter === 'featured' ? 'selected' : ''; ?>>Featured</option>
                        <option value="not_featured" <?php echo $featured_filter === 'not_featured' ? 'selected' : ''; ?>>Not Featured</option>
                    </select>
                </div>
                
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                
                <div class="col-md-2">
                    <a href="auctions.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times me-2"></i>Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Auctions Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Auctions List</h5>
            <span class="text-muted">Showing <?php echo count($auctions); ?> of <?php echo $total_auctions; ?> auctions</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($auctions)): ?>
            <div class="text-center py-5">
                <i class="fas fa-gavel fa-3x text-muted mb-3"></i>
                <h5>No auctions found</h5>
                <p class="text-muted">Try adjusting your search criteria</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Auction</th>
                            <th>Seller</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Activity</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($auctions as $auction): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if ($auction['primary_image']): ?>
                                    <img src="<?php echo SITE_URL; ?>assets/uploads/<?php echo htmlspecialchars($auction['primary_image']); ?>" 
                                         class="user-avatar me-3" style="object-fit: cover;" alt="Auction">
                                    <?php else: ?>
                                    <div class="user-avatar me-3 bg-light d-flex align-items-center justify-content-center">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars(substr($auction['title'], 0, 30)) . (strlen($auction['title']) > 30 ? '...' : ''); ?></div>
                                        <?php if ($auction['is_featured']): ?>
                                        <span class="badge bg-warning small">Featured</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($auction['seller_name']); ?></td>
                            <td><?php echo htmlspecialchars($auction['category_name']); ?></td>
                            <td>
                                <div class="fw-bold"><?php echo format_currency($auction['current_price']); ?></div>
                                <small class="text-muted">Start: <?php echo format_currency($auction['starting_price']); ?></small>
                            </td>
                            <td>
                                <?php
                                $status_class = '';
                                $status_text = '';
                                if ($auction['status'] === 'active' && strtotime($auction['end_time']) > time()) {
                                    $status_class = 'success';
                                    $status_text = 'Active';
                                } elseif ($auction['status'] === 'active' && strtotime($auction['end_time']) <= time()) {
                                    $status_class = 'warning';
                                    $status_text = 'Expired';
                                } elseif ($auction['status'] === 'ended') {
                                    $status_class = 'danger';
                                    $status_text = 'Ended';
                                } else {
                                    $status_class = 'secondary';
                                    $status_text = ucfirst($auction['status']);
                                }
                                ?>
                                <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                <?php if ($auction['status'] === 'active' && strtotime($auction['end_time']) > time()): ?>
                                <div class="small text-muted countdown-timer" data-end-time="<?php echo $auction['end_time']; ?>">
                                    <?php echo time_remaining($auction['end_time']); ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small">
                                    <div><i class="fas fa-gavel me-1"></i><?php echo $auction['bid_count']; ?> bids</div>
                                    <div><i class="fas fa-heart me-1"></i><?php echo $auction['watch_count']; ?> watching</div>
                                    <div><i class="fas fa-eye me-1"></i><?php echo number_format($auction['view_count']); ?> views</div>
                                </div>
                            </td>
                            <td>
                                <div><?php echo date('M j, Y', strtotime($auction['created_at'])); ?></div>
                                <small class="text-muted"><?php echo time_ago($auction['created_at']); ?></small>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                            data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>auction-details.php?id=<?php echo $auction['id']; ?>">
                                                <i class="fas fa-eye me-2"></i>View Details
                                            </a>
                                        </li>
                                        
                                        <?php if ($auction['is_featured']): ?>
                                        <li>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="auction_id" value="<?php echo $auction['id']; ?>">
                                                <input type="hidden" name="action" value="unfeature">
                                                <button type="submit" class="dropdown-item">
                                                    <i class="fas fa-star-half-alt me-2"></i>Unfeature
                                                </button>
                                            </form>
                                        </li>
                                        <?php else: ?>
                                        <li>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="auction_id" value="<?php echo $auction['id']; ?>">
                                                <input type="hidden" name="action" value="feature">
                                                <button type="submit" class="dropdown-item text-warning">
                                                    <i class="fas fa-star me-2"></i>Feature
                                                </button>
                                            </form>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php if ($auction['status'] === 'active' && strtotime($auction['end_time']) > time()): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="auction_id" value="<?php echo $auction['id']; ?>">
                                                <input type="hidden" name="action" value="end">
                                                <button type="submit" class="dropdown-item text-danger" 
                                                        onclick="return confirm('End this auction early?')">
                                                    <i class="fas fa-stop me-2"></i>End Auction
                                                </button>
                                            </form>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Auctions pagination" class="mt-4">
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
</div>

<script>
$(document).ready(function() {
    // Initialize countdown timers
    updateCountdowns();
    setInterval(updateCountdowns, 1000);
});
</script>

<?php include '../includes/footer.php'; ?>
