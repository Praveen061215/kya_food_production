<?php
/**
 * KYA Food Production - Get Item Details API
 * AJAX endpoint to fetch inventory item details
 */

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

SessionManager::start();
SessionManager::requireLogin();

// Check if user has inventory view permissions
if (!SessionManager::hasPermission('inventory_view')) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

// Get item ID
$itemId = $_GET['id'] ?? 0;
if (!$itemId || !is_numeric($itemId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid item ID']);
    exit();
}

$userInfo = SessionManager::getUserInfo();
$db = new Database();
$conn = $db->connect();

try {
    // Get inventory item details
    $stmt = $conn->prepare("
        SELECT i.*, 
               u.full_name as created_by_name,
               s.name as supplier_name,
               s.email as supplier_email,
               s.phone as supplier_phone
        FROM inventory i
        LEFT JOIN users u ON i.created_by = u.id
        LEFT JOIN suppliers s ON i.supplier_id = s.id
        WHERE i.id = ?
    ");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();

    if (!$item) {
        http_response_code(404);
        echo json_encode(['error' => 'Item not found']);
        exit();
    }

    // Check section permissions for non-admin users
    if ($userInfo['role'] !== 'admin' && !in_array($item['section'], $userInfo['sections'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit();
    }

    // Calculate alert status
    $alertStatus = 'normal';
    $alertMessage = '';
    $alertColor = 'success';

    if ($item['status'] === 'inactive') {
        $alertStatus = 'inactive';
        $alertMessage = 'Item is currently inactive';
        $alertColor = 'secondary';
    } elseif ($item['quantity'] <= 0) {
        $alertStatus = 'out_of_stock';
        $alertMessage = 'Item is out of stock';
        $alertColor = 'danger';
    } elseif ($item['min_threshold'] > 0 && $item['quantity'] <= $item['min_threshold']) {
        $alertStatus = 'critical';
        $alertMessage = 'Critical stock level - immediate reorder required';
        $alertColor = 'danger';
    } elseif ($item['reorder_level'] > 0 && $item['quantity'] <= $item['reorder_level']) {
        $alertStatus = 'low_stock';
        $alertMessage = 'Low stock - reorder recommended';
        $alertColor = 'warning';
    } elseif ($item['expiry_date']) {
        $daysToExpiry = (strtotime($item['expiry_date']) - time()) / (24 * 60 * 60);
        if ($daysToExpiry <= 0) {
            $alertStatus = 'expired';
            $alertMessage = 'Item has expired';
            $alertColor = 'danger';
        } elseif ($daysToExpiry <= 7) {
            $alertStatus = 'expiring_soon';
            $alertMessage = 'Item expires soon (' . floor($daysToExpiry) . ' days)';
            $alertColor = 'warning';
        }
    }

    // Get transaction history
    $historyStmt = $conn->prepare("
        SELECT h.*, u.full_name as user_name
        FROM inventory_history h
        LEFT JOIN users u ON h.user_id = u.id
        WHERE h.inventory_id = ?
        ORDER BY h.created_at DESC
        LIMIT 10
    ");
    $historyStmt->execute([$itemId]);
    $history = $historyStmt->fetchAll();

    // Get related orders
    $ordersStmt = $conn->prepare("
        SELECT oi.*, o.order_number, o.customer_name, o.order_date, o.status as order_status
        FROM order_items oi
        LEFT JOIN orders o ON oi.order_id = o.id
        WHERE oi.inventory_id = ?
        ORDER BY o.order_date DESC
        LIMIT 5
    ");
    $ordersStmt->execute([$itemId]);
    $orders = $ordersStmt->fetchAll();

    // Prepare response
    $response = [
        'success' => true,
        'item' => [
            'id' => $item['id'],
            'item_code' => $item['item_code'],
            'item_name' => $item['item_name'],
            'section' => $item['section'],
            'section_name' => getSectionName($item['section']),
            'category' => $item['category'],
            'subcategory' => $item['subcategory'],
            'description' => $item['description'],
            'quantity' => $item['quantity'],
            'unit' => $item['unit'],
            'unit_cost' => $item['unit_cost'],
            'total_value' => $item['total_value'],
            'min_threshold' => $item['min_threshold'],
            'max_threshold' => $item['max_threshold'],
            'reorder_level' => $item['reorder_level'],
            'manufacture_date' => $item['manufacture_date'],
            'expiry_date' => $item['expiry_date'],
            'batch_number' => $item['batch_number'],
            'supplier_name' => $item['supplier_name'],
            'supplier_email' => $item['supplier_email'],
            'supplier_phone' => $item['supplier_phone'],
            'storage_location' => $item['storage_location'],
            'storage_temperature' => $item['storage_temperature'],
            'storage_humidity' => $item['storage_humidity'],
            'quality_grade' => $item['quality_grade'],
            'status' => $item['status'],
            'notes' => $item['notes'],
            'created_by_name' => $item['created_by_name'],
            'created_at' => $item['created_at'],
            'updated_at' => $item['updated_at']
        ],
        'alert_status' => $alertStatus,
        'alert_message' => $alertMessage,
        'alert_color' => $alertColor,
        'history' => array_map(function($h) {
            return [
                'id' => $h['id'],
                'transaction_type' => $h['transaction_type'],
                'quantity_change' => $h['quantity_change'],
                'previous_quantity' => $h['previous_quantity'],
                'new_quantity' => $h['new_quantity'],
                'notes' => $h['notes'],
                'user_name' => $h['user_name'],
                'created_at' => $h['created_at']
            ];
        }, $history),
        'orders' => array_map(function($o) use ($item) {
            return [
                'order_id' => $o['order_id'],
                'order_number' => $o['order_number'],
                'customer_name' => $o['customer_name'],
                'quantity' => $o['quantity'],
                'unit_price' => $o['unit_price'],
                'total_price' => $o['total_price'],
                'order_date' => $o['order_date'],
                'order_status' => $o['order_status']
            ];
        }, $orders)
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    error_log('Get item details error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
}
?>
