<?php
require_once 'includes/functions.php';
require_once 'includes/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$auction_id = $_GET['id'] ?? 0;

if (!$auction_id || !is_numeric($auction_id)) {
    die("Error: No valid auction ID provided. Please use: " . SITE_URL . "auction-details.php?id=1");
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get auction details with primary image (check both image column and auction_images table)
$auction_query = "SELECT a.*, c.name as category_name, u.username as seller_name, u.id as seller_id,
                  (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count,
                  COALESCE(
                    (SELECT ai.image_path FROM auction_images ai WHERE ai.auction_id = a.id AND ai.is_primary = 1 LIMIT 1),
                    a.image
                  ) as image
                  FROM auctions a 
                  JOIN categories c ON a.category_id = c.id 
                  JOIN users u ON a.seller_id = u.id 
                  WHERE a.id = ?";
$auction_stmt = $conn->prepare($auction_query);
$auction_stmt->execute([$auction_id]);
$auction = $auction_stmt->fetch(PDO::FETCH_ASSOC);

if (!$auction) {
    die("Error: Auction not found. Auction ID: $auction_id. <a href='" . SITE_URL . "auction-hub.php'>View all auctions</a>");
}

// Get bid history
$bids_query = "SELECT b.*, u.username FROM bids b 
               JOIN users u ON b.bidder_id = u.id 
               WHERE b.auction_id = ? 
               ORDER BY b.bid_time DESC LIMIT 10";
$bids_stmt = $conn->prepare($bids_query);
$bids_stmt->execute([$auction_id]);
$bids = $bids_stmt->fetchAll(PDO::FETCH_ASSOC);

$current_user_id = $_SESSION['user_id'] ?? null;
$is_seller = $current_user_id && ((int)$current_user_id === (int)$auction['seller_id']);

$is_active = (($auction['status'] ?? '') === 'active') && (strtotime($auction['end_time'] ?? '') > time());
$can_bid = ($current_user_id && !$is_seller && $is_active);

$bid_increment = $auction['bid_increment'] ?? ($auction['min_increment'] ?? 10);

$status_text = $is_active ? 'LIVE' : strtoupper((string)($auction['status'] ?? 'ended'));
$status_badge_class = $is_active ? 'bg-success' : 'bg-secondary';

$page_title = htmlspecialchars($auction['title']);
include 'includes/header.php';
?>

<script>
var siteUrl = "<?php echo SITE_URL; ?>";
</script>
<style>
.auction-details-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem 0;
    margin-bottom: 2rem;
}

.bid-history {
    max-height: 400px;
    overflow-y: auto;
}

.quick-bid-btn {
    min-width: 120px;
}

.countdown-timer {
    font-size: 1.5rem;
    font-weight: bold;
    color: #dc3545;
}

.auction-image {
    max-height: 400px;
    object-fit: cover;
    border-radius: 8px;
}

.bid-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
}

.current-bid-display {
    font-size: 2rem;
    font-weight: bold;
    color: #28a745;
}
</style>

