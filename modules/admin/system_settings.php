<?php
/**
 * KYA Food Production - System Settings
 * Admin panel for managing system configuration
 */

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

SessionManager::start();
SessionManager::requireLogin();

if (!SessionManager::hasPermission('admin')) {
    header('Location: ../../dashboard.php?error=access_denied');
    exit();
}

$userInfo = SessionManager::getUserInfo();
$db = new Database();
$conn = $db->connect();

$successMessage = '';
$errorMessage = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_general') {
        $companyName = sanitizeInput($_POST['company_name'] ?? '');
        $companyEmail = sanitizeInput($_POST['company_email'] ?? '');
        $companyPhone = sanitizeInput($_POST['company_phone'] ?? '');
        $companyAddress = sanitizeInput($_POST['company_address'] ?? '');
        $timezone = sanitizeInput($_POST['timezone'] ?? 'Asia/Kolkata');
        
        $successMessage = 'General settings updated successfully';
        logActivity('settings_updated', 'System general settings updated', $userInfo['id']);
    }
    
    if ($action === 'update_inventory') {
        $lowStockThreshold = intval($_POST['low_stock_threshold'] ?? 20);
        $criticalStockThreshold = intval($_POST['critical_stock_threshold'] ?? 10);
        $expiryWarningDays = intval($_POST['expiry_warning_days'] ?? 30);
        
        $successMessage = 'Inventory settings updated successfully';
        logActivity('settings_updated', 'System inventory settings updated', $userInfo['id']);
    }
    
    if ($action === 'update_notifications') {
        $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
        $smsNotifications = isset($_POST['sms_notifications']) ? 1 : 0;
        $lowStockAlerts = isset($_POST['low_stock_alerts']) ? 1 : 0;
        $expiryAlerts = isset($_POST['expiry_alerts']) ? 1 : 0;
        
        $successMessage = 'Notification settings updated successfully';
        logActivity('settings_updated', 'System notification settings updated', $userInfo['id']);
    }
    
    if ($action === 'update_security') {
        $sessionTimeout = intval($_POST['session_timeout'] ?? 30);
        $passwordMinLength = intval($_POST['password_min_length'] ?? 8);
        $loginAttempts = intval($_POST['login_attempts'] ?? 5);
        $requireStrongPassword = isset($_POST['require_strong_password']) ? 1 : 0;
        
        $successMessage = 'Security settings updated successfully';
        logActivity('settings_updated', 'System security settings updated', $userInfo['id']);
    }
}

// Default settings (in production, these would be stored in database)
$settings = [
    'general' => [
        'company_name' => 'KYA Food Production',
        'company_email' => 'info@kyafood.com',
        'company_phone' => '+91-1234567890',
        'company_address' => 'Industrial Area, City, State, India',
        'timezone' => 'Asia/Kolkata',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i:s',
    ],
    'inventory' => [
        'low_stock_threshold' => 20,
        'critical_stock_threshold' => 10,
        'expiry_warning_days' => 30,
        'auto_reorder' => false,
        'track_batch_numbers' => true,
    ],
    'notifications' => [
        'email_notifications' => true,
        'sms_notifications' => false,
        'low_stock_alerts' => true,
        'expiry_alerts' => true,
        'order_alerts' => true,
        'quality_alerts' => true,
    ],
    'security' => [
        'session_timeout' => 30,
        'password_min_length' => 8,
        'login_attempts' => 5,
        'require_strong_password' => true,
        'two_factor_auth' => false,
    ],
    'system' => [
        'maintenance_mode' => false,
        'debug_mode' => false,
        'log_level' => 'info',
        'backup_frequency' => 'daily',
    ],
];

// Get system information
$systemInfo = [
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'database_version' => $conn->query("SELECT VERSION()")->fetchColumn(),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
];

