// Auction Nova Main JavaScript

$(document).ready(function() {
    var siteUrl = (window.siteUrl || window.SITE_URL || '');
    
    // Skip auction-specific features on admin dashboard
    if (window.location.pathname.includes('/admin/')) {
        return;
    }
    
    // Sticky header shrink on scroll
    initializeNavbarShrink();

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Countdown timers
    updateCountdowns();
    setInterval(updateCountdowns, 1000);

    // Real-time bid updates
    if (typeof auctionId !== 'undefined') {
        setInterval(function() {
            updateBidInfo(auctionId);
        }, 5000);
    }

    // Image upload preview
    handleImageUpload();

    // Search filters
    initializeFilters();

    // Watchlist functionality
    initializeWatchlist();
});

function initializeNavbarShrink() {
    var navbar = document.querySelector('.navbar.fixed-top');
    if (!navbar) return;

    var lastState = null;

    var apply = function() {
        var shouldShrink = window.scrollY > 8;
        if (lastState === shouldShrink) return;
        lastState = shouldShrink;
        navbar.classList.toggle('navbar-shrink', shouldShrink);
    };

    apply();
    window.addEventListener('scroll', apply, { passive: true });
    window.addEventListener('resize', apply);
}

// Countdown Timer Function
function updateCountdowns() {
    $('.countdown-timer').each(function() {
        var endTime = $(this).data('end-time');
        var now = new Date().getTime();
        var end = new Date(endTime).getTime();
        var timeLeft = end - now;

        if (timeLeft > 0) {
            var days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
            var hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

            var display = '';
            if (days > 0) {
                display = days + 'd ' + hours + 'h ' + minutes + 'm';
            } else if (hours > 0) {
                display = hours + 'h ' + minutes + 'm ' + seconds + 's';
            } else {
                display = minutes + 'm ' + seconds + 's';
                $(this).addClass('ending-soon');
            }

            $(this).text(display);
        } else {
            $(this).text('Ended').removeClass('ending-soon').addClass('ended');
        }
    });
}

// Real-time Bid Updates
function updateBidInfo(auctionId) {
    $.ajax({
        url: (window.siteUrl || window.SITE_URL || '') + 'api/get-bid-info.php',
        method: 'GET',
        data: { auction_id: auctionId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#current-price').text('₹' + numberWithCommas(response.current_price));
                $('#bid-count').text(response.bid_count + ' bids');
                
                // Update bid history
                if (response.latest_bids) {
                    updateBidHistory(response.latest_bids);
                }
                
                // Update minimum bid
                var minBid = parseFloat(response.current_price) + parseFloat(response.min_increment);
                $('#bid-amount').attr('min', minBid);
                $('#min-bid-display').text('₹' + numberWithCommas(minBid));
            }
        },
        error: function() {
            console.log('Error updating bid information');
        }
    });
}

// Update Bid History
function updateBidHistory(bids) {
    var historyHtml = '';
    bids.forEach(function(bid) {
        historyHtml += '<div class="bid-item d-flex justify-content-between align-items-center">';
        historyHtml += '<div>';
        historyHtml += '<strong>₹' + numberWithCommas(bid.bid_amount) + '</strong>';
        historyHtml += '<small class="text-muted d-block">by ' + bid.username + '</small>';
        historyHtml += '</div>';
        historyHtml += '<small class="text-muted">' + timeAgo(bid.bid_time) + '</small>';
        historyHtml += '</div>';
    });
    $('#bid-history').html(historyHtml);
}

// Place Bid Function
function placeBid(auctionId) {
    var bidAmount = $('#bid-amount').val();
    var csrfToken = $('input[name="csrf_token"]').val();
    
    if (!bidAmount || bidAmount <= 0) {
        showAlert('danger', 'Please enter a valid bid amount');
        return;
    }
    
    $('#place-bid-btn').prop('disabled', true).html('<span class="loading-spinner"></span> Placing Bid...');
    
    $.ajax({
        url: (window.siteUrl || window.SITE_URL || '') + 'api/place-bid.php',
        method: 'POST',
        data: {
            auction_id: auctionId,
            bid_amount: bidAmount,
            csrf_token: csrfToken
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                updateBidInfo(auctionId);
                $('#bid-amount').val('');
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'An error occurred while placing the bid');
        },
        complete: function() {
            $('#place-bid-btn').prop('disabled', false).html('<i class="fas fa-gavel me-2"></i>Place Bid');
        }
    });
}

