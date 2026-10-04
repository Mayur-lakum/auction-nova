<?php
// Start session and check authentication
session_start();
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/auth.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect(SITE_URL . 'auth/login.php');
}

$page_title = 'My Profile';
$error = '';
$success = '';

// Initialize database
$db = Database::getInstance();
$conn = $db->getConnection();

// Get user data
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_profile') {
            $first_name = sanitize_input($_POST['first_name']);
            $last_name = sanitize_input($_POST['last_name']);
            $phone = sanitize_input($_POST['phone']);
            $address = sanitize_input($_POST['address']);
            
            if (empty($first_name) || empty($last_name)) {
                $error = 'First name and last name are required.';
            } else {
                // Handle profile picture upload
                $profile_picture = $user['profile_picture'];
                if (!empty($_FILES['profile_picture']['name'])) {
                    $uploaded_file = upload_image($_FILES['profile_picture'], '../uploads/profiles/');
                    if ($uploaded_file) {
                        // Delete old profile picture if it's not default
                        if ($user['profile_picture'] !== 'default-avatar.png' && file_exists('../uploads/profiles/' . $user['profile_picture'])) {
                            unlink('../uploads/profiles/' . $user['profile_picture']);
                        }
                        $profile_picture = $uploaded_file;
                    } else {
                        $error = 'Failed to upload profile picture. Please check file format and size.';
                    }
                }
                
                if (!$error) {
                    $update_query = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ?, profile_picture = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    
                    if ($update_stmt->execute([$first_name, $last_name, $phone, $address, $profile_picture, $_SESSION['user_id']])) {
                        $success = 'Profile updated successfully!';
                        // Refresh user data
                        $user_stmt->execute([$_SESSION['user_id']]);
                        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $error = 'Failed to update profile. Please try again.';
                    }
                }
            }
        } elseif ($action === 'change_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error = 'All password fields are required.';
            } elseif (!password_verify($current_password, $user['password'])) {
                $error = 'Current password is incorrect.';
            } elseif (strlen($new_password) < 8) {
                $error = 'New password must be at least 8 characters long.';
            } elseif ($new_password !== $confirm_password) {
                $error = 'New passwords do not match.';
            } else {
                $hashed_password = password_hash($new_password, HASH_ALGO);
                $password_query = "UPDATE users SET password = ? WHERE id = ?";
                $password_stmt = $conn->prepare($password_query);
                
                if ($password_stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                    $success = 'Password changed successfully!';
                } else {
                    $error = 'Failed to change password. Please try again.';
                }
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Profile Sidebar -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <?php if ($user['profile_picture'] && $user['profile_picture'] !== 'default-avatar.png'): ?>
                        <img src="<?php echo SITE_URL; ?>uploads/profiles/<?php echo $user['profile_picture']; ?>" 
                             class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;" 
                             alt="Profile Picture">
                        <?php else: ?>
                        <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center text-white" 
                             style="width: 120px; height: 120px; font-size: 3rem;">
                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <h4 class="fw-bold"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                    <p class="text-muted mb-2">@<?php echo htmlspecialchars($user['username']); ?></p>
                    <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                    
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="fw-bold">
                                <?php
                                $bids_query = "SELECT COUNT(*) as count FROM bids WHERE bidder_id = ?";
                                $bids_stmt = $conn->prepare($bids_query);
                                $bids_stmt->execute([$user['id']]);
                                echo $bids_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                ?>
                            </div>
                            <small class="text-muted">Bids</small>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold">
                                <?php
                                $auctions_query = "SELECT COUNT(*) as count FROM auctions WHERE seller_id = ?";
                                $auctions_stmt = $conn->prepare($auctions_query);
                                $auctions_stmt->execute([$user['id']]);
                                echo $auctions_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                ?>
                            </div>
                            <small class="text-muted">Auctions</small>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold">
                                <?php
                                $watchlist_query = "SELECT COUNT(*) as count FROM watchlist WHERE user_id = ?";
                                $watchlist_stmt = $conn->prepare($watchlist_query);
                                $watchlist_stmt->execute([$user['id']]);
                                echo $watchlist_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                ?>
                            </div>
                            <small class="text-muted">Watching</small>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="text-start">
                        <small class="text-muted d-block">Member since</small>
                        <div class="fw-bold"><?php echo date('F Y', strtotime($user['created_at'])); ?></div>
                        
                        <?php if ($user['is_verified']): ?>
                        <div class="mt-2">
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i>Verified
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Profile Forms -->
        <div class="col-lg-8">
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <!-- Profile Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user me-2"></i>Profile Information
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                <small class="text-muted">Username cannot be changed</small>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="date_of_birth" 
                                   value="<?php echo $user['date_of_birth']; ?>" disabled>
                            <small class="text-muted">Date of birth cannot be changed</small>
                        </div>
                        
                        <div class="mb-4">
                            <label for="profile_picture" class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" id="profile_picture" name="profile_picture" 
                                   accept="image/*">
                            <small class="text-muted">Upload a new profile picture (JPG, PNG, GIF - Max 5MB)</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Profile
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-lock me-2"></i>Change Password
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                       minlength="8" required>
                                <small class="text-muted">Minimum 8 characters</small>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       minlength="8" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key me-2"></i>Change Password
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Account Settings -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-cog me-2"></i>Account Settings
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6>Account Status</h6>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?> me-2">
                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                                <?php if ($user['is_verified']): ?>
                                <span class="badge bg-primary">Verified</span>
                                <?php else: ?>
                                <span class="badge bg-warning">Unverified</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <h6>Account Type</h6>
                            <span class="badge bg-info"><?php echo ucfirst($user['role']); ?></span>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Quick Actions</h6>
                            <div class="d-grid gap-2">
                                <a href="<?php echo SITE_URL; ?>user/dashboard.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                                <a href="<?php echo SITE_URL; ?>user/watchlist.php" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-heart me-2"></i>My Watchlist
                                </a>
                                <a href="<?php echo SITE_URL; ?>seller/my-auctions.php" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-gavel me-2"></i>My Auctions
                                </a>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Privacy & Security</h6>
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-warning btn-sm" onclick="downloadData()">
                                    <i class="fas fa-download me-2"></i>Download My Data
                                </button>
                                <button class="btn btn-outline-danger btn-sm" onclick="deactivateAccount()">
                                    <i class="fas fa-user-times me-2"></i>Deactivate Account
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alert Container -->
<div id="alert-container" class="container mt-3"></div>

<!-- CSRF Token -->
<input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

// Profile picture preview
document.getElementById('profile_picture').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // You can add image preview functionality here
            console.log('Image selected:', file.name);
        };
        reader.readAsDataURL(file);
    }
});

function downloadData() {
    if (confirm('This will generate a file containing all your account data. Continue?')) {
        // Implement data download functionality
        showAlert('info', 'Data download feature will be implemented soon.');
    }
}

function deactivateAccount() {
    if (confirm('Are you sure you want to deactivate your account? This action can be reversed by contacting support.')) {
        // Implement account deactivation
        showAlert('warning', 'Account deactivation feature will be implemented soon.');
    }
}
</script>

<?php include '../includes/footer.php'; ?>
