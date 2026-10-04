<?php
require_once '../includes/functions.php';
$page_title = 'Frequently Asked Questions';
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="text-center mb-5">
                <h1 class="fw-bold mb-3">Frequently Asked Questions</h1>
                <p class="lead text-muted">Find answers to common questions about <?php echo SITE_NAME; ?></p>
            </div>

            <!-- FAQ Accordion -->
            <div class="accordion" id="faqAccordion">
                <!-- General Questions -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            What is <?php echo SITE_NAME; ?>?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <?php echo SITE_NAME; ?> is India's premier online auction platform where buyers and sellers can participate in secure, transparent auctions. We provide a safe marketplace for unique items, collectibles, electronics, and much more.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            How do I register on <?php echo SITE_NAME; ?>?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Registration is simple and free! Click on "Register" in the top navigation, fill out the required information including your name, email, and phone number. You must be at least <?php echo MIN_AGE_REQUIREMENT; ?> years old to register. After registration, verify your email to start bidding.
                        </div>
                    </div>
                </div>

                <!-- Bidding Questions -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            How does bidding work?
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Bidding is straightforward: find an auction you're interested in, enter your bid amount (must be higher than current bid + minimum increment of ₹<?php echo MIN_BID_INCREMENT; ?>), and click "Place Bid". The highest bidder when the auction ends wins the item. All bids are binding.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                            Can I retract a bid once placed?
                        </button>
                    </h2>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            No, all bids are final and binding. Once you place a bid, you cannot retract it. Please make sure you want to purchase the item and can afford the bid amount before placing your bid.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                            What happens when I win an auction?
                        </button>
                    </h2>
                    <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Congratulations! When you win an auction, you'll receive a notification and email. The seller will contact you with payment and shipping details. You must complete payment within the agreed timeframe to finalize the purchase.
                        </div>
                    </div>
                </div>

                <!-- Selling Questions -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                            How do I sell items on <?php echo SITE_NAME; ?>?
                        </button>
                    </h2>
                    <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            To sell items, log in to your account and click "Create Auction". Upload clear photos, write a detailed description, set your starting price and auction duration (1-<?php echo MAX_AUCTION_DURATION; ?> days). Once submitted, your auction will go live and buyers can start bidding.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                            What fees do I pay as a seller?
                        </button>
                    </h2>
                    <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Currently, listing items on <?php echo SITE_NAME; ?> is free! We may introduce small success fees in the future, but we'll always notify sellers in advance of any fee changes.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                            Can I end my auction early?
                        </button>
                    </h2>
                    <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            You can end your auction early only if there are no bids placed. Once someone has bid on your item, the auction must run its full course. This protects bidders' interests and maintains trust in our platform.
                        </div>
                    </div>
                </div>

                <!-- Payment & Security -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq9">
                            Is my personal information safe?
                        </button>
                    </h2>
                    <div id="faq9" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Yes! We use industry-standard security measures to protect your personal information. Your data is encrypted, and we never share your information with third parties without your consent. Read our Privacy Policy for complete details.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq10">
                            What payment methods are accepted?
                        </button>
                    </h2>
                    <div id="faq10" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Payment methods are arranged directly between buyers and sellers. We recommend using secure payment methods like bank transfers, UPI, or cash on delivery for local transactions. Always verify the seller's identity before making payments.
                        </div>
                    </div>
                </div>

                <!-- Technical Support -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq11">
                            I'm having technical issues. What should I do?
                        </button>
                    </h2>
                    <div id="faq11" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            For technical issues, try refreshing your browser or clearing your cache first. If the problem persists, contact our support team through the Contact Us page with details about the issue, including your browser type and any error messages.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq12">
                            How do I report suspicious activity or fraud?
                        </button>
                    </h2>
                    <div id="faq12" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            If you encounter suspicious activity, fake listings, or potential fraud, please report it immediately through our Contact Us page or email us at <?php echo SITE_EMAIL; ?>. Include as much detail as possible, including usernames, auction IDs, and screenshots if available.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Still Have Questions -->
            <div class="text-center mt-5">
                <div class="card bg-light">
                    <div class="card-body py-4">
                        <h4 class="fw-bold mb-3">Still Have Questions?</h4>
                        <p class="text-muted mb-4">Can't find what you're looking for? Our support team is here to help!</p>
                        <a href="contact.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-envelope me-2"></i>Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
