<?php
// Start session
session_start();

// Load configuration
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/auth.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Initialize database
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $user_id = $_SESSION['user_id'];
    
    // Get form data
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone'] ?? '');
    $date_of_birth = sanitize_input($_POST['date_of_birth'] ?? '');
    
    // Validate input
    if (empty($first_name) || empty($last_name) || empty($username) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        exit;
    }
    
    // Check if username or email already exists (excluding current user)
    $check_query = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute([$username, $email, $user_id]);
    
    if ($check_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit;
    }
    
    // Update user profile
    $update_query = "UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ?, phone = ?, date_of_birth = ? WHERE id = ?";
    $update_stmt = $pdo->prepare($update_query);
    $result = $update_stmt->execute([$first_name, $last_name, $username, $email, $phone, $date_of_birth, $user_id]);
    
    if ($result) {
        // Update session variables
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
    
} catch (Exception $e) {
    error_log('Error updating profile: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error updating profile']);
}
?>
