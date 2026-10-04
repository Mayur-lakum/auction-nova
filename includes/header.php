<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files with error suppression
@include_once __DIR__ . '/Database.php';
@include_once __DIR__ . '/functions.php';
@include_once __DIR__ . '/auth.php';

// Define fallback is_logged_in function if database is not available
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

$bootstrap_css_href = file_exists(__DIR__ . '/../assets/css/bootstrap.min.css')
    ? (SITE_URL . 'assets/css/bootstrap.min.css')
    : 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css';

$fontawesome_css_href = file_exists(__DIR__ . '/../assets/css/all.min.css')
    ? (SITE_URL . 'assets/css/all.min.css')
    : 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Find amazing deals on unique items in our online auction. Bid and win your favorite products at the best prices.">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <script>
    window.siteUrl = "<?php echo SITE_URL; ?>";
    window.SITE_URL = window.siteUrl;
    window.currentUserId = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
    </script>
    <script>
    (function() {
        var fallbackSrc = (window.SITE_URL || "<?php echo SITE_URL; ?>") + 'assets/images/no-image.jpg';

        window.addEventListener('error', function(e) {
            var t = e && e.target;
            if (!t || !t.tagName || t.tagName.toUpperCase() !== 'IMG') return;
            if (t.dataset && t.dataset.fallbackApplied === '1') return;
            if (t.src && t.src.indexOf('assets/images/no-image.jpg') !== -1) return;

            if (t.dataset) t.dataset.fallbackApplied = '1';
            t.src = fallbackSrc;
        }, true);
    })();
    </script>
    
    <!-- Bootstrap CSS -->
    <link href="<?php echo $bootstrap_css_href; ?>" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="<?php echo $fontawesome_css_href; ?>" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Theme Color -->
    <meta name="theme-color" content="#4e73df">
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-gavel"></i>
                <span class="d-none d-md-inline"><?php echo SITE_NAME; ?></span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 rounded-pill mx-1 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active bg-primary text-white' : 'text-dark'; ?>" href="<?php echo SITE_URL; ?>">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                                        <li class="nav-item">
                        <a class="nav-link px-3 py-2 rounded-pill mx-1 <?php echo basename($_SERVER['PHP_SELF']) == 'auction-hub.php' ? 'active bg-primary text-white' : 'text-dark'; ?>" href="<?php echo SITE_URL; ?>auction-hub.php">
                            <i class="fas fa-gavel me-1"></i>Auctions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 rounded-pill mx-1 text-dark" href="<?php echo SITE_URL; ?>#about">
                            <i class="fas fa-info-circle me-1"></i>About Us
                        </a>
                    </li>
                </ul>
                
                <!-- Search Form -->
                <form class="d-flex me-3" method="GET" action="<?php echo SITE_URL; ?>auctions/">
                    <input class="form-control me-2" type="search" name="search" placeholder="Search auctions..." 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button class="btn btn-outline-light" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                
                <!-- User Menu -->
                <ul class="navbar-nav align-items-center">
                    <!-- Cart Button with Counter - Visible to all users -->
                    <li class="nav-item ms-2">
                        <a class="btn btn-cart position-relative" href="<?php echo SITE_URL; ?>cart/" aria-label="Shopping Cart">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="d-none d-sm-inline">Cart</span>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cart-counter" style="display: none;">
                                0
                                <span class="visually-hidden">items in cart</span>
                            </span>
                        </a>
                    </li>
                    <?php if (is_logged_in()): ?>
                        <!-- Watchlist Icon -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>user/watchlist.php" aria-label="Watchlist">
                                <i class="far fa-heart"></i>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="avatar-sm d-flex align-items-center justify-content-center bg-primary text-white rounded-circle me-2" style="width: 32px; height: 32px;">
                                    <?php echo strtoupper(substr(htmlspecialchars($_SESSION['username']), 0, 1)); ?>
                                </div>
                                <span class="d-none d-lg-inline"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>user/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>user/profile.php">
                                    <i class="fas fa-user-edit me-2"></i>Profile
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>user/watchlist.php">
                                    <i class="fas fa-heart me-2"></i>Watchlist
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>user/dashboard.php#my-bids">
                                    <i class="fas fa-gavel me-2"></i>My Bids
                                </a></li>
                                <?php if (is_admin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/">
                                    <i class="fas fa-cog me-2"></i>Admin Panel
                                </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>auth/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>auth/register.php">
                                <i class="fas fa-user-plus me-1"></i>Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Flash Messages -->
    <?php 
    // Display flash messages if they exist
    if (function_exists('get_flash_messages')) {
        $flash_messages = get_flash_messages();
        if (!empty($flash_messages)): ?>
            <div class="container mt-5 pt-4">
                <?php foreach ($flash_messages as $type => $message): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($type); ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php 
        endif;
    } ?>
    
    <main class="flex-grow-1"><?php // Content will be inserted here ?>
