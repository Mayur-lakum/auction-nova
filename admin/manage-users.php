<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/functions.php';

// Use the same admin authentication as dashboard.php
$SINGLE_ADMIN_EMAIL = 'admin@auctionnova.com';
if (!isset($_SESSION['admin_email']) || $_SESSION['admin_email'] !== $SINGLE_ADMIN_EMAIL) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();
$message = '';
$error = '';

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $role = sanitize_input($_POST['role'] ?? 'user');
    
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Username, email, and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        try {
            // Check if email exists
            $check = $db->getConnection()->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'Email already exists.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->getConnection()->prepare("INSERT INTO users (username, email, password, first_name, last_name, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, TRUE, NOW())");
                $stmt->execute([$username, $email, $hashed_password, $first_name, $last_name, $role]);
                $message = 'User added successfully.';
            }
        } catch (Exception $e) {
            $error = 'Failed to add user: ' . $e->getMessage();
        }
    }
}

// Handle Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $role = sanitize_input($_POST['role'] ?? 'user');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if ($id <= 0 || empty($username) || empty($email)) {
        $error = 'Username and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        try {
            // Check if email exists for another user
            $check = $db->getConnection()->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->execute([$email, $id]);
            if ($check->fetch()) {
                $error = 'Email already exists for another user.';
            } else {
                $stmt = $db->getConnection()->prepare("UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, role = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$username, $email, $first_name, $last_name, $role, $is_active, $id]);
                $message = 'User updated successfully.';
            }
        } catch (Exception $e) {
            $error = 'Failed to update user: ' . $e->getMessage();
        }
    }
}

// Handle Delete User
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        // Prevent deleting own account
        if ($id === (int)$_SESSION['user_id']) {
            $error = 'Cannot delete your own account.';
        } else {
            $stmt = $db->getConnection()->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'User deleted successfully.';
        }
    } catch (Exception $e) {
        $error = 'Failed to delete user: ' . $e->getMessage();
    }
}

// Toggle Active Status
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        // Prevent disabling own account
        if ($id === (int)$_SESSION['user_id']) {
            $error = 'Cannot disable your own account.';
        } else {
            $stmt = $db->getConnection()->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'User status updated.';
        }
    } catch (Exception $e) {
        $error = 'Failed to update status: ' . $e->getMessage();
    }
}

// Search and filter
$search = sanitize_input($_GET['search'] ?? '');
$role_filter = sanitize_input($_GET['role'] ?? '');

// Build query
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM auctions WHERE seller_id = u.id) as auction_count,
          (SELECT COUNT(*) FROM bids WHERE bidder_id = u.id) as bid_count 
          FROM users u WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $search_term = "%$search%";
    $params = [$search_term, $search_term, $search_term, $search_term];
}

if (!empty($role_filter)) {
    $query .= " AND u.role = ?";
    $params[] = $role_filter;
}

$query .= " ORDER BY u.created_at DESC";

$users = [];
try {
    $stmt = $db->getConnection()->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Failed to load users: ' . $e->getMessage();
}

// Get user for edit
$edit_user = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $db->getConnection()->prepare("SELECT id, username, email, first_name, last_name, role, is_active FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = 'Failed to load user.';
    }
}

$page_title = 'Manage Users';
require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="fas fa-users me-2"></i>Manage Users</h2>
            <p class="text-muted mb-0">Add, edit, and manage user accounts</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="dashboard.php">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" 
                           placeholder="Search by name, email, or username..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Role</label>
                    <select class="form-select" name="role">
                        <option value="">All Roles</option>
                        <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="super_admin" <?php echo $role_filter === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Search
                    </button>
                    <a href="manage-users.php" class="btn btn-outline-secondary">
                        <i class="fas fa-refresh me-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <!-- Add/Edit User Form -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header fw-semibold">
                    <?php echo $edit_user ? 'Edit User' : 'Add New User'; ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $edit_user ? 'edit' : 'add'; ?>">
                        <?php if ($edit_user): ?>
                            <input type="hidden" name="id" value="<?php echo (int)$edit_user['id']; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Username *</label>
                            <input type="text" class="form-control" name="username" required
                                value="<?php echo $edit_user ? htmlspecialchars($edit_user['username']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" required
                                value="<?php echo $edit_user ? htmlspecialchars($edit_user['email']) : ''; ?>">
                        </div>

                        <?php if (!$edit_user): ?>
                            <div class="mb-3">
                                <label class="form-label">Password *</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name"
                                    value="<?php echo $edit_user ? htmlspecialchars($edit_user['first_name'] ?? '') : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name"
                                    value="<?php echo $edit_user ? htmlspecialchars($edit_user['last_name'] ?? '') : ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role">
                                <option value="user" <?php echo ($edit_user && $edit_user['role'] === 'user') ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo ($edit_user && $edit_user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="super_admin" <?php echo ($edit_user && $edit_user['role'] === 'super_admin') ? 'selected' : ''; ?>>Super Admin</option>
                            </select>
                        </div>

                        <?php if ($edit_user): ?>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="is_active" id="is_active" 
                                    <?php echo ($edit_user['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        <?php endif; ?>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i><?php echo $edit_user ? 'Update User' : 'Add User'; ?>
                            </button>
                            <?php if ($edit_user): ?>
                                <a href="manage-users.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Users List -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header fw-semibold">
                    All Users (<?php echo count($users); ?>)
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th class="text-center">Auctions</th>
                                    <th class="text-center">Bids</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            No users found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></div>
                                                <div class="small text-muted">@<?php echo htmlspecialchars($user['username']); ?> · <?php echo htmlspecialchars($user['email']); ?></div>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $user['role'] === 'admin' || $user['role'] === 'super_admin' ? 'bg-danger' : 'bg-info'; ?>">
                                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $user['role']))); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary"><?php echo (int)$user['auction_count']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary"><?php echo (int)$user['bid_count']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <a href="?action=toggle&id=<?php echo (int)$user['id']; ?>" 
                                                   class="badge <?php echo ($user['is_active'] ?? 1) ? 'bg-success' : 'bg-secondary'; ?> text-decoration-none">
                                                    <?php echo ($user['is_active'] ?? 1) ? 'Active' : 'Inactive'; ?>
                                                </a>
                                            </td>
                                            <td class="text-end">
                                                <a href="?action=edit&id=<?php echo (int)$user['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?action=delete&id=<?php echo (int)$user['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this user?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

