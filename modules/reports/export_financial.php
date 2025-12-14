<?php
/**
 * KYA Food Production - Financial Report Export
 * Export financial reports in PDF, Excel, and CSV formats
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

// Get parameters
$format = $_GET['format'] ?? 'pdf';
$section_filter = $_GET['section'] ?? '';
$period_filter = $_GET['period'] ?? 'current_month';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Build WHERE clause
$whereConditions = [];
$params = [];

// Date range
switch ($period_filter) {
    case 'today':
        $date_from = $date_to = date('Y-m-d');
        break;
    case 'this_week':
        $date_from = date('Y-m-d', strtotime('monday this week'));
        $date_to = date('Y-m-d');
        break;
    case 'this_month':
        $date_from = date('Y-m-01');
        $date_to = date('Y-m-d');
        break;
    case 'this_year':
        $date_from = date('Y-01-01');
        $date_to = date('Y-m-d');
        break;
}

if ($date_from) {
    $whereConditions[] = "DATE(i.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $whereConditions[] = "DATE(i.created_at) <= ?";
    $params[] = $date_to;
}

if ($section_filter) {
    $whereConditions[] = "i.section = ?";
    $params[] = $section_filter;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get financial data
try {
    // Overall statistics
    $statsStmt = $conn->prepare("
        SELECT 
            SUM(i.quantity * COALESCE(i.unit_price, 0)) as total_inventory_value,
            COUNT(*) as total_items,
            AVG(COALESCE(i.unit_price, 0)) as avg_unit_cost,
            SUM(CASE WHEN i.alert_status IN ('low_stock', 'critical') THEN i.quantity * COALESCE(i.unit_price, 0) ELSE 0 END) as low_stock_value,
            SUM(CASE WHEN i.expiry_date < CURDATE() THEN i.quantity * COALESCE(i.unit_price, 0) ELSE 0 END) as expired_value,
            SUM(CASE WHEN i.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN i.quantity * COALESCE(i.unit_price, 0) ELSE 0 END) as expiring_value
        FROM inventory i
        $whereClause
    ");
    $statsStmt->execute($params);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Section breakdown
    $sectionStmt = $conn->prepare("
        SELECT 
            i.section,
            COUNT(*) as item_count,
            SUM(i.quantity) as total_quantity,
            SUM(i.quantity * COALESCE(i.unit_price, 0)) as total_value,
            AVG(COALESCE(i.unit_price, 0)) as avg_price
        FROM inventory i
        $whereClause
        GROUP BY i.section
        ORDER BY i.section
    ");
    $sectionStmt->execute($params);
    $sectionData = $sectionStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Detailed items
    $itemsStmt = $conn->prepare("
        SELECT 
            i.item_code,
            i.item_name,
            i.category,
            i.section,
            i.quantity,
            i.unit,
            COALESCE(i.unit_price, 0) as unit_price,
            (i.quantity * COALESCE(i.unit_price, 0)) as total_value,
            i.status,
            i.alert_status
        FROM inventory i
        $whereClause
        ORDER BY i.section, i.item_name
    ");
    $itemsStmt->execute($params);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    die('Error fetching data: ' . $e->getMessage());
}

// Export based on format
if ($format === 'csv') {
    // CSV Export
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="financial_report_' . date('Y-m-d_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    
    // Summary section
    fputcsv($output, ['KYA Food Production - Financial Report']);
    fputcsv($output, ['Generated', date('Y-m-d H:i:s')]);
    fputcsv($output, ['Period', $date_from . ' to ' . $date_to]);
    fputcsv($output, []);
    
    // Statistics
    fputcsv($output, ['Financial Summary']);
    fputcsv($output, ['Total Inventory Value', 'Rs. ' . number_format($stats['total_inventory_value'], 2)]);
    fputcsv($output, ['Total Items', number_format($stats['total_items'])]);
    fputcsv($output, ['Average Unit Cost', 'Rs. ' . number_format($stats['avg_unit_cost'], 2)]);
    fputcsv($output, ['Low Stock Value', 'Rs. ' . number_format($stats['low_stock_value'], 2)]);
    fputcsv($output, ['Expired Value', 'Rs. ' . number_format($stats['expired_value'], 2)]);
    fputcsv($output, ['Expiring Soon Value', 'Rs. ' . number_format($stats['expiring_value'], 2)]);
    fputcsv($output, []);
    
    // Section breakdown
    fputcsv($output, ['Section Breakdown']);
    fputcsv($output, ['Section', 'Items', 'Total Quantity', 'Total Value', 'Avg Price']);
    foreach ($sectionData as $section) {
        fputcsv($output, [
            'Section ' . $section['section'],
            $section['item_count'],
            number_format($section['total_quantity'], 2),
            'Rs. ' . number_format($section['total_value'], 2),
            'Rs. ' . number_format($section['avg_price'], 2)
        ]);
    }
    fputcsv($output, []);
    
    // Detailed items
    fputcsv($output, ['Detailed Inventory Items']);
    fputcsv($output, ['Item Code', 'Item Name', 'Category', 'Section', 'Quantity', 'Unit', 'Unit Price', 'Total Value', 'Status', 'Alert Status']);
    foreach ($items as $item) {
        fputcsv($output, [
            $item['item_code'],
            $item['item_name'],
            $item['category'],
            'Section ' . $item['section'],
            number_format($item['quantity'], 2),
            $item['unit'],
            'Rs. ' . number_format($item['unit_price'], 2),
            'Rs. ' . number_format($item['total_value'], 2),
            $item['status'],
            $item['alert_status']
        ]);
    }
    
    fclose($output);
    exit();
}

elseif ($format === 'pdf') {
    // Simple HTML to PDF conversion
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="financial_report_' . date('Y-m-d_His') . '.pdf"');
    
    // For now, we'll create an HTML version that can be printed to PDF
    // In production, you'd use a library like TCPDF or mPDF
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Financial Report</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { color: #2c5f41; border-bottom: 3px solid #2c5f41; padding-bottom: 10px; }
            h2 { color: #4a8b3a; margin-top: 30px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th { background: #2c5f41; color: white; padding: 12px; text-align: left; }
            td { padding: 10px; border-bottom: 1px solid #ddd; }
            tr:hover { background: #f5f5f5; }
            .summary-box { background: #f8f9fa; padding: 15px; border-left: 4px solid #2c5f41; margin: 20px 0; }
            .stat-row { display: flex; justify-content: space-between; padding: 8px 0; }
            .stat-label { font-weight: bold; }
            .stat-value { color: #2c5f41; font-weight: bold; }
            @media print {
                body { margin: 0; }
                button { display: none; }
            }
        </style>
    </head>
    <body>
        <h1>KYA Food Production - Financial Report</h1>
        <p><strong>Generated:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        <p><strong>Period:</strong> <?php echo $date_from . ' to ' . $date_to; ?></p>
        
        <div class="summary-box">
            <h2>Financial Summary</h2>
            <div class="stat-row">
                <span class="stat-label">Total Inventory Value:</span>
                <span class="stat-value">Rs. <?php echo number_format($stats['total_inventory_value'], 2); ?></span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Total Items:</span>
                <span class="stat-value"><?php echo number_format($stats['total_items']); ?></span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Average Unit Cost:</span>
                <span class="stat-value">Rs. <?php echo number_format($stats['avg_unit_cost'], 2); ?></span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Low Stock Value:</span>
                <span class="stat-value">Rs. <?php echo number_format($stats['low_stock_value'], 2); ?></span>
            </div>
        </div>
        
        <h2>Section Breakdown</h2>
        <table>
            <thead>
                <tr>
                    <th>Section</th>
                    <th>Items</th>
                    <th>Total Quantity</th>
                    <th>Total Value</th>
                    <th>Avg Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sectionData as $section): ?>
                <tr>
                    <td>Section <?php echo $section['section']; ?></td>
                    <td><?php echo number_format($section['item_count']); ?></td>
                    <td><?php echo number_format($section['total_quantity'], 2); ?></td>
                    <td>Rs. <?php echo number_format($section['total_value'], 2); ?></td>
                    <td>Rs. <?php echo number_format($section['avg_price'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2>Detailed Inventory Items</h2>
        <table>
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Section</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total Value</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                    <td>Section <?php echo $item['section']; ?></td>
                    <td><?php echo number_format($item['quantity'], 2); ?> <?php echo htmlspecialchars($item['unit']); ?></td>
                    <td>Rs. <?php echo number_format($item['unit_price'], 2); ?></td>
                    <td>Rs. <?php echo number_format($item['total_value'], 2); ?></td>
                    <td><?php echo htmlspecialchars($item['status']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <script>
            // Auto-print on load for PDF generation
            window.onload = function() {
                window.print();
            };
        </script>
    </body>
    </html>
    <?php
    exit();
}

else {
    die('Invalid export format');
}
?>
