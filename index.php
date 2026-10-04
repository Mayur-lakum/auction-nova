<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
require_once __DIR__ . '/config/config.php';

// Include necessary files
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Initialize page variables
$page_title = 'Home';

try {
    // Set a timeout for database operations
    set_time_limit(5);
    
    // Initialize database connection
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get statistics with timeout protection
    $stats_query = "SELECT 
                    (SELECT COUNT(*) FROM users WHERE role = 'user' AND is_active = TRUE) as total_users,
                    (SELECT COUNT(*) FROM auctions WHERE status = 'active' AND end_time > NOW()) as active_auctions,
                    (SELECT COUNT(*) FROM auctions WHERE status = 'ended') as completed_auctions,
                    (SELECT COUNT(*) FROM bids) as total_bids";
    $stats_stmt = $conn->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get featured auctions
    $featured_query = "SELECT a.*, c.name as category_name, u.username as seller_name,
                      (SELECT image_path FROM auction_images WHERE auction_id = a.id AND is_primary = TRUE LIMIT 1) as primary_image,
                      (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count
                      FROM auctions a 
                      JOIN categories c ON a.category_id = c.id 
                      JOIN users u ON a.seller_id = u.id 
                      WHERE a.status = 'active' AND a.end_time > NOW() AND a.is_featured = TRUE 
                      ORDER BY a.created_at DESC LIMIT 6";
    $featured_stmt = $conn->prepare($featured_query);
    $featured_stmt->execute();
    $featured_auctions = $featured_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get categories
    $categories_query = "SELECT * FROM categories WHERE is_active = TRUE ORDER BY name";
    $categories_stmt = $conn->prepare($categories_query);
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log('Database error, using fallback mode: ' . $e->getMessage());
    
    $stats = [
        'total_users' => 0,
        'active_auctions' => 0,
        'completed_auctions' => 0,
        'total_bids' => 0
    ];
    
    $featured_auctions = [];
    $categories = [
        ['id' => 1, 'name' => 'Electronics'],
        ['id' => 2, 'name' => 'Fashion'],
        ['id' => 3, 'name' => 'Home & Garden'],
        ['id' => 4, 'name' => 'Sports'],
        ['id' => 5, 'name' => 'Books'],
        ['id' => 6, 'name' => 'Other']
    ];
}

// Include header after setting variables
include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <h1 class="hero-title">Find Amazing Deals on Unique Items</h1>
                <p class="hero-subtitle">Discover and bid on a wide range of products. Join thousands of happy bidders today and find incredible deals!</p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="<?php echo SITE_URL; ?>auctions/" class="btn btn-primary btn-lg px-4 py-3 fw-semibold d-flex align-items-center">
                        <i class="fas fa-gavel me-2"></i> Start Bidding Now
                    </a>
                    <a href="<?php echo SITE_URL; ?>auth/register.php" class="btn btn-outline-primary btn-lg px-4 py-3 fw-semibold">
                        Join for Free
                    </a>
                    <?php if (is_logged_in()): ?>
                    <a href="<?php echo SITE_URL; ?>seller/create-auction.php" class="btn btn-outline-primary btn-lg px-4 py-3 fw-semibold">
                        <i class="fas fa-plus me-2"></i>Sell an Item
                    </a>
                    <?php endif; ?>
                </div>
                <div class="mt-4">
                    <div class="text-white-50">
                        <div class="fw-medium">Join our community of bidders</div>
                        <div class="small"><?php echo number_format($stats['total_users']); ?>+ happy members</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="position-relative">
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="
                        background: linear-gradient(135deg, rgba(29, 78, 216, 0.08) 0%, rgba(37, 99, 235, 0.12) 50%, rgba(59, 130, 246, 0.08) 100%);
                        border-radius: 2rem;
                        transform: rotate(3deg);
                        backdrop-filter: blur(20px);
                    "></div>
                    <div class="position-relative z-1 text-center py-5">
                        <div class="hero-brand-container mb-4">
                            <div class="brand-logo-circle mb-4">
                                <div class="brand-icon">
                                    <i class="fas fa-gavel"></i>
                                </div>
                            </div>
                            <div class="hero-brand-text">
                                <h1 class="brand-title mb-0">
                                    <span class="brand-word auction-word">AUCTION</span>
                                    <span class="brand-word nova-word">NOVA</span>
                                </h1>
                                <div class="brand-tagline mt-3">
                                    <span class="tagline-text">Where Amazing Finds Meet Great Deals</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5">
                            <div class="d-flex justify-content-center gap-3 flex-wrap">
                                <div class="feature-badge">
                                    <i class="fas fa-gavel me-2"></i>Live Auctions
                                </div>
                                <div class="feature-badge">
                                    <i class="fas fa-shield-alt me-2"></i>Secure Bidding
                                </div>
                                <div class="feature-badge">
                                    <i class="fas fa-truck me-2"></i>Fast Delivery
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon mb-3" style="width: 70px; height: 70px; margin: 0 auto; background: rgba(29, 78, 216, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-users" style="font-size: 2rem; color: var(--an-primary);"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['total_users']); ?>+</div>
                    <div class="stat-label">Registered Users</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon mb-3" style="width: 70px; height: 70px; margin: 0 auto; background: rgba(5, 150, 105, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-gavel" style="font-size: 2rem; color: var(--an-success);"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['active_auctions']); ?>+</div>
                    <div class="stat-label">Active Auctions</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon mb-3" style="width: 70px; height: 70px; margin: 0 auto; background: rgba(217, 119, 6, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-flag-checkered" style="font-size: 2rem; color: var(--an-warning);"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['completed_auctions']); ?>+</div>
                    <div class="stat-label">Completed Auctions</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon mb-3" style="width: 70px; height: 70px; margin: 0 auto; background: rgba(220, 38, 38, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-hand-paper" style="font-size: 2rem; color: var(--an-danger);"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['total_bids']); ?>+</div>
                    <div class="stat-label">Total Bids</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Shopping Cart Preview Section -->
<?php if (is_logged_in()): ?>
<!-- Cart Section -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">Your Cart</h2>
            <a href="<?php echo SITE_URL; ?>cart/" class="btn btn-outline-primary">View Full Cart</a>
        </div>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
                <h3>Your cart is empty</h3>
                <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet.</p>
                <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">
                    <i class="fas fa-shopping-bag me-2"></i> Start Shopping
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Live Bids Section -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">Your Active Bids</h2>
            <a href="<?php echo SITE_URL; ?>user/dashboard.php#my-bids" class="btn btn-outline-primary">View All Bids</a>
        </div>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-gavel fa-4x text-muted mb-4"></i>
                <h3>No active bids</h3>
                <p class="text-muted mb-4">You haven't placed any bids yet. Start bidding on auctions!</p>
                <a href="<?php echo SITE_URL; ?>auction-hub.php" class="btn btn-primary">
                    <i class="fas fa-gavel me-2"></i> Browse Auctions
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<style>
.stat-card {
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.05);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.05) !important;
}

.stat-number {
    font-weight: 700;
    background: linear-gradient(135deg, #4e73df, #224abe);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.stat-icon {
    transition: transform 0.3s ease;
}

.stat-card:hover .stat-icon {
    transform: scale(1.1);
}

/* Enhanced Auction Nova Branding Styles */
.hero-brand-container {
    position: relative;
    z-index: 10;
}

.brand-logo-circle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #FF6B6B 0%, #4ECDC4 50%, #45B7D1 100%);
    border-radius: 50%;
    box-shadow: 0 20px 40px rgba(255, 107, 107, 0.3);
    position: relative;
    animation: brandLogoFloat 3s ease-in-out infinite;
    margin: 0 auto;
}

.brand-logo-circle::before {
    content: '';
    position: absolute;
    top: -5px;
    left: -5px;
    right: -5px;
    bottom: -5px;
    background: linear-gradient(135deg, #FF6B6B 0%, #4ECDC4 50%, #45B7D1 100%);
    border-radius: 50%;
    z-index: -1;
    opacity: 0.3;
    animation: brandLogoPulse 2s ease-in-out infinite;
}

.brand-icon {
    font-size: 3rem;
    color: white;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    animation: brandIconSpin 4s ease-in-out infinite;
}

.brand-title {
    font-size: 4.5rem;
    font-weight: 900;
    line-height: 0.9;
    letter-spacing: -0.03em;
    margin: 0;
    display: block;
    position: relative;
}

.brand-word {
    display: block;
    position: relative;
    animation: brandWordSlide 1s ease-out forwards;
    opacity: 0;
    transform: translateY(30px);
}

.auction-word {
    background: linear-gradient(135deg, #FF6B6B 0%, #4ECDC4 50%, #45B7D1 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-shadow: 0 10px 30px rgba(255, 107, 107, 0.2);
    filter: drop-shadow(0 5px 15px rgba(255, 107, 107, 0.3));
    animation-delay: 0.2s;
}

.nova-word {
    background: linear-gradient(135deg, #45B7D1 0%, #96CEB4 50%, #FFEAA7 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-shadow: 0 10px 30px rgba(69, 183, 209, 0.2);
    filter: drop-shadow(0 5px 15px rgba(69, 183, 209, 0.3));
    animation-delay: 0.4s;
}

.brand-tagline {
    opacity: 0;
    animation: taglineFadeIn 1s ease-out 0.8s forwards;
    transform: translateY(20px);
}

.tagline-text {
    font-size: 1.2rem;
    font-weight: 500;
    background: linear-gradient(135deg, #45B7D1 0%, #96CEB4 50%, #4ECDC4 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: 0.02em;
}

.feature-badge {
    display: inline-flex;
    align-items: center;
    padding: 12px 24px;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(15px);
    border: 2px solid rgba(102, 126, 234, 0.2);
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.95rem;
    color: #4a5568;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    opacity: 0;
    animation: badgeSlideUp 0.8s ease-out forwards;
    position: relative;
    overflow: hidden;
}

.feature-badge::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
    transition: left 0.5s ease;
}

.feature-badge:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.2);
    border-color: rgba(102, 126, 234, 0.4);
    color: #667eea;
}

.feature-badge:hover::before {
    left: 100%;
}

.feature-badge:nth-child(1) {
    animation-delay: 1s;
}

.feature-badge:nth-child(2) {
    animation-delay: 1.2s;
}

.feature-badge:nth-child(3) {
    animation-delay: 1.4s;
}

/* Animations */
@keyframes brandLogoFloat {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-10px);
    }
}

@keyframes brandLogoPulse {
    0%, 100% {
        transform: scale(1);
        opacity: 0.3;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.1;
    }
}

@keyframes brandIconSpin {
    0%, 100% {
        transform: rotate(0deg);
    }
    25% {
        transform: rotate(-5deg);
    }
    75% {
        transform: rotate(5deg);
    }
}

@keyframes brandWordSlide {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes taglineFadeIn {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes badgeSlideUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .brand-logo-circle {
        width: 80px;
        height: 80px;
    }
    
    .brand-icon {
        font-size: 2rem;
    }
    
    .brand-title {
        font-size: 3rem;
    }
    
    .tagline-text {
        font-size: 1rem;
    }
    
    .feature-badge {
        padding: 10px 20px;
        font-size: 0.85rem;
    }
}

/* About Us Section Styles */
.feature-item {
    transition: all 0.3s ease;
}

.feature-item:hover {
    transform: translateY(-3px);
}

.icon-circle {
    transition: all 0.3s ease;
}

.feature-item:hover .icon-circle {
    transform: scale(1.1);
    box-shadow: 0 8px 25px 0.2);
}

.about-image {
    min-height: 400px;
}

.value-card {
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.05);
}

.value-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.1) !important;
}

