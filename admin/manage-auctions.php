<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/functions.php';

// Use the same admin authentication as dashboard.php
$SINGLE_ADMIN_EMAIL = 'admin@auctionnova.com';
if (!isset($_SESSION['admin_email']) || $_SESSION['admin_email'] !== $SINGLE_ADMIN_EMAIL) {
    header('Location: login.php');
    exit();
}

$page_title = 'Auction Management';
$page_scripts = ['assets/js/admin-auction-management.js'];

$db = Database::getInstance();
$conn = $db->getConnection();
$categories = [];
try {
    $categories = $conn->query("SELECT id, name FROM categories WHERE is_active = TRUE ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}

$csrf = generate_csrf_token();

require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="fas fa-gavel me-2"></i>Auction Management</h2>
            <p class="text-muted mb-0">Manage auctions, review bids, take actions, and send auction notifications</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" id="btnRefresh">
                <i class="fas fa-rotate me-2"></i>Refresh
            </button>
            <a class="btn btn-outline-primary" href="<?php echo SITE_URL; ?>admin/dashboard.php">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" id="filterSearch" placeholder="Search title or description">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="filterStatus">
                        <option value="all">All</option>
                        <option value="active">Active</option>
                        <option value="draft">Draft</option>
                        <option value="ended">Ended</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select class="form-select" id="filterCategory">
                        <option value="all">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <button class="btn btn-primary w-100" id="btnApplyFilters">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="fw-semibold">All Auctions</div>
            <div class="small text-muted">Live updates enabled</div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="adminAuctionsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Auction</th>
                            <th>Seller</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Activity</th>
                            <th>End</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="adminAuctionsTbody">
                        <tr><td colspan="7" class="text-center py-4 text-muted">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="auctionDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="detailsTitle">Auction</h5>
                    <div class="text-muted small" id="detailsMeta"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="notifyAuctionId" value="">
                <input type="hidden" id="actionAuctionId" value="">

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div id="detailsOverview"></div>
                        <div class="mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="fw-semibold">Bids</div>
                                <div class="small text-muted">Top 200</div>
                            </div>
                            <div id="detailsBids"></div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-header fw-semibold">Admin Actions</div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-danger" data-admin-action="end">
                                        <i class="fas fa-stop me-2"></i>End Auction Now
                                    </button>
                                </div>
                                <div class="small text-muted mt-2">Use “Extend” from the table for time changes.</div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header fw-semibold">Send Auction Notification</div>
                            <div class="card-body">
                                <form id="notifyForm">
                                    <div class="mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" class="form-control" id="notifyTitle" placeholder="e.g. Auction Update">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Message</label>
                                        <textarea class="form-control" id="notifyMessage" rows="4" placeholder="Write a message for seller + all bidders"></textarea>
                                    </div>
                                    <div class="mb-4">
                                    <button type="submit" class="btn btn-primary w-100" style="display: block !important; visibility: visible !important; min-height: 44px; font-size: 14px; font-weight: 600;">
                                        <i class="fas fa-paper-plane me-2"></i>Send Notification
                                    </button>
                                </div>
                                    <div class="small text-muted mt-2">Sent to seller + all unique bidders.</div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.ADMIN_CSRF_TOKEN = <?php echo json_encode($csrf); ?>;
</script>

<?php require_once '../includes/footer.php'; ?>
