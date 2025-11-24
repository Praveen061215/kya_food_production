


<?php
/**
 * KYA Food Production - Order Invoice PDF
 * Generates a simple invoice PDF for a given order
 */

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

SessionManager::start();
SessionManager::requireLogin();

if (!SessionManager::hasPermission('admin')) {
    header('Location: ../../dashboard.php?error=access_denied');
    exit();
}

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($orderId <= 0) {
    die('Invalid order ID');
}

$db = new Database();
$conn = $db->connect();

try {
    $stmt = $conn->prepare("SELECT o.*, 
                                   u1.full_name AS created_by_name,
                                   u2.full_name AS assigned_to_name
                            FROM orders o
                            LEFT JOIN users u1 ON o.created_by = u1.id
                            LEFT JOIN users u2 ON o.assigned_to = u2.id
                            WHERE o.id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        die('Order not found');
    }
    
    // Load order items
    $stmt = $conn->prepare("
        SELECT oi.*, 
               i.item_name, i.item_code, i.category, i.unit
        FROM order_items oi
        LEFT JOIN inventory i ON oi.inventory_id = i.id
        WHERE oi.order_id = ?
        ORDER BY oi.created_at ASC
    ");
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log('Invoice load order error: ' . $e->getMessage());
    die('Failed to load order');
}

$orderNumber = htmlspecialchars($order['order_number']);
$customerName = htmlspecialchars($order['customer_name']);
$customerEmail = $order['customer_email'] ? htmlspecialchars($order['customer_email']) : '';
$customerPhone = $order['customer_phone'] ? htmlspecialchars($order['customer_phone']) : '';
$customerAddress = $order['customer_address'] ? nl2br(htmlspecialchars($order['customer_address'])) : '';
$orderDate = formatDate($order['order_date']);
$requiredDate = $order['required_date'] ? formatDate($order['required_date']) : 'N/A';
$exportCountry = $order['export_country'] ? htmlspecialchars($order['export_country']) : 'N/A';
$totalAmount = formatCurrency($order['total_amount'], $order['currency']);
$paymentStatus = ucfirst($order['payment_status']);
$generatedAt = date('Y-m-d H:i');
$companyName = COMPANY_NAME;

$html = <<<HTML
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 11pt; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18pt; }
        .header p { margin: 2px 0; }
        .section-title { font-weight: bold; margin-top: 15px; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 6px; border: 1px solid #ccc; }
        .no-border td { border: none; }
        .right { text-align: right; }
        .small { font-size: 9pt; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>INVOICE</h1>
        <p>{$companyName}</p>
        <p class="small">Generated on {$generatedAt}</p>
    </div>

    <table class="no-border">
        <tr>
            <td>
                <div class="section-title">Bill To</div>
                <div>{$customerName}</div>
HTML;

if ($customerEmail) {
    $html .= "<div>{$customerEmail}</div>";
}
if ($customerPhone) {
    $html .= "<div>{$customerPhone}</div>";
}
if ($customerAddress) {
    $html .= "<div>{$customerAddress}</div>";
}

$html .= <<<HTML
            </td>
            <td class="right">
                <div class="section-title">Invoice Details</div>
                <div>Invoice #: {$orderNumber}</div>
                <div>Order Date: {$orderDate}</div>
                <div>Required Date: {$requiredDate}</div>
                <div>Export Country: {$exportCountry}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Order Items</div>
    <table>
        <thead>
            <tr>
                <th>Item Code</th>
                <th>Description</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total Price</th>
            </tr>
        </thead>
        <tbody>
HTML;

if (!empty($orderItems)) {
    foreach ($orderItems as $item) {
        $itemName = htmlspecialchars($item['item_name'] ?? 'Unknown Item');
        $itemCode = htmlspecialchars($item['item_code'] ?? 'N/A');
        $category = htmlspecialchars($item['category'] ?? 'N/A');
        $quantity = number_format($item['quantity'], 2);
        $unit = htmlspecialchars($item['unit'] ?? 'pcs');
        $unitPrice = formatCurrency($item['unit_price']);
        $totalPrice = formatCurrency($item['total_price']);
        
        $html .= <<<HTML
            <tr>
                <td>{$itemCode}</td>
                <td>
                    <div>{$itemName}</div>
                    <div class="small">Category: {$category}</div>
                </td>
                <td class="right">{$quantity} {$unit}</td>
                <td class="right">{$unitPrice}</td>
                <td class="right">{$totalPrice}</td>
            </tr>
HTML;
    }
} else {
    $html .= <<<HTML
            <tr>
                <td colspan="5" class="center">No items found for this order</td>
            </tr>
HTML;
}

$subtotal = array_sum(array_column($orderItems, 'total_price'));
$subtotalFormatted = formatCurrency($subtotal);

$html .= <<<HTML
        </tbody>
    </table>

    <table class="no-border" style="margin-top: 10px;">
        <tr>
            <td></td>
            <td class="right">
                <strong>Subtotal: {$subtotalFormatted}</strong><br/>
                <strong>Total: {$totalAmount}</strong><br/>
                <span class="small">Payment status: {$paymentStatus}</span>
            </td>
        </tr>
    </table>

    <p class="small" style="margin-top: 30px;">This is a system-generated invoice based on the order details recorded in the KYA Food Production Management System.</p>
</body>
</html>
HTML;
// Output as a normal HTML page (browser can Print / Save as PDF)
header('Content-Type: text/html; charset=utf-8');
echo $html;
exit;
