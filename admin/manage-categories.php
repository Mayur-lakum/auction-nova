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

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = sanitize_input($_POST['name'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    
    if (empty($name)) {
        $error = 'Category name is required.';
    } else {
        try {
            $stmt = $db->getConnection()->prepare("INSERT INTO categories (name, description, is_active) VALUES (?, ?, TRUE)");
            $stmt->execute([$name, $description]);
            $message = 'Category added successfully.';
        } catch (Exception $e) {
            $error = 'Failed to add category: ' . $e->getMessage();
        }
    }
}

// Handle Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $name = sanitize_input($_POST['name'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if ($id <= 0 || empty($name)) {
        $error = 'Category name is required.';
    } else {
        try {
            $stmt = $db->getConnection()->prepare("UPDATE categories SET name = ?, description = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$name, $description, $is_active, $id]);
            $message = 'Category updated successfully.';
        } catch (Exception $e) {
            $error = 'Failed to update category: ' . $e->getMessage();
        }
    }
}

// Handle Delete Category
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        // Check if category has auctions
        $check = $db->getConnection()->prepare("SELECT COUNT(*) as count FROM auctions WHERE category_id = ?");
        $check->execute([$id]);
        $result = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $error = 'Cannot delete category: It has auctions assigned to it.';
        } else {
            $stmt = $db->getConnection()->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Category deleted successfully.';
        }
    } catch (Exception $e) {
        $error = 'Failed to delete category: ' . $e->getMessage();
    }
}

// Toggle Active Status
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $db->getConnection()->prepare("UPDATE categories SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Category status updated.';
    } catch (Exception $e) {
        $error = 'Failed to update status: ' . $e->getMessage();
    }
}

// Get all categories
$categories = [];
try {
    $categories = $db->fetchAll("SELECT c.*, COUNT(a.id) as auction_count FROM categories c LEFT JOIN auctions a ON c.id = a.category_id GROUP BY c.id ORDER BY c.name");
} catch (Exception $e) {
    $error = 'Failed to load categories: ' . $e->getMessage();
}

// Get category for edit
$edit_category = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $db->getConnection()->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $edit_category = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = 'Failed to load category.';
    }
}

$page_title = 'Manage Categories';
require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="fas fa-tags me-2"></i>Manage Categories</h2>
            <p class="text-muted mb-0">Add, edit, and manage auction categories</p>
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

    <div class="row g-4">
        <!-- Add/Edit Category Form -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header fw-semibold">
                    <?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $edit_category ? 'edit' : 'add'; ?>">
                        <?php if ($edit_category): ?>
                            <input type="hidden" name="id" value="<?php echo (int)$edit_category['id']; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Category Name *</label>
                            <input type="text" class="form-control" name="name" required
                                value="<?php echo $edit_category ? htmlspecialchars($edit_category['name']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"><?php echo $edit_category ? htmlspecialchars($edit_category['description'] ?? '') : ''; ?></textarea>
                        </div>

                        <?php if ($edit_category): ?>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="is_active" id="is_active" 
                                    <?php echo ($edit_category['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        <?php endif; ?>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i><?php echo $edit_category ? 'Update Category' : 'Add Category'; ?>
                            </button>
                            <?php if ($edit_category): ?>
                                <a href="manage-categories.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Categories List -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header fw-semibold">
                    All Categories (<?php echo count($categories); ?>)
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th class="text-center">Auctions</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            No categories found. Add your first category.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <tr>
                                            <td><?php echo (int)$cat['id']; ?></td>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($cat['name']); ?></td>
                                            <td class="text-muted small">
                                                <?php echo htmlspecialchars(substr($cat['description'] ?? '', 0, 50)) . (strlen($cat['description'] ?? '') > 50 ? '...' : ''); ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary"><?php echo (int)$cat['auction_count']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <a href="?action=toggle&id=<?php echo (int)$cat['id']; ?>" 
                                                   class="badge <?php echo ($cat['is_active'] ?? 1) ? 'bg-success' : 'bg-secondary'; ?> text-decoration-none">
                                                    <?php echo ($cat['is_active'] ?? 1) ? 'Active' : 'Inactive'; ?>
                                                </a>
                                            </td>
                                            <td class="text-end">
                                                <a href="?action=edit&id=<?php echo (int)$cat['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?action=delete&id=<?php echo (int)$cat['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this category?');">
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

