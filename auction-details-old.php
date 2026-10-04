<?php
require_once 'includes/functions.php';
require_once 'includes/Database.php';

$auction_id = $_GET['id'] ?? 0;

if (!$auction_id || !is_numeric($auction_id)) {
    header('Location: ' . SITE_URL . 'auctions/');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get auction details
$auction_query = "SELECT a.*, c.name as category_name, u.username as seller_name, u.id as seller_id,
                  (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count,
                  (SELECT COUNT(*) FROM watchlist WHERE auction_id = a.id) as watch_count
                  FROM auctions a 
                  JOIN categories c ON a.category_id = c.id 
                  JOIN users u ON a.seller_id = u.id 
                  WHERE a.id = ?";
$auction_stmt = $conn->prepare($auction_query);
$auction_stmt->execute([$auction_id]);
$auction = $auction_stmt->fetch(PDO::FETCH_ASSOC);

if (!$auction) {
    header('Location: ' . SITE_URL . 'auctions/');
    exit;
}

// Update view count
$view_update = "UPDATE auctions SET view_count = view_count + 1 WHERE id = ?";
$view_stmt = $conn->prepare($view_update);
$view_stmt->execute([$auction_id]);

// Get auction images
$images_query = "SELECT * FROM auction_images WHERE auction_id = ? ORDER BY is_primary DESC, id ASC";
$images_stmt = $conn->prepare($images_query);
$images_stmt->execute([$auction_id]);
$images = $images_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get bid history
$bids_query = "SELECT b.*, u.username FROM bids b 
               JOIN users u ON b.bidder_id = u.id 
               WHERE b.auction_id = ? 
               ORDER BY b.created_at DESC LIMIT 20";
$bids_stmt = $conn->prepare($bids_query);
$bids_stmt->execute([$auction_id]);
$bids = $bids_stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if user is watching this auction
$is_watching = false;
if (is_logged_in()) {
    $watch_query = "SELECT id FROM watchlist WHERE user_id = ? AND auction_id = ?";
    $watch_stmt = $conn->prepare($watch_query);
    $watch_stmt->execute([$_SESSION['user_id'], $auction_id]);
    $is_watching = $watch_stmt->fetch() ? true : false;
}

// Get similar auctions
$similar_query = "SELECT a.*, 
                  (SELECT image_path FROM auction_images WHERE auction_id = a.id AND is_primary = TRUE LIMIT 1) as primary_image,
                  (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count
                  FROM auctions a 
                  WHERE a.category_id = ? AND a.id != ? AND a.status = 'active' AND a.end_time > NOW()
                  ORDER BY a.created_at DESC LIMIT 4";
$similar_stmt = $conn->prepare($similar_query);
$similar_stmt->execute([$auction['category_id'], $auction_id]);
$similar_auctions = $similar_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = $auction['title'];
include 'includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>auctions/">Auctions</a></li>
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>auctions/?category=<?php echo $auction['category_id']; ?>"><?php echo htmlspecialchars($auction['category_name']); ?></a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($auction['title']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Image Gallery -->
            <div class="card mb-4">
                <div class="card-body p-0">
                    <?php if (!empty($images)): ?>
                    <div id="auctionCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <?php foreach ($images as $index => $image): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <img src="<?php echo auction_image_url($image['image_path']); ?>" 
                                     class="d-block w-100" style="height: 400px; object-fit: cover;" 
                                     onerror="this.onerror=null;this.src='<?php echo SITE_URL; ?>assets/images/no-image.jpg';"
                                     alt="<?php echo htmlspecialchars($auction['title']); ?>">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($images) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#auctionCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#auctionCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                        
                        <!-- Thumbnails -->
                        <div class="carousel-indicators position-relative mt-3 mb-0">
                            <?php foreach ($images as $index => $image): ?>
                            <button type="button" data-bs-target="#auctionCarousel" data-bs-slide-to="<?php echo $index; ?>" 
                                    class="<?php echo $index === 0 ? 'active' : ''; ?>" style="width: 60px; height: 60px; margin: 0 5px;">
                                <img src="<?php echo auction_image_url($image['image_path']); ?>" 
                                     class="w-100 h-100" style="object-fit: cover; border-radius: 4px;"
                                     onerror="this.onerror=null;this.src='<?php echo SITE_URL; ?>assets/images/no-image.jpg';">
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 400px;">
                        <i class="fas fa-image fa-5x text-muted"></i>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Auction Details -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h1 class="h3 fw-bold mb-2"><?php echo htmlspecialchars($auction['title']); ?></h1>
                            <div class="d-flex align-items-center text-muted">
                                <i class="fas fa-tag me-2"></i>
                                <span class="me-3"><?php echo htmlspecialchars($auction['category_name']); ?></span>
                                <i class="fas fa-eye me-2"></i>
                                <span class="me-3"><?php echo number_format($auction['view_count']); ?> views</span>
                                <i class="fas fa-clock me-2"></i>
                                <span>Listed <?php echo time_ago($auction['created_at']); ?></span>
                            </div>
                        </div>
                        
                        <?php if ($auction['is_featured']): ?>
                        <span class="badge bg-warning fs-6">
                            <i class="fas fa-star me-1"></i>Featured
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Description</h5>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($auction['description'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Seller Information</h5>
                            <div class="d-flex align-items-center">
                                <img src="<?php echo SITE_URL; ?>assets/images/default-avatar.png" 
                                     class="user-avatar me-3" alt="Seller">
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($auction['seller_name']); ?></div>
                                    <small class="text-muted">Seller since <?php echo date('Y', strtotime($auction['created_at'])); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Auction Status -->
                    <div class="alert <?php echo is_auction_active($auction) ? 'alert-success' : 'alert-danger'; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>
                                    <?php if (is_auction_active($auction)): ?>
                                    <i class="fas fa-play-circle me-2"></i>Auction Active
                                    <?php else: ?>
                                    <i class="fas fa-stop-circle me-2"></i>Auction Ended
                                    <?php endif; ?>
                                </strong>
                            </div>
                            <div class="countdown-timer" data-end-time="<?php echo $auction['end_time']; ?>">
                                <?php echo time_remaining($auction['end_time']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bid History -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>Bid History (<?php echo count($bids); ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($bids)): ?>
                    <p class="text-muted text-center py-3">No bids placed yet. Be the first to bid!</p>
                    <?php else: ?>
                    <div class="bid-history" id="bid-history">
                        <?php foreach ($bids as $bid): ?>
                        <div class="bid-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo format_currency($bid['bid_amount']); ?></strong>
                                <small class="text-muted d-block">by <?php echo htmlspecialchars($bid['username']); ?></small>
                            </div>
                            <small class="text-muted"><?php echo time_ago($bid['created_at']); ?></small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Bidding Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="display-6 fw-bold text-primary" id="current-price">
                            <?php echo format_currency($auction['current_price']); ?>
                        </div>
                        <small class="text-muted" id="bid-count">
                            <?php echo $auction['bid_count']; ?> bids • <?php echo $auction['watch_count']; ?> watching
                        </small>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">Starting price: <?php echo format_currency($auction['starting_price']); ?></small>
                    </div>

                    <?php if (is_logged_in() && can_bid($auction, $_SESSION['user_id'])): ?>
                    <div class="bid-section">
                        <h6 class="fw-bold mb-3">Place Your Bid</h6>
                        
                        <div class="mb-3">
                            <label class="form-label">Bid Amount</label>
                            <div class="bid-input-group">
                                <span class="currency-symbol">₹</span>
                                <input type="number" class="form-control" id="bid-amount" 
                                       min="<?php echo $auction['current_price'] + $auction['min_increment']; ?>" 
                                       step="<?php echo $auction['min_increment']; ?>"
                                       placeholder="<?php echo $auction['current_price'] + $auction['min_increment']; ?>">
                            </div>
                            <small class="text-muted" id="min-bid-display">
                                Minimum bid: <?php echo format_currency($auction['current_price'] + $auction['min_increment']); ?>
                            </small>
                        </div>

                        <!-- Quick Bid Buttons -->
                        <div class="mb-3">
                            <small class="text-muted">Quick Bids:</small>
                            <div class="d-grid gap-2 mt-2">
                                <?php 
                                $min_increment = $auction['min_increment'] ?? 10;
                                $current_price = $auction['current_price'];
                                ?>
                                <button class="btn btn-success quick-bid-btn" 
                                        data-bid-amount="<?php echo $current_price + $min_increment; ?>">
                                    +$<?php echo number_format($min_increment, 2); ?> 
                                    ($<?php echo number_format($current_price + $min_increment, 2); ?>)
                                </button>
                                <button class="btn btn-success quick-bid-btn" 
                                        data-bid-amount="<?php echo $current_price + ($min_increment * 2); ?>">
                                    +$<?php echo number_format($min_increment * 2, 2); ?> 
                                    ($<?php echo number_format($current_price + ($min_increment * 2), 2); ?>)
                                </button>
                                <button class="btn btn-success quick-bid-btn" 
                                        data-bid-amount="<?php echo $current_price + ($min_increment * 5); ?>">
                                    +$<?php echo number_format($min_increment * 5, 2); ?> 
                                    ($<?php echo number_format($current_price + ($min_increment * 5), 2); ?>)
                                </button>
                            </div>
                        </div>

                        <button type="button" class="btn btn-bid w-100 mb-3" id="place-bid-btn" 
                                onclick="placeBid(<?php echo $auction_id; ?>)">
                            <i class="fas fa-gavel me-2"></i>Place Bid
                        </button>
                    </div>
                    <?php elseif (!is_logged_in()): ?>
                    <div class="text-center">
                        <p class="text-muted mb-3">Login to place a bid</p>
                        <a href="<?php echo SITE_URL; ?>auth/login.php" class="btn btn-primary w-100">
                            <i class="fas fa-sign-in-alt me-2"></i>Login to Bid
                        </a>
                    </div>
                    <?php elseif ($auction['seller_id'] == $_SESSION['user_id']): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>This is your auction
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>This auction has ended
                    </div>
                    <?php endif; ?>

                    <!-- Watchlist Button -->
                    <?php if (is_logged_in() && $auction['seller_id'] != $_SESSION['user_id']): ?>
                    <button class="btn btn-watchlist w-100 <?php echo $is_watching ? 'active' : ''; ?>" 
                            data-auction-id="<?php echo $auction_id; ?>">
                        <i class="<?php echo $is_watching ? 'fas' : 'far'; ?> fa-heart me-2"></i>
                        <?php echo $is_watching ? 'Watching' : 'Add to Watchlist'; ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Auction Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Auction Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <small class="text-muted">Start Time</small>
                            <div class="fw-bold"><?php echo date('M j, Y g:i A', strtotime($auction['start_time'])); ?></div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">End Time</small>
                            <div class="fw-bold"><?php echo date('M j, Y g:i A', strtotime($auction['end_time'])); ?></div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Min Increment</small>
                            <div class="fw-bold"><?php echo format_currency($auction['min_increment']); ?></div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Status</small>
                            <div class="fw-bold text-<?php echo is_auction_active($auction) ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($auction['status']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Share -->
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Share This Auction</h6>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary btn-sm flex-fill" onclick="shareAuction('facebook')">
                            <i class="fab fa-facebook-f"></i>
                        </button>
                        <button class="btn btn-outline-info btn-sm flex-fill" onclick="shareAuction('twitter')">
                            <i class="fab fa-twitter"></i>
                        </button>
                        <button class="btn btn-outline-success btn-sm flex-fill" onclick="shareAuction('whatsapp')">
                            <i class="fab fa-whatsapp"></i>
                        </button>
                        <button class="btn btn-outline-secondary btn-sm flex-fill" onclick="copyLink()">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Similar Auctions -->
    <?php if (!empty($similar_auctions)): ?>
    <div class="mt-5">
        <h3 class="fw-bold mb-4">Similar Auctions</h3>
        <div class="row g-4">
            <?php foreach ($similar_auctions as $similar): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card auction-card h-100">
                    <div class="position-relative">
                        <?php if ($similar['primary_image']): ?>
                        <img src="<?php echo auction_image_url($similar['primary_image']); ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($similar['title']); ?>"
                             onerror="this.onerror=null;this.src='<?php echo SITE_URL; ?>assets/images/no-image.jpg';">
                        <?php else: ?>
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center">
                            <i class="fas fa-image fa-3x text-muted"></i>
                        </div>
                        <?php endif; ?>
                        <div class="countdown-timer position-absolute bottom-0 start-0 m-2" 
                             data-end-time="<?php echo $similar['end_time']; ?>">
                            <?php echo time_remaining($similar['end_time']); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars(substr($similar['title'], 0, 40)) . (strlen($similar['title']) > 40 ? '...' : ''); ?></h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="price-display small"><?php echo format_currency($similar['current_price']); ?></div>
                            <small class="text-muted"><?php echo $similar['bid_count']; ?> bids</small>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <a href="<?php echo SITE_URL; ?>auction-details.php?id=<?php echo $similar['id']; ?>" 
                           class="btn btn-sm btn-outline-primary w-100">View</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Alert Container -->
<div id="alert-container" class="container mt-3"></div>

<!-- CSRF Token -->
<input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

<script>
var siteUrl = '<?php echo SITE_URL; ?>';
var auctionId = <?php echo $auction_id; ?>;

$(document).ready(function() {
    // Start auto-refresh for bid updates
    startAutoRefresh(5000);
    
    // Initialize countdown timer
    updateCountdowns();
    setInterval(updateCountdowns, 1000);
});

function initSSE() {
    const eventSource = new EventSource('<?php echo SITE_URL; ?>api/live-updates.php');
    
    eventSource.onopen = function(event) {
        console.log('SSE connection opened');
    };
    
    eventSource.addEventListener('bid_placed', function(event) {
        const data = JSON.parse(event.data);
        console.log('New bid received:', data);
        
        // Update UI if this is the current auction
        if (data.auction_id == <?php echo $auction_id; ?>) {
            updateCurrentBid(data.bid_amount);
            showBidNotification(data.bidder_username, data.bid_amount);
        }
    });
    
    eventSource.addEventListener('auction_created_live', function(event) {
        const data = JSON.parse(event.data);
        console.log('New auction created:', data);
        // Could update a "new auctions" notification
    });
    
    eventSource.onerror = function(event) {
        console.log('SSE error:', event);
        // Attempt to reconnect after 5 seconds
        setTimeout(() => {
            initSSE();
        }, 5000);
    };
}

function updateCurrentBid(newPrice) {
    // Update current price display
    const priceElement = document.getElementById('current-price');
    if (priceElement) {
        priceElement.textContent = '$' + newPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    
    // Update minimum bid input
    const bidInput = document.getElementById('bid-amount');
    const minIncrement = <?php echo $auction['min_increment']; ?>;
    const newMinBid = newPrice + minIncrement;
    bidInput.value = newMinBid.toFixed(2);
    bidInput.setAttribute('min', newMinBid);
    
    // Update min bid display
    const minBidDisplay = document.getElementById('min-bid-display');
    if (minBidDisplay) {
        minBidDisplay.textContent = 'Minimum bid: $' + newMinBid.toFixed(2);
    }
    
    // Update quick bid buttons
    updateQuickBidButtons(newPrice);
}

function updateQuickBidButtons(newCurrentPrice) {
    const minIncrement = <?php echo $auction['min_increment']; ?>;
    
    document.querySelectorAll('.quick-bid-btn').forEach((button, index) => {
        const multiplier = index + 1;
        const newAmount = newCurrentPrice + (minIncrement * multiplier);
        button.dataset.bidAmount = newAmount;
        button.innerHTML = `+$${(minIncrement * multiplier).toFixed(2)} ($${newAmount.toFixed(2)})`;
    });
}

function showBidNotification(username, amount) {
    const notification = document.createElement('div');
    notification.className = 'alert alert-info alert-dismissible fade show position-fixed top-0 end-0 m-3';
    notification.style.zIndex = '9999';
    notification.innerHTML = `
        <strong>New Bid!</strong><br>
        ${username} placed a bid of $${amount.toFixed(2)}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

function shareAuction(platform) {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent('<?php echo addslashes($auction['title']); ?>');
    
    let shareUrl = '';
    
    switch(platform) {
        case 'facebook':
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
            break;
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
            break;
        case 'whatsapp':
            shareUrl = `https://wa.me/?text=${title} ${url}`;
            break;
    }
    
    if (shareUrl) {
        window.open(shareUrl, '_blank', 'width=600,height=400');
    }
}

function copyLink() {
    navigator.clipboard.writeText(window.location.href).then(function() {
        showAlert('success', 'Link copied to clipboard!');
    });
}

// Enhanced Bidding Functionality
function placeBid(auctionId) {
    console.log('placeBid called with auctionId:', auctionId);
    
    if (!<?php echo is_logged_in() ? 'true' : 'false'; ?>) {
        alert('Please login to place a bid');
        window.location.href = '<?php echo SITE_URL; ?>auth/login.php';
        return;
    }
    
    const bidInput = document.getElementById('bid-amount');
    console.log('bidInput element:', bidInput);
    
    if (!bidInput) {
        alert('Bid input field not found!');
        return;
    }
    
    const bidAmount = parseFloat(bidInput.value);
    const currentPrice = <?php echo $auction['current_price']; ?>;
    const minIncrement = <?php echo $auction['min_increment']; ?>;
    const minBid = currentPrice + minIncrement;
    
    console.log('bidAmount:', bidAmount, 'currentPrice:', currentPrice, 'minBid:', minBid);
    
    // Validation
    if (!bidAmount || isNaN(bidAmount)) {
        alert('Please enter a valid bid amount');
        bidInput.focus();
        return;
    }
    
    if (bidAmount < minBid) {
        alert(`Minimum bid is ₹${minBid.toFixed(2)}`);
        bidInput.value = minBid.toFixed(2);
        bidInput.focus();
        return;
    }
    
    // Show loading state
    const bidBtn = document.getElementById('place-bid-btn');
    const originalText = bidBtn.innerHTML;
    bidBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Placing Bid...';
    bidBtn.disabled = true;
    
    // Prepare form data
    const formData = new FormData();
    formData.append('auction_id', auctionId);
    formData.append('bid_amount', bidAmount);
    formData.append('csrf_token', '<?php echo generate_csrf_token(); ?>');
    
    // Make API call
    fetch('<?php echo SITE_URL; ?>api/place-bid.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            alert('Bid placed successfully! Amount: ₹' + data.new_price);
            
            // Update UI
            updateBidInfo(data.new_price);
            
            // Refresh bid history
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to place bid. Please try again. Error: ' + error.message);
    })
    .finally(() => {
        // Reset button
        bidBtn.innerHTML = originalText;
        bidBtn.disabled = false;
    });
}

function updateBidInfo(newPrice) {
    // Update current price display
    const priceElement = document.querySelector('.current-price');
    if (priceElement) {
        priceElement.textContent = '₹' + newPrice.toLocaleString('en-IN', {minimumFractionDigits: 2});
    }
    
    // Update minimum bid input
    const bidInput = document.getElementById('bid-amount');
    const minIncrement = <?php echo $auction['min_increment']; ?>;
    const newMinBid = newPrice + minIncrement;
    bidInput.value = newMinBid.toFixed(2);
    bidInput.setAttribute('min', newMinBid);
    
    // Update bid count
    const bidCountElement = document.querySelector('.bid-count-display');
    if (bidCountElement) {
        const currentCount = parseInt(bidCountElement.textContent) || 0;
        bidCountElement.textContent = (currentCount + 1) + ' bids';
    }
}

function showAlert(type, message) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert-floating');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-floating`;
    alert.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideInRight 0.3s ease-out;
    `;
    alert.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
            <span>${message}</span>
            <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    document.body.appendChild(alert);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alert.parentElement) {
            alert.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => alert.remove(), 300);
        }
    }, 5000);
}

// Auto-update bid amount when page loads
document.addEventListener('DOMContentLoaded', function() {
    const bidInput = document.getElementById('bid-amount');
    if (bidInput) {
        const currentPrice = <?php echo $auction['current_price']; ?>;
        const minIncrement = <?php echo $auction['min_increment']; ?>;
        const minBid = currentPrice + minIncrement;
        bidInput.value = minBid.toFixed(2);
        bidInput.setAttribute('min', minBid);
        bidInput.setAttribute('step', minIncrement);
    }
    
    // Add quick bid button handlers
    document.querySelectorAll('.quick-bid-btn').forEach(button => {
        button.addEventListener('click', function() {
            const bidAmount = parseFloat(this.dataset.bidAmount);
            document.getElementById('bid-amount').value = bidAmount.toFixed(2);
            placeBid(<?php echo $auction_id; ?>);
        });
    });
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    .alert-floating {
        border-radius: 12px !important;
        border: none !important;
        font-weight: 500;
    }
`;
document.head.appendChild(style);
</script>

<?php include 'includes/footer.php'; ?>
