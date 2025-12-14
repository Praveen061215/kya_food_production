-- =====================================================
-- KYA Food Production - Quality Reports Dummy Data
-- Sample quality inspection data for testing reports
-- =====================================================

USE kya_food_production;

-- Insert comprehensive quality inspection data
INSERT INTO quality_inspections (
    section, batch_number, inspection_date, inspector_id,
    quality_grade, status, temperature, humidity, ph_level,
    contamination_check, visual_inspection, notes, created_at
) VALUES
-- Section 1 - Raw Materials Quality Inspections
(1, 'BATCH-S1-001', DATE_SUB(NOW(), INTERVAL 1 DAY), 1,
 'A', 'passed', 22.5, 65.0, 6.8, 'pass', 'pass', 
 'Excellent quality raw turmeric. No visible defects. Color and aroma excellent.', 
 DATE_SUB(NOW(), INTERVAL 1 DAY)),

(1, 'BATCH-S1-002', DATE_SUB(NOW(), INTERVAL 2 DAY), 1,
 'A', 'passed', 21.8, 62.0, 6.5, 'pass', 'pass',
 'Premium quality coriander seeds. Uniform size and color. Moisture content optimal.',
 DATE_SUB(NOW(), INTERVAL 2 DAY)),

(1, 'BATCH-S1-003', DATE_SUB(NOW(), INTERVAL 3 DAY), 1,
 'B', 'passed', 23.2, 68.0, 6.9, 'pass', 'pass',
 'Good quality red chili. Minor color variations acceptable. Overall good batch.',
 DATE_SUB(NOW(), INTERVAL 3 DAY)),

(1, 'BATCH-S1-004', DATE_SUB(NOW(), INTERVAL 4 DAY), 1,
 'C', 'conditional', 24.5, 72.0, 7.2, 'pass', 'conditional',
 'Black pepper batch with some moisture issues. Requires additional drying before processing.',
 DATE_SUB(NOW(), INTERVAL 4 DAY)),

(1, 'BATCH-S1-005', DATE_SUB(NOW(), INTERVAL 5 DAY), 1,
 'F', 'failed', 26.0, 78.0, 7.5, 'fail', 'fail',
 'Cumin seeds batch rejected. High moisture content and visible mold growth. Batch returned to supplier.',
 DATE_SUB(NOW(), INTERVAL 5 DAY)),

(1, 'BATCH-S1-006', DATE_SUB(NOW(), INTERVAL 6 DAY), 1,
 'A', 'passed', 22.0, 64.0, 6.7, 'pass', 'pass',
 'Excellent cardamom quality. Strong aroma, uniform green color. Premium grade.',
 DATE_SUB(NOW(), INTERVAL 6 DAY)),

(1, 'BATCH-S1-007', DATE_SUB(NOW(), INTERVAL 7 DAY), 1,
 'B', 'passed', 23.5, 66.0, 6.8, 'pass', 'pass',
 'Fenugreek seeds in good condition. Slight size variation but within acceptable limits.',
 DATE_SUB(NOW(), INTERVAL 7 DAY)),

-- Section 2 - Processing Quality Inspections
(2, 'BATCH-S2-001', DATE_SUB(NOW(), INTERVAL 1 DAY), 1,
 'A', 'passed', 28.5, 55.0, 6.5, 'pass', 'pass',
 'Grinding process excellent. Particle size uniform. No contamination detected.',
 DATE_SUB(NOW(), INTERVAL 1 DAY)),

(2, 'BATCH-S2-002', DATE_SUB(NOW(), INTERVAL 2 DAY), 1,
 'A', 'passed', 29.0, 52.0, 6.6, 'pass', 'pass',
 'Turmeric powder processing perfect. Color retention excellent. Curcumin levels optimal.',
 DATE_SUB(NOW(), INTERVAL 2 DAY)),

