<?php
/**
 * KYA Food Production - Activity Logs
 * Admin panel for viewing system activity logs
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

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Filters
$filterUser = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$filterModule = isset($_GET['module']) ? sanitizeInput($_GET['module']) : '';
$filterAction = isset($_GET['action']) ? sanitizeInput($_GET['action']) : '';
$filterDateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filterDateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build WHERE clause
$whereConditions = [];
$params = [];

if ($filterUser > 0) {
    $whereConditions[] = "al.user_id = ?";
    $params[] = $filterUser;
}

if (!empty($filterModule)) {
    $whereConditions[] = "al.module = ?";
    $params[] = $filterModule;
}

if (!empty($filterAction)) {
    $whereConditions[] = "al.action LIKE ?";
    $params[] = "%{$filterAction}%";
}

if (!empty($filterDateFrom)) {
    $whereConditions[] = "DATE(al.created_at) >= ?";
    $params[] = $filterDateFrom;
}

if (!empty($filterDateTo)) {
    $whereConditions[] = "DATE(al.created_at) <= ?";
    $params[] = $filterDateTo;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
try {
    $countStmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM activity_logs al
        $whereClause
    ");
    $countStmt->execute($params);
    $totalLogs = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalLogs / $perPage);
} catch (Exception $e) {
    error_log('Count logs error: ' . $e->getMessage());
    $totalLogs = 0;
    $totalPages = 1;
}

// Get activity logs
try {
    $stmt = $conn->prepare("
        SELECT 
            al.*,
            u.username,
            u.full_name,
            u.role
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        $whereClause
        ORDER BY al.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $perPage;
    $params[] = $offset;
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Fetch logs error: ' . $e->getMessage());
    $logs = [];
}

// Get all users for filter
try {
    $usersStmt = $conn->query("SELECT id, username, full_name FROM users ORDER BY username");
    $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $users = [];
}

// Get unique modules
$modules = ['auth', 'inventory', 'orders', 'receiving', 'processing', 'users', 'settings', 'reports'];

// Get statistics
try {
    $statsStmt = $conn->query("
        SELECT 
            COUNT(*) as total_logs,
            COUNT(DISTINCT user_id) as unique_users,
            COUNT(DISTINCT module) as unique_modules,
            COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_logs
        FROM activity_logs
    ");
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $stats = ['total_logs' => 0, 'unique_users' => 0, 'unique_modules' => 0, 'today_logs' => 0];
}

$pageTitle = 'Activity Logs';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0">
            <i class="fas fa-history me-2"></i>Activity Logs
        </h2>
        <div>
            <button type="button" class="btn btn-success" onclick="exportLogs()">
                <i class="fas fa-file-export me-2"></i>Export Logs
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Admin
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total Logs</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['total_logs']); ?></h3>
                        </div>
                        <i class="fas fa-database fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Today's Logs</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['today_logs']); ?></h3>
                        </div>
                        <i class="fas fa-calendar-day fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Active Users</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['unique_users']); ?></h3>
                        </div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Modules</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['unique_modules']); ?></h3>
                        </div>
                        <i class="fas fa-th-large fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter me-2"></i>Filter Logs
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="user_id" class="form-label">User</label>
                    <select name="user_id" id="user_id" class="form-select">
                        <option value="">All Users</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" 
                                    <?php echo $filterUser == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['username'] . ' - ' . $user['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="module" class="form-label">Module</label>
                    <select name="module" id="module" class="form-select">
                        <option value="">All Modules</option>
                        <?php foreach ($modules as $module): ?>
                            <option value="<?php echo $module; ?>" 
                                    <?php echo $filterModule == $module ? 'selected' : ''; ?>>
                                <?php echo ucfirst($module); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="action" class="form-label">Action</label>
                    <input type="text" name="action" id="action" class="form-control" 
                           value="<?php echo htmlspecialchars($filterAction); ?>" 
                           placeholder="e.g., login, create">
                </div>

                <div class="col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" 
                           value="<?php echo htmlspecialchars($filterDateFrom); ?>">
                </div>

                <div class="col-md-2">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" 
                           value="<?php echo htmlspecialchars($filterDateTo); ?>">
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Apply Filters
                    </button>
                    <a href="logs.php" class="btn btn-secondary">
                        <i class="fas fa-redo me-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Activity Logs Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Activity Log Entries
                <span class="badge bg-secondary"><?php echo number_format($totalLogs); ?> total</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th style="width: 180px;">Timestamp</th>
                            <th style="width: 150px;">User</th>
                            <th style="width: 100px;">Module</th>
                            <th style="width: 150px;">Action</th>
                            <th>Details</th>
                            <th style="width: 120px;">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No activity logs found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo $log['id']; ?></td>
                                    <td>
                                        <small>
                                            <?php echo date('M d, Y', strtotime($log['created_at'])); ?><br>
                                            <span class="text-muted"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($log['username']): ?>
                                            <strong><?php echo htmlspecialchars($log['username']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($log['full_name'] ?? ''); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($log['module'] ?? 'system'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $actionClass = 'secondary';
                                        if (strpos($log['action'], 'create') !== false || strpos($log['action'], 'add') !== false) {
                                            $actionClass = 'success';
                                        } elseif (strpos($log['action'], 'update') !== false || strpos($log['action'], 'edit') !== false) {
                                            $actionClass = 'warning';
                                        } elseif (strpos($log['action'], 'delete') !== false || strpos($log['action'], 'remove') !== false) {
                                            $actionClass = 'danger';
                                        } elseif (strpos($log['action'], 'login') !== false) {
                                            $actionClass = 'primary';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $actionClass; ?>">
                                            <?php echo htmlspecialchars($log['action']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <?php 
                                            $details = $log['details'] ?? '';
                                            if (strlen($details) > 100) {
                                                echo htmlspecialchars(substr($details, 0, 100)) . '...';
                                            } else {
                                                echo htmlspecialchars($details);
                                            }
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer">
                <nav>
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $filterUser ? '&user_id=' . $filterUser : ''; ?><?php echo $filterModule ? '&module=' . $filterModule : ''; ?><?php echo $filterAction ? '&action=' . $filterAction : ''; ?><?php echo $filterDateFrom ? '&date_from=' . $filterDateFrom : ''; ?><?php echo $filterDateTo ? '&date_to=' . $filterDateTo : ''; ?>">
                                Previous
                            </a>
                        </li>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $filterUser ? '&user_id=' . $filterUser : ''; ?><?php echo $filterModule ? '&module=' . $filterModule : ''; ?><?php echo $filterAction ? '&action=' . $filterAction : ''; ?><?php echo $filterDateFrom ? '&date_from=' . $filterDateFrom : ''; ?><?php echo $filterDateTo ? '&date_to=' . $filterDateTo : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $filterUser ? '&user_id=' . $filterUser : ''; ?><?php echo $filterModule ? '&module=' . $filterModule : ''; ?><?php echo $filterAction ? '&action=' . $filterAction : ''; ?><?php echo $filterDateFrom ? '&date_from=' . $filterDateFrom : ''; ?><?php echo $filterDateTo ? '&date_to=' . $filterDateTo : ''; ?>">
                                Next
                            </a>
                        </li>
                    </ul>
                </nav>
                <p class="text-center text-muted mt-2 mb-0">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $perPage, $totalLogs); ?> of <?php echo number_format($totalLogs); ?> logs
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function exportLogs() {
    alert('Export functionality will be implemented. This will export logs to CSV format.');
    // TODO: Implement CSV export
}
</script>

<?php include '../../includes/footer.php'; ?>
