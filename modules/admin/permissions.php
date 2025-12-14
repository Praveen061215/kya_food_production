<?php
/**
 * KYA Food Production - Permissions Management
 * Admin panel for managing user roles and permissions
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

// Define available permissions
$availablePermissions = [
    'admin' => 'Full System Access',
    'user_manage' => 'Manage Users',
    'inventory_view' => 'View Inventory',
    'inventory_manage' => 'Manage Inventory',
    'orders_view' => 'View Orders',
    'orders_manage' => 'Manage Orders',
    'receiving_view' => 'View Receiving Records',
    'receiving_manage' => 'Manage Receiving',
    'processing_view' => 'View Processing Logs',
    'processing_manage' => 'Manage Processing',
    'reports_view' => 'View Reports',
    'reports_generate' => 'Generate Reports',
    'section1_access' => 'Access Section 1',
    'section2_access' => 'Access Section 2',
    'section3_access' => 'Access Section 3',
];

// Define role-based default permissions
$rolePermissions = [
    'admin' => [
        'admin', 'user_manage', 'inventory_view', 'inventory_manage', 
        'orders_view', 'orders_manage', 'receiving_view', 'receiving_manage',
        'processing_view', 'processing_manage', 'reports_view', 'reports_generate',
        'section1_access', 'section2_access', 'section3_access'
    ],
    'section1_manager' => [
        'inventory_view', 'inventory_manage', 'receiving_view', 'receiving_manage',
        'reports_view', 'section1_access'
    ],
    'section1_operator' => [
        'inventory_view', 'receiving_view', 'section1_access'
    ],
    'section2_manager' => [
        'inventory_view', 'processing_view', 'processing_manage',
        'reports_view', 'section2_access'
    ],
    'section2_operator' => [
        'inventory_view', 'processing_view', 'section2_access'
    ],
    'section3_manager' => [
        'inventory_view', 'orders_view', 'orders_manage', 'processing_view',
        'reports_view', 'section3_access'
    ],
    'section3_operator' => [
        'inventory_view', 'orders_view', 'section3_access'
    ],
];

// Get all users with their roles
try {
    $stmt = $conn->query("
        SELECT 
            id, username, full_name, email, role, section, is_active,
            created_at, last_login
        FROM users
        ORDER BY role, username
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Fetch users error: ' . $e->getMessage());
    $users = [];
}

// Get role statistics
$roleStats = [];
foreach ($users as $user) {
    $role = $user['role'];
    if (!isset($roleStats[$role])) {
        $roleStats[$role] = 0;
    }
    $roleStats[$role]++;
}

$pageTitle = 'Permissions Management';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0">
            <i class="fas fa-shield-alt me-2"></i>Permissions Management
        </h2>
        <div>
            <a href="users.php" class="btn btn-primary">
                <i class="fas fa-users me-2"></i>Manage Users
            </a>
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

    <div class="row mb-4">
        <!-- Role Statistics -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Role Distribution
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($roleStats as $role => $count): ?>
                            <div class="col-md-3 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h3 class="mb-1"><?php echo $count; ?></h3>
                                        <p class="mb-0 text-muted"><?php echo ucwords(str_replace('_', ' ', $role)); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Role Permissions Matrix -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-table me-2"></i>Role Permissions Matrix
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 200px;">Permission</th>
                            <?php foreach (array_keys($rolePermissions) as $role): ?>
                                <th class="text-center" style="min-width: 100px;">
                                    <?php echo ucwords(str_replace('_', ' ', $role)); ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($availablePermissions as $permission => $description): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($description); ?></strong>
                                    <br><small class="text-muted"><?php echo $permission; ?></small>
                                </td>
                                <?php foreach (array_keys($rolePermissions) as $role): ?>
                                    <td class="text-center">
                                        <?php if (in_array($permission, $rolePermissions[$role])): ?>
                                            <i class="fas fa-check-circle text-success fs-5"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle text-danger fs-5"></i>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Users by Role -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-users-cog me-2"></i>Users by Role
            </h5>
        </div>
        <div class="card-body">
            <div class="accordion" id="roleAccordion">
                <?php 
                $currentRole = '';
                $roleIndex = 0;
                foreach ($users as $user): 
                    if ($currentRole !== $user['role']):
                        if ($currentRole !== ''): ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
                        <?php endif; 
                        $currentRole = $user['role'];
                        $roleIndex++;
                        $collapseId = 'collapse' . $roleIndex;
                        ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading<?php echo $roleIndex; ?>">
                    <button class="accordion-button <?php echo $roleIndex > 1 ? 'collapsed' : ''; ?>" type="button" 
                            data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" 
                            aria-expanded="<?php echo $roleIndex === 1 ? 'true' : 'false'; ?>">
                        <i class="fas fa-user-tag me-2"></i>
                        <?php echo ucwords(str_replace('_', ' ', $currentRole)); ?>
                        <span class="badge bg-primary ms-2"><?php echo $roleStats[$currentRole]; ?> users</span>
                    </button>
                </h2>
                <div id="<?php echo $collapseId; ?>" 
                     class="accordion-collapse collapse <?php echo $roleIndex === 1 ? 'show' : ''; ?>" 
                     aria-labelledby="heading<?php echo $roleIndex; ?>">
                    <div class="accordion-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Section</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                    <?php endif; ?>
                    <tr>
                        <td>
                            <i class="fas fa-user me-2"></i>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email'] ?? '-'); ?></td>
                        <td>
                            <?php if ($user['section']): ?>
                                <span class="badge bg-info">Section <?php echo $user['section']; ?></span>
                            <?php else: ?>
                                <span class="text-muted">All</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                        </td>
                        <td>
                            <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" 
                               class="btn btn-sm btn-outline-primary" title="Edit User">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>

    <!-- Permission Descriptions -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-info-circle me-2"></i>Permission Descriptions
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary">System Permissions</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-shield-alt text-danger me-2"></i>
                            <strong>Full System Access:</strong> Complete administrative control
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-users-cog text-primary me-2"></i>
                            <strong>Manage Users:</strong> Create, edit, and delete user accounts
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-file-alt text-info me-2"></i>
                            <strong>Generate Reports:</strong> Create and export system reports
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary">Module Permissions</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-boxes text-warning me-2"></i>
                            <strong>Inventory:</strong> View and manage inventory items
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-shopping-cart text-success me-2"></i>
                            <strong>Orders:</strong> View and process customer orders
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-truck-loading text-info me-2"></i>
                            <strong>Receiving:</strong> Manage incoming material receipts
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-cogs text-secondary me-2"></i>
                            <strong>Processing:</strong> Track and manage processing operations
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Notes -->
    <div class="alert alert-info mt-4">
        <h6 class="alert-heading">
            <i class="fas fa-lock me-2"></i>Security Notes
        </h6>
        <ul class="mb-0">
            <li>Only administrators can modify user roles and permissions</li>
            <li>Section managers have full control within their assigned section</li>
            <li>Operators have read-only access with limited write permissions</li>
            <li>All permission changes are logged in the activity log</li>
            <li>Users must re-login for permission changes to take effect</li>
        </ul>
    </div>
</div>

<script>
// Auto-collapse accordion items after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    // Keep first accordion open by default
    console.log('Permissions page loaded');
});
</script>

<?php include '../../includes/footer.php'; ?>
