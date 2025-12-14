<?php
/**
 * KYA Food Production - View Processing Log Details
 */

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

SessionManager::start();
SessionManager::requireLogin();

if (!SessionManager::hasPermission('processing_view') && !SessionManager::hasPermission('processing_manage')) {
    header('Location: ../../dashboard.php?error=access_denied');
    exit();
}

$userInfo = SessionManager::getUserInfo();
$db = new Database();
$conn = $db->connect();

$log_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($log_id <= 0) {
    header('Location: logs.php?error=invalid_id');
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT 
            pl.*,
            i.item_name,
            i.item_code,
            i.category,
            u1.full_name as operator_name,
            u1.username as operator_username,
            u2.full_name as supervisor_name,
            u2.username as supervisor_username
        FROM processing_logs pl
        LEFT JOIN inventory i ON pl.item_id = i.id
        LEFT JOIN users u1 ON pl.operator_id = u1.id
        LEFT JOIN users u2 ON pl.supervisor_id = u2.id
        WHERE pl.id = ?
    ");
    $stmt->execute([$log_id]);
    $log = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$log) {
        header('Location: logs.php?error=not_found');
        exit();
    }

    if ($userInfo['role'] !== 'admin' && $log['section'] != $userInfo['section']) {
        header('Location: logs.php?error=access_denied');
        exit();
    }

} catch (Exception $e) {
    error_log('View log error: ' . $e->getMessage());
    header('Location: logs.php?error=database_error');
    exit();
}

$pageTitle = 'View Processing Log - ' . $log['batch_id'];
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0">
            <i class="fas fa-clipboard-list me-2"></i>Processing Log Details
        </h2>
        <div>
            <?php if (SessionManager::hasPermission('processing_manage')): ?>
                <a href="edit_log.php?id=<?php echo $log['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i>Edit Log
                </a>
            <?php endif; ?>
            <a href="logs.php?section=<?php echo $log['section']; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Logs
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Basic Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Batch ID</label>
                            <h5><?php echo htmlspecialchars($log['batch_id']); ?></h5>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Section</label>
                            <h5>
                                <span class="badge bg-primary">Section <?php echo $log['section']; ?></span>
                            </h5>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Process Type</label>
                            <h5>
                                <span class="badge bg-info"><?php echo htmlspecialchars($log['process_type']); ?></span>
                            </h5>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Status</label>
                            <h5>
                                <?php if (!$log['end_time'] && $log['start_time']): ?>
                                    <span class="badge bg-warning">Active</span>
                                <?php elseif ($log['end_time']): ?>
                                    <span class="badge bg-success">Completed</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Pending</span>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <?php if ($log['item_name']): ?>
                            <div class="col-12 mb-3">
                                <label class="text-muted small">Item</label>
                                <h5>
                                    <?php echo htmlspecialchars($log['item_name']); ?>
                                    <small class="text-muted">(<?php echo htmlspecialchars($log['item_code']); ?>)</small>
                                </h5>
                                <?php if ($log['category']): ?>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($log['category']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-balance-scale me-2"></i>Quantity & Yield
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Input Quantity</label>
                            <h4 class="text-success"><?php echo number_format($log['input_quantity'], 3); ?> kg</h4>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Output Quantity</label>
                            <h4 class="text-primary">
                                <?php echo $log['output_quantity'] ? number_format($log['output_quantity'], 3) . ' kg' : '-'; ?>
                            </h4>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Yield Percentage</label>
                            <h4>
                                <?php if ($log['yield_percentage']): ?>
                                    <span class="badge bg-<?php echo $log['yield_percentage'] >= 90 ? 'success' : ($log['yield_percentage'] >= 75 ? 'warning' : 'danger'); ?>">
                                        <?php echo number_format($log['yield_percentage'], 2); ?>%
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </h4>
                        </div>
                        <?php if ($log['output_quantity'] && $log['input_quantity'] > $log['output_quantity']): ?>
                            <div class="col-md-4 mb-3">
                                <label class="text-muted small">Waste/Loss</label>
                                <h5 class="text-danger">
                                    <?php echo number_format($log['input_quantity'] - $log['output_quantity'], 3); ?> kg
                                </h5>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>Time Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Start Time</label>
                            <h5><?php echo $log['start_time'] ? formatDateTime($log['start_time']) : '-'; ?></h5>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">End Time</label>
                            <h5><?php echo $log['end_time'] ? formatDateTime($log['end_time']) : '-'; ?></h5>
                        </div>
                        <?php if ($log['duration_minutes']): ?>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Duration</label>
                                <h5>
                                    <?php 
                                    $hours = floor($log['duration_minutes'] / 60);
                                    $minutes = $log['duration_minutes'] % 60;
                                    echo $hours > 0 ? "{$hours}h " : "";
                                    echo "{$minutes}m";
                                    ?>
                                </h5>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($log['notes']): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-sticky-note me-2"></i>Notes
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($log['notes'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Personnel
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Operator</label>
                        <h5>
                            <?php if ($log['operator_name']): ?>
                                <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($log['operator_name']); ?>
                                <br><small class="text-muted">@<?php echo htmlspecialchars($log['operator_username']); ?></small>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Supervisor</label>
                        <h5>
                            <?php if ($log['supervisor_name']): ?>
                                <i class="fas fa-user-tie me-2"></i><?php echo htmlspecialchars($log['supervisor_name']); ?>
                                <br><small class="text-muted">@<?php echo htmlspecialchars($log['supervisor_username']); ?></small>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </h5>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-thermometer-half me-2"></i>Environmental Conditions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Temperature</label>
                        <h5>
                            <?php echo $log['temperature'] ? number_format($log['temperature'], 1) . '°C' : '-'; ?>
                        </h5>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Humidity</label>
                        <h5>
                            <?php echo $log['humidity'] ? number_format($log['humidity'], 1) . '% RH' : '-'; ?>
                        </h5>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i>Quality Check
                    </h5>
                </div>
                <div class="card-body">
                    <h4>
                        <?php if ($log['quality_check']): ?>
                            <?php 
                            $badgeClass = $log['quality_check'] === 'pass' ? 'success' : ($log['quality_check'] === 'fail' ? 'danger' : 'warning');
                            ?>
                            <span class="badge bg-<?php echo $badgeClass; ?> fs-6">
                                <?php echo ucfirst($log['quality_check']); ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">Not checked</span>
                        <?php endif; ?>
                    </h4>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>Record Info
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">Created:</small><br>
                        <strong><?php echo formatDateTime($log['created_at']); ?></strong>
                    </div>
                    <div>
                        <small class="text-muted">Last Updated:</small><br>
                        <strong><?php echo formatDateTime($log['updated_at']); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
