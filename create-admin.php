<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$page_title = 'Create New Admin Account';
require_once 'includes/header.php';

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = sanitize_input($_POST['role'] ?? 'admin');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF
    if (!verify_csrf_token($csrf_token)) {
        $error = 'Invalid request. Please try again.';
    } elseif (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Check if email already exists
            $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check_email->execute([$email]);
            
            if ($check_email->fetch()) {
                $error = 'Email address already exists.';
            } else {
                // Check if username already exists
                $check_username = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $check_username->execute([$username]);
                
                if ($check_username->fetch()) {
                    $error = 'Username already exists.';
                } else {
                    // Create new admin account
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $insert_admin = $conn->prepare("
                        INSERT INTO users (username, email, password, role, is_active, created_at) 
                        VALUES (?, ?, ?, ?, 1, NOW())
                    ");
                    
                    if ($insert_admin->execute([$username, $email, $hashed_password, $role])) {
                        $message = "✅ Admin account created successfully!<br>";
                        $message .= "<strong>Username:</strong> " . htmlspecialchars($username) . "<br>";
                        $message .= "<strong>Email:</strong> " . htmlspecialchars($email) . "<br>";
                        $message .= "<strong>Role:</strong> " . htmlspecialchars($role) . "<br>";
                        $message .= "<br><a href='admin/login.php' class='btn btn-primary'>Login to Admin Panel</a>";
                        
                        // Clear form
                        $_POST = [];
                    } else {
                        $error = 'Failed to create admin account. Please try again.';
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-user-shield me-2"></i>
                        Create New Admin Account
                    </h3>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="username" name="username" 
                                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                               placeholder="Enter username" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                               placeholder="admin@example.com" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" 
                                               placeholder="Enter password (min 6 chars)" required minlength="6">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                               placeholder="Confirm password" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="role" class="form-label">Admin Role *</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="admin" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="super_admin" <?php echo (($_POST['role'] ?? '') === 'super_admin') ? 'selected' : ''; ?>>Super Admin</option>
                            </select>
                            <div class="form-text">
                                <strong>Admin:</strong> Regular admin access<br>
                                <strong>Super Admin:</strong> Full system access
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>
                                Create Admin Account
                            </button>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <div class="row text-center">
                        <div class="col-md-4">
                            <a href="setup-admin.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-search me-2"></i>
                                Check Existing Admins
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="admin/login.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Admin Login
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="index.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-home me-2"></i>
                                Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
