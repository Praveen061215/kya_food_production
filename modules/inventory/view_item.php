<?php
/**
 * KYA Food Production - View Inventory Item Details
 * Display comprehensive information about a single inventory item
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

// Get item ID
$itemId = $_GET['id'] ?? 0;
if (!$itemId || !is_numeric($itemId)) {
    header('Location: index.php?error=invalid_item');
    exit();
}

$userInfo = SessionManager::getUserInfo();
$db = new Database();
$conn = $db->connect();

// Get inventory item details
try {
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
        header('Location: index.php?error=item_not_found');
        exit();
    }

    // Check section permissions for non-admin users
    if ($userInfo['role'] !== 'admin' && !in_array($item['section'], $userInfo['sections'])) {
        header('Location: index.php?error=access_denied');
        exit();
    }

    // Get transaction history for this item
    $historyStmt = $conn->prepare("
        SELECT h.*, u.full_name as user_name
        FROM inventory_history h
        LEFT JOIN users u ON h.user_id = u.id
        WHERE h.inventory_id = ?
        ORDER BY h.created_at DESC
        LIMIT 20
    ");
    $historyStmt->execute([$itemId]);
    $history = $historyStmt->fetchAll();

    // Get related orders using this item
    $ordersStmt = $conn->prepare("
        SELECT oi.*, o.order_number, o.customer_name, o.order_date, o.status as order_status
        FROM order_items oi
        LEFT JOIN orders o ON oi.order_id = o.id
        WHERE oi.inventory_id = ?
        ORDER BY o.order_date DESC
        LIMIT 10
    ");
    $ordersStmt->execute([$itemId]);
    $orders = $ordersStmt->fetchAll();

} catch (Exception $e) {
    error_log('View inventory item error: ' . $e->getMessage());
    header('Location: index.php?error=database_error');
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

$pageTitle = "Inventory Item - " . htmlspecialchars($item['item_name']);
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0">
            <i class="fas fa-box me-2"></i>Inventory Item Details
        </h2>
        <div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Inventory
            </a>
            <?php if (SessionManager::hasPermission('inventory_manage')): ?>
                <a href="edit_item.php?id=<?php echo $item['id']; ?>" class="btn btn-primary ms-2">
                    <i class="fas fa-edit me-2"></i>Edit Item
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alert Status -->
    <?php if ($alertStatus !== 'normal' && $alertStatus !== 'inactive'): ?>
        <div class="alert alert-<?php echo $alertColor; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong><?php echo ucfirst(str_replace('_', ' ', $alertStatus)); ?>:</strong> <?php echo $alertMessage; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Item Details Cards -->
    <div class="row">
        <!-- Basic Information -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Basic Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Item Code</label>
                            <div class="fw-bold"><?php echo htmlspecialchars($item['item_code']); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Section</label>
                            <div>
                                <span class="badge" style="background-color: <?php echo getSectionColor($item['section']); ?>">
                                    Section <?php echo $item['section']; ?> - <?php echo getSectionName($item['section']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Category</label>
                            <div class="fw-bold"><?php echo htmlspecialchars($item['category']); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Subcategory</label>
                            <div><?php echo $item['subcategory'] ? htmlspecialchars($item['subcategory']) : '<span class="text-muted">Not specified</span>'; ?></div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label text-muted">Item Name</label>
                            <div class="fw-bold fs-5"><?php echo htmlspecialchars($item['item_name']); ?></div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label text-muted">Description</label>
                            <div><?php echo $item['description'] ? htmlspecialchars($item['description']) : '<span class="text-muted">No description</span>'; ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Status</label>
                            <div><?php echo getStatusBadge($item['status'], INVENTORY_STATUS); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Quality Grade</label>
                            <div>
                                <span class="badge bg-<?php echo $item['quality_grade'] === 'A' ? 'success' : ($item['quality_grade'] === 'B' ? 'info' : ($item['quality_grade'] === 'C' ? 'warning' : 'danger')); ?>">
                                    Grade <?php echo htmlspecialchars($item['quality_grade']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Information -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Stock Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Current Quantity</label>
                            <div class="fw-bold fs-4"><?php echo number_format($item['quantity'], 3); ?> <?php echo htmlspecialchars($item['unit']); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Unit Cost</label>
                            <div class="fw-bold"><?php echo $item['unit_cost'] ? formatCurrency($item['unit_cost']) : 'Not set'; ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Total Value</label>
                            <div class="fw-bold fs-5 text-primary">
                                <?php echo $item['total_value'] ? formatCurrency($item['total_value']) : 'Not calculated'; ?>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Minimum Threshold</label>
                            <div><?php echo number_format($item['min_threshold'], 3); ?> <?php echo htmlspecialchars($item['unit']); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Maximum Threshold</label>
                            <div><?php echo number_format($item['max_threshold'], 3); ?> <?php echo htmlspecialchars($item['unit']); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Reorder Level</label>
                            <div><?php echo $item['reorder_level'] ? number_format($item['reorder_level'], 3) . ' ' . htmlspecialchars($item['unit']) : 'Not set'; ?></div>
                        </div>
                    </div>

                    <!-- Stock Level Visual -->
                    <div class="mt-3">
                        <label class="form-label text-muted">Stock Level</label>
                        <div class="progress" style="height: 25px;">
                            <?php
                            $percentage = $item['max_threshold'] > 0 ? ($item['quantity'] / $item['max_threshold']) * 100 : 0;
                            $progressColor = $percentage <= 20 ? 'danger' : ($percentage <= 50 ? 'warning' : 'success');
                            ?>
                            <div class="progress-bar bg-<?php echo $progressColor; ?>" 
                                 style="width: <?php echo min($percentage, 100); ?>%">
                                <?php echo round($percentage); ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Information -->
    <div class="row">
        <!-- Dates & Batch -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar me-2"></i>Dates & Batch Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Batch Number</label>
                            <div class="fw-bold"><?php echo $item['batch_number'] ? htmlspecialchars($item['batch_number']) : '<span class="text-muted">Not set</span>'; ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Manufacture Date</label>
                            <div><?php echo $item['manufacture_date'] ? formatDate($item['manufacture_date']) : '<span class="text-muted">Not set</span>'; ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Expiry Date</label>
                            <div>
                                <?php if ($item['expiry_date']): ?>
                                    <?php 
                                    $daysToExpiry = (strtotime($item['expiry_date']) - time()) / (24 * 60 * 60);
                                    $expiryClass = $daysToExpiry <= 7 ? 'text-danger' : ($daysToExpiry <= 30 ? 'text-warning' : '');
                                    ?>
                                    <span class="<?php echo $expiryClass; ?> fw-bold">
                                        <?php echo formatDate($item['expiry_date']); ?>
                                    </span>
                                    <?php if ($daysToExpiry > 0): ?>
                                        <br><small class="<?php echo $expiryClass; ?>"><?php echo floor($daysToExpiry); ?> days remaining</small>
                                    <?php else: ?>
                                        <br><small class="text-danger">Expired</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Not set</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Created Date</label>
                            <div><?php echo formatDate($item['created_at']); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Last Updated</label>
                            <div><?php echo $item['updated_at'] ? formatDate($item['updated_at']) : '<span class="text-muted">Never updated</span>'; ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Created By</label>
                            <div><?php echo $item['created_by_name'] ? htmlspecialchars($item['created_by_name']) : '<span class="text-muted">Unknown</span>'; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Storage & Supplier -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-warehouse me-2"></i>Storage & Supplier
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Storage Location</label>
                            <div><?php echo $item['storage_location'] ? htmlspecialchars($item['storage_location']) : '<span class="text-muted">Not specified</span>'; ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Storage Temperature</label>
                            <div><?php echo $item['storage_temperature'] ? htmlspecialchars($item['storage_temperature']) . '°C' : '<span class="text-muted">Not specified</span>'; ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Storage Humidity</label>
                            <div><?php echo $item['storage_humidity'] ? htmlspecialchars($item['storage_humidity']) . '%' : '<span class="text-muted">Not specified</span>'; ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Supplier</label>
                            <div>
                                <?php if ($item['supplier_name']): ?>
                                    <div class="fw-bold"><?php echo htmlspecialchars($item['supplier_name']); ?></div>
                                    <?php if ($item['supplier_email']): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($item['supplier_email']); ?></small><br>
                                    <?php endif; ?>
                                    <?php if ($item['supplier_phone']): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($item['supplier_phone']); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">No supplier assigned</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($item['notes']): ?>
                        <div class="mt-3">
                            <label class="form-label text-muted">Notes</label>
                            <div class="alert alert-info">
                                <?php echo htmlspecialchars($item['notes']); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Orders -->
    <?php if (!empty($orders)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-shopping-cart me-2"></i>Related Orders
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order Number</th>
                                <th>Customer</th>
                                <th>Quantity Used</th>
                                <th>Unit Price</th>
                                <th>Total Price</th>
                                <th>Order Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <a href="../orders/view_order.php?id=<?php echo $order['order_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($order['order_number']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo number_format($order['quantity'], 2); ?> <?php echo htmlspecialchars($item['unit']); ?></td>
                                    <td><?php echo formatCurrency($order['unit_price']); ?></td>
                                    <td><?php echo formatCurrency($order['total_price']); ?></td>
                                    <td><?php echo formatDate($order['order_date']); ?></td>
                                    <td><?php echo getStatusBadge($order['order_status'], ORDER_STATUS); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Transaction History -->
    <?php if (!empty($history)): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>Transaction History
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Transaction Type</th>
                                <th>Quantity Change</th>
                                <th>Previous Quantity</th>
                                <th>New Quantity</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $entry): ?>
                                <tr>
                                    <td><?php echo formatDate($entry['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['user_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $entry['transaction_type'] === 'in' ? 'success' : ($entry['transaction_type'] === 'out' ? 'danger' : 'info'); ?>">
                                            <?php echo ucfirst($entry['transaction_type']); ?>
                                        </span>
                                    </td>
                                    <td class="<?php echo $entry['transaction_type'] === 'in' ? 'text-success' : ($entry['transaction_type'] === 'out' ? 'text-danger' : 'text-info'); ?>">
                                        <?php echo $entry['transaction_type'] === 'in' ? '+' : ($entry['transaction_type'] === 'out' ? '-' : ''); ?>
                                        <?php echo number_format($entry['quantity_change'], 3); ?>
                                    </td>
                                    <td><?php echo number_format($entry['previous_quantity'], 3); ?></td>
                                    <td><?php echo number_format($entry['new_quantity'], 3); ?></td>
                                    <td><?php echo $entry['notes'] ? htmlspecialchars($entry['notes']) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
