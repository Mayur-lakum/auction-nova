<?php
require_once '../includes/functions.php';
require_login();
require_admin();

$page_title = 'Manage Users';
$db = new Database();
$conn = $db->connect();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        flash_message('error', 'Invalid request');
    } else {
        $user_id = (int)$_POST['user_id'];
        $action = $_POST['action'];
        
        switch ($action) {
            case 'activate':
                $query = "UPDATE users SET is_active = TRUE WHERE id = ?";
                $stmt = $conn->prepare($query);
                if ($stmt->execute([$user_id])) {
                    log_admin_action($_SESSION['user_id'], 'activate_user', 'user', $user_id, 'User activated');
                    flash_message('success', 'User activated successfully');
                }
                break;
                
            case 'deactivate':
                $query = "UPDATE users SET is_active = FALSE WHERE id = ?";
                $stmt = $conn->prepare($query);
                if ($stmt->execute([$user_id])) {
                    log_admin_action($_SESSION['user_id'], 'deactivate_user', 'user', $user_id, 'User deactivated');
                    flash_message('success', 'User deactivated successfully');
                }
                break;
                
            case 'verify':
                $query = "UPDATE users SET is_verified = TRUE WHERE id = ?";
                $stmt = $conn->prepare($query);
                if ($stmt->execute([$user_id])) {
                    log_admin_action($_SESSION['user_id'], 'verify_user', 'user', $user_id, 'User verified');
                    flash_message('success', 'User verified successfully');
                }
                break;
        }
        
        redirect($_SERVER['REQUEST_URI']);
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';
$role_filter = $_GET['role'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query conditions
$where_conditions = ["1=1"];
$params = [];

if ($search) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($status_filter === 'active') {
    $where_conditions[] = "is_active = TRUE";
} elseif ($status_filter === 'inactive') {
    $where_conditions[] = "is_active = FALSE";
}

if ($role_filter !== 'all') {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM users WHERE $where_clause";
$count_stmt = $conn->prepare($count_query);
$count_stmt->execute($params);
$total_users = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_users / $per_page);

// Get users
$users_query = "SELECT u.*, 
                (SELECT COUNT(*) FROM auctions WHERE seller_id = u.id) as auction_count,
                (SELECT COUNT(*) FROM bids WHERE bidder_id = u.id) as bid_count
                FROM users u 
                WHERE $where_clause 
                ORDER BY u.created_at DESC 
                LIMIT $per_page OFFSET $offset";

$users_stmt = $conn->prepare($users_query);
$users_stmt->execute($params);
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) as active_users,
                SUM(CASE WHEN is_verified = TRUE THEN 1 ELSE 0 END) as verified_users,
                SUM(CASE WHEN role = 'admin' OR role = 'super_admin' THEN 1 ELSE 0 END) as admin_users
                FROM users";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="fas fa-users me-2"></i>Manage Users
            </h2>
            <p class="text-muted mb-0">View and manage all registered users</p>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
                <div class="stat-number"><?php echo number_format($stats['active_users']); ?></div>
                <div class="stat-label">Active Users</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
                <div class="stat-number"><?php echo number_format($stats['verified_users']); ?></div>
                <div class="stat-label">Verified Users</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card" style="background: linear-gradient(135deg, #dc3545, #e83e8c);">
                <div class="stat-number"><?php echo number_format($stats['admin_users']); ?></div>
                <div class="stat-label">Admin Users</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search Users</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by name, username, or email">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Role</label>
                    <select class="form-select" name="role">
                        <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>All Roles</option>
                        <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="super_admin" <?php echo $role_filter === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Filter
                    </button>
                </div>
                
                <div class="col-md-2">
                    <a href="users.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times me-2"></i>Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Users List</h5>
            <span class="text-muted">Showing <?php echo count($users); ?> of <?php echo $total_users; ?> users</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5>No users found</h5>
                <p class="text-muted">Try adjusting your search criteria</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Role</th>
                            <th>Activity</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-3 bg-primary text-white d-flex align-items-center justify-content-center">
                                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                        <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($user['email']); ?></div>
                                <?php if ($user['phone']): ?>
                                <small class="text-muted"><?php echo htmlspecialchars($user['phone']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                    <?php if ($user['is_verified']): ?>
                                    <span class="badge bg-primary">Verified</span>
                                    <?php else: ?>
                                    <span class="badge bg-warning">Unverified</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></span>
                            </td>
                            <td>
                                <div class="small">
                                    <div><i class="fas fa-gavel me-1"></i><?php echo $user['auction_count']; ?> auctions</div>
                                    <div><i class="fas fa-hand-paper me-1"></i><?php echo $user['bid_count']; ?> bids</div>
                                </div>
                            </td>
                            <td>
                                <div><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                                <small class="text-muted"><?php echo time_ago($user['created_at']); ?></small>
                            </td>
                            <td>
                                <?php if ($user['role'] !== 'super_admin' && $user['id'] !== $_SESSION['user_id']): ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                            data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="user-details.php?id=<?php echo $user['id']; ?>">
                                                <i class="fas fa-eye me-2"></i>View Details
                                            </a>
                                        </li>
                                        
                                        <?php if ($user['is_active']): ?>
                                        <li>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="deactivate">
                                                <button type="submit" class="dropdown-item text-warning" 
                                                        onclick="return confirm('Deactivate this user?')">
                                                    <i class="fas fa-user-times me-2"></i>Deactivate
                                                </button>
                                            </form>
                                        </li>
                                        <?php else: ?>
                                        <li>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="activate">
                                                <button type="submit" class="dropdown-item text-success">
                                                    <i class="fas fa-user-check me-2"></i>Activate
                                                </button>
                                            </form>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php if (!$user['is_verified']): ?>
                                        <li>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="verify">
                                                <button type="submit" class="dropdown-item text-primary">
                                                    <i class="fas fa-check-circle me-2"></i>Verify
                                                </button>
                                            </form>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <?php else: ?>
                                <span class="text-muted small">Protected</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Users pagination" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            </li>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                    <?php echo $i; ?>
                </a>
            </li>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
