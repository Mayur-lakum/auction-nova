<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect(SITE_URL . 'user/dashboard.php');
}

$page_title = 'Register';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $phone = sanitize_input($_POST['phone']);
        $date_of_birth = $_POST['date_of_birth'];
        $terms_accepted = isset($_POST['terms_accepted']);
        
        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name) || empty($date_of_birth)) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (!validate_age($date_of_birth)) {
            $error = 'You must be at least ' . MIN_AGE_REQUIREMENT . ' years old to register.';
        } elseif (!$terms_accepted) {
            $error = 'You must accept the terms and conditions.';
        } else {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Check if username or email already exists
            $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->execute([$username, $email]);
            
            if ($check_stmt->fetch()) {
                $error = 'Username or email already exists.';
            } else {
                // Hash password
                $hashed_password = password_hash($password, HASH_ALGO);
                
                // Insert new user
                $insert_query = "INSERT INTO users (username, email, password, first_name, last_name, phone, date_of_birth) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                
                if ($insert_stmt->execute([$username, $email, $hashed_password, $first_name, $last_name, $phone, $date_of_birth])) {
                    $user_id = $conn->lastInsertId();
                    
                    // Send welcome notification
                    send_notification($user_id, 'message_received', 'Welcome to ' . SITE_NAME, 
                                    'Thank you for joining our auction community! Start browsing auctions and place your first bid.');
                    
                    $success = 'Registration successful! You can now login with your credentials.';
                    
                    // Auto-login the user
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = 'user';
                    $_SESSION['is_verified'] = false;
                    
                    flash_message('success', 'Welcome to ' . SITE_NAME . ', ' . $first_name . '!');
                    redirect(SITE_URL . 'user/dashboard.php');
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="min-vh-100 d-flex align-items-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%); background-attachment: fixed;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card border-0 shadow-lg" style="border-radius: 25px; backdrop-filter: blur(15px); background: rgba(255, 255, 255, 0.9); border: 1px solid rgba(255, 255, 255, 0.2);">
                    <div class="card-body p-5">
                    <div class="text-center mb-5">
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle shadow-lg" 
                                 style="width: 90px; height: 90px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <i class="fas fa-user-plus fa-2x text-white"></i>
                            </div>
                        </div>
                        <h2 class="fw-bold mb-2" style="color: #2d3748;">Create Account</h2>
                        <p class="text-muted" style="color: #718096;">Join <?php echo SITE_NAME; ?> and start bidding today!</p>
                    </div>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label fw-semibold" style="color: #2d3748;">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                                       style="border-radius: 12px; border: 2px solid #e2e8f0; transition: all 0.3s ease; background: rgba(255, 255, 255, 0.8);"
                                       required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label fw-semibold" style="color: #2d3748;">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                                       style="border-radius: 12px; border: 2px solid #e2e8f0; transition: all 0.3s ease; background: rgba(255, 255, 255, 0.8);"
                                       required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold" style="color: #2d3748;">Username *</label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-radius: 12px 0 0 12px; border: 2px solid #e2e8f0; background: rgba(255, 255, 255, 0.8);">
                                    <i class="fas fa-user" style="color: #718096;"></i>
                                </span>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                       pattern="[a-zA-Z0-9_]{3,20}" title="3-20 characters, letters, numbers and underscore only"
                                       style="border-radius: 0 12px 12px 0; border: 2px solid #e2e8f0; border-left: none; transition: all 0.3s ease; background: rgba(255, 255, 255, 0.8);"
                                       required>
                            </div>
                            <small class="form-text" style="color: #718096;">3-20 characters, letters, numbers and underscore only</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold" style="color: #2d3748;">Email Address *</label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-radius: 12px 0 0 12px; border: 2px solid #e2e8f0; background: rgba(255, 255, 255, 0.8);">
                                    <i class="fas fa-envelope" style="color: #718096;"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                       style="border-radius: 0 12px 12px 0; border: 2px solid #e2e8f0; border-left: none; transition: all 0.3s ease; background: rgba(255, 255, 255, 0.8);"
                                       required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label fw-semibold" style="color: #2d3748;">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-radius: 12px 0 0 12px; border: 2px solid #e2e8f0; background: rgba(255, 255, 255, 0.8);">
                                    <i class="fas fa-phone" style="color: #718096;"></i>
                                </span>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                       pattern="[0-9]{10}" title="10 digit phone number"
                                       style="border-radius: 0 12px 12px 0; border: 2px solid #e2e8f0; border-left: none; transition: all 0.3s ease; background: rgba(255, 255, 255, 0.8);">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="date_of_birth" class="form-label fw-semibold" style="color: #2d3748;">Date of Birth *</label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-radius: 12px 0 0 12px; border: 2px solid #e2e8f0; background: rgba(255, 255, 255, 0.8);">
                                    <i class="fas fa-calendar" style="color: #718096;"></i>
                                </span>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                       value="<?php echo isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : ''; ?>"
                                       max="<?php echo date('Y-m-d', strtotime('-' . MIN_AGE_REQUIREMENT . ' years')); ?>"
                                       style="border-radius: 0 12px 12px 0; border: 2px solid #e2e8f0; border-left: none; transition: all 0.3s ease; background: rgba(255, 255, 255, 0.8);"
                                       required>
                            </div>
                            <small class="form-text" style="color: #718096;">You must be at least <?php echo MIN_AGE_REQUIREMENT; ?> years old</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label fw-semibold" style="color: #2d3748;">Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text" style="border-radius: 12px 0 0 12px; border: 2px solid #e2e8f0; background: rgba(255, 255, 255, 0.8);">
                                        <i class="fas fa-lock" style="color: #718096;"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           minlength="8" 
                                           style="border-radius: 0; border: 2px solid #e2e8f0; border-left: none; border-right: none; transition: all 0.3s ease; background: rgba(255, 255, 255, 0.8);"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword" 
                                            style="border-radius: 0 12px 12px 0; border: 2px solid #e2e8f0; border-left: none; background: rgba(255, 255, 255, 0.8); transition: all 0.3s ease;">
                                        <i class="fas fa-eye" style="color: #718096;"></i>
                                    </button>
                                </div>
                                <small class="form-text" style="color: #718096;">Minimum 8 characters</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label fw-semibold" style="color: #2d3748;">Confirm Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text" style="border-radius: 12px 0 0 12px; border: 2px solid #e2e8f0; background: rgba(255, 255, 255, 0.8);">
                                        <i class="fas fa-lock" style="color: #718096;"></i>
                                    </span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           minlength="8" 
                                           style="border-radius: 0 12px 12px 0; border: 2px solid #e2e8f0; border-left: none; transition: all 0.3s ease; background: rgba(255, 255, 255, 0.8);"
                                           required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms_accepted" name="terms_accepted" required>
                            <label class="form-check-label" for="terms_accepted" style="color: #4a5568;">
                                I agree to the <a href="<?php echo SITE_URL; ?>pages/terms.php" target="_blank" style="color: #667eea;">Terms of Service</a> 
                                and <a href="<?php echo SITE_URL; ?>pages/privacy.php" target="_blank" style="color: #667eea;">Privacy Policy</a> *
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-lg fw-bold text-white w-100 mb-3" 
                                style="border-radius: 15px; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-0" style="color: #718096;">Already have an account?</p>
                        <a href="login.php" class="btn btn-lg mt-2" 
                           style="border-radius: 15px; padding: 12px 30px; background: rgba(255, 255, 255, 0.8); border: 2px solid #e2e8f0; color: #667eea; text-decoration: none; transition: all 0.3s ease;">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS for Enhanced Register -->
<style>
/* Input Focus Effects */
.form-control:focus {
    border-color: #667eea !important;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25) !important;
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.95) !important;
}

/* Button Hover Effects */
.btn-lg:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6) !important;
}

.btn-lg:hover:not(.btn-primary) {
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15) !important;
    background: rgba(255, 255, 255, 0.95) !important;
}

/* Card Animation */
.card {
    animation: slideUp 0.6s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Logo Animation */
.rounded-circle {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(102, 126, 234, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(102, 126, 234, 0);
    }
}

/* Alert Enhancement */
.alert {
    border-radius: 15px;
    border: none;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .card-body {
        padding: 2rem !important;
    }
    
    .rounded-circle {
        width: 60px !important;
        height: 60px !important;
    }
    
    .rounded-circle i {
        font-size: 1.5rem !important;
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

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const termsAccepted = document.getElementById('terms_accepted').checked;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        showAlert('danger', 'Passwords do not match.');
        return false;
    }
    
    if (!termsAccepted) {
        e.preventDefault();
        showAlert('danger', 'You must accept the terms and conditions.');
        return false;
    }
});

// Username validation
document.getElementById('username').addEventListener('input', function() {
    const username = this.value;
    const regex = /^[a-zA-Z0-9_]{3,20}$/;
    
    if (username && !regex.test(username)) {
        this.setCustomValidity('Username must be 3-20 characters, letters, numbers and underscore only');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php include '../includes/footer.php'; ?>
