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
    $title = sanitize_input($_POST['title']);
    $category_id = (int)$_POST['category_id'];
    $starting_price = (float)$_POST['starting_price'];
    $start_time = sanitize_input($_POST['start_time']);
    $end_time = sanitize_input($_POST['end_time']);
    $description = sanitize_input($_POST['description']);
    
    // Validate input
    if (empty($title) || empty($category_id) || empty($starting_price) || empty($start_time) || empty($end_time) || empty($description)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }
    
    if ($starting_price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Starting price must be greater than 0']);
        exit;
    }
    
    if (strtotime($start_time) >= strtotime($end_time)) {
        echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
        exit;
    }
    
    if (strtotime($start_time) < time()) {
        echo json_encode(['success' => false, 'message' => 'Start time cannot be in the past']);
        exit;
    }
    
    // Create auction
    $insert_query = "INSERT INTO auctions (title, category_id, seller_id, starting_price, current_price, start_time, end_time, description, status, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', NOW())";
    $insert_stmt = $pdo->prepare($insert_query);
    $result = $insert_stmt->execute([$title, $category_id, $user_id, $starting_price, $starting_price, $start_time, $end_time, $description]);
    
    if ($result) {
        $auction_id = $pdo->lastInsertId();
        
        // Handle image uploads
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $upload_dir = '../assets/uploads/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = time() . '_' . basename($_FILES['images']['name'][$key]);
                    $file_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($tmp_name, $file_path)) {
                        // Insert image record
                        $image_query = "INSERT INTO auction_images (auction_id, image_path, is_primary) VALUES (?, ?, ?)";
                        $is_primary = ($key === 0) ? 1 : 0; // First image is primary
                        $image_stmt = $pdo->prepare($image_query);
                        $image_stmt->execute([$auction_id, $file_name, $is_primary]);
                    }
                }
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Auction created successfully', 'auction_id' => $auction_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create auction']);
    }
    
} catch (Exception $e) {
    error_log('Error creating auction: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error creating auction']);
}
?>
