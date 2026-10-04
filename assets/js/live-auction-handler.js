// Frontend handler for live auction insertion via SSE
document.addEventListener('DOMContentLoaded', function() {
    if (typeof AuctionSSE === 'undefined') return;

    AuctionSSE.on('auction_created_live', function(data) {
        // Only act if we’re on the Live Auctions page
        const grid = document.getElementById('liveAuctionsGrid');
        if (!grid) return;

        const card = document.createElement('div');
        card.className = 'col-lg-4 col-md-6';
        card.setAttribute('data-auction-id', data.auction_id);
        card.innerHTML = `
            <div class="card h-100 live-auction-card">
                <div class="position-relative">
                    <div class="card-img-top d-flex align-items-center justify-content-center text-center fw-bold" style="height: 200px; background: #f1f5f9; color: #0f172a; padding: 12px;">
                        ${data.title}
                    </div>
                    <div class="position-absolute top-0 end-0 m-2">
                        <span class="badge bg-danger animate-pulse">LIVE</span>
                    </div>
                    <div class="position-absolute bottom-0 start-0 m-2">
                        <span class="badge bg-dark">
                            <i class="fas fa-clock me-1"></i>
                            <span class="countdown-timer" data-end-time="${data.end_time}" data-auction-id="${data.auction_id}">--:--:--</span>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <h5 class="card-title">${data.title}</h5>
                    <p class="text-muted small mb-2">
                        <i class="fas fa-user me-1"></i>${data.seller_name}
                        <span class="ms-2"><i class="fas fa-tag me-1"></i>${data.category_name}</span>
                    </p>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="text-muted small">Current Bid</span>
                            <div class="h5 text-success mb-0">$${Number(data.current_price).toFixed(2)}</div>
                        </div>
                        <div class="text-end">
                            <span class="text-muted small">0 bids</span>
                        </div>
                    </div>
                    <div class="bidding-controls">
                        <div class="input-group mb-2">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control bid-amount" 
                                   placeholder="Enter amount" 
                                   min="${(Number(data.current_price) + 1).toFixed(2)}"
                                   step="1"
                                   data-auction-id="${data.auction_id}">
                        </div>
                        <button class="btn btn-success w-100 place-bid-btn" data-auction-id="${data.auction_id}">
                            <i class="fas fa-gavel me-2"></i>Place Bid
                        </button>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="${SITE_URL}auction-details.php?id=${data.auction_id}" 
                       class="btn btn-outline-primary w-100">
                        <i class="fas fa-eye me-2"></i>View Details
                    </a>
                </div>
            </div>
        `;
        // Prepend to top of grid
        grid.insertBefore(card, grid.firstChild);
        // Flash highlight
        card.classList.add('border-success', 'border-2');
        setTimeout(() => card.classList.remove('border-success', 'border-2'), 3000);
    });

    AuctionSSE.on('auction_deleted', function(payload) {
        console.log('[LiveGrid] auction_deleted received', payload);
        // Remove from live auction grid instantly
        const card = document.querySelector(`[data-auction-id="${payload.auction_id}"]`);
        if (card) {
            card.style.transition = 'opacity 0.3s';
            card.style.opacity = '0';
            setTimeout(() => card.remove(), 300);
        }
    });

    function removeFromLiveGrid(payload, label) {
        if (!payload || !payload.auction_id) return;
        console.log('[LiveGrid] ' + label + ' received', payload);
        const card = document.querySelector(`[data-auction-id="${payload.auction_id}"]`);
        if (card) {
            card.style.transition = 'opacity 0.3s';
            card.style.opacity = '0';
            setTimeout(() => card.remove(), 300);
        }
    }

    AuctionSSE.on('auction_sold', function(payload) {
        removeFromLiveGrid(payload, 'auction_sold');
    });

    AuctionSSE.on('auction_to_cart', function(payload) {
        removeFromLiveGrid(payload, 'auction_to_cart');
    });
});
