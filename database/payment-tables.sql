-- Payment and Billing Tables for Auction Nova

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    auction_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('razorpay', 'paypal', 'stripe', 'bank_transfer') DEFAULT 'razorpay',
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    razorpay_payment_id VARCHAR(255),
    razorpay_order_id VARCHAR(255),
    razorpay_signature VARCHAR(255),
    transaction_id VARCHAR(255),
    payment_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE,
    INDEX idx_user_payments (user_id),
    INDEX idx_auction_payments (auction_id),
    INDEX idx_payment_status (payment_status)
);

-- Bills/Invoices table
CREATE TABLE bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    user_id INT NOT NULL,
    auction_id INT NOT NULL,
    bill_number VARCHAR(50) UNIQUE NOT NULL,
    bill_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    bill_status ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    pdf_path VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE,
    INDEX idx_user_bills (user_id),
    INDEX idx_bill_status (bill_status),
    INDEX idx_bill_date (bill_date)
);

-- Bill items table (for detailed billing)
CREATE TABLE bill_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    item_description VARCHAR(255) NOT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE
);

-- Payment configurations table
CREATE TABLE payment_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gateway_name VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    api_key VARCHAR(255),
    api_secret VARCHAR(255),
    webhook_secret VARCHAR(255),
    test_mode BOOLEAN DEFAULT TRUE,
    config_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default Razorpay configuration
INSERT INTO payment_config (gateway_name, is_active, test_mode, config_data) VALUES 
('razorpay', TRUE, TRUE, JSON_OBJECT(
    'currency', 'INR',
    'company_name', 'Auction Nova',
    'company_logo', 'assets/images/logo.png',
    'theme_color', '#0056ff'
));

-- Add payment-related columns to auctions table
ALTER TABLE auctions ADD COLUMN payment_required BOOLEAN DEFAULT TRUE;
ALTER TABLE auctions ADD COLUMN payment_deadline_hours INT DEFAULT 48;
ALTER TABLE auctions ADD COLUMN processing_fee_percent DECIMAL(5,2) DEFAULT 2.50;

-- Add payment status to bids table
ALTER TABLE bids ADD COLUMN payment_status ENUM('not_required', 'pending', 'completed', 'failed') DEFAULT 'not_required';
ALTER TABLE bids ADD COLUMN payment_id INT NULL;
ALTER TABLE bids ADD FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL;