.value-icon {
    transition: all 0.3s ease;
}

.value-card:hover .value-icon i {
    transform: scale(1.2);
}

/* Floating stats animation */
.position-absolute .bg-white {
    animation: float 3s ease-in-out infinite;
}

.position-absolute.top-10 .bg-white {
    animation-delay: 0s;
}

.position-absolute.bottom-10 .bg-white {
    animation-delay: 1.5s;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px) rotate(3deg);
    }
    50% {
        transform: translateY(-10px) rotate(3deg);
    }
}

/* Enhanced View All Buttons */
.btn-lg[href*="auctions/"]:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(37, 99, 235, 0.4) !important;
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #3b82f6 100%) !important;
}
</style>

<script>
// Animate numbers on scroll
function animateValue(obj, start, end, duration) {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        obj.innerHTML = Math.floor(progress * (end - start) + start).toLocaleString();
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}

// Initialize animation when stats are in view
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const statNumbers = entry.target.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const value = parseInt(stat.textContent.replace(/\D/g, ''));
                const duration = 2000; // 2 seconds
                animateValue(stat, 0, value, duration);
            });
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

document.addEventListener('DOMContentLoaded', () => {
    const statsSection = document.querySelector('.stat-card').parentElement.parentElement.parentElement;
    observer.observe(statsSection);
});
</script>

