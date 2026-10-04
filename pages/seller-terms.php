<?php
require_once '../includes/functions.php';
$page_title = 'Seller Terms and Conditions';
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h1 class="mb-0">Seller Terms and Conditions</h1>
                </div>
                <div class="card-body p-5">
                    <p class="text-muted mb-4">Last updated: <?php echo date('F j, Y'); ?></p>
                    
                    <h3>1. Seller Eligibility</h3>
                    <p>To sell on <?php echo SITE_NAME; ?>, you must:</p>
                    <ul>
                        <li>Be at least <?php echo MIN_AGE_REQUIREMENT; ?> years of age</li>
                        <li>Have a verified account with accurate information</li>
                        <li>Comply with all applicable laws and regulations</li>
                        <li>Own or have legal right to sell the items you list</li>
                    </ul>
                    
                    <h3>2. Listing Requirements</h3>
                    <p>When creating auction listings:</p>
                    <ul>
                        <li>Provide accurate, complete, and honest descriptions</li>
                        <li>Upload clear, representative photos of the actual item</li>
                        <li>Set reasonable starting prices and auction durations</li>
                        <li>Specify shipping costs and payment methods clearly</li>
                    </ul>
                    
                    <h3>3. Prohibited Items</h3>
                    <p>You may not sell:</p>
                    <ul>
                        <li>Illegal items or services</li>
                        <li>Stolen, counterfeit, or replica items</li>
                        <li>Items that violate intellectual property rights</li>
                        <li>Hazardous materials or weapons</li>
                        <li>Adult content or services</li>
                        <li>Live animals</li>
                    </ul>
                    
                    <h3>4. Auction Management</h3>
                    <p>As a seller, you agree to:</p>
                    <ul>
                        <li>Honor all successful auction sales</li>
                        <li>Not end auctions early if bids have been placed</li>
                        <li>Respond to buyer questions promptly</li>
                        <li>Complete transactions within reasonable timeframes</li>
                    </ul>
                    
                    <h3>5. Payment and Fees</h3>
                    <p>Currently, listing items on <?php echo SITE_NAME; ?> is free. We reserve the right to introduce fees in the future with advance notice to sellers.</p>
                    
                    <h3>6. Shipping and Delivery</h3>
                    <p>Sellers are responsible for:</p>
                    <ul>
                        <li>Packaging items securely</li>
                        <li>Shipping within stated timeframes</li>
                        <li>Providing tracking information when available</li>
                        <li>Ensuring items arrive as described</li>
                    </ul>
                    
                    <h3>7. Returns and Disputes</h3>
                    <p>While <?php echo SITE_NAME; ?> facilitates connections between buyers and sellers, we do not handle returns or disputes directly. Sellers should work with buyers to resolve any issues fairly.</p>
                    
                    <h3>8. Account Suspension</h3>
                    <p>We may suspend or terminate seller accounts for:</p>
                    <ul>
                        <li>Violating these terms or our general terms of service</li>
                        <li>Listing prohibited items</li>
                        <li>Engaging in fraudulent activity</li>
                        <li>Receiving multiple complaints from buyers</li>
                    </ul>
                    
                    <h3>9. Seller Ratings</h3>
                    <p>Buyers may rate and review their experience with sellers. These ratings help build trust in our community and may affect your ability to sell on the platform.</p>
                    
                    <h3>10. Contact and Support</h3>
                    <p>For seller support or questions about these terms, contact us at <?php echo SITE_EMAIL; ?>.</p>
                    
                    <div class="text-center mt-5">
                        <div class="d-flex gap-3 justify-content-center">
                            <a href="<?php echo SITE_URL; ?>seller/create-auction.php" class="btn btn-warning">
                                <i class="fas fa-plus me-2"></i>Create Auction
                            </a>
                            <a href="<?php echo SITE_URL; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-home me-2"></i>Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
