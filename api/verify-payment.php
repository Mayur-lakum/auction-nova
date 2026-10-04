<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';
require_once '../config/payment.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login to verify payment']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$razorpay_payment_id = $_POST['razorpay_payment_id'] ?? '';
$razorpay_order_id = $_POST['razorpay_order_id'] ?? '';
$razorpay_signature = $_POST['razorpay_signature'] ?? '';

if (!$razorpay_payment_id || !$razorpay_order_id || !$razorpay_signature) {
    echo json_encode(['success' => false, 'message' => 'Missing payment verification data']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Get payment record
    $payment_query = "SELECT * FROM payments WHERE razorpay_order_id = ? AND user_id = ?";
    $payment_stmt = $conn->prepare($payment_query);
    $payment_stmt->execute([$razorpay_order_id, $_SESSION['user_id']]);
    $payment = $payment_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Payment record not found']);
        exit;
    }
    
    // Verify signature
    $api = getRazorpayApi();
    
    $attributes = [
        'razorpay_order_id' => $razorpay_order_id,
        'razorpay_payment_id' => $razorpay_payment_id,
        'razorpay_signature' => $razorpay_signature
    ];
    
    try {
        $api->utility->verifyPaymentSignature($attributes);
        $signature_valid = true;
    } catch (Exception $e) {
        $signature_valid = false;
    }
    
    if (!$signature_valid) {
        // Update payment as failed
        $update_query = "UPDATE payments SET payment_status = 'failed' WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->execute([$payment['id']]);
        
        echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
        exit;
    }
    
    // Payment verified successfully
    $conn->beginTransaction();
    
    // Update payment record
    $update_payment = "UPDATE payments SET 
                       payment_status = 'completed',
                       razorpay_payment_id = ?,
                       razorpay_signature = ?,
                       payment_date = NOW(),
                       transaction_id = ?
                       WHERE id = ?";
    $update_stmt = $conn->prepare($update_payment);
    $update_stmt->execute([$razorpay_payment_id, $razorpay_signature, $razorpay_payment_id, $payment['id']]);
    
    // Update bill status
    $update_bill = "UPDATE bills SET bill_status = 'paid' WHERE payment_id = ?";
    $bill_stmt = $conn->prepare($update_bill);
    $bill_stmt->execute([$payment['id']]);
    
    // Update winning bid payment status
    $update_bid = "UPDATE bids SET payment_status = 'completed', payment_id = ? 
                   WHERE auction_id = ? AND bidder_id = ? AND is_winning = TRUE";
    $bid_stmt = $conn->prepare($update_bid);
    $bid_stmt->execute([$payment['id'], $payment['auction_id'], $payment['user_id']]);
    
    // Get auction details for notification
    $auction_query = "SELECT title FROM auctions WHERE id = ?";
    $auction_stmt = $conn->prepare($auction_query);
    $auction_stmt->execute([$payment['auction_id']]);
    $auction = $auction_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Send notification to user
    send_notification(
        $payment['user_id'],
        'payment_completed',
        'Payment Successful',
        'Your payment for "' . $auction['title'] . '" has been completed successfully.',
        $payment['auction_id']
    );
    
    // Send notification to seller
    $seller_query = "SELECT seller_id FROM auctions WHERE id = ?";
    $seller_stmt = $conn->prepare($seller_query);
    $seller_stmt->execute([$payment['auction_id']]);
    $seller = $seller_stmt->fetch(PDO::FETCH_ASSOC);
    
    send_notification(
        $seller['seller_id'],
        'payment_received',
        'Payment Received',
        'Payment has been received for your auction "' . $auction['title'] . '".',
        $payment['auction_id']
    );
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment verified successfully',
        'payment_id' => $payment['id'],
        'transaction_id' => $razorpay_payment_id
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    error_log('Payment verification error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Payment verification failed. Please contact support.']);
}
?>
