<?php
/**
 * KYA Food Production - Upload Compliance Document
 * Upload new compliance documents, certificates, licenses
 */

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

SessionManager::start();
SessionManager::requireLogin();

if (!SessionManager::hasPermission('compliance_manage')) {
    header('Location: index.php?error=access_denied');
    exit();
}

$userInfo = SessionManager::getUserInfo();
$db = new Database();
$conn = $db->connect();

$success_message = '';
$error_message = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    try {
        $document_type = sanitizeInput($_POST['document_type']);
        $document_name = sanitizeInput($_POST['document_name']);
        $document_number = sanitizeInput($_POST['document_number']);
        $issuing_authority = sanitizeInput($_POST['issuing_authority']);
        $issue_date = $_POST['issue_date'] ?: null;
        $expiry_date = $_POST['expiry_date'] ?: null;
        $section = $_POST['section'] ?: null;
        $notes = sanitizeInput($_POST['notes']);
        
        // Handle file upload
        if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['document_file'];
            $file_name = $file['name'];
            $file_size = $file['size'];
            $file_tmp = $file['tmp_name'];
            $file_type = $file['type'];
            
            // Validate file type
            $allowed_types = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Invalid file type. Only PDF, JPG, PNG, and DOC files are allowed.');
            }
            
            // Validate file size (max 10MB)
            if ($file_size > 10 * 1024 * 1024) {
                throw new Exception('File size exceeds 10MB limit.');
            }
            
            // Create upload directory if not exists
            $upload_base = '../../uploads/compliance/';
            $upload_dir = $upload_base . $document_type . 's/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $unique_filename;
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $file_path)) {
                // Determine status based on expiry date
                $status = 'active';
                if ($expiry_date && strtotime($expiry_date) < time()) {
                    $status = 'expired';
                } elseif ($expiry_date && strtotime($expiry_date) < strtotime('+30 days')) {
                    $status = 'pending_renewal';
                }
                
                // Insert into database
                $stmt = $conn->prepare("
                    INSERT INTO compliance_documents (
                        document_type, document_name, document_number, issuing_authority,
                        issue_date, expiry_date, file_path, file_name, file_size, file_type,
                        section, status, notes, uploaded_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $document_type, $document_name, $document_number, $issuing_authority,
                    $issue_date, $expiry_date, $file_path, $file_name, $file_size, $file_type,
                    $section, $status, $notes, $userInfo['id']
                ]);
                
                logActivity('compliance_upload', "Uploaded compliance document: $document_name", $userInfo['id']);
                
                $success_message = "Document uploaded successfully!";
                
                // Redirect after 2 seconds
                header("refresh:2;url=index.php");
            } else {
                throw new Exception('Failed to upload file.');
            }
        } else {
            throw new Exception('No file uploaded or upload error occurred.');
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Compliance upload error: " . $e->getMessage());
    }
}

$pageTitle = 'Upload Compliance Document';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Upload Compliance Document</h1>
                    <p class="text-muted mb-0">Upload certificates, licenses, and compliance documents</p>
                </div>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Documents
                </a>
            </div>

            <!-- Success/Error Messages -->
            <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Upload Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-upload me-2"></i>Document Information
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="document_type" class="form-label">Document Type <span class="text-danger">*</span></label>
                                <select name="document_type" id="document_type" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <option value="certificate">Certificate</option>
                                    <option value="license">License</option>
                                    <option value="audit">Audit Report</option>
                                    <option value="permit">Permit</option>
                                    <option value="inspection">Inspection Report</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="document_name" class="form-label">Document Name <span class="text-danger">*</span></label>
                                <input type="text" name="document_name" id="document_name" class="form-control" 
                                       placeholder="e.g., FSSAI Food Safety Certificate" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="document_number" class="form-label">Document/Certificate Number</label>
                                <input type="text" name="document_number" id="document_number" class="form-control" 
                                       placeholder="e.g., FSSAI-2024-001234">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="issuing_authority" class="form-label">Issuing Authority</label>
                                <input type="text" name="issuing_authority" id="issuing_authority" class="form-control" 
                                       placeholder="e.g., Food Safety Authority">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="issue_date" class="form-label">Issue Date</label>
                                <input type="date" name="issue_date" id="issue_date" class="form-control">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="expiry_date" class="form-label">Expiry Date</label>
                                <input type="date" name="expiry_date" id="expiry_date" class="form-control">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="section" class="form-label">Section</label>
                                <select name="section" id="section" class="form-select">
                                    <option value="">All Sections</option>
                                    <option value="1">Section 1 - Raw Materials</option>
                                    <option value="2">Section 2 - Processing</option>
                                    <option value="3">Section 3 - Packaging</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="document_file" class="form-label">Upload File <span class="text-danger">*</span></label>
                            <input type="file" name="document_file" id="document_file" class="form-control" 
                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                            <div class="form-text">
                                Allowed formats: PDF, JPG, PNG, DOC, DOCX. Maximum size: 10MB
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3" 
                                      placeholder="Additional notes or comments about this document"></textarea>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>Upload Document
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Upload Guidelines -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Upload Guidelines
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>Ensure documents are clear and readable</li>
                        <li>Use PDF format for best compatibility</li>
                        <li>Include expiry dates for certificates and licenses</li>
                        <li>Add relevant notes for future reference</li>
                        <li>Keep file sizes under 10MB for faster uploads</li>
                        <li>Use descriptive names for easy identification</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
