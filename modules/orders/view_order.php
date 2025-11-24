<?php
/**
 * KYA Food Production - View Order
 * Read-only view of a single customer order
 */

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

SessionManager::start();
SessionManager::requireLogin();

// Restrict to admin (same as orders index)
if (!SessionManager::hasPermission('admin')) {
    header('Location: ../../dashboard.php?error=access_denied');
    exit();
}

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($orderId <= 0) {
    header('Location: index.php?error=invalid_order');
    exit();
}

$db = new Database();
$conn = $db->connect();

// Load order with creator/assignee names
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
        header('Location: index.php?error=order_not_found');
        exit();
    }
} catch (Exception $e) {
    error_log('View order error: ' . $e->getMessage());
    header('Location: index.php?error=order_load_failed');
    exit();
}

$pageTitle = 'View Order #' . htmlspecialchars($order['order_number']);

include '../../includes/header.php';
?>

<div class="content-area">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Order <?php echo htmlspecialchars($order['order_number']); ?></h1>
            <p class="text-muted mb-0">Detailed information for this customer order.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Orders
            </a>
            <a href="order_invoice.php?id=<?php echo (int)$order['id']; ?>" target="_blank" class="btn btn-success">
                <i class="fas fa-file-pdf me-2"></i>Generate Invoice
            </a>
            <a href="edit_order.php?id=<?php echo (int)$order['id']; ?>" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i>Edit Order
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="stats-card primary">
                <div class="stats-icon primary"><i class="fas fa-user"></i></div>
                <div class="stats-number"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                <div class="stats-label">Customer</div>
                <?php if ($order['customer_email']): ?>
                    <div class="stats-sublabel"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stats-card info">
                <div class="stats-icon info"><i class="fas fa-calendar-alt"></i></div>
                <div class="stats-number"><?php echo formatDate($order['order_date']); ?></div>
                <div class="stats-label">Order Date</div>
                <div class="stats-sublabel">
                    Required: 
                    <?php echo $order['required_date'] ? formatDate($order['required_date']) : 'Not set'; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stats-card success">
                <div class="stats-icon success"><i class="fas fa-dollar-sign"></i></div>
                <div class="stats-number"><?php echo formatCurrency($order['total_amount'], $order['currency']); ?></div>
                <div class="stats-label">Total Amount</div>
                <div class="stats-sublabel">Payment: <?php echo ucfirst($order['payment_status']); ?></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Order Details</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Order Number</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($order['order_number']); ?></dd>

                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            <?php
                            $statusColors = [
                                'pending' => 'warning',
                                'processing' => 'info',
                                'quality_check' => 'primary',
                                'packaging' => 'secondary',
                                'ready_to_ship' => 'success',
                                'shipped' => 'success',
                                'delivered' => 'success',
                                'cancelled' => 'danger'
                            ];
                            $statusColor = $statusColors[$order['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $statusColor; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                            </span>
                        </dd>

                        <dt class="col-sm-4">Priority</dt>
                        <dd class="col-sm-8"><?php echo ucfirst($order['priority']); ?></dd>

                        <dt class="col-sm-4">Export Country</dt>
                        <dd class="col-sm-8"><?php echo $order['export_country'] ? htmlspecialchars($order['export_country']) : 'N/A'; ?></dd>

                        <dt class="col-sm-4">Created By</dt>
                        <dd class="col-sm-8"><?php echo $order['created_by_name'] ? htmlspecialchars($order['created_by_name']) : 'N/A'; ?></dd>

                        <dt class="col-sm-4">Assigned To</dt>
                        <dd class="col-sm-8"><?php echo $order['assigned_to_name'] ? htmlspecialchars($order['assigned_to_name']) : 'Unassigned'; ?></dd>

                        <dt class="col-sm-4">Created At</dt>
                        <dd class="col-sm-8"><?php echo formatDate($order['created_at'], 'long'); ?></dd>

                        <dt class="col-sm-4">Last Updated</dt>
                        <dd class="col-sm-8"><?php echo formatDate($order['updated_at'], 'long'); ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Customer & Shipping</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Customer Phone</dt>
                        <dd class="col-sm-8"><?php echo $order['customer_phone'] ? htmlspecialchars($order['customer_phone']) : 'N/A'; ?></dd>

                        <dt class="col-sm-4">Address</dt>
                        <dd class="col-sm-8"><?php echo $order['customer_address'] ? nl2br(htmlspecialchars($order['customer_address'])) : 'N/A'; ?></dd>

                        <dt class="col-sm-4">Shipping Method</dt>
                        <dd class="col-sm-8"><?php echo $order['shipping_method'] ? htmlspecialchars($order['shipping_method']) : 'N/A'; ?></dd>

                        <dt class="col-sm-4">Tracking Number</dt>
                        <dd class="col-sm-8"><?php echo $order['tracking_number'] ? htmlspecialchars($order['tracking_number']) : 'N/A'; ?></dd>

                        <dt class="col-sm-4">Special Instructions</dt>
                        <dd class="col-sm-8"><?php echo $order['special_instructions'] ? nl2br(htmlspecialchars($order['special_instructions'])) : 'None'; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Items Section -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Order Items</h5>
            <div>
                <a href="order_items.php?order_id=<?php echo (int)$order['id']; ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i>Manage Items
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php
            // Load order items
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
            ?>

            <?php if (!empty($orderItems)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total Price</th>
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
                                        <span class="badge bg-light text-dark"><?php echo htmlspecialchars($item['item_code']); ?></span>
                                        <?php if ($item['inventory_section']): ?>
                                            <br><small class="text-muted">Section <?php echo $item['inventory_section']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                        <?php if ($item['quality_grade']): ?>
                                            <br><span class="badge bg-info"><?php echo $item['quality_grade']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                                    <td>
                                        <?php echo number_format($item['quantity'], 2); ?> <?php echo htmlspecialchars($item['unit']); ?>
                                        <?php if ($item['notes']): ?>
                                            <br><small class="text-muted" title="<?php echo htmlspecialchars($item['notes']); ?>">
                                                <i class="fas fa-sticky-note"></i> Notes
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatCurrency($item['unit_price']); ?></td>
                                    <td><strong><?php echo formatCurrency($item['total_price']); ?></strong></td>
                                    <td>
                                        <?php echo number_format($item['allocated_quantity'], 2); ?>
                                        <?php 
                                        $allocPercent = $item['quantity'] > 0 ? ($item['allocated_quantity'] / $item['quantity']) * 100 : 0;
                                        ?>
                                        <div class="progress mt-1" style="height: 4px;">
                                            <div class="progress-bar" style="width: <?php echo min($allocPercent, 100); ?>%;"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo number_format($item['fulfilled_quantity'], 2); ?>
                                        <?php 
                                        $fulfillPercent = $item['quantity'] > 0 ? ($item['fulfilled_quantity'] / $item['quantity']) * 100 : 0;
                                        ?>
                                        <div class="progress mt-1" style="height: 4px;">
                                            <div class="progress-bar bg-success" style="width: <?php echo min($fulfillPercent, 100); ?>%;"></div>
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
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editOrderItem(<?php echo $item['id']; ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                    onclick="updateItemStatus(<?php echo $item['id']; ?>, '<?php echo $item['status']; ?>')" title="Update Status">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-active fw-bold">
                                <td colspan="5">Total</td>
                                <td><?php echo formatCurrency(array_sum(array_column($orderItems, 'total_price'))); ?></td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Requirements Summary -->
                <?php if (!empty(array_filter(array_column($orderItems, 'quality_requirements'))) || !empty(array_filter(array_column($orderItems, 'packaging_requirements')))): ?>
                    <div class="row mt-4">
                        <?php if (!empty(array_filter(array_column($orderItems, 'quality_requirements')))): ?>
                            <div class="col-md-6">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fas fa-award me-2"></i>Quality Requirements</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach ($orderItems as $item): ?>
                                            <?php if ($item['quality_requirements']): ?>
                                                <div class="mb-2">
                                                    <strong><?php echo htmlspecialchars($item['item_name']); ?>:</strong>
                                                    <p class="mb-1 small text-muted"><?php echo nl2br(htmlspecialchars($item['quality_requirements'])); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty(array_filter(array_column($orderItems, 'packaging_requirements')))): ?>
                            <div class="col-md-6">
                                <div class="card border-secondary">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0"><i class="fas fa-box me-2"></i>Packaging Requirements</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach ($orderItems as $item): ?>
                                            <?php if ($item['packaging_requirements']): ?>
                                                <div class="mb-2">
                                                    <strong><?php echo htmlspecialchars($item['item_name']); ?>:</strong>
                                                    <p class="mb-1 small text-muted"><?php echo nl2br(htmlspecialchars($item['packaging_requirements'])); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No order items</h5>
                    <p class="text-muted">This order doesn't have any items yet.</p>
                    <a href="order_items.php?order_id=<?php echo (int)$order['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Order Items
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Update Item Status Modal -->
<div class="modal fade" id="itemStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Item Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="order_items.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="item_id" id="item_status_id">
                    <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                    
                    <div class="mb-3">
                        <label for="item_status_select" class="form-label">New Status</label>
                        <select name="status" id="item_status_select" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="allocated">Allocated</option>
                            <option value="processed">Processed</option>
                            <option value="packaged">Packaged</option>
                            <option value="fulfilled">Fulfilled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editOrderItem(itemId) {
    window.location.href = 'order_items.php?order_id=<?php echo (int)$order['id']; ?>&edit_id=' + itemId;
}

function updateItemStatus(itemId, currentStatus) {
    document.getElementById('item_status_id').value = itemId;
    document.getElementById('item_status_select').value = currentStatus;
    new bootstrap.Modal(document.getElementById('itemStatusModal')).show();
}
</script>

<?php include '../../includes/footer.php'; ?>
