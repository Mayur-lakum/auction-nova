<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Include necessary files
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Cart.php';
require_once __DIR__ . '/../includes/auth.php';

// Initialize database and cart
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $cart = new Cart($pdo);
    
    // Handle AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        
        $response = ['success' => false, 'message' => 'Invalid request'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'update':
                    $auctionId = (int)($_POST['auction_id'] ?? 0);
                    $quantity = (int)($_POST['quantity'] ?? 1);
                    $response = $cart->updateQuantity($auctionId, $quantity);
                    break;
                    
                case 'remove':
                    $auctionId = (int)($_POST['auction_id'] ?? 0);
                    $response = $cart->removeItem($auctionId);
                    break;
                    
                case 'clear':
                    $response = $cart->clear();
                    break;
                    
                case 'get_cart':
                    $response = [
                        'success' => true,
                        'cart' => $cart->getCart(),
                        'item_count' => $cart->getItemCount()
                    ];
                    break;
            }
        }
        
        echo json_encode($response);
        exit;
    }
    
} catch (Exception $e) {
    error_log('Cart Error: ' . $e->getMessage());
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
        exit;
    } else {
        die('An error occurred. Please try again later.');
    }
}

// Set page title and include header
$page_title = 'Shopping Cart';
require_once __DIR__ . '/../includes/header.php';

// Get cart data
$cartData = $cart->getCart();
$itemCount = $cart->getItemCount();
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Your Shopping Cart</h1>
            
            <?php if ($itemCount > 0): ?>
                <div id="cart-container">
                    <!-- Cart items will be loaded here via JavaScript -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading your cart...</p>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <a href="<?php echo SITE_URL; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                        </a>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <button id="clear-cart" class="btn btn-outline-danger">
                            <i class="fas fa-trash-alt me-1"></i> Clear Cart
                        </button>
                        <a href="<?php echo SITE_URL; ?>checkout/" class="btn btn-primary ms-2">
                            Proceed to Checkout <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
                        <h3>Your cart is empty</h3>
                        <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet.</p>
                        <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">
                            <i class="fas fa-shopping-bag me-2"></i> Start Shopping
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Cart Item Template (Hidden) -->
<template id="cart-item-template">
    <div class="card mb-3 cart-item" data-auction-id="{{auction_id}}">
        <div class="row g-0">
            <div class="col-md-2">
                <img src="{{image_url}}" class="img-fluid rounded-start" alt="{{title}}" style="height: 120px; object-fit: cover; width: 100%;">
            </div>
            <div class="col-md-8">
                <div class="card-body">
                    <h5 class="card-title">{{title}}</h5>
                    <p class="card-text text-muted">Seller: {{seller_name}}</p>
                    <div class="d-flex align-items-center">
                        <button class="btn btn-sm btn-outline-secondary update-quantity" data-action="decrease">-</button>
                        <input type="number" class="form-control quantity-input mx-2" value="{{quantity}}" min="1" style="width: 70px;" data-auction-id="{{auction_id}}">
                        <button class="btn btn-sm btn-outline-secondary update-quantity" data-action="increase">+</button>
                        <button class="btn btn-link text-danger ms-3 remove-item" data-auction-id="{{auction_id}}">
                            <i class="fas fa-trash-alt"></i> Remove
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-2 text-end">
                <div class="card-body h-100 d-flex flex-column justify-content-between">
                    <div>
                        <span class="fw-bold item-price">{{price_formatted}}</span>
                    </div>
                    <div>
                        <span class="text-muted">Total: </span>
                        <span class="fw-bold item-total">{{total_formatted}}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Order Summary Template (Hidden) -->
