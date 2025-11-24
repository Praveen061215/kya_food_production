<?php
/**
 * KYA Food Production - Order Item Functions
 * Reusable functions for order item CRUD operations
 */

// Prevent direct access
if (!defined('KYA_FOOD_PRODUCTION')) {
    die('Direct access not permitted');
}

/**
 * Get order items for a specific order
 */
function getOrderItems($orderId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT oi.*, 
                   i.item_name, i.item_code, i.category, i.unit, i.quality_grade,
                   i.section as inventory_section
            FROM order_items oi
            LEFT JOIN inventory i ON oi.inventory_id = i.id
            WHERE oi.order_id = ?
            ORDER BY oi.created_at ASC
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log('Get order items error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get single order item by ID
 */
function getOrderItem($itemId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT oi.*, 
                   i.item_name, i.item_code, i.category, i.unit, i.quality_grade,
                   i.section as inventory_section
            FROM order_items oi
            LEFT JOIN inventory i ON oi.inventory_id = i.id
            WHERE oi.id = ?
        ");
        $stmt->execute([$itemId]);
        return $stmt->fetch();
        
    } catch (Exception $e) {
        error_log('Get order item error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Add new order item
 */
function addOrderItem($orderId, $data, $userId) {
    global $conn;
    
    try {
        // Validate required fields
        if (empty($data['inventory_id']) || empty($data['quantity']) || empty($data['unit_price'])) {
            return ['success' => false, 'message' => 'Please fill all required fields'];
        }
        
        // Check if inventory exists and has sufficient quantity
        $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE id = ?");
        $stmt->execute([$data['inventory_id']]);
        $inventory = $stmt->fetch();
        
        if (!$inventory) {
            return ['success' => false, 'message' => 'Selected inventory item not found'];
        }
        
        if ($inventory['quantity'] < $data['quantity']) {
            return ['success' => false, 'message' => 'Insufficient inventory quantity'];
        }
        
        // Insert order item
        $stmt = $conn->prepare("
            INSERT INTO order_items (order_id, inventory_id, quantity, unit_price, 
                                   quality_requirements, packaging_requirements, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $orderId,
            $data['inventory_id'],
            $data['quantity'],
            $data['unit_price'],
            $data['quality_requirements'] ?? null,
            $data['packaging_requirements'] ?? null,
            $data['notes'] ?? null
        ]);
        
        if ($result) {
            // Update order total amount
            updateOrderTotal($orderId);
            
            logActivity('order_item_added', "Order item added to order ID: {$orderId}", $userId);
            return ['success' => true, 'item_id' => $conn->lastInsertId()];
        } else {
            return ['success' => false, 'message' => 'Failed to add order item'];
        }
        
    } catch (Exception $e) {
        error_log('Add order item error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

/**
 * Update existing order item
 */
function updateOrderItem($itemId, $data, $userId) {
    global $conn;
    
    try {
        // Validate required fields
        if (empty($data['inventory_id']) || empty($data['quantity']) || empty($data['unit_price'])) {
            return ['success' => false, 'message' => 'Please fill all required fields'];
        }
        
        // Check if item exists
        $stmt = $conn->prepare("SELECT order_id, quantity FROM order_items WHERE id = ?");
        $stmt->execute([$itemId]);
        $existingItem = $stmt->fetch();
        
        if (!$existingItem) {
            return ['success' => false, 'message' => 'Order item not found'];
        }
        
        // Check inventory availability
        $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE id = ?");
        $stmt->execute([$data['inventory_id']]);
        $inventory = $stmt->fetch();
        
        if (!$inventory || $inventory['quantity'] < $data['quantity']) {
            return ['success' => false, 'message' => 'Insufficient inventory quantity'];
        }
        
        // Update order item
        $stmt = $conn->prepare("
            UPDATE order_items 
            SET inventory_id = ?, quantity = ?, unit_price = ?, 
                quality_requirements = ?, packaging_requirements = ?, notes = ?
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $data['inventory_id'],
            $data['quantity'],
            $data['unit_price'],
            $data['quality_requirements'] ?? null,
            $data['packaging_requirements'] ?? null,
            $data['notes'] ?? null,
            $itemId
        ]);
        
        if ($result) {
            // Update order total amount
            updateOrderTotal($existingItem['order_id']);
            
            logActivity('order_item_updated', "Order item updated: ID {$itemId}", $userId);
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Failed to update order item'];
        }
        
    } catch (Exception $e) {
        error_log('Update order item error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

/**
 * Delete order item
 */
function deleteOrderItem($itemId, $userId) {
    global $conn;
    
    try {
        // Get order_id before deletion
        $stmt = $conn->prepare("SELECT order_id FROM order_items WHERE id = ?");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();
        
        if (!$item) {
            return ['success' => false, 'message' => 'Order item not found'];
        }
        
        // Delete the order item
        $stmt = $conn->prepare("DELETE FROM order_items WHERE id = ?");
        $result = $stmt->execute([$itemId]);
        
        if ($result) {
            // Update order total amount
            updateOrderTotal($item['order_id']);
            
            logActivity('order_item_deleted', "Order item deleted: ID {$itemId}", $userId);
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Failed to delete order item'];
        }
        
    } catch (Exception $e) {
        error_log('Delete order item error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

/**
 * Update order item status
 */
function updateItemStatus($itemId, $status, $userId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("UPDATE order_items SET status = ? WHERE id = ?");
        $result = $stmt->execute([$status, $itemId]);
        
        if ($result && $stmt->rowCount() > 0) {
            logActivity('order_item_status_updated', "Item status updated to: {$status}", $userId);
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Order item not found'];
        }
        
    } catch (Exception $e) {
        error_log('Update item status error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update item status'];
    }
}

/**
 * Allocate quantity for order item
 */
function allocateQuantity($itemId, $allocatedQty, $userId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            UPDATE order_items 
            SET allocated_quantity = ?, status = 'allocated' 
            WHERE id = ? AND quantity >= ?
        ");
        $result = $stmt->execute([$allocatedQty, $itemId, $allocatedQty]);
        
        if ($result && $stmt->rowCount() > 0) {
            logActivity('quantity_allocated', "Quantity allocated: {$allocatedQty}", $userId);
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Invalid allocation quantity'];
        }
        
    } catch (Exception $e) {
        error_log('Allocate quantity error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to allocate quantity'];
    }
}

/**
 * Fulfill quantity for order item
 */
function fulfillQuantity($itemId, $fulfilledQty, $userId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            UPDATE order_items 
            SET fulfilled_quantity = ?, status = 'fulfilled' 
            WHERE id = ? AND quantity >= ?
        ");
        $result = $stmt->execute([$fulfilledQty, $itemId, $fulfilledQty]);
        
        if ($result && $stmt->rowCount() > 0) {
            logActivity('quantity_fulfilled', "Quantity fulfilled: {$fulfilledQty}", $userId);
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Invalid fulfillment quantity'];
        }
        
    } catch (Exception $e) {
        error_log('Fulfill quantity error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to fulfill quantity'];
    }
}

/**
 * Update order total amount based on items
 */
function updateOrderTotal($orderId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            UPDATE orders 
            SET total_amount = (
                SELECT COALESCE(SUM(total_price), 0) 
                FROM order_items 
                WHERE order_id = ?
            )
            WHERE id = ?
        ");
        return $stmt->execute([$orderId, $orderId]);
        
    } catch (Exception $e) {
        error_log('Update order total error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get order items statistics
 */
function getOrderItemsStats($orderId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_items,
                COALESCE(SUM(quantity), 0) as total_quantity,
                COALESCE(SUM(total_price), 0) as total_value,
                COALESCE(SUM(allocated_quantity), 0) as allocated_quantity,
                COALESCE(SUM(fulfilled_quantity), 0) as fulfilled_quantity,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_items,
                COUNT(CASE WHEN status = 'allocated' THEN 1 END) as allocated_items,
                COUNT(CASE WHEN status = 'processed' THEN 1 END) as processed_items,
                COUNT(CASE WHEN status = 'packaged' THEN 1 END) as packaged_items,
                COUNT(CASE WHEN status = 'fulfilled' THEN 1 END) as fulfilled_items
            FROM order_items 
            WHERE order_id = ?
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetch();
        
    } catch (Exception $e) {
        error_log('Get order items stats error: ' . $e->getMessage());
        return [
            'total_items' => 0,
            'total_quantity' => 0,
            'total_value' => 0,
            'allocated_quantity' => 0,
            'fulfilled_quantity' => 0,
            'pending_items' => 0,
            'allocated_items' => 0,
            'processed_items' => 0,
            'packaged_items' => 0,
            'fulfilled_items' => 0
        ];
    }
}

/**
 * Get available inventory items for order selection
 */
function getAvailableInventory() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT id, item_name, item_code, category, unit, quantity, quality_grade, section
            FROM inventory 
            WHERE status = 'active' AND quantity > 0
            ORDER BY section, category, item_name
        ");
        $stmt->execute();
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log('Get available inventory error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Validate order item data
 */
function validateOrderItemData($data, $isUpdate = false) {
    $errors = [];
    
    // Required fields
    if (empty($data['inventory_id'])) {
        $errors[] = 'Inventory item is required';
    }
    
    if (empty($data['quantity']) || $data['quantity'] <= 0) {
        $errors[] = 'Quantity must be greater than 0';
    }
    
    if (empty($data['unit_price']) || $data['unit_price'] <= 0) {
        $errors[] = 'Unit price must be greater than 0';
    }
    
    // Numeric validation
    if (!is_numeric($data['quantity'])) {
        $errors[] = 'Quantity must be a valid number';
    }
    
    if (!is_numeric($data['unit_price'])) {
        $errors[] = 'Unit price must be a valid number';
    }
    
    return $errors;
}

/**
 * Check if order can be fulfilled based on inventory
 */
function checkOrderFulfillment($orderId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT oi.quantity, i.quantity as available_quantity
            FROM order_items oi
            LEFT JOIN inventory i ON oi.inventory_id = i.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll();
        
        foreach ($items as $item) {
            if ($item['quantity'] > $item['available_quantity']) {
                return [
                    'can_fulfill' => false,
                    'message' => 'Insufficient inventory for some items'
                ];
            }
        }
        
        return ['can_fulfill' => true, 'message' => 'Order can be fulfilled'];
        
    } catch (Exception $e) {
        error_log('Check order fulfillment error: ' . $e->getMessage());
        return ['can_fulfill' => false, 'message' => 'Error checking fulfillment'];
    }
}

/**
 * Export order items to CSV
 */
function exportOrderItemsCSV($orderId) {
    global $conn;
    
    try {
        $items = getOrderItems($orderId);
        
        if (empty($items)) {
            return false;
        }
        
        $filename = "order_items_{$orderId}_" . date('Y-m-d') . ".csv";
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'Item Code',
            'Item Name',
            'Category',
            'Quantity',
            'Unit',
            'Unit Price',
            'Total Price',
            'Allocated Quantity',
            'Fulfilled Quantity',
            'Status',
            'Quality Requirements',
            'Packaging Requirements',
            'Notes'
        ]);
        
        // CSV data
        foreach ($items as $item) {
            fputcsv($output, [
                $item['item_code'] ?? '',
                $item['item_name'] ?? '',
                $item['category'] ?? '',
                $item['quantity'],
                $item['unit'] ?? '',
                $item['unit_price'],
                $item['total_price'],
                $item['allocated_quantity'],
                $item['fulfilled_quantity'],
                $item['status'],
                $item['quality_requirements'],
                $item['packaging_requirements'],
                $item['notes']
            ]);
        }
        
        fclose($output);
        return true;
        
    } catch (Exception $e) {
        error_log('Export order items CSV error: ' . $e->getMessage());
        return false;
    }
}

?>
