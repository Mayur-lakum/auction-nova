<?php
// Start session
session_start();

// Load configuration
require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/auth.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'], true)) {
    echo '<div class="alert alert-danger">Access denied. Admin privileges required.</div>';
    exit;
}

// Initialize database
$db = Database::getInstance();
$pdo = $db->getConnection();

function admin_log($action, $target_type, $target_id, $description) {
    global $pdo;
    $admin_id = (int)($_SESSION['user_id'] ?? 0);
    if ($admin_id <= 0) {
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$admin_id, $action, $target_type, $target_id, $description, $ip]);
}

// Handle actions
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        displayUsers();
        break;
    case 'add':
        addUser();
        break;
    case 'edit':
        editUser();
        break;
    case 'delete':
        deleteUser();
        break;
    case 'toggle_status':
        toggleUserStatus();
        break;
    default:
        displayUsers();
}

function displayUsers() {
    global $pdo;
    
    $query = "SELECT id, username, email, first_name, last_name, role, is_active, is_verified, created_at FROM users ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<div class="card">';
    echo '<div class="card-header d-flex justify-content-between align-items-center">';
    echo '<h6 class="mb-0">User Management</h6>';
    echo '<button class="btn btn-sm btn-primary" onclick="showAddUserModal()">Add User</button>';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped table-hover">';
    echo '<thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Name</th><th>Role</th><th>Status</th><th>Verified</th><th>Joined</th><th>Actions</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($users as $user) {
        $status_badge = $user['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
        $verified_badge = $user['is_verified'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-warning">No</span>';
        $role_badge = $user['role'] === 'admin' ? '<span class="badge bg-danger">Admin</span>' : '<span class="badge bg-primary">User</span>';
        
        echo '<tr>';
        echo '<td>' . $user['id'] . '</td>';
        echo '<td>' . htmlspecialchars($user['username']) . '</td>';
        echo '<td>' . htmlspecialchars($user['email']) . '</td>';
        echo '<td>' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</td>';
        echo '<td>' . $role_badge . '</td>';
        echo '<td>' . $status_badge . '</td>';
        echo '<td>' . $verified_badge . '</td>';
        echo '<td>' . date('M j, Y', strtotime($user['created_at'])) . '</td>';
        echo '<td>';
        echo '<div class="btn-group btn-group-sm">';
        echo '<button class="btn btn-outline-primary" onclick="editUser(' . $user['id'] . ')">Edit</button>';
        if ($user['id'] != $_SESSION['user_id']) {
            echo '<button class="btn btn-outline-' . ($user['is_active'] ? 'warning' : 'success') . '" onclick="toggleUserStatus(' . $user['id'] . ')">' . ($user['is_active'] ? 'Deactivate' : 'Activate') . '</button>';
            echo '<button class="btn btn-outline-danger" onclick="deleteUser(' . $user['id'] . ')">Delete</button>';
        }
        echo '</div>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // Add User Modal
    echo '<div class="modal fade" id="addUserModal" tabindex="-1">';
    echo '<div class="modal-dialog">';
    echo '<div class="modal-content">';
    echo '<div class="modal-header">';
    echo '<h5 class="modal-title">Add New User</h5>';
    echo '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>';
    echo '</div>';
    echo '<div class="modal-body">';
    echo '<form id="addUserForm">';
    echo '<div class="mb-3"><label class="form-label">Username</label><input type="text" class="form-control" name="username" required></div>';
    echo '<div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email" required></div>';
    echo '<div class="mb-3"><label class="form-label">First Name</label><input type="text" class="form-control" name="first_name" required></div>';
    echo '<div class="mb-3"><label class="form-label">Last Name</label><input type="text" class="form-control" name="last_name" required></div>';
    echo '<div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" name="password" required></div>';
    echo '<div class="mb-3"><label class="form-label">Role</label><select class="form-select" name="role"><option value="user">User</option><option value="admin">Admin</option></select></div>';
    echo '</form>';
    echo '</div>';
    echo '<div class="modal-footer">';
    echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>';
    echo '<button type="button" class="btn btn-primary" onclick="addUser()">Add User</button>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

function addUser() {
    global $pdo;
    
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $password = $_POST['password'];
    $role = sanitize_input($_POST['role']);
    
    // Validate input
    if (empty($username) || empty($email) || empty($first_name) || empty($last_name) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }
    
    // Check if username or email already exists
    $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute([$username, $email]);
    
    if ($check_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $insert_query = "INSERT INTO users (username, email, first_name, last_name, password, role, is_active, is_verified, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 1, 1, NOW())";
    $insert_stmt = $pdo->prepare($insert_query);
    $result = $insert_stmt->execute([$username, $email, $first_name, $last_name, $hashed_password, $role]);
    
    if ($result) {
        $new_id = (int)$pdo->lastInsertId();
        admin_log('user_create', 'user', $new_id, 'Created user ' . $username . ' (' . $email . ') role=' . $role);
        echo json_encode(['success' => true, 'message' => 'User added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add user']);
    }
}

function editUser() {
    echo '<div class="alert alert-info">Edit user functionality coming soon...</div>';
}

function deleteUser() {
    global $pdo;
    
    $user_id = (int)$_POST['user_id'];
    
    if ($user_id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
        exit;
    }
    
    // Delete user
    $delete_query = "DELETE FROM users WHERE id = ?";
    $delete_stmt = $pdo->prepare($delete_query);
    $result = $delete_stmt->execute([$user_id]);
    
    if ($result) {
        admin_log('user_delete', 'user', $user_id, 'Deleted user id=' . $user_id);
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
    }
}

function toggleUserStatus() {
    global $pdo;
    
    $user_id = (int)$_POST['user_id'];
    
    if ($user_id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Cannot change your own status']);
        exit;
    }
    
    // Get current status
    $status_query = "SELECT is_active FROM users WHERE id = ?";
    $status_stmt = $pdo->prepare($status_query);
    $status_stmt->execute([$user_id]);
    $user = $status_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Toggle status
    $new_status = $user['is_active'] ? 0 : 1;
    $update_query = "UPDATE users SET is_active = ? WHERE id = ?";
    $update_stmt = $pdo->prepare($update_query);
    $result = $update_stmt->execute([$new_status, $user_id]);
    
    if ($result) {
        admin_log('user_toggle_status', 'user', $user_id, 'Set is_active=' . $new_status . ' for user id=' . $user_id);
        echo json_encode(['success' => true, 'message' => 'User status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            addUser();
            break;
        case 'delete':
            deleteUser();
            break;
        case 'toggle_status':
            toggleUserStatus();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
?>
