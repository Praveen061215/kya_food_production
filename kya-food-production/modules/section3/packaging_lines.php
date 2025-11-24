<?php
/**
 * KYA Food Production - Section 3 Packaging Lines
 * Packaging & Storage Line Overview and Monitoring
 */

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

// Standard session handling and access control (Section 3)
SessionManager::start();
SessionManager::requireLogin();
SessionManager::requireSection(3);

$userInfo = SessionManager::getUserInfo();

$db = (new Database())->connect();

// Create packaging_lines table if it doesn't exist (shared, scoped by section)
$createTableSql = "
CREATE TABLE IF NOT EXISTS packaging_lines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section INT NOT NULL,
    line_code VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    product_family VARCHAR(100) NOT NULL,
    status ENUM('running','idle','maintenance','stopped') DEFAULT 'idle',
    current_speed_units_per_hour INT DEFAULT 0,
    target_speed_units_per_hour INT DEFAULT 0,
    oee_percentage DECIMAL(5,2) DEFAULT 0,
    shift_production_units INT DEFAULT 0,
    last_changeover DATETIME NULL,
    next_planned_changeover DATETIME NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_section (section),
    INDEX idx_line_code (line_code),
    INDEX idx_status (status)
) ENGINE=InnoDB";

try {
    $db->exec($createTableSql);
} catch (PDOException $e) {
    error_log('Packaging lines table creation failed: ' . $e->getMessage());
}

// Seed sample packaging lines for Section 3 if empty
try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM packaging_lines WHERE section = 3");
    $stmt->execute();
    $count = (int)$stmt->fetchColumn();

    if ($count === 0) {
        $sample = [
            ['S3-LINE-001', 'Retail Pouch Line', 'Retail Packs', 'running', 4800, 5000, 92.5, 38000, '2024-01-15 06:00:00', '2024-01-15 18:00:00'],
            ['S3-LINE-002', 'Bulk Bagging Line', 'Bulk 25kg', 'idle', 0, 2000, 0.0, 12000, '2024-01-14 14:00:00', '2024-01-16 08:00:00'],
            ['S3-LINE-003', 'Export Carton Line', 'Export Cartons', 'maintenance', 0, 3000, 0.0, 0, '2024-01-13 10:00:00', '2024-01-20 10:00:00'],
            ['S3-LINE-004', 'Sampling & Promotions Line', 'Samples', 'stopped', 0, 1000, 0.0, 1500, '2024-01-12 09:00:00', null]
        ];

        $ins = $db->prepare("INSERT INTO packaging_lines (section, line_code, name, product_family, status, current_speed_units_per_hour, target_speed_units_per_hour, oee_percentage, shift_production_units, last_changeover, next_planned_changeover, notes) VALUES (3, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($sample as $row) {
            $ins->execute([
                $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7], $row[8], $row[9], 'Sample packaging line record for Section 3'
            ]);
        }
    }
} catch (PDOException $e) {
    error_log('Packaging lines seed failed: ' . $e->getMessage());
}

// Filters
$statusFilter   = $_GET['status']   ?? '';
$productFilter  = $_GET['product_family'] ?? '';
$searchFilter   = $_GET['search']   ?? '';

$where = ['section = 3'];
$params = [];

if ($statusFilter !== '') {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}

if ($productFilter !== '') {
    $where[] = 'product_family = ?';
    $params[] = $productFilter;
}

if ($searchFilter !== '') {
    $where[] = '(line_code LIKE ? OR name LIKE ? OR product_family LIKE ?)';
    $params[] = '%' . $searchFilter . '%';
    $params[] = '%' . $searchFilter . '%';
    $params[] = '%' . $searchFilter . '%';
}

$whereSql = implode(' AND ', $where);

// Stats
$statsSql = "
    SELECT
        COUNT(*) AS total_lines,
        SUM(CASE WHEN status = 'running' THEN 1 ELSE 0 END) AS running_lines,
        SUM(CASE WHEN status = 'idle' THEN 1 ELSE 0 END) AS idle_lines,
        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) AS maintenance_lines,
        AVG(oee_percentage) AS avg_oee,
        SUM(shift_production_units) AS total_shift_output
    FROM packaging_lines
    WHERE $whereSql
";

