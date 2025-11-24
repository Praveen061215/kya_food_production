<?php
/**
 * KYA Food Production - Section 2 Equipment Management
 * Dehydration Processing Equipment Overview & Monitoring
 */

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

// Standard session handling and access control (Section 2)
SessionManager::start();
SessionManager::requireLogin();
SessionManager::requireSection(2);

$userInfo = SessionManager::getUserInfo();

$db = (new Database())->connect();

// Create equipment table if it doesn't exist (shared across sections)
$createTableSql = "
CREATE TABLE IF NOT EXISTS equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section INT NOT NULL,
    equipment_code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    location VARCHAR(100),
    status ENUM('available','in_use','maintenance','offline') DEFAULT 'available',
    utilization_percentage DECIMAL(5,2) DEFAULT 0,
    last_maintenance_date DATE NULL,
    next_maintenance_date DATE NULL,
    temperature_range VARCHAR(50) NULL,
    capacity VARCHAR(50) NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_section (section),
    INDEX idx_equipment_code (equipment_code),
    INDEX idx_status (status)
) ENGINE=InnoDB";

try {
    $db->exec($createTableSql);
} catch (PDOException $e) {
    error_log('Equipment table creation failed: ' . $e->getMessage());
}

// Seed sample equipment for Section 2 if empty
try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM equipment WHERE section = 2");
    $stmt->execute();
    $count = (int)$stmt->fetchColumn();

    if ($count === 0) {
        $sample = [
            ['S2-DRY-001', 'Tunnel Dryer A', 'dryer', 'Processing Line 1', 'in_use', 78.5, '2024-01-05', '2024-02-05', '55-75°C', '500 kg/batch'],
            ['S2-DRY-002', 'Tunnel Dryer B', 'dryer', 'Processing Line 2', 'available', 35.0, '2023-12-20', '2024-01-20', '55-75°C', '400 kg/batch'],
            ['S2-DEH-001', 'Dehydrator 1', 'dehydrator', 'Room D2', 'maintenance', 0.0, '2024-01-10', '2024-01-25', '60-80°C', '300 kg/batch'],
            ['S2-PACK-001', 'Packing Conveyor', 'conveyor', 'Packing Area', 'in_use', 90.0, '2023-12-15', '2024-02-15', null, '1000 kg/day'],
            ['S2-QC-001', 'Quality Inspection Table', 'inspection', 'QC Lab', 'available', 10.0, '2023-11-30', '2024-03-01', null, null]
        ];

        $ins = $db->prepare("INSERT INTO equipment (section, equipment_code, name, type, location, status, utilization_percentage, last_maintenance_date, next_maintenance_date, temperature_range, capacity, notes) VALUES (2, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($sample as $row) {
            $ins->execute([
                $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7], $row[8], $row[9], 'Sample equipment record for Section 2'
            ]);
        }
    }
} catch (PDOException $e) {
    error_log('Equipment seed failed: ' . $e->getMessage());
}

// Filters
$statusFilter   = $_GET['status']   ?? '';
$typeFilter     = $_GET['type']     ?? '';
$searchFilter   = $_GET['search']   ?? '';

$where = ['section = 2'];
$params = [];

if ($statusFilter !== '') {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}
if ($typeFilter !== '') {
    $where[] = 'type = ?';
    $params[] = $typeFilter;
}
if ($searchFilter !== '') {
    $where[] = '(equipment_code LIKE ? OR name LIKE ? OR location LIKE ?)';
    $params[] = '%' . $searchFilter . '%';
    $params[] = '%' . $searchFilter . '%';
    $params[] = '%' . $searchFilter . '%';
}

$whereSql = implode(' AND ', $where);

// Stats
$statsSql = "
    SELECT 
        COUNT(*) AS total_equipment,
        SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) AS in_use,
        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS available,
        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) AS maintenance,
        AVG(utilization_percentage) AS avg_utilization
    FROM equipment
    WHERE $whereSql
