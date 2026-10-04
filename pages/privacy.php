<?php
require_once '../includes/functions.php';
$page_title = 'Privacy Policy';
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h1 class="mb-0">Privacy Policy</h1>
                </div>
                <div class="card-body p-5">
                    <p class="text-muted mb-4">Last updated: <?php echo date('F j, Y'); ?></p>
                    
                    <h3>1. Information We Collect</h3>
                    <p>We collect information you provide directly to us, such as:</p>
                    <ul>
                        <li>Account registration information (name, email, phone number)</li>
                        <li>Profile information and photos</li>
                        <li>Auction listings and bid information</li>
                        <li>Communications with other users and our support team</li>
                    </ul>
                    
                    <h3>2. How We Use Your Information</h3>
                    <p>We use the information we collect to:</p>
                    <ul>
                        <li>Provide, maintain, and improve our services</li>
                        <li>Process transactions and send related information</li>
                        <li>Send you technical notices and support messages</li>
                        <li>Communicate with you about products, services, and events</li>
                        <li>Monitor and analyze trends and usage</li>
                    </ul>
                    
                    <h3>3. Information Sharing</h3>
                    <p>We may share your information in the following situations:</p>
                    <ul>
                        <li>With other users as necessary for auction transactions</li>
                        <li>With service providers who assist in our operations</li>
                        <li>When required by law or to protect our rights</li>
                        <li>In connection with a business transaction (merger, sale, etc.)</li>
                    </ul>
                    
                    <h3>4. Data Security</h3>
                    <p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. However, no method of transmission over the internet is 100% secure.</p>
                    
                    <h3>5. Your Rights</h3>
                    <p>You have the right to:</p>
                    <ul>
                        <li>Access and update your personal information</li>
                        <li>Delete your account and associated data</li>
                        <li>Opt out of marketing communications</li>
                        <li>Request a copy of your data</li>
                    </ul>
                    
                    <h3>6. Cookies and Tracking</h3>
                    <p>We use cookies and similar technologies to enhance your experience, analyze usage patterns, and deliver targeted content. You can control cookie settings through your browser preferences.</p>
                    
                    <h3>7. Third-Party Links</h3>
                    <p>Our service may contain links to third-party websites. We are not responsible for the privacy practices or content of these external sites.</p>
                    
                    <h3>8. Children's Privacy</h3>
                    <p>Our service is not intended for users under <?php echo MIN_AGE_REQUIREMENT; ?> years of age. We do not knowingly collect personal information from children under <?php echo MIN_AGE_REQUIREMENT; ?>.</p>
                    
                    <h3>9. Changes to Privacy Policy</h3>
                    <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new policy on this page and updating the "Last updated" date.</p>
                    
                    <h3>10. Contact Us</h3>
                    <p>If you have any questions about this Privacy Policy, please contact us at <?php echo SITE_EMAIL; ?>.</p>
                    
                    <div class="text-center mt-5">
                        <a href="<?php echo SITE_URL; ?>" class="btn btn-success">
                            <i class="fas fa-home me-2"></i>Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
