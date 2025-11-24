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
    $exportCountry  = trim($_POST['export_country'] ?? '');
    $orderDate      = $_POST['order_date'] ?? date('Y-m-d');
    $requiredDate   = $_POST['required_date'] ?? null;
    $priority       = $_POST['priority'] ?? 'medium';
    $currency       = $_POST['currency'] ?? 'USD';
    $totalAmount    = (float)($_POST['total_amount'] ?? 0);
    // DB enum: pending, partial, paid, refunded
    $paymentStatus  = $_POST['payment_status'] ?? 'pending';

    if ($customerName === '') {
        $errorMessage = 'Customer name is required.';
    } else {
        try {
            $orderNumber = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);

            $stmt = $conn->prepare("INSERT INTO orders (
                    order_number,
                    customer_name,
                    customer_email,
                    export_country,
                    order_date,
                    required_date,
                    status,
                    priority,
                    total_amount,
                    currency,
                    payment_status,
                    created_by,
                    created_at,
                    updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, NOW(), NOW()
                )");

            $stmt->execute([
                $orderNumber,
                $customerName,
                $customerEmail ?: null,
                $exportCountry ?: null,
                $orderDate,
                $requiredDate ?: null,
                $priority,
                $totalAmount,
                $currency,
                $paymentStatus,
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

                <div class="col-md-4">
                    <label for="export_country" class="form-label">Export Country</label>
                    <select name="export_country" id="export_country" class="form-select">
                        <?php $countrySel = $_POST['export_country'] ?? ''; ?>
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
                           value="<?php echo htmlspecialchars($_POST['order_date'] ?? date('Y-m-d')); ?>">
                </div>
                <div class="col-md-4">
                    <label for="required_date" class="form-label">Required Date</label>
                    <input type="date" name="required_date" id="required_date" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['required_date'] ?? ''); ?>">
                </div>

                <div class="col-md-4">
                    <label for="priority" class="form-label">Priority</label>
                    <select name="priority" id="priority" class="form-select">
                        <?php $prioritySel = $_POST['priority'] ?? 'medium'; ?>
                        <option value="low" <?php echo $prioritySel === 'low' ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo $prioritySel === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo $prioritySel === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="urgent" <?php echo $prioritySel === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="currency" class="form-label">Currency</label>
                    <input type="text" name="currency" id="currency" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['currency'] ?? 'USD'); ?>">
                </div>
                <div class="col-md-4">
                    <label for="total_amount" class="form-label">Estimated Total Amount</label>
                    <input type="number" step="0.01" min="0" name="total_amount" id="total_amount" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['total_amount'] ?? '0'); ?>">
                </div>

                <div class="col-md-4">
                    <label for="payment_status" class="form-label">Payment Status</label>
                    <?php $paySel = $_POST['payment_status'] ?? 'pending'; ?>
                    <select name="payment_status" id="payment_status" class="form-select">
                        <option value="pending" <?php echo $paySel === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="partial" <?php echo $paySel === 'partial' ? 'selected' : ''; ?>>Partially Paid</option>
                        <option value="paid" <?php echo $paySel === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="refunded" <?php echo $paySel === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                    </select>
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
            } else if (selected === '') {
                currencyInput.value = 'USD';
            }
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>
