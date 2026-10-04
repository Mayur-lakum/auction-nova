<?php
// Test password verification
$password = 'admin123';
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "<h3>Password Test</h3>";
echo "Password: " . $password . "<br>";
echo "Hash: " . $hash . "<br>";
echo "Verification: " . (password_verify($password, $hash) ? 'SUCCESS' : 'FAILED') . "<br>";

// Generate new hash
$new_hash = password_hash($password, PASSWORD_DEFAULT);
echo "<br>New Hash: " . $new_hash . "<br>";
echo "New Verification: " . (password_verify($password, $new_hash) ? 'SUCCESS' : 'FAILED') . "<br>";

// Test database connection
require_once 'includes/functions.php';
$db = new Database();
$conn = $db->connect();

if ($conn) {
    echo "<br><h3>Database Test</h3>";
    echo "Database connection: SUCCESS<br>";
    
    // Check admin user
    $query = "SELECT * FROM users WHERE email = 'admin@auctionnova.com'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Admin user found:<br>";
        echo "ID: " . $user['id'] . "<br>";
        echo "Username: " . $user['username'] . "<br>";
        echo "Email: " . $user['email'] . "<br>";
        echo "Role: " . $user['role'] . "<br>";
        echo "Active: " . ($user['is_active'] ? 'Yes' : 'No') . "<br>";
        echo "Verified: " . ($user['is_verified'] ? 'Yes' : 'No') . "<br>";
        echo "Password Hash: " . $user['password'] . "<br>";
        echo "Password Check: " . (password_verify('admin123', $user['password']) ? 'SUCCESS' : 'FAILED') . "<br>";
    } else {
        echo "Admin user NOT found!<br>";
    }
} else {
    echo "<br>Database connection: FAILED<br>";
}
?>
