<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Admin Debug Page</h1>";

try {
    echo "<p>1. Including functions...</p>";
    require_once '../includes/functions.php';
    echo "<p style='color: green;'>✅ Functions included</p>";
    
    echo "<p>2. Checking session...</p>";
    echo "<p>Session status: " . session_status() . "</p>";
    echo "<p>Session data: " . print_r($_SESSION, true) . "</p>";
    
    echo "<p>3. Checking login status...</p>";
    if (function_exists('is_logged_in')) {
        $logged_in = is_logged_in();
        echo "<p>Logged in: " . ($logged_in ? 'YES' : 'NO') . "</p>";
        
        if ($logged_in) {
            echo "<p>4. Checking admin status...</p>";
            if (function_exists('is_admin')) {
                $is_admin = is_admin();
                echo "<p>Is admin: " . ($is_admin ? 'YES' : 'NO') . "</p>";
                echo "<p>User role: " . ($_SESSION['role'] ?? 'not set') . "</p>";
            } else {
                echo "<p style='color: red;'>❌ is_admin function not found</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ is_logged_in function not found</p>";
    }
    
    echo "<p>5. Testing database connection...</p>";
    $db = new Database();
    $conn = $db->connect();
    if ($conn) {
        echo "<p style='color: green;'>✅ Database connected</p>";
        
        // Test a simple query
        $test_query = "SELECT COUNT(*) as count FROM users";
        $test_stmt = $conn->prepare($test_query);
        $test_stmt->execute();
        $result = $test_stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Users in database: " . $result['count'] . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: <pre>" . $e->getTraceAsString() . "</pre></p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Try Admin Dashboard</a></p>";
echo "<p><a href='../auth/login.php'>Login Page</a></p>";
echo "<p><a href='../index.php'>Main Site</a></p>";
?>
