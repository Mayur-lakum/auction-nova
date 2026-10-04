<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Security functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function is_admin() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin');
}

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

function format_currency($amount) {
    return '$' . number_format($amount, 2);
}

function time_ago($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } else {
        return date('M j, Y', $time);
    }
}

function time_remaining($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $time - $now;
    
    if ($diff <= 0) {
        return 'Ended';
    }
    
    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);
    $seconds = $diff % 60;
    
    if ($hours > 0) {
        return $hours . 'h ' . $minutes . 'm ' . $seconds . 's';
    } elseif ($minutes > 0) {
        return $minutes . 'm ' . $seconds . 's';
    } else {
        return $seconds . 's';
    }
}

function startAutoRefresh($interval) {
    return '';
}

// require_login() function is defined in auth.php

function require_admin() {
    if (!is_admin()) {
        header('Location: ' . SITE_URL . 'index.php');
        exit();
    }
}

function upload_image($file, $directory = 'uploads/') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $allowed_types = ALLOWED_IMAGE_TYPES;
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        return false;
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    $filename = uniqid() . '.' . $file_extension;
    $upload_path = $directory . $filename;
    
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return $filename;
    }
    
    return false;
}

function auction_image_url($image) {
    if (empty($image)) {
        return SITE_URL . 'assets/images/no-image.jpg';
    }

    $image = trim((string)$image);
    if ($image === '') {
        return SITE_URL . 'assets/images/no-image.jpg';
    }

    if (preg_match('#^https?://#i', $image)) {
        return $image;
    }

    $image = ltrim($image, '/');

    $doc_root = realpath(__DIR__ . '/..');
    if ($doc_root) {
        $local_path = null;

        if (substr($image, 0, 7) === 'assets/') {
            $local_path = $doc_root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $image);
        } elseif (substr($image, 0, 8) === 'uploads/') {
            $local_path = $doc_root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $image);
        } else {
            $local_path = $doc_root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $image);
        }

        if ($local_path && !file_exists($local_path)) {
            return SITE_URL . 'assets/images/no-image.jpg';
        }
    }

    if (substr($image, 0, 7) === 'assets/' || substr($image, 0, 8) === 'uploads/') {
        return SITE_URL . $image;
    }

    return SITE_URL . 'assets/uploads/' . $image;
}

function send_notification($user_id, $type, $title, $message, $auction_id = null) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $query = "INSERT INTO notifications (user_id, type, title, message, auction_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    return $stmt->execute([$user_id, $type, $title, $message, $auction_id]);
}

function get_user_notifications($user_id, $limit = 10) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Ensure limit is an integer and safe
    $limit = (int)$limit;
    $query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function mark_notification_read($notification_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $query = "UPDATE notifications SET is_read = TRUE WHERE id = ?";
    $stmt = $conn->prepare($query);
    return $stmt->execute([$notification_id]);
}

function log_admin_action($admin_id, $action, $target_type, $target_id = null, $description = null) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    
    $query = "INSERT INTO admin_logs (admin_id, action, target_type, target_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    return $stmt->execute([$admin_id, $action, $target_type, $target_id, $description, $ip_address]);
}

function redirect($url) {
    header('Location: ' . $url);
    exit();
}

function flash_message($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function get_flash_messages() {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function validate_age($date_of_birth) {
    $today = new DateTime();
    $dob = new DateTime($date_of_birth);
    $age = $today->diff($dob)->y;
    return $age >= MIN_AGE_REQUIREMENT;
}

function is_auction_active($auction) {
    return $auction['status'] === 'active' && strtotime($auction['end_time']) > time();
}

function can_bid($auction, $user_id) {
    return is_auction_active($auction) && $auction['seller_id'] != $user_id;
}
?>