<template id="order-summary-template">
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Order Summary</h5>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <span>Subtotal (<span id="summary-item-count">{{item_count}}</span> items):</span>
                <span id="subtotal-amount">{{subtotal_formatted}}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>Shipping:</span>
                <span id="shipping-amount">{{shipping_formatted}}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>Tax:</span>
                <span id="tax-amount">{{tax_formatted}}</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between fw-bold fs-5">
                <span>Total:</span>
                <span id="total-amount">{{total_formatted}}</span>
            </div>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Format currency
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    }

    // Update cart UI
    function updateCartUI(cart) {
        const cartContainer = document.getElementById('cart-container');
        const itemTemplate = document.getElementById('cart-item-template').innerHTML;
        const summaryTemplate = document.getElementById('order-summary-template').innerHTML;
        
        // Update cart counter in header
        updateCartCounter(cart.item_count);
        
        if (cart.items.length === 0) {
            cartContainer.innerHTML = `
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
                        <h3>Your cart is empty</h3>
                        <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet.</p>
                        <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">
                            <i class="fas fa-shopping-bag me-2"></i> Start Shopping
                        </a>
                    </div>
                </div>
            `;
            return;
        }

        // Render cart items
        let itemsHTML = '';
        cart.items.forEach(item => {
            let itemHTML = itemTemplate
                .replace(/\{\{auction_id\}\}/g, item.auction_id)
                .replace('{{image_url}}', item.image_url || '<?php echo SITE_URL; ?>assets/images/placeholder.jpg')
                .replace('{{title}}', item.title)
                .replace('{{seller_name}}', item.seller_name || 'N/A')
                .replace('{{quantity}}', item.quantity)
                .replace('{{price_formatted}}', formatCurrency(item.price))
                .replace('{{total_formatted}}', formatCurrency(item.price * item.quantity));
            itemsHTML += itemHTML;
        });

        // Render order summary
        const summaryHTML = summaryTemplate
            .replace('{{item_count}}', cart.item_count)
            .replace('{{subtotal_formatted}}', formatCurrency(cart.subtotal))
            .replace('{{shipping_formatted}}', formatCurrency(cart.shipping))
            .replace('{{tax_formatted}}', formatCurrency(cart.tax))
            .replace('{{total_formatted}}', formatCurrency(cart.total));

        // Update the DOM
        cartContainer.innerHTML = `
            <div class="row">
                <div class="col-lg-8">
                    ${itemsHTML}
                </div>
                <div class="col-lg-4">
                    ${summaryHTML}
                </div>
            </div>
        `;

        // Add event listeners
        addCartEventListeners();
    }

    // Add event listeners for cart actions
    function addCartEventListeners() {
        // Quantity updates
        document.querySelectorAll('.update-quantity').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.closest('.d-flex').querySelector('.quantity-input');
                let quantity = parseInt(input.value) || 1;
                
                if (this.dataset.action === 'increase') {
                    quantity++;
                } else if (this.dataset.action === 'decrease' && quantity > 1) {
                    quantity--;
                }
                
                input.value = quantity;
                updateCartItem(parseInt(input.dataset.auctionId), quantity);
            });
        });

        // Manual quantity input
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const quantity = Math.max(1, parseInt(this.value) || 1);
                this.value = quantity;
                updateCartItem(parseInt(this.dataset.auctionId), quantity);
            });
        });

        // Remove item
        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to remove this item from your cart?')) {
                    removeCartItem(parseInt(this.dataset.auctionId));
                }
            });
        });
    }

    // Update cart item quantity
    function updateCartItem(auctionId, quantity) {
        fetch('<?php echo SITE_URL; ?>cart/', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `action=update&auction_id=${auctionId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Refresh cart data
                loadCart();
            } else {
                alert(data.message || 'Failed to update cart. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }

    // Remove item from cart
    function removeCartItem(auctionId) {
        fetch('<?php echo SITE_URL; ?>cart/', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `action=remove&auction_id=${auctionId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Refresh cart data
                loadCart();
            } else {
                alert(data.message || 'Failed to remove item. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }

    // Clear cart
    document.getElementById('clear-cart')?.addEventListener('click', function() {
        if (confirm('Are you sure you want to clear your cart?')) {
            fetch('<?php echo SITE_URL; ?>cart/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'action=clear'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to home page
                    window.location.href = '<?php echo SITE_URL; ?>';
                } else {
                    alert(data.message || 'Failed to clear cart. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
    });

    // Load cart data
    function loadCart() {
        fetch('<?php echo SITE_URL; ?>cart/', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'action=get_cart'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartUI(data.cart);
            } else {
                console.error('Failed to load cart:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading cart:', error);
        });
    }

    // Update cart counter in header
    function updateCartCounter(itemCount) {
        const cartCounter = document.getElementById('cart-counter');
        if (cartCounter) {
            cartCounter.textContent = itemCount;
            // Hide badge if cart is empty
            if (itemCount === 0) {
                cartCounter.style.display = 'none';
            } else {
                cartCounter.style.display = 'inline-block';
            }
        }
    }

    // Initialize
    loadCart();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
