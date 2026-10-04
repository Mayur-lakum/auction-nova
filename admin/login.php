<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Redirect if already logged in as admin (check after form processing)
$should_redirect = false;
if (is_logged_in() && isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'super_admin'], true)) {
    $should_redirect = true;
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF
    if (!verify_csrf_token($csrf_token)) {
        $error = 'Invalid request. Please try again.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please fill all fields.';
    } else {
        $db = Database::getInstance();
        $user = $db->fetch("SELECT * FROM users WHERE email = ? AND is_active = 1", [$email]);
        
        if ($user && password_verify($password, $user['password']) && in_array($user['role'], ['admin', 'super_admin'], true)) {
            // Set admin session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_login_time'] = time();
            
            // Log admin login
            log_admin_action($_SESSION['user_id'], 'login', 'system', null, "Admin {$user['email']} logged in");
            
            header('Location: ' . SITE_URL . 'admin/dashboard.php');
            exit();
        } else {
            $error = 'Invalid admin credentials or access denied.';
        }
    }
}

// Redirect if already logged in (after form processing)
if ($should_redirect && empty($error)) {
    header('Location: ' . SITE_URL . 'admin/dashboard.php');
    exit();
}

$page_title = 'Admin Login - Secure Access';
require_once '../includes/header.php';
?>

<div class="container-fluid vh-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="row h-100">
        <!-- Left Panel - Admin Info -->
        <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-center align-items-center text-white p-5">
            <div class="text-center">
                <div class="mb-4">
                    <i class="fas fa-shield-alt fa-4x"></i>
                </div>
                <h1 class="display-4 fw-bold mb-3">Admin Portal</h1>
                <p class="lead mb-4">Secure Management Center</p>
                <div class="row text-start">
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-lock me-2"></i>
                            <span>Role-Based Access</span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-shield me-2"></i>
                            <span>Secure Authentication</span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-chart-line me-2"></i>
                            <span>Full Control Panel</span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-history me-2"></i>
                            <span>Audit Trail</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Panel - Login Form -->
        <div class="col-lg-6 d-flex flex-column justify-content-center align-items-center p-5">
            <div class="card shadow-lg border-0" style="width: 100%; max-width: 450px;">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="mb-3">
                            <i class="fas fa-user-shield fa-3x text-primary"></i>
                        </div>
                        <h2 class="fw-bold">Admin Login</h2>
                        <p class="text-muted">Enter your admin credentials</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Admin Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                       placeholder="admin@example.com" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter your password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Login as Admin
                            </button>
                        </div>
                        
                        <div class="text-center">
                            <a href="<?php echo SITE_URL; ?>auth/login.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-1"></i>
                                Back to User Login
                            </a>
                        </div>
                    </form>
                    
                    <div class="mt-4 pt-3 border-top">
                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Security Notice:</strong> This portal is restricted to authorized administrators only.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Auto-focus email field
document.getElementById('email').focus();
</script>

// Use existing log_admin_action function from includes/functions.php

<?php require_once '../includes/footer.php'; ?>