$statsStmt = $db->prepare($statsSql);
$statsStmt->execute($params);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [
    'total_lines' => 0,
    'running_lines' => 0,
    'idle_lines' => 0,
    'maintenance_lines' => 0,
    'avg_oee' => 0,
    'total_shift_output' => 0,
];

// Lines list
$listSql = "
    SELECT * FROM packaging_lines
    WHERE $whereSql
    ORDER BY status, name
";

$listStmt = $db->prepare($listSql);
$listStmt->execute($params);
$lines = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Section 3 - Packaging Lines';
include '../../includes/header.php';
?>

<div class="content-area">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <span class="badge me-2 bg-info text-dark">Section 3</span>
                Packaging Lines Overview
            </h1>
            <p class="text-muted mb-0">Monitor packaging line status, speed, and performance</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Section 3 Dashboard
        </a>
    </div>

    <!-- Stats cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-start border-primary border-3 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold fs-4"><?php echo (int)$stats['total_lines']; ?></div>
                            <div class="text-muted small">Total Lines</div>
                        </div>
                        <i class="fas fa-industry text-primary fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-start border-success border-3 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold fs-4"><?php echo (int)$stats['running_lines']; ?></div>
                            <div class="text-muted small">Running</div>
                        </div>
                        <i class="fas fa-play text-success fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-start border-warning border-3 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold fs-4"><?php echo (int)$stats['idle_lines']; ?></div>
                            <div class="text-muted small">Idle</div>
                        </div>
                        <i class="fas fa-pause text-warning fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-start border-danger border-3 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold fs-4"><?php echo number_format((float)$stats['avg_oee'], 1); ?>%</div>
                            <div class="text-muted small">Average OEE</div>
                        </div>
                        <i class="fas fa-tachometer-alt text-danger fs-3"></i>
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
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <?php
                        $statuses = [
                            'running' => 'Running',
                            'idle' => 'Idle',
                            'maintenance' => 'Maintenance',
                            'stopped' => 'Stopped'
                        ];
                        foreach ($statuses as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo $statusFilter === $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Product Family</label>
                    <input type="text" name="product_family" class="form-control" value="<?php echo htmlspecialchars($productFilter); ?>" placeholder="Retail Packs, Bulk 25kg, Export Cartons...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($searchFilter); ?>" placeholder="Line code, name...">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <a href="packaging_lines.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Lines table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-industry me-2"></i>Packaging Lines</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Line Code</th>
                            <th>Name</th>
                            <th>Product Family</th>
                            <th>Status</th>
                            <th>Speed (units/hr)</th>
                            <th>OEE</th>
                            <th>Shift Output</th>
                            <th>Changeover</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($lines)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                No packaging lines found for the selected criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($lines as $line): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($line['line_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($line['name']); ?></td>
                                <td><?php echo htmlspecialchars($line['product_family']); ?></td>
                                <td>
                                    <?php
                                    $badgeClass = 'bg-secondary';
                                    if ($line['status'] === 'running') $badgeClass = 'bg-success';
                                    elseif ($line['status'] === 'idle') $badgeClass = 'bg-warning text-dark';
                                    elseif ($line['status'] === 'maintenance') $badgeClass = 'bg-info text-dark';
                                    elseif ($line['status'] === 'stopped') $badgeClass = 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?> text-uppercase small">
                                        <?php echo htmlspecialchars($line['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small">
                                        <div>Current: <?php echo (int)$line['current_speed_units_per_hour']; ?></div>
                                        <div class="text-muted">Target: <?php echo (int)$line['target_speed_units_per_hour']; ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo (float)$line['oee_percentage']; ?>%;"></div>
                                        </div>
                                        <span class="ms-2 small"><?php echo number_format((float)$line['oee_percentage'], 1); ?>%</span>
                                    </div>
                                </td>
                                <td><?php echo (int)$line['shift_production_units']; ?></td>
                                <td class="small">
                                    <div>Last: <?php echo $line['last_changeover'] ? htmlspecialchars($line['last_changeover']) : 'N/A'; ?></div>
                                    <div>Next: <?php echo $line['next_planned_changeover'] ? htmlspecialchars($line['next_planned_changeover']) : 'N/A'; ?></div>
                                </td>
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
