<?php
session_start();

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/functions.php';
require_once '../includes/live_events.php';
require_once 'access-control.php';

// Verify admin session
verify_admin_session();

$db = Database::getInstance();

// AJAX endpoint for realtime stats (polling)
if (isset($_GET['ajax']) && $_GET['ajax'] === 'stats') {
    header('Content-Type: application/json');
    try {
        $stats = [
            'live_auctions' => (int)($db->fetch("SELECT COUNT(*) AS c FROM auctions WHERE status = 'active' AND end_time > NOW()")['c'] ?? 0),
            'live_bids' => (int)($db->fetch("SELECT COUNT(*) AS c FROM bids b JOIN auctions a ON a.id=b.auction_id WHERE a.status='active' AND a.end_time > NOW()")['c'] ?? 0),
            'active_users_in_live' => (int)($db->fetch("SELECT COUNT(DISTINCT b.bidder_id) AS c FROM bids b JOIN auctions a ON a.id=b.auction_id WHERE a.status='active' AND a.end_time > NOW()")['c'] ?? 0),
            'ending_soon' => (int)($db->fetch("SELECT COUNT(*) AS c FROM auctions WHERE status='active' AND end_time BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 10 MINUTE)")['c'] ?? 0),
            'total_auctions' => (int)($db->fetch("SELECT COUNT(*) AS c FROM auctions")['c'] ?? 0),
        ];
        echo json_encode(['success' => true, 'stats' => $stats]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// AJAX endpoint for live auctions list
if (isset($_GET['ajax']) && $_GET['ajax'] === 'live_auctions') {
    header('Content-Type: text/html');
    try {
        $sql = "SELECT a.id, a.title, a.current_price, a.end_time, a.status, (SELECT COUNT(*) FROM bids b WHERE b.auction_id=a.id) AS bid_count FROM auctions a WHERE a.status='active' AND a.end_time > NOW() ORDER BY a.created_at DESC LIMIT 10";
        $live_auctions = $db->fetchAll($sql);
        
        ob_start();
        if (empty($live_auctions)):
            echo '<div class="text-muted text-center p-3">No live auctions</div>';
        else:
            foreach ($live_auctions as $a):
        ?>
        <div class="live-row">
            <div>
                <div class="t"><?php echo htmlspecialchars($a['title']); ?> <span class="badge-live">LIVE</span></div>
                <div class="meta">Bids: <?php echo (int)$a['bid_count']; ?> • Ends: <span class="countdown-timer" data-end="<?php echo htmlspecialchars($a['end_time']); ?>"></span></div>
            </div>
            <div style="text-align:right;">
                <div style="font-weight:800;">$<?php echo number_format((float)$a['current_price'], 2); ?></div>
                <a class="btn btn-sm btn-outline-primary" href="manage-auction.php?id=<?php echo (int)$a['id']; ?>">Manage</a>
            </div>
        </div>
        <?php
            endforeach;
        endif;
        echo ob_get_clean();
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error loading auctions</div>';
    }
    exit();
}

$success = '';
$error = '';

if (isset($_GET['success'])) {
    $success = 'Auction created and is LIVE now.';
}

// Load categories for dropdown and broadcast
$categories = [];
try {
    $categories = $db->fetchAll("SELECT id, name FROM categories WHERE is_active = TRUE ORDER BY name");
} catch (Exception $e) {
    $categories = [];
}

// Ensure image column exists
try {
    $db->query("ALTER TABLE auctions ADD COLUMN image VARCHAR(255) NULL");
} catch (Exception $e) {
    // Column already exists or error; ignore
}

// Ensure bid_increment column exists
try {
    $db->query("ALTER TABLE auctions ADD COLUMN bid_increment DECIMAL(10,2) DEFAULT 10.00");
    error_log("bid_increment column created or already exists");
} catch (Exception $e) {
    error_log("bid_increment column already exists or error: " . $e->getMessage());
}

// Ensure min_increment column exists (fallback if schema uses this name)
try {
    $db->query("ALTER TABLE auctions ADD COLUMN min_increment DECIMAL(10,2) DEFAULT 10.00");
} catch (Exception $e) {
    // Column already exists or error; ignore
}

// Ensure reserve_price column exists
try {
    $db->query("ALTER TABLE auctions ADD COLUMN reserve_price DECIMAL(10,2) NULL");
} catch (Exception $e) {
    // Column already exists or error; ignore
}

// Ensure auto_extend_seconds column exists
try {
    $db->query("ALTER TABLE auctions ADD COLUMN auto_extend_seconds INT DEFAULT 30");
} catch (Exception $e) {
    // Column already exists or error; ignore
}

// Ensure live_updates table exists
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS live_updates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(50) NOT NULL,
            event_data JSON NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (event_type),
            INDEX (created_at)
        )
    ");
} catch (Exception $e) {
    error_log('Failed to ensure live_updates table: ' . $e->getMessage());
}

// Create & Go Live
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_go_live') {
    $title = sanitize_input($_POST['title'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $starting_price = (float)($_POST['starting_price'] ?? 0);
    $min_increment = (float)($_POST['min_increment'] ?? 1);
    $reserve_price = (float)($_POST['reserve_price'] ?? 0);
    $duration_minutes = (int)($_POST['duration_minutes'] ?? 60);
    $auto_extend_seconds = (int)($_POST['auto_extend_seconds'] ?? 30);
    $category_id = (int)($_POST['category_id'] ?? 1); // default to first category if not set

    if ($title === '' || $description === '' || $starting_price <= 0 || $min_increment <= 0 || $duration_minutes <= 0 || $category_id <= 0) {
        $error = 'Please fill all required fields with valid values.';
    } else {
        try {
            // Ensure all required columns exist before inserting
            try {
                $db->query("ALTER TABLE auctions ADD COLUMN bid_increment DECIMAL(10,2) DEFAULT 10.00");
            } catch (Exception $e) {}
            try {
                $db->query("ALTER TABLE auctions ADD COLUMN reserve_price DECIMAL(10,2) NULL");
            } catch (Exception $e) {}
            try {
                $db->query("ALTER TABLE auctions ADD COLUMN auto_extend_seconds INT DEFAULT 30");
            } catch (Exception $e) {}

            $end_time = date('Y-m-d H:i:s', time() + ($duration_minutes * 60));
            $pdo = $db->getConnection();

            // LIVE ONLY: status='active' immediately (matches schema enum)
            // seller_id: use the admin’s user_id if available; otherwise default to 1 (ensure a valid seller)
            $seller_id = (int)($_SESSION['user_id'] ?? 0);
            if ($seller_id === 0) {
                // Fallback: pick the first admin user from DB as seller, or 1 if none
                $fallback = $db->fetch("SELECT id FROM users WHERE role IN ('admin','super_admin') LIMIT 1");
                $seller_id = (int)($fallback['id'] ?? 1);
            }

            error_log("Attempting insert: title=$title, seller_id=$seller_id, category_id=$category_id, starting_price=$starting_price, current_price=$starting_price, end_time=$end_time");
            $stmt = $pdo->prepare(
                "INSERT INTO auctions (title, description, seller_id, category_id, starting_price, current_price, bid_increment, end_time, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())"
            );
            if (!$stmt) {
                error_log('Prepare failed: ' . implode(' ', $pdo->errorInfo()));
            }
            $result = $stmt->execute([$title, $description, $seller_id, $category_id, $starting_price, $starting_price, $min_increment, $end_time]);
            if (!$result) {
                error_log('Execute failed: ' . implode(' ', $stmt->errorInfo()));
            } else {
                $auction_id = (int)$pdo->lastInsertId();
                error_log("Admin created auction ID $auction_id with status 'active'");
            }

            // Handle image upload
            $image_path = '';
            if (!empty($_FILES['auction_image']['tmp_name']) && $_FILES['auction_image']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext = strtolower(pathinfo($_FILES['auction_image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $max_size = 5 * 1024 * 1024; // 5MB
                    if ($_FILES['auction_image']['size'] <= $max_size) {
                        $filename = 'auction_' . $auction_id . '_' . time() . '.' . $ext;
                        $target_dir = __DIR__ . '/../assets/uploads/';
                        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
                        $target_path = $target_dir . $filename;
                        if (move_uploaded_file($_FILES['auction_image']['tmp_name'], $target_path)) {
                            $image_path = $filename;
                            try {
                                $pdo->prepare("UPDATE auctions SET image = ? WHERE id = ?")->execute([$image_path, $auction_id]);
                            } catch (Exception $e) {
                                error_log('Failed to store image path: ' . $e->getMessage());
                            }

                            try {
                                $pdo->prepare("INSERT INTO auction_images (auction_id, image_path, is_primary) VALUES (?, ?, 1)")
                                    ->execute([$auction_id, $image_path]);
                            } catch (Exception $e) {
                                error_log('Failed to store auction_images record: ' . $e->getMessage());
                            }
                        }
                    }
                }
            }

            // Optional fields if columns exist; ignore failures safely
            try {
                $db->query("UPDATE auctions SET min_increment = ? WHERE id = ?", [$min_increment, $auction_id]);
            } catch (Exception $e) {
                // ignore if schema doesn't have these columns
            }

            $success = 'Auction created and is LIVE now.';
            
            error_log("About to broadcast auction_created_live for ID $auction_id with image '$image_path'");
            // Broadcast auction_created_live event
            broadcast_event('auction_created_live', [
                'auction_id' => $auction_id,
                'title' => $title,
                'category_id' => $category_id,
                'starting_price' => $starting_price,
                'current_price' => $starting_price,
                'end_time' => $end_time,
                'image' => auction_image_url($image_path),
                'seller_name' => $_SESSION['username'] ?? 'Admin',
                'category_name' => $categories[array_search($category_id, array_column($categories, 'id'))]['name'] ?? 'Unknown'
            ]);
            
            // Redirect to self to refresh the live auctions list
            header('Location: ' . SITE_URL . 'admin/dashboard.php?success=1');
            exit;
        } catch (Exception $e) {
            $error = 'Failed to create live auction: ' . $e->getMessage();
            error_log('Create auction DB error: ' . $e->getMessage());
        }
    }
}

$live_auctions = [];
try {
    $sql = "SELECT a.id, a.title, a.current_price, a.end_time, a.status, (SELECT COUNT(*) FROM bids b WHERE b.auction_id=a.id) AS bid_count FROM auctions a WHERE a.status='active' AND a.end_time > NOW() ORDER BY a.created_at DESC LIMIT 10";
    error_log("Live auctions SQL: $sql");
    $live_auctions = $db->fetchAll($sql);
    error_log("Live auctions count: " . count($live_auctions));
    // Log each auction for debugging
    foreach ($live_auctions as $a) {
        error_log("Live auction: ID={$a['id']}, status={$a['status']}, end_time={$a['end_time']}");
    }
} catch (Exception $e) {
    error_log('Live auctions query error: ' . $e->getMessage());
    $live_auctions = [];
}

$page_title = 'Admin Control Room';
require_once '../includes/header.php';
?>

<style>
 .admin-wrap { padding: 18px; }
 .topbar { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; }
 .stats { display:grid; grid-template-columns: repeat(5, minmax(160px, 1fr)); gap:12px; margin-bottom:18px; }
 @media (max-width: 1200px) { .stats { grid-template-columns: repeat(2, minmax(160px, 1fr)); } }
 .stat { background:#fff; border-radius:12px; padding:14px 16px; box-shadow:0 2px 10px rgba(0,0,0,.08); border-left:4px solid #6f42c1; }
 .stat .n { font-size:22px; font-weight:800; color:#111827; }
 .stat .l { font-size:12px; letter-spacing:.4px; text-transform:uppercase; color:#6b7280; }
 .card { background:#fff; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,.08); overflow:hidden; }
 .card-h { padding:14px 16px; background:linear-gradient(135deg,#6f42c1,#0d6efd); color:#fff; display:flex; justify-content:space-between; align-items:center; }
 .card-b { padding:16px; }
 .grid { display:grid; grid-template-columns: 420px 1fr; gap:12px; }
 @media (max-width: 1100px) { .grid { grid-template-columns:1fr; } }
 .live-row { display:flex; justify-content:space-between; align-items:center; padding:12px 12px; border:1px solid #e5e7eb; border-radius:10px; margin-bottom:10px; }
 .live-row .t { font-weight:700; color:#111827; }
 .badge-live { background:#dc3545; color:#fff; font-weight:700; border-radius:999px; padding:4px 10px; font-size:12px; }
 .meta { color:#6b7280; font-size:12px; }
</style>

<div class="container-fluid admin-wrap">
    <div class="topbar">
        <div>
            <h2 style="margin:0">Admin Control Room</h2>
            <div class="meta">Single admin • Live-only auctions</div>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a class="btn btn-outline-primary" href="../index.php" target="_blank">View User Site</a>
            <a class="btn btn-outline-secondary" href="logout.php">Logout</a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat"><div class="n" id="s_live_auctions">0</div><div class="l">Live Auctions</div></div>
        <div class="stat"><div class="n" id="s_live_bids">0</div><div class="l">Live Bids</div></div>
        <div class="stat"><div class="n" id="s_active_users">0</div><div class="l">Active Users</div></div>
        <div class="stat"><div class="n" id="s_ending_soon">0</div><div class="l">Ending Soon (10m)</div></div>
        <div class="stat"><div class="n" id="s_total_auctions">0</div><div class="l">Total Auctions</div></div>
    </div>

    <div class="grid">
        <div class="card">
            <div class="card-h">
                <div style="font-weight:800;">Create & Go Live</div>
                <span class="badge-live">LIVE</span>
            </div>
            <div class="card-b">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create_go_live">

                    <div class="mb-3">
                        <label class="form-label">Category *</label>
                        <select class="form-select" name="category_id" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Item Details *</label>
                        <textarea class="form-control" name="description" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Auction Image</label>
                        <input type="file" class="form-control" name="auction_image" accept="image/*">
                        <div class="small text-muted">Optional: JPG/PNG up to 5MB</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Starting Price *</label>
                            <input class="form-control" type="number" name="starting_price" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Min Bid Increment *</label>
                            <input class="form-control" type="number" name="min_increment" step="0.01" min="0.01" value="1" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration (minutes) *</label>
                            <input class="form-control" type="number" name="duration_minutes" min="1" value="60" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reserve Price (optional)</label>
                            <input class="form-control" type="number" name="reserve_price" step="0.01" min="0">
                        </div>
                    </div>

                    <button class="btn btn-danger w-100" type="submit" onclick="return confirm('Create auction and publish LIVE immediately?');">
                        Create & Go Live
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-h">
                <div style="font-weight:800;">Live Auctions</div>
                <div class="meta" style="color:#fff; opacity:.9;">Visible to users only when status = LIVE</div>
            </div>
            <div class="card-b" id="liveAuctionsContainer">
                <?php if (empty($live_auctions)): ?>
                    <div class="text-muted">No live auctions right now.</div>
                <?php else: ?>
                    <?php foreach ($live_auctions as $a): ?>
                        <div class="live-row">
                            <div>
                                <div class="t"><?php echo htmlspecialchars($a['title']); ?> <span class="badge-live">LIVE</span></div>
                                <div class="meta">Bids: <?php echo (int)$a['bid_count']; ?> • Ends: <span class="countdown-timer" data-end="<?php echo htmlspecialchars($a['end_time']); ?>"></span></div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-weight:800;">$<?php echo number_format((float)$a['current_price'], 2); ?></div>
                                <a class="btn btn-sm btn-outline-primary" href="manage-auction.php?id=<?php echo (int)$a['id']; ?>">Manage</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function setText(id, val) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = val;
}

async function refreshStats() {
  try {
    const res = await fetch('dashboard.php?ajax=stats', { cache: 'no-store' });
    const data = await res.json();
    if (!data.success) return;
    const s = data.stats;
    setText('s_live_auctions', s.live_auctions ?? 0);
    setText('s_live_bids', s.live_bids ?? 0);
    setText('s_active_users', s.active_users_in_live ?? 0);
    setText('s_ending_soon', s.ending_soon ?? 0);
    setText('s_total_auctions', s.total_auctions ?? 0);
  } catch (e) {
    // ignore
  }
}

// Live countdown timers
function updateCountdowns() {
    document.querySelectorAll('.countdown-timer').forEach(el => {
        const end = new Date(el.getAttribute('data-end')).getTime();
        const now = Date.now();
        const diff = end - now;
        if (diff <= 0) {
            // Remove the entire live row when auction ends
            const row = el.closest('.live-row');
            if (row) {
                row.style.transition = 'opacity 0.3s';
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 300);
            }
            // Also refresh live auctions list in case it was a delayed update
            setTimeout(() => refreshLiveAuctions(), 500);
            return; // Skip setting text to 'Ended'
        } else {
            const h = Math.floor(diff / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            const s = Math.floor((diff % 60000) / 1000);
            el.textContent = `${h}h ${m}m ${s}s`;
            el.style.color = diff < 600000 ? '#dc3545' : '#111827';
        }
    });
}

refreshStats();
setInterval(refreshStats, 5000);

// Initialize SSE for real-time updates
// SSE is intentionally opt-in to avoid keeping the page in a perpetual "loading" state.
// Call initAdminSSE() manually if you really want realtime updates.

function initAdminSSE() {
    if (window.__adminEventSource) {
        try { window.__adminEventSource.close(); } catch (e) {}
        window.__adminEventSource = null;
    }
    const eventSource = new EventSource('<?php echo SITE_URL; ?>api/live-updates.php');
    window.__adminEventSource = eventSource;
    
    eventSource.onopen = function(event) {
        console.log('Admin SSE connection opened');
    };
    
    eventSource.addEventListener('bid_placed', function(event) {
        const data = JSON.parse(event.data);
        console.log('Admin: New bid received:', data);
        
        // Update live auctions list
        refreshLiveAuctions();
        
        // Show notification
        showAdminNotification('New Bid!', `${data.bidder_username} bid $${data.bid_amount.toFixed(2)} on auction #${data.auction_id}`);
    });
    
    eventSource.addEventListener('auction_created_live', function(event) {
        const data = JSON.parse(event.data);
        console.log('Admin: New auction created:', data);
        
        // Refresh live auctions list
        refreshLiveAuctions();
        
        // Show notification
        showAdminNotification('New Auction!', `${data.title} is now live`);
    });
    
    eventSource.onerror = function(event) {
        console.log('Admin SSE error:', event);
        // Attempt to reconnect after 5 seconds
        setTimeout(() => {
            initAdminSSE();
        }, 5000);
    };
}

function refreshLiveAuctions() {
    // Refresh the live auctions section via AJAX
    fetch('<?php echo SITE_URL; ?>admin/dashboard.php?ajax=live_auctions')
        .then(response => response.text())
        .then(html => {
            const container = document.getElementById('liveAuctionsContainer');
            if (container) {
                container.innerHTML = html;
                updateCountdowns();
            }
        })
        .catch(error => console.error('Error refreshing live auctions:', error));
}

function showAdminNotification(title, message) {
    const notification = document.createElement('div');
    notification.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
    notification.style.zIndex = '9999';
    notification.innerHTML = `
        <strong>${title}</strong><br>
        ${message}
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
setInterval(updateCountdowns, 1000);
updateCountdowns();

// Also refresh live auctions list every 30 seconds to catch server-side changes
setInterval(refreshLiveAuctions, 30000);
</script>

<?php require_once '../includes/footer.php'; ?>