(2, 'BATCH-S2-003', DATE_SUB(NOW(), INTERVAL 3 DAY), 1,
 'B', 'passed', 30.2, 58.0, 6.7, 'pass', 'pass',
 'Chili powder batch good. Heat level consistent. Minor color variation in final product.',
 DATE_SUB(NOW(), INTERVAL 3 DAY)),

(2, 'BATCH-S2-004', DATE_SUB(NOW(), INTERVAL 4 DAY), 1,
 'C', 'conditional', 31.5, 62.0, 6.9, 'pass', 'conditional',
 'Coriander powder processing acceptable. Requires additional sieving for uniformity.',
 DATE_SUB(NOW(), INTERVAL 4 DAY)),

(2, 'BATCH-S2-005', DATE_SUB(NOW(), INTERVAL 5 DAY), 1,
 'A', 'passed', 28.0, 54.0, 6.4, 'pass', 'pass',
 'Garam masala blend excellent. All spices properly mixed. Aroma profile perfect.',
 DATE_SUB(NOW(), INTERVAL 5 DAY)),

(2, 'BATCH-S2-006', DATE_SUB(NOW(), INTERVAL 6 DAY), 1,
 'B', 'passed', 29.5, 56.0, 6.6, 'pass', 'pass',
 'Black pepper grinding good. Some larger particles present but within tolerance.',
 DATE_SUB(NOW(), INTERVAL 6 DAY)),

(2, 'BATCH-S2-007', DATE_SUB(NOW(), INTERVAL 8 DAY), 1,
 'F', 'failed', 32.0, 68.0, 7.0, 'fail', 'fail',
 'Metal contamination detected in batch. Equipment maintenance required. Batch discarded.',
 DATE_SUB(NOW(), INTERVAL 8 DAY)),

-- Section 3 - Packaging Quality Inspections
(3, 'BATCH-S3-001', DATE_SUB(NOW(), INTERVAL 1 DAY), 1,
 'A', 'passed', 25.0, 60.0, NULL, 'pass', 'pass',
 'Packaging quality excellent. Seals intact. Labels properly aligned. No defects.',
 DATE_SUB(NOW(), INTERVAL 1 DAY)),

(3, 'BATCH-S3-002', DATE_SUB(NOW(), INTERVAL 2 DAY), 1,
 'A', 'passed', 24.5, 58.0, NULL, 'pass', 'pass',
 'All packages meet weight specifications. Vacuum sealing perfect. Print quality excellent.',
 DATE_SUB(NOW(), INTERVAL 2 DAY)),

(3, 'BATCH-S3-003', DATE_SUB(NOW(), INTERVAL 3 DAY), 1,
 'B', 'passed', 26.0, 62.0, NULL, 'pass', 'pass',
 'Good packaging quality. Minor label misalignment on 2% of units. Acceptable.',
 DATE_SUB(NOW(), INTERVAL 3 DAY)),

(3, 'BATCH-S3-004', DATE_SUB(NOW(), INTERVAL 4 DAY), 1,
 'C', 'conditional', 27.0, 65.0, NULL, 'pass', 'conditional',
 'Some packages show weak seals. Requires re-sealing before shipment.',
 DATE_SUB(NOW(), INTERVAL 4 DAY)),

(3, 'BATCH-S3-005', DATE_SUB(NOW(), INTERVAL 5 DAY), 1,
 'A', 'passed', 24.0, 59.0, NULL, 'pass', 'pass',
 'Premium packaging batch. All quality parameters exceeded. Ready for distribution.',
 DATE_SUB(NOW(), INTERVAL 5 DAY)),

(3, 'BATCH-S3-006', DATE_SUB(NOW(), INTERVAL 6 DAY), 1,
 'B', 'passed', 25.5, 61.0, NULL, 'pass', 'pass',
 'Packaging acceptable. Weight variance within 2%. Labels clear and readable.',
 DATE_SUB(NOW(), INTERVAL 6 DAY)),