<!-- Featured Auctions -->
<?php if (!empty($featured_auctions)): ?>
<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <span class="badge bg-primary-soft text-primary mb-3 px-3 py-2 fw-normal">Premium Listings</span>
                <h2 class="fw-bold mb-2">Featured Auctions</h2>
                <p class="lead text-muted mb-0">Handpicked items with the best deals</p>
            </div>
        </div>
        <div class="position-relative">
            <div class="row g-4" id="featuredAuctions">
                <?php foreach ($featured_auctions as $auction): 
                    $time_left = strtotime($auction['end_time']) - time();
                    $hours_left = floor($time_left / 3600);
                    $minutes_left = floor(($time_left % 3600) / 60);
                    $current_bid = $auction['current_bid'] > 0 ? $auction['current_bid'] : $auction['starting_price'];
                    $progress = min(100, max(5, 100 - (($time_left / (24 * 3600)) * 100) / 7));
                ?>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="auction-card card h-100 border-0 shadow-sm overflow-hidden position-relative">
                        <div class="position-relative">
                            <div class="auction-image-container">
                                <img src="<?php echo auction_image_url($auction['primary_image']); ?>" 
                                     class="card-img-top" alt="<?php echo htmlspecialchars($auction['title']); ?>" 
                                     style="height: 200px; object-fit: cover; transition: transform 0.5s ease;"
                                     onerror="this.onerror=null;this.src='<?php echo SITE_URL; ?>assets/images/no-image.jpg';">
                                <div class="image-overlay"></div>
                                <div class="position-absolute top-0 end-0 m-3">
                                    <span class="badge badge-featured">
                                        <i class="fas fa-star me-1"></i> Featured
                                    </span>
                                </div>
                                <div class="position-absolute top-0 start-0 m-3">
                                    <button class="btn btn-sm btn-light rounded-circle p-2 shadow-sm" 
                                            data-bs-toggle="tooltip" data-bs-placement="top" 
                                            title="Add to watchlist">
                                        <i class="far fa-heart text-danger"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="auction-countdown position-absolute bottom-0 start-0 end-0 p-3" 
                                 style="background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);">
                                <div class="d-flex justify-content-between align-items-center text-white">
                                    <div>
                                        <small class="d-block opacity-75">Ending In</small>
                                        <div class="countdown-timer <?php echo $time_left < 3600 ? 'ending-soon' : ($time_left < 0 ? 'ended' : ''); ?>">
                                            <?php 
                                            if ($time_left < 0) {
                                                echo 'Ended';
                                            } elseif ($time_left < 3600) {
                                                echo $minutes_left . ' min';
                                            } elseif ($time_left < 86400) {
                                                echo $hours_left . 'h ' . $minutes_left . 'm';
                                            } else {
                                                $days = floor($time_left / 86400);
                                                echo $days . 'd ' . ($hours_left % 24) . 'h';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="badge bg-dark bg-opacity-50 mb-2">
                                            <i class="fas fa-gavel me-1"></i> <?php echo $auction['bid_count']; ?>
                                        </div>
                                        <div class="progress" style="height: 4px; width: 80px;">
                                            <div class="progress-bar bg-warning" role="progressbar" 
                                                 style="width: <?php echo $progress; ?>%" 
                                                 aria-valuenow="<?php echo $progress; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body position-relative">
                            <div class="category-badge">
                                <span class="badge">
                                    <?php echo htmlspecialchars($auction['category_name']); ?>
                                </span>
                            </div>
                            
                            <h5 class="card-title mb-2">
                                <a href="<?php echo SITE_URL . 'auction-details.php?id=' . $auction['id']; ?>" 
                                   class="text-dark text-decoration-none stretched-link">
                                    <?php echo htmlspecialchars($auction['title']); ?>
                                </a>
                            </h5>
                            
                            <p class="text-muted small mb-3">
                                <i class="fas fa-user text-muted me-1"></i> 
                                <span class="fw-medium"><?php echo htmlspecialchars($auction['seller_name']); ?></span>
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div>
                                    <div class="text-muted small">Current Bid</div>
                                    <div class="h5 mb-0 text-primary fw-bold">
                                        $<?php echo number_format($current_bid, 2); ?>
                                    </div>
                                </div>
                                <a href="<?php echo SITE_URL . 'auction-details.php?id=' . $auction['id']; ?>" 
                                   class="btn btn-primary">
                                    Place Bid
                                </a>
                            </div>
                            
                            <?php if ($time_left < 3600): // Less than 1 hour left ?>
                            <div class="position-absolute top-0 start-50 translate-middle">
                                <span class="badge bg-danger bg-opacity-90 px-3 py-2 mt-n4" 
                                      style="box-shadow: 0 0.5rem 1rem rgba(220, 53, 69, 0.2);">
                                    <i class="fas fa-bolt me-1"></i> Ending Soon!
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="text-center mt-5">
            <a href="<?php echo SITE_URL; ?>auctions/" 
               class="btn btn-outline-primary btn-lg px-5 py-3 fw-semibold">
                Explore All Auctions <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<style>
.auction-card {
    transition: all 0.3s ease;
    border-radius: 0.75rem;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.05);
}