";

$statsStmt = $db->prepare($statsSql);
$statsStmt->execute($params);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [
    'total_equipment' => 0,
    'in_use' => 0,
    'available' => 0,
    'maintenance' => 0,
    'avg_utilization' => 0,
];

// Equipment list
$listSql = "
    SELECT * FROM equipment
    WHERE $whereSql
    ORDER BY status, name
";

$listStmt = $db->prepare($listSql);
$listStmt->execute($params);
$equipment = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Section 2 - Equipment Management';
include '../../includes/header.php';
?>

<div class="content-area">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <span class="badge me-2 bg-warning text-dark">Section 2</span>
                Equipment Management
            </h1>
            <p class="text-muted mb-0">Monitor and manage dehydration processing equipment</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Section 2 Dashboard
        </a>
    </div>

    <!-- Stats cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-start border-primary border-3 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold fs-4"><?php echo (int)$stats['total_equipment']; ?></div>
                            <div class="text-muted small">Total Equipment</div>
                        </div>
                        <i class="fas fa-tools text-primary fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-start border-success border-3 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold fs-4"><?php echo (int)$stats['in_use']; ?></div>
                            <div class="text-muted small">In Use</div>
                        </div>
                        <i class="fas fa-play text-success fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-start border-info border-3 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold fs-4"><?php echo (int)$stats['available']; ?></div>
                            <div class="text-muted small">Available</div>
                        </div>
                        <i class="fas fa-check-circle text-info fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-start border-warning border-3 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold fs-4"><?php echo number_format((float)$stats['avg_utilization'], 1); ?>%</div>
                            <div class="text-muted small">Avg Utilization</div>
                        </div>
                        <i class="fas fa-tachometer-alt text-warning fs-3"></i>
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
                        $statuses = ['available' => 'Available', 'in_use' => 'In Use', 'maintenance' => 'Maintenance', 'offline' => 'Offline'];
                        foreach ($statuses as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo $statusFilter === $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All</option>
                        <?php
                        $types = ['dryer' => 'Dryer', 'dehydrator' => 'Dehydrator', 'conveyor' => 'Conveyor', 'inspection' => 'Inspection', 'other' => 'Other'];
                        foreach ($types as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo $typeFilter === $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($searchFilter); ?>" placeholder="Code, name, location...">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="equipment.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times me-1"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Equipment table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Equipment List</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Utilization</th>
                            <th>Maintenance</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($equipment)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                No equipment found for the selected filters.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($equipment as $eq): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($eq['equipment_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($eq['name']); ?></td>
                                <td class="text-capitalize"><?php echo htmlspecialchars($eq['type']); ?></td>
                                <td><?php echo htmlspecialchars($eq['location']); ?></td>
                                <td>
                                    <?php
                                    $badgeClass = 'bg-secondary';
                                    if ($eq['status'] === 'available') $badgeClass = 'bg-success';
                                    elseif ($eq['status'] === 'in_use') $badgeClass = 'bg-primary';
                                    elseif ($eq['status'] === 'maintenance') $badgeClass = 'bg-warning text-dark';
                                    elseif ($eq['status'] === 'offline') $badgeClass = 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?> text-uppercase small">
                                        <?php echo str_replace('_', ' ', $eq['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo (float)$eq['utilization_percentage']; ?>%;"></div>
                                        </div>
                                        <span class="ms-2 small"><?php echo number_format((float)$eq['utilization_percentage'], 1); ?>%</span>
                                    </div>
                                </td>
                                <td class="small">
                                    <div>Last: <?php echo $eq['last_maintenance_date'] ? htmlspecialchars($eq['last_maintenance_date']) : 'N/A'; ?></div>
                                    <div>Next: <?php echo $eq['next_maintenance_date'] ? htmlspecialchars($eq['next_maintenance_date']) : 'N/A'; ?></div>
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
