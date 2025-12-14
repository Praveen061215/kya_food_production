<?php
/**
 * KYA Food Production - Invoice Generator
 * Generate invoices for inventory transactions and orders
 */

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

SessionManager::start();
SessionManager::requireLogin();

if (!SessionManager::hasPermission('reports_view')) {
    die('Access denied');
}

$userInfo = SessionManager::getUserInfo();
$db = new Database();
$conn = $db->connect();

// Get invoice parameters
$invoice_type = $_GET['type'] ?? 'inventory'; // inventory, order, custom
$section = $_GET['section'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Generate invoice number
$invoice_number = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
$invoice_date = date('Y-m-d');

// Get inventory data for invoice
try {
    $whereConditions = [];
    $params = [];
    
    if ($date_from) {
        $whereConditions[] = "DATE(created_at) >= ?";
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $whereConditions[] = "DATE(created_at) <= ?";
        $params[] = $date_to;
    }
    
    if ($section) {
        $whereConditions[] = "section = ?";
        $params[] = $section;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $stmt = $conn->prepare("
        SELECT 
            item_code,
            item_name,
            category,
            quantity,
            unit,
            COALESCE(unit_price, 0) as unit_price,
            (quantity * COALESCE(unit_price, 0)) as total_price,
            created_at
        FROM inventory
        $whereClause
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['total_price'];
    }
    
    $tax_rate = 0.18; // 18% GST
    $tax_amount = $subtotal * $tax_rate;
    $total_amount = $subtotal + $tax_amount;
    
} catch (Exception $e) {
    die('Error generating invoice: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?php echo $invoice_number; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
        
        .invoice-container {
            max-width: 900px;
            margin: 30px auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .invoice-header {
            border-bottom: 3px solid #2c5f41;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .company-logo {
            max-height: 80px;
            margin-bottom: 10px;
        }
        
        .company-info {
            color: #2c5f41;
        }
        
        .invoice-title {
            font-size: 2.5rem;
            color: #2c5f41;
            font-weight: bold;
        }
        
        .invoice-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .invoice-table th {
            background: #2c5f41;
            color: white;
            padding: 12px;
        }
        
        .invoice-table td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .total-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 1.1rem;
        }
        
        .grand-total {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c5f41;
            border-top: 2px solid #2c5f41;
            padding-top: 15px;
            margin-top: 10px;
        }
        
        .footer-note {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Print/Download Buttons -->
        <div class="no-print mb-3">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Invoice
            </button>
            <button onclick="downloadPDF()" class="btn btn-success">
                <i class="fas fa-download"></i> Download PDF
            </button>
            <a href="financial.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Reports
            </a>
        </div>
        
        <!-- Invoice Header -->
        <div class="invoice-header">
            <div class="row">
                <div class="col-md-6">
                    <img src="../../assets/images/logo.png.png" alt="KYA Logo" class="company-logo">
                    <h2 class="company-info mb-1">KYA Food Production</h2>
                    <p class="mb-0">Integrated Management System</p>
                    <p class="mb-0">123 Industrial Area, Food Processing Zone</p>
                    <p class="mb-0">Phone: +91 1234567890</p>
                    <p class="mb-0">Email: info@kyafood.com</p>
                    <p class="mb-0">GSTIN: 29ABCDE1234F1Z5</p>
                </div>
                <div class="col-md-6 text-end">
                    <h1 class="invoice-title">INVOICE</h1>
                    <div class="invoice-details text-start">
                        <p class="mb-1"><strong>Invoice Number:</strong> <?php echo $invoice_number; ?></p>
                        <p class="mb-1"><strong>Invoice Date:</strong> <?php echo date('d M Y', strtotime($invoice_date)); ?></p>
                        <p class="mb-1"><strong>Period:</strong> <?php echo date('d M Y', strtotime($date_from)) . ' - ' . date('d M Y', strtotime($date_to)); ?></p>
                        <?php if ($section): ?>
                        <p class="mb-0"><strong>Section:</strong> Section <?php echo $section; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bill To Section -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h5 class="text-muted">BILL TO:</h5>
                <p class="mb-0"><strong>Internal Department</strong></p>
                <p class="mb-0">KYA Food Production</p>
                <p class="mb-0">Inventory Management</p>
            </div>
            <div class="col-md-6 text-end">
                <h5 class="text-muted">PREPARED BY:</h5>
                <p class="mb-0"><strong><?php echo htmlspecialchars($userInfo['full_name']); ?></strong></p>
                <p class="mb-0"><?php echo htmlspecialchars($userInfo['role']); ?></p>
                <p class="mb-0"><?php echo htmlspecialchars($userInfo['email']); ?></p>
            </div>
        </div>
        
        <!-- Items Table -->
        <table class="table invoice-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item Code</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="7" class="text-center">No items found for the selected period</td>
                </tr>
                <?php else: ?>
                    <?php $counter = 1; foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['category']); ?></td>
                        <td><?php echo number_format($item['quantity'], 2); ?> <?php echo htmlspecialchars($item['unit']); ?></td>
                        <td>Rs. <?php echo number_format($item['unit_price'], 2); ?></td>
                        <td class="text-end">Rs. <?php echo number_format($item['total_price'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Totals Section -->
        <div class="row">
            <div class="col-md-6 offset-md-6">
                <div class="total-section">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>Rs. <?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span>GST (18%):</span>
                        <span>Rs. <?php echo number_format($tax_amount, 2); ?></span>
                    </div>
                    <div class="total-row grand-total">
                        <span>GRAND TOTAL:</span>
                        <span>Rs. <?php echo number_format($total_amount, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer Notes -->
        <div class="footer-note">
            <h6>Terms & Conditions:</h6>
            <ul class="small">
                <li>This is a system-generated invoice for internal inventory tracking purposes.</li>
                <li>All prices are in Indian Rupees (INR).</li>
                <li>GST is calculated as per current tax regulations.</li>
                <li>For any queries, please contact the Accounts Department.</li>
            </ul>
            <p class="text-center mt-4 mb-0">
                <strong>Thank you for your business!</strong><br>
                <small>This is a computer-generated invoice and does not require a signature.</small>
            </p>
        </div>
    </div>
    
    <script>
        function downloadPDF() {
            window.print();
        }
    </script>
</body>
</html>
