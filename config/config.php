<?php
/**
 * AuctionNova Configuration
 */

/* =========================
   Site Configuration
========================= */

define('SITE_NAME', 'AuctionNova');
define('SITE_URL', 'http://localhost/Auction%20nova%202/');
define('SITE_EMAIL', 'admin@auctionnova.com');

/* =========================
   Development
========================= */

define('DEV_ADMIN_BYPASS', false);

/* =========================
   Database Configuration
========================= */

require_once __DIR__ . '/database.php';

/* =========================
   Security
========================= */

define('HASH_ALGO', PASSWORD_DEFAULT);
define('SESSION_TIMEOUT', 3600);

/* =========================
   Auction Settings
========================= */

define('MIN_BID_INCREMENT', 50);
define('MAX_AUCTION_DURATION', 7);
define('MIN_AGE_REQUIREMENT', 18);

/* =========================
   Upload Settings
========================= */

define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

/* =========================
   Email
========================= */

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');

/* =========================
   Timezone
========================= */

date_default_timezone_set('Asia/Kolkata');

/* =========================
   Error Reporting
========================= */

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* =========================
   Session
========================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}