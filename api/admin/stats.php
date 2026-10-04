<?php
// Start session
session_start();

// Load configuration
require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/auth.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!is_logged_in() || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'], true)) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']);
    exit;
}

try {
    // Initialize database
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get comprehensive system statistics
    $stats = [];
    
    // Business Metrics
    // Revenue is derived from ended auctions' highest bid per auction (no payment tables in schema).
    $revenue_query = "SELECT COALESCE(SUM(t.max_bid), 0) AS total_revenue
                      FROM (
                          SELECT auction_id, MAX(bid_amount) AS max_bid
                          FROM bids
                          GROUP BY auction_id
                      ) t
                      JOIN auctions a ON a.id = t.auction_id
                      WHERE a.status = 'ended'";
    $revenue_stmt = $pdo->prepare($revenue_query);
    $revenue_stmt->execute();
    $revenue_result = $revenue_stmt->fetch(PDO::FETCH_ASSOC);
    $total_revenue_raw = (float)($revenue_result['total_revenue'] ?? 0);
    $stats['total_revenue'] = number_format($total_revenue_raw, 2, '.', '');
    
    // Commission (configurable later via settings; default 10%)
    $commission_rate = 0.10;
    $stats['platform_commission'] = number_format($total_revenue_raw * $commission_rate, 2, '.', '');
    
    // Payouts/Refunds/Escrow require payment tables (not present). Provide safe placeholders.
    $stats['pending_payouts'] = 0;
    $stats['refunds_issued'] = 0;
    $stats['escrow_balance'] = number_format(0, 2, '.', '');
    
    // Auction Metrics
    $active_query = "SELECT COUNT(*) as active_count FROM auctions WHERE status = 'active' AND end_time > NOW()";
    $active_stmt = $pdo->prepare($active_query);
    $active_stmt->execute();
    $active_result = $active_stmt->fetch(PDO::FETCH_ASSOC);
    $stats['active_auctions'] = $active_result['active_count'];
    
    $ending_today_query = "SELECT COUNT(*) as ending_count FROM auctions 
                          WHERE DATE(end_time) = CURDATE() AND status = 'active'";
    $ending_stmt = $pdo->prepare($ending_today_query);
    $ending_stmt->execute();
    $ending_result = $ending_stmt->fetch(PDO::FETCH_ASSOC);
    $stats['ending_today'] = $ending_result['ending_count'];
    
    // No flagged column in current schema
    $stats['flagged_auctions'] = 0;
    
    $cancelled_query = "SELECT COUNT(*) as cancelled_count FROM auctions WHERE status = 'cancelled'";
    $cancelled_stmt = $pdo->prepare($cancelled_query);
    $cancelled_stmt->execute();
    $cancelled_result = $cancelled_stmt->fetch(PDO::FETCH_ASSOC);
    $stats['cancelled_auctions'] = $cancelled_result['cancelled_count'];
    
    // User Metrics
    $users_query = "SELECT COUNT(*) as total_users FROM users";
    $users_stmt = $pdo->prepare($users_query);
    $users_stmt->execute();
    $users_result = $users_stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_users'] = $users_result['total_users'];
    
    $new_today_query = "SELECT COUNT(*) as new_today FROM users WHERE DATE(created_at) = CURDATE()";
    $new_today_stmt = $pdo->prepare($new_today_query);
    $new_today_stmt->execute();
    $new_today_result = $new_today_stmt->fetch(PDO::FETCH_ASSOC);
    $stats['new_users_today'] = $new_today_result['new_today'];
    
    // Sellers pending verification (works even if role doesn't include seller; returns 0)
    $pending_verification_query = "SELECT COUNT(*) as pending_count FROM users WHERE role = 'seller' AND is_verified = FALSE AND is_active = TRUE";
    $pending_verification_stmt = $pdo->prepare($pending_verification_query);
    $pending_verification_stmt->execute();
    $pending_verification_result = $pending_verification_stmt->fetch(PDO::FETCH_ASSOC);
    $stats['pending_verification'] = $pending_verification_result['pending_count'];
    
    $suspended_query = "SELECT COUNT(*) as suspended_count FROM users WHERE is_active = FALSE";
    $suspended_stmt = $pdo->prepare($suspended_query);
    $suspended_stmt->execute();
    $suspended_result = $suspended_stmt->fetch(PDO::FETCH_ASSOC);
    $stats['suspended_users'] = $suspended_result['suspended_count'];
    
    // Risk Indicators (placeholders - would come from actual monitoring systems)
    $stats['failed_payments'] = 0;
    $stats['open_disputes'] = 0;
    $stats['fraud_flags'] = 0;
    $stats['manual_reviews'] = 0;
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    error_log('Error loading admin stats: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error loading statistics']);
}
?>
