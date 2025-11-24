<?php
/**
 * KYA Food Production - Inventory Data Export
 * Export inventory data to CSV and PDF formats
 */

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

SessionManager::start();
SessionManager::requireLogin();

// Check if user has inventory view permissions
if (!SessionManager::hasPermission('inventory_view')) {
    header('Location: ../../dashboard.php?error=access_denied');
    exit();
}

$userInfo = SessionManager::getUserInfo();
$db = new Database();
$conn = $db->connect();

// Get export format
$format = $_GET['export'] ?? 'csv';
if (!in_array($format, ['csv', 'pdf'])) {
    header('Location: index.php?error=invalid_format');
    exit();
}

// Get filter parameters (same as index.php)
$section = $_GET['section'] ?? '';
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';
$alert_status = $_GET['alert_status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query conditions
$whereConditions = [];
$params = [];

// Section filter based on user permissions
if ($userInfo['role'] !== 'admin') {
    $userSections = $userInfo['sections'];
    if (!empty($userSections)) {
        $placeholders = str_repeat('?,', count($userSections) - 1) . '?';
        $whereConditions[] = "section IN ($placeholders)";
        $params = array_merge($params, $userSections);
    }
} elseif ($section) {
    $whereConditions[] = "section = ?";
    $params[] = $section;
}

if ($category) {
    $whereConditions[] = "category = ?";
    $params[] = $category;
}

if ($status) {
    $whereConditions[] = "status = ?";
    $params[] = $status;
}

if ($alert_status) {
    $whereConditions[] = "alert_status = ?";
    $params[] = $alert_status;
}

if ($search) {
    $whereConditions[] = "(item_name LIKE ? OR item_code LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get inventory data
try {
    $stmt = $conn->prepare("
        SELECT i.*, 
               u.full_name as created_by_name,
               s.name as supplier_name
        FROM inventory i
        LEFT JOIN users u ON i.created_by = u.id
        LEFT JOIN suppliers s ON i.supplier_id = s.id
        $whereClause
        ORDER BY i.item_code
    ");
    $stmt->execute($params);
    $inventoryData = $stmt->fetchAll();

    if (empty($inventoryData)) {
        header('Location: index.php?error=no_data');
        exit();
    }

    // Export based on format
    if ($format === 'csv') {
        exportCSV($inventoryData);
    } elseif ($format === 'pdf') {
        exportPDF($inventoryData);
    }

} catch (Exception $e) {
    error_log('Export inventory data error: ' . $e->getMessage());
    header('Location: index.php?error=export_failed');
    exit();
}

function exportCSV($data) {
    $filename = 'inventory_export_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    $headers = [
        'Item Code',
        'Item Name',
        'Section',
        'Category',
        'Subcategory',
        'Description',
        'Quantity',
        'Unit',
        'Unit Cost',
        'Total Value',
        'Min Threshold',
        'Max Threshold',
        'Reorder Level',
        'Manufacture Date',
        'Expiry Date',
        'Batch Number',
        'Supplier',
        'Storage Location',
        'Storage Temperature',
        'Storage Humidity',
        'Quality Grade',
        'Status',
        'Notes',
        'Created By',
        'Created At',
        'Updated At'
    ];
    
    fputcsv($output, $headers);
    
    // CSV data
    foreach ($data as $row) {
        $csvRow = [
            $row['item_code'],
            $row['item_name'],
            'Section ' . $row['section'] . ' - ' . getSectionName($row['section']),
            $row['category'],
            $row['subcategory'] ?? '',
            $row['description'] ?? '',
            $row['quantity'],
            $row['unit'],
            $row['unit_cost'] ?? 0,
            $row['total_value'] ?? 0,
            $row['min_threshold'],
            $row['max_threshold'],
            $row['reorder_level'] ?? '',
            $row['manufacture_date'] ?? '',
            $row['expiry_date'] ?? '',
            $row['batch_number'] ?? '',
            $row['supplier_name'] ?? '',
            $row['storage_location'] ?? '',
            $row['storage_temperature'] ?? '',
            $row['storage_humidity'] ?? '',
            'Grade ' . $row['quality_grade'],
            ucfirst($row['status']),
            $row['notes'] ?? '',
            $row['created_by_name'] ?? '',
            $row['created_at'],
            $row['updated_at'] ?? ''
        ];
        
        fputcsv($output, $csvRow);
    }
    
    fclose($output);
    exit;
}

function exportPDF($data) {
    // For PDF export, we'll create a simple HTML table that can be saved as PDF
    $filename = 'inventory_export_' . date('Y-m-d_H-i-s') . '.pdf';
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Expires: 0');
    
    // Generate HTML content for PDF
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Inventory Export</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { color: #333; text-align: center; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            @media print {
                body { margin: 10px; }
                table { page-break-inside: auto; }
                tr { page-break-inside: avoid; page-break-after: auto; }
            }
        </style>
    </head>
    <body>
        <h1>KYA Food Production - Inventory Export</h1>
        <p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>
        <p><strong>Total Items:</strong> ' . count($data) . '</p>
        
        <table>
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Section</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Unit Cost</th>
                    <th>Total Value</th>
                    <th>Status</th>
                    <th>Expiry Date</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($data as $row) {
        $html .= '
                <tr>
                    <td>' . htmlspecialchars($row['item_code']) . '</td>
                    <td>' . htmlspecialchars($row['item_name']) . '</td>
                    <td>Section ' . $row['section'] . '</td>
                    <td>' . htmlspecialchars($row['category']) . '</td>
                    <td class="text-right">' . number_format($row['quantity'], 3) . '</td>
                    <td>' . htmlspecialchars($row['unit']) . '</td>
                    <td class="text-right">' . formatCurrency($row['unit_cost'] ?? 0) . '</td>
                    <td class="text-right">' . formatCurrency($row['total_value'] ?? 0) . '</td>
                    <td class="text-center">' . ucfirst($row['status']) . '</td>
                    <td>' . ($row['expiry_date'] ? formatDate($row['expiry_date']) : '-') . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
        
        <p style="margin-top: 30px; font-size: 12px; color: #666;">
            This report was generated from KYA Food Production System on ' . date('Y-m-d H:i:s') . '
        </p>
    </body>
    </html>';
    
    // Use a simple approach - output HTML that browsers can print to PDF
    // In a real implementation, you might use a library like TCPDF or DomPDF
    echo $html;
    exit;
}
?>