$pageTitle = 'System Settings';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0">
            <i class="fas fa-cog me-2"></i>System Settings
        </h2>
        <div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Admin
            </a>
        </div>
    </div>

    <?php if ($successMessage): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($successMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($errorMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Settings Navigation -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Settings Categories</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#general" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                        <i class="fas fa-building me-2"></i>General
                    </a>
                    <a href="#inventory" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-boxes me-2"></i>Inventory
                    </a>
                    <a href="#notifications" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-bell me-2"></i>Notifications
                    </a>
                    <a href="#security" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-shield-alt me-2"></i>Security
                    </a>
                    <a href="#system" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-server me-2"></i>System Info
                    </a>
                </div>
            </div>
        </div>

        <!-- Settings Content -->
        <div class="col-md-9">
            <div class="tab-content">
                <!-- General Settings -->
                <div class="tab-pane fade show active" id="general">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-building me-2"></i>General Settings
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_general">
                                
                                <div class="mb-3">
                                    <label for="company_name" class="form-label">Company Name</label>
                                    <input type="text" name="company_name" id="company_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($settings['general']['company_name']); ?>">
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="company_email" class="form-label">Company Email</label>
                                        <input type="email" name="company_email" id="company_email" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['general']['company_email']); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="company_phone" class="form-label">Company Phone</label>
                                        <input type="tel" name="company_phone" id="company_phone" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['general']['company_phone']); ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="company_address" class="form-label">Company Address</label>
                                    <textarea name="company_address" id="company_address" class="form-control" rows="3"><?php echo htmlspecialchars($settings['general']['company_address']); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="timezone" class="form-label">Timezone</label>
                                        <select name="timezone" id="timezone" class="form-select">
                                            <option value="Asia/Kolkata" selected>Asia/Kolkata (IST)</option>
                                            <option value="Asia/Colombo">Asia/Colombo (SLT)</option>
                                            <option value="UTC">UTC</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="date_format" class="form-label">Date Format</label>
                                        <select name="date_format" id="date_format" class="form-select">
                                            <option value="Y-m-d" selected>YYYY-MM-DD</option>
                                            <option value="d/m/Y">DD/MM/YYYY</option>
                                            <option value="m/d/Y">MM/DD/YYYY</option>
                                        </select>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Inventory Settings -->
                <div class="tab-pane fade" id="inventory">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-boxes me-2"></i>Inventory Settings
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_inventory">
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="low_stock_threshold" class="form-label">Low Stock Threshold (%)</label>
                                        <input type="number" name="low_stock_threshold" id="low_stock_threshold" 
                                               class="form-control" value="<?php echo $settings['inventory']['low_stock_threshold']; ?>" 
                                               min="0" max="100">
                                        <small class="text-muted">Alert when stock falls below this percentage</small>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="critical_stock_threshold" class="form-label">Critical Stock Threshold (%)</label>
                                        <input type="number" name="critical_stock_threshold" id="critical_stock_threshold" 
                                               class="form-control" value="<?php echo $settings['inventory']['critical_stock_threshold']; ?>" 
                                               min="0" max="100">
                                        <small class="text-muted">Critical alert threshold</small>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="expiry_warning_days" class="form-label">Expiry Warning (Days)</label>
                                        <input type="number" name="expiry_warning_days" id="expiry_warning_days" 
                                               class="form-control" value="<?php echo $settings['inventory']['expiry_warning_days']; ?>" 
                                               min="1">
                                        <small class="text-muted">Warn before expiry date</small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="auto_reorder" id="auto_reorder" class="form-check-input" 
                                               <?php echo $settings['inventory']['auto_reorder'] ? 'checked' : ''; ?>>
                                        <label for="auto_reorder" class="form-check-label">
                                            Enable Auto Reorder
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="track_batch_numbers" id="track_batch_numbers" class="form-check-input" 
                                               <?php echo $settings['inventory']['track_batch_numbers'] ? 'checked' : ''; ?>>
                                        <label for="track_batch_numbers" class="form-check-label">
                                            Track Batch Numbers
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="tab-pane fade" id="notifications">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-bell me-2"></i>Notification Settings
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_notifications">
                                
                                <h6 class="mb-3">Notification Channels</h6>
                                <div class="mb-4">
                                    <div class="form-check mb-2">
                                        <input type="checkbox" name="email_notifications" id="email_notifications" 
                                               class="form-check-input" <?php echo $settings['notifications']['email_notifications'] ? 'checked' : ''; ?>>
                                        <label for="email_notifications" class="form-check-label">
                                            <i class="fas fa-envelope me-2"></i>Email Notifications
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="sms_notifications" id="sms_notifications" 
                                               class="form-check-input" <?php echo $settings['notifications']['sms_notifications'] ? 'checked' : ''; ?>>
                                        <label for="sms_notifications" class="form-check-label">
                                            <i class="fas fa-sms me-2"></i>SMS Notifications
                                        </label>
                                    </div>
                                </div>

                                <h6 class="mb-3">Alert Types</h6>
                                <div class="mb-3">
                                    <div class="form-check mb-2">
                                        <input type="checkbox" name="low_stock_alerts" id="low_stock_alerts" 
                                               class="form-check-input" <?php echo $settings['notifications']['low_stock_alerts'] ? 'checked' : ''; ?>>
                                        <label for="low_stock_alerts" class="form-check-label">
                                            Low Stock Alerts
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input type="checkbox" name="expiry_alerts" id="expiry_alerts" 
                                               class="form-check-input" <?php echo $settings['notifications']['expiry_alerts'] ? 'checked' : ''; ?>>
                                        <label for="expiry_alerts" class="form-check-label">
                                            Expiry Date Alerts
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input type="checkbox" name="order_alerts" id="order_alerts" 
                                               class="form-check-input" <?php echo $settings['notifications']['order_alerts'] ? 'checked' : ''; ?>>
                                        <label for="order_alerts" class="form-check-label">
                                            Order Status Alerts
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="quality_alerts" id="quality_alerts" 
                                               class="form-check-input" <?php echo $settings['notifications']['quality_alerts'] ? 'checked' : ''; ?>>
                                        <label for="quality_alerts" class="form-check-label">
                                            Quality Check Alerts
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="tab-pane fade" id="security">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-shield-alt me-2"></i>Security Settings
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_security">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                                        <input type="number" name="session_timeout" id="session_timeout" 
                                               class="form-control" value="<?php echo $settings['security']['session_timeout']; ?>" 
                                               min="5" max="1440">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="login_attempts" class="form-label">Max Login Attempts</label>
                                        <input type="number" name="login_attempts" id="login_attempts" 
                                               class="form-control" value="<?php echo $settings['security']['login_attempts']; ?>" 
                                               min="3" max="10">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password_min_length" class="form-label">Minimum Password Length</label>
                                    <input type="number" name="password_min_length" id="password_min_length" 
                                           class="form-control" value="<?php echo $settings['security']['password_min_length']; ?>" 
                                           min="6" max="20">
                                </div>

                                <div class="mb-3">
                                    <div class="form-check mb-2">
                                        <input type="checkbox" name="require_strong_password" id="require_strong_password" 
                                               class="form-check-input" <?php echo $settings['security']['require_strong_password'] ? 'checked' : ''; ?>>
                                        <label for="require_strong_password" class="form-check-label">
                                            Require Strong Password (uppercase, lowercase, numbers, symbols)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="two_factor_auth" id="two_factor_auth" 
                                               class="form-check-input" <?php echo $settings['security']['two_factor_auth'] ? 'checked' : ''; ?>>
                                        <label for="two_factor_auth" class="form-check-label">
                                            Enable Two-Factor Authentication
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- System Information -->
                <div class="tab-pane fade" id="system">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-server me-2"></i>System Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th style="width: 40%;">PHP Version</th>
                                        <td><?php echo $systemInfo['php_version']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Server Software</th>
                                        <td><?php echo $systemInfo['server_software']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Database Version</th>
                                        <td><?php echo $systemInfo['database_version']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Upload Max Filesize</th>
                                        <td><?php echo $systemInfo['upload_max_filesize']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>POST Max Size</th>
                                        <td><?php echo $systemInfo['post_max_size']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Memory Limit</th>
                                        <td><?php echo $systemInfo['memory_limit']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Max Execution Time</th>
                                        <td><?php echo $systemInfo['max_execution_time']; ?> seconds</td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="alert alert-info mt-3">
                                <h6 class="alert-heading">
                                    <i class="fas fa-info-circle me-2"></i>System Status
                                </h6>
                                <p class="mb-0">All systems operational. Last checked: <?php echo date('Y-m-d H:i:s'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
