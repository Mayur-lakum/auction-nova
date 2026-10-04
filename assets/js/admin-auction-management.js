(function(window) {
    'use strict';

    function qs(sel) { return document.querySelector(sel); }
    function qsa(sel) { return Array.from(document.querySelectorAll(sel)); }

    async function apiGet(params) {
        const url = SITE_URL + 'api/admin/auction-management.php?' + new URLSearchParams(params).toString();
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        return res.json();
    }

    async function apiPost(action, body) {
        const form = new URLSearchParams();
        form.set('action', action);
        form.set('csrf_token', window.ADMIN_CSRF_TOKEN);
        Object.keys(body || {}).forEach(k => form.set(k, body[k]));

        const url = SITE_URL + 'api/admin/auction-management.php';
        console.log('POST to', url, { action, body, csrf: window.ADMIN_CSRF_TOKEN });
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: form.toString()
        });

        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('API returned non-JSON:', { url, action, status: res.status, text: text.slice(0, 300) });
            return { success: false, message: 'Server returned an invalid response (not JSON). Check PHP error log.', status: res.status };
        }
        console.log('Response', data);
        return data;
    }

    function formatCurrency(val) {
        const n = Number(val || 0);
        return '₹' + n.toFixed(2);
    }

    function badgeForStatus(row) {
        const end = new Date(row.end_time);
        const now = new Date();

        if (row.status === 'active' && end > now) return '<span class="badge bg-success">Active</span>';
        if (row.status === 'active' && end <= now) return '<span class="badge bg-warning">Expired</span>';
        if (row.status === 'ended') return '<span class="badge bg-secondary">Ended</span>';
        if (row.status === 'cancelled') return '<span class="badge bg-danger">Cancelled</span>';
        return '<span class="badge bg-light text-dark">' + (row.status || 'unknown') + '</span>';
    }

    function renderRow(row) {
        const highest = row.highest_bid || row.current_price || row.starting_price;
        const imageUrl = row.image_url || row.auction_image || '';
        const hasImage = imageUrl && !imageUrl.includes('no-image');
        return `
        <tr data-auction-id="${row.id}">
            <td>
                <div class="d-flex align-items-center gap-2">
                    ${hasImage ? `<img src="${escapeHtml(imageUrl)}" alt="" class="rounded" style="width:50px;height:50px;object-fit:cover;">` : '<div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:50px;height:50px;"><i class="fas fa-image text-muted"></i></div>'}
                    <div>
                        <div class="fw-semibold">${escapeHtml(row.title || '')}</div>
                        <div class="small text-muted">#${row.id} · ${escapeHtml(row.category_name || '')}</div>
                    </div>
                </div>
            </td>
            <td>
                <div>${escapeHtml(row.seller_name || '')}</div>
                <div class="small text-muted">Seller ID: ${row.seller_id}</div>
            </td>
            <td>
                <div class="fw-semibold">${formatCurrency(row.current_price)}</div>
                <div class="small text-muted">Start: ${formatCurrency(row.starting_price)}</div>
            </td>
            <td>${badgeForStatus(row)}</td>
            <td>
                <div class="small">
                    <div><span class="fw-semibold">${row.bid_count || 0}</span> bids</div>
                    <div>High: <span class="fw-semibold">${formatCurrency(highest)}</span></div>
                </div>
            </td>
            <td>
                <div class="small">${new Date(row.end_time).toLocaleString()}</div>
            </td>
            <td class="text-end">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" data-action="view" data-id="${row.id}">View</button>
                    <button class="btn btn-outline-secondary" data-action="extend" data-id="${row.id}">Extend</button>
                    <button class="btn btn-outline-danger" data-action="delete" data-id="${row.id}">Delete</button>
                </div>
            </td>
        </tr>`;
    }

    function escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    async function refreshList() {
        const tbody = qs('#adminAuctionsTbody');
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">Loading…</td></tr>';

        const params = {
            action: 'list',
            search: qs('#filterSearch')?.value || '',
            status: qs('#filterStatus')?.value || 'all',
            category: qs('#filterCategory')?.value || 'all'
        };

        const data = await apiGet(params);
        if (!data.success) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger">Failed to load auctions</td></tr>';
            return;
        }

        if (!data.auctions || data.auctions.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No auctions found</td></tr>';
            return;
        }

        tbody.innerHTML = data.auctions.map(renderRow).join('');
    }

    async function openDetails(auctionId) {
        const modalEl = qs('#auctionDetailsModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        qs('#detailsTitle').textContent = 'Loading…';
        qs('#detailsMeta').textContent = '';
        qs('#detailsOverview').innerHTML = '<div class="text-muted">Loading…</div>';
        qs('#detailsBids').innerHTML = '<div class="text-muted">Loading…</div>';

        qs('#notifyAuctionId').value = String(auctionId);
        qs('#actionAuctionId').value = String(auctionId);

        modal.show();

        const data = await apiGet({ action: 'details', auction_id: auctionId });
        if (!data.success) {
            qs('#detailsTitle').textContent = 'Failed to load';
            qs('#detailsOverview').innerHTML = '<div class="text-danger">' + escapeHtml(data.message || 'Error') + '</div>';
            return;
        }

        const a = data.auction;
        const primaryImage = a.image_url || (a.images && a.images.length > 0 ? a.images[0].image_url : '');
        const hasImage = primaryImage && !primaryImage.includes('no-image');
        
        qs('#detailsTitle').textContent = a.title;
        qs('#detailsMeta').textContent = `#${a.id} · ${a.category_name} · Seller: ${a.seller_name}`;

        qs('#detailsOverview').innerHTML = `
            <div class="row g-3">
                <div class="col-12">
                    ${hasImage ? `<div class="text-center mb-3"><img src="${escapeHtml(primaryImage)}" alt="${escapeHtml(a.title)}" class="img-fluid rounded" style="max-height:200px;"></div>` : ''}
                </div>
                <div class="col-md-6">
                    <div class="card"><div class="card-body">
                        <div class="text-muted small">Status</div>
                        <div class="fw-semibold">${escapeHtml(a.status)}</div>
                        <div class="text-muted small mt-2">End Time</div>
                        <div class="fw-semibold">${new Date(a.end_time).toLocaleString()}</div>
                    </div></div>
                </div>
                <div class="col-md-6">
                    <div class="card"><div class="card-body">
                        <div class="text-muted small">Current Price</div>
                        <div class="fw-semibold">${formatCurrency(a.current_price)}</div>
                        <div class="text-muted small mt-2">Starting Price</div>
                        <div class="fw-semibold">${formatCurrency(a.starting_price)}</div>
                    </div></div>
                </div>
                <div class="col-12">
                    <div class="card"><div class="card-body">
                        <div class="text-muted small">Description</div>
                        <div>${escapeHtml(a.description || '')}</div>
                    </div></div>
                </div>
            </div>
        `;

        const bids = data.bids || [];
        if (bids.length === 0) {
            qs('#detailsBids').innerHTML = '<div class="text-muted">No bids yet.</div>';
        } else {
            qs('#detailsBids').innerHTML = `
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Bidder</th>
                                <th class="text-end">Amount</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${bids.map((b, idx) => `
                                <tr class="${b.is_winning ? 'table-success' : ''}">
                                    <td>${idx + 1}</td>
                                    <td>
                                        <div class="fw-semibold">${escapeHtml((b.first_name || '') + ' ' + (b.last_name || ''))}</div>
                                        <div class="small text-muted">@${escapeHtml(b.username || '')} · ${escapeHtml(b.email || '')}</div>
                                    </td>
                                    <td class="text-end fw-semibold">${formatCurrency(b.bid_amount)}</td>
                                    <td>${new Date(b.bid_time).toLocaleString()}</td>
                                    <td>${b.is_winning ? '<span class="badge bg-success">Winning</span>' : '<span class="badge bg-secondary">Outbid</span>'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }
    }

    async function onTableClick(e) {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;

        const id = Number(btn.getAttribute('data-id'));
        const action = btn.getAttribute('data-action');

        if (action === 'view') {
            await openDetails(id);
            return;
        }

        if (action === 'extend') {
            const minutes = prompt('Extend auction by minutes (1-240):', '5');
            if (!minutes) return;
            const res = await apiPost('extend', { auction_id: String(id), minutes: String(minutes) });
            if (!res.success) {
                alert(res.message || 'Failed');
            }
            return;
        }

        if (action === 'delete') {
            if (!confirm('Delete this auction? This will remove bids/watchlist/images too.')) return;
            console.log('[Admin] Deleting auction', id);
            const res = await apiPost('delete', { auction_id: String(id) });
            console.log('[Admin] Delete response', res);
            if (!res.success) {
                alert(res.message || 'Failed');
                return;
            }
            // Remove row immediately without refresh
            const row = btn.closest('tr');
            if (row) row.remove();
            return;
        }
    }

    async function onActionButtons(e) {
        const btn = e.target.closest('button[data-admin-action]');
        if (!btn) return;

        const action = btn.getAttribute('data-admin-action');
        const auctionId = qs('#actionAuctionId')?.value;
        console.log('Admin action clicked', { action, auctionId });
        if (!auctionId) {
            alert('Auction ID missing');
            return;
        }

        if (action === 'end') {
            if (!confirm('End this auction now?')) return;
            const res = await apiPost('end', { auction_id: auctionId });
            if (!res.success) alert(res.message || 'Failed');
        }

        if (action === 'sell') {
            if (!confirm('Sell this auction to the current highest bidder and end it now?')) return;
            const res = await apiPost('sell', { auction_id: auctionId });
            if (!res.success) {
                alert(res.message || 'Failed');
            } else {
                alert('Sold to ' + (res.winner_username || 'winner') + ' for ' + formatCurrency(res.winning_bid));
            }
        }

        if (action === 'extend') {
            const minutes = prompt('Extend auction by minutes (1-240):', '5');
            if (!minutes) return;
            const res = await apiPost('extend', { auction_id: auctionId, minutes: String(minutes) });
            if (!res.success) {
                alert(res.message || 'Failed');
            } else {
                alert('Auction extended. New end time: ' + new Date(res.end_time).toLocaleString());
            }
        }

        if (action === 'delete') {
            if (!confirm('Delete this auction? This will remove bids/watchlist/images too.')) return;
            const res = await apiPost('delete', { auction_id: auctionId });
            if (!res.success) {
                alert(res.message || 'Failed');
            } else {
                // Redirect back to dashboard after delete
                window.location.href = SITE_URL + 'admin/dashboard.php';
            }
        }
    }

    async function onNotifySubmit(e) {
        e.preventDefault();

        const auctionId = qs('#notifyAuctionId').value;
        const title = qs('#notifyTitle').value.trim();
        const message = qs('#notifyMessage').value.trim();

        if (!title || !message) {
            alert('Title and message are required.');
            return;
        }

        const res = await apiPost('notify', { auction_id: auctionId, title, message });
        if (!res.success) {
            alert(res.message || 'Failed');
            return;
        }

        qs('#notifyTitle').value = '';
        qs('#notifyMessage').value = '';
        alert('Notification sent to ' + res.sent_to + ' users');
    }

    function wireRealtime() {
        if (typeof AuctionSSE === 'undefined') return;

        const refreshSoon = () => setTimeout(refreshList, 250);

        AuctionSSE.on('auction_deleted', refreshSoon);
        AuctionSSE.on('auction_ended', refreshSoon);
        AuctionSSE.on('auction_sold', refreshSoon);
        AuctionSSE.on('auction_extended', refreshSoon);
        AuctionSSE.on('bid_placed', refreshSoon);
        AuctionSSE.on('auction_created_live', refreshSoon);

        // Per-auction page: if we’re on manage-auction.php, update fields instantly
        AuctionSSE.on('auction_ended', function(payload) {
            if (window.location.pathname.includes('manage-auction.php')) {
                const currentId = qs('#actionAuctionId')?.value;
                if (currentId && String(currentId) === String(payload.auction_id)) {
                    const statusEl = qs('#auctionStatus');
                    if (statusEl) statusEl.textContent = 'ended';
                    alert('Auction has been ended.');
                }
            }
        });

        AuctionSSE.on('auction_extended', function(payload) {
            if (window.location.pathname.includes('manage-auction.php')) {
                const currentId = qs('#actionAuctionId')?.value;
                if (currentId && String(currentId) === String(payload.auction_id)) {
                    const endTimeEl = qs('#auctionEndTime');
                    if (endTimeEl) {
                        endTimeEl.textContent = new Date(payload.end_time).toLocaleString();
                    }
                    alert('Auction extended by ' + payload.minutes + ' minutes.');
                }
            }
        });

        AuctionSSE.on('auction_deleted', function(payload) {
            console.log('[AdminSSE] auction_deleted received', payload);
            if (window.location.pathname.includes('manage-auction.php')) {
                const currentId = qs('#actionAuctionId')?.value;
                if (currentId && String(currentId) === String(payload.auction_id)) {
                    alert('Auction was deleted. Redirecting to dashboard...');
                    window.location.href = SITE_URL + 'admin/dashboard.php';
                }
            }
        });

        AuctionSSE.on('auction_sold', function(payload) {
            if (window.location.pathname.includes('manage-auction.php')) {
                const currentId = qs('#actionAuctionId')?.value;
                if (currentId && String(currentId) === String(payload.auction_id)) {
                    const statusEl = qs('#auctionStatus');
                    if (statusEl) statusEl.textContent = 'ended';
                    alert('Auction sold to ' + (payload.winner_username || 'winner') + ' for ' + formatCurrency(payload.winning_bid));
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Bind admin action buttons (for per-auction page)
        document.addEventListener('click', onActionButtons);

        // Bind notify form (for per-auction page)
        const notifyForm = qs('#notifyForm');
        if (notifyForm) {
            notifyForm.addEventListener('submit', onNotifySubmit);
        }

        // Only bind table/listing logic if we’re on the list page
        if (!qs('#adminAuctionsTbody')) return;

        qs('#btnRefresh')?.addEventListener('click', refreshList);
        qs('#btnApplyFilters')?.addEventListener('click', function(e) {
            e.preventDefault();
            refreshList();
        });
        wireFilters();
        refreshList();
        wireRealtime();
    });

    function debounce(fn, wait) {
        let t;
        return function() {
            clearTimeout(t);
            t = setTimeout(fn, wait);
        };
    }
})(window);
