<?php
/**
 * KYA Food Production - Order Items Management
 * Full CRUD operations for order items
 */

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

SessionManager::start();
SessionManager::requireLogin();

// Restrict to admin (same as orders module)
if (!SessionManager::hasPermission('admin')) {
    header('Location: ../../dashboard.php?error=access_denied');
    exit();
}

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;

if ($orderId <= 0) {
    header('Location: index.php?error=invalid_order');
    exit();
}

$db = new Database();
$conn = $db->connect();
$userInfo = SessionManager::getUserInfo();

// Load order information
try {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Location: index.php?error=order_not_found');
        exit();
    }
} catch (Exception $e) {
    error_log('Load order error: ' . $e->getMessage());
    header('Location: index.php?error=order_load_failed');
    exit();
}

// Handle form submissions
$successMessage = '';
$errorMessage = '';
$editItem = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_item':
                $result = addOrderItem($orderId, $_POST, $userInfo['id']);
                if ($result['success']) {
                    $successMessage = "Order item added successfully!";
                } else {
                    $errorMessage = $result['message'];
                }
                break;
                
            case 'update_item':
                $result = updateOrderItem($_POST['item_id'], $_POST, $userInfo['id']);
                if ($result['success']) {
                    $successMessage = "Order item updated successfully!";
                    $editId = 0; // Reset edit mode after successful update
                } else {
                    $errorMessage = $result['message'];
                    $editId = $_POST['item_id']; // Stay in edit mode
                }
                break;
                
            case 'delete_item':
                $result = deleteOrderItem($_POST['item_id'], $userInfo['id']);
                if ($result['success']) {
                    $successMessage = "Order item deleted successfully!";
                } else {
                    $errorMessage = $result['message'];
                }
                break;
                
            case 'update_status':
                $result = updateItemStatus($_POST['item_id'], $_POST['status'], $userInfo['id']);
                if ($result['success']) {
                    $successMessage = "Item status updated successfully!";
                } else {
                    $errorMessage = $result['message'];
                }
                break;
                
            case 'allocate_quantity':
                $result = allocateQuantity($_POST['item_id'], $_POST['allocated_quantity'], $userInfo['id']);
                if ($result['success']) {
                    $successMessage = "Quantity allocated successfully!";
                } else {
                    $errorMessage = $result['message'];
                }
                break;
                
            case 'fulfill_quantity':
                $result = fulfillQuantity($_POST['item_id'], $_POST['fulfilled_quantity'], $userInfo['id']);
                if ($result['success']) {
                    $successMessage = "Quantity fulfilled successfully!";
                } else {
                    $errorMessage = $result['message'];
                }
                break;
        }
    }
}

// Load edit item if in edit mode
if ($editId > 0) {
    try {
        $stmt = $conn->prepare("
            SELECT oi.*, i.item_name, i.item_code, i.category, i.unit, i.quality_grade
            FROM order_items oi
            LEFT JOIN inventory i ON oi.inventory_id = i.id
            WHERE oi.id = ? AND oi.order_id = ?
        ");
        $stmt->execute([$editId, $orderId]);
        $editItem = $stmt->fetch();
        
        if (!$editItem) {
            $errorMessage = "Order item not found";
            $editId = 0;
        }
    } catch (Exception $e) {
        error_log('Load edit item error: ' . $e->getMessage());
        $errorMessage = "Failed to load order item";
        $editId = 0;
    }
}

// Load all order items
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
    $orderItems = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Load order items error: ' . $e->getMessage());
    $orderItems = [];
}

