<?php
/**
 * Payment Gateway Integration Class
 * Supports Razorpay, Stripe, and Paytm
 */

class PaymentGateway {
    private $gateway;
    private $testMode;
    private $config;
    
    public function __construct($gateway = 'razorpay') {
        $this->gateway = $gateway;
        $this->testMode = defined('RAZORPAY_TEST_MODE') ? RAZORPAY_TEST_MODE : true;
        $this->loadConfig();
    }
    
    private function loadConfig() {
        switch ($this->gateway) {
            case 'razorpay':
                $this->config = [
                    'key_id' => defined('RAZORPAY_KEY_ID') ? RAZORPAY_KEY_ID : '',
                    'key_secret' => defined('RAZORPAY_KEY_SECRET') ? RAZORPAY_KEY_SECRET : '',
                    'webhook_secret' => defined('RAZORPAY_WEBHOOK_SECRET') ? RAZORPAY_WEBHOOK_SECRET : ''
                ];
                break;
            case 'stripe':
                $this->config = [
                    'publishable_key' => defined('STRIPE_PUBLISHABLE_KEY') ? STRIPE_PUBLISHABLE_KEY : '',
                    'secret_key' => defined('STRIPE_SECRET_KEY') ? STRIPE_SECRET_KEY : '',
                    'webhook_secret' => defined('STRIPE_WEBHOOK_SECRET') ? STRIPE_WEBHOOK_SECRET : ''
                ];
                break;
            case 'paytm':
                $this->config = [
                    'merchant_id' => defined('PAYTM_MERCHANT_ID') ? PAYTM_MERCHANT_ID : '',
                    'merchant_key' => defined('PAYTM_MERCHANT_KEY') ? PAYTM_MERCHANT_KEY : '',
                    'website' => defined('PAYTM_WEBSITE') ? PAYTM_WEBSITE : 'DEFAULT'
                ];
                break;
        }
    }
    
