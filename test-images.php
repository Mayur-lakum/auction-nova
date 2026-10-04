<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$page_title = 'Image Test - Auction Site';
require_once 'includes/header.php';
?>

<div class="container mt-4">
    <h2>🖼️ Image Display Test</h2>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <h4>Test 1: Auction Details Image</h4>
            <div class="card">
                <div class="card-body">
                    <?php
                    // Simulate auction with image
                    $test_auction = [
                        'title' => 'BMW M4 Test',
                        'image' => 'bmw_m4_test.jpg'
                    ];
                    ?>
                    <?php if (!empty($test_auction['image'])): ?>
                        <img src="<?php echo SITE_URL; ?>assets/uploads/<?php echo htmlspecialchars($test_auction['image']); ?>" 
                             class="auction-image w-100 mb-3" alt="<?php echo htmlspecialchars($test_auction['title']); ?>">
                        <p class="text-success">✅ Image loaded: <?php echo htmlspecialchars($test_auction['image']); ?></p>
                    <?php else: ?>
                        <img src="<?php echo SITE_URL; ?>assets/images/no-image.jpg" 
                             class="auction-image w-100 mb-3" alt="<?php echo htmlspecialchars($test_auction['title']); ?>">
                        <p class="text-muted">📷 Using fallback image</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <h4>Test 2: Fallback Image</h4>
            <div class="card">
                <div class="card-body">
                    <?php
                    // Simulate auction without image
                    $test_auction_no_image = [
                        'title' => 'No Image Test',
                        'image' => null
                    ];
                    ?>
                    <?php if (!empty($test_auction_no_image['image'])): ?>
                        <img src="<?php echo SITE_URL; ?>assets/uploads/<?php echo htmlspecialchars($test_auction_no_image['image']); ?>" 
                             class="auction-image w-100 mb-3" alt="<?php echo htmlspecialchars($test_auction_no_image['title']); ?>">
                    <?php else: ?>
                        <img src="<?php echo SITE_URL; ?>assets/images/no-image.jpg" 
                             class="auction-image w-100 mb-3" alt="<?php echo htmlspecialchars($test_auction_no_image['title']); ?>">
                        <p class="text-success">✅ Fallback image loaded correctly</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <h4>Test 3: File System Check</h4>
            <div class="card">
                <div class="card-body">
                    <h6>Uploads Directory:</h6>
                    <ul>
                        <?php
                        $uploads_dir = 'assets/uploads/';
                        if (is_dir($uploads_dir)) {
                            $files = scandir($uploads_dir);
                            foreach ($files as $file) {
                                if ($file !== '.' && $file !== '..') {
                                    echo "<li class='text-success'>✅ $file</li>";
                                }
                            }
                        } else {
                            echo "<li class='text-danger'>❌ Uploads directory not found</li>";
                        }
                        ?>
                    </ul>
                    
                    <h6>Images Directory:</h6>
                    <ul>
                        <?php
                        $images_dir = 'assets/images/';
                        if (is_dir($images_dir)) {
                            $files = scandir($images_dir);
                            foreach ($files as $file) {
                                if ($file !== '.' && $file !== '..') {
                                    echo "<li class='text-success'>✅ $file</li>";
                                }
                            }
                        } else {
                            echo "<li class='text-danger'>❌ Images directory not found</li>";
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <h4>Test 4: Direct Image Links</h4>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6>BMW M4 Test Image:</h6>
                            <img src="<?php echo SITE_URL; ?>assets/uploads/bmw_m4_test.jpg" 
                                 style="width: 100%; max-height: 150px; object-fit: cover; border: 1px solid #ddd;">
                            <p class="small text-muted mt-2">assets/uploads/bmw_m4_test.jpg</p>
                        </div>
                        <div class="col-md-4">
                            <h6>Fallback Image:</h6>
                            <img src="<?php echo SITE_URL; ?>assets/images/no-image.jpg" 
                                 style="width: 100%; max-height: 150px; object-fit: cover; border: 1px solid #ddd;">
                            <p class="small text-muted mt-2">assets/images/no-image.jpg</p>
                        </div>
                        <div class="col-md-4">
                            <h6>Default Avatar:</h6>
                            <img src="<?php echo SITE_URL; ?>assets/images/default-avatar.png" 
                                 style="width: 100%; max-height: 150px; object-fit: cover; border: 1px solid #ddd;">
                            <p class="small text-muted mt-2">assets/images/default-avatar.png</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="auction-hub.php" class="btn btn-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Auction Hub
        </a>
        <a href="index.php" class="btn btn-secondary ms-2">
            <i class="fas fa-home me-2"></i>Back to Home
        </a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
