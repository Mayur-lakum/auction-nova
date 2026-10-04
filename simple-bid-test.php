<?php
require_once 'includes/Database.php';

session_start();
$_SESSION['user_id'] = 1; // Force login

$db = Database::getInstance();
$conn = $db->getConnection();

// Create test auction if needed
$check = $conn->query("SELECT id FROM auctions WHERE status = 'active' LIMIT 1");
if (!$check->fetch()) {
    $conn->exec("INSERT INTO auctions (title, description, seller_id, category_id, starting_price, current_price, bid_increment, end_time, status) VALUES ('Test Item', 'Description', 1, 1, 100.00, 100.00, 10.00, DATE_ADD(NOW(), INTERVAL 2 HOUR), 'active')");
}

// Get auction
$auction = $conn->query("SELECT * FROM auctions WHERE status = 'active' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$auction_id = $auction['id'];

// Handle bid submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bid') {
    $bid_amount = (float)$_POST['bid_amount'];
    $min_bid = $auction['current_price'] + $auction['bid_increment'];
    
    if ($bid_amount >= $min_bid) {
        $conn->beginTransaction();
        
        // Insert bid
        $conn->prepare("INSERT INTO bids (auction_id, bidder_id, bid_amount, bid_time) VALUES (?, 1, ?, NOW())")
             ->execute([$auction_id, $bid_amount]);
        
        // Update auction
        $conn->prepare("UPDATE auctions SET current_price = ? WHERE id = ?")
             ->execute([$bid_amount, $auction_id]);
        
        $conn->commit();
        
        $success = "Bid placed: $" . number_format($bid_amount, 2);
        
        // Refresh auction data
        $stmt = $conn->prepare("SELECT * FROM auctions WHERE id = ?");
        $stmt->execute([$auction_id]);
        $auction = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = "Minimum bid: $" . number_format($min_bid, 2);
    }
}

// Get bids
$stmt = $conn->prepare("SELECT b.*, u.username FROM bids b LEFT JOIN users u ON b.bidder_id = u.id WHERE b.auction_id = ? ORDER BY b.bid_time DESC LIMIT 5");
$stmt->execute([$auction_id]);
$bids = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Bidding Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Simple Bidding Test</h2>
    
    <?php if ($auction): ?>
    <div class="card">
        <div class="card-body">
            <h4><?= htmlspecialchars($auction['title']) ?></h4>
            <p>Current Price: <span class="text-success fs-4">$<?= number_format($auction['current_price'], 2) ?></span></p>
            <p>Bid Increment: $<?= number_format($auction['bid_increment'], 2) ?></p>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="post">
                <input type="hidden" name="action" value="bid">
                <div class="mb-3">
                    <label>Bid Amount:</label>
                    <input type="number" name="bid_amount" step="<?= $auction['bid_increment'] ?>" 
                           min="<?= $auction['current_price'] + $auction['bid_increment'] ?>" 
                           class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Place Bid</button>
            </form>
        </div>
    </div>
    
    <div class="card mt-3">
        <div class="card-header">
            <h5>Recent Bids</h5>
        </div>
        <div class="card-body">
            <?php if ($bids): ?>
                <?php foreach ($bids as $bid): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?= htmlspecialchars($bid['username'] ?? 'User ' . $bid['bidder_id']) ?></span>
                        <span>$<?= number_format($bid['bid_amount'], 2) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">No bids yet</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php else: ?>
        <div class="alert alert-warning">No active auction found</div>
    <?php endif; ?>
</div>
</body>
</html>
