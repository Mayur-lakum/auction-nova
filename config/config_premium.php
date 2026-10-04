<?php
// Premium Configuration for Auction Nova
// Extend the main config with premium features

// Payment Gateway Configuration
define('RAZORPAY_KEY_ID', 'rzp_test_YourKeyIdHere');
define('RAZORPAY_KEY_SECRET', 'YourSecretKeyHere');
define('RAZORPAY_WEBHOOK_SECRET', 'YourWebhookSecretHere');
define('RAZORPAY_TEST_MODE', true);

// Google Maps API Configuration
define('GOOGLE_MAPS_API_KEY', 'YourGoogleMapsApiKeyHere');
define('GOOGLE_MAPS_ENABLED', false);

// SMS Configuration (Twilio)
define('TWILIO_ACCOUNT_SID', 'YourTwilioAccountSID');
define('TWILIO_AUTH_TOKEN', 'YourTwilioAuthToken');
define('TWILIO_PHONE_NUMBER', '+1234567890');
define('TWILIO_ENABLED', false);

// Email Configuration (SendGrid)
define('SENDGRID_API_KEY', 'YourSendGridApiKey');
define('SENDGRID_ENABLED', false);

// OTP Configuration
define('OTP_LENGTH', 6);
define('OTP_EXPIRY_MINUTES', 5);
define('OTP_MAX_ATTEMPTS', 3);

// KYC Configuration
define('KYC_MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('KYC_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);
define('KYC_UPLOAD_PATH', 'uploads/kyc/');

// Shipping Configuration
define('DEFAULT_SHIPPING_COST', 0);
define('FREE_SHIPPING_THRESHOLD', 1000);
define('SHIPPING_PARTNERS', json_encode(['delhivery', 'bluedart', 'fedex']));

// Commission Configuration
define('DEFAULT_COMMISSION_PERCENTAGE', 5);
define('COMMISSION_TAX_PERCENTAGE', 18); // GST

// Notification Configuration
define('NOTIFICATION_BATCH_SIZE', 100);
define('NOTIFICATION_RETRY_ATTEMPTS', 3);

// Security Configuration
define('ENABLE_2FA', false);
define('SESSION_IP_CHECK', false);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);

// Auto-bid Configuration
define('AUTO_BID_CHECK_INTERVAL', 30); // seconds
define('AUTO_BID_MAX_INCREMENT', 1000);

// Anti-sniping Configuration
define('ANTI_SNIPE_ENABLED', true);
define('ANTI_SNIPE_MINUTES', 5);

// File Upload Configuration
define('IMAGE_QUALITY', 85);
define('THUMBNAIL_WIDTH', 300);
define('THUMBNAIL_HEIGHT', 300);

// Cache Configuration
define('CACHE_ENABLED', false);
define('CACHE_DURATION', 3600); // 1 hour

// API Configuration
define('API_RATE_LIMIT', 100); // requests per minute
define('API_TIMEOUT', 30); // seconds

// Legal Pages
define('TERMS_UPDATED', '2024-01-01');
define('PRIVACY_UPDATED', '2024-01-01');

// Premium Features Toggle
define('ENABLE_KYC', true);
define('ENABLE_PAYMENTS', true);
define('ENABLE_SHIPPING', true);
define('ENABLE_REVIEWS', true);
define('ENABLE_AUTOBID', true);
define('ENABLE_REPORTS', true);
define('ENABLE_ANALYTICS', false);

// Development/Testing
define('SKIP_PAYMENT_ENABLED', true);
define('TEST_MODE', true);
define('DEBUG_EMAILS', false);
define('LOG_ALL_REQUESTS', false);

// Cron Job Configuration
define('CRON_SECRET_KEY', 'YourCronSecretKey');
define('CRON_AUTO_END_AUCTIONS', true);
define('CRON_SEND_REMINDERS', true);
define('CRON_CLEANUP_OLD_DATA', true);

// Backup Configuration
define('BACKUP_ENABLED', false);
define('BACKUP_SCHEDULE', 'daily');
define('BACKUP_RETENTION_DAYS', 30);

// Mobile App Configuration
define('MOBILE_APP_ENABLED', false);
define('FCM_SERVER_KEY', 'YourFCMServerKey');

// Multi-language Configuration
define('DEFAULT_LANGUAGE', 'en');
define('SUPPORTED_LANGUAGES', json_encode(['en', 'hi', 'gu', 'ta', 'te']));

// Currency Configuration
define('DEFAULT_CURRENCY', 'INR');
define('SUPPORTED_CURRENCIES', json_encode(['INR', 'USD', 'EUR', 'GBP']));

// Social Login Configuration
define('GOOGLE_CLIENT_ID', 'YourGoogleClientId');
define('GOOGLE_CLIENT_SECRET', 'YourGoogleClientSecret');
define('SOCIAL_LOGIN_ENABLED', false);