.auction-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.075) !important;
}

.auction-image-container {
    position: relative;
    overflow: hidden;
}

.auction-image-container img {
    transition: transform 0.5s ease;
}

.auction-card:hover .auction-image-container img {
    transform: scale(1.05);
}

.image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, rgba(0,0,0,0) 0%, rgba(0,0,0,0.03) 100%);
    transition: all 0.3s ease;
}

.auction-card:hover .image-overlay {
    background: linear-gradient(to bottom, rgba(0,0,0,0.05) 0%, rgba(0,0,0,0.1) 100%);
}

.countdown-days,
.countdown-hours,
.countdown-minutes {
    font-size: 1.25rem;
    line-height: 1;
}

.category-badge {
    position: absolute;
    top: -16px;
    right: 1.25rem;
}

.btn-hover-lift {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.btn-hover-lift:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(13, 110, 253, 0.15);
}

.bg-primary-soft {
    background-color: rgba(13, 110, 253, 0.1) !important;
    color: #0d6efd !important;
}
</style>

<script>
// Tooltips for this page
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
<?php endif; ?>

<!-- Categories Section -->
<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="fw-bold mb-3">
                    <i class="fas fa-th-large me-2"></i>Browse by Category
                </h2>
                <p class="text-muted">Find exactly what you're looking for</p>
            </div>
        </div>
        <div class="row g-4">
            <?php foreach (array_slice($categories, 0, 8) as $category): ?>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <a href="<?php echo SITE_URL; ?>auctions/?category=<?php echo $category['id']; ?>" 
                   class="text-decoration-none">
                    <div class="card h-100 text-center category-card">
                        <div class="card-body">
                            <i class="fas fa-box fa-3x text-primary mb-3"></i>
                            <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                            <p class="card-text text-muted small">
                                <?php echo htmlspecialchars($category['description']); ?>
                            </p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?php echo SITE_URL; ?>auctions/" 
               class="btn px-4 py-2 fw-semibold"
               style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #3b82f6 100%); 
                      border: none; 
                      color: white; 
                      border-radius: 50px; 
                      box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
                      transition: all 0.3s ease;
                      text-decoration: none;">
                View All Categories <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="fw-bold mb-3">How It Works</h2>
                <p class="lead">Get started in just 3 simple steps</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <div class="mb-3">
                    <i class="fas fa-user-plus fa-4x" style="color: var(--an-primary);"></i>
                </div>
                <h4 class="fw-bold mb-3">1. Register</h4>
                <p>Create your free account and verify your identity to start bidding and selling.</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="mb-3">
                    <i class="fas fa-search fa-4x" style="color: var(--an-primary);"></i>
                </div>
                <h4 class="fw-bold mb-3">2. Browse & Bid</h4>
                <p>Explore thousands of auctions, find items you love, and place your bids.</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="mb-3">
                    <i class="fas fa-trophy fa-4x" style="color: var(--an-primary);"></i>
                </div>
                <h4 class="fw-bold mb-3">3. Win & Enjoy</h4>
                <p>Win auctions, complete payments, and enjoy your new purchases!</p>
            </div>
        </div>
    </div>
