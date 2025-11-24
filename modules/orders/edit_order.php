<?php
/**
 * KYA Food Production - Edit Order
 * Edit core details of an existing customer order
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

$userInfo = SessionManager::getUserInfo();

$db = new Database();
$conn = $db->connect();

$errorMessage = '';

// Load existing order
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

$pageTitle = 'Edit Order #' . htmlspecialchars($order['order_number']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName   = trim($_POST['customer_name'] ?? '');
    $customerEmail  = trim($_POST['customer_email'] ?? '');
    $exportCountry  = trim($_POST['export_country'] ?? '');
    $orderDate      = $_POST['order_date'] ?? $order['order_date'];
    $requiredDate   = $_POST['required_date'] ?? $order['required_date'];
    $priority       = $_POST['priority'] ?? $order['priority'];
    $currency       = $_POST['currency'] ?? $order['currency'];
    $totalAmount    = (float)($_POST['total_amount'] ?? $order['total_amount']);
    $paymentStatus  = $_POST['payment_status'] ?? $order['payment_status'];
    $status         = $_POST['status'] ?? $order['status'];

    if ($customerName === '') {
        $errorMessage = 'Customer name is required.';
    } else {
        try {
            $stmt = $conn->prepare("UPDATE orders SET
                    customer_name = ?,
                    customer_email = ?,
                    export_country = ?,
                    order_date = ?,
                    required_date = ?,
                    status = ?,
                    priority = ?,
                    total_amount = ?,
                    currency = ?,
                    payment_status = ?,
                    updated_at = NOW()
                WHERE id = ?");

            $stmt->execute([
                $customerName,
                $customerEmail ?: null,
                $exportCountry ?: null,
                $orderDate,
                $requiredDate ?: null,
                $status,
                $priority,
                $totalAmount,
                $currency,
                $paymentStatus,
                $orderId
            ]);

            SessionManager::logActivity('order_updated', 'orders', $orderId, $order, [
                'customer_name' => $customerName,
                'status' => $status,
                'priority' => $priority,
                'total_amount' => $totalAmount
            ]);

            header('Location: index.php?success=order_updated');
            exit();
        } catch (Exception $e) {
            error_log('Update order error: ' . $e->getMessage());
            $errorMessage = 'Failed to update order. Please try again.';
        }
    }
}

include '../../includes/header.php';
?>

<div class="content-area">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Edit Order <?php echo htmlspecialchars($order['order_number']); ?></h1>
            <p class="text-muted mb-0">Update key details for this customer order.</p>
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
                           value="<?php echo htmlspecialchars($_POST['customer_name'] ?? $order['customer_name']); ?>">
                </div>
                <div class="col-md-6">
                    <label for="customer_email" class="form-label">Customer Email</label>
                    <input type="email" name="customer_email" id="customer_email" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['customer_email'] ?? ($order['customer_email'] ?? '')); ?>">
                </div>

                <div class="col-md-4">
                    <label for="export_country" class="form-label">Export Country</label>
                    <?php $countrySel = $_POST['export_country'] ?? ($order['export_country'] ?? ''); ?>
                    <select name="export_country" id="export_country" class="form-select">
                        <option value="" <?php echo $countrySel === '' ? 'selected' : ''; ?>>Select country...</option>
                        <option value="Sri Lanka" <?php echo $countrySel === 'Sri Lanka' ? 'selected' : ''; ?>>Sri Lanka</option>
                        <option value="India" <?php echo $countrySel === 'India' ? 'selected' : ''; ?>>India</option>
                        <option value="USA" <?php echo $countrySel === 'USA' ? 'selected' : ''; ?>>USA</option>
                        <option value="United Kingdom" <?php echo $countrySel === 'United Kingdom' ? 'selected' : ''; ?>>United Kingdom</option>
                        <option value="Germany" <?php echo $countrySel === 'Germany' ? 'selected' : ''; ?>>Germany</option>
                        <option value="France" <?php echo $countrySel === 'France' ? 'selected' : ''; ?>>France</option>
                        <option value="Australia" <?php echo $countrySel === 'Australia' ? 'selected' : ''; ?>>Australia</option>
                        <option value="Canada" <?php echo $countrySel === 'Canada' ? 'selected' : ''; ?>>Canada</option>
                        <option value="Other" <?php echo $countrySel === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="order_date" class="form-label">Order Date</label>
                    <input type="date" name="order_date" id="order_date" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['order_date'] ?? $order['order_date']); ?>">
                </div>
                <div class="col-md-4">
                    <label for="required_date" class="form-label">Required Date</label>
                    <input type="date" name="required_date" id="required_date" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['required_date'] ?? ($order['required_date'] ?? '')); ?>">
                </div>

                <div class="col-md-4">
                    <label for="status" class="form-label">Order Status</label>
                    <?php $statusSel = $_POST['status'] ?? $order['status']; ?>
                    <select name="status" id="status" class="form-select">
                        <option value="pending" <?php echo $statusSel === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $statusSel === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="quality_check" <?php echo $statusSel === 'quality_check' ? 'selected' : ''; ?>>Quality Check</option>
                        <option value="packaging" <?php echo $statusSel === 'packaging' ? 'selected' : ''; ?>>Packaging</option>
                        <option value="ready_to_ship" <?php echo $statusSel === 'ready_to_ship' ? 'selected' : ''; ?>>Ready to Ship</option>
                        <option value="shipped" <?php echo $statusSel === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $statusSel === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $statusSel === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="priority" class="form-label">Priority</label>
                    <?php $prioritySel = $_POST['priority'] ?? $order['priority']; ?>
                    <select name="priority" id="priority" class="form-select">
                        <option value="low" <?php echo $prioritySel === 'low' ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo $prioritySel === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo $prioritySel === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="urgent" <?php echo $prioritySel === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="currency" class="form-label">Currency</label>
                    <input type="text" name="currency" id="currency" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['currency'] ?? $order['currency']); ?>">
                </div>
                <div class="col-md-4">
                    <label for="total_amount" class="form-label">Total Amount</label>
                    <input type="number" step="0.01" min="0" name="total_amount" id="total_amount" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['total_amount'] ?? $order['total_amount']); ?>">
                </div>

                <div class="col-md-4">
                    <label for="payment_status" class="form-label">Payment Status</label>
                    <?php $paySel = $_POST['payment_status'] ?? $order['payment_status']; ?>
                    <select name="payment_status" id="payment_status" class="form-select">
                        <option value="pending" <?php echo $paySel === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="partial" <?php echo $paySel === 'partial' ? 'selected' : ''; ?>>Partially Paid</option>
                        <option value="paid" <?php echo $paySel === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="refunded" <?php echo $paySel === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                    </select>
                </div>

                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Order
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Auto-suggest currency based on selected export country
    document.addEventListener('DOMContentLoaded', function () {
        var countrySelect = document.getElementById('export_country');
        var currencyInput = document.getElementById('currency');

        if (!countrySelect || !currencyInput) return;

        var countryToCurrency = {
            'Sri Lanka': 'LKR',
            'India': 'INR',
            'USA': 'USD',
            'United Kingdom': 'GBP',
            'Germany': 'EUR',
            'France': 'EUR',
            'Australia': 'AUD',
            'Canada': 'CAD'
        };

        countrySelect.addEventListener('change', function () {
            var selected = countrySelect.value;
            if (countryToCurrency[selected]) {
                currencyInput.value = countryToCurrency[selected];
            }
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>