// Watchlist Functions
function initializeWatchlist() {
    $('.watchlist-btn').click(function(e) {
        e.preventDefault();
        var auctionId = $(this).data('auction-id');
        var isWatched = $(this).hasClass('active');
        toggleWatchlist(auctionId, !isWatched, $(this));
    });
}

function toggleWatchlist(auctionId, add, button) {
    var csrfToken = $('input[name="csrf_token"]').val();
    
    $.ajax({
        url: (window.siteUrl || window.SITE_URL || '') + 'api/toggle-watchlist.php',
        method: 'POST',
        data: {
            auction_id: auctionId,
            action: add ? 'add' : 'remove',
            csrf_token: csrfToken
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (add) {
                    button.addClass('active').html('<i class="fas fa-heart me-1"></i>Watching');
                } else {
                    button.removeClass('active').html('<i class="far fa-heart me-1"></i>Watch');
                }
                showAlert('success', response.message);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'An error occurred');
        }
    });
}

// Image Upload Handling
function handleImageUpload() {
    var uploadArea = $('.image-upload-area');
    var fileInput = $('#auction-images');
    var preview = $('.image-preview');
    
    uploadArea.click(function() {
        fileInput.click();
    });
    
    uploadArea.on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('dragover');
    });
    
    uploadArea.on('dragleave', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
    });
    
    uploadArea.on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
        var files = e.originalEvent.dataTransfer.files;
        handleFiles(files);
    });
    
    fileInput.change(function() {
        handleFiles(this.files);
    });
    
    function handleFiles(files) {
        preview.empty();
        for (var i = 0; i < files.length; i++) {
            if (files[i].type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var imageHtml = '<div class="image-preview-item">';
                    imageHtml += '<img src="' + e.target.result + '" alt="Preview">';
                    imageHtml += '<button type="button" class="remove-image" onclick="removeImage(this)">';
                    imageHtml += '<i class="fas fa-times"></i>';
                    imageHtml += '</button>';
                    imageHtml += '</div>';
                    preview.append(imageHtml);
                };
                reader.readAsDataURL(files[i]);
            }
        }
    }
}

function removeImage(button) {
    $(button).closest('.image-preview-item').remove();
}

// Search Filters
function initializeFilters() {
    // Price range slider
    if ($('#price-range').length) {
        var priceRange = document.getElementById('price-range');
        noUiSlider.create(priceRange, {
            start: [0, 100000],
            connect: true,
            range: {
                'min': 0,
                'max': 1000000
            },
            format: {
                to: function(value) {
                    return Math.round(value);
                },
                from: function(value) {
                    return Number(value);
                }
            }
        });
        
        priceRange.noUiSlider.on('update', function(values) {
            $('#min-price').val(values[0]);
            $('#max-price').val(values[1]);
        });
    }
    
    // Category filter
    $('.category-filter').change(function() {
        updateFilters();
    });
    
    // Sort options
    $('#sort-by').change(function() {
        updateFilters();
    });
}

function updateFilters() {
    var form = $('#filter-form');
    if (form.length) {
        form.submit();
    }
}

// Utility Functions
function numberWithCommas(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function timeAgo(dateString) {
    var now = new Date();
    var past = new Date(dateString);
    var diff = Math.floor((now - past) / 1000);
    
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
    if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
    return Math.floor(diff / 86400) + ' days ago';
}

function showAlert(type, message) {
    var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">';
    alertHtml += message;
    alertHtml += '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    alertHtml += '</div>';
    
    $('#alert-container').html(alertHtml);
    
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
}

// Form Validation
function validateForm(formId) {
    var form = document.getElementById(formId);
    if (form.checkValidity() === false) {
        event.preventDefault();
        event.stopPropagation();
    }
    form.classList.add('was-validated');
    return form.checkValidity();
}

// Auto-refresh for auction pages
function startAutoRefresh(interval = 30000) {
    setInterval(function() {
        if (typeof auctionId !== 'undefined') {
            updateBidInfo(auctionId);
        }
    }, interval);
}

// Notification handling
function markNotificationRead(notificationId) {
    $.ajax({
        url: (window.siteUrl || window.SITE_URL || '') + 'api/mark-notification-read.php',
        method: 'POST',
        data: {
            notification_id: notificationId,
            csrf_token: $('input[name="csrf_token"]').val()
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#notification-' + notificationId).removeClass('unread');
            }
        }
    });
}

// Initialize page-specific functionality
function initializePage() {
    // Add any page-specific initialization here
    if (typeof pageInit === 'function') {
        pageInit();
    }
}
