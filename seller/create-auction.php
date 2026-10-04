<?php
require_once '../includes/functions.php';
require_login();

$page_title = 'Create Auction';
$error = '';
$success = '';

$db = new Database();
$conn = $db->connect();

// Get categories
$categories_query = "SELECT * FROM categories WHERE is_active = TRUE ORDER BY name";
$categories_stmt = $conn->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $title = sanitize_input($_POST['title']);
        $description = sanitize_input($_POST['description']);
        $category_id = (int)$_POST['category_id'];
        $starting_price = (float)$_POST['starting_price'];
        $buy_now_price = !empty($_POST['buy_now_price']) ? (float)$_POST['buy_now_price'] : null;
        $min_increment = (float)$_POST['min_increment'];
        $duration_days = (int)$_POST['duration_days'];
        $duration_hours = (int)$_POST['duration_hours'];
        
        // Validation
        if (empty($title) || empty($description) || !$category_id || !$starting_price || !$min_increment || !$duration_days) {
            $error = 'Please fill in all required fields.';
        } elseif ($starting_price <= 0) {
            $error = 'Starting price must be greater than 0.';
        } elseif ($min_increment <= 0) {
            $error = 'Minimum increment must be greater than 0.';
        } elseif ($duration_days > MAX_AUCTION_DURATION) {
            $error = 'Maximum auction duration is ' . MAX_AUCTION_DURATION . ' days.';
        } elseif ($buy_now_price && $buy_now_price <= $starting_price) {
            $error = 'Buy now price must be higher than starting price.';
        } else {
            try {
                $conn->beginTransaction();
                
                // Calculate end time
                $total_hours = ($duration_days * 24) + $duration_hours;
                $end_time = date('Y-m-d H:i:s', strtotime("+$total_hours hours"));
                
                // Insert auction
                $auction_query = "INSERT INTO auctions (seller_id, category_id, title, description, starting_price, 
                                 current_price, buy_now_price, min_increment, end_time, status) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
                $auction_stmt = $conn->prepare($auction_query);
                $auction_stmt->execute([
                    $_SESSION['user_id'], $category_id, $title, $description, 
                    $starting_price, $starting_price, $buy_now_price, $min_increment, $end_time
                ]);
                
                $auction_id = $conn->lastInsertId();
                
                // Handle image uploads
                if (!empty($_FILES['images']['name'][0])) {
                    $upload_dir = '../assets/uploads/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $is_primary = true;
                    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                            $file_extension = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
                            
                            if (in_array($file_extension, ALLOWED_IMAGE_TYPES)) {
                                $filename = uniqid() . '.' . $file_extension;
                                $upload_path = $upload_dir . $filename;
                                
                                if (move_uploaded_file($tmp_name, $upload_path)) {
                                    $image_query = "INSERT INTO auction_images (auction_id, image_path, is_primary) VALUES (?, ?, ?)";
                                    $image_stmt = $conn->prepare($image_query);
                                    $image_stmt->execute([$auction_id, $filename, $is_primary]);
                                    $is_primary = false; // Only first image is primary
                                }
                            }
                        }
                    }
                }
                
                $conn->commit();
                
                // Send notification to admin
                $admin_query = "SELECT id FROM users WHERE role IN ('admin', 'super_admin') AND is_active = TRUE";
                $admin_stmt = $conn->prepare($admin_query);
                $admin_stmt->execute();
                $admins = $admin_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($admins as $admin) {
                    send_notification(
                        $admin['id'],
                        'auction_ending',
                        'New auction created',
                        'A new auction "' . $title . '" has been created and needs review.',
                        $auction_id
                    );
                }
                
                flash_message('success', 'Auction created successfully!');
                redirect(SITE_URL . 'auction-details.php?id=' . $auction_id);
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Failed to create auction. Please try again.';
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-plus me-2"></i>Create New Auction
                    </h3>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="fw-bold mb-3">Basic Information</h5>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="title" class="form-label">Auction Title *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                                       maxlength="200" required>
                                <div class="form-text">Make it descriptive and eye-catching</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">Category *</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="5" 
                                          maxlength="2000" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                <div class="form-text">Provide detailed information about the item</div>
                            </div>
                        </div>
                        
                        <!-- Images -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="fw-bold mb-3">Images</h5>
                                <div class="image-upload-area" onclick="document.getElementById('images').click()">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <h6>Click to upload images or drag and drop</h6>
                                    <p class="text-muted mb-0">Upload up to 5 images (JPG, PNG, GIF - Max 5MB each)</p>
                                </div>
                                <input type="file" id="images" name="images[]" multiple accept="image/*" style="display: none;">
                                <div class="image-preview mt-3"></div>
                            </div>
                        </div>
                        
                        <!-- Pricing -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="fw-bold mb-3">Pricing</h5>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="starting_price" class="form-label">Starting Price (₹) *</label>
                                <input type="number" class="form-control" id="starting_price" name="starting_price" 
                                       value="<?php echo isset($_POST['starting_price']) ? $_POST['starting_price'] : ''; ?>"
                                       min="1" step="0.01" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="min_increment" class="form-label">Minimum Bid Increment (₹) *</label>
                                <input type="number" class="form-control" id="min_increment" name="min_increment" 
                                       value="<?php echo isset($_POST['min_increment']) ? $_POST['min_increment'] : MIN_BID_INCREMENT; ?>"
                                       min="1" step="0.01" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="buy_now_price" class="form-label">Buy Now Price (₹)</label>
                                <input type="number" class="form-control" id="buy_now_price" name="buy_now_price" 
                                       value="<?php echo isset($_POST['buy_now_price']) ? $_POST['buy_now_price'] : ''; ?>"
                                       min="1" step="0.01">
                                <div class="form-text">Optional - Allow instant purchase</div>
                            </div>
                        </div>
                        
                        <!-- Duration -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="fw-bold mb-3">Auction Duration</h5>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="duration_days" class="form-label">Days *</label>
                                <select class="form-select" id="duration_days" name="duration_days" required>
                                    <?php for ($i = 1; $i <= MAX_AUCTION_DURATION; $i++): ?>
                                    <option value="<?php echo $i; ?>" 
                                            <?php echo (isset($_POST['duration_days']) && $_POST['duration_days'] == $i) ? 'selected' : ($i == 7 ? 'selected' : ''); ?>>
                                        <?php echo $i; ?> day<?php echo $i > 1 ? 's' : ''; ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="duration_hours" class="form-label">Additional Hours</label>
                                <select class="form-select" id="duration_hours" name="duration_hours">
                                    <?php for ($i = 0; $i < 24; $i++): ?>
                                    <option value="<?php echo $i; ?>" 
                                            <?php echo (isset($_POST['duration_hours']) && $_POST['duration_hours'] == $i) ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> hour<?php echo $i != 1 ? 's' : ''; ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Auction End Time:</strong> <span id="end-time-preview">Select duration to see end time</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Terms -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="terms_accepted" name="terms_accepted" required>
                                    <label class="form-check-label" for="terms_accepted">
                                        I agree to the <a href="<?php echo SITE_URL; ?>pages/seller-terms.php" target="_blank">Seller Terms and Conditions</a> *
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex gap-3">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-gavel me-2"></i>Create Auction
                                    </button>
                                    <a href="<?php echo SITE_URL; ?>seller/my-auctions.php" class="btn btn-outline-secondary btn-lg">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Update end time preview
    function updateEndTimePreview() {
        const days = parseInt($('#duration_days').val()) || 0;
        const hours = parseInt($('#duration_hours').val()) || 0;
        
        if (days > 0) {
            const now = new Date();
            const endTime = new Date(now.getTime() + (days * 24 + hours) * 60 * 60 * 1000);
            $('#end-time-preview').text(endTime.toLocaleString());
        }
    }
    
    $('#duration_days, #duration_hours').change(updateEndTimePreview);
    updateEndTimePreview();
    
    // Image upload handling
    $('#images').change(function() {
        const files = this.files;
        const preview = $('.image-preview');
        preview.empty();
        
        if (files.length > 5) {
            showAlert('warning', 'Maximum 5 images allowed');
            return;
        }
        
        for (let i = 0; i < Math.min(files.length, 5); i++) {
            const file = files[i];
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imageHtml = `
                        <div class="image-preview-item">
                            <img src="${e.target.result}" alt="Preview">
                            <button type="button" class="remove-image" onclick="removeImage(this)">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                    preview.append(imageHtml);
                };
                reader.readAsDataURL(file);
            }
        }
    });
    
    // Form validation
    $('form').submit(function(e) {
        const title = $('#title').val().trim();
        const description = $('#description').val().trim();
        const category = $('#category_id').val();
        const startingPrice = parseFloat($('#starting_price').val());
        const minIncrement = parseFloat($('#min_increment').val());
        const termsAccepted = $('#terms_accepted').is(':checked');
        
        if (!title || !description || !category || !startingPrice || !minIncrement) {
            e.preventDefault();
            showAlert('danger', 'Please fill in all required fields.');
            return false;
        }
        
        if (startingPrice <= 0) {
            e.preventDefault();
            showAlert('danger', 'Starting price must be greater than 0.');
            return false;
        }
        
        if (minIncrement <= 0) {
            e.preventDefault();
            showAlert('danger', 'Minimum increment must be greater than 0.');
            return false;
        }
        
        const buyNowPrice = parseFloat($('#buy_now_price').val());
        if (buyNowPrice && buyNowPrice <= startingPrice) {
            e.preventDefault();
            showAlert('danger', 'Buy now price must be higher than starting price.');
            return false;
        }
        
        if (!termsAccepted) {
            e.preventDefault();
            showAlert('danger', 'You must accept the seller terms and conditions.');
            return false;
        }
    });
});

function removeImage(button) {
    $(button).closest('.image-preview-item').remove();
}
</script>

<?php include '../includes/footer.php'; ?>