</section>

<!-- About Us Section -->
<section id="about" class="py-5 bg-light">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <span class="badge bg-primary-soft text-primary mb-3 px-3 py-2 fw-normal">Our Story</span>
                <h2 class="fw-bold mb-3">About Auction Nova</h2>
                <p class="lead text-muted mb-0">Revolutionizing the way people buy and sell unique items online</p>
            </div>
        </div>
        
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="about-content">
                    <h3 class="fw-bold mb-4" style="color: #2d3748;">Our Mission</h3>
                    <p class="mb-4" style="color: #4a5568; line-height: 1.8;">
                        At Auction Nova, we believe everyone deserves access to unique items and great deals. Our platform connects buyers and sellers from around the world, creating a vibrant marketplace where rare finds meet enthusiastic bidders.
                    </p>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="feature-item d-flex align-items-start">
                                <div class="feature-icon me-3">
                                    <div class="icon-circle" style="width: 50px; height: 50px; background: linear-gradient(135deg, #FF6B6B 0%, #4ECDC4 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-shield-alt text-white"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="fw-semibold mb-2">Secure Platform</h5>
                                    <p class="text-muted small mb-0">Advanced security measures protect every transaction</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-item d-flex align-items-start">
                                <div class="feature-icon me-3">
                                    <div class="icon-circle" style="width: 50px; height: 50px; background: linear-gradient(135deg, #45B7D1 0%, #96CEB4 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-users text-white"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="fw-semibold mb-2">Global Community</h5>
                                    <p class="text-muted small mb-0">Connect with buyers and sellers worldwide</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-item d-flex align-items-start">
                                <div class="feature-icon me-3">
                                    <div class="icon-circle" style="width: 50px; height: 50px; background: linear-gradient(135deg, #FFEAA7 0%, #FF6B6B 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-clock text-white"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="fw-semibold mb-2">24/7 Auctions</h5>
                                    <p class="text-muted small mb-0">Bid anytime, anywhere with our live auction system</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-item d-flex align-items-start">
                                <div class="feature-icon me-3">
                                    <div class="icon-circle" style="width: 50px; height: 50px; background: linear-gradient(135deg, #4ECDC4 0%, #45B7D1 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-trophy text-white"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="fw-semibold mb-2">Best Deals</h5>
                                    <p class="text-muted small mb-0">Find incredible bargains and rare collectibles</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="<?php echo SITE_URL; ?>pages/about.php" class="btn btn-primary btn-lg px-4 py-3 fw-semibold">
                            Learn More About Us <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="about-image position-relative">
                    <div class="position-relative">
                        <img src="https://picsum.photos/seed/auction-nova-about/600/400.jpg" 
                             alt="About Auction Nova" 
                             class="img-fluid rounded-3 shadow-lg"
                             style="width: 100%; height: 400px; object-fit: cover;">
                        <div class="position-absolute top-0 start-0 w-100 h-100 rounded-3" 
                             style="background: linear-gradient(135deg, rgba(255, 107, 107, 0.1) 0%, rgba(78, 205, 196, 0.1) 100%); pointer-events: none;">
                        </div>
                    </div>
                    
                    <!-- Floating Stats Cards -->
                    <div class="position-absolute top-10 end-10">
                        <div class="bg-white rounded-2 shadow-lg p-3" style="transform: rotate(3deg);">
                            <div class="text-center">
                                <div class="h4 mb-0 fw-bold" style="color: #FF6B6B;"><?php echo number_format($stats['total_users']); ?>+</div>
                                <div class="small text-muted">Happy Users</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="position-absolute bottom-10 start-10">
                        <div class="bg-white rounded-2 shadow-lg p-3" style="transform: rotate(-3deg);">
                            <div class="text-center">
                                <div class="h4 mb-0 fw-bold" style="color: #4ECDC4;"><?php echo number_format($stats['completed_auctions']); ?>+</div>
                                <div class="small text-muted">Successful Auctions</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Values Section -->
        <div class="row mt-5 pt-5">
            <div class="col-12 text-center mb-4">
                <h3 class="fw-bold mb-3">Our Core Values</h3>
                <p class="text-muted">The principles that guide everything we do</p>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="value-card text-center p-4 rounded-3 bg-white shadow-sm h-100">
                    <div class="value-icon mb-3">
                        <i class="fas fa-heart fa-2x" style="color: #FF6B6B;"></i>
                    </div>
                    <h5 class="fw-semibold mb-2">Passion</h5>
                    <p class="text-muted small">Driven by our love for unique items and great deals</p>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="value-card text-center p-4 rounded-3 bg-white shadow-sm h-100">
                    <div class="value-icon mb-3">
                        <i class="fas fa-handshake fa-2x" style="color: #4ECDC4;"></i>
                    </div>
                    <h5 class="fw-semibold mb-2">Trust</h5>
                    <p class="text-muted small">Building lasting relationships with our community</p>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="value-card text-center p-4 rounded-3 bg-white shadow-sm h-100">
                    <div class="value-icon mb-3">
                        <i class="fas fa-lightbulb fa-2x" style="color: #45B7D1;"></i>
                    </div>
                    <h5 class="fw-semibold mb-2">Innovation</h5>
                    <p class="text-muted small">Constantly improving the auction experience</p>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="value-card text-center p-4 rounded-3 bg-white shadow-sm h-100">
                    <div class="value-icon mb-3">
                        <i class="fas fa-star fa-2x" style="color: #FFEAA7;"></i>
                    </div>
                    <h5 class="fw-semibold mb-2">Excellence</h5>
                    <p class="text-muted small">Delivering the best auction platform experience</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Alert Container for AJAX messages -->
