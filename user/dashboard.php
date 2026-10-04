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

// Initialize database
$db = Database::getInstance();
$pdo = $db->getConnection();

// Get user information
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $pdo->prepare($user_query);
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Get user statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM auctions WHERE seller_id = ?) as total_auctions,
    (SELECT COUNT(*) FROM auctions WHERE seller_id = ? AND status = 'active') as active_auctions,
    (SELECT COUNT(*) FROM bids WHERE bidder_id = ?) as total_bids,
    (SELECT COUNT(*) FROM auctions WHERE seller_id = ? AND status = 'completed') as completed_auctions";
$stats_stmt = $pdo->prepare($stats_query);
$stats_stmt->execute([$user_id, $user_id, $user_id, $user_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get recent activity
$recent_activity_query = "SELECT 
    (SELECT CONCAT('Placed bid on ', title) FROM bids b JOIN auctions a ON b.auction_id = a.id WHERE b.bidder_id = ? ORDER BY b.bid_time DESC LIMIT 1) as last_bid,
    (SELECT CONCAT('Created auction: ', title) FROM auctions WHERE seller_id = ? ORDER BY created_at DESC LIMIT 1) as last_auction";
$activity_stmt = $pdo->prepare($recent_activity_query);
$activity_stmt->execute([$user_id, $user_id]);
$activity = $activity_stmt->fetch(PDO::FETCH_ASSOC);

$page_title = 'Dashboard';
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3 col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <img src="<?php echo SITE_URL; ?>assets/images/default-avatar.png" 
                             class="rounded-circle" alt="Profile" style="width: 80px; height: 80px; object-fit: cover;">
                    </div>
                    <h5 class="card-title"><?php echo htmlspecialchars($user['username']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                </div>
            </div>

            <!-- Navigation Menu -->
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <nav class="nav nav-pills nav-fill flex-column">
                        <a class="nav-link active" href="#dashboard" data-bs-toggle="pill">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="#profile" data-bs-toggle="pill">
                            <i class="fas fa-user me-2"></i>Profile
                        </a>
                        <a class="nav-link" href="#watchlist" data-bs-toggle="pill">
                            <i class="fas fa-heart me-2"></i>Watchlist
                        </a>
                        <?php if ($user['role'] === 'admin'): ?>
                        <a class="nav-link" href="#admin-panel" data-bs-toggle="pill">
                            <i class="fas fa-cog me-2"></i>Admin Panel
                        </a>
                        <?php endif; ?>
                        <a class="nav-link text-danger" href="<?php echo SITE_URL; ?>auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-lg-9 col-md-8">
            <div class="tab-content">
                <!-- Dashboard Tab -->
                <div class="tab-pane fade show active" id="dashboard">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview</h5>
                        </div>
                        <div class="card-body">
                            <!-- Statistics Cards -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <div class="card text-center border-primary">
                                        <div class="card-body">
                                            <h3 class="text-primary"><?php echo $stats['total_auctions']; ?></h3>
                                            <p class="mb-0">Total Auctions</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card text-center border-success">
                                        <div class="card-body">
                                            <h3 class="text-success"><?php echo $stats['active_auctions']; ?></h3>
                                            <p class="mb-0">Active Auctions</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card text-center border-info">
                                        <div class="card-body">
                                            <h3 class="text-info"><?php echo $stats['total_bids']; ?></h3>
                                            <p class="mb-0">Total Bids</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card text-center border-warning">
                                        <div class="card-body">
                                            <h3 class="text-warning"><?php echo $stats['completed_auctions']; ?></h3>
                                            <p class="mb-0">Completed</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Activity -->
                            <h6 class="mb-3">Recent Activity</h6>
                            <div class="list-group">
                                <?php if ($activity['last_bid']): ?>
                                <div class="list-group-item">
                                    <i class="fas fa-gavel text-primary me-2"></i>
                                    <?php echo htmlspecialchars($activity['last_bid']); ?>
                                </div>
                                <?php endif; ?>
                                <?php if ($activity['last_auction']): ?>
                                <div class="list-group-item">
                                    <i class="fas fa-plus-circle text-success me-2"></i>
                                    <?php echo htmlspecialchars($activity['last_auction']); ?>
                                </div>
                                <?php endif; ?>
                                <?php if (!$activity['last_bid'] && !$activity['last_auction']): ?>
                                <div class="list-group-item text-muted">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No recent activity. Start by creating an auction or placing bids!
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Tab -->
                <div class="tab-pane fade" id="profile">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user me-2"></i>Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form id="profileForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" name="first_name" 
                                               value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" name="last_name" 
                                               value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" name="username" 
                                               value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" class="form-control" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" name="date_of_birth" 
                                               value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Update Profile
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Watchlist Tab -->
                <div class="tab-pane fade" id="watchlist">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-heart me-2"></i>My Watchlist</h5>
                        </div>
                        <div class="card-body">
                            <div id="watchlistContent">
                                <div class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2">Loading your watchlist...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Panel Tab (only for admins) -->
                <?php if ($user['role'] === 'admin'): ?>
                <div class="tab-pane fade" id="admin-panel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Admin Panel</h5>
                        </div>
                        <div class="card-body">
                            <!-- Admin Statistics -->
                            <div class="row g-3 mb-4">
                                <!-- Business Metrics -->
                                <div class="col-12">
                                    <h6 class="text-primary mb-3"><i class="fas fa-chart-line me-2"></i>Business Metrics</h6>
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <div class="card border-primary text-center">
                                                <div class="card-body">
                                                    <i class="fas fa-dollar-sign fa-2x text-primary mb-2"></i>
                                                    <h4 id="totalRevenue">-</h4>
                                                    <p class="mb-0">Total Revenue</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border-success text-center">
                                                <div class="card-body">
                                                    <i class="fas fa-percentage fa-2x text-success mb-2"></i>
                                                    <h4 id="platformCommission">-</h4>
                                                    <p class="mb-0">Platform Commission</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border-warning text-center">
                                                <div class="card-body">
                                                    <i class="fas fa-hand-holding-usd fa-2x text-warning mb-2"></i>
                                                    <h4 id="pendingPayouts">-</h4>
                                                    <p class="mb-0">Pending Payouts</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border-info text-center">
                                                <div class="card-body">
                                                    <i class="fas fa-lock fa-2x text-info mb-2"></i>
                                                    <h4 id="escrowBalance">-</h4>
                                                    <p class="mb-0">Escrow Balance</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Auction Metrics -->
                                <div class="col-12">
                                    <h6 class="text-success mb-3"><i class="fas fa-gavel me-2"></i>Auction Metrics</h6>
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <div class="card border-success text-center">
                                                <div class="card-body">
                                                    <i class="fas fa-play fa-2x text-success mb-2"></i>
                                                    <h4 id="activeAuctions">-</h4>
                                                    <p class="mb-0">Active Auctions</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border-warning text-center">
                                                <div class="card-body">
                                                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                                    <h4 id="endingToday">-</h4>
                                                    <p class="mb-0">Ending Today</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border-danger text-center">
                                                <div class="card-body">
                                                    <i class="fas fa-flag fa-2x text-danger mb-2"></i>
                                                    <h4 id="flaggedAuctions">-</h4>
                                                    <p class="mb-0">Flagged Auctions</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border-secondary text-center">
                                                <div class="card-body">
                                                    <i class="fas fa-ban fa-2x text-secondary mb-2"></i>
                                                    <h4 id="cancelledAuctions">-</h4>
                                                    <p class="mb-0">Cancelled Auctions</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- User Metrics -->
                                <div class="col-12">
                                    <h6 class="text-info mb-3"><i class="fas fa-users me-2"></i>User Metrics</h6>
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <div class="card border-info text-center">
                                                <div class="card-body">
                                                    <i class="fas fa-users fa-2x text-info mb-2"></i>
                                                    <h4 id="totalUsers">-</h4>
                                                    <p class="mb-0">Total Users</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border-primary text-center">
                                                <div class="card-body">
                                                    <i class="fas fa-user-plus fa-2x text-primary mb-2"></i>
                                                    <h4 id="newUsersToday">-</h4>
                                                    <p class="mb-0">New Users Today</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border-warning text-center">
                                                <div class="card-body">
                                                    <i class="fas fa-user-check fa-2x text-warning mb-2"></i>
                                                    <h4 id="pendingVerification">-</h4>
                                                    <p class="mb-0">Pending Verification</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border-danger text-center">
                                                <div class="card-body">
                                                    <i class="fas fa-user-slash fa-2x text-danger mb-2"></i>
                                                    <h4 id="suspendedUsers">-</h4>
                                                    <p class="mb-0">Suspended Users</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Risk Indicators -->
                                <div class="col-12">
                                    <h6 class="text-danger mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Risk Indicators</h6>
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <div class="card border-danger text-center">
                                                <div class="card-body">
                                                    <i class="fas fa-credit-card fa-2x text-danger mb-2"></i>
                                                    <h4 id="failedPayments">-</h4>
                                                    <p class="mb-0">Failed Payments</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border-warning text-center">
                                                <div class="card-body">
                                                    <i class="fas fa-balance-scale fa-2x text-warning mb-2"></i>
                                                    <h4 id="openDisputes">-</h4>
                                                    <p class="mb-0">Open Disputes</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border-danger text-center">
                                                <div class="card-body">
                                                    <i class="fas fa-shield-alt fa-2x text-danger mb-2"></i>
                                                    <h4 id="fraudFlags">-</h4>
                                                    <p class="mb-0">Fraud Flags</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border-info text-center">
                                                <div class="card-body">
                                                    <i class="fas fa-search fa-2x text-info mb-2"></i>
                                                    <h4 id="manualReviews">-</h4>
                                                    <p class="mb-0">Manual Reviews</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Admin Management Sections -->
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="card border-primary">
                                        <div class="card-body text-center">
                                            <i class="fas fa-users fa-3x text-primary mb-3"></i>
                                            <h6>User Management</h6>
                                            <p class="small text-muted">Full user control & verification</p>
                                            <button class="btn btn-primary btn-sm" onclick="loadAdminSection('users')">
                                                Manage Users
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-success">
                                        <div class="card-body text-center">
                                            <i class="fas fa-gavel fa-3x text-success mb-3"></i>
                                            <h6>Auction Control</h6>
                                            <p class="small text-muted">Monitor & manage all auctions</p>
                                            <button class="btn btn-success btn-sm" onclick="loadAdminSection('auctions')">
                                                Manage Auctions
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-warning">
                                        <div class="card-body text-center">
                                            <i class="fas fa-user-shield fa-3x text-warning mb-3"></i>
                                            <h6>KYC & Verification</h6>
                                            <p class="small text-muted">Seller verification queue</p>
                                            <button class="btn btn-warning btn-sm" onclick="loadAdminSection('verification')">
                                                Verify Sellers
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-info">
                                        <div class="card-body text-center">
                                            <i class="fas fa-dollar-sign fa-3x text-info mb-3"></i>
                                            <h6>Financial Control</h6>
                                            <p class="small text-muted">Payments, escrow & payouts</p>
                                            <button class="btn btn-info btn-sm" onclick="loadAdminSection('payments')">
                                                Manage Payments
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-danger">
                                        <div class="card-body text-center">
                                            <i class="fas fa-balance-scale fa-3x text-danger mb-3"></i>
                                            <h6>Dispute Resolution</h6>
                                            <p class="small text-muted">Handle disputes & support</p>
                                            <button class="btn btn-danger btn-sm" onclick="loadAdminSection('disputes')">
                                                Resolve Disputes
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-secondary">
                                        <div class="card-body text-center">
                                            <i class="fas fa-chart-bar fa-3x text-secondary mb-3"></i>
                                            <h6>Reports & Analytics</h6>
                                            <p class="small text-muted">Detailed business insights</p>
                                            <button class="btn btn-secondary btn-sm" onclick="loadAdminSection('reports')">
                                                View Reports
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-dark">
                                        <div class="card-body text-center">
                                            <i class="fas fa-cogs fa-3x text-dark mb-3"></i>
                                            <h6>System Settings</h6>
                                            <p class="small text-muted">Platform configuration</p>
                                            <button class="btn btn-dark btn-sm" onclick="loadAdminSection('settings')">
                                                Configure System
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-primary">
                                        <div class="card-body text-center">
                                            <i class="fas fa-file-alt fa-3x text-primary mb-3"></i>
                                            <h6>Content Management</h6>
                                            <p class="small text-muted">CMS & legal pages</p>
                                            <button class="btn btn-primary btn-sm" onclick="loadAdminSection('content')">
                                                Manage Content
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-warning">
                                        <div class="card-body text-center">
                                            <i class="fas fa-history fa-3x text-warning mb-3"></i>
                                            <h6>Audit Logs</h6>
                                            <p class="small text-muted">Complete admin audit trail</p>
                                            <button class="btn btn-warning btn-sm" onclick="loadAdminSection('audit')">
                                                View Logs
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="adminContent" class="mt-4"></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize dashboard
    loadWatchlist();
    
    // Load admin statistics if user is admin
    <?php if ($user['role'] === 'admin'): ?>
    loadAdminStats();
    <?php endif; ?>
    
    // Profile form submission
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        e.preventDefault();
        updateProfile(this);
    });
});

