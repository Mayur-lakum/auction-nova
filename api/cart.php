<?php
// Enable CORS
header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN'] ?? '*');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Cart.php';

try {
    $db = new Database();
    $pdo = $db->connect();
    $cart = new Cart($pdo);

    $response = ['success' => false, 'message' => 'Invalid request'];
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'add':
                $auctionId = (int)($input['auction_id'] ?? 0);
                $quantity = (int)($input['quantity'] ?? 1);
                $response = $cart->addItem($auctionId, $quantity);
                break;
                
            case 'update':
                $auctionId = (int)($input['auction_id'] ?? 0);
                $quantity = (int)($input['quantity'] ?? 1);
                $response = $cart->updateQuantity($auctionId, $quantity);
                break;
                
            case 'remove':
                $auctionId = (int)($input['auction_id'] ?? 0);
                $response = $cart->removeItem($auctionId);
                break;
                
            case 'clear':
                $response = $cart->clear();
                break;
                
            case 'get':
                $response = [
                    'success' => true,
                    'cart' => $cart->getCart(),
                    'cart_count' => $cart->getItemCount()
                ];
                break;
                
            default:
                $response = ['success' => false, 'message' => 'Invalid action'];
                break;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Handle GET requests (e.g., for fetching cart data)
        $response = [
            'success' => true,
            'cart' => $cart->getCart(),
            'cart_count' => $cart->getItemCount()
        ];
    }
    
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    $response = [
        'success' => false,
        'message' => 'An error occurred. Please try again later.',
        'error' => $e->getMessage()
    ];
}

// Ensure no output before this
if (ob_get_level() > 0) {
    ob_clean();
}

echo json_encode($response);

// Make sure no additional output is sent
exit;
?>
