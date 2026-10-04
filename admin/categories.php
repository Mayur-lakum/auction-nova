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

$page_title = 'Manage Categories';
$db = new Database();
$conn = $db->connect();

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        flash_message('error', 'Invalid request');
    } else {
        $action = $_POST['action'];
        
        if ($action === 'add_category') {
            $name = sanitize_input($_POST['name']);
            $description = sanitize_input($_POST['description']);
            
            if (!empty($name)) {
                $query = "INSERT INTO categories (name, description) VALUES (?, ?)";
                $stmt = $conn->prepare($query);
                if ($stmt->execute([$name, $description])) {
                    log_admin_action($_SESSION['user_id'], 'add_category', 'category', $conn->lastInsertId(), 'Category added: ' . $name);
                    flash_message('success', 'Category added successfully');
                } else {
                    flash_message('error', 'Failed to add category');
                }
            } else {
                flash_message('error', 'Category name is required');
            }
        } elseif ($action === 'edit_category') {
            $category_id = (int)$_POST['category_id'];
            $name = sanitize_input($_POST['name']);
            $description = sanitize_input($_POST['description']);
            
            if (!empty($name)) {
                $query = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                if ($stmt->execute([$name, $description, $category_id])) {
                    log_admin_action($_SESSION['user_id'], 'edit_category', 'category', $category_id, 'Category updated: ' . $name);
                    flash_message('success', 'Category updated successfully');
                } else {
                    flash_message('error', 'Failed to update category');
                }
            }
        } elseif ($action === 'toggle_status') {
            $category_id = (int)$_POST['category_id'];
            $is_active = (int)$_POST['is_active'];
            
            $query = "UPDATE categories SET is_active = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            if ($stmt->execute([$is_active, $category_id])) {
                $status = $is_active ? 'activated' : 'deactivated';
                log_admin_action($_SESSION['user_id'], 'toggle_category', 'category', $category_id, 'Category ' . $status);
                flash_message('success', 'Category ' . $status . ' successfully');
            }
        }
        
        redirect($_SERVER['REQUEST_URI']);
    }
}

// Get categories with auction counts
$categories_query = "SELECT c.*, 
                     (SELECT COUNT(*) FROM auctions WHERE category_id = c.id) as auction_count
                     FROM categories c 
                     ORDER BY c.name";
$categories_stmt = $conn->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="fas fa-tags me-2"></i>Manage Categories
            </h2>
            <p class="text-muted mb-0">Add, edit, and manage auction categories</p>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="fas fa-plus me-2"></i>Add Category
            </button>
        </div>
    </div>

    <!-- Categories Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Categories List</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Auctions</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo $category['id']; ?></td>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($category['name']); ?></div>
                            </td>
                            <td>
                                <div class="text-muted">
                                    <?php echo htmlspecialchars(substr($category['description'], 0, 60)) . (strlen($category['description']) > 60 ? '...' : ''); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo number_format($category['auction_count']); ?> auctions</span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $category['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div><?php echo date('M j, Y', strtotime($category['created_at'])); ?></div>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                            data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <button class="dropdown-item" onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                                <i class="fas fa-edit me-2"></i>Edit
                                            </button>
                                        </li>
                                        <li>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="is_active" value="<?php echo $category['is_active'] ? 0 : 1; ?>">
                                                <button type="submit" class="dropdown-item <?php echo $category['is_active'] ? 'text-warning' : 'text-success'; ?>">
                                                    <i class="fas fa-<?php echo $category['is_active'] ? 'pause' : 'play'; ?> me-2"></i>
                                                    <?php echo $category['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="add_category">
                    
                    <div class="mb-3">
                        <label for="add_name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="add_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_description" class="form-label">Description</label>
                        <textarea class="form-control" id="add_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="edit_category">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCategory(category) {
    document.getElementById('edit_category_id').value = category.id;
    document.getElementById('edit_name').value = category.name;
    document.getElementById('edit_description').value = category.description || '';
    
    var editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    editModal.show();
}
</script>

<?php include '../includes/footer.php'; ?>
