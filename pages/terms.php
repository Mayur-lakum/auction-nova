<?php
require_once '../includes/functions.php';
$page_title = 'Terms of Service';
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h1 class="mb-0">Terms of Service</h1>
                </div>
                <div class="card-body p-5">
                    <p class="text-muted mb-4">Last updated: <?php echo date('F j, Y'); ?></p>
                    
                    <h3>1. Acceptance of Terms</h3>
                    <p>By accessing and using <?php echo SITE_NAME; ?>, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.</p>
                    
                    <h3>2. User Registration</h3>
                    <p>To use our services, you must:</p>
                    <ul>
                        <li>Be at least <?php echo MIN_AGE_REQUIREMENT; ?> years of age</li>
                        <li>Provide accurate and complete registration information</li>
                        <li>Maintain the security of your account credentials</li>
                        <li>Accept responsibility for all activities under your account</li>
                    </ul>
                    
                    <h3>3. Bidding Rules</h3>
                    <p>When participating in auctions:</p>
                    <ul>
                        <li>All bids are binding and cannot be retracted</li>
                        <li>You must honor winning bids and complete payment</li>
                        <li>Minimum bid increment is ₹<?php echo MIN_BID_INCREMENT; ?></li>
                        <li>You cannot bid on your own auctions</li>
                    </ul>
                    
                    <h3>4. Seller Responsibilities</h3>
                    <p>As a seller, you agree to:</p>
                    <ul>
                        <li>Provide accurate item descriptions and photos</li>
                        <li>Honor all successful auction sales</li>
                        <li>Communicate promptly with winning bidders</li>
                        <li>Ship items as described and in reasonable time</li>
                    </ul>
                    
                    <h3>5. Prohibited Activities</h3>
                    <p>Users may not:</p>
                    <ul>
                        <li>List illegal, stolen, or counterfeit items</li>
                        <li>Manipulate bidding through fake accounts</li>
                        <li>Harass or threaten other users</li>
                        <li>Violate any applicable laws or regulations</li>
                    </ul>
                    
                    <h3>6. Limitation of Liability</h3>
                    <p><?php echo SITE_NAME; ?> acts as a platform connecting buyers and sellers. We are not responsible for the quality, safety, or legality of items listed, the truth or accuracy of listings, or the ability of sellers to sell or buyers to pay.</p>
                    
                    <h3>7. Privacy Policy</h3>
                    <p>Your privacy is important to us. Please review our Privacy Policy to understand how we collect, use, and protect your information.</p>
                    
                    <h3>8. Changes to Terms</h3>
                    <p>We reserve the right to modify these terms at any time. Users will be notified of significant changes, and continued use of the service constitutes acceptance of modified terms.</p>
                    
                    <h3>9. Contact Information</h3>
                    <p>If you have any questions about these Terms of Service, please contact us at <?php echo SITE_EMAIL; ?>.</p>
                    
                    <div class="text-center mt-5">
                        <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
