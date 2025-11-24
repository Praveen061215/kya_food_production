<?php
/**
 * KYA Food Production - Section 3 Quality Check
 * Packaging & Storage Quality Inspection Overview
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

// Create section3_quality_checks table if it doesn't exist (scoped to Section 3)
$createTableSql = "
CREATE TABLE IF NOT EXISTS section3_quality_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section INT NOT NULL DEFAULT 3,
    batch_id VARCHAR(50) NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    packaging_type VARCHAR(100) NOT NULL,
    inspection_date DATETIME NOT NULL,
    inspector_name VARCHAR(100) NOT NULL,
    checklist TEXT NOT NULL,
    defects_found INT DEFAULT 0,
    critical_defects INT DEFAULT 0,
    major_defects INT DEFAULT 0,
    minor_defects INT DEFAULT 0,
    status ENUM('passed','rework','rejected') DEFAULT 'passed',
    overall_grade ENUM('A+','A','B+','B','C','D') DEFAULT 'A',
    corrective_actions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_section (section),
    INDEX idx_batch_id (batch_id),
    INDEX idx_status (status),
    INDEX idx_inspection_date (inspection_date)
) ENGINE=InnoDB";

try {
    $db->exec($createTableSql);
} catch (PDOException $e) {
    error_log('Section3 quality_checks table creation failed: ' . $e->getMessage());
}

// Seed sample quality checks for Section 3 if empty
try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM section3_quality_checks WHERE section = 3");
    $stmt->execute();
    $count = (int)$stmt->fetchColumn();

    if ($count === 0) {
        $sample = [
            ['S3-BATCH-001', 'Dried Mango - 100g Pouch', 'Retail Pouch', '2024-01-15 09:30:00', 'QC Inspector 1', 0, 0, 0, 'passed', 'A+', 'All parameters within specification.'],
            ['S3-BATCH-002', 'Mixed Veg Chips - 200g', 'Retail Pouch', '2024-01-15 11:00:00', 'QC Inspector 2', 3, 0, 1, 'rework', 'B+', 'Minor sealing and weight deviations, rework required.'],
            ['S3-BATCH-003', 'Herbal Tea Mix - Carton', 'Export Carton', '2024-01-14 15:30:00', 'QC Inspector 1', 1, 1, 0, 'rejected', 'C', 'Incorrect label information, batch rejected.'],
            ['S3-BATCH-004', 'Bulk 25kg Bag', 'Bulk Bag', '2024-01-14 10:15:00', 'QC Inspector 3', 2, 0, 2, 'rework', 'B', 'Two bags with damaged stitching, rework required.']
        ];

        $ins = $db->prepare("INSERT INTO section3_quality_checks (section, batch_id, product_name, packaging_type, inspection_date, inspector_name, checklist, defects_found, critical_defects, major_defects, minor_defects, status, overall_grade, corrective_actions) VALUES (3, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($sample as $row) {
            $ins->execute([
                $row[0],
                $row[1],
                $row[2],
                $row[3],
                $row[4],
                "Packaging integrity\nLabel accuracy\nNet weight\nSealing quality",
                $row[5],
                $row[6],
                $row[7],
                $row[5] + $row[6] + $row[7],
                $row[8],
                $row[9],
                $row[10]
            ]);
        }
    }
} catch (PDOException $e) {
    error_log('Section3 quality_checks seed failed: ' . $e->getMessage());
}

// Filters
$statusFilter   = $_GET['status']   ?? '';
$gradeFilter    = $_GET['grade']    ?? '';
$searchFilter   = $_GET['search']   ?? '';
date_default_timezone_set('Asia/Colombo');
$dateFromFilter = $_GET['date_from'] ?? '';
$dateToFilter   = $_GET['date_to']   ?? '';

$where = ['section = 3'];
$params = [];

if ($statusFilter !== '') {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}

if ($gradeFilter !== '') {
    $where[] = 'overall_grade = ?';
    $params[] = $gradeFilter;
}

if ($searchFilter !== '') {
    $where[] = '(batch_id LIKE ? OR product_name LIKE ? OR inspector_name LIKE ?)';
    $params[] = '%' . $searchFilter . '%';
    $params[] = '%' . $searchFilter . '%';
    $params[] = '%' . $searchFilter . '%';
}

if ($dateFromFilter !== '') {
    $where[] = 'DATE(inspection_date) >= ?';
    $params[] = $dateFromFilter;
}

if ($dateToFilter !== '') {
    $where[] = 'DATE(inspection_date) <= ?';
    $params[] = $dateToFilter;
}

$whereSql = implode(' AND ', $where);

// Stats
$statsSql = "
    SELECT
        COUNT(*) AS total_checks,
        SUM(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) AS passed_checks,
        SUM(CASE WHEN status = 'rework' THEN 1 ELSE 0 END) AS rework_checks,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_checks,
        AVG(defects_found) AS avg_defects
    FROM section3_quality_checks
    WHERE $whereSql
";

$statsStmt = $db->prepare($statsSql);
$statsStmt->execute($params);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [
    'total_checks' => 0,
    'passed_checks' => 0,
    'rework_checks' => 0,
    'rejected_checks' => 0,
    'avg_defects' => 0,
];

// List
$listSql = "
    SELECT * FROM section3_quality_checks
    WHERE $whereSql
    ORDER BY inspection_date DESC
";

$listStmt = $db->prepare($listSql);
$listStmt->execute($params);
$checks = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Section 3 - Quality Check';
include '../../includes/header.php';
?>

<div class="content-area">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <span class="badge me-2 bg-info text-dark">Section 3</span>
                Quality Checks
            </h1>
            <p class="text-muted mb-0">Packaging and storage quality inspections and results</p>
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
                            <div class="fw-bold fs-4"><?php echo (int)$stats['total_checks']; ?></div>
                            <div class="text-muted small">Total Inspections</div>
                        </div>
                        <i class="fas fa-clipboard-check text-primary fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-start border-success border-3 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold fs-4"><?php echo (int)$stats['passed_checks']; ?></div>
                            <div class="text-muted small">Passed</div>
                        </div>
                        <i class="fas fa-check-circle text-success fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-start border-warning border-3 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold fs-4"><?php echo (int)$stats['rework_checks']; ?></div>
                            <div class="text-muted small">Rework</div>
                        </div>
                        <i class="fas fa-tools text-warning fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-start border-danger border-3 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold fs-4"><?php echo (int)$stats['rejected_checks']; ?></div>
                            <div class="text-muted small">Rejected</div>
                        </div>
                        <i class="fas fa-times-circle text-danger fs-3"></i>
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
                            'passed' => 'Passed',
                            'rework' => 'Rework',
                            'rejected' => 'Rejected'
                        ];
                        foreach ($statuses as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo $statusFilter === $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Grade</label>
                    <select name="grade" class="form-select">
                        <option value="">All</option>
                        <?php
                        $grades = ['A+','A','B+','B','C','D'];
                        foreach ($grades as $grade): ?>
                            <option value="<?php echo $grade; ?>" <?php echo $gradeFilter === $grade ? 'selected' : ''; ?>>
                                <?php echo $grade; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFromFilter); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateToFilter); ?>">
                </div>
                <div class="col-md-9">
                    <label class="form-label">Search</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($searchFilter); ?>" placeholder="Batch ID, product, inspector...">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <a href="quality_check.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times me-1"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Checks table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Quality Checks</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Batch ID</th>
                            <th>Product</th>
                            <th>Packaging</th>
                            <th>Inspector</th>
                            <th>Status</th>
                            <th>Grade</th>
                            <th>Defects</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($checks)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                No quality checks found for the selected criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($checks as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($row['inspection_date']))); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['batch_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['packaging_type']); ?></td>
                                <td><?php echo htmlspecialchars($row['inspector_name']); ?></td>
                                <td>
                                    <?php
                                    $badgeClass = 'bg-secondary';
                                    if ($row['status'] === 'passed') $badgeClass = 'bg-success';
                                    elseif ($row['status'] === 'rework') $badgeClass = 'bg-warning text-dark';
                                    elseif ($row['status'] === 'rejected') $badgeClass = 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?> text-uppercase small">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($row['overall_grade']); ?></span>
                                </td>
                                <td class="small">
                                    <div>Total: <?php echo (int)$row['defects_found']; ?></div>
                                    <div>Critical: <?php echo (int)$row['critical_defects']; ?></div>
                                    <div>Major: <?php echo (int)$row['major_defects']; ?></div>
                                    <div>Minor: <?php echo (int)$row['minor_defects']; ?></div>
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
