<?php
require_once '../includes/functions.php';
require_login();

$page_title = 'Notifications';
$db = new Database();
$conn = $db->connect();

$user_id = $_SESSION['user_id'];
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Mark notification as read if requested
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    mark_notification_read($_GET['mark_read']);
    redirect('notifications.php');
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM notifications WHERE user_id = ?";
$count_stmt = $conn->prepare($count_query);
$count_stmt->execute([$user_id]);
$total_notifications = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_notifications / $per_page);

// Get notifications
$notifications_query = "SELECT * FROM notifications 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC 
                        LIMIT $per_page OFFSET $offset";
$notifications_stmt = $conn->prepare($notifications_query);
$notifications_stmt->execute([$user_id]);
$notifications = $notifications_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread count
$unread_query = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = FALSE";
$unread_stmt = $conn->prepare($unread_query);
$unread_stmt->execute([$user_id]);
$unread_count = $unread_stmt->fetch(PDO::FETCH_ASSOC)['unread'];

include '../includes/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="fas fa-bell me-2"></i>Notifications
            </h2>
            <p class="text-muted mb-0">
                You have <?php echo $unread_count; ?> unread notifications
            </p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>

    <?php if (empty($notifications)): ?>
    <!-- No Notifications -->
    <div class="text-center py-5">
        <i class="fas fa-bell-slash fa-4x text-muted mb-4"></i>
        <h3>No notifications yet</h3>
        <p class="text-muted mb-4">
            When you receive notifications about bids, auctions, and other activities, they'll appear here.
        </p>
        <a href="<?php echo SITE_URL; ?>auctions/" class="btn btn-primary">
            Browse Auctions
        </a>
    </div>
    <?php else: ?>

    <!-- Notifications List -->
    <div class="row">
        <div class="col-12">
            <?php foreach ($notifications as $notification): ?>
            <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?> mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-2">
                            <?php
                            $icon_class = '';
                            $badge_class = '';
                            switch ($notification['type']) {
                                case 'bid_placed':
                                    $icon_class = 'fas fa-gavel text-primary';
                                    $badge_class = 'bg-primary';
                                    break;
                                case 'bid_won':
                                    $icon_class = 'fas fa-trophy text-warning';
                                    $badge_class = 'bg-warning';
                                    break;
                                case 'bid_lost':
                                    $icon_class = 'fas fa-times-circle text-danger';
                                    $badge_class = 'bg-danger';
                                    break;
                                case 'auction_ending':
                                    $icon_class = 'fas fa-clock text-warning';
                                    $badge_class = 'bg-warning';
                                    break;
                                case 'auction_ended':
                                    $icon_class = 'fas fa-stop-circle text-secondary';
                                    $badge_class = 'bg-secondary';
                                    break;
                                default:
                                    $icon_class = 'fas fa-info-circle text-info';
                                    $badge_class = 'bg-info';
                            }
                            ?>
                            <i class="<?php echo $icon_class; ?> me-3"></i>
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                <span class="badge <?php echo $badge_class; ?> small">
                                    <?php echo ucfirst(str_replace('_', ' ', $notification['type'])); ?>
                                </span>
                            </div>
                        </div>
                        
                        <p class="mb-2"><?php echo htmlspecialchars($notification['message']); ?></p>
                        
                        <div class="d-flex align-items-center justify-content-between">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo time_ago($notification['created_at']); ?>
                            </small>
                            
                            <div class="d-flex gap-2">
                                <?php if ($notification['auction_id']): ?>
                                <a href="<?php echo SITE_URL; ?>auction-details.php?id=<?php echo $notification['auction_id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i>View Auction
                                </a>
                                <?php endif; ?>
                                
                                <?php if (!$notification['is_read']): ?>
                                <a href="?mark_read=<?php echo $notification['id']; ?>" 
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-check me-1"></i>Mark Read
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Notifications pagination" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page - 1; ?>">
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
                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>

    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