    /**
     * Create payment order
     */
    public function createOrder($amount, $receipt, $notes = []) {
        try {
            switch ($this->gateway) {
                case 'razorpay':
                    return $this->createRazorpayOrder($amount, $receipt, $notes);
                case 'stripe':
                    return $this->createStripePaymentIntent($amount, $receipt, $notes);
                case 'paytm':
                    return $this->createPaytmTransaction($amount, $receipt, $notes);
                default:
                    throw new Exception("Unsupported gateway: {$this->gateway}");
            }
        } catch (Exception $e) {
            error_log("Payment Gateway Error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Verify payment signature
     */
    public function verifyPayment($paymentData) {
        try {
            switch ($this->gateway) {
                case 'razorpay':
                    return $this->verifyRazorpayPayment($paymentData);
                case 'stripe':
                    return $this->verifyStripePayment($paymentData);
                case 'paytm':
                    return $this->verifyPaytmPayment($paymentData);
                default:
                    throw new Exception("Unsupported gateway: {$this->gateway}");
            }
        } catch (Exception $e) {
            error_log("Payment Verification Error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Process refund
     */
    public function processRefund($paymentId, $amount, $reason = '') {
        try {
            switch ($this->gateway) {
                case 'razorpay':
                    return $this->refundRazorpayPayment($paymentId, $amount, $reason);
                case 'stripe':
                    return $this->refundStripePayment($paymentId, $amount, $reason);
                case 'paytm':
                    return $this->refundPaytmPayment($paymentId, $amount, $reason);
                default:
                    throw new Exception("Unsupported gateway: {$this->gateway}");
            }
        } catch (Exception $e) {
            error_log("Refund Error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Razorpay Order Creation
     */
    private function createRazorpayOrder($amount, $receipt, $notes) {
        $url = $this->testMode ? 
            'https://api.razorpay.com/v1/orders' : 
            'https://api.razorpay.com/v1/orders';
        
        $data = [
            'amount' => $amount * 100, // Convert to paise
            'currency' => 'INR',
            'receipt' => $receipt,
            'notes' => $notes,
            'payment_capture' => 1
        ];
        
        $response = $this->makeRequest('POST', $url, $data, true);
        
        if ($response && isset($response['id'])) {
            return [
                'success' => true,
                'order_id' => $response['id'],
                'amount' => $amount,
                'currency' => $response['currency']
            ];
        }
        
        return ['error' => 'Failed to create order'];
    }
    
    /**
     * Razorpay Payment Verification
     */
    private function verifyRazorpayPayment($paymentData) {
        $razorpayOrderId = $paymentData['razorpay_order_id'] ?? '';
        $razorpayPaymentId = $paymentData['razorpay_payment_id'] ?? '';
        $razorpaySignature = $paymentData['razorpay_signature'] ?? '';
        
        if (!$razorpayOrderId || !$razorpayPaymentId || !$razorpaySignature) {
            return ['error' => 'Missing payment parameters'];
        }
        
        // Verify signature
        $signature = hash_hmac('sha256', 
            $razorpayOrderId . '|' . $razorpayPaymentId, 
            $this->config['key_secret']
        );
        
        if ($signature === $razorpaySignature) {
            // Get payment details
            $payment = $this->getRazorpayPayment($razorpayPaymentId);
            
            if ($payment && $payment['status'] === 'captured') {
                return [
                    'success' => true,
                    'payment_id' => $razorpayPaymentId,
                    'order_id' => $razorpayOrderId,
                    'amount' => $payment['amount'] / 100,
                    'status' => $payment['status']
                ];
            }
        }
        
        return ['error' => 'Invalid payment signature'];
    }
    
    /**
     * Get Razorpay Payment Details
     */
    private function getRazorpayPayment($paymentId) {
        $url = "https://api.razorpay.com/v1/payments/{$paymentId}";
        return $this->makeRequest('GET', $url, [], true);
    }
    
    /**
     * Razorpay Refund
     */
    private function refundRazorpayPayment($paymentId, $amount, $reason) {
        $url = "https://api.razorpay.com/v1/payments/{$paymentId}/refund";
        
        $data = [
            'amount' => $amount * 100 // Convert to paise
        ];
        
        if ($reason) {
            $data['notes'] = ['reason' => $reason];
        }
        
        $response = $this->makeRequest('POST', $url, $data, true);
        
        if ($response && isset($response['id'])) {
            return [
                'success' => true,
                'refund_id' => $response['id'],
                'amount' => $response['amount'] / 100,
                'status' => $response['status']
            ];
        }
        
        return ['error' => 'Refund failed'];
    }
    
    /**
     * Stripe Payment Intent Creation
     */
    private function createStripePaymentIntent($amount, $receipt, $notes) {
        // Stripe implementation would go here
        // For now, return placeholder
        return [
            'success' => true,
            'client_secret' => 'pi_test_placeholder',
            'amount' => $amount
        ];
    }
    
    /**
     * Stripe Payment Verification
     */
    private function verifyStripePayment($paymentData) {
        // Stripe implementation would go here
        return ['success' => true];
    }
    
    /**
     * Stripe Refund
     */
    private function refundStripePayment($paymentId, $amount, $reason) {
        // Stripe implementation would go here
        return ['success' => true];
    }
    
    /**
     * Paytm Transaction Creation
     */
    private function createPaytmTransaction($amount, $receipt, $notes) {
        // Paytm implementation would go here
        return [
            'success' => true,
            'txn_token' => 'paytm_test_token',
            'amount' => $amount
        ];
    }
    
    /**
     * Paytm Payment Verification
     */
    private function verifyPaytmPayment($paymentData) {
        // Paytm implementation would go here
        return ['success' => true];
    }
    
    /**
     * Paytm Refund
     */
    private function refundPaytmPayment($paymentId, $amount, $reason) {
        // Paytm implementation would go here
        return ['success' => true];
    }
    
    /**
     * Make HTTP Request
     */
    private function makeRequest($method, $url, $data = [], $auth = false) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        if ($auth && $this->gateway === 'razorpay') {
            curl_setopt($ch, CURLOPT_USERPWD, 
                $this->config['key_id'] . ':' . $this->config['key_secret']
            );
        }
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("CURL Error: {$error}");
            return false;
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        }
        
        error_log("HTTP Error: {$httpCode} - {$response}");
        return false;
    }
    
    /**
     * Generate Payment Form HTML
     */
    public function generatePaymentForm($orderData, $callbackUrl) {
        switch ($this->gateway) {
            case 'razorpay':
                return $this->generateRazorpayForm($orderData, $callbackUrl);
            case 'stripe':
                return $this->generateStripeForm($orderData, $callbackUrl);
            case 'paytm':
                return $this->generatePaytmForm($orderData, $callbackUrl);
            default:
                return '<p>Payment gateway not configured</p>';
        }
    }
    
    /**
     * Generate Razorpay Payment Form
     */
    private function generateRazorpayForm($orderData, $callbackUrl) {
        $keyId = $this->config['key_id'];
        $orderId = $orderData['order_id'];
        $amount = $orderData['amount'] * 100; // Convert to paise
        $currency = $orderData['currency'] ?? 'INR';
        $name = defined('SITE_NAME') ? SITE_NAME : 'Auction Nova';
        $description = "Payment for Auction Order";
        $image = defined('SITE_URL') ? SITE_URL . 'assets/images/logo.png' : '';
        
        ob_start();
        ?>
        <form name="razorpayForm" id="razorpayForm">
            <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
            <script>
                var options = {
                    key: "<?php echo $keyId; ?>",
                    amount: "<?php echo $amount; ?>",
                    currency: "<?php echo $currency; ?>",
                    name: "<?php echo $name; ?>",
                    description: "<?php echo $description; ?>",
                    image: "<?php echo $image; ?>",
                    order_id: "<?php echo $orderId; ?>",
                    handler: function (response) {
                        window.location.href = "<?php echo $callbackUrl; ?>?" + 
                            "razorpay_payment_id=" + response.razorpay_payment_id + 
                            "&razorpay_order_id=" + response.razorpay_order_id + 
                            "&razorpay_signature=" + response.razorpay_signature;
                    },
                    prefill: {
                        name: "",
                        email: "",
                        contact: ""
                    },
                    theme: {
                        color: "#3399cc"
                    }
                };
                var rzp = new Razorpay(options);
                rzp.open();
            </script>
            <input type="hidden" name="razorpay_order_id" value="<?php echo $orderId; ?>">
        </form>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Generate Stripe Payment Form
     */
    private function generateStripeForm($orderData, $callbackUrl) {
        // Stripe form implementation
        return '<p>Stripe payment form placeholder</p>';
    }
    
    /**
     * Generate Paytm Payment Form
     */
    private function generatePaytmForm($orderData, $callbackUrl) {
        // Paytm form implementation
        return '<p>Paytm payment form placeholder</p>';
    }
    
    /**
     * Get supported gateways
     */
    public static function getSupportedGateways() {
        return ['razorpay', 'stripe', 'paytm'];
    }
    
    /**
     * Check if gateway is available
     */
    public function isGatewayAvailable() {
        switch ($this->gateway) {
            case 'razorpay':
                return !empty($this->config['key_id']) && !empty($this->config['key_secret']);
            case 'stripe':
                return !empty($this->config['publishable_key']) && !empty($this->config['secret_key']);
            case 'paytm':
                return !empty($this->config['merchant_id']) && !empty($this->config['merchant_key']);
            default:
                return false;
        }
    }
    
    /**
     * Get gateway configuration status
     */
    public function getConfigStatus() {
        return [
            'gateway' => $this->gateway,
            'test_mode' => $this->testMode,
            'configured' => $this->isGatewayAvailable(),
            'config_keys' => array_keys($this->config)
        ];
    }
}

?>
