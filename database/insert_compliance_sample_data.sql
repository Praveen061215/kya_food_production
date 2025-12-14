-- =====================================================
-- KYA Food Production - Compliance Sample Data
-- Sample compliance documents for testing
-- =====================================================

USE kya_food_production;

-- Insert sample compliance documents
INSERT INTO compliance_documents (
    document_type, document_name, document_number, issuing_authority,
    issue_date, expiry_date, file_path, file_name, file_size, file_type,
    section, status, notes, uploaded_by, uploaded_at
) VALUES
-- Food Safety Certificates
('certificate', 'FSSAI Food Safety Certificate', 'FSSAI-2024-001234', 'Food Safety and Standards Authority of India',
 '2024-01-15', '2025-01-14', 'uploads/compliance/certificates/fssai_cert_2024.pdf', 'fssai_cert_2024.pdf', 
 245678, 'application/pdf', 1, 'active', 'Annual food safety certification for Section 1 operations', 1, DATE_SUB(NOW(), INTERVAL 30 DAY)),

('certificate', 'ISO 22000:2018 Certificate', 'ISO-22000-2024-5678', 'International Organization for Standardization',
 '2024-03-01', '2027-02-28', 'uploads/compliance/certificates/iso22000_cert.pdf', 'iso22000_cert.pdf',
 312456, 'application/pdf', NULL, 'active', 'Food Safety Management System certification for entire facility', 1, DATE_SUB(NOW(), INTERVAL 60 DAY)),

('certificate', 'HACCP Certification', 'HACCP-2024-9876', 'National Accreditation Board',
 '2024-02-10', '2025-02-09', 'uploads/compliance/certificates/haccp_cert.pdf', 'haccp_cert.pdf',
 198765, 'application/pdf', 2, 'active', 'Hazard Analysis Critical Control Points certification for processing section', 1, DATE_SUB(NOW(), INTERVAL 45 DAY)),

-- Business Licenses
('license', 'Manufacturing License', 'MFG-LIC-2024-4321', 'State Food & Drug Administration',
 '2024-04-01', '2025-03-31', 'uploads/compliance/licenses/manufacturing_license.pdf', 'manufacturing_license.pdf',
 156789, 'application/pdf', NULL, 'active', 'General manufacturing license for food production', 1, DATE_SUB(NOW(), INTERVAL 90 DAY)),

('license', 'Trade License', 'TRADE-2024-8765', 'Municipal Corporation',
 '2024-01-01', '2024-12-31', 'uploads/compliance/licenses/trade_license.pdf', 'trade_license.pdf',
 134567, 'application/pdf', NULL, 'pending_renewal', 'Business trade license - renewal due soon', 1, DATE_SUB(NOW(), INTERVAL 120 DAY)),

-- Inspection Reports
('inspection', 'Health & Safety Inspection Report', 'HSI-2024-11-001', 'State Health Department',
 '2024-11-15', NULL, 'uploads/compliance/inspections/health_inspection_nov2024.pdf', 'health_inspection_nov2024.pdf',
 445678, 'application/pdf', NULL, 'active', 'Quarterly health and safety inspection - All clear', 1, DATE_SUB(NOW(), INTERVAL 15 DAY)),

('inspection', 'Fire Safety Inspection', 'FIRE-2024-10-002', 'Fire Department',
 '2024-10-20', '2025-10-19', 'uploads/compliance/inspections/fire_safety_oct2024.pdf', 'fire_safety_oct2024.pdf',
 223456, 'application/pdf', NULL, 'active', 'Annual fire safety compliance inspection', 1, DATE_SUB(NOW(), INTERVAL 40 DAY)),

-- Audit Reports
('audit', 'Internal Quality Audit Q3 2024', 'IQA-Q3-2024', 'Internal Quality Team',
 '2024-09-30', NULL, 'uploads/compliance/audits/internal_audit_q3_2024.pdf', 'internal_audit_q3_2024.pdf',
 567890, 'application/pdf', NULL, 'active', 'Quarterly internal quality audit report', 1, DATE_SUB(NOW(), INTERVAL 70 DAY)),

-- Permits
('permit', 'Environmental Clearance Permit', 'ENV-PERMIT-2024-3456', 'State Pollution Control Board',
 '2024-06-01', '2029-05-31', 'uploads/compliance/permits/environmental_permit.pdf', 'environmental_permit.pdf',
 289012, 'application/pdf', NULL, 'active', '5-year environmental clearance for food processing operations', 1, DATE_SUB(NOW(), INTERVAL 150 DAY)),

-- Expiring Soon (for testing alerts)
('certificate', 'Pest Control Certificate', 'PEST-2024-7890', 'Certified Pest Control Services',
 '2024-11-01', '2025-01-31', 'uploads/compliance/certificates/pest_control_cert.pdf', 'pest_control_cert.pdf',
 123456, 'application/pdf', 1, 'active', 'Quarterly pest control certification - expiring soon', 1, DATE_SUB(NOW(), INTERVAL 10 DAY)),

-- Expired (for testing)
('license', 'Water Quality License', 'WATER-2023-5432', 'Water Quality Board',
 '2023-01-01', '2024-12-01', 'uploads/compliance/licenses/water_license_2023.pdf', 'water_license_2023.pdf',
 178901, 'application/pdf', 1, 'expired', 'Water quality testing license - EXPIRED, renewal required', 1, DATE_SUB(NOW(), INTERVAL 180 DAY));

-- Summary
SELECT 'Compliance sample data inserted successfully!' as Status;

SELECT 
    document_type,
    status,
    COUNT(*) as count
FROM compliance_documents
GROUP BY document_type, status
ORDER BY document_type, status;

SELECT 'Documents expiring within 60 days:' as Alert;
SELECT 
    document_name,
    document_type,
    expiry_date,
    DATEDIFF(expiry_date, CURDATE()) as days_until_expiry
FROM compliance_documents
WHERE expiry_date IS NOT NULL 
AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
ORDER BY expiry_date;