<div class="auction-details-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2"><?php echo htmlspecialchars($auction['title']); ?></h1>
                <p class="mb-0">
                    <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($auction['seller_name']); ?>
                    <span class="ms-3"><i class="fas fa-tag me-2"></i><?php echo htmlspecialchars($auction['category_name']); ?></span>
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="countdown-timer" data-end-time="<?php echo $auction['end_time']; ?>">
                    Loading...
                </div>
                <small>Auction Ends</small>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <!-- Auction Image and Details -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <?php 
                    // Debug output - remove after fixing
                    error_log("Auction ID: $auction_id, Image: " . ($auction['image'] ?? 'NULL') . ", Empty: " . (empty($auction['image']) ? 'YES' : 'NO'));
                    
                    if (!empty($auction['image'])): 
                    ?>
                        <img src="<?php echo SITE_URL; ?>assets/uploads/<?php echo htmlspecialchars($auction['image']); ?>" 
                             class="auction-image w-100 mb-3" alt="<?php echo htmlspecialchars($auction['title']); ?>">
                    <?php else: ?>
                        <img src="<?php echo SITE_URL; ?>assets/images/no-image.jpg" 
                             class="auction-image w-100 mb-3" alt="<?php echo htmlspecialchars($auction['title']); ?>">
                    <?php endif; ?>
                    
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($auction['description'])); ?></p>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h5>Auction Details</h5>
                            <ul class="list-unstyled">
                                <li><strong>Starting Price:</strong> $<?php echo number_format($auction['starting_price'], 2); ?></li>
                                <li><strong>Current Bid:</strong> <span class="text-success" id="currentBidPriceDetails">$<?php echo number_format($auction['current_price'], 2); ?></span></li>
                                <li><strong>Total Bids:</strong> <?php echo $auction['bid_count']; ?></li>
                                <li><strong>Bid Increment:</strong> $<?php echo number_format($bid_increment, 2); ?></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>Timing</h5>
                            <ul class="list-unstyled">
                                <li><strong>Started:</strong> <?php echo date('M j, Y g:i A', strtotime($auction['created_at'])); ?></li>
                                <li><strong>Ends:</strong> <?php echo date('M j, Y g:i A', strtotime($auction['end_time'])); ?></li>
                                <li><strong>Status:</strong> <span class="badge <?php echo $status_badge_class; ?>"><?php echo htmlspecialchars($status_text); ?></span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bid History -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Bids</h5>
                </div>
                <div class="card-body">
                    <div class="bid-history">
                        <?php if (empty($bids)): ?>
                            <p class="text-muted text-center">No bids yet. Be the first!</p>
                        <?php else: ?>
                            <?php foreach ($bids as $bid): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong><?php echo htmlspecialchars($bid['username']); ?></strong>
                                        <small class="text-muted d-block">
                                            <?php echo date('M j, g:i A', strtotime($bid['bid_time'])); ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <strong>$<?php echo number_format($bid['bid_amount'], 2); ?></strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bidding Section -->
        <div class="col-lg-4">
            <div class="bid-section mb-4">
                <h4 class="mb-3">Place Your Bid</h4>
                <?php if (!$current_user_id): ?>
                    <div class="alert alert-warning">
                        Login required to place a bid.
                        <a href="<?php echo SITE_URL; ?>auth/login.php" class="alert-link">Login</a>
                    </div>
                <?php elseif ($is_seller): ?>
                    <div class="alert alert-warning">
                        You cannot bid on your own auction.
                    </div>
                <?php endif; ?>
                
                <div class="text-center mb-4">
                    <div class="current-bid-display" id="currentBidPrice">
                        $<?php echo number_format($auction['current_price'], 2); ?>
                    </div>
                    <small class="text-muted">Current Bid</small>
                </div>
                
                <!-- Quick Bid Buttons -->
                <div class="mb-3">
                    <p class="small text-muted mb-2">Quick Bids:</p>
                    <div class="d-grid gap-2">
                        <?php 
                        $current_price = $auction['current_price'];
                        ?>
                        <button class="btn btn-success quick-bid-btn" 
                                data-multiplier="1"
                                data-bid-amount="<?php echo $current_price + $bid_increment; ?>" <?php echo $can_bid ? '' : 'disabled'; ?>>
                            +$<?php echo number_format($bid_increment, 2); ?> 
                            (<?php echo number_format($current_price + $bid_increment, 2); ?>)
                        </button>
                        <button class="btn btn-success quick-bid-btn" 
                                data-multiplier="2"
                                data-bid-amount="<?php echo $current_price + ($bid_increment * 2); ?>" <?php echo $can_bid ? '' : 'disabled'; ?>>
                            +$<?php echo number_format($bid_increment * 2, 2); ?> 
                            (<?php echo number_format($current_price + ($bid_increment * 2), 2); ?>)
                        </button>
                        <button class="btn btn-success quick-bid-btn" 
                                data-multiplier="5"
                                data-bid-amount="<?php echo $current_price + ($bid_increment * 5); ?>" <?php echo $can_bid ? '' : 'disabled'; ?>>
                            +$<?php echo number_format($bid_increment * 5, 2); ?> 
                            (<?php echo number_format($current_price + ($bid_increment * 5), 2); ?>)
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
                               min="<?php echo $current_price + $bid_increment; ?>"
                               step="<?php echo $bid_increment; ?>"
                               placeholder="Enter amount" <?php echo $can_bid ? '' : 'disabled'; ?>>
                        <button class="btn btn-primary" type="button" id="placeCustomBid" 
                                style="min-width: 100px; z-index: 1; background-color: #007bff !important; border-color: #007bff !important; color: white !important;" <?php echo $can_bid ? '' : 'disabled'; ?>>
                            Place Bid
                        </button>
                    </div>
                </div>
                
                <div id="bidMessage" class="alert" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<script>
