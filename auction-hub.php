<?php
// Load configuration
require_once __DIR__ . '/config/config.php';

// Include necessary files
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/auth.php';

// Initialize database
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get categories for filters
    $categories_query = "SELECT * FROM categories WHERE is_active = TRUE ORDER BY name";
    $categories_stmt = $conn->prepare($categories_query);
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log('Error fetching data: ' . $e->getMessage());
    $categories = [];
}

// Helper function to resolve auction image path
function resolve_auction_image($filename) {
    if (empty($filename)) {
        return SITE_URL . 'assets/images/no-image.jpg';
    }
    
    $filename = htmlspecialchars($filename);
    
    // Check if image exists in assets/uploads/ (standard location)
    if (file_exists(__DIR__ . '/assets/uploads/' . $filename)) {
        return SITE_URL . 'assets/uploads/' . $filename . '?v=' . time();
    }
    // Check if image exists in uploads/ (root - legacy/compatibility)
    elseif (file_exists(__DIR__ . '/uploads/' . $filename)) {
        return SITE_URL . 'uploads/' . $filename . '?v=' . time();
    }
    // Default to assets/uploads/ even if file doesn't exist (will show onerror fallback)
    else {
        return SITE_URL . 'assets/uploads/' . $filename;
    }
}

// Set page title and include header
$page_title = 'Auction Hub';
$page_scripts = ['assets/js/live-auction-handler.js'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fw-bold mb-3">
                <i class="fas fa-gavel me-2"></i>Auction Hub
            </h1>
            <p class="text-muted">Discover amazing deals across all auction types</p>
        </div>
    </div>

    <!-- Auction Type Tabs -->
    <ul class="nav nav-tabs mb-4" id="auctionTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="live-tab" data-bs-toggle="tab" data-bs-target="#live" type="button" role="tab">
                <i class="fas fa-broadcast-tower me-2"></i>Live Auctions
                <span class="badge bg-danger ms-1">HOT</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="results-tab" data-bs-toggle="tab" data-bs-target="#results" type="button" role="tab">
                <i class="fas fa-trophy me-2"></i>Auction Results
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab">
                <i class="fas fa-calendar-alt me-2"></i>Upcoming Auctions
            </button>
        </li>
    </ul>

    <div class="tab-content" id="auctionTabContent">
        <!-- Live Auctions Tab -->
        <div class="tab-pane fade show active" id="live" role="tabpanel">
            <!-- Search and Filter Bar -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="liveSearch" placeholder="Search live auctions...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" id="liveCategoryFilter">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="liveSort">
                        <option value="ending">Ending Soon</option>
                        <option value="popular">Most Popular</option>
                        <option value="price-low">Price: Low to High</option>
                        <option value="price-high">Price: High to Low</option>
                    </select>
                </div>
            </div>

            <!-- Live Auctions Grid -->
            <div class="row g-4" id="liveAuctionsGrid">
                <?php 
                // Get live auctions (active and ending soon)
                $live_query = "SELECT a.id, a.title, a.current_price, a.end_time, a.starting_price, a.status,
                              u.username AS seller_name, c.name AS category_name,
                              (SELECT COUNT(*) FROM bids b WHERE b.auction_id = a.id) AS bid_count,
                              (SELECT image_path FROM auction_images WHERE auction_id = a.id AND is_primary = TRUE LIMIT 1) as primary_image
                              FROM auctions a
                              JOIN categories c ON a.category_id = c.id
                              JOIN users u ON a.seller_id = u.id 
                              WHERE a.status = 'active' AND a.end_time > NOW() 
                              ORDER BY a.end_time ASC LIMIT 12";
                
                try {
                    $live_stmt = $conn->prepare($live_query);
                    if (!$live_stmt) {
                        error_log('Prepare failed for live auctions: ' . implode(' ', $conn->errorInfo()));
                    }
                    $live_stmt->execute();
                    $live_auctions = $live_stmt->fetchAll(PDO::FETCH_ASSOC);
                    error_log("User live auctions count: " . count($live_auctions));
                    foreach ($live_auctions as $a) {
                        error_log("User live auction: ID={$a['id']}, status={$a['status']}, end_time={$a['end_time']}");
                    }
                    
                    foreach ($live_auctions as $auction):
                ?>
                <div class="col-lg-4 col-md-6" data-auction-id="<?php echo (int)$auction['id']; ?>">
                    <div class="card h-100 live-auction-card">
                        <div class="position-relative">
                            <?php 
                            // Use helper function to resolve image path
                            $image_path = resolve_auction_image($auction['primary_image'] ?? '');
                            ?>
                            <img src="<?php echo $image_path; ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($auction['title']); ?>" 
                                 style="height: 200px; object-fit: cover;"
                                 onerror="this.src='<?php echo SITE_URL; ?>assets/images/no-image.jpg'; this.onerror=null;">
                            <div class="position-absolute top-0 end-0 m-2">
                                <span class="badge bg-danger animate-pulse">LIVE</span>
                            </div>
                            <div class="position-absolute bottom-0 start-0 m-2">
                                <span class="badge bg-dark">
                                    <i class="fas fa-clock me-1"></i>
                                    <span class="countdown-timer" data-end-time="<?php echo $auction['end_time']; ?>" data-auction-id="<?php echo (int)$auction['id']; ?>">--:--:--</span>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($auction['title']); ?></h5>
                            <p class="text-muted small mb-2">
                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($auction['seller_name']); ?>
                                <span class="ms-2"><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($auction['category_name']); ?></span>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="text-muted small">Current Bid</span>
                                    <div class="h5 text-success mb-0">$<?php echo number_format($auction['current_price'], 2); ?></div>
                                </div>
                                <div class="text-end">
                                    <span class="text-muted small"><?php echo $auction['bid_count']; ?> bids</span>
                                </div>
                            </div>
                            
                            <div class="card-footer bg-transparent">
                                <a href="<?php echo SITE_URL; ?>auction-details.php?id=<?php echo $auction['id']; ?>" 
                                   class="btn btn-outline-primary w-100">
                                    <i class="fas fa-eye me-2"></i>View Auction Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php 
                    endforeach;
                } catch (Exception $e) {
                    error_log('User live auctions query error: ' . $e->getMessage());
                    echo '<div class="col-12"><div class="alert alert-warning">Unable to load live auctions. Please try again later.</div></div>';
                }
                ?>
            </div>
        </div>

        <!-- Auction Results Tab -->
        <div class="tab-pane fade" id="results" role="tabpanel">
            <!-- Search and Filter Bar -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="resultsSearch" placeholder="Search completed auctions...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" id="resultsCategoryFilter">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="resultsSort">
                        <option value="recent">Recently Ended</option>
                        <option value="price-high">Final Price: High to Low</option>
                        <option value="price-low">Final Price: Low to High</option>
                        <option value="bids">Most Bids</option>
                    </select>
                </div>
            </div>

            <!-- Results Grid -->
            <div class="row g-4" id="resultsGrid">
                <?php 
                // Get completed auctions
                $results_query = "SELECT a.*, c.name as category_name, u.username as seller_name,
                                 (SELECT image_path FROM auction_images WHERE auction_id = a.id AND is_primary = TRUE LIMIT 1) as primary_image,
                                 (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count,
                                 (SELECT MAX(amount) FROM bids WHERE auction_id = a.id) as final_bid,
                                 (SELECT username FROM users WHERE id = (SELECT user_id FROM bids WHERE auction_id = a.id ORDER BY amount DESC LIMIT 1)) as winner_name
                                 FROM auctions a 
                                 JOIN categories c ON a.category_id = c.id 
                                 JOIN users u ON a.seller_id = u.id 
                                 WHERE a.status = 'completed' 
                                 ORDER BY a.end_time DESC LIMIT 12";
                
                try {
                    $results_stmt = $conn->prepare($results_query);
                    $results_stmt->execute();
                    $completed_auctions = $results_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($completed_auctions as $auction):
                ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 completed-auction-card">
                        <div class="position-relative">
                            <?php 
                            // Use helper function to resolve image path
                            $image_path = resolve_auction_image($auction['primary_image'] ?? '');
                            ?>
                            <img src="<?php echo $image_path; ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($auction['title']); ?>" 
                                 style="height: 200px; object-fit: cover;"
                                 onerror="this.src='<?php echo SITE_URL; ?>assets/images/no-image.jpg'; this.onerror=null;">
                            <div class="position-absolute top-0 end-0 m-2">
                                <span class="badge bg-secondary">COMPLETED</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($auction['title']); ?></h5>
                            <p class="text-muted small mb-2">
                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($auction['seller_name']); ?>
                                <span class="ms-2"><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($auction['category_name']); ?></span>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <span class="text-muted small">Final Price</span>
                                    <div class="h5 text-primary mb-0">$<?php echo number_format($auction['final_bid'] ?: $auction['starting_price'], 2); ?></div>
                                </div>
                                <div class="text-end">
                                    <span class="text-muted small"><?php echo $auction['bid_count']; ?> bids</span>
                                </div>
                            </div>
                            <?php if ($auction['winner_name']): ?>
                            <div class="alert alert-success py-2 mb-2">
                                <i class="fas fa-trophy me-1"></i>
                                Won by <?php echo htmlspecialchars($auction['winner_name']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent">
                            <a href="<?php echo SITE_URL; ?>auction-details.php?id=<?php echo $auction['id']; ?>" 
                               class="btn btn-outline-primary w-100">
                                <i class="fas fa-eye me-2"></i>View Results
                            </a>
                        </div>
                    </div>
                </div>
                <?php 
                    endforeach;
                } catch (Exception $e) {
                    echo '<div class="col-12"><div class="alert alert-warning">Unable to load auction results. Please try again later.</div></div>';
                }
                ?>
            </div>
        </div>

        <!-- Upcoming Auctions Tab -->
        <div class="tab-pane fade" id="upcoming" role="tabpanel">
            <!-- Search and Filter Bar -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="upcomingSearch" placeholder="Search upcoming auctions...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" id="upcomingCategoryFilter">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="upcomingSort">
                        <option value="starting">Starting Soon</option>
                        <option value="price-low">Starting Price: Low to High</option>
                        <option value="price-high">Starting Price: High to Low</option>
                    </select>
                </div>
            </div>

            <!-- Upcoming Grid -->
            <div class="row g-4" id="upcomingGrid">
                <?php 
                // Get upcoming auctions
                $upcoming_query = "SELECT a.*, c.name as category_name, u.username as seller_name,
                                   (SELECT image_path FROM auction_images WHERE auction_id = a.id AND is_primary = TRUE LIMIT 1) as primary_image
                                   FROM auctions a 
                                   JOIN categories c ON a.category_id = c.id 
                                   JOIN users u ON a.seller_id = u.id 
                                   WHERE a.status = 'scheduled' AND a.start_time > NOW() 
                                   ORDER BY a.start_time ASC LIMIT 12";
                
                try {
                    $upcoming_stmt = $conn->prepare($upcoming_query);
                    $upcoming_stmt->execute();
                    $upcoming_auctions = $upcoming_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($upcoming_auctions as $auction):
                ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 upcoming-auction-card">
                        <div class="position-relative">
                            <?php 
                            // Use helper function to resolve image path
                            $image_path = resolve_auction_image($auction['primary_image'] ?? '');
                            ?>
                            <img src="<?php echo $image_path; ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($auction['title']); ?>" 
                                 style="height: 200px; object-fit: cover;"
                                 onerror="this.src='<?php echo SITE_URL; ?>assets/images/no-image.jpg'; this.onerror=null;">
                            <div class="position-absolute top-0 end-0 m-2">
                                <span class="badge bg-info">UPCOMING</span>
                            </div>
                            <div class="position-absolute bottom-0 start-0 m-2">
                                <span class="badge bg-dark">
                                    <i class="fas fa-calendar me-1"></i>
                                    Starts: <?php echo date('M j, g:i A', strtotime($auction['start_time'])); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($auction['title']); ?></h5>
                            <p class="text-muted small mb-2">
                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($auction['seller_name']); ?>
                                <span class="ms-2"><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($auction['category_name']); ?></span>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="text-muted small">Starting Price</span>
                                    <div class="h5 text-info mb-0">$<?php echo number_format($auction['starting_price'], 2); ?></div>
                                </div>
                                <div class="text-end">
                                    <span class="text-muted small">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php 
                                        $time_diff = strtotime($auction['start_time']) - time();
                                        if ($time_diff < 3600) {
                                            echo floor($time_diff / 60) . ' min';
                                        } elseif ($time_diff < 86400) {
                                            echo floor($time_diff / 3600) . ' hours';
                                        } else {
                                            echo floor($time_diff / 86400) . ' days';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <button class="btn btn-outline-info w-100" onclick="setAuctionReminder(<?php echo $auction['id']; ?>)">
                                <i class="fas fa-bell me-2"></i>Set Reminder
                            </button>
                        </div>
                        <div class="card-footer bg-transparent">
                            <a href="<?php echo SITE_URL; ?>auction-details.php?id=<?php echo $auction['id']; ?>" 
                               class="btn btn-outline-primary w-100">
                                <i class="fas fa-eye me-2"></i>Preview Auction
                            </a>
                        </div>
                    </div>
                </div>
                <?php 
                    endforeach;
                } catch (Exception $e) {
                    echo '<div class="col-12"><div class="alert alert-warning">Unable to load upcoming auctions. Please try again later.</div></div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<style>
.animate-pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.live-auction-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.live-auction-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.bidding-controls .btn-success {
    background: linear-gradient(135deg, #28a745, #20c997);
    border: none;
    transition: all 0.3s ease;
}

.bidding-controls .btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize countdown timers
    initCountdownTimers();
    
    // Initialize auction hub functionality
    initAuctionHub();
});

// Initialize countdown timers
function initCountdownTimers() {
    const timers = document.querySelectorAll('.countdown-timer');
    
    timers.forEach(timer => {
        const endTime = new Date(timer.dataset.endTime).getTime();
        
        const updateTimer = () => {
            const now = new Date().getTime();
            const distance = endTime - now;
            
            if (distance < 0) {
                timer.textContent = 'ENDED';
                timer.parentElement.classList.remove('bg-dark');
                timer.parentElement.classList.add('bg-secondary');
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            if (days > 0) {
                timer.textContent = `${days}d ${hours}h ${minutes}m`;
            } else if (hours > 0) {
                timer.textContent = `${hours}h ${minutes}m ${seconds}s`;
            } else {
                timer.textContent = `${minutes}m ${seconds}s`;
                timer.parentElement.classList.add('animate-pulse');
            }
        };
        
        updateTimer();
        setInterval(updateTimer, 1000);
    });
}

// Initialize auction hub functionality
function initAuctionHub() {
    // Live bidding functionality
    document.querySelectorAll('.place-bid-btn').forEach(button => {
        button.addEventListener('click', function() {
            const auctionId = this.dataset.auctionId;
            const bidInput = document.querySelector(`.bid-amount[data-auction-id="${auctionId}"]`);
            const bidAmount = parseFloat(bidInput.value);
            
            if (!bidAmount || bidAmount <= 0) {
                showAlert('Please enter a valid bid amount', 'warning');
                return;
            }
            
            placeBid(auctionId, bidAmount);
        });
    });
    
    // Search and filter functionality
    initAuctionFilters();
    
    // Set reminder functionality
    window.setAuctionReminder = function(auctionId) {
        if (confirm('Would you like to set a reminder for this auction?')) {
            const reminders = JSON.parse(localStorage.getItem('auctionReminders') || '[]');
            if (!reminders.includes(auctionId)) {
                reminders.push(auctionId);
                localStorage.setItem('auctionReminders', JSON.stringify(reminders));
                showAlert('Reminder set for this auction!', 'success');
            } else {
                showAlert('You already have a reminder set for this auction', 'info');
            }
        }
    };
}

// Place bid function
function placeBid(auctionId, amount) {
    const button = document.querySelector(`.place-bid-btn[data-auction-id="${auctionId}"]`);
    const originalText = button.innerHTML;
    
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Placing Bid...';
    
    fetch('<?php echo SITE_URL; ?>api/place-bid.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `auction_id=${auctionId}&amount=${amount}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Bid placed successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.message || 'Failed to place bid', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred. Please try again.', 'danger');
    })
    .finally(() => {
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

// Initialize auction filters
function initAuctionFilters() {
    // Live auctions filters
    const liveSearch = document.getElementById('liveSearch');
    const liveCategoryFilter = document.getElementById('liveCategoryFilter');
    const liveSort = document.getElementById('liveSort');
    
    if (liveSearch) {
        liveSearch.addEventListener('input', () => filterAuctions('live'));
    }
    if (liveCategoryFilter) {
        liveCategoryFilter.addEventListener('change', () => filterAuctions('live'));
    }
    if (liveSort) {
        liveSort.addEventListener('change', () => filterAuctions('live'));
    }
    
    // Results filters
    const resultsSearch = document.getElementById('resultsSearch');
    const resultsCategoryFilter = document.getElementById('resultsCategoryFilter');
    const resultsSort = document.getElementById('resultsSort');
    
    if (resultsSearch) {
        resultsSearch.addEventListener('input', () => filterAuctions('results'));
    }
    if (resultsCategoryFilter) {
        resultsCategoryFilter.addEventListener('change', () => filterAuctions('results'));
    }
    if (resultsSort) {
        resultsSort.addEventListener('change', () => filterAuctions('results'));
    }
    
    // Upcoming filters
    const upcomingSearch = document.getElementById('upcomingSearch');
    const upcomingCategoryFilter = document.getElementById('upcomingCategoryFilter');
    const upcomingSort = document.getElementById('upcomingSort');
    
    if (upcomingSearch) {
        upcomingSearch.addEventListener('input', () => filterAuctions('upcoming'));
    }
    if (upcomingCategoryFilter) {
        upcomingCategoryFilter.addEventListener('change', () => filterAuctions('upcoming'));
    }
    if (upcomingSort) {
        upcomingSort.addEventListener('change', () => filterAuctions('upcoming'));
    }
}

// Filter auctions function
function filterAuctions(type) {
    const searchInput = document.getElementById(`${type}Search`);
    const categoryFilter = document.getElementById(`${type}CategoryFilter`);
    const sortFilter = document.getElementById(`${type}Sort`);
    const grid = document.getElementById(`${type}Grid`);
    
    const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
    const category = categoryFilter ? categoryFilter.value : '';
    const sortBy = sortFilter ? sortFilter.value : '';
    
    const cards = grid.querySelectorAll('.col-lg-4, .col-md-6');
    
    cards.forEach(card => {
        const title = card.querySelector('.card-title').textContent.toLowerCase();
        const seller = card.querySelector('.fa-user').parentElement.textContent.toLowerCase();
        const categoryText = card.querySelector('.fa-tag').parentElement.textContent.toLowerCase();
        
        let matchesSearch = title.includes(searchTerm) || seller.includes(searchTerm);
        let matchesCategory = !category || categoryText.includes(category.toLowerCase());
        
        if (matchesSearch && matchesCategory) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
    
    if (sortBy) {
        sortAuctions(grid, sortBy, type);
    }
}

// Sort auctions function
function sortAuctions(grid, sortBy, type) {
    const cards = Array.from(grid.querySelectorAll('.col-lg-4, .col-md-6'));
    
    cards.sort((a, b) => {
        switch (sortBy) {
            case 'ending':
            case 'starting':
                return 0;
            case 'price-low':
                const priceA = parseFloat(a.querySelector('.h5').textContent.replace('$', ''));
                const priceB = parseFloat(b.querySelector('.h5').textContent.replace('$', ''));
                return priceA - priceB;
            case 'price-high':
                const priceHighA = parseFloat(a.querySelector('.h5').textContent.replace('$', ''));
                const priceHighB = parseFloat(b.querySelector('.h5').textContent.replace('$', ''));
                return priceHighB - priceHighA;
            case 'popular':
            case 'bids':
                const bidsA = parseInt(a.querySelector('.text-muted.small').textContent.match(/\d+/)[0]);
                const bidsB = parseInt(b.querySelector('.text-muted.small').textContent.match(/\d+/)[0]);
                return bidsB - bidsA;
            default:
                return 0;
        }
    });
    
    cards.forEach(card => grid.appendChild(card));
}

// Show alert function
function showAlert(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alert.style.zIndex = '1060';
    alert.role = 'alert';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 150);
    }, 3000);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
