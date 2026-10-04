<?php
// Admin Auction Management API (JSON)
session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/live_events.php';

header('Content-Type: application/json');

// Debug: log incoming request
error_log('admin-auction-management API: ' . $_SERVER['REQUEST_METHOD'] . ' ' . ($_GET['action'] ?? $_POST['action'] ?? 'none'));

// Accept admin panel session (admin_email) OR role-based admin
$isAdmin = (isset($_SESSION['admin_email']) && $_SESSION['admin_email'] === 'admin@auctionnova.com') || is_admin();
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            handle_list($pdo);
            break;
        case 'details':
            handle_details($pdo);
            break;
        case 'end':
            require_post_csrf();
            handle_end($pdo);
            break;
        case 'extend':
            require_post_csrf();
            handle_extend($pdo);
            break;
        case 'delete':
            require_post_csrf();
            handle_delete($pdo);
            break;
        case 'notify':
            require_post_csrf();
            handle_notify($pdo);
            break;
        case 'sell':
            require_post_csrf();
            handle_sell($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function require_post_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

function handle_list(PDO $pdo): void {
    $search = trim($_GET['search'] ?? '');
    $status = trim($_GET['status'] ?? 'all');
    $category = trim($_GET['category'] ?? 'all');

    $where = ['1=1'];
    $params = [];

    if ($search !== '') {
        $where[] = '(a.title LIKE ? OR a.description LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
    }

    if ($status !== 'all') {
        $where[] = 'a.status = ?';
        $params[] = $status;
    }

    if ($category !== 'all' && is_numeric($category)) {
        $where[] = 'a.category_id = ?';
        $params[] = (int)$category;
    }

    $whereClause = implode(' AND ', $where);

    $query = "SELECT a.id, a.title, a.seller_id, u.username AS seller_name, c.name AS category_name,
                     a.starting_price, a.current_price, a.end_time, a.status, a.is_featured, a.view_count, a.created_at,
                     a.image,
                     (SELECT image_path FROM auction_images WHERE auction_id = a.id AND is_primary = 1 LIMIT 1) as auction_image,
                     (SELECT COUNT(*) FROM bids b WHERE b.auction_id = a.id) AS bid_count,
                     (SELECT MAX(bid_amount) FROM bids b WHERE b.auction_id = a.id) AS highest_bid
              FROM auctions a
              JOIN users u ON u.id = a.seller_id
              JOIN categories c ON c.id = a.category_id
              WHERE {$whereClause}
              ORDER BY a.created_at DESC
              LIMIT 500";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'auctions' => $rows]);
}

function handle_details(PDO $pdo): void {
    $auctionId = (int)($_GET['auction_id'] ?? 0);
    if ($auctionId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid auction_id']);
        return;
    }

    $auctionQuery = "SELECT a.*, u.username AS seller_name, u.email AS seller_email, c.name AS category_name
                    FROM auctions a
                    JOIN users u ON u.id = a.seller_id
                    JOIN categories c ON c.id = a.category_id
                    WHERE a.id = ?";
    $auctionStmt = $pdo->prepare($auctionQuery);
    $auctionStmt->execute([$auctionId]);
    $auction = $auctionStmt->fetch(PDO::FETCH_ASSOC);

    if (!$auction) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Auction not found']);
        return;
    }

    $bidsQuery = "SELECT b.id, b.bid_amount, b.bid_time, b.is_winning,
                         u.id AS user_id, u.username, u.first_name, u.last_name, u.email
                  FROM bids b
                  JOIN users u ON u.id = b.bidder_id
                  WHERE b.auction_id = ?
                  ORDER BY b.bid_amount DESC, b.bid_time ASC
                  LIMIT 200";
    $bidsStmt = $pdo->prepare($bidsQuery);
    $bidsStmt->execute([$auctionId]);
    $bids = $bidsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Add full image URLs to auction data
    $auction['image_url'] = auction_image_url($auction['image'] ?? '');
    $auction['thumbnail_url'] = $auction['image_url'];

    // Get all images for this auction
    $imgStmt = $pdo->prepare("SELECT id, image_path, is_primary FROM auction_images WHERE auction_id = ? ORDER BY is_primary DESC, id ASC");
    $imgStmt->execute([$auctionId]);
    $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add full URLs to images
    foreach ($images as &$img) {
        $img['image_url'] = auction_image_url($img['image_path']);
    }
    $auction['images'] = $images;
    
    echo json_encode(['success' => true, 'auction' => $auction, 'bids' => $bids]);
}

