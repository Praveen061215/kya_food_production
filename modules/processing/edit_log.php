<?php
/**
 * KYA Food Production - Edit Processing Log
 */

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

SessionManager::start();
SessionManager::requireLogin();

if (!SessionManager::hasPermission('processing_manage')) {
    header('Location: ../../dashboard.php?error=access_denied');
    exit();
}

$userInfo = SessionManager::getUserInfo();
$db = new Database();
$conn = $db->connect();

$log_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$errors = [];
$success = false;

if ($log_id <= 0) {
    header('Location: logs.php?error=invalid_id');
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT pl.* 
        FROM processing_logs pl
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
    error_log('Edit log error: ' . $e->getMessage());
    header('Location: logs.php?error=database_error');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = sanitizeInput($_POST['section'] ?? '');
    $batch_id = sanitizeInput($_POST['batch_id'] ?? '');
    $process_type = sanitizeInput($_POST['process_type'] ?? '');
    $item_id = !empty($_POST['item_id']) ? intval($_POST['item_id']) : null;
    $input_quantity = floatval($_POST['input_quantity'] ?? 0);
    $output_quantity = !empty($_POST['output_quantity']) ? floatval($_POST['output_quantity']) : null;
    $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
    $operator_id = !empty($_POST['operator_id']) ? intval($_POST['operator_id']) : null;
    $supervisor_id = !empty($_POST['supervisor_id']) ? intval($_POST['supervisor_id']) : null;
    $temperature = !empty($_POST['temperature']) ? floatval($_POST['temperature']) : null;
    $humidity = !empty($_POST['humidity']) ? floatval($_POST['humidity']) : null;
    $quality_check = sanitizeInput($_POST['quality_check'] ?? 'pending');
    $notes = sanitizeInput($_POST['notes'] ?? '');

    // Validation
    if (empty($section)) {
        $errors[] = 'Section is required';
    }

    if (empty($batch_id)) {
        $errors[] = 'Batch ID is required';
    }

    if (empty($process_type)) {
        $errors[] = 'Process type is required';
    }

    if ($input_quantity <= 0) {
        $errors[] = 'Input quantity must be greater than 0';
    }

    // Calculate yield percentage and duration
    $yield_percentage = null;
    $duration_minutes = null;

    if ($output_quantity !== null && $input_quantity > 0) {
        $yield_percentage = ($output_quantity / $input_quantity) * 100;
    }

    if ($end_time && $start_time) {
        $start = new DateTime($start_time);
        $end = new DateTime($end_time);
        $duration_minutes = ($end->getTimestamp() - $start->getTimestamp()) / 60;
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                UPDATE processing_logs SET
                    section = ?,
                    batch_id = ?,
                    process_type = ?,
                    item_id = ?,
                    input_quantity = ?,
                    output_quantity = ?,
                    yield_percentage = ?,
                    start_time = ?,
                    end_time = ?,
                    duration_minutes = ?,
                    operator_id = ?,
                    supervisor_id = ?,
                    temperature = ?,
                    humidity = ?,
                    quality_check = ?,
                    notes = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $result = $stmt->execute([
                $section, $batch_id, $process_type, $item_id, $input_quantity,
                $output_quantity, $yield_percentage, $start_time, $end_time,
                $duration_minutes, $operator_id, $supervisor_id, $temperature,
                $humidity, $quality_check, $notes, $log_id
            ]);

            if ($result) {
                logActivity('processing_log_updated', "Processing log updated: Batch {$batch_id}", $userInfo['id']);
                header("Location: view_log.php?id={$log_id}&success=updated");
                exit();
            } else {
                $errors[] = 'Failed to update processing log';
            }

        } catch (Exception $e) {
            error_log('Update processing log error: ' . $e->getMessage());
            $errors[] = 'Database error occurred: ' . $e->getMessage();
        }
    }
} else {
    // Pre-fill form with existing data
    $_POST = $log;
    if ($log['start_time']) {
        $_POST['start_time'] = date('Y-m-d\TH:i', strtotime($log['start_time']));
    }
    if ($log['end_time']) {
        $_POST['end_time'] = date('Y-m-d\TH:i', strtotime($log['end_time']));
    }
}

