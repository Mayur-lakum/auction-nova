(function(window) {
    'use strict';

    function showToast(title, message) {
        try {
            let container = document.getElementById('sseToastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'sseToastContainer';
                container.className = 'toast-container position-fixed top-0 end-0 p-3';
                container.style.zIndex = '1080';
                document.body.appendChild(container);
            }

            const toastEl = document.createElement('div');
            toastEl.className = 'toast align-items-center text-bg-primary border-0';
            toastEl.setAttribute('role', 'alert');
            toastEl.setAttribute('aria-live', 'assertive');
            toastEl.setAttribute('aria-atomic', 'true');

            toastEl.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <div class="fw-semibold">${escapeHtml(title)}</div>
                        <div>${escapeHtml(message)}</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;

            container.appendChild(toastEl);
            const toast = bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 5000 });
            toast.show();
            toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        } catch (e) {
            // ignore
        }
    }

    function escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function removeAuctionFromGrid(auctionId) {
        const el = document.querySelector('[data-auction-id="' + auctionId + '"]');
        if (el) el.remove();
    }

    function updateAuctionEndTime(auctionId, endTime) {
        const timers = document.querySelectorAll('.countdown-timer[data-auction-id="' + auctionId + '"]');
        timers.forEach(t => t.setAttribute('data-end-time', endTime));
    }

    function updateCartCounter(itemCount) {
        const cartCounter = document.getElementById('cart-counter');
        if (!cartCounter) return;

        const n = parseInt(itemCount, 10) || 0;
        cartCounter.textContent = n;
        cartCounter.style.display = n > 0 ? 'inline-block' : 'none';
    }

    function getQueryParam(name) {
        try {
            const params = new URLSearchParams(window.location.search || '');
            return params.get(name);
        } catch (e) {
            return null;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof AuctionSSE === 'undefined') return;

        AuctionSSE.on('auction_deleted', function(payload) {
            console.log('[SSE] auction_deleted received', payload);
            removeAuctionFromGrid(payload.auction_id);

            const path = String(window.location.pathname || '');
            if (path.endsWith('auction-details.php')) {
                const currentId = getQueryParam('id');
                if (currentId && String(currentId) === String(payload.auction_id)) {
                    showToast('Auction Deleted', 'This auction was removed by admin.');
                    window.location.href = (window.SITE_URL || window.siteUrl || '/') + 'auction-hub.php';
                }
            }
        });

        AuctionSSE.on('auction_ended', function(payload) {
            removeAuctionFromGrid(payload.auction_id);
        });

        function maybeAddToWinnerCart(payload) {
            if (!payload || !payload.auction_id) return;

            removeAuctionFromGrid(payload.auction_id);

            const myId = window.currentUserId;
            if (!myId) return;
            if (String(payload.winner_user_id) !== String(myId)) return;

            const awardedKey = 'auctionnova_awarded_cart_' + String(myId) + '_' + String(payload.auction_id);
            try {
                if (localStorage.getItem(awardedKey) === '1') return;
            } catch (e) {
            }

            const baseUrl = (window.SITE_URL || window.siteUrl || '');
            fetch(baseUrl + 'api/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'add', auction_id: payload.auction_id, quantity: 1 })
            })
            .then(r => r.json())
            .then(res => {
                if (res && res.success) {
                    updateCartCounter(res.cart_count);
                    try { localStorage.setItem(awardedKey, '1'); } catch (e) {}
                    showToast('Auction Won', 'Item added to your cart');
                }
            })
            .catch(() => {
            });
        }

        AuctionSSE.on('auction_sold', function(payload) {
            maybeAddToWinnerCart(payload);
        });

        AuctionSSE.on('auction_to_cart', function(payload) {
            maybeAddToWinnerCart(payload);
        });

        AuctionSSE.on('auction_extended', function(payload) {
            updateAuctionEndTime(payload.auction_id, payload.end_time);
            showToast('Auction Extended', 'End time updated for auction #' + payload.auction_id);
        });

        AuctionSSE.on('auction_notification', function(payload) {
            showToast(payload.title || 'Auction Update', payload.message || '');
        });
    });
})(window);
