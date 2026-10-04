<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';
require_once '../config/payment.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login to make payment']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$auction_id = (int)$_POST['auction_id'];
$user_id = $_SESSION['user_id'];

if (!$auction_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid auction ID']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Get auction details and verify user won the auction
    $auction_query = "SELECT a.*, 
                      (SELECT MAX(bid_amount) FROM bids WHERE auction_id = a.id) as winning_bid,
                      (SELECT bidder_id FROM bids WHERE auction_id = a.id AND is_winning = TRUE LIMIT 1) as winner_id
                      FROM auctions a 
                      WHERE a.id = ? AND a.status = 'ended'";
    $auction_stmt = $conn->prepare($auction_query);
    $auction_stmt->execute([$auction_id]);
    $auction = $auction_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$auction) {
        echo json_encode(['success' => false, 'message' => 'Auction not found or not ended']);
        exit;
    }
    
    if ($auction['winner_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'You did not win this auction']);
        exit;
    }
    
    // Check if payment already exists
    $payment_check = "SELECT id FROM payments WHERE auction_id = ? AND user_id = ? AND payment_status != 'failed'";
    $payment_stmt = $conn->prepare($payment_check);
    $payment_stmt->execute([$auction_id, $user_id]);
    
    if ($payment_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Payment already exists for this auction']);
        exit;
    }
    
    // Calculate payment amounts
    $baseAmount = $auction['winning_bid'];
    $amounts = calculateTotalAmount($baseAmount);
    
    // Create Razorpay order
    $api = getRazorpayApi();
    
    $orderData = [
        'receipt' => 'auction_' . $auction_id . '_' . time(),
        'amount' => convertToSmallestUnit($amounts['total_amount']),
        'currency' => PAYMENT_CURRENCY,
        'notes' => [
            'auction_id' => $auction_id,
            'user_id' => $user_id,
            'auction_title' => $auction['title']
        ]
    ];
    
    $razorpayOrder = $api->order->create($orderData);
    
    // Save payment record
    $payment_query = "INSERT INTO payments (user_id, auction_id, amount, payment_method, payment_status, razorpay_order_id) 
                      VALUES (?, ?, ?, 'razorpay', 'pending', ?)";
    $payment_stmt = $conn->prepare($payment_query);
    $payment_stmt->execute([$user_id, $auction_id, $amounts['total_amount'], $razorpayOrder['id']]);
    
    $payment_id = $conn->lastInsertId();
    
    // Create bill record
    $bill_number = generateBillNumber();
    $bill_query = "INSERT INTO bills (payment_id, user_id, auction_id, bill_number, bill_date, due_date, 
                   subtotal, tax_amount, total_amount, bill_status) 
                   VALUES (?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), ?, ?, ?, 'sent')";
    $bill_stmt = $conn->prepare($bill_query);
    $bill_stmt->execute([
        $payment_id, $user_id, $auction_id, $bill_number,
        $amounts['subtotal'], $amounts['tax_amount'], $amounts['total_amount']
    ]);
    
    $bill_id = $conn->lastInsertId();
    
    // Add bill items
    $item_query = "INSERT INTO bill_items (bill_id, item_description, quantity, unit_price, total_price) 
                   VALUES (?, ?, 1, ?, ?)";
    $item_stmt = $conn->prepare($item_query);
    
    // Main auction item
    $item_stmt->execute([
        $bill_id,
        'Auction Item: ' . $auction['title'],
        $baseAmount,
        $baseAmount
    ]);
    
    // Processing fee
    $item_stmt->execute([
        $bill_id,
        'Processing Fee (' . PROCESSING_FEE_PERCENT . '%)',
        $amounts['processing_fee'],
        $amounts['processing_fee']
    ]);
    
    echo json_encode([
        'success' => true,
        'payment_id' => $payment_id,
        'bill_id' => $bill_id,
        'bill_number' => $bill_number,
        'razorpay_order_id' => $razorpayOrder['id'],
        'razorpay_key_id' => RAZORPAY_KEY_ID,
        'amount' => convertToSmallestUnit($amounts['total_amount']),
        'currency' => PAYMENT_CURRENCY,
        'amounts' => $amounts,
        'auction' => [
            'id' => $auction['id'],
            'title' => $auction['title'],
            'winning_bid' => $baseAmount
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Payment creation error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to create payment. Please try again.']);
}
?>
