    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-gavel me-2"></i><?php echo SITE_NAME; ?>
                    </h5>
                    <p class="text-muted">
                        Your trusted online auction platform. Discover unique items, 
                        place bids, and win amazing deals from sellers worldwide.
                    </p>
                    <div class="social-links">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL); ?>" class="text-light me-3" target="_blank" rel="noopener noreferrer" aria-label="Share on Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(SITE_URL); ?>&text=<?php echo urlencode(SITE_NAME); ?>" class="text-light me-3" target="_blank" rel="noopener noreferrer" aria-label="Share on X"><i class="fab fa-twitter"></i></a>
                        <a href="https://www.instagram.com/" class="text-light me-3" target="_blank" rel="noopener noreferrer" aria-label="Open Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(SITE_URL); ?>" class="text-light" target="_blank" rel="noopener noreferrer" aria-label="Share on LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>" class="text-muted text-decoration-none">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>auctions/" class="text-muted text-decoration-none">Browse Auctions</a></li>
                        <li><a href="<?php echo SITE_URL; ?>seller/create-auction.php" class="text-muted text-decoration-none">Sell Item</a></li>
                        <li><a href="<?php echo SITE_URL; ?>pages/how-it-works.php" class="text-muted text-decoration-none">How It Works</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Categories</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>auctions/?category=1" class="text-muted text-decoration-none">Electronics</a></li>
                        <li><a href="<?php echo SITE_URL; ?>auctions/?category=2" class="text-muted text-decoration-none">Vehicles</a></li>
                        <li><a href="<?php echo SITE_URL; ?>auctions/?category=3" class="text-muted text-decoration-none">Antiques</a></li>
                        <li><a href="<?php echo SITE_URL; ?>auctions/?category=4" class="text-muted text-decoration-none">Jewelry</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Support</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>pages/faq.php" class="text-muted text-decoration-none">FAQ</a></li>
                        <li><a href="<?php echo SITE_URL; ?>pages/contact.php" class="text-muted text-decoration-none">Contact Us</a></li>
                        <li><a href="<?php echo SITE_URL; ?>pages/terms.php" class="text-muted text-decoration-none">Terms of Service</a></li>
                        <li><a href="<?php echo SITE_URL; ?>pages/privacy.php" class="text-muted text-decoration-none">Privacy Policy</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Contact Info</h6>
                    <ul class="list-unstyled text-muted">
                        <li><i class="fas fa-envelope me-2"></i><?php echo SITE_EMAIL; ?></li>
                        <li><i class="fas fa-phone me-2"></i>+91 98765 43210</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i>Mumbai, India</li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0">
                        &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">
                        Made with <i class="fas fa-heart text-danger"></i> in India
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <?php
    $bootstrap_js_src = file_exists(__DIR__ . '/../assets/js/bootstrap.bundle.min.js')
        ? (SITE_URL . 'assets/js/bootstrap.bundle.min.js')
        : 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js';

    $jquery_js_src = file_exists(__DIR__ . '/../assets/js/jquery-3.7.0.min.js')
        ? (SITE_URL . 'assets/js/jquery-3.7.0.min.js')
        : 'https://code.jquery.com/jquery-3.7.0.min.js';

    $main_js_src = SITE_URL . 'assets/js/main.js';
    $main_js_path = __DIR__ . '/../assets/js/main.js';
    if (file_exists($main_js_path)) {
        $main_js_src .= '?v=' . filemtime($main_js_path);
    }

    $sse_client_src = SITE_URL . 'assets/js/sse-client.js';
    $sse_client_path = __DIR__ . '/../assets/js/sse-client.js';
    if (file_exists($sse_client_path)) {
        $sse_client_src .= '?v=' . filemtime($sse_client_path);
    }

    $sse_user_sync_src = SITE_URL . 'assets/js/sse-user-auction-sync.js';
    $sse_user_sync_path = __DIR__ . '/../assets/js/sse-user-auction-sync.js';
    if (file_exists($sse_user_sync_path)) {
        $sse_user_sync_src .= '?v=' . filemtime($sse_user_sync_path);
    }
    ?>

    <!-- Bootstrap JS -->
    <script src="<?php echo $bootstrap_js_src; ?>"></script>
    <!-- jQuery -->
    <script src="<?php echo $jquery_js_src; ?>"></script>
    <!-- Custom JS -->
    <script src="<?php echo $main_js_src; ?>"></script>
    <!-- SSE Realtime Client -->
    <script src="<?php echo $sse_client_src; ?>"></script>
    <!-- SSE User Auction Sync -->
    <script src="<?php echo $sse_user_sync_src; ?>"></script>
    
    <!-- Page-specific scripts -->
    <?php if (isset($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <?php
            $page_script_src = SITE_URL . $script;
            $page_script_path = __DIR__ . '/../' . ltrim($script, '/');
            if (file_exists($page_script_path)) {
                $page_script_src .= (strpos($page_script_src, '?') === false ? '?' : '&') . 'v=' . filemtime($page_script_path);
            }
            ?>
            <script src="<?php echo $page_script_src; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
