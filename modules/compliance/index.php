<?php
/**
 * KYA Food Production - Compliance Documents Dashboard
 * Overview of all compliance documents, certificates, and licenses
 */

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

SessionManager::start();
SessionManager::requireLogin();

// Check if user has compliance access
if (!SessionManager::hasPermission('compliance_view')) {
    header('Location: ../../dashboard.php?error=access_denied');
    exit();
}

$userInfo = SessionManager::getUserInfo();
$db = new Database();
$conn = $db->connect();

// Get filter parameters
$type_filter = $_GET['type'] ?? '';
$status_filter = $_GET['status'] ?? '';
$section_filter = $_GET['section'] ?? '';

// Build WHERE clause
$whereConditions = [];
$params = [];

if ($type_filter) {
    $whereConditions[] = "document_type = ?";
    $params[] = $type_filter;
}

if ($status_filter) {
    $whereConditions[] = "status = ?";
    $params[] = $status_filter;
}

if ($section_filter) {
    $whereConditions[] = "section = ?";
    $params[] = $section_filter;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

try {
    // Get statistics
    $statsStmt = $conn->query("
        SELECT 
            COUNT(*) as total_documents,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_documents,
            COUNT(CASE WHEN status = 'expired' THEN 1 END) as expired_documents,
            COUNT(CASE WHEN status = 'pending_renewal' THEN 1 END) as pending_renewal,
            COUNT(CASE WHEN expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as expiring_soon,
            COUNT(CASE WHEN expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY) THEN 1 END) as expiring_60_days
        FROM compliance_documents
    ");
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get all documents
    $docsStmt = $conn->prepare("
        SELECT 
            cd.*,
            u.full_name as uploaded_by_name,
            CASE 
                WHEN cd.expiry_date < CURDATE() THEN 'expired'
                WHEN cd.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring_soon'
                WHEN cd.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY) THEN 'expiring_60'
                ELSE 'valid'
            END as expiry_status,
            DATEDIFF(cd.expiry_date, CURDATE()) as days_until_expiry
        FROM compliance_documents cd
        LEFT JOIN users u ON cd.uploaded_by = u.id
        $whereClause
        ORDER BY cd.expiry_date ASC, cd.uploaded_at DESC
    ");
    $docsStmt->execute($params);
    $documents = $docsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get expiring documents for alerts
    $expiringStmt = $conn->query("
        SELECT *
        FROM compliance_documents
        WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        AND status = 'active'
        ORDER BY expiry_date ASC
        LIMIT 5
    ");
    $expiringDocs = $expiringStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Compliance documents error: " . $e->getMessage());
    $stats = ['total_documents' => 0, 'active_documents' => 0, 'expired_documents' => 0, 'pending_renewal' => 0, 'expiring_soon' => 0, 'expiring_60_days' => 0];
    $documents = [];
    $expiringDocs = [];
}

$pageTitle = 'Compliance Documents';
include '../../includes/header.php';
?>

<style>
.stats-card {
    border-left: 4px solid;
    transition: transform 0.2s;
}
.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.stats-card.primary { border-left-color: #007bff; }
.stats-card.success { border-left-color: #28a745; }
.stats-card.warning { border-left-color: #ffc107; }
.stats-card.danger { border-left-color: #dc3545; }
.stats-card.info { border-left-color: #17a2b8; }

.document-card {
    border-left: 4px solid #28a745;
    transition: all 0.3s;
}
.document-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.document-card.expiring-soon {
    border-left-color: #ffc107;
    background: #fff9e6;
}
.document-card.expired {
    border-left-color: #dc3545;
    background: #ffe6e6;
}

.badge-expiring {
    background: #ffc107;
    color: #000;
}
.badge-expired {
    background: #dc3545;
}
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Compliance Documents</h1>
            <p class="text-muted mb-0">Manage certificates, licenses, and compliance documentation</p>
        </div>
        <div>
            <a href="upload.php" class="btn btn-primary">
                <i class="fas fa-upload me-2"></i>Upload Document
            </a>
            <a href="certificates.php" class="btn btn-success">
                <i class="fas fa-certificate me-2"></i>Certificates
            </a>
        </div>
    </div>

    <!-- Expiring Documents Alert -->
    <?php if (!empty($expiringDocs)): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Documents Expiring Soon</h5>
        <ul class="mb-0">
            <?php foreach ($expiringDocs as $doc): ?>
            <li>
                <strong><?php echo htmlspecialchars($doc['document_name']); ?></strong> 
                expires on <?php echo date('M d, Y', strtotime($doc['expiry_date'])); ?>
                (<?php echo max(0, (int)((strtotime($doc['expiry_date']) - time()) / 86400)); ?> days remaining)
            </li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card stats-card primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Documents</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['total_documents']); ?></h3>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-file-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card stats-card success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Active</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['active_documents']); ?></h3>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card stats-card warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Expiring (30d)</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['expiring_soon']); ?></h3>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card stats-card danger h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Expired</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['expired_documents']); ?></h3>
                        </div>
                        <div class="text-danger">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card stats-card info h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Pending Renewal</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['pending_renewal']); ?></h3>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-sync-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card stats-card warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Expiring (60d)</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['expiring_60_days']); ?></h3>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Document Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="certificate" <?php echo $type_filter === 'certificate' ? 'selected' : ''; ?>>Certificates</option>
                        <option value="license" <?php echo $type_filter === 'license' ? 'selected' : ''; ?>>Licenses</option>
                        <option value="audit" <?php echo $type_filter === 'audit' ? 'selected' : ''; ?>>Audits</option>
                        <option value="permit" <?php echo $type_filter === 'permit' ? 'selected' : ''; ?>>Permits</option>
                        <option value="inspection" <?php echo $type_filter === 'inspection' ? 'selected' : ''; ?>>Inspections</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="expired" <?php echo $status_filter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                        <option value="pending_renewal" <?php echo $status_filter === 'pending_renewal' ? 'selected' : ''; ?>>Pending Renewal</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Section</label>
                    <select name="section" class="form-select">
                        <option value="">All Sections</option>
                        <option value="1" <?php echo $section_filter === '1' ? 'selected' : ''; ?>>Section 1</option>
                        <option value="2" <?php echo $section_filter === '2' ? 'selected' : ''; ?>>Section 2</option>
                        <option value="3" <?php echo $section_filter === '3' ? 'selected' : ''; ?>>Section 3</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-redo me-1"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Documents List -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-folder-open me-2"></i>Compliance Documents
                <span class="badge bg-primary ms-2"><?php echo count($documents); ?></span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($documents)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No compliance documents found</p>
                    <a href="upload.php" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>Upload First Document
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($documents as $doc): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card document-card h-100 <?php 
                            echo $doc['expiry_status'] === 'expired' ? 'expired' : 
                                ($doc['expiry_status'] === 'expiring_soon' ? 'expiring-soon' : ''); 
                        ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-<?php 
                                            echo $doc['document_type'] === 'certificate' ? 'certificate' : 
                                                ($doc['document_type'] === 'license' ? 'id-card' : 
                                                ($doc['document_type'] === 'audit' ? 'clipboard-check' : 
                                                ($doc['document_type'] === 'permit' ? 'stamp' : 'file-alt'))); 
                                        ?> me-2"></i>
                                        <?php echo htmlspecialchars($doc['document_name']); ?>
                                    </h6>
                                    <span class="badge bg-<?php 
                                        echo $doc['status'] === 'active' ? 'success' : 
                                            ($doc['status'] === 'expired' ? 'danger' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $doc['status'])); ?>
                                    </span>
                                </div>
                                
                                <p class="text-muted small mb-2">
                                    <strong>Type:</strong> <?php echo ucfirst($doc['document_type']); ?><br>
                                    <?php if ($doc['document_number']): ?>
                                    <strong>Number:</strong> <?php echo htmlspecialchars($doc['document_number']); ?><br>
                                    <?php endif; ?>
                                    <?php if ($doc['issuing_authority']): ?>
                                    <strong>Authority:</strong> <?php echo htmlspecialchars($doc['issuing_authority']); ?><br>
                                    <?php endif; ?>
                                    <?php if ($doc['issue_date']): ?>
                                    <strong>Issued:</strong> <?php echo date('M d, Y', strtotime($doc['issue_date'])); ?><br>
                                    <?php endif; ?>
                                    <?php if ($doc['expiry_date']): ?>
                                    <strong>Expires:</strong> <?php echo date('M d, Y', strtotime($doc['expiry_date'])); ?>
                                    <?php if ($doc['days_until_expiry'] !== null && $doc['days_until_expiry'] >= 0): ?>
                                        <span class="badge badge-expiring ms-1"><?php echo $doc['days_until_expiry']; ?> days</span>
                                    <?php elseif ($doc['days_until_expiry'] < 0): ?>
                                        <span class="badge badge-expired ms-1">Expired</span>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </p>
                                
                                <div class="d-flex gap-2 mt-3">
                                    <a href="view.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-primary flex-fill">
                                        <i class="fas fa-eye me-1"></i>View
                                    </a>
                                    <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" class="btn btn-sm btn-success" download>
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <?php if (SessionManager::hasPermission('compliance_manage')): ?>
                                    <a href="edit.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