// Load watchlist
function loadWatchlist() {
    fetch('<?php echo SITE_URL; ?>api/watchlist.php', {
        method: 'GET',
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(response => response.json())
    .then(data => {
        const content = document.getElementById('watchlistContent');
        if (data.success && data.watchlist.length > 0) {
            let html = '<div class="row g-3">';
            data.watchlist.forEach(item => {
                html += `
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6>${item.title}</h6>
                                <p class="text-muted">Current Bid: $${item.current_bid}</p>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeFromWatchlist(${item.auction_id})">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            content.innerHTML = html;
        } else {
            content.innerHTML = '<p class="text-muted text-center">No items in your watchlist.</p>';
        }
    })
    .catch(error => {
        document.getElementById('watchlistContent').innerHTML = 
            '<p class="text-danger text-center">Error loading watchlist.</p>';
    });
}

// Load admin statistics
function loadAdminStats() {
    fetch('<?php echo SITE_URL; ?>api/admin/stats.php', {
        method: 'GET',
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Business Metrics
            document.getElementById('totalRevenue').textContent = '$' + data.stats.total_revenue;
            document.getElementById('platformCommission').textContent = '$' + data.stats.platform_commission;
            document.getElementById('pendingPayouts').textContent = data.stats.pending_payouts;
            document.getElementById('escrowBalance').textContent = data.stats.escrow_balance;
            
            // Auction Metrics
            document.getElementById('activeAuctions').textContent = data.stats.active_auctions;
            document.getElementById('endingToday').textContent = data.stats.ending_today;
            document.getElementById('flaggedAuctions').textContent = data.stats.flagged_auctions;
            document.getElementById('cancelledAuctions').textContent = data.stats.cancelled_auctions;
            
            // User Metrics
            document.getElementById('totalUsers').textContent = data.stats.total_users;
            document.getElementById('newUsersToday').textContent = data.stats.new_users_today;
            document.getElementById('pendingVerification').textContent = data.stats.pending_verification;
            document.getElementById('suspendedUsers').textContent = data.stats.suspended_users;
            
            // Risk Indicators
            document.getElementById('failedPayments').textContent = data.stats.failed_payments;
            document.getElementById('openDisputes').textContent = data.stats.open_disputes;
            document.getElementById('fraudFlags').textContent = data.stats.fraud_flags;
            document.getElementById('manualReviews').textContent = data.stats.manual_reviews;
        }
    })
    .catch(error => {
        console.error('Error loading admin stats:', error);
    });
}

// Update profile
function updateProfile(form) {
    const formData = new FormData(form);
    
    fetch('<?php echo SITE_URL; ?>api/update-profile.php', {
        method: 'POST',
        body: formData,
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Profile updated successfully!', 'success');
        } else {
            showAlert(data.message || 'Error updating profile', 'danger');
        }
    })
    .catch(error => {
        showAlert('Error updating profile', 'danger');
    });
}

// Increase bid function
function increaseBid(auctionId, currentBid) {
    const newBid = prompt('Enter your new bid amount:', currentBid + 1);
    if (newBid && parseFloat(newBid) > currentBid) {
        placeBid(auctionId, parseFloat(newBid));
    }
}

// Place bid function
function placeBid(auctionId, amount) {
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
            loadMyBids();
        } else {
            showAlert(data.message || 'Failed to place bid', 'danger');
        }
    })
    .catch(error => {
        showAlert('Error placing bid', 'danger');
    });
}

// Admin functions
function loadAdminSection(section) {
    const content = document.getElementById('adminContent');
    content.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';
    
    fetch(`<?php echo SITE_URL; ?>api/admin/${section}.php`, {
        method: 'GET',
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(response => response.text())
    .then(html => {
        content.innerHTML = html;
    })
    .catch(error => {
        content.innerHTML = '<p class="text-danger">Error loading admin section.</p>';
    });
}

// Admin user management functions
function showAddUserModal() {
    const modal = new bootstrap.Modal(document.getElementById('addUserModal'));
    modal.show();
}

function addUser() {
    const form = document.getElementById('addUserForm');
    const formData = new FormData(form);
    
    fetch('<?php echo SITE_URL; ?>api/admin/users.php', {
        method: 'POST',
        body: formData,
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
            loadAdminSection('users');
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        showAlert('Error adding user', 'danger');
    });
}

function editUser(userId) {
    showAlert('Edit user functionality coming soon...', 'info');
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        fetch('<?php echo SITE_URL; ?>api/admin/users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `action=delete&user_id=${userId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                loadAdminSection('users');
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            showAlert('Error deleting user', 'danger');
        });
    }
}

function toggleUserStatus(userId) {
    fetch('<?php echo SITE_URL; ?>api/admin/users.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=toggle_status&user_id=${userId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            loadAdminSection('users');
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        showAlert('Error updating user status', 'danger');
    });
}

// Show alert function
function showAlert(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alert.style.zIndex = '1060';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 150);
    }, 3000);
}
</script>

<?php require_once '../includes/footer.php'; ?>