// Countdown Timer
function updateCountdown() {
    const elements = document.querySelectorAll('.countdown-timer');
    elements.forEach(element => {
        const endTime = new Date(element.dataset.endTime).getTime();
        const now = new Date().getTime();
        const distance = endTime - now;
        
        if (distance < 0) {
            element.innerHTML = "Auction Ended";
            element.classList.add('text-danger');
            return;
        }
        
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        element.innerHTML = `${hours}h ${minutes}m ${seconds}s`;
    });
}

setInterval(updateCountdown, 1000);
updateCountdown();

// Bidding functionality
const auctionId = <?php echo $auction_id; ?>;
let currentPrice = <?php echo (float)$auction['current_price']; ?>;
const bidIncrement = <?php echo (float)($auction['bid_increment'] ?? ($auction['min_increment'] ?? 10)); ?>;
const canBid = <?php echo $can_bid ? 'true' : 'false'; ?>;

function disableBiddingUI() {
    document.querySelectorAll('.quick-bid-btn').forEach(btn => btn.setAttribute('disabled', 'disabled'));
    const customInput = document.getElementById('customBidAmount');
    if (customInput) customInput.setAttribute('disabled', 'disabled');
    const customBtn = document.getElementById('placeCustomBid');
    if (customBtn) customBtn.setAttribute('disabled', 'disabled');
}

function showMessage(message, type) {
    const el = document.getElementById('bidMessage');
    if (!el) return;

    el.className = 'alert alert-' + (type || 'info');
    el.textContent = message;
    el.style.display = 'block';
}

function hideMessage() {
    const el = document.getElementById('bidMessage');
    if (!el) return;
    el.style.display = 'none';
}

function updateMinBidUI() {
    const customBidInput = document.getElementById('customBidAmount');
    if (!customBidInput) return;

    customBidInput.min = (currentPrice + bidIncrement).toFixed(2);
    customBidInput.step = bidIncrement;
}

// Quick bid buttons
document.querySelectorAll('.quick-bid-btn').forEach(function(button) {
    console.log('Found quick bid button:', button);
    button.addEventListener('click', function() {
        const bidAmount = parseFloat(this.dataset.bidAmount);
        console.log('Quick bid clicked:', bidAmount);
        placeBidAmount(bidAmount);
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
            placeBidAmount(bidAmount);
        } else {
            showMessage('Minimum bid is $' + (currentPrice + bidIncrement).toFixed(2), 'danger');
        }
    });
} else {
    console.error('Custom bid button not found!');
}