// Load available inventory items for selection
try {
    $stmt = $conn->prepare("
        SELECT id, item_name, item_code, category, unit, quantity, quality_grade, section
        FROM inventory 
        WHERE status = 'active' AND quantity > 0
        ORDER BY section, category, item_name
    ");
    $stmt->execute();
    $inventoryItems = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Load inventory error: ' . $e->getMessage());
    $inventoryItems = [];
}

$pageTitle = 'Manage Order Items - ' . htmlspecialchars($order['order_number']);

// CRUD Functions
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
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Failed to add order item'];
        }
        
    } catch (Exception $e) {
        error_log('Add order item error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

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

include '../../includes/header.php';
?>

<div class="content-area">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Order Items Management</h1>
            <p class="text-muted mb-0">Manage items for Order <?php echo htmlspecialchars($order['order_number']); ?></p>
        </div>
        <div>
            <a href="view_order.php?id=<?php echo $orderId; ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Order
            </a>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($successMessage): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $successMessage; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($errorMessage): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $errorMessage; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Order Summary -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stats-card primary">
                <div class="stats-icon primary"><i class="fas fa-shopping-cart"></i></div>
                <div class="stats-number"><?php echo htmlspecialchars($order['order_number']); ?></div>
                <div class="stats-label">Order Number</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card info">
                <div class="stats-icon info"><i class="fas fa-boxes"></i></div>
                <div class="stats-number"><?php echo count($orderItems); ?></div>
                <div class="stats-label">Total Items</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card success">
                <div class="stats-icon success"><i class="fas fa-dollar-sign"></i></div>
                <div class="stats-number"><?php echo formatCurrency($order['total_amount']); ?></div>
                <div class="stats-label">Order Total</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card warning">
                <div class="stats-icon warning"><i class="fas fa-user"></i></div>
                <div class="stats-number"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                <div class="stats-label">Customer</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Add/Edit Item Form -->
        <div class="col-lg-5 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plus me-2"></i>
                        <?php echo $editItem ? 'Edit Order Item' : 'Add Order Item'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="itemForm">
                        <input type="hidden" name="action" value="<?php echo $editItem ? 'update_item' : 'add_item'; ?>">
                        <?php if ($editItem): ?>
                            <input type="hidden" name="item_id" value="<?php echo $editItem['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="inventory_id" class="form-label">Inventory Item *</label>
                            <select name="inventory_id" id="inventory_id" class="form-select" required>
                                <option value="">Select Inventory Item</option>
                                <?php foreach ($inventoryItems as $item): ?>
                                    <option value="<?php echo $item['id']; ?>"
                                            data-unit="<?php echo htmlspecialchars($item['unit']); ?>"
                                            data-quantity="<?php echo $item['quantity']; ?>"
                                            <?php echo ($editItem && $editItem['inventory_id'] == $item['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($item['section']); ?> - 
                                        <?php echo htmlspecialchars($item['item_name']); ?> 
                                        (<?php echo htmlspecialchars($item['item_code']); ?>)
                                        - Stock: <?php echo number_format($item['quantity'], 2); ?> <?php echo htmlspecialchars($item['unit']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="quantity" class="form-label">Quantity *</label>
                                <input type="number" step="0.01" min="0.01" name="quantity" id="quantity" 
                                       class="form-control" required
                                       value="<?php echo $editItem ? $editItem['quantity'] : ''; ?>">
                                <small class="text-muted">Available: <span id="available_qty">0</span></small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="unit_price" class="form-label">Unit Price *</label>
                                <input type="number" step="0.01" min="0.01" name="unit_price" id="unit_price" 
                                       class="form-control" required
                                       value="<?php echo $editItem ? $editItem['unit_price'] : ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="quality_requirements" class="form-label">Quality Requirements</label>
                            <textarea name="quality_requirements" id="quality_requirements" 
                                      class="form-control" rows="3"><?php echo $editItem ? htmlspecialchars($editItem['quality_requirements']) : ''; ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="packaging_requirements" class="form-label">Packaging Requirements</label>
                            <textarea name="packaging_requirements" id="packaging_requirements" 
                                      class="form-control" rows="3"><?php echo $editItem ? htmlspecialchars($editItem['packaging_requirements']) : ''; ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea name="notes" id="notes" 
                                      class="form-control" rows="2"><?php echo $editItem ? htmlspecialchars($editItem['notes']) : ''; ?></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i><?php echo $editItem ? 'Update Item' : 'Add Item'; ?>
                            </button>
                            <?php if ($editItem): ?>
                                <a href="order_items.php?order_id=<?php echo $orderId; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Order Items List -->
        <div class="col-lg-7 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Order Items</h5>
                    <div>
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="exportItems()">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($orderItems)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Allocated</th>
                                        <th>Fulfilled</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($item['item_code']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo number_format($item['quantity'], 2); ?> <?php echo htmlspecialchars($item['unit']); ?>
                                            </td>
                                            <td>
                                                <div>
                                                    <?php echo formatCurrency($item['unit_price']); ?>
                                                    <br><strong><?php echo formatCurrency($item['total_price']); ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span><?php echo number_format($item['allocated_quantity'], 2); ?></span>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="showAllocateModal(<?php echo $item['id']; ?>, <?php echo $item['allocated_quantity']; ?>, <?php echo $item['quantity']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span><?php echo number_format($item['fulfilled_quantity'], 2); ?></span>
                                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                                            onclick="showFulfillModal(<?php echo $item['id']; ?>, <?php echo $item['fulfilled_quantity']; ?>, <?php echo $item['quantity']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $statusColors = [
                                                    'pending' => 'warning',
                                                    'allocated' => 'info',
                                                    'processed' => 'primary',
                                                    'packaged' => 'secondary',
                                                    'fulfilled' => 'success'
                                                ];
                                                $statusColor = $statusColors[$item['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $statusColor; ?>">
                                                    <?php echo ucfirst($item['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="order_items.php?order_id=<?php echo $orderId; ?>&edit_id=<?php echo $item['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteItem(<?php echo $item['id']; ?>)" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-active fw-bold">
                                        <td colspan="2">Total</td>
                                        <td><?php echo formatCurrency(array_sum(array_column($orderItems, 'total_price'))); ?></td>
                                        <td colspan="4"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No order items</h5>
                            <p class="text-muted">Add items to this order using the form.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Allocate Quantity Modal -->
<div class="modal fade" id="allocateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Allocate Quantity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="allocate_quantity">
                    <input type="hidden" name="item_id" id="allocate_item_id">
                    
                    <div class="mb-3">
                        <label for="allocate_quantity" class="form-label">Allocate Quantity</label>
                        <input type="number" step="0.01" min="0" name="allocated_quantity" id="allocate_quantity" 
                               class="form-control" required>
                        <small class="text-muted">Maximum: <span id="max_allocate">0</span></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Allocate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Fulfill Quantity Modal -->
<div class="modal fade" id="fulfillModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Fulfill Quantity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="fulfill_quantity">
                    <input type="hidden" name="item_id" id="fulfill_item_id">
                    
                    <div class="mb-3">
                        <label for="fulfill_quantity" class="form-label">Fulfill Quantity</label>
                        <input type="number" step="0.01" min="0" name="fulfilled_quantity" id="fulfill_quantity" 
                               class="form-control" required>
                        <small class="text-muted">Maximum: <span id="max_fulfill">0</span></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Fulfill</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Update available quantity when inventory item changes
document.getElementById('inventory_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const unit = selectedOption.dataset.unit || '';
    const quantity = parseFloat(selectedOption.dataset.quantity) || 0;
    
    document.getElementById('available_qty').textContent = quantity + ' ' + unit;
    
    // Set max quantity
    document.getElementById('quantity').max = quantity;
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const inventorySelect = document.getElementById('inventory_id');
    if (inventorySelect.value) {
        inventorySelect.dispatchEvent(new Event('change'));
    }
});

function showAllocateModal(itemId, currentQty, maxQty) {
    document.getElementById('allocate_item_id').value = itemId;
    document.getElementById('allocate_quantity').value = currentQty;
    document.getElementById('max_allocate').textContent = maxQty;
    document.getElementById('allocate_quantity').max = maxQty;
    new bootstrap.Modal(document.getElementById('allocateModal')).show();
}

function showFulfillModal(itemId, currentQty, maxQty) {
    document.getElementById('fulfill_item_id').value = itemId;
    document.getElementById('fulfill_quantity').value = currentQty;
    document.getElementById('max_fulfill').textContent = maxQty;
    document.getElementById('fulfill_quantity').max = maxQty;
    new bootstrap.Modal(document.getElementById('fulfillModal')).show();
}

function deleteItem(itemId) {
    if (confirm('Are you sure you want to delete this order item?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_item">
            <input type="hidden" name="item_id" value="${itemId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function exportItems() {
    window.print();
}
</script>

<?php include '../../includes/footer.php'; ?>
