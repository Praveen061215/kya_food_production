<?php
/**
 * KYA Food Production - Certificates Management
 * View and manage all certificates
 */

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

SessionManager::start();
SessionManager::requireLogin();

if (!SessionManager::hasPermission('compliance_view')) {
    header('Location: ../../dashboard.php?error=access_denied');
    exit();
}

$userInfo = SessionManager::getUserInfo();
$db = new Database();
$conn = $db->connect();

try {
    // Get all certificates
    $stmt = $conn->prepare("
        SELECT 
            cd.*,
            u.full_name as uploaded_by_name,
            DATEDIFF(cd.expiry_date, CURDATE()) as days_until_expiry
        FROM compliance_documents cd
        LEFT JOIN users u ON cd.uploaded_by = u.id
        WHERE cd.document_type = 'certificate'
        ORDER BY cd.expiry_date ASC, cd.document_name
    ");
    $stmt->execute();
    $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Certificates error: " . $e->getMessage());
    $certificates = [];
}

$pageTitle = 'Certificates Management';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Certificates Management</h1>
            <p class="text-muted mb-0">View and manage all compliance certificates</p>
        </div>
        <div>
            <a href="upload.php" class="btn btn-primary">
                <i class="fas fa-upload me-2"></i>Upload Certificate
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-certificate me-2"></i>All Certificates
                <span class="badge bg-primary ms-2"><?php echo count($certificates); ?></span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($certificates)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-certificate fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No certificates found</p>
                    <a href="upload.php" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>Upload First Certificate
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Certificate Name</th>
                                <th>Number</th>
                                <th>Issuing Authority</th>
                                <th>Issue Date</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($certificates as $cert): ?>
                            <tr class="<?php echo $cert['status'] === 'expired' ? 'table-danger' : ($cert['days_until_expiry'] !== null && $cert['days_until_expiry'] <= 30 ? 'table-warning' : ''); ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($cert['document_name']); ?></strong>
                                    <?php if ($cert['section']): ?>
                                    <br><small class="text-muted">Section <?php echo $cert['section']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($cert['document_number']); ?></td>
                                <td><?php echo htmlspecialchars($cert['issuing_authority']); ?></td>
                                <td><?php echo $cert['issue_date'] ? date('M d, Y', strtotime($cert['issue_date'])) : '-'; ?></td>
                                <td>
                                    <?php if ($cert['expiry_date']): ?>
                                        <?php echo date('M d, Y', strtotime($cert['expiry_date'])); ?>
                                        <?php if ($cert['days_until_expiry'] !== null && $cert['days_until_expiry'] >= 0): ?>
                                            <br><small class="text-muted">(<?php echo $cert['days_until_expiry']; ?> days)</small>
                                        <?php elseif ($cert['days_until_expiry'] < 0): ?>
                                            <br><small class="text-danger">(Expired)</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $cert['status'] === 'active' ? 'success' : 
                                            ($cert['status'] === 'expired' ? 'danger' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $cert['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="view.php?id=<?php echo $cert['id']; ?>" class="btn btn-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo htmlspecialchars($cert['file_path']); ?>" class="btn btn-success" download title="Download">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
