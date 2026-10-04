<?php
require_once '../includes/functions.php';
$page_title = 'Contact Us';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $name = sanitize_input($_POST['name']);
        $email = sanitize_input($_POST['email']);
        $subject = sanitize_input($_POST['subject']);
        $message = sanitize_input($_POST['message']);
        
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error = 'Please fill in all fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // In a real application, you would send an email here
            // For now, we'll just show a success message
            $success = 'Thank you for your message! We will get back to you within 24 hours.';
            
            // Clear form data
            $name = $email = $subject = $message = '';
        }
    }
}

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Contact Form -->
        <div class="col-lg-8 mb-5">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-envelope me-2"></i>Send us a Message</h3>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject *</label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="">Select a subject</option>
                                <option value="General Inquiry">General Inquiry</option>
                                <option value="Account Issues">Account Issues</option>
                                <option value="Bidding Problems">Bidding Problems</option>
                                <option value="Payment Issues">Payment Issues</option>
                                <option value="Technical Support">Technical Support</option>
                                <option value="Report Fraud">Report Fraud</option>
                                <option value="Seller Support">Seller Support</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label for="message" class="form-label">Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="6" 
                                      placeholder="Please describe your inquiry in detail..." required><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Contact Information -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Contact Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="fw-bold"><i class="fas fa-envelope text-primary me-2"></i>Email</h6>
                        <p class="mb-0"><?php echo SITE_EMAIL; ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold"><i class="fas fa-phone text-success me-2"></i>Phone</h6>
                        <p class="mb-0">+91 98765 43210</p>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold"><i class="fas fa-map-marker-alt text-danger me-2"></i>Address</h6>
                        <p class="mb-0">
                            <?php echo SITE_NAME; ?> Headquarters<br>
                            123 Business District<br>
                            Mumbai, Maharashtra 400001<br>
                            India
                        </p>
                    </div>
                    
                    <div>
                        <h6 class="fw-bold"><i class="fas fa-clock text-warning me-2"></i>Business Hours</h6>
                        <p class="mb-0">
                            Monday - Friday: 9:00 AM - 6:00 PM<br>
                            Saturday: 10:00 AM - 4:00 PM<br>
                            Sunday: Closed
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Link -->
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-question-circle fa-3x text-info mb-3"></i>
                    <h5 class="fw-bold">Need Quick Answers?</h5>
                    <p class="text-muted">Check our FAQ section for common questions and solutions.</p>
                    <a href="faq.php" class="btn btn-info">
                        <i class="fas fa-question-circle me-2"></i>View FAQ
                    </a>
                </div>
            </div>
            
            <!-- Social Media -->
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-share-alt me-2"></i>Follow Us</h6>
                </div>
                <div class="card-body text-center">
                    <div class="d-flex justify-content-center gap-3">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL); ?>" class="btn btn-outline-primary" target="_blank" rel="noopener noreferrer" aria-label="Share on Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(SITE_URL); ?>&text=<?php echo urlencode(SITE_NAME); ?>" class="btn btn-outline-info" target="_blank" rel="noopener noreferrer" aria-label="Share on X">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.instagram.com/" class="btn btn-outline-danger" target="_blank" rel="noopener noreferrer" aria-label="Open Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(SITE_URL); ?>" class="btn btn-outline-primary" target="_blank" rel="noopener noreferrer" aria-label="Share on LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