(3, 'BATCH-S3-007', DATE_SUB(NOW(), INTERVAL 7 DAY), 1,
 'F', 'failed', 28.0, 70.0, NULL, 'fail', 'fail',
 'Packaging material defective. Multiple seal failures. Batch rejected. New packaging material ordered.',
 DATE_SUB(NOW(), INTERVAL 7 DAY)),

-- Additional recent inspections for better statistics
(1, 'BATCH-S1-008', DATE_SUB(NOW(), INTERVAL 10 DAY), 1,
 'A', 'passed', 22.5, 63.0, 6.6, 'pass', 'pass',
 'Fennel seeds excellent quality. Uniform color and size.',
 DATE_SUB(NOW(), INTERVAL 10 DAY)),

(1, 'BATCH-S1-009', DATE_SUB(NOW(), INTERVAL 12 DAY), 1,
 'A', 'passed', 21.5, 61.0, 6.5, 'pass', 'pass',
 'Mustard seeds premium grade. No impurities detected.',
 DATE_SUB(NOW(), INTERVAL 12 DAY)),

(2, 'BATCH-S2-008', DATE_SUB(NOW(), INTERVAL 9 DAY), 1,
 'A', 'passed', 28.5, 53.0, 6.5, 'pass', 'pass',
 'Cumin powder processing excellent. Aroma strong and fresh.',
 DATE_SUB(NOW(), INTERVAL 9 DAY)),

(2, 'BATCH-S2-009', DATE_SUB(NOW(), INTERVAL 11 DAY), 1,
 'B', 'passed', 29.5, 57.0, 6.7, 'pass', 'pass',
 'Cardamom powder good quality. Slight moisture variation.',
 DATE_SUB(NOW(), INTERVAL 11 DAY)),

(3, 'BATCH-S3-008', DATE_SUB(NOW(), INTERVAL 8 DAY), 1,
 'A', 'passed', 24.5, 59.0, NULL, 'pass', 'pass',
 'Packaging inspection passed all criteria. Excellent batch.',
 DATE_SUB(NOW(), INTERVAL 8 DAY)),

(3, 'BATCH-S3-009', DATE_SUB(NOW(), INTERVAL 10 DAY), 1,
 'A', 'passed', 25.0, 60.0, NULL, 'pass', 'pass',
 'All packages within specifications. Ready for shipment.',
 DATE_SUB(NOW(), INTERVAL 10 DAY)),

-- Current month data for trends
(1, 'BATCH-S1-010', DATE_SUB(NOW(), INTERVAL 15 DAY), 1,
 'B', 'passed', 23.0, 65.0, 6.8, 'pass', 'pass',
 'Raw materials inspection satisfactory.',
 DATE_SUB(NOW(), INTERVAL 15 DAY)),

(2, 'BATCH-S2-010', DATE_SUB(NOW(), INTERVAL 14 DAY), 1,
 'A', 'passed', 28.0, 54.0, 6.5, 'pass', 'pass',
 'Processing quality excellent.',
 DATE_SUB(NOW(), INTERVAL 14 DAY)),

(3, 'BATCH-S3-010', DATE_SUB(NOW(), INTERVAL 13 DAY), 1,
 'A', 'passed', 24.0, 58.0, NULL, 'pass', 'pass',
 'Packaging meets all standards.',
 DATE_SUB(NOW(), INTERVAL 13 DAY));

-- Summary
SELECT 'Quality inspection dummy data inserted successfully!' as Status;

SELECT 
    section,
    quality_grade,
    status,
    COUNT(*) as count
FROM quality_inspections
GROUP BY section, quality_grade, status
ORDER BY section, quality_grade;

SELECT 'Quality Inspections Summary:' as Info;
SELECT 
    COUNT(*) as total_inspections,
    COUNT(CASE WHEN status = 'passed' THEN 1 END) as passed,
    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
    COUNT(CASE WHEN status = 'conditional' THEN 1 END) as conditional,
    ROUND(COUNT(CASE WHEN status = 'passed' THEN 1 END) * 100.0 / COUNT(*), 2) as pass_rate
FROM quality_inspections;
