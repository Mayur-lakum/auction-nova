<?php
require_once 'includes/Database.php';

session_start();
$_SESSION['user_id'] = 1; // Force login

$db = Database::getInstance();
$conn = $db->getConnection();

// Get auction
$auction = $conn->query("SELECT * FROM auctions WHERE status = 'active' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$auction) {
    die("No active auction found");
}

$current_price = $auction['current_price'];
$bid_increment = $auction['bid_increment'] ?? 10;
$auction_id = $auction['id'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Auction Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Debug: <?= htmlspecialchars($auction['title']) ?></h2>
    
    <div class="card">
        <div class="card-body">
            <h4>Current Price: <span class="text-success">$<?= number_format($current_price, 2) ?></span></h4>
            <p>Bid Increment: $<?= number_format($bid_increment, 2) ?></p>
            
            <!-- Quick Bid Buttons -->
            <div class="mb-3">
                <p class="small text-muted mb-2">Quick Bids:</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-success quick-bid-btn" 
                            data-bid-amount="<?= $current_price + $bid_increment ?>">
                        +$<?= number_format($bid_increment, 2) ?> 
                        (<?= number_format($current_price + $bid_increment, 2) ?>)
                    </button>
                </div>
            </div>
            
            <!-- Custom Bid Input -->
            <div class="mb-3">
                <p class="small text-muted mb-2">Custom Amount:</p>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" 
                           class="form-control" 
                           id="customBidAmount" 
                           min="<?= $current_price + $bid_increment ?>"
                           step="<?= $bid_increment ?>"
                           placeholder="Enter amount"
                           value="<?= $current_price + $bid_increment ?>">
                    <button class="btn btn-primary" type="button" id="placeCustomBid">Place Bid</button>
                </div>
            </div>
            
            <div id="bidMessage" class="alert" style="display: none;"></div>
        </div>
    </div>
</div>

<script>
const auctionId = <?= $auction_id ?>;
let currentPrice = <?= $current_price ?>;
const bidIncrement = <?= $bid_increment ?>;

function showMessage(message, type) {
    const el = document.getElementById('bidMessage');
    if (!el) return;
    el.className = 'alert alert-' + (type || 'info');
    el.textContent = message;
    el.style.display = 'block';
}

// Quick bid buttons
document.querySelectorAll('.quick-bid-btn').forEach(function(button) {
    button.addEventListener('click', function() {
        const bidAmount = parseFloat(this.dataset.bidAmount);
        console.log('Quick bid clicked:', bidAmount);
        placeBid(bidAmount);
    });
});

// Custom bid button
const customBidBtn = document.getElementById('placeCustomBid');
console.log('Custom button found:', !!customBidBtn);
if (customBidBtn) {
    customBidBtn.addEventListener('click', function() {
        const bidAmount = parseFloat(document.getElementById('customBidAmount').value);
        console.log('Custom bid clicked:', bidAmount);
        if (bidAmount && bidAmount >= (currentPrice + bidIncrement)) {
            placeBid(bidAmount);
        } else {
            showMessage('Minimum bid is $' + (currentPrice + bidIncrement).toFixed(2), 'danger');
        }
    });
} else {
    console.error('Custom bid button not found!');
}

function placeBid(amount) {
    console.log('Placing bid:', amount);
    showMessage('Placing bid...', 'info');
    
    fetch('api/place-bid.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            auction_id: auctionId,
            bid_amount: amount
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Response text:', text);
        try {
            const data = JSON.parse(text);
            console.log('Parsed data:', data);
            if (data.success) {
                showMessage('Bid placed successfully!', 'success');
                currentPrice = data.new_price;
                setTimeout(() => location.reload(), 1000); // Refresh to see changes
            } else {
                showMessage('Error: ' + (data.message || 'Unknown error'), 'danger');
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            showMessage('Invalid server response', 'danger');
        }
    })
    .catch(error => {
        showMessage('Network error', 'danger');
        console.error('Fetch error:', error);
    });
}
</script>
</body>
</html>