function placeBidAmount(amount) {
    console.log('Placing bid:', amount);

    if (!canBid) {
        showMessage('You cannot bid on this auction.', 'danger');
        return;
    }
    
    if (!amount || isNaN(amount)) {
        showMessage('Invalid bid amount', 'danger');
        return;
    }

    if (amount < (currentPrice + bidIncrement)) {
        showMessage('Minimum bid is $' + (currentPrice + bidIncrement).toFixed(2), 'danger');
        return;
    }

    showMessage('Placing bid...', 'info');
    
    fetch('<?php echo SITE_URL; ?>api/place-bid.php', {
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
        if (response.status === 401) {
            throw new Error('LOGIN_REQUIRED');
        }
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
                updateCurrentBid(currentPrice);
                addBidToHistory(data.username, data.bid_amount, data.timestamp);
                setTimeout(() => refreshBidInfo(), 1000);
            } else {
                showMessage('Error: ' + (data.message || 'Unknown error'), 'danger');
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            showMessage('Invalid server response', 'danger');
        }
    })
    .catch(error => {
        if (error && error.message === 'LOGIN_REQUIRED') {
            showMessage('Login required to place a bid.', 'danger');
            return;
        }
        showMessage('Network error', 'danger');
        console.error('Fetch error:', error);
    });
}

function applyBidUpdate(payload) {
    const newPrice = parseFloat(payload.bid_amount);
    if (!newPrice || isNaN(newPrice)) {
        return;
    }

    currentPrice = newPrice;
    updateCurrentBid(newPrice);
    addBidToHistory(payload.bidder_username || 'Anonymous', newPrice, payload.timestamp || null);
}

