<?php
require_once 'includes/functions.php';

echo "<h2>User Debug Information</h2>";

if (is_logged_in()) {
    echo "<h3>Current Session Data:</h3>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    echo "<h3>Database User Data:</h3>";
    $db = new Database();
    $conn = $db->connect();
    
    $query = "SELECT id, username, email, role, is_active, is_verified, created_at FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<pre>";
        print_r($user);
        echo "</pre>";
        
        echo "<h3>Authentication Check Results:</h3>";
        echo "<p><strong>is_logged_in():</strong> " . (is_logged_in() ? 'TRUE' : 'FALSE') . "</p>";
        echo "<p><strong>is_admin():</strong> " . (is_admin() ? 'TRUE' : 'FALSE') . "</p>";
        echo "<p><strong>Session Role:</strong> " . ($_SESSION['role'] ?? 'NOT SET') . "</p>";
        echo "<p><strong>Database Role:</strong> " . $user['role'] . "</p>";
        
        if ($_SESSION['role'] !== $user['role']) {
            echo "<p style='color: red;'><strong>WARNING: Session role doesn't match database role!</strong></p>";
        }
    } else {
        echo "<p style='color: red;'>User not found in database!</p>";
    }
    
    echo "<h3>All Users in Database:</h3>";
    $all_users_query = "SELECT id, username, email, role, is_active FROM users ORDER BY id";
    $all_users_stmt = $conn->prepare($all_users_query);
    $all_users_stmt->execute();
    $all_users = $all_users_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Active</th></tr>";
    foreach ($all_users as $u) {
        $highlight = ($u['id'] == $_SESSION['user_id']) ? 'background-color: yellow;' : '';
        echo "<tr style='$highlight'>";
        echo "<td>" . $u['id'] . "</td>";
        echo "<td>" . $u['username'] . "</td>";
        echo "<td>" . $u['email'] . "</td>";
        echo "<td><strong>" . $u['role'] . "</strong></td>";
        echo "<td>" . ($u['is_active'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "<p>You are not logged in.</p>";
    echo "<p><a href='auth/login.php'>Login</a></p>";
}

echo "<hr>";
echo "<p><a href='auth/logout.php'>Logout</a> | <a href='index.php'>Home</a></p>";
?>
