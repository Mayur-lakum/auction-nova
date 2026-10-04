<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Verify user is a seller
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'seller') {
    header('Location: ../index.php');
    exit();
}

$page_title = 'Seller Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Seller Tools</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="listings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-list me-2"></i> My Listings
                    </a>
                    <a href="create-listing.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus-circle me-2"></i> Create Listing
                    </a>
                    <a href="orders.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-shopping-cart me-2"></i> Orders
                    </a>
                    <a href="earnings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-wallet me-2"></i> Earnings
                    </a>
                    <a href="reviews.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-star me-2"></i> Reviews
                    </a>
                    <a href="settings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog me-2"></i> Seller Settings
                    </a>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="position-relative mb-3">
                        <img src="../assets/images/default-avatar.png" alt="Seller" class="rounded-circle" width="80">
                    </div>
                    <h5 class="mb-1"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h5>
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <div class="text-warning me-1">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="far fa-star"></i>
                        </div>
                        <small class="text-muted">(24 reviews)</small>
                    </div>
                    <span class="badge bg-success">Top Rated Seller</span>
                    <div class="mt-3">
                        <a href="#" class="btn btn-sm btn-outline-primary w-100 mb-2">
                            <i class="fas fa-store me-1"></i> View Store
                        </a>
                        <a href="#" class="btn btn-sm btn-outline-secondary w-100">
                            <i class="fas fa-pencil-alt me-1"></i> Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 col-6 mb-3">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase text-white-50 small mb-1">Total Sales</h6>
                                    <h3 class="mb-0">$24,850</h3>
                                </div>
                                <div class="icon-shape bg-white-10 rounded-circle p-3">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <span class="text-white-50 small"><i class="fas fa-arrow-up"></i> 12.5% from last month</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase text-white-50 small mb-1">Active Listings</h6>
                                    <h3 class="mb-0">18</h3>
                                </div>
                                <div class="icon-shape bg-white-10 rounded-circle p-3">
                                    <i class="fas fa-tag"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <a href="#" class="text-white-50 small">View all listings <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase text-white-50 small mb-1">Pending Orders</h6>
                                    <h3 class="mb-0">5</h3>
                                </div>
                                <div class="icon-shape bg-white-10 rounded-circle p-3">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <a href="#" class="text-white-50 small">Process now <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase text-white-50 small mb-1">Messages</h6>
                                    <h3 class="mb-0">3</h3>
                                </div>
                                <div class="icon-shape bg-white-10 rounded-circle p-3">
                                    <i class="fas fa-envelope"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <a href="#" class="text-white-50 small">View messages <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Activity</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="activityFilter" data-bs-toggle="dropdown" aria-expanded="false">
                            Last 7 days
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="activityFilter">
                            <li><a class="dropdown-item active" href="#">Last 7 days</a></li>
                            <li><a class="dropdown-item" href="#">Last 30 days</a></li>
                            <li><a class="dropdown-item" href="#">This month</a></li>
                            <li><a class="dropdown-item" href="#">Custom range</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar avatar-sm bg-success-soft text-success rounded-circle">
                                        <i class="fas fa-dollar-sign"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-0">New Sale</h6>
                                        <small class="text-muted">2 hours ago</small>
                                    </div>
                                    <p class="mb-0 small">Your item "Vintage Camera" was purchased by <a href="#">@photolover92</a> for $245.00</p>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar avatar-sm bg-primary-soft text-primary rounded-circle">
                                        <i class="fas fa-gavel"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-0">New Bid</h6>
                                        <small class="text-muted">5 hours ago</small>
                                    </div>
                                    <p class="mb-0 small">@collector123 placed a bid of $1,250.00 on your "Antique Pocket Watch"</p>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar avatar-sm bg-warning-soft text-warning rounded-circle">
                                        <i class="fas fa-exclamation-circle"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-0">Auction Ending Soon</h6>
                                        <small class="text-muted">1 day ago</small>
                                    </div>
                                    <p class="mb-0 small">Your auction "Rare Coin Collection" ends in 2 hours</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white text-center">
                        <a href="#" class="btn btn-sm btn-link">View All Activity</a>
                    </div>
                </div>
            </div>
            
            <!-- Recent Listings -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Listings</h5>
                    <a href="create-listing.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i> Add New
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>Price</th>
                                <th>Bids</th>
                                <th>Ends In</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="../assets/images/auctions/7.jpg" alt="Product" class="rounded me-2" width="40">
                                        <div>
                                            <h6 class="mb-0">Vintage Leather Jacket</h6>
                                            <small class="text-muted">#AN-2023-001</small>
                                        </div>
                                    </div>
                                </td>
                                <td>$125.00</td>
                                <td>8</td>
                                <td>2d 4h</td>
                                <td><span class="badge bg-success">Active</span></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-h"></i>
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="actionsDropdown">
                                            <li><a class="dropdown-item" href="#"><i class="fas fa-eye me-2"></i>View</a></li>
                                            <li><a class="dropdown-item" href="#"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                            <li><a class="dropdown-item" href="#"><i class="fas fa-chart-line me-2"></i>Stats</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-trash-alt me-2"></i>Delete</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <!-- More listing rows... -->
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white">
                    <nav aria-label="Listings pagination">
                        <ul class="pagination justify-content-center mb-0">
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
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<style>
.avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
}

.icon-shape {
    width: 50px;
    height: 50px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.bg-primary-soft {
    background-color: rgba(58, 134, 255, 0.1) !important;
}

.bg-success-soft {
    background-color: rgba(6, 214, 160, 0.1) !important;
}

.bg-warning-soft {
    background-color: rgba(255, 190, 11, 0.1) !important;
}

.bg-info-soft {
    background-color: rgba(23, 162, 184, 0.1) !important;
}

.text-primary-soft {
    color: rgba(58, 134, 255, 0.8) !important;
}

.text-success-soft {
    color: rgba(6, 214, 160, 0.8) !important;
}

.text-warning-soft {
    color: rgba(255, 190, 11, 0.8) !important;
}

.text-info-soft {
    color: rgba(23, 162, 184, 0.8) !important;
}

.bg-white-10 {
    background-color: rgba(255, 255, 255, 0.1) !important;
}

.text-white-50 {
    color: rgba(255, 255, 255, 0.8) !important;
}
</style>
