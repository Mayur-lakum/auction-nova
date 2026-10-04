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

// Handle actions
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        displayCategories();
        break;
    case 'add':
        addCategory();
        break;
    case 'edit':
        editCategory();
        break;
    case 'delete':
        deleteCategory();
        break;
    default:
        displayCategories();
}

function displayCategories() {
    global $pdo;
    
    $query = "SELECT * FROM categories ORDER BY name";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<div class="card">';
    echo '<div class="card-header d-flex justify-content-between align-items-center">';
    echo '<h6 class="mb-0">Categories</h6>';
    echo '<button class="btn btn-sm btn-primary" onclick="showAddCategoryModal()">Add Category</button>';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($categories as $category) {
        $status_badge = $category['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
        
        echo '<tr>';
        echo '<td>' . $category['id'] . '</td>';
        echo '<td>' . htmlspecialchars($category['name']) . '</td>';
        echo '<td>' . htmlspecialchars($category['description']) . '</td>';
        echo '<td>' . $status_badge . '</td>';
        echo '<td>';
        echo '<div class="btn-group btn-group-sm">';
        echo '<button class="btn btn-outline-primary" onclick="editCategory(' . $category['id'] . ')">Edit</button>';
        echo '<button class="btn btn-outline-danger" onclick="deleteCategory(' . $category['id'] . ')">Delete</button>';
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

function addCategory() {
    echo '<div class="alert alert-info">Add category functionality coming soon...</div>';
}

function editCategory() {
    echo '<div class="alert alert-info">Edit category functionality coming soon...</div>';
}

function deleteCategory() {
    echo '<div class="alert alert-info">Delete category functionality coming soon...</div>';
}
?>
