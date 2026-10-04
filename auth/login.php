<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect(SITE_URL . 'user/dashboard.php');
}

$page_title = 'Login';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        
        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $query = "SELECT id, username, email, password, role, is_active, is_verified FROM users WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                if (!$user['is_active']) {
                    $error = 'Your account has been deactivated. Please contact support.';
                } else {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['is_verified'] = $user['is_verified'];

                    // admin/access-control.php requires these for the admin panel
                    if ($user['role'] === 'admin' || $user['role'] === 'super_admin') {
                        $_SESSION['is_admin'] = true;
                        $_SESSION['admin_email'] = $user['email'];
                        $_SESSION['admin_login_time'] = time();
                    }
                    
                    // Update last login
                    $update_query = "UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->execute([$user['id']]);
                    
                    flash_message('success', 'Welcome back, ' . $user['username'] . '!');
                    
                    // Redirect based on user role
                    if ($user['role'] === 'admin' || $user['role'] === 'super_admin') {
                        $redirect_url = $_SESSION['redirect_after_login'] ?? SITE_URL . 'admin/index.php';
                    } else {
                        $redirect_url = $_SESSION['redirect_after_login'] ?? SITE_URL . 'user/dashboard.php';
                    }
                    unset($_SESSION['redirect_after_login']);
                    redirect($redirect_url);
                }
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="login-page">
    <div class="login-shell">
        <!-- Brand panel -->
        <div class="login-brand">
            <div class="login-brand-content">
                <div class="login-brand-icon">
                    <i class="fas fa-gavel"></i>
                </div>
                <h1><?php echo SITE_NAME; ?></h1>
                <p>Bid, win, and discover unique items in live auctions.</p>
                <ul class="login-brand-points">
                    <li><i class="fas fa-check-circle"></i> Real-time bidding</li>
                    <li><i class="fas fa-check-circle"></i> Secure payments</li>
                    <li><i class="fas fa-check-circle"></i> Verified sellers</li>
                </ul>
            </div>
        </div>

        <!-- Form panel -->
        <div class="login-form-panel">
            <div class="login-form-wrap">
                <h2>Welcome back</h2>
                <p class="login-subtitle">Sign in to your <?php echo SITE_NAME; ?> account</p>

                <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span><?php echo $error; ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" action="" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="login-input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="you@example.com"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                   required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="login-input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="Enter your password"
                                   required>
                            <button class="login-toggle-password" type="button" id="togglePassword" aria-label="Show password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <a href="forgot-password.php" class="login-link-muted">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn login-btn-primary w-100 mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i>Sign In
                    </button>

                    <a href="<?php echo SITE_URL; ?>admin/login.php" class="btn login-btn-secondary w-100">
                        <i class="fas fa-shield-alt me-2"></i>Login as Admin
                    </a>
                </form>

                <p class="login-signup-line">
                    Don't have an account?
                    <a href="register.php">Create Account</a>
                </p>

                <div class="login-quick-hint">
                    <i class="fas fa-info-circle me-1"></i>
                    Quick Admin Login: admin@auctionnova.com / admin123
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.login-page {
    min-height: calc(100vh - 76px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2.5rem 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-shell {
    width: 100%;
    max-width: 960px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
    animation: loginFadeUp 0.5s ease-out;
}

@keyframes loginFadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
}

.login-brand {
    background: linear-gradient(160deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    padding: 3rem 2.5rem;
    display: flex;
    align-items: center;
}

.login-brand-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
}

.login-brand h1 {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
}

.login-brand p {
    color: rgba(255, 255, 255, 0.85);
    margin-bottom: 2rem;
}

.login-brand-points {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.login-brand-points li {
    color: rgba(255, 255, 255, 0.95);
    font-size: 0.95rem;
}

.login-brand-points i {
    margin-right: 0.5rem;
    color: #a8f0c6;
}

.login-form-panel {
    padding: 3rem 2.5rem;
    display: flex;
    align-items: center;
}

.login-form-wrap {
    width: 100%;
}

.login-form-wrap h2 {
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 0.25rem;
}

.login-subtitle {
    color: #718096;
    margin-bottom: 1.5rem;
}

.login-form-wrap .form-label {
    font-weight: 600;
    color: #2d3748;
    font-size: 0.9rem;
}

.login-input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.login-input-group > i:first-child {
    position: absolute;
    left: 14px;
    color: #a0aec0;
    font-size: 0.9rem;
}

.login-input-group .form-control {
    padding-left: 2.5rem;
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    padding-top: 0.65rem;
    padding-bottom: 0.65rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.login-input-group .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
}

.login-input-group .form-control.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.12);
}

.login-toggle-password {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    color: #a0aec0;
    padding: 0;
}

.login-link-muted {
    color: #667eea;
    font-size: 0.9rem;
    font-weight: 600;
    text-decoration: none;
}

.login-link-muted:hover {
    text-decoration: underline;
}

.login-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 0.75rem;
    font-weight: 600;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.login-btn-primary:hover {
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.login-btn-secondary {
    background: #fff;
    color: #2d3748;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.75rem;
    font-weight: 600;
    transition: background 0.15s ease;
}

.login-btn-secondary:hover {
    background: #f7fafc;
    color: #2d3748;
}

.login-signup-line {
    text-align: center;
    color: #718096;
    margin: 1.5rem 0 0;
    font-size: 0.95rem;
}

.login-signup-line a {
    color: #667eea;
    font-weight: 700;
    text-decoration: none;
}

.login-signup-line a:hover {
    text-decoration: underline;
}

.login-quick-hint {
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid #edf2f7;
    text-align: center;
    color: #a0aec0;
    font-size: 0.8rem;
}

@media (max-width: 768px) {
    .login-shell {
        grid-template-columns: 1fr;
    }
    .login-brand {
        padding: 2rem;
    }
    .login-form-panel {
        padding: 2rem;
    }
}
</style>

<script>
// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function() {
    const password = document.getElementById('password');
    const icon = this.querySelector('i');

    if (password.type === 'password') {
        password.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        password.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Form validation with visual feedback
document.querySelector('form').addEventListener('submit', function(e) {
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    let isValid = true;

    email.classList.remove('is-invalid');
    password.classList.remove('is-invalid');

    if (!email.value || !email.value.includes('@')) {
        email.classList.add('is-invalid');
        isValid = false;
    }

    if (!password.value || password.value.length < 3) {
        password.classList.add('is-invalid');
        isValid = false;
    }

    if (!isValid) {
        e.preventDefault();
    }
});

// Auto-fill admin credentials (for testing) — same behavior as before
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('admin') === 'true') {
        document.getElementById('email').value = 'admin@auctionnova.com';
        document.getElementById('password').value = 'admin123';
    }
});
</script>

<?php include '../includes/footer.php'; ?>