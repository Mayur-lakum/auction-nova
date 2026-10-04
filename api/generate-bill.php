<?php
require_once '../includes/functions.php';
require_once '../config/payment.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ' . SITE_URL . 'auth/login.php');
    exit;
}

$bill_id = $_GET['id'] ?? 0;

if (!$bill_id || !is_numeric($bill_id)) {
    header('Location: ' . SITE_URL . 'user/dashboard.php');
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Get bill details with related data
    $bill_query = "SELECT b.*, p.razorpay_payment_id, p.transaction_id, p.payment_date,
                   a.title as auction_title, a.description as auction_description,
                   u.first_name, u.last_name, u.email, u.phone, u.address
                   FROM bills b
                   JOIN payments p ON b.payment_id = p.id
                   JOIN auctions a ON b.auction_id = a.id
                   JOIN users u ON b.user_id = u.id
                   WHERE b.id = ? AND b.user_id = ?";
    $bill_stmt = $conn->prepare($bill_query);
    $bill_stmt->execute([$bill_id, $_SESSION['user_id']]);
    $bill = $bill_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bill) {
        header('Location: ' . SITE_URL . 'user/dashboard.php');
        exit;
    }
    
    // Get bill items
    $items_query = "SELECT * FROM bill_items WHERE bill_id = ? ORDER BY id";
    $items_stmt = $conn->prepare($items_query);
    $items_stmt->execute([$bill_id]);
    $bill_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If PDF download is requested
    if (isset($_GET['download']) && $_GET['download'] == 'pdf') {
        generatePDFBill($bill, $bill_items);
        exit;
    }
    
} catch (Exception $e) {
    error_log('Bill generation error: ' . $e->getMessage());
    header('Location: ' . SITE_URL . 'user/dashboard.php');
    exit;
}

/**
 * Generate PDF bill using HTML to PDF conversion
 */
function generatePDFBill($bill, $bill_items) {
    // Create HTML content for PDF
    $html = generateBillHTML($bill, $bill_items);
    
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="bill-' . $bill['bill_number'] . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Simple HTML to PDF conversion (you can use libraries like TCPDF or mPDF for better results)
    // For now, we'll use a simple HTML response that browsers can print to PDF
    echo $html;
}

/**
 * Generate HTML content for bill
 */
function generateBillHTML($bill, $bill_items) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Bill - <?php echo $bill['bill_number']; ?></title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #0056ff; padding-bottom: 20px; }
            .company-name { font-size: 28px; font-weight: bold; color: #0056ff; margin-bottom: 10px; }
            .company-details { font-size: 14px; color: #666; }
            .bill-info { display: flex; justify-content: space-between; margin: 30px 0; }
            .bill-to, .bill-details { width: 48%; }
            .bill-details { text-align: right; }
            .section-title { font-size: 18px; font-weight: bold; margin-bottom: 10px; color: #0056ff; }
            .info-row { margin: 5px 0; }
            .items-table { width: 100%; border-collapse: collapse; margin: 30px 0; }
            .items-table th, .items-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
            .items-table th { background-color: #f8f9fa; font-weight: bold; }
            .items-table .amount { text-align: right; }
            .totals { margin-top: 30px; }
            .totals-table { width: 300px; margin-left: auto; border-collapse: collapse; }
            .totals-table td { padding: 8px; border-bottom: 1px solid #ddd; }
            .totals-table .total-row { font-weight: bold; font-size: 16px; border-top: 2px solid #0056ff; }
            .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #666; }
            .payment-info { background-color: #f8f9fa; padding: 20px; margin: 30px 0; border-radius: 5px; }
            @media print { body { margin: 0; } }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="company-name"><?php echo PAYMENT_COMPANY_NAME; ?></div>
            <div class="company-details">
                <?php echo nl2br(COMPANY_ADDRESS); ?><br>
                Phone: <?php echo COMPANY_PHONE; ?> | Email: <?php echo COMPANY_EMAIL; ?><br>
                <?php echo COMPANY_GST; ?>
            </div>
        </div>
        
        <div class="bill-info">
            <div class="bill-to">
                <div class="section-title">Bill To:</div>
                <div class="info-row"><strong><?php echo $bill['first_name'] . ' ' . $bill['last_name']; ?></strong></div>
                <div class="info-row"><?php echo $bill['email']; ?></div>
                <?php if ($bill['phone']): ?>
                <div class="info-row"><?php echo $bill['phone']; ?></div>
                <?php endif; ?>
                <?php if ($bill['address']): ?>
                <div class="info-row"><?php echo nl2br($bill['address']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="bill-details">
                <div class="section-title">Bill Details:</div>
                <div class="info-row"><strong>Bill Number:</strong> <?php echo $bill['bill_number']; ?></div>
                <div class="info-row"><strong>Bill Date:</strong> <?php echo date('d M Y', strtotime($bill['bill_date'])); ?></div>
                <div class="info-row"><strong>Due Date:</strong> <?php echo date('d M Y', strtotime($bill['due_date'])); ?></div>
                <div class="info-row"><strong>Status:</strong> <span style="color: <?php echo $bill['bill_status'] == 'paid' ? 'green' : 'orange'; ?>;"><?php echo ucfirst($bill['bill_status']); ?></span></div>
            </div>
        </div>
        
        <div class="section-title">Auction Details:</div>
        <div style="background-color: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px;">
            <strong><?php echo htmlspecialchars($bill['auction_title']); ?></strong><br>
            <?php if ($bill['auction_description']): ?>
            <div style="margin-top: 10px; color: #666;">
                <?php echo htmlspecialchars(substr($bill['auction_description'], 0, 200)) . (strlen($bill['auction_description']) > 200 ? '...' : ''); ?>
            </div>
            <?php endif; ?>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th class="amount">Unit Price</th>
                    <th class="amount">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bill_items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['item_description']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td class="amount"><?php echo formatCurrency($item['unit_price']); ?></td>
                    <td class="amount"><?php echo formatCurrency($item['total_price']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="totals">
            <table class="totals-table">
                <tr>
                    <td>Subtotal:</td>
                    <td class="amount"><?php echo formatCurrency($bill['subtotal']); ?></td>
                </tr>
                <?php if ($bill['discount_amount'] > 0): ?>
                <tr>
                    <td>Discount:</td>
                    <td class="amount">-<?php echo formatCurrency($bill['discount_amount']); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td>Tax (<?php echo TAX_PERCENT; ?>%):</td>
                    <td class="amount"><?php echo formatCurrency($bill['tax_amount']); ?></td>
                </tr>
                <tr class="total-row">
                    <td><strong>Total Amount:</strong></td>
                    <td class="amount"><strong><?php echo formatCurrency($bill['total_amount']); ?></strong></td>
                </tr>
            </table>
        </div>
        
        <?php if ($bill['bill_status'] == 'paid' && $bill['payment_date']): ?>
        <div class="payment-info">
            <div class="section-title">Payment Information:</div>
            <div class="info-row"><strong>Payment Date:</strong> <?php echo date('d M Y H:i', strtotime($bill['payment_date'])); ?></div>
            <div class="info-row"><strong>Transaction ID:</strong> <?php echo $bill['transaction_id']; ?></div>
            <div class="info-row"><strong>Payment Method:</strong> Razorpay</div>
            <div class="info-row" style="color: green;"><strong>Status: PAID</strong></div>
        </div>
        <?php endif; ?>
        
        <?php if ($bill['notes']): ?>
        <div style="margin-top: 30px;">
            <div class="section-title">Notes:</div>
            <div><?php echo nl2br(htmlspecialchars($bill['notes'])); ?></div>
        </div>
        <?php endif; ?>
        
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>This is a computer-generated bill and does not require a signature.</p>
            <p>Generated on <?php echo date('d M Y H:i'); ?></p>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

// Display HTML bill
$page_title = 'Bill - ' . $bill['bill_number'];
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Bill Details</h2>
                <div>
                    <a href="<?php echo SITE_URL; ?>api/generate-bill.php?id=<?php echo $bill_id; ?>&download=pdf" 
                       class="btn btn-primary" target="_blank">
                        <i class="fas fa-download me-2"></i>Download PDF
                    </a>
                    <a href="<?php echo SITE_URL; ?>user/dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <?php echo generateBillHTML($bill, $bill_items); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
