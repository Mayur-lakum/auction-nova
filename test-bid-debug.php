<!DOCTYPE html>
<html>
<head>
    <title>Bid Debug Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Bid Debug Test</h2>
    
    <div class="card">
        <div class="card-body">
            <h4>Test Auction</h4>
            <p>Current Price: <span id="currentPrice" class="text-success">$100.00</span></p>
            <p>Bid Increment: $10.00</p>
            
            <!-- Test Buttons -->
            <div class="mb-3">
                <p class="small text-muted mb-2">Quick Bids:</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-success quick-bid-btn" data-bid-amount="110">
                        +$10 (110)
                    </button>
                    <button class="btn btn-success quick-bid-btn" data-bid-amount="120">
                        +$20 (120)
                    </button>
                </div>
            </div>
            
            <!-- Custom Bid -->
            <div class="mb-3">
                <p class="small text-muted mb-2">Custom Amount:</p>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control" id="customBidAmount" min="110" step="10" placeholder="Enter amount">
                    <button class="btn btn-primary" type="button" id="placeCustomBid">Place Bid</button>
                </div>
            </div>
            
            <div id="bidMessage" class="alert" style="display: none;"></div>
            <div id="debugInfo" class="alert alert-info" style="display: none;"></div>
        </div>
    </div>
</div>

<script>
const auctionId = 1;
let currentPrice = 100;
const bidIncrement = 10;

function showMessage(message, type) {
    const el = document.getElementById('bidMessage');
    el.className = 'alert alert-' + (type || 'info');
    el.textContent = message;
    el.style.display = 'block';
}

function showDebug(message) {
    const el = document.getElementById('debugInfo');
    el.textContent = message;
    el.style.display = 'block';
}

// Quick bid buttons
document.querySelectorAll('.quick-bid-btn').forEach(function(button) {
    console.log('Found quick bid button:', button);
    button.addEventListener('click', function() {
        const bidAmount = parseFloat(this.dataset.bidAmount);
        console.log('Quick bid clicked:', bidAmount);
        showDebug('Quick bid clicked: $' + bidAmount);
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
        showDebug('Custom bid clicked: $' + bidAmount);
        if (bidAmount && bidAmount >= (currentPrice + bidIncrement)) {
            placeBid(bidAmount);
        } else {
            showMessage('Minimum bid is $' + (currentPrice + bidIncrement).toFixed(2), 'danger');
        }
    });
} else {
    console.error('Custom bid button not found!');
    showDebug('ERROR: Custom bid button not found!');
}

function placeBid(amount) {
    console.log('Placing bid:', amount);
    showDebug('Placing bid: $' + amount);
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
        showDebug('Response: ' + text);
        try {
            const data = JSON.parse(text);
            console.log('Parsed data:', data);
            if (data.success) {
                showMessage('Bid placed successfully!', 'success');
                currentPrice = data.new_price;
                document.getElementById('currentPrice').textContent = '$' + currentPrice.toFixed(2);
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
        showDebug('Network error: ' + error.message);
    });
}

// Initial debug info
showDebug('Page loaded. Buttons should be working.');
</script>
</body>
</html>