<div id="alert-container" class="container mt-3"></div>

<!-- CSRF Token for AJAX requests -->
<input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

<script>
// Site URL for JavaScript
var siteUrl = '<?php echo SITE_URL; ?>';

// Initialize countdown timers
$(document).ready(function() {
    updateCountdowns();
    setInterval(updateCountdowns, 1000);
});

// Real-time updates for user side
function checkForUserUpdates() {
    fetch('includes/real-time-user.php?action=get_auctions')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateAuctionDisplays(data);
            }
        })
        .catch(error => console.log('Auction update error:', error));
    
    fetch('includes/real-time-user.php?action=get_stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStats(data.stats);
            }
        })
        .catch(error => console.log('Stats update error:', error));
}

function updateAuctionDisplays(data) {
    // Update featured auctions
    const featuredContainer = document.querySelector('.featured-auctions');
    if (featuredContainer && data.featured) {
        updateAuctionGrid(featuredContainer, data.featured);
    }
    
    // Update recent auctions
    const recentContainer = document.querySelector('.recent-auctions');
    if (recentContainer && data.recent) {
        updateAuctionGrid(recentContainer, data.recent);
    }
}

function updateAuctionGrid(container, auctions) {
    const cards = container.querySelectorAll('.auction-card');
    const newCount = auctions.length;
    const currentCount = cards.length;
    
    if (newCount !== currentCount) {
        // Animate the change
        container.style.opacity = '0.5';
        setTimeout(() => {
            location.reload(); // Simple reload for now
        }, 500);
    }
}

function updateStats(newStats) {
    const statElements = {
        'total_users': document.querySelector('[data-stat="users"]'),
        'active_auctions': document.querySelector('[data-stat="auctions"]'),
        'completed_auctions': document.querySelector('[data-stat="completed"]'),
        'total_bids': document.querySelector('[data-stat="bids"]')
    };
    
    Object.keys(statElements).forEach(key => {
        const element = statElements[key];
        if (element && newStats[key] !== undefined) {
            const oldValue = parseInt(element.textContent.replace(/,/g, ''));
            const newValue = newStats[key];
            
            if (oldValue !== newValue) {
                element.style.transform = 'scale(1.2)';
                element.style.color = '#48bb78';
                element.textContent = newValue.toLocaleString();
                
                setTimeout(() => {
                    element.style.transform = 'scale(1)';
                    element.style.color = '';
                }, 500);
            }
        }
    });
}

// Check for updates every 10 seconds
setInterval(checkForUserUpdates, 10000);

// Check when page becomes visible again
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        checkForUserUpdates();
    }
});

</script>

<?php include 'includes/footer.php'; ?>
