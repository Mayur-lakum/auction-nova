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

$page_title = 'Manage Auction';
$page_scripts = ['assets/js/admin-auction-management.js'];

$csrf = generate_csrf_token();

$auction_id = (int)($_GET['id'] ?? 0);
if ($auction_id <= 0) {
    header('Location: dashboard.php');
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

// Load auction + seller + category
$auction = $pdo->prepare("
    SELECT a.*, u.username AS seller_name, u.email AS seller_email, c.name AS category_name
    FROM auctions a
    JOIN users u ON u.id = a.seller_id
    JOIN categories c ON c.id = a.category_id
    WHERE a.id = ?
");
$auction->execute([$auction_id]);
$auction = $auction->fetch(PDO::FETCH_ASSOC);

if (!$auction) {
    header('Location: dashboard.php');
    exit;
}

// Load auction images
$auction_images = [];
try {
    $img_stmt = $pdo->prepare("SELECT image_path FROM auction_images WHERE auction_id = ? ORDER BY is_primary DESC, id ASC");
    $img_stmt->execute([$auction_id]);
    $auction_images = $img_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $auction_images = [];
}
$bids = $pdo->prepare("
    SELECT b.id, b.bid_amount, b.bid_time, b.is_winning,
           u.id AS user_id, u.username, u.first_name, u.last_name, u.email
    FROM bids b
    JOIN users u ON u.id = b.bidder_id
    WHERE b.auction_id = ?
    ORDER BY b.bid_amount DESC, b.bid_time ASC
    LIMIT 200
");
$bids->execute([$auction_id]);
$bids = $bids->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="fas fa-cog me-2"></i>Manage Auction</h2>
            <p class="text-muted mb-0">Full control for: <?php echo htmlspecialchars($auction['title']); ?></p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?php echo SITE_URL; ?>admin/dashboard.php">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Auction Image Card -->
            <?php if (!empty($auction_images) || !empty($auction['image'])): ?>
            <div class="card mb-4">
                <div class="card-header fw-semibold">Auction Image</div>
                <div class="card-body text-center">
                    <?php
                    $primary_image = !empty($auction_images) ? $auction_images[0] : ($auction['image'] ?? '');
                    $image_url = auction_image_url($primary_image);
                    ?>
                    <img src="<?php echo htmlspecialchars($image_url); ?>" 
                         alt="<?php echo htmlspecialchars($auction['title']); ?>" 
                         class="img-fluid rounded" style="max-height: 300px;">
                </div>
            </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header fw-semibold">Auction Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">Status</div>
                            <div class="fw-semibold" id="auctionStatus"><?php echo htmlspecialchars($auction['status']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">End Time</div>
                            <div class="fw-semibold" id="auctionEndTime"><?php echo date('M j, Y H:i', strtotime($auction['end_time'])); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Current Price</div>
                            <div class="fw-semibold"><?php echo format_currency($auction['current_price']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Starting Price</div>
                            <div class="fw-semibold"><?php echo format_currency($auction['starting_price']); ?></div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">Description</div>
                            <div><?php echo nl2br(htmlspecialchars($auction['description'] ?? '')); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="fw-semibold">Bids (<?php echo count($bids); ?>)</div>
                    <div class="small text-muted">Top 200</div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($bids)): ?>
                        <div class="text-center py-5 text-muted">No bids yet.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Bidder</th>
                                        <th class="text-end">Amount</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bids as $idx => $b): ?>
                                    <tr class="<?php echo $b['is_winning'] ? 'table-success' : ''; ?>">
                                        <td><?php echo $idx + 1; ?></td>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars(($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? '')); ?></div>
                                            <div class="small text-muted">@<?php echo htmlspecialchars($b['username'] ?? ''); ?> · <?php echo htmlspecialchars($b['email'] ?? ''); ?></div>
                                        </td>
                                        <td class="text-end fw-semibold"><?php echo format_currency($b['bid_amount']); ?></td>
                                        <td><?php echo date('M j, Y H:i:s', strtotime($b['bid_time'])); ?></td>
                                        <td><?php echo $b['is_winning'] ? '<span class="badge bg-success">Winning</span>' : '<span class="badge bg-secondary">Outbid</span>'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header fw-semibold">Admin Actions</div>
                <div class="card-body">
                    <input type="hidden" id="actionAuctionId" value="<?php echo $auction_id; ?>">
                    <div class="d-grid gap-2">
                        <button class="btn btn-success" data-admin-action="sell" <?php echo ($auction['status'] ?? '') === 'active' ? '' : 'disabled'; ?>>
                            <i class="fas fa-trophy me-2"></i>Sell to Highest Bidder
                        </button>
                        <button class="btn btn-outline-danger" data-admin-action="end">
                            <i class="fas fa-stop me-2"></i>End Auction Now
                        </button>
                        <button class="btn btn-outline-warning" data-admin-action="extend">
                            <i class="fas fa-clock me-2"></i>Extend Auction
                        </button>
                        <button class="btn btn-outline-danger" data-admin-action="delete">
                            <i class="fas fa-trash me-2"></i>Delete Auction
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header fw-semibold">Send Auction Notification</div>
                <div class="card-body">
                    <form id="notifyForm">
                        <input type="hidden" id="notifyAuctionId" value="<?php echo $auction_id; ?>">
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

<script>
window.ADMIN_CSRF_TOKEN = <?php echo json_encode($csrf); ?>;
</script>

<?php require_once '../includes/footer.php'; ?>
