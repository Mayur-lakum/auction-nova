<?php
require_once '../includes/functions.php';
$page_title = 'How It Works';
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="text-center mb-5">
                <h1 class="fw-bold mb-3">How <?php echo SITE_NAME; ?> Works</h1>
                <p class="lead text-muted">Learn how to buy and sell on India's most trusted auction platform</p>
            </div>

            <!-- For Buyers -->
            <div class="card mb-5">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>For Buyers</h3>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="fas fa-user-plus fa-2x"></i>
                                </div>
                                <h5 class="fw-bold">1. Register</h5>
                                <p class="text-muted">Create your free account with valid email and phone number. You must be 18+ to register.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="fas fa-search fa-2x"></i>
                                </div>
                                <h5 class="fw-bold">2. Browse & Bid</h5>
                                <p class="text-muted">Search through thousands of auctions, add items to watchlist, and place competitive bids.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="fas fa-trophy fa-2x"></i>
                                </div>
                                <h5 class="fw-bold">3. Win & Pay</h5>
                                <p class="text-muted">Win auctions with the highest bid and complete secure payment to get your items.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- For Sellers -->
            <div class="card mb-5">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0"><i class="fas fa-gavel me-2"></i>For Sellers</h3>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="fas fa-camera fa-2x"></i>
                                </div>
                                <h5 class="fw-bold">1. List Your Item</h5>
                                <p class="text-muted">Upload high-quality photos, write detailed descriptions, and set your starting price.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                                <h5 class="fw-bold">2. Set Duration</h5>
                                <p class="text-muted">Choose auction duration (1-7 days), set minimum bid increment, and optional buy-now price.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="fas fa-rupee-sign fa-2x"></i>
                                </div>
                                <h5 class="fw-bold">3. Get Paid</h5>
                                <p class="text-muted">Receive bids from interested buyers and get paid when your auction ends successfully.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bidding Rules -->
            <div class="card mb-5">
                <div class="card-header bg-warning text-dark">
                    <h3 class="mb-0"><i class="fas fa-gavel me-2"></i>Bidding Rules</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-3">Bidding Guidelines</h5>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Minimum bid increment: ₹<?php echo MIN_BID_INCREMENT; ?></li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>You cannot bid on your own auctions</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Bids are binding once placed</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Highest bidder wins when auction ends</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Real-time bid updates</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-3">Auction Duration</h5>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-clock text-primary me-2"></i>Minimum: 1 day</li>
                                <li class="mb-2"><i class="fas fa-clock text-primary me-2"></i>Maximum: <?php echo MAX_AUCTION_DURATION; ?> days</li>
                                <li class="mb-2"><i class="fas fa-clock text-primary me-2"></i>Automatic end when time expires</li>
                                <li class="mb-2"><i class="fas fa-clock text-primary me-2"></i>Sellers can end early if no bids</li>
                                <li class="mb-2"><i class="fas fa-clock text-primary me-2"></i>Real-time countdown timers</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Safety Tips -->
            <div class="card mb-5">
                <div class="card-header bg-danger text-white">
                    <h3 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Safety & Security</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-3">For Buyers</h5>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-eye text-info me-2"></i>Check seller ratings and reviews</li>
                                <li class="mb-2"><i class="fas fa-images text-info me-2"></i>Review all item photos carefully</li>
                                <li class="mb-2"><i class="fas fa-question-circle text-info me-2"></i>Ask questions before bidding</li>
                                <li class="mb-2"><i class="fas fa-credit-card text-info me-2"></i>Use secure payment methods only</li>
                                <li class="mb-2"><i class="fas fa-flag text-info me-2"></i>Report suspicious listings</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-3">For Sellers</h5>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-camera text-success me-2"></i>Upload clear, honest photos</li>
                                <li class="mb-2"><i class="fas fa-edit text-success me-2"></i>Write accurate descriptions</li>
                                <li class="mb-2"><i class="fas fa-shipping-fast text-success me-2"></i>Specify shipping terms clearly</li>
                                <li class="mb-2"><i class="fas fa-handshake text-success me-2"></i>Communicate promptly with buyers</li>
                                <li class="mb-2"><i class="fas fa-star text-success me-2"></i>Maintain good seller ratings</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Getting Started -->
            <div class="text-center">
                <h3 class="fw-bold mb-3">Ready to Get Started?</h3>
                <p class="text-muted mb-4">Join thousands of satisfied buyers and sellers on <?php echo SITE_NAME; ?></p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <?php if (!is_logged_in()): ?>
                    <a href="<?php echo SITE_URL; ?>auth/register.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Register Now
                    </a>
                    <a href="<?php echo SITE_URL; ?>auctions/" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-search me-2"></i>Browse Auctions
                    </a>
                    <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>seller/create-auction.php" class="btn btn-success btn-lg">
                        <i class="fas fa-plus me-2"></i>Create Auction
                    </a>
                    <a href="<?php echo SITE_URL; ?>auctions/" class="btn btn-primary btn-lg">
                        <i class="fas fa-search me-2"></i>Browse Auctions
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
