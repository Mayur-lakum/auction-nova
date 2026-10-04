<?php
// Create Auction - BLANK
session_start();
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Admin authentication check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'super_admin'])) {
    header('Location: login.php');
    exit();
}

$page_title = 'Create Auction';
require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1>Create Auction</h1>
            <p>Auction creation page is currently blank. Ready for new design.</p>
        </div>
    </div>
</div>

    submitBtn.disabled = true;
    
    // If form validation fails, reset button
    setTimeout(() => {
        if (submitBtn.disabled) {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }, 3000);
});
</script>

<?php require_once '../includes/footer.php'; ?>