// Analytics Configuration
define('GOOGLE_ANALYTICS_ID', 'GA-XXXXXXXXX');
define('GOOGLE_TAG_MANAGER_ID', 'GTM-XXXXXXX');

// CDN Configuration
define('CDN_ENABLED', false);
define('CDN_URL', 'https://cdn.auctionnova.com');

// Load Balancer Configuration
define('LOAD_BALANCER_ENABLED', false);
define('LOAD_BALANCER_IP_HEADER', 'X-Forwarded-For');

// Database Configuration for Premium Features
define('DB_SLAVE_ENABLED', false);
define('DB_SLAVE_HOST', 'localhost');
define('DB_SLAVE_NAME', 'auction_nova_slave');

// Redis Configuration
define('REDIS_ENABLED', false);
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_PASSWORD', '');

// Elasticsearch Configuration
define('ELASTICSEARCH_ENABLED', false);
define('ELASTICSEARCH_HOST', 'localhost');
define('ELASTICSEARCH_PORT', 9200);

// Error Reporting Configuration
define('ERROR_REPORTING_ENABLED', true);
define('ERROR_REPORTING_LEVEL', E_ALL);
define('ERROR_EMAIL', 'errors@auctionnova.com');

// Performance Monitoring
define('PERFORMANCE_MONITORING', false);
define('SLOW_QUERY_THRESHOLD', 1000); // milliseconds

// Legal Compliance
define('GDPR_COMPLIANT', true);
define('CCPA_COMPLIANT', true);
define('DATA_RETENTION_DAYS', 365);

// Feature Flags
define('FEATURE_LIVE_AUCTIONS', false);
define('FEATURE_AI_PRICING', false);
define('FEATURE_ESCROW', false);
define('FEATURE_MULTI_CURRENCY', false);
define('FEATURE_MULTI_LANGUAGE', false);

// Third-party Services
define('AWS_ACCESS_KEY', 'YourAWSAccessKey');
define('AWS_SECRET_KEY', 'YourAWSSecretKey');
define('AWS_REGION', 'ap-south-1');
define('AWS_S3_BUCKET', 'auctionnova-uploads');

// PDF Generation
define('PDF_LIBRARY', 'dompdf'); // or 'tcpdf'
define('PDF_HEADER_LOGO', 'assets/images/logo.png');

// Image Processing
define('IMAGE_LIBRARY', 'gd'); // or 'imagick'

// Rate Limiting
define('RATE_LIMIT_BIDDING', 10); // bids per minute
define('RATE_LIMIT_REGISTRATION', 3); // registrations per hour
define('RATE_LIMIT_LOGIN', 5); // login attempts per minute

// Fraud Detection
define('FRAUD_DETECTION_ENABLED', true);
define('FRAUD_SCORE_THRESHOLD', 70);

// Marketplace Configuration
define('MARKETPLACE_MODE', true); // true for marketplace, false for single seller
define('SELLER_VERIFICATION_REQUIRED', false);

// Subscription Configuration (Future Feature)
define('SUBSCRIPTION_ENABLED', false);
define('SUBSCRIPTION_PLANS', json_encode([
    'basic' => ['price' => 0, 'features' => ['basic_bidding', 'watchlist']],
    'premium' => ['price' => 99, 'features' => ['advanced_bidding', 'analytics', 'support']],
    'enterprise' => ['price' => 499, 'features' => ['all_features', 'api_access', 'dedicated_support']]
]));

// API Configuration for External Integrations
define('WEBHOOK_ENDPOINTS', json_encode([
    'razorpay' => SITE_URL . 'api/webhooks/razorpay',
    'payment_success' => SITE_URL . 'api/webhooks/payment-success',
    'auction_end' => SITE_URL . 'api/webhooks/auction-end'
]));

// Security Headers
define('SECURITY_HEADERS', json_encode([
    'X-Frame-Options' => 'DENY',
    'X-Content-Type-Options' => 'nosniff',
    'X-XSS-Protection' => '1; mode=block',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains'
]));

// Email Queue Configuration
define('EMAIL_QUEUE_ENABLED', false);
define('EMAIL_BATCH_SIZE', 50);

// SMS Queue Configuration
define('SMS_QUEUE_ENABLED', false);
define('SMS_BATCH_SIZE', 100);

// Background Job Configuration
define('BACKGROUND_JOBS_ENABLED', false);
define('JOB_QUEUE_TABLE', 'background_jobs');

// Monitoring and Alerts
define('MONITORING_ENABLED', false);
define('ALERT_EMAIL', 'alerts@auctionnova.com');

// Development Tools
define('DEBUG_BAR', false);
define('QUERY_LOG', false);
define('PROFILER', false);

?>
