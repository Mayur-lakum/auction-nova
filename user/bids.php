<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'My Bids';
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-3">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">My Account</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="bids.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-gavel me-2"></i> My Bids
                    </a>
                    <a href="watchlist.php" class="list-group-item list-group-item-action">
                        <i class="far fa-heart me-2"></i> Watchlist
                    </a>
                    <a href="orders.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-shopping-bag me-2"></i> My Orders
                    </a>
                    <a href="payments.php" class="list-group-item list-group-item-action">
                        <i class="far fa-credit-card me-2"></i> Payment Methods
                    </a>
                    <a href="settings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog me-2"></i> Account Settings
                    </a>
                    <a href="../includes/logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>
            
            <div class="card shadow-sm d-none d-lg-block">
                <div class="card-body text-center">
                    <div class="position-relative mb-3">
                        <img src="../assets/images/default-avatar.png" alt="User" class="rounded-circle" width="100">
                        <button class="btn btn-sm btn-primary position-absolute bottom-0 end-0 rounded-circle" style="width: 32px; height: 32px;">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                    <h5 class="mb-1"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h5>
                    <p class="text-muted small mb-0">Member since <?php echo date('F Y', strtotime('2023-01-15')); ?></p>
                    <div class="d-flex justify-content-center mt-2">
                        <div class="text-center px-3">
                            <div class="h5 mb-0">42</div>
                            <small class="text-muted">Bids</small>
                        </div>
                        <div class="text-center px-3">
                            <div class="h5 mb-0">8</div>
                            <small class="text-muted">Won</small>
                        </div>
                        <div class="text-center px-3">
                            <div class="h5 mb-0">12</div>
                            <small class="text-muted">Watching</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">My Bids</h2>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-secondary active">All Bids</button>
                    <button type="button" class="btn btn-outline-secondary">Active</button>
                    <button type="button" class="btn btn-outline-secondary">Won</button>
                    <button type="button" class="btn btn-outline-secondary">Lost</button>
                </div>
            </div>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Active Bids (3)</h5>
                        <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                </div>
                <div class="list-group list-group-flush">
                    <!-- Active Bid 1 -->
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <img src="../assets/images/auctions/3.jpg" alt="Product" class="img-fluid rounded">
                            </div>
                            <div class="col-md-5">
                                <h6 class="mb-1">Vintage Gibson Les Paul Guitar</h6>
                                <p class="text-muted small mb-1">Ends in: <span class="text-danger">2d 4h 23m</span></p>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-primary-soft text-primary me-2">Musical Instruments</span>
                                    <span class="badge bg-success-soft text-success">Winning</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex flex-column">
                                    <small class="text-muted">Your Max Bid</small>
                                    <span class="fw-bold">$2,750.00</span>
                                    <small class="text-muted">Placed on Dec 25, 2023</small>
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <button class="btn btn-sm btn-outline-primary w-100 mb-2">Increase Bid</button>
                                <button class="btn btn-sm btn-outline-secondary w-100">Cancel Bid</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Active Bid 2 -->
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <img src="../assets/images/auctions/4.jpg" alt="Product" class="img-fluid rounded">
                            </div>
                            <div class="col-md-5">
                                <h6 class="mb-1">Rare 1960s Rolex Daytona</h6>
                                <p class="text-muted small mb-1">Ends in: <span class="text-danger">1d 2h 45m</span></p>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-primary-soft text-primary me-2">Luxury Watches</span>
                                    <span class="badge bg-warning-soft text-warning">Outbid</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex flex-column">
                                    <small class="text-muted">Your Max Bid</small>
                                    <span class="fw-bold">$15,000.00</span>
                                    <small class="text-muted">Placed on Dec 26, 2023</small>
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <button class="btn btn-sm btn-outline-primary w-100 mb-2">Increase Bid</button>
                                <button class="btn btn-sm btn-outline-secondary w-100">Cancel Bid</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Recently Won (2)</h5>
                </div>
                <div class="list-group list-group-flush">
                    <!-- Won Bid 1 -->
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <img src="../assets/images/auctions/5.jpg" alt="Product" class="img-fluid rounded">
                            </div>
                            <div class="col-md-5">
                                <h6 class="mb-1">Signed Michael Jordan Rookie Card</h6>
                                <p class="text-muted small mb-1">Ended: Dec 24, 2023</p>
                                <span class="badge bg-success-soft text-success">Won for $8,250.00</span>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex flex-column">
                                    <small class="text-muted">Your Winning Bid</small>
                                    <span class="fw-bold">$8,250.00</span>
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <a href="../payments/checkout.php" class="btn btn-sm btn-primary w-100 mb-2">Pay Now</a>
                                <a href="#" class="btn btn-sm btn-outline-secondary w-100">Contact Seller</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Recently Ended (3)</h5>
                </div>
                <div class="list-group list-group-flush">
                    <!-- Lost Bid 1 -->
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <img src="../assets/images/auctions/6.jpg" alt="Product" class="img-fluid rounded">
                            </div>
                            <div class="col-md-5">
                                <h6 class="mb-1">Vintage Fender Stratocaster 1957</h6>
                                <p class="text-muted small mb-1">Ended: Dec 20, 2023</p>
                                <span class="badge bg-danger-soft text-danger">Outbid - Sold for $42,500.00</span>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex flex-column">
                                    <small class="text-muted">Your Max Bid</small>
                                    <span class="fw-bold">$40,000.00</span>
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <button class="btn btn-sm btn-outline-primary w-100">View Similar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <nav aria-label="Bid pagination">
                    <ul class="pagination justify-content-center">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
