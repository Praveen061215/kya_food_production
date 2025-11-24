<?php
/**
 * KYA Food Production - Admin Users Management
 * View and manage application users
 */

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

SessionManager::start();
SessionManager::requireLogin();
SessionManager::requireRole('admin');

$userInfo = SessionManager::getUserInfo();

$db = (new Database())->connect();

// Filters
$roleFilter   = $_GET['role']   ?? '';
$statusFilter = $_GET['status'] ?? '';
$searchFilter = $_GET['search'] ?? '';

$where = ['1=1'];
$params = [];

if ($roleFilter !== '') {
    $where[] = 'role = ?';
    $params[] = $roleFilter;
}

if ($statusFilter !== '') {
    // assuming users table has an `is_active` column (1=active,0=inactive)
    if ($statusFilter === 'active') {
        $where[] = 'is_active = 1';
    } elseif ($statusFilter === 'inactive') {
        $where[] = 'is_active = 0';
    }
}

if ($searchFilter !== '') {
    $where[] = '(username LIKE ? OR full_name LIKE ? OR email LIKE ?)';
    $params[] = '%' . $searchFilter . '%';
    $params[] = '%' . $searchFilter . '%';
    $params[] = '%' . $searchFilter . '%';
}

$whereSql = implode(' AND ', $where);

// Load users
$sql = "
    SELECT id, username, full_name, email, role, is_active, created_at, last_login
    FROM users
    WHERE $whereSql
    ORDER BY created_at DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Basic stats
$statsSql = "
    SELECT
        COUNT(*) AS total_users,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_users,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive_users
    FROM users
";

$stats = $db->query($statsSql)->fetch(PDO::FETCH_ASSOC) ?: [
    'total_users' => 0,
    'active_users' => 0,
    'inactive_users' => 0,
];

$pageTitle = 'Admin - Users';
include '../../includes/header.php';
?>

<div class="content-area">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <span class="badge me-2 bg-danger">Admin</span>
                User Management
            </h1>
            <p class="text-muted mb-0">View application users, roles, and statuses.</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Admin Dashboard
        </a>
    </div>

    <!-- Stats cards -->
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card border-start border-primary border-3 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold fs-4"><?php echo (int)$stats['total_users']; ?></div>
                            <div class="text-muted small">Total Users</div>
                        </div>
                        <i class="fas fa-users text-primary fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card border-start border-success border-3 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold fs-4"><?php echo (int)$stats['active_users']; ?></div>
                            <div class="text-muted small">Active</div>
                        </div>
                        <i class="fas fa-user-check text-success fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card border-start border-secondary border-3 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold fs-4"><?php echo (int)$stats['inactive_users']; ?></div>
                            <div class="text-muted small">Inactive</div>
                        </div>
                        <i class="fas fa-user-slash text-secondary fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form class="row g-3" method="get">
                <div class="col-md-3">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <?php
                        // assumes USER_ROLES constant/array exists like elsewhere in the app
                        foreach (USER_ROLES as $roleKey => $roleDef): ?>
                            <option value="<?php echo $roleKey; ?>" <?php echo $roleFilter === $roleKey ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($roleDef['label'] ?? ucfirst($roleKey)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($searchFilter); ?>" placeholder="Username, full name, email...">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <a href="users.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Users table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-users me-2"></i>Users</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Last Login</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                No users found for the selected criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo (int)$u['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($u['full_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($u['email'] ?? ''); ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($u['role']); ?></span>
                                </td>
                                <td>
                                    <?php if ((int)$u['is_active'] === 1): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?php echo htmlspecialchars($u['created_at'] ?? ''); ?></td>
                                <td class="small"><?php echo htmlspecialchars($u['last_login'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
