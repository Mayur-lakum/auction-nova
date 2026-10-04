<?php
require_once 'includes/functions.php';

echo "<h2>Fix User Roles - Admin Tool</h2>";

if (!is_logged_in()) {
    echo "<p>Please login first.</p>";
    echo "<p><a href='auth/login.php'>Login</a></p>";
    exit;
}

$db = new Database();
$conn = $db->connect();

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'fix_roles') {
        // Reset all non-admin users to 'user' role
        $fix_query = "UPDATE users SET role = 'user' WHERE role NOT IN ('admin', 'super_admin') OR role IS NULL";
        $fix_stmt = $conn->prepare($fix_query);
        $fix_stmt->execute();
        
        echo "<div style='color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; margin: 10px 0;'>";
        echo "✅ Fixed user roles! All non-admin users now have 'user' role.";
        echo "</div>";
        
        // Force logout to refresh session
        echo "<p><strong>Please logout and login again to refresh your session.</strong></p>";
        echo "<p><a href='auth/logout.php' class='btn btn-warning'>Logout Now</a></p>";
    }
    
    if ($_POST['action'] === 'update_role' && isset($_POST['user_id']) && isset($_POST['new_role'])) {
        $user_id = (int)$_POST['user_id'];
        $new_role = $_POST['new_role'];
        
        if (in_array($new_role, ['user', 'admin', 'super_admin'])) {
            $update_query = "UPDATE users SET role = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->execute([$new_role, $user_id]);
            
            echo "<div style='color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; margin: 10px 0;'>";
            echo "✅ Updated user role successfully!";
            echo "</div>";
        }
    }
}

// Get all users
$users_query = "SELECT id, username, email, role, is_active, created_at FROM users ORDER BY id";
$users_stmt = $conn->prepare($users_query);
$users_stmt->execute();
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Current Session Info:</h3>";
echo "<p><strong>Your User ID:</strong> " . $_SESSION['user_id'] . "</p>";
echo "<p><strong>Your Role:</strong> " . $_SESSION['role'] . "</p>";
echo "<p><strong>Is Admin:</strong> " . (is_admin() ? 'YES' : 'NO') . "</p>";

echo "<h3>All Users in Database:</h3>";

echo "<form method='POST' style='margin: 20px 0;'>";
echo "<button type='submit' name='action' value='fix_roles' onclick='return confirm(\"This will reset all non-admin users to user role. Continue?\")' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>";
echo "🔧 Fix All User Roles";
echo "</button>";
echo "</form>";

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
echo "<tr style='background: #f8f9fa;'>";
echo "<th style='padding: 10px;'>ID</th>";
echo "<th style='padding: 10px;'>Username</th>";
echo "<th style='padding: 10px;'>Email</th>";
echo "<th style='padding: 10px;'>Current Role</th>";
echo "<th style='padding: 10px;'>Active</th>";
echo "<th style='padding: 10px;'>Created</th>";
echo "<th style='padding: 10px;'>Actions</th>";
echo "</tr>";

foreach ($users as $user) {
    $highlight = ($user['id'] == $_SESSION['user_id']) ? 'background-color: #fff3cd;' : '';
    $role_color = '';
    
    switch ($user['role']) {
        case 'super_admin':
            $role_color = 'color: #dc3545; font-weight: bold;';
            break;
        case 'admin':
            $role_color = 'color: #fd7e14; font-weight: bold;';
            break;
        case 'user':
            $role_color = 'color: #198754;';
            break;
        default:
            $role_color = 'color: #6c757d; font-style: italic;';
    }
    
    echo "<tr style='$highlight'>";
    echo "<td style='padding: 8px;'>" . $user['id'] . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($user['username']) . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($user['email']) . "</td>";
    echo "<td style='padding: 8px; $role_color'>" . ($user['role'] ?: 'NULL') . "</td>";
    echo "<td style='padding: 8px;'>" . ($user['is_active'] ? '✅' : '❌') . "</td>";
    echo "<td style='padding: 8px;'>" . date('M j, Y', strtotime($user['created_at'])) . "</td>";
    echo "<td style='padding: 8px;'>";
    
    // Role change form
    echo "<form method='POST' style='display: inline;'>";
    echo "<input type='hidden' name='user_id' value='" . $user['id'] . "'>";
    echo "<select name='new_role' style='padding: 2px;'>";
    echo "<option value='user'" . ($user['role'] === 'user' ? ' selected' : '') . ">User</option>";
    echo "<option value='admin'" . ($user['role'] === 'admin' ? ' selected' : '') . ">Admin</option>";
    echo "<option value='super_admin'" . ($user['role'] === 'super_admin' ? ' selected' : '') . ">Super Admin</option>";
    echo "</select>";
    echo "<button type='submit' name='action' value='update_role' style='padding: 2px 8px; margin-left: 5px;'>Update</button>";
    echo "</form>";
    
    echo "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Troubleshooting Steps:</h3>";
echo "<ol>";
echo "<li><strong>Check your current role:</strong> If you see admin features but your role is 'user', there's a session issue.</li>";
echo "<li><strong>Fix roles:</strong> Click 'Fix All User Roles' to reset all users to proper roles.</li>";
echo "<li><strong>Logout and login:</strong> This refreshes your session with correct role.</li>";
echo "<li><strong>Clear browser cache:</strong> Sometimes old session data gets cached.</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='index.php'>← Back to Home</a> | <a href='auth/logout.php'>Logout</a></p>";
?>
