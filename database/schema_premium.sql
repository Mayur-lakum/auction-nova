-- Auction Nova Premium Database Schema Extensions
-- Run this script to add premium features to existing database

USE auction_nova;

-- KYC Documents table
CREATE TABLE kyc_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_type ENUM('aadhaar', 'pan', 'passport', 'driving_license') NOT NULL,
    document_number VARCHAR(50) NOT NULL,
    document_front VARCHAR(255) NOT NULL,
    document_back VARCHAR(255) NULL,
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    rejection_reason TEXT NULL,
    verified_at TIMESTAMP NULL,
    verified_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    auction_id INT NOT NULL,
    winner_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('razorpay', 'stripe', 'paytm', 'cod') NOT NULL,
    payment_status ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(255) NULL,
    razorpay_order_id VARCHAR(255) NULL,
    razorpay_payment_id VARCHAR(255) NULL,
    razorpay_signature VARCHAR(255) NULL,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    invoice_path VARCHAR(255) NULL,
    payment_date TIMESTAMP NULL,
    refund_date TIMESTAMP NULL,
    refund_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE,
    FOREIGN KEY (winner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Shipping addresses table
CREATE TABLE shipping_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_type ENUM('home', 'office', 'other') DEFAULT 'home',
    recipient_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(10) NOT NULL,
    country VARCHAR(100) DEFAULT 'India',
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    auction_id INT NOT NULL,
    winner_id INT NOT NULL,
    seller_id INT NOT NULL,
    payment_id INT NULL,
    shipping_address_id INT NOT NULL,
    order_status ENUM('pending_payment', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending_payment',
    tracking_number VARCHAR(100) NULL,
    shipping_cost DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    shipped_date TIMESTAMP NULL,
    delivered_date TIMESTAMP NULL,
    notes TEXT NULL,
    FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE,
    FOREIGN KEY (winner_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL,
    FOREIGN KEY (shipping_address_id) REFERENCES shipping_addresses(id) ON DELETE RESTRICT
);

-- Reviews and ratings table
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    auction_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewed_user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT NULL,
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (auction_id, reviewer_id)
);

-- Auto-bids table
CREATE TABLE auto_bids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    auction_id INT NOT NULL,
    user_id INT NOT NULL,
    max_bid_amount DECIMAL(10,2) NOT NULL,
    current_bid_amount DECIMAL(10,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_autobid (auction_id, user_id)
);

-- Reports table
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    reported_type ENUM('user', 'auction', 'review') NOT NULL,
    reported_id INT NOT NULL,
    reason ENUM('fraud', 'inappropriate_content', 'fake_item', 'spam', 'harassment', 'other') NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'under_review', 'resolved', 'dismissed') DEFAULT 'pending',
    admin_notes TEXT NULL,
    resolved_by INT NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Seller earnings table
CREATE TABLE seller_earnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    auction_id INT NOT NULL,
    gross_amount DECIMAL(10,2) NOT NULL,
    commission_amount DECIMAL(10,2) NOT NULL,
    net_amount DECIMAL(10,2) NOT NULL,
    payout_status ENUM('pending', 'processing', 'paid', 'failed') DEFAULT 'pending',
    payout_date TIMESTAMP NULL,
    payout_reference VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE
);

-- Commission settings table
CREATE TABLE commission_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NULL,
    min_amount DECIMAL(10,2) DEFAULT 0.00,
    max_amount DECIMAL(10,2) DEFAULT 999999.99,
    commission_percentage DECIMAL(5,2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Email templates table
CREATE TABLE email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) UNIQUE NOT NULL,
    subject VARCHAR(255) NOT NULL,
    html_content TEXT NOT NULL,
    text_content TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- SMS templates table
CREATE TABLE sms_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) UNIQUE NOT NULL,
    content TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Notification settings table
CREATE TABLE notification_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email_notifications BOOLEAN DEFAULT TRUE,
    sms_notifications BOOLEAN DEFAULT FALSE,
    push_notifications BOOLEAN DEFAULT TRUE,
    bid_outbid BOOLEAN DEFAULT TRUE,
    bid_won BOOLEAN DEFAULT TRUE,
    bid_lost BOOLEAN DEFAULT TRUE,
    auction_ending BOOLEAN DEFAULT TRUE,
    auction_ended BOOLEAN DEFAULT TRUE,
    payment_reminder BOOLEAN DEFAULT TRUE,
    order_updates BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_notification_settings (user_id)
);

-- API keys table
CREATE TABLE api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(50) NOT NULL,
    key_name VARCHAR(100) NOT NULL,
    key_value TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_api_key (service_name, key_name)
);

-- System settings table
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add new columns to existing tables
ALTER TABLE users ADD COLUMN kyc_verified BOOLEAN DEFAULT FALSE AFTER is_verified;
ALTER TABLE users ADD COLUMN phone_verified BOOLEAN DEFAULT FALSE AFTER phone;
ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT FALSE AFTER email;
ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER updated_at;
ALTER TABLE users ADD COLUMN login_count INT DEFAULT 0 AFTER last_login;

