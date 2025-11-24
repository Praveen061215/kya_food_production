<?php
/**
 * KYA Food Production - Get Transfer Details API
 * AJAX endpoint to fetch inventory transfer details
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

// Get transfer ID
$transferId = $_GET['id'] ?? 0;
if (!$transferId || !is_numeric($transferId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid transfer ID']);
    exit();
}

$userInfo = SessionManager::getUserInfo();
$db = new Database();
$conn = $db->connect();

try {
    // Get transfer details
    $stmt = $conn->prepare("
        SELECT it.*, 
               u_from.full_name as from_user_name,
               u_to.full_name as to_user_name,
               i_from.item_name as from_item_name,
               i_to.item_name as to_item_name,
               s_from.name as from_section_name,
               s_to.name as to_section_name
        FROM inventory_transfers it
        LEFT JOIN users u_from ON it.from_user_id = u_from.id
        LEFT JOIN users u_to ON it.to_user_id = u_to.id
        LEFT JOIN inventory i_from ON it.from_item_id = i_from.id
        LEFT JOIN inventory i_to ON it.to_item_id = i_to.id
        LEFT JOIN sections s_from ON it.from_section = s_from.id
        LEFT JOIN sections s_to ON it.to_section = s_to.id
        WHERE it.id = ?
    ");
    $stmt->execute([$transferId]);
    $transfer = $stmt->fetch();

    if (!$transfer) {
        http_response_code(404);
        echo json_encode(['error' => 'Transfer not found']);
        exit();
    }

    // Check section permissions for non-admin users
    if ($userInfo['role'] !== 'admin') {
        $userSections = $userInfo['sections'];
        if (!in_array($transfer['from_section'], $userSections) && !in_array($transfer['to_section'], $userSections)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit();
        }
    }

    // Get transfer items
    $itemsStmt = $conn->prepare("
        SELECT iti.*, 
               i.item_name, i.item_code, i.unit
        FROM inventory_transfer_items iti
        LEFT JOIN inventory i ON iti.inventory_id = i.id
        WHERE iti.transfer_id = ?
        ORDER BY i.item_name
    ");
    $itemsStmt->execute([$transferId]);
    $items = $itemsStmt->fetchAll();

    // Prepare response
    $response = [
        'success' => true,
        'transfer' => [
            'id' => $transfer['id'],
            'transfer_number' => $transfer['transfer_number'],
            'from_section' => $transfer['from_section'],
            'from_section_name' => $transfer['from_section_name'] ?? 'Section ' . $transfer['from_section'],
            'to_section' => $transfer['to_section'],
            'to_section_name' => $transfer['to_section_name'] ?? 'Section ' . $transfer['to_section'],
            'from_user_name' => $transfer['from_user_name'],
            'to_user_name' => $transfer['to_user_name'],
            'transfer_date' => $transfer['transfer_date'],
            'status' => $transfer['status'],
            'notes' => $transfer['notes'],
            'created_at' => $transfer['created_at'],
            'updated_at' => $transfer['updated_at']
        ],
        'items' => array_map(function($item) {
            return [
                'id' => $item['id'],
                'inventory_id' => $item['inventory_id'],
                'item_name' => $item['item_name'],
                'item_code' => $item['item_code'],
                'unit' => $item['unit'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'total_cost' => $item['total_cost']
            ];
        }, $items)
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    error_log('Get transfer details error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
}
?>
