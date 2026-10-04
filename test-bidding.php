<?php
require_once 'includes/functions.php';

// Simple bidding test page
$page_title = 'Test Bidding';

// Check if user is logged in
if (!is_logged_in()) {
    echo "<h3>Please login first</h3>";
    echo "<a href='auth/login.php'>Login</a>";
    exit;
}

// Get a sample auction for testing
$db = new Database();
$conn = $db->connect();

$query = "SELECT * FROM auctions WHERE status = 'active' AND end_time > NOW() LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->execute();
$auction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$auction) {
    echo "<h3>No active auctions found for testing</h3>";
    exit;
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Bidding Test</h4>
                </div>
                <div class="card-body">
                    <h5>Auction: <?php echo htmlspecialchars($auction['title']); ?></h5>
                    <p><strong>Current Price:</strong> ₹<?php echo number_format($auction['current_price'], 2); ?></p>
                    <p><strong>Min Increment:</strong> ₹<?php echo number_format($auction['min_increment'], 2); ?></p>
                    <p><strong>Min Bid:</strong> ₹<?php echo number_format($auction['current_price'] + $auction['min_increment'], 2); ?></p>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <label for="test-bid-amount" class="form-label">Your Bid Amount</label>
                        <input type="number" class="form-control" id="test-bid-amount" 
                               min="<?php echo $auction['current_price'] + $auction['min_increment']; ?>"
                               step="<?php echo $auction['min_increment']; ?>"
                               value="<?php echo $auction['current_price'] + $auction['min_increment']; ?>">
                    </div>
                    
                    <button type="button" class="btn btn-primary" onclick="testBid(<?php echo $auction['id']; ?>)">
                        Test Place Bid
                    </button>
                    
                    <div id="test-result" class="mt-3"></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Debug Info</h4>
                </div>
                <div class="card-body">
                    <p><strong>User ID:</strong> <?php echo $_SESSION['user_id']; ?></p>
                    <p><strong>Username:</strong> <?php echo $_SESSION['username']; ?></p>
                    <p><strong>Auction ID:</strong> <?php echo $auction['id']; ?></p>
                    <p><strong>Seller ID:</strong> <?php echo $auction['seller_id']; ?></p>
                    <p><strong>CSRF Token:</strong> <?php echo generate_csrf_token(); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testBid(auctionId) {
    console.log('Testing bid for auction:', auctionId);
    
    const bidInput = document.getElementById('test-bid-amount');
    const bidAmount = parseFloat(bidInput.value);
    const resultDiv = document.getElementById('test-result');
    
    console.log('Bid amount:', bidAmount);
    
    if (!bidAmount || isNaN(bidAmount)) {
        resultDiv.innerHTML = '<div class="alert alert-danger">Please enter a valid bid amount</div>';
        return;
    }
    
    resultDiv.innerHTML = '<div class="alert alert-info">Placing bid...</div>';
    
    const formData = new FormData();
    formData.append('auction_id', auctionId);
    formData.append('bid_amount', bidAmount);
    formData.append('csrf_token', '<?php echo generate_csrf_token(); ?>');
    
    console.log('Sending request to:', '<?php echo SITE_URL; ?>api/place-bid.php');
    
    fetch('<?php echo SITE_URL; ?>api/place-bid.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.text(); // Get as text first to see raw response
    })
    .then(text => {
        console.log('Raw response:', text);
        try {
            const data = JSON.parse(text);
            console.log('Parsed data:', data);
            
            if (data.success) {
                resultDiv.innerHTML = `<div class="alert alert-success">
                    <strong>Success!</strong> ${data.message}<br>
                    New price: ₹${data.new_price}
                </div>`;
            } else {
                resultDiv.innerHTML = `<div class="alert alert-danger">
                    <strong>Error:</strong> ${data.message}
                </div>`;
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            resultDiv.innerHTML = `<div class="alert alert-danger">
                <strong>Parse Error:</strong> ${e.message}<br>
                <strong>Raw response:</strong> ${text}
            </div>`;
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        resultDiv.innerHTML = `<div class="alert alert-danger">
            <strong>Network Error:</strong> ${error.message}
        </div>`;
    });
}
</script>

<?php include 'includes/footer.php'; ?>
