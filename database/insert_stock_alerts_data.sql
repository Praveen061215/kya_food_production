-- =====================================================
-- KYA Food Production - Stock Alerts Dummy Data
-- Inventory items with various alert statuses
-- =====================================================

USE kya_food_production;

-- Insert inventory items with different alert statuses
-- Critical alerts (quantity <= critical_level)
INSERT INTO inventory (
    item_code, item_name, description, category, section, 
    quantity, unit, reorder_level, critical_level, 
    unit_price, supplier, location, 
    alert_status, alert_acknowledged, status, 
    created_by, created_at, updated_at
) VALUES
-- Critical Stock Items
('RAW-001', 'Premium Turmeric Powder', 'High-grade turmeric powder for spice blends', 'Raw Materials', 1, 
 5, 'kg', 50, 10, 450.00, 'Kerala Spice Traders', 'Section 1 - Storage Room A', 
 'critical', 0, 'active', 1, DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()),

('RAW-002', 'Organic Coriander Seeds', 'Certified organic coriander seeds', 'Raw Materials', 1, 
 8, 'kg', 40, 15, 280.00, 'Organic Farms Ltd', 'Section 1 - Storage Room A', 
 'critical', 0, 'active', 1, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()),

('PKG-001', 'Food Grade Plastic Bags (500g)', 'Vacuum-sealed packaging bags', 'Packaging', 2, 
 150, 'pieces', 1000, 200, 2.50, 'PackMaster Industries', 'Section 2 - Packaging Area', 
 'critical', 0, 'active', 1, DATE_SUB(NOW(), INTERVAL 3 DAY), NOW()),

('RAW-003', 'Red Chili Powder', 'Premium quality red chili powder', 'Raw Materials', 1, 
 12, 'kg', 60, 20, 520.00, 'Spice Merchants Co', 'Section 1 - Storage Room B', 
 'critical', 1, 'active', 1, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- Low Stock Items
('RAW-004', 'Black Pepper Whole', 'Premium black peppercorns', 'Raw Materials', 1, 
 35, 'kg', 80, 20, 680.00, 'Kerala Spice Traders', 'Section 1 - Storage Room A', 
 'low_stock', 0, 'active', 1, DATE_SUB(NOW(), INTERVAL 4 DAY), NOW()),

('RAW-005', 'Cumin Seeds', 'Whole cumin seeds for grinding', 'Raw Materials', 1, 
 45, 'kg', 100, 30, 380.00, 'Rajasthan Spices', 'Section 1 - Storage Room B', 
 'low_stock', 0, 'active', 1, DATE_SUB(NOW(), INTERVAL 3 DAY), NOW()),

('PKG-002', 'Printed Labels (1kg)', 'Product labels with nutritional info', 'Packaging', 2, 
 800, 'pieces', 2000, 500, 1.20, 'PrintPro Solutions', 'Section 2 - Packaging Area', 
 'low_stock', 0, 'active', 1, DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()),

('RAW-006', 'Cardamom Pods', 'Green cardamom pods', 'Raw Materials', 1, 
 18, 'kg', 30, 10, 1200.00, 'Hill Spice Traders', 'Section 1 - Cold Storage', 
 'low_stock', 1, 'active', 1, DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),

('PKG-003', 'Carton Boxes (5kg)', 'Corrugated shipping boxes', 'Packaging', 2, 
 450, 'pieces', 1000, 200, 15.00, 'BoxMart Ltd', 'Section 2 - Storage', 
 'low_stock', 0, 'active', 1, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()),

-- Items with expiry warnings (expiring within 7 days)
('RAW-007', 'Dried Curry Leaves', 'Sun-dried curry leaves', 'Raw Materials', 1, 
 25, 'kg', 50, 15, 180.00, 'South India Herbs', 'Section 1 - Drying Area', 
 'low_stock', 0, 'active', 1, DATE_SUB(NOW(), INTERVAL 30 DAY), NOW()),

('RAW-008', 'Fenugreek Seeds', 'Whole fenugreek seeds', 'Raw Materials', 1, 
 8, 'kg', 40, 12, 220.00, 'Organic Farms Ltd', 'Section 1 - Storage Room A', 
 'critical', 0, 'active', 1, DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()),

-- Normal stock items (for comparison)
('RAW-009', 'Fennel Seeds', 'Premium fennel seeds', 'Raw Materials', 1, 
 120, 'kg', 80, 20, 320.00, 'Spice Merchants Co', 'Section 1 - Storage Room B', 
 'normal', 0, 'active', 1, DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),

('RAW-010', 'Mustard Seeds', 'Yellow mustard seeds', 'Raw Materials', 1, 
 95, 'kg', 60, 15, 180.00, 'Rajasthan Spices', 'Section 1 - Storage Room A', 
 'normal', 0, 'active', 1, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),

('PKG-004', 'Sealing Tape', 'Heavy-duty packaging tape', 'Packaging', 2, 
 85, 'rolls', 50, 10, 45.00, 'PackMaster Industries', 'Section 2 - Packaging Area', 
 'normal', 0, 'active', 1, DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Update some items with expiry dates
UPDATE inventory SET expiry_date = DATE_ADD(CURDATE(), INTERVAL 5 DAY) WHERE item_code = 'RAW-007';
UPDATE inventory SET expiry_date = DATE_ADD(CURDATE(), INTERVAL 3 DAY) WHERE item_code = 'RAW-008';
UPDATE inventory SET expiry_date = DATE_ADD(CURDATE(), INTERVAL 30 DAY) WHERE item_code = 'RAW-009';
UPDATE inventory SET expiry_date = DATE_ADD(CURDATE(), INTERVAL 60 DAY) WHERE item_code = 'RAW-010';

-- Set acknowledged_by for acknowledged alerts
UPDATE inventory 
SET alert_acknowledged_by = 1, alert_acknowledged_at = DATE_SUB(NOW(), INTERVAL 1 DAY)
WHERE alert_acknowledged = 1;

-- Insert some inventory logs for these items
INSERT INTO inventory_logs (inventory_id, action, old_quantity, new_quantity, notes, created_by, created_at)
SELECT id, 'stock_update', quantity + 50, quantity, 'Stock depleted due to production', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)
FROM inventory WHERE item_code IN ('RAW-001', 'RAW-002', 'PKG-001');

INSERT INTO inventory_logs (inventory_id, action, old_quantity, new_quantity, notes, created_by, created_at)
SELECT id, 'alert_triggered', quantity, quantity, 'Critical stock level reached', 1, DATE_SUB(NOW(), INTERVAL 6 HOUR)
FROM inventory WHERE alert_status = 'critical' AND alert_acknowledged = 0;

-- Summary
SELECT 'Stock alerts dummy data inserted successfully!' as Status;

SELECT 
    alert_status,
    COUNT(*) as count,
    SUM(CASE WHEN alert_acknowledged = 1 THEN 1 ELSE 0 END) as acknowledged,
    SUM(CASE WHEN alert_acknowledged = 0 THEN 1 ELSE 0 END) as pending
FROM inventory
WHERE alert_status != 'normal'
GROUP BY alert_status
ORDER BY FIELD(alert_status, 'critical', 'low_stock');

SELECT 'Inventory items with alerts:' as Info;
SELECT item_code, item_name, quantity, reorder_level, critical_level, alert_status, alert_acknowledged
FROM inventory
WHERE alert_status != 'normal'
ORDER BY FIELD(alert_status, 'critical', 'low_stock'), alert_acknowledged;
