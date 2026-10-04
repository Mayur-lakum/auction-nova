<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

echo "<h2>Admin Account Check & Setup</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Check for existing admin accounts
    echo "<h3>Checking for existing admin accounts...</h3>";
    
    $admin_check = $conn->prepare("SELECT id, username, email, role, is_active FROM users WHERE role IN ('admin', 'super_admin') AND is_active = 1");
    $admin_check->execute();
    $admins = $admin_check->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($admins)) {
        echo "<h4>✅ Found existing admin accounts:</h4>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th></tr>";
        foreach ($admins as $admin) {
            echo "<tr>";
            echo "<td>{$admin['id']}</td>";
            echo "<td>" . htmlspecialchars($admin['username']) . "</td>";
            echo "<td>" . htmlspecialchars($admin['email']) . "</td>";
            echo "<td>" . htmlspecialchars($admin['role']) . "</td>";
            echo "<td>" . ($admin['is_active'] ? 'Active' : 'Inactive') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><strong>You can use any of these accounts to login at:</strong> <a href='admin/login.php'>admin/login.php</a></p>";
    } else {
        echo "<h4>❌ No admin accounts found. Creating default admin account...</h4>";
        
        // Create default admin account
        $username = 'admin';
        $email = 'admin@auctionnova.com';
        $password = 'admin123'; // Default password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'admin';
        
        $insert_admin = $conn->prepare("
            INSERT INTO users (username, email, password, role, is_active, created_at) 
            VALUES (?, ?, ?, ?, 1, NOW())
        ");
        
        if ($insert_admin->execute([$username, $email, $hashed_password, $role])) {
            echo "<h4>✅ Admin account created successfully!</h4>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Username</th><th>Email</th><th>Password</th><th>Role</th></tr>";
            echo "<tr>";
            echo "<td>" . htmlspecialchars($username) . "</td>";
            echo "<td>" . htmlspecialchars($email) . "</td>";
            echo "<td><strong>{$password}</strong></td>";
            echo "<td>" . htmlspecialchars($role) . "</td>";
            echo "</tr>";
            echo "</table>";
            
            echo "<h3>🔐 Login Credentials:</h3>";
            echo "<ul>";
            echo "<li><strong>Email:</strong> {$email}</li>";
            echo "<li><strong>Password:</strong> {$password}</li>";
            echo "</ul>";
            
            echo "<p><strong>⚠️ Important:</strong> Please change the default password after first login!</p>";
            echo "<p><strong>Login URL:</strong> <a href='admin/login.php'>admin/login.php</a></p>";
        } else {
            echo "<h4>❌ Failed to create admin account</h4>";
            echo "<p>Please check your database connection and try again.</p>";
        }
    }
    
    // Check database structure
    echo "<h3>Database Structure Check:</h3>";
    $columns = $conn->prepare("DESCRIBE users");
    $columns->execute();
    $user_columns = $columns->fetchAll(PDO::FETCH_ASSOC);
    
    $required_columns = ['id', 'username', 'email', 'password', 'role', 'is_active'];
    $found_columns = [];
    
    foreach ($user_columns as $column) {
        if (in_array($column['Field'], $required_columns)) {
            $found_columns[] = $column['Field'];
        }
    }
    
    if (count($found_columns) === count($required_columns)) {
        echo "<p>✅ All required columns exist in users table</p>";
    } else {
        echo "<p>❌ Missing columns: " . implode(', ', array_diff($required_columns, $found_columns)) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h4>❌ Database Error:</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please ensure:</p>";
    echo "<ul>";
    echo "<li>Database server is running (MySQL/MariaDB)</li>";
    echo "<li>Database 'auction_nova' exists</li>";
    echo "<li>Database credentials in config/config.php are correct</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Back to Home</a></p>";
?>
