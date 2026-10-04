<?php
// Clear all session data to start fresh
session_start();
session_destroy();

// Start new session
session_start();

echo "<h2>Admin System Test</h2>";
echo "<p>All session data has been cleared.</p>";
echo "<p>You can now test the admin login system:</p>";

echo "<h3>Test Steps:</h3>";
echo "<ol>";
echo "<li><a href='create-admin.php'>Step 1: Create Admin Account</a></li>";
echo "<li><a href='admin/login.php'>Step 2: Try Admin Login</a></li>";
echo "<li><a href='setup-admin.php'>Step 3: Check Existing Admins</a></li>";
echo "</ol>";

echo "<h3>Direct Links:</h3>";
echo "<ul>";
echo "<li><strong>Create Admin:</strong> <a href='create-admin.php'>create-admin.php</a></li>";
echo "<li><strong>Admin Login:</strong> <a href='admin/login.php'>admin/login.php</a></li>";
echo "<li><strong>Admin Dashboard:</strong> <a href='admin/dashboard.php'>admin/dashboard.php</a> (requires login)</li>";
echo "<li><strong>Check Admins:</strong> <a href='setup-admin.php'>setup-admin.php</a></li>";
echo "</ul>";

echo "<h3>Test Credentials (if you created default admin):</h3>";
echo "<ul>";
echo "<li><strong>Email:</strong> admin@auctionnova.com</li>";
echo "<li><strong>Password:</strong> admin123</li>";
echo "</ul>";

echo "<p><a href='index.php'>← Back to Home</a></p>";
?>