ALTER TABLE auctions ADD COLUMN buy_now_enabled BOOLEAN DEFAULT FALSE AFTER buy_now_price;
ALTER TABLE auctions ADD COLUMN reserve_price DECIMAL(10,2) NULL AFTER buy_now_enabled;
ALTER TABLE auctions ADD COLUMN shipping_cost DECIMAL(10,2) DEFAULT 0.00 AFTER reserve_price;
ALTER TABLE auctions ADD COLUMN location_city VARCHAR(100) NULL AFTER shipping_cost;
ALTER TABLE auctions ADD COLUMN location_state VARCHAR(100) NULL AFTER location_city;
ALTER TABLE auctions ADD COLUMN auto_extend BOOLEAN DEFAULT TRUE AFTER location_state;
ALTER TABLE auctions ADD COLUMN extend_minutes INT DEFAULT 5 AFTER auto_extend;

ALTER TABLE bids ADD COLUMN is_auto_bid BOOLEAN DEFAULT FALSE AFTER is_winning;
ALTER TABLE bids ADD COLUMN ip_address VARCHAR(45) NULL AFTER is_auto_bid;

-- Insert default commission settings
INSERT INTO commission_settings (category_id, min_amount, max_amount, commission_percentage) VALUES
(NULL, 0.00, 999.99, 5.00),
(NULL, 1000.00, 9999.99, 4.00),
(NULL, 10000.00, 999999.99, 3.00),
(1, 0.00, 999999.99, 4.00), -- Electronics
(2, 0.00, 999999.99, 5.00), -- Vehicles
(4, 0.00, 999999.99, 3.00); -- Jewelry

-- Insert default email templates
INSERT INTO email_templates (template_name, subject, html_content, text_content) VALUES
('bid_placed', 'Your bid has been placed successfully!', 
'<h3>Dear {user_name},</h3><p>Your bid of ₹{bid_amount} has been successfully placed on <strong>{auction_title}</strong>.</p><p>Current highest bid: ₹{current_price}</p><p>Auction ends: {end_time}</p><p>Good luck!</p>',
'Dear {user_name}, Your bid of ₹{bid_amount} has been successfully placed on {auction_title}. Current highest bid: ₹{current_price}. Auction ends: {end_time}. Good luck!'),

('bid_outbid', 'You have been outbid!', 
'<h3>Dear {user_name},</h3><p>You have been outbid on <strong>{auction_title}</strong>.</p><p>New highest bid: ₹{current_price}</p><p>Place a new bid to win this auction!</p>',
'Dear {user_name}, You have been outbid on {auction_title}. New highest bid: ₹{current_price}. Place a new bid to win this auction!'),

('auction_won', 'Congratulations! You won the auction!', 
'<h3>Dear {user_name},</h3><p>Congratulations! You won <strong>{auction_title}</strong> with a bid of ₹{winning_amount}.</p><p>Please proceed to payment to complete your purchase.</p>',
'Dear {user_name}, Congratulations! You won {auction_title} with a bid of ₹{winning_amount}. Please proceed to payment to complete your purchase.');

-- Insert default SMS templates
INSERT INTO sms_templates (template_name, content) VALUES
('bid_placed', 'Auction Nova: Your bid of ₹{bid_amount} placed successfully on {auction_title}. Current highest: ₹{current_price}'),
('bid_outbid', 'Auction Nova: You have been outbid on {auction_title}. New highest bid: ₹{current_price}'),
('auction_won', 'Auction Nova: Congratulations! You won {auction_title} for ₹{winning_amount}. Please complete payment.'),
('otp_login', 'Auction Nova: Your OTP is {otp}. Valid for 5 minutes.'),
('otp_register', 'Auction Nova: Your verification OTP is {otp}. Valid for 10 minutes.');

-- Insert default API keys
INSERT INTO api_keys (service_name, key_name, key_value) VALUES
('razorpay', 'key_id', 'rzp_test_'),
('razorpay', 'key_secret', ''),
('google_maps', 'api_key', ''),
('twilio', 'account_sid', ''),
('twilio', 'auth_token', ''),
('twilio', 'phone_number', ''),
('sendgrid', 'api_key', '');

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('site_maintenance', 'false', 'boolean', 'Site under maintenance mode', false),
('enable_skip_payment', 'true', 'boolean', 'Enable skip payment for testing', false),
('min_bid_increment', '50', 'number', 'Minimum bid increment amount', true),
('max_auction_duration', '7', 'number', 'Maximum auction duration in days', true),
('auto_extend_time', '5', 'number', 'Auto extend auction time in minutes', true),
('commission_default', '5', 'number', 'Default commission percentage', false),
('email_notifications', 'true', 'boolean', 'Enable email notifications', false),
('sms_notifications', 'false', 'boolean', 'Enable SMS notifications', false),
('razorpay_test_mode', 'true', 'boolean', 'Razorpay test mode', false),
('google_maps_enabled', 'false', 'boolean', 'Enable Google Maps integration', false);

-- Create indexes for better performance
CREATE INDEX idx_auctions_end_time ON auctions(end_time);
CREATE INDEX idx_auctions_status ON auctions(status);
CREATE INDEX idx_auctions_featured ON auctions(is_featured);
CREATE INDEX idx_bids_auction_time ON bids(auction_id, bid_time DESC);
CREATE INDEX idx_notifications_user_unread ON notifications(user_id, is_read);
CREATE INDEX idx_watchlist_user ON watchlist(user_id);
CREATE INDEX idx_payments_status ON payments(payment_status);
CREATE INDEX idx_orders_status ON orders(order_status);
CREATE INDEX idx_reviews_rating ON reviews(rating);
