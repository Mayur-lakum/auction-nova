<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

echo "<h2>🔍 Debug Auction Images</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Check live auctions and their images
    echo "<h3>Live Auctions Image Check:</h3>";
    
    $live_query = "SELECT a.id, a.title, a.image, 
                   (SELECT ai.image_path FROM auction_images ai WHERE ai.auction_id = a.id AND ai.is_primary = 1 LIMIT 1) as primary_image
                   FROM auctions a 
                   WHERE a.status = 'active' AND a.end_time > NOW() 
                   LIMIT 5";
    
    $stmt = $conn->prepare($live_query);
    $stmt->execute();
    $auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($auctions)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>Image Column</th><th>Primary Image</th><th>File Exists</th><th>Generated Path</th></tr>";
        
        foreach ($auctions as $auction) {
            $image_path = !empty($auction['primary_image']) ? $auction['primary_image'] : $auction['image'];
            $full_path = 'assets/uploads/' . $image_path;
            $file_exists = file_exists($full_path);
            
            echo "<tr>";
            echo "<td>{$auction['id']}</td>";
            echo "<td>" . htmlspecialchars($auction['title']) . "</td>";
            echo "<td>" . htmlspecialchars($auction['image'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($auction['primary_image'] ?? 'NULL') . "</td>";
            echo "<td>" . ($file_exists ? '✅ YES' : '❌ NO') . "</td>";
            echo "<td>" . htmlspecialchars($full_path) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No live auctions found.</p>";
    }
    
    // Check uploads directory
    echo "<h3>Uploads Directory Contents:</h3>";
    $uploads_dir = 'assets/uploads/';
    if (is_dir($uploads_dir)) {
        $files = scandir($uploads_dir);
        echo "<ul>";
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $file_path = $uploads_dir . $file;
                $size = filesize($file_path);
                echo "<li>✅ $file (" . number_format($size/1024, 2) . " KB)</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p>❌ Uploads directory not found!</p>";
    }
    
    // Test image URLs
    echo "<h3>Test Image URLs:</h3>";
    echo "<div class='row'>";
    
    // Test BMW image
    echo "<div class='col-md-4'>";
    echo "<h6>BMW M4 Test:</h6>";
    echo "<img src='" . SITE_URL . "assets/uploads/bmw_m4_test.jpg' style='width: 100%; height: 100px; object-fit: cover; border: 1px solid #ddd;'>";
    echo "<p class='small'>" . SITE_URL . "assets/uploads/bmw_m4_test.jpg</p>";
    echo "</div>";
    
    // Test fallback
    echo "<div class='col-md-4'>";
    echo "<h6>Fallback Image:</h6>";
    echo "<img src='" . SITE_URL . "assets/images/no-image.jpg' style='width: 100%; height: 100px; object-fit: cover; border: 1px solid #ddd;'>";
    echo "<p class='small'>" . SITE_URL . "assets/images/no-image.jpg</p>";
    echo "</div>";
    
    // Test direct file
    echo "<div class='col-md-4'>";
    echo "<h6>Direct Path Test:</h6>";
    $direct_path = 'assets/uploads/bmw_m4_test.jpg';
    if (file_exists($direct_path)) {
        echo "<img src='$direct_path' style='width: 100%; height: 100px; object-fit: cover; border: 1px solid #ddd;'>";
        echo "<p class='small text-success'>✅ Direct path works</p>";
    } else {
        echo "<p class='text-danger'>❌ File not found: $direct_path</p>";
    }
    echo "</div>";
    
    echo "</div>";
    
    // Fix suggestions
    echo "<h3>🔧 Quick Fix Options:</h3>";
    echo "<div class='alert alert-info'>";
    echo "<h6>If images are not showing, try these fixes:</h6>";
    echo "<ol>";
    echo "<li><strong>Upload sample images:</strong> Copy images to assets/uploads/</li>";
    echo "<li><strong>Update database:</strong> Set image column for auctions</li>";
    echo "<li><strong>Check permissions:</strong> Ensure uploads directory is readable</li>";
    echo "<li><strong>Verify SITE_URL:</strong> Check config.php for correct base URL</li>";
    echo "</ol>";
    echo "</div>";
    
    // Create sample auction with image
    echo "<h3>📝 Create Sample Auction with Image:</h3>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='create_sample' value='1'>";
    echo "<button type='submit' class='btn btn-primary'>Create Sample Auction with BMW Image</button>";
    echo "</form>";
    
    if (isset($_POST['create_sample'])) {
        try {
            $insert = $conn->prepare("
                INSERT INTO auctions (title, description, starting_price, current_price, status, seller_id, category_id, image, start_time, end_time, created_at) 
                VALUES (?, ?, ?, ?, 'active', 1, 1, ?, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), NOW())
            ");
            
            $result = $insert->execute([
                'BMW M4 Sport Edition',
                'Beautiful BMW M4 in excellent condition with low mileage.',
                50000,
                50000,
                'bmw_m4_test.jpg'
            ]);
            
            if ($result) {
                echo "<div class='alert alert-success'>✅ Sample auction created with BMW image!</div>";
            }
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>❌ Error: " . $e->getMessage() . "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Database Error: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<p><a href='auction-hub.php'>← Back to Auction Hub</a></p>";
echo "<p><a href='index.php'>← Back to Home</a></p>";
?>
