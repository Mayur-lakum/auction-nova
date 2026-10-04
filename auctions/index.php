<?php
require_once '../includes/functions.php';
require_once '../includes/Database.php';

$page_title = 'Browse Auctions';
$db = Database::getInstance();
$conn = $db->getConnection();

// Get filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = ["a.status = 'active'", "a.end_time > NOW()"];
$params = [];

if ($search) {
    $where_conditions[] = "(a.title LIKE ? OR a.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category) {
    $where_conditions[] = "a.category_id = ?";
    $params[] = $category;
}

if ($min_price) {
    $where_conditions[] = "a.current_price >= ?";
    $params[] = $min_price;
}

if ($max_price) {
    $where_conditions[] = "a.current_price <= ?";
    $params[] = $max_price;
}

$where_clause = implode(' AND ', $where_conditions);

// Sort options
$sort_options = [
    'newest' => 'a.created_at DESC',
    'ending_soon' => 'a.end_time ASC',
    'price_low' => 'a.current_price ASC',
    'price_high' => 'a.current_price DESC',
    'most_bids' => 'bid_count DESC'
];

$order_by = $sort_options[$sort] ?? $sort_options['newest'];

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
                   ORDER BY $order_by 
                   LIMIT $per_page OFFSET $offset";

$auctions_stmt = $conn->prepare($auctions_query);
$auctions_stmt->execute($params);
$auctions = $auctions_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$categories_query = "SELECT * FROM categories WHERE is_active = TRUE ORDER BY name";
$categories_stmt = $conn->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="filter-sidebar">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-filter me-2"></i>Filters
                </h5>
                
                <form method="GET" action="" id="filter-form">
                    <!-- Search -->
                    <div class="filter-group">
                        <h6>Search</h6>
                        <input type="text" class="form-control" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search auctions...">
                    </div>
                    
                    <!-- Category -->
                    <div class="filter-group">
                        <h6>Category</h6>
                        <select class="form-select category-filter" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Price Range -->
                    <div class="filter-group">
                        <h6>Price Range</h6>
                        <div class="row">
                            <div class="col-6">
                                <input type="number" class="form-control form-control-sm" 
                                       name="min_price" placeholder="Min" 
                                       value="<?php echo htmlspecialchars($min_price); ?>">
                            </div>
                            <div class="col-6">
                                <input type="number" class="form-control form-control-sm" 
                                       name="max_price" placeholder="Max" 
                                       value="<?php echo htmlspecialchars($max_price); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sort -->
                    <div class="filter-group">
                        <h6>Sort By</h6>
                        <select class="form-select" name="sort" id="sort-by">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="ending_soon" <?php echo $sort === 'ending_soon' ? 'selected' : ''; ?>>Ending Soon</option>
                            <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="most_bids" <?php echo $sort === 'most_bids' ? 'selected' : ''; ?>>Most Bids</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-search me-2"></i>Apply Filters
                    </button>
                    
                    <a href="<?php echo SITE_URL; ?>auctions/" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times me-2"></i>Clear Filters
                    </a>
                </form>
            </div>
        </div>
        
        <!-- Auctions Grid -->
        <div class="col-lg-9">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">Browse Auctions</h2>
                    <p class="text-muted mb-0">
                        Showing <?php echo number_format($total_auctions); ?> auctions
                        <?php if ($search): ?>
                        for "<?php echo htmlspecialchars($search); ?>"
                        <?php endif; ?>
                    </p>
                </div>
                
                <?php if (is_logged_in()): ?>
                <a href="<?php echo SITE_URL; ?>seller/create-auction.php" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>Create Auction
                </a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($auctions)): ?>
            <!-- No Results -->
            <div class="text-center py-5">
                <i class="fas fa-search fa-4x text-muted mb-3"></i>
                <h4>No auctions found</h4>
                <p class="text-muted">Try adjusting your search criteria or browse all categories.</p>
                <a href="<?php echo SITE_URL; ?>auctions/" class="btn btn-primary">
                    View All Auctions
                </a>
            </div>
            <?php else: ?>
            
            <!-- Auctions Grid -->
            <div class="row g-4">
                <?php foreach ($auctions as $auction): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card auction-card h-100">
                        <div class="position-relative">
                            <div class="card-img-top d-flex align-items-center justify-content-center text-center fw-bold" style="height: 200px; background: #f1f5f9; color: #0f172a; padding: 12px;">
                                <?php echo htmlspecialchars($auction['title']); ?>
                            </div>
                            
                            <?php if ($auction['is_featured']): ?>
                            <span class="badge bg-warning position-absolute top-0 end-0 m-2">Featured</span>
                            <?php endif; ?>
                            
                            <div class="countdown-timer position-absolute bottom-0 start-0 m-2" 
                                 data-end-time="<?php echo $auction['end_time']; ?>">
                                <?php echo time_remaining($auction['end_time']); ?>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo htmlspecialchars(substr($auction['title'], 0, 50)); ?>
                                <?php if (strlen($auction['title']) > 50): ?>...<?php endif; ?>
                            </h5>
                            
                            <p class="card-text text-muted small mb-2">
                                <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($auction['category_name']); ?>
                                <span class="ms-2">
                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($auction['seller_name']); ?>
                                </span>
                            </p>
                            
                            <p class="card-text small text-muted">
                                <?php echo htmlspecialchars(substr($auction['description'], 0, 80)); ?>
                                <?php if (strlen($auction['description']) > 80): ?>...<?php endif; ?>
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <div class="price-display"><?php echo format_currency($auction['current_price']); ?></div>
                                    <small class="text-muted">
                                        <?php echo $auction['bid_count']; ?> bids • 
                                        <?php echo $auction['watch_count']; ?> watching
                                    </small>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block">Starting at</small>
                                    <small class="starting-price"><?php echo format_currency($auction['starting_price']); ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="d-flex gap-2">
                                <a href="<?php echo SITE_URL; ?>auction-details.php?id=<?php echo $auction['id']; ?>" 
                                   class="btn btn-primary flex-fill">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                                
                                <?php if (is_logged_in()): ?>
                                <button class="btn btn-outline-danger watchlist-btn" 
                                        data-auction-id="<?php echo $auction['id']; ?>"
                                        title="Add to Watchlist">
                                    <i class="far fa-heart"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Auction pagination" class="mt-5">
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
    </div>
</div>

<!-- Alert Container -->
<div id="alert-container" class="container mt-3"></div>

<!-- CSRF Token -->
<input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

<script>
var siteUrl = '<?php echo SITE_URL; ?>';

$(document).ready(function() {
    // Auto-submit form on filter change
    $('.category-filter, #sort-by').change(function() {
        $('#filter-form').submit();
    });
    
    // Initialize countdown timers
    updateCountdowns();
    setInterval(updateCountdowns, 1000);
});
</script>

<?php include '../includes/footer.php'; ?>