function handle_end(PDO $pdo): void {
    $auctionId = (int)($_POST['auction_id'] ?? 0);
    if ($auctionId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid auction_id']);
        return;
    }

    $pdo->beginTransaction();

    $pdo->prepare("UPDATE auctions SET status='ended', end_time = NOW() WHERE id = ?")->execute([$auctionId]);

    $pdo->prepare("UPDATE bids SET is_winning = 0 WHERE auction_id = ?")->execute([$auctionId]);
    $pdo->prepare("UPDATE bids SET is_winning = 1 WHERE id = (
                        SELECT id FROM (
                            SELECT id FROM bids WHERE auction_id = ? ORDER BY bid_amount DESC, bid_time ASC LIMIT 1
                        ) t
                    )")->execute([$auctionId]);

    $pdo->commit();

    log_admin_action($_SESSION['user_id'] ?? 0, 'end_auction', 'auction', $auctionId, 'Auction ended by admin');

    broadcast_event('auction_ended', ['auction_id' => $auctionId]);

    echo json_encode(['success' => true]);
}

function handle_extend(PDO $pdo): void {
    $auctionId = (int)($_POST['auction_id'] ?? 0);
    $minutes = (int)($_POST['minutes'] ?? 0);

    if ($auctionId <= 0 || $minutes <= 0 || $minutes > 240) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        return;
    }

    $pdo->prepare("UPDATE auctions SET end_time = DATE_ADD(end_time, INTERVAL ? MINUTE) WHERE id = ?")->execute([$minutes, $auctionId]);
    log_admin_action($_SESSION['user_id'] ?? 0, 'extend_auction', 'auction', $auctionId, "Extended by {$minutes} minutes");

    $newEnd = $pdo->prepare("SELECT end_time FROM auctions WHERE id = ?");
    $newEnd->execute([$auctionId]);
    $endTime = $newEnd->fetchColumn();

    broadcast_event('auction_extended', ['auction_id' => $auctionId, 'end_time' => $endTime, 'minutes' => $minutes]);

    echo json_encode(['success' => true, 'end_time' => $endTime]);
}

function handle_delete(PDO $pdo): void {
    $auctionId = (int)($_POST['auction_id'] ?? 0);
    if ($auctionId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid auction_id']);
        return;
    }

    // Hard delete (cascades bids/images/watchlist)
    $pdo->prepare("DELETE FROM auctions WHERE id = ?")->execute([$auctionId]);

    log_admin_action($_SESSION['user_id'] ?? 0, 'delete_auction', 'auction', $auctionId, 'Auction deleted by admin');

    broadcast_event('auction_deleted', ['auction_id' => $auctionId]);

    echo json_encode(['success' => true]);
}