// Get inventory items for dropdown
try {
    $inventoryStmt = $conn->query("SELECT id, item_code, item_name FROM inventory WHERE status = 'active' ORDER BY item_name");
    $inventoryItems = $inventoryStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $inventoryItems = [];
}

// Get users for operator/supervisor dropdown
try {
    $usersStmt = $conn->query("SELECT id, full_name, role FROM users WHERE is_active = 1 ORDER BY full_name");
    $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $users = [];
}

$pageTitle = 'Edit Processing Log - ' . $log['batch_id'];
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0">
            <i class="fas fa-edit me-2"></i>Edit Processing Log
        </h2>
        <div>
            <a href="view_log.php?id=<?php echo $log_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Details
            </a>
        </div>
    </div>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Error:</strong> Please fix the following issues:
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Edit Form -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-clipboard-list me-2"></i>Processing Log Details
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" id="editLogForm">
                <div class="row">
                    <!-- Basic Information -->
                    <div class="col-12 mt-3">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-info-circle me-2"></i>Basic Information
                        </h6>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="section" class="form-label">Section <span class="text-danger">*</span></label>
                        <select name="section" id="section" class="form-select" required>
                            <option value="">Select Section</option>
                            <option value="1" <?php echo ($_POST['section'] == '1') ? 'selected' : ''; ?>>Section 1 - Raw Materials</option>
                            <option value="2" <?php echo ($_POST['section'] == '2') ? 'selected' : ''; ?>>Section 2 - Processing</option>
                            <option value="3" <?php echo ($_POST['section'] == '3') ? 'selected' : ''; ?>>Section 3 - Packaging</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="batch_id" class="form-label">Batch ID <span class="text-danger">*</span></label>
                        <input type="text" name="batch_id" id="batch_id" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['batch_id']); ?>" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="process_type" class="form-label">Process Type <span class="text-danger">*</span></label>
                        <select name="process_type" id="process_type" class="form-select" required>
                            <option value="">Select Process Type</option>
                            <?php 
                            $processTypes = ['Washing', 'Cutting', 'Drying', 'Grinding', 'Mixing', 'Cooking', 'Cooling', 'Packaging', 'Quality Check', 'Other'];
                            foreach ($processTypes as $type): 
                            ?>
                                <option value="<?php echo $type; ?>" <?php echo ($_POST['process_type'] == $type) ? 'selected' : ''; ?>>
                                    <?php echo $type; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="item_id" class="form-label">Item (Optional)</label>
                        <select name="item_id" id="item_id" class="form-select">
                            <option value="">Select Item</option>
                            <?php foreach ($inventoryItems as $item): ?>
                                <option value="<?php echo $item['id']; ?>" 
                                        <?php echo ($_POST['item_id'] == $item['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($item['item_code'] . ' - ' . $item['item_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Quantity Information -->
                    <div class="col-12 mt-3">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-balance-scale me-2"></i>Quantity Information
                        </h6>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="input_quantity" class="form-label">Input Quantity (kg) <span class="text-danger">*</span></label>
                        <input type="number" name="input_quantity" id="input_quantity" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['input_quantity']); ?>" 
                               step="0.001" min="0" required onchange="calculateYield()">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="output_quantity" class="form-label">Output Quantity (kg)</label>
                        <input type="number" name="output_quantity" id="output_quantity" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['output_quantity'] ?? ''); ?>" 
                               step="0.001" min="0" onchange="calculateYield()">
                        <div class="form-text" id="yield_display"></div>
                    </div>

                    <!-- Time Information -->
                    <div class="col-12 mt-3">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-clock me-2"></i>Time Information
                        </h6>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="start_time" class="form-label">Start Time</label>
                        <input type="datetime-local" name="start_time" id="start_time" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['start_time'] ?? ''); ?>"
                               onchange="calculateDuration()">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="end_time" class="form-label">End Time</label>
                        <input type="datetime-local" name="end_time" id="end_time" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['end_time'] ?? ''); ?>"
                               onchange="calculateDuration()">
                        <div class="form-text" id="duration_display"></div>
                    </div>

                    <!-- Personnel -->
                    <div class="col-12 mt-3">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-users me-2"></i>Personnel
                        </h6>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="operator_id" class="form-label">Operator</label>
                        <select name="operator_id" id="operator_id" class="form-select">
                            <option value="">Select Operator</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" 
                                        <?php echo ($_POST['operator_id'] == $user['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['full_name'] . ' (' . $user['role'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="supervisor_id" class="form-label">Supervisor</label>
                        <select name="supervisor_id" id="supervisor_id" class="form-select">
                            <option value="">Select Supervisor</option>
                            <?php foreach ($users as $user): ?>
                                <?php if (in_array($user['role'], ['admin', 'section1_manager', 'section2_manager', 'section3_manager'])): ?>
                                    <option value="<?php echo $user['id']; ?>" 
                                            <?php echo ($_POST['supervisor_id'] == $user['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['full_name'] . ' (' . $user['role'] . ')'); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Environmental Conditions -->
                    <div class="col-12 mt-3">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-thermometer-half me-2"></i>Environmental Conditions
                        </h6>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="temperature" class="form-label">Temperature (°C)</label>
                        <input type="number" name="temperature" id="temperature" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['temperature'] ?? ''); ?>" 
                               step="0.1">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="humidity" class="form-label">Humidity (%)</label>
                        <input type="number" name="humidity" id="humidity" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['humidity'] ?? ''); ?>" 
                               step="0.1" min="0" max="100">
                    </div>

                    <!-- Quality & Notes -->
                    <div class="col-12 mt-3">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-check-circle me-2"></i>Quality & Notes
                        </h6>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="quality_check" class="form-label">Quality Check</label>
                        <select name="quality_check" id="quality_check" class="form-select">
                            <option value="pending" <?php echo ($_POST['quality_check'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="pass" <?php echo ($_POST['quality_check'] == 'pass') ? 'selected' : ''; ?>>Pass</option>
                            <option value="fail" <?php echo ($_POST['quality_check'] == 'fail') ? 'selected' : ''; ?>>Fail</option>
                        </select>
                    </div>

                    <div class="col-12 mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="4"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="row mt-4">
                    <div class="col-12">
                        <hr>
                        <div class="d-flex justify-content-between">
                            <a href="view_log.php?id=<?php echo $log_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Processing Log
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Calculate Yield Percentage
function calculateYield() {
    const input = parseFloat(document.getElementById('input_quantity').value) || 0;
    const output = parseFloat(document.getElementById('output_quantity').value) || 0;
    const yieldDisplay = document.getElementById('yield_display');
    
    if (input > 0 && output > 0) {
        const yieldPercentage = ((output / input) * 100).toFixed(2);
        yieldDisplay.innerHTML = `<strong>Yield: ${yieldPercentage}%</strong>`;
        
        if (yieldPercentage < 70) {
            yieldDisplay.className = 'form-text text-danger';
        } else if (yieldPercentage < 85) {
            yieldDisplay.className = 'form-text text-warning';
        } else {
            yieldDisplay.className = 'form-text text-success';
        }
    } else {
        yieldDisplay.innerHTML = '';
    }
}

// Calculate Duration
function calculateDuration() {
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    const durationDisplay = document.getElementById('duration_display');
    
    if (startTime && endTime) {
        const start = new Date(startTime);
        const end = new Date(endTime);
        const diffMs = end - start;
        
        if (diffMs > 0) {
            const diffMins = Math.floor(diffMs / 60000);
            const hours = Math.floor(diffMins / 60);
            const minutes = diffMins % 60;
            
            durationDisplay.innerHTML = `<strong>Duration: ${hours}h ${minutes}m</strong>`;
            durationDisplay.className = 'form-text text-info';
        } else {
            durationDisplay.innerHTML = '<span class="text-danger">End time must be after start time</span>';
        }
    } else {
        durationDisplay.innerHTML = '';
    }
}

// Auto-calculate on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateYield();
    calculateDuration();
});
</script>

<?php include '../../includes/footer.php'; ?>
