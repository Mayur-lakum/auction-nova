<?php
require_once '../includes/functions.php';

// Test admin login
if (isset($_POST['test_login'])) {
    $db = new Database();
    $conn = $db->connect();
    
    // Get the admin user
    $query = "SELECT id, username, email, role FROM users WHERE role IN ('admin', 'super_admin') LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        // Set session for admin
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['username'] = $admin['username'];
        $_SESSION['email'] = $admin['email'];
        $_SESSION['role'] = $admin['role'];
        $_SESSION['is_verified'] = true;
        
        echo "<p style='color: green;'>✅ Admin session created!</p>";
        echo "<p>User ID: " . $admin['id'] . "</p>";
        echo "<p>Username: " . $admin['username'] . "</p>";
        echo "<p>Role: " . $admin['role'] . "</p>";
        echo "<p><a href='index.php'>Go to Admin Dashboard</a></p>";
    } else {
        echo "<p style='color: red;'>❌ No admin user found in database</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3>Admin Login Test</h3>
                    </div>
                    <div class="card-body">
                        <p>This will create an admin session for testing purposes.</p>
                        
                        <form method="POST">
                            <button type="submit" name="test_login" class="btn btn-primary">
                                Create Admin Session
                            </button>
                        </form>
                        
                        <hr>
                        
                        <h5>Current Session:</h5>
                        <pre><?php print_r($_SESSION); ?></pre>
                        
                        <hr>
                        
                        <p><strong>Links:</strong></p>
                        <ul>
                            <li><a href="debug.php">Debug Page</a></li>
                            <li><a href="index.php">Admin Dashboard</a></li>
                            <li><a href="../auth/login.php">Normal Login</a></li>
                            <li><a href="../index.php">Main Site</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
