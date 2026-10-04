<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Secure Checkout';
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="text-center mb-5">
                <h1 class="display-5 fw-bold mb-3">Secure Checkout</h1>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <span class="text-success"><i class="fas fa-check-circle"></i> Cart</span>
                    <span class="text-success"><i class="fas fa-check-circle"></i> Information</span>
                    <span class="text-primary"><i class="fas fa-credit-card"></i> Payment</span>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-7">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Payment Method</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="paymentMethod" id="creditCard" checked>
                                    <label class="form-check-label fw-bold" for="creditCard">
                                        <i class="far fa-credit-card me-2"></i> Credit/Debit Card
                                    </label>
                                </div>
                                
                                <div id="cardElement" class="border p-3 rounded mb-3">
                                    <!-- Stripe Elements will be inserted here -->
                                    <div class="mb-3">
                                        <label class="form-label">Card Number</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" placeholder="1234 5678 9012 3456">
                                            <span class="input-group-text"><i class="fab fa-cc-visa"></i> <i class="fab fa-cc-mastercard ms-1"></i></span>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Expiry Date</label>
                                            <input type="text" class="form-control" placeholder="MM/YY">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">CVV</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" placeholder="123">
                                                <span class="input-group-text" data-bs-toggle="tooltip" title="3 or 4 digit security code">
                                                    <i class="fas fa-question-circle"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Name on Card</label>
                                        <input type="text" class="form-control" placeholder="John Smith">
                                    </div>
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="paymentMethod" id="paypal">
                                    <label class="form-check-label fw-bold" for="paypal">
                                        <i class="fab fa-paypal me-2"></i> PayPal
                                    </label>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="paymentMethod" id="crypto">
                                    <label class="form-check-label fw-bold" for="crypto">
                                        <i class="fab fa-bitcoin me-2"></i> Cryptocurrency
                                    </label>
                                </div>
                            </div>

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="saveCard" checked>
                                <label class="form-check-label small" for="saveCard">
                                    Save this card for future payments
                                </label>
                            </div>

                            <button class="btn btn-primary btn-lg w-100" id="submitPayment">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                Pay $23,100.00
                            </button>
                            
                            <p class="text-muted small mt-3">
                                <i class="fas fa-lock me-1"></i> Your payment information is encrypted and secure.
                                We do not store your credit card details.
                            </p>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Billing Address</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="sameAsShipping" checked>
                                <label class="form-check-label" for="sameAsShipping">
                                    Same as shipping address
                                </label>
                            </div>
                            
                            <div id="billingAddressForm" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <input type="text" class="form-control" placeholder="123 Main St">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">City</label>
                                        <input type="text" class="form-control">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">State</label>
                                        <select class="form-select">
                                            <option>Select State</option>
                                            <option>New York</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">ZIP</label>
                                        <input type="text" class="form-control">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Country</label>
                                    <select class="form-select">
                                        <option>United States</option>
                                        <option>Canada</option>
                                        <option>United Kingdom</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-5">
                    <div class="card shadow-sm sticky-top" style="top: 20px;">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal (2 items)</span>
                                <span>$21,250.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping</span>
                                <span class="text-success">FREE</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax</span>
                                <span>$1,700.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Insurance</span>
                                <span>$150.00</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <h5 class="mb-0">Total</h5>
                                <h4 class="mb-0 text-primary">$23,100.00</h4>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <h6 class="mb-3">Items in your order</h6>
                            <div class="d-flex mb-3">
                                <img src="../assets/images/auctions/1.jpg" alt="Product" class="rounded me-3" width="60">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Vintage Rolex Submariner</h6>
                                    <small class="text-muted">Qty: 1</small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold">$12,500.00</div>
                                </div>
                            </div>
                            <div class="d-flex">
                                <img src="../assets/images/auctions/2.jpg" alt="Product" class="rounded me-3" width="60">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Rare 1st Edition Charizard</h6>
                                    <small class="text-muted">Qty: 1</small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold">$8,750.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Success Modal -->
<div class="modal fade" id="paymentSuccessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-5">
                <div class="mb-4">
                    <div class="success-animation">
                        <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                            <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                            <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                        </svg>
                    </div>
                </div>
                <h3 class="mb-3">Payment Successful!</h3>
                <p class="text-muted mb-4">Your order has been placed successfully. We've sent a confirmation email to your registered email address.</p>
                <div class="d-grid gap-2">
                    <a href="../user/orders.php" class="btn btn-primary">View Order</a>
                    <a href="../auctions/" class="btn btn-outline-secondary">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<style>
.success-animation {
    margin: 0 auto;
    width: 100px;
    height: 100px;
}

.checkmark {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    display: block;
    stroke-width: 4;
    stroke: #4bb71b;
    stroke-miterlimit: 10;
    box-shadow: inset 0px 0px 0px #4bb71b;
    animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
}

.checkmark__circle {
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    stroke-width: 4;
    stroke-miterlimit: 10;
    stroke: #4bb71b;
    fill: none;
    animation: stroke .6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}

.checkmark__check {
    transform-origin: 50% 50%;
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    animation: stroke .3s cubic-bezier(0.65, 0, 0.45, 1) .8s forwards;
}

@keyframes stroke {
    100% {
        stroke-dashoffset: 0;
    }
}

@keyframes scale {
    0%, 100% {
        transform: none;
    }
    50% {
        transform: scale3d(1.1, 1.1, 1);
    }
}

@keyframes fill {
    100% {
        box-shadow: inset 0px 0px 0px 30px #fff;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle billing address form
    const sameAsShipping = document.getElementById('sameAsShipping');
    const billingAddressForm = document.getElementById('billingAddressForm');
    
    sameAsShipping.addEventListener('change', function() {
        billingAddressForm.style.display = this.checked ? 'none' : 'block';
    });
    
    // Simulate payment processing
    const submitBtn = document.getElementById('submitPayment');
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentSuccessModal'));
    
    submitBtn.addEventListener('click', function() {
        const spinner = this.querySelector('.spinner-border');
        const btnText = this.querySelector('span:not(.spinner-border)');
        
        // Show loading state
        this.disabled = true;
        spinner.classList.remove('d-none');
        btnText.textContent = 'Processing...';
        
        // Simulate API call
        setTimeout(() => {
            // Hide loading state
            spinner.classList.add('d-none');
            this.disabled = false;
            btnText.textContent = 'Payment Successful!';
            
            // Show success modal
            setTimeout(() => {
                paymentModal.show();
            }, 500);
            
        }, 2000);
    });
});
</script>
