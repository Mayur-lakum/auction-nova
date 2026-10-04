<?php
// Start session
session_start();

// Load configuration
require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/auth.php';

// Check if user is logged in and is admin
if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    echo '<div class="alert alert-danger">Access denied. Admin privileges required.</div>';
    exit;
}

// Initialize database
$db = Database::getInstance();
$pdo = $db->getConnection();

// Handle actions
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        displayUsers();
        break;
    case 'edit':
        editUser();
        break;
    case 'delete':
        deleteUser();
        break;
    default:
        displayUsers();
}

function displayUsers() {
    global $pdo;
    
    $query = "SELECT id, username, email, role, is_active, is_verified, created_at FROM users ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<div class="card">';
    echo '<div class="card-header d-flex justify-content-between align-items-center">';
    echo '<h6 class="mb-0">All Users</h6>';
    echo '<button class="btn btn-sm btn-primary" onclick="showAddUserModal()">Add User</button>';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Verified</th><th>Joined</th><th>Actions</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($users as $user) {
        $status_badge = $user['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
        $verified_badge = $user['is_verified'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-warning">No</span>';
        $role_badge = $user['role'] === 'admin' ? '<span class="badge bg-danger">Admin</span>' : '<span class="badge bg-primary">User</span>';
        
        echo '<tr>';
        echo '<td>' . $user['id'] . '</td>';
        echo '<td>' . htmlspecialchars($user['username']) . '</td>';
        echo '<td>' . htmlspecialchars($user['email']) . '</td>';
        echo '<td>' . $role_badge . '</td>';
        echo '<td>' . $status_badge . '</td>';
        echo '<td>' . $verified_badge . '</td>';
        echo '<td>' . date('M j, Y', strtotime($user['created_at'])) . '</td>';
        echo '<td>';
        echo '<div class="btn-group btn-group-sm">';
        echo '<button class="btn btn-outline-primary" onclick="editUser(' . $user['id'] . ')">Edit</button>';
        if ($user['id'] != $_SESSION['user_id']) {
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
}

function editUser() {
    // Implementation for editing user
    echo '<div class="alert alert-info">Edit user functionality coming soon...</div>';
}

function deleteUser() {
    // Implementation for deleting user
    echo '<div class="alert alert-info">Delete user functionality coming soon...</div>';
}
?>