function handle_notify(PDO $pdo): void {
    $auctionId = (int)($_POST['auction_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($auctionId <= 0 || $title === '' || $message === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        return;
    }

    // Send to seller + all unique bidders
    $recipients = [];

    $sellerStmt = $pdo->prepare("SELECT seller_id FROM auctions WHERE id = ?");
    $sellerStmt->execute([$auctionId]);
    $sellerId = (int)$sellerStmt->fetchColumn();
    if ($sellerId > 0) {
        $recipients[$sellerId] = true;
    }

    $biddersStmt = $pdo->prepare("SELECT DISTINCT bidder_id FROM bids WHERE auction_id = ?");
    $biddersStmt->execute([$auctionId]);
    foreach ($biddersStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $bidderId = (int)$row['bidder_id'];
        if ($bidderId > 0) {
            $recipients[$bidderId] = true;
        }
    }

    foreach (array_keys($recipients) as $userId) {
        // Use safe default notification type
        send_notification($userId, 'message_received', $title, $message, $auctionId);
    }

    log_admin_action($_SESSION['user_id'], 'notify_auction', 'auction', $auctionId, $title);

    // Also broadcast a realtime event so UIs can show toast instantly
    broadcast_event('auction_notification', [
        'auction_id' => $auctionId,
        'title' => $title,
        'message' => $message
    ]);

    echo json_encode(['success' => true, 'sent_to' => count($recipients)]);
}

function handle_sell(PDO $pdo): void {
    $auctionId = (int)($_POST['auction_id'] ?? 0);
    if ($auctionId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid auction_id']);
        return;
    }

    $pdo->beginTransaction();

    $auctionStmt = $pdo->prepare("SELECT id, title, seller_id, status, current_price FROM auctions WHERE id = ? FOR UPDATE");
    $auctionStmt->execute([$auctionId]);
    $auction = $auctionStmt->fetch(PDO::FETCH_ASSOC);
    if (!$auction) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Auction not found']);
        return;
    }

    if (($auction['status'] ?? '') !== 'active') {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Auction is not active']);
        return;
    }

    $winStmt = $pdo->prepare(
        "SELECT b.id, b.bid_amount, b.bid_time, b.bidder_id, u.username
         FROM bids b
         JOIN users u ON u.id = b.bidder_id
         WHERE b.auction_id = ?
         ORDER BY b.bid_amount DESC, b.bid_time ASC
         LIMIT 1"
    );
    $winStmt->execute([$auctionId]);
    $winningBid = $winStmt->fetch(PDO::FETCH_ASSOC);

    if (!$winningBid) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No bids available to sell this auction']);
        return;
    }

    $winningAmount = (float)$winningBid['bid_amount'];

    $pdo->prepare("UPDATE auctions SET status='ended', end_time = NOW(), current_price = ? WHERE id = ?")
        ->execute([$winningAmount, $auctionId]);

    $pdo->prepare("UPDATE bids SET is_winning = 0 WHERE auction_id = ?")
        ->execute([$auctionId]);
    $pdo->prepare("UPDATE bids SET is_winning = 1 WHERE id = ?")
        ->execute([(int)$winningBid['id']]);

    $pdo->commit();

    log_admin_action($_SESSION['user_id'] ?? 0, 'sell_auction', 'auction', $auctionId, 'Sold to highest bidder by admin');

    $sellerId = (int)$auction['seller_id'];
    $winnerId = (int)$winningBid['bidder_id'];
    $winnerUsername = $winningBid['username'] ?? ('User' . $winnerId);

    send_notification($winnerId, 'bid_won', 'You won the auction!',
        'You won "' . ($auction['title'] ?? 'Auction') . '" with a bid of ₹' . number_format($winningAmount, 2),
        $auctionId
    );

    $others = $pdo->prepare("SELECT DISTINCT bidder_id FROM bids WHERE auction_id = ? AND bidder_id <> ?");
    $others->execute([$auctionId, $winnerId]);
    foreach ($others->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $uid = (int)$row['bidder_id'];
        if ($uid > 0) {
            send_notification($uid, 'bid_lost', 'Auction ended',
                'Auction "' . ($auction['title'] ?? 'Auction') . '" was sold to ' . $winnerUsername . ' for ₹' . number_format($winningAmount, 2),
                $auctionId
            );
        }
    }

    if ($sellerId > 0) {
        send_notification($sellerId, 'auction_ended', 'Your auction was sold',
            'Your auction "' . ($auction['title'] ?? 'Auction') . '" was sold to ' . $winnerUsername . ' for ₹' . number_format($winningAmount, 2),
            $auctionId
        );
    }

    broadcast_event('auction_sold', [
        'auction_id' => $auctionId,
        'winner_user_id' => $winnerId,
        'winner_username' => $winnerUsername,
        'winning_bid' => $winningAmount,
        'bid_id' => (int)$winningBid['id'],
        'bid_time' => $winningBid['bid_time'] ?? null,
    ]);

    broadcast_event('auction_to_cart', [
        'auction_id' => $auctionId,
        'winner_user_id' => $winnerId,
        'winner_username' => $winnerUsername,
        'winning_bid' => $winningAmount
    ]);

    echo json_encode([
        'success' => true,
        'auction_id' => $auctionId,
        'winner_user_id' => $winnerId,
        'winner_username' => $winnerUsername,
        'winning_bid' => $winningAmount
    ]);
}