function addBidToHistory(username, amount, timestamp) {
    const historyContainer = document.getElementById('bidHistory');
    if (!historyContainer) return;
    
    const bidItem = document.createElement('div');
    bidItem.className = 'd-flex justify-content-between mb-2';
    bidItem.innerHTML = `
        <span>${username}</span>
        <span>$${parseFloat(amount).toFixed(2)}</span>
        <small class="text-muted">${timestamp ? new Date(timestamp).toLocaleString() : 'Just now'}</small>
    `;
    
    historyContainer.insertBefore(bidItem, historyContainer.firstChild);
    
    // Keep only last 10 bids
    while (historyContainer.children.length > 10) {
        historyContainer.removeChild(historyContainer.lastChild);
    }
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function updateQuickBidButtons(newCurrentPrice) {
    document.querySelectorAll('.quick-bid-btn').forEach(function(button, index) {
        const multiplier = parseFloat(button.dataset.multiplier || '1');
        const newAmount = newCurrentPrice + (bidIncrement * multiplier);
        button.dataset.bidAmount = newAmount;
        button.innerHTML = `+$${(bidIncrement * multiplier).toFixed(2)} ($${newAmount.toFixed(2)})`;
    });
    
    // Update custom bid minimum
    updateMinBidUI();
}

function updateCurrentBid(newPrice) {
    // Update current bid display
    const currentBidEl = document.getElementById('currentBidPrice');
    if (currentBidEl) currentBidEl.textContent = '$' + newPrice.toFixed(2);

    const detailsBidEl = document.getElementById('currentBidPriceDetails');
    if (detailsBidEl) detailsBidEl.textContent = '$' + newPrice.toFixed(2);
    
    // Update quick bid buttons
    updateQuickBidButtons(newPrice);
}

function showBidNotification(username, amount) {
    const notification = document.createElement('div');
    notification.className = 'alert alert-info alert-dismissible fade show position-fixed top-0 end-0 m-3';
    notification.style.zIndex = '9999';
    notification.innerHTML = `
        <strong>New Bid!</strong><br>
        ${username} placed a bid of $${amount.toFixed(2)}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Initialize SSE for real-time updates
let eventSource = null;
let sseReconnectTimer = null;
function initSSE() {
    if (eventSource) {
        try { eventSource.close(); } catch (e) {}
        eventSource = null;
    }

    if (sseReconnectTimer) {
        clearTimeout(sseReconnectTimer);
        sseReconnectTimer = null;
    }

    const lastIdKey = 'auctionnova_sse_last_id_' + auctionId;
    const savedLastId = (() => {
        try {
            const v = localStorage.getItem(lastIdKey);
            return v && /^\d+$/.test(v) ? v : '0';
        } catch (e) {
            return '0';
        }
    })();

    const sseUrl = '<?php echo SITE_URL; ?>api/live-updates.php?last_id=' + encodeURIComponent(savedLastId);
    eventSource = new EventSource(sseUrl);
    
    eventSource.onopen = function(event) {
        console.log('SSE connection opened');
    };
    
    eventSource.addEventListener('bid_placed', function(event) {
        const data = JSON.parse(event.data);
        console.log('New bid received:', data);

        if (event.lastEventId) {
            try { localStorage.setItem(lastIdKey, String(event.lastEventId)); } catch (e) {}
        }
        
        // Update UI if this is the current auction
        if (data.auction_id == auctionId) {
            applyBidUpdate(data);
            showBidNotification(data.bidder_username, parseFloat(data.bid_amount));
        }
    });

    eventSource.addEventListener('auction_sold', function(event) {
        const data = JSON.parse(event.data);
        if (data.auction_id == auctionId) {
            const finalPrice = parseFloat(data.winning_bid);
            if (!isNaN(finalPrice)) {
                currentPrice = finalPrice;
                updateCurrentBid(finalPrice);
            }
            disableBiddingUI();
            showMessage('Auction sold to ' + (data.winner_username || 'winner') + ' for $' + Number(data.winning_bid).toFixed(2), 'warning');
        }
    });

    eventSource.onerror = function(event) {
        console.log('SSE error:', event);
        try { eventSource.close(); } catch (e) {}
        eventSource = null;
        // Attempt to reconnect after 5 seconds
        sseReconnectTimer = setTimeout(() => {
            initSSE();
        }, 5000);
    };
}

function renderBidHistory(latestBids) {
    const history = document.querySelector('.bid-history');
    if (!history) return;

    history.innerHTML = '';
    if (!latestBids || !latestBids.length) {
        const p = document.createElement('p');
        p.className = 'text-muted text-center';
        p.textContent = 'No bids yet. Be the first!';
        history.appendChild(p);
        return;
    }

    latestBids.forEach((bid) => {
        const username = bid.username || 'Anonymous';
        const amount = parseFloat(bid.bid_amount);
        const when = bid.bid_time ? new Date(String(bid.bid_time).replace(' ', 'T')) : null;
        const whenText = when && !isNaN(when.getTime()) ? when.toLocaleString() : '';

        const item = document.createElement('div');
        item.className = 'd-flex justify-content-between align-items-center mb-2';
        item.innerHTML = `
            <div>
                <strong>${escapeHtml(username)}</strong>
                <small class="text-muted d-block">${escapeHtml(whenText)}</small>
            </div>
            <div class="text-end">
                <strong>$${isNaN(amount) ? '0.00' : amount.toFixed(2)}</strong>
            </div>
        `;
        history.appendChild(item);
    });
}

let pollTimer = null;
let lastSnapshotKey = '';
function startBidPolling() {
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(refreshBidInfo, 3000);
    refreshBidInfo();
}

function refreshBidInfo() {
    fetch('<?php echo SITE_URL; ?>api/get-bid-info.php?auction_id=' + encodeURIComponent(auctionId))
    .then(response => response.json())
    .then(data => {
        if (data && data.success) {
            const newPrice = parseFloat(data.current_price);
            if (!isNaN(newPrice) && newPrice !== currentPrice) {
                currentPrice = newPrice;
                updateCurrentBid(newPrice);
            }
            renderBidHistory(data.latest_bids || []);
            updateMinBidUI();

            if (String(data.status || '') !== 'active') {
                disableBiddingUI();
            }
        }
    })
    .catch(() => {
        // Silently ignore polling errors
    });
}

updateMinBidUI();
startBidPolling();
// Initialize SSE for real-time updates
initSSE();
</script>

<?php include 'includes/footer.php'; ?>
