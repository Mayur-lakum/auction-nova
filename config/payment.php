<?php
/**
 * Payment Configuration for Auction Nova
 * Razorpay Integration
 */

// Razorpay Configuration
define('RAZORPAY_KEY_ID', 'rzp_test_your_key_id_here'); // Replace with your Razorpay Key ID
define('RAZORPAY_KEY_SECRET', 'your_key_secret_here'); // Replace with your Razorpay Key Secret

// Payment Settings
define('PAYMENT_CURRENCY', 'INR');
define('PAYMENT_COMPANY_NAME', 'Auction Nova');
define('PAYMENT_THEME_COLOR', '#0056ff');

// Processing Fees
define('PROCESSING_FEE_PERCENT', 2.5); // 2.5% processing fee
define('TAX_PERCENT', 18.0); // 18% GST

// Payment Deadlines
define('PAYMENT_DEADLINE_HOURS', 48); // 48 hours to complete payment after winning

// Bill Configuration
define('BILL_PREFIX', 'AN'); // Bill number prefix: AN-2024-001
define('COMPANY_ADDRESS', 'Auction Nova Pvt Ltd
123 Business Street
Mumbai, Maharashtra 400001
India');
define('COMPANY_PHONE', '+91-9876543210');
define('COMPANY_EMAIL', 'billing@auctionnova.com');
define('COMPANY_GST', 'GST123456789');

/**
 * Get Razorpay API instance
 */
function getRazorpayApi() {
    $sdkPath = __DIR__ . '/../vendor/razorpay/razorpay/Razorpay.php';
    if (!file_exists($sdkPath)) {
        throw new Exception('Razorpay SDK not installed. Please install the Razorpay PHP SDK or enable test mode payment flow.');
    }
    
    require_once $sdkPath;
    
    return new \Razorpay\Api\Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
}

/**
 * Calculate total amount with fees and taxes
 */
function calculateTotalAmount($baseAmount) {
    $processingFee = ($baseAmount * PROCESSING_FEE_PERCENT) / 100;
    $subtotal = $baseAmount + $processingFee;
    $taxAmount = ($subtotal * TAX_PERCENT) / 100;
    $totalAmount = $subtotal + $taxAmount;
    
    return [
        'base_amount' => $baseAmount,
        'processing_fee' => $processingFee,
        'subtotal' => $subtotal,
        'tax_amount' => $taxAmount,
        'total_amount' => $totalAmount
    ];
}

/**
 * Generate unique bill number
 */
function generateBillNumber() {
    $year = date('Y');
    $month = date('m');
    
    // Get next bill number for this month
    $db = new Database();
    $conn = $db->connect();
    
    $query = "SELECT COUNT(*) + 1 as next_number FROM bills 
              WHERE bill_number LIKE ? AND YEAR(created_at) = ? AND MONTH(created_at) = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([BILL_PREFIX . '-' . $year . '-' . $month . '-%', $year, $month]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $nextNumber = str_pad($result['next_number'], 3, '0', STR_PAD_LEFT);
    
    return BILL_PREFIX . '-' . $year . '-' . $month . '-' . $nextNumber;
}

/**
 * Format currency for display
 */
function formatCurrency($amount, $currency = PAYMENT_CURRENCY) {
    switch ($currency) {
        case 'INR':
            return '₹' . number_format($amount, 2);
        case 'USD':
            return '$' . number_format($amount, 2);
        default:
            return $currency . ' ' . number_format($amount, 2);
    }
}

/**
 * Convert amount to smallest currency unit (paise for INR)
 */
function convertToSmallestUnit($amount, $currency = PAYMENT_CURRENCY) {
    switch ($currency) {
        case 'INR':
            return intval($amount * 100); // Convert to paise
        case 'USD':
            return intval($amount * 100); // Convert to cents
        default:
            return intval($amount * 100);
    }
}

/**
 * Convert from smallest currency unit to main unit
 */
function convertFromSmallestUnit($amount, $currency = PAYMENT_CURRENCY) {
    switch ($currency) {
        case 'INR':
            return $amount / 100; // Convert from paise
        case 'USD':
            return $amount / 100; // Convert from cents
        default:
            return $amount / 100;
    }
}
?>
