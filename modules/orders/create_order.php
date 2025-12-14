<?php
/**
 * KYA Food Production - Create Order
 * Full page for creating new customer orders
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

$userInfo = SessionManager::getUserInfo();

$db = new Database();
$conn = $db->connect();

$pageTitle = 'Create New Order';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName   = trim($_POST['customer_name'] ?? '');
    $customerEmail  = trim($_POST['customer_email'] ?? '');
    $customerPhone  = trim($_POST['customer_phone'] ?? '');
    $deliveryAddress = trim($_POST['delivery_address'] ?? '');
    $orderDate      = $_POST['order_date'] ?? date('Y-m-d');
    $totalAmount    = (float)($_POST['total_amount'] ?? 0);
    $notes          = trim($_POST['notes'] ?? '');

    if ($customerName === '') {
        $errorMessage = 'Customer name is required.';
    } else {
        try {
            $orderNumber = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);

            $stmt = $conn->prepare("INSERT INTO orders (
                    order_number,
                    customer_name,
                    customer_email,
                    customer_phone,
                    delivery_address,
                    order_date,
                    status,
                    total_amount,
                    notes,
                    created_by
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?
                )");

            $stmt->execute([
                $orderNumber,
                $customerName,
                $customerEmail ?: null,
                $customerPhone ?: null,
                $deliveryAddress ?: null,
                $orderDate,
                $totalAmount,
                $notes ?: null,
                $userInfo['id'] ?? null
            ]);

            SessionManager::logActivity('order_created', 'orders', $conn->lastInsertId(), null, [
                'order_number' => $orderNumber,
                'customer_name' => $customerName,
                'total_amount' => $totalAmount
            ]);

            header('Location: index.php?success=order_created');
            exit();
        } catch (Exception $e) {
            error_log('Create order error: ' . $e->getMessage());
            $errorMessage = 'Failed to create order. Please try again.';
        }
    }
}

include '../../includes/header.php';
?>

<div class="content-area">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Create New Order</h1>
            <p class="text-muted mb-0">Capture key customer and order details to start processing.</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Orders
            </a>
        </div>
    </div>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($errorMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <label for="customer_name" class="form-label">Customer Name<span class="text-danger">*</span></label>
                    <input type="text" name="customer_name" id="customer_name" class="form-control" required
                           value="<?php echo htmlspecialchars($_POST['customer_name'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label for="customer_email" class="form-label">Customer Email</label>
                    <input type="email" name="customer_email" id="customer_email" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['customer_email'] ?? ''); ?>">
                </div>

                <div class="col-md-6">
                    <label for="customer_phone" class="form-label">Customer Phone</label>
                    <input type="tel" name="customer_phone" id="customer_phone" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['customer_phone'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label for="order_date" class="form-label">Order Date</label>
                    <input type="date" name="order_date" id="order_date" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['order_date'] ?? date('Y-m-d')); ?>">
                </div>

                <div class="col-md-12">
                    <label for="delivery_address" class="form-label">Delivery Address</label>
                    <textarea name="delivery_address" id="delivery_address" class="form-control" rows="2"><?php echo htmlspecialchars($_POST['delivery_address'] ?? ''); ?></textarea>
                </div>

                <div class="col-md-6">
                    <label for="total_amount" class="form-label">Total Amount (₹)</label>
                    <input type="number" step="0.01" min="0" name="total_amount" id="total_amount" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['total_amount'] ?? '0'); ?>">
                </div>

                <div class="col-md-12">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                </div>

                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Create Order
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
