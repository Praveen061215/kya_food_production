-- =====================================================
-- KYA Food Production - Compliance Documents Table
-- Store and manage compliance documents, certificates, licenses
-- =====================================================

USE kya_food_production;

-- Create compliance_documents table
CREATE TABLE IF NOT EXISTS compliance_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_type ENUM('certificate', 'license', 'audit', 'permit', 'inspection', 'other') NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    document_number VARCHAR(100),
    issuing_authority VARCHAR(255),
    issue_date DATE,
    expiry_date DATE,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT,
    file_type VARCHAR(50),
    section INT,
    status ENUM('active', 'expired', 'pending_renewal', 'archived') DEFAULT 'active',
    notes TEXT,
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_expiry (expiry_date),
    INDEX idx_type (document_type),
    INDEX idx_status (status),
    INDEX idx_section (section)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create uploads directory structure (to be created manually)
-- uploads/compliance/certificates/
-- uploads/compliance/licenses/
-- uploads/compliance/audits/
-- uploads/compliance/permits/
-- uploads/compliance/inspections/
-- uploads/compliance/other/

SELECT 'Compliance documents table created successfully!' as Status;
