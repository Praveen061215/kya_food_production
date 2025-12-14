-- =====================================================
-- KYA Food Production - Shipping Dummy Data
-- Section 3 - Shipping and Distribution Management
-- =====================================================

USE kya_food_production;

-- First, ensure we have some inventory items for Section 3
INSERT INTO inventory (
    item_code, item_name, description, category, section,
    quantity, unit, unit_price, supplier, location,
    status, created_by, created_at, updated_at
) VALUES
('FIN-001', 'Turmeric Powder 500g Pack', 'Packaged turmeric powder ready for distribution', 'Finished Goods', 3,
 500, 'units', 85.00, 'Internal Production', 'Section 3 - Warehouse A', 
 'active', 1, DATE_SUB(NOW(), INTERVAL 10 DAY), NOW()),

('FIN-002', 'Chili Powder 250g Pack', 'Premium red chili powder packaged', 'Finished Goods', 3,
 750, 'units', 65.00, 'Internal Production', 'Section 3 - Warehouse A',
 'active', 1, DATE_SUB(NOW(), INTERVAL 8 DAY), NOW()),

('FIN-003', 'Coriander Powder 500g Pack', 'Ground coriander powder packaged', 'Finished Goods', 3,
 600, 'units', 55.00, 'Internal Production', 'Section 3 - Warehouse B',
 'active', 1, DATE_SUB(NOW(), INTERVAL 7 DAY), NOW()),

('FIN-004', 'Garam Masala 100g Pack', 'Premium spice blend packaged', 'Finished Goods', 3,
 400, 'units', 120.00, 'Internal Production', 'Section 3 - Warehouse A',
 'active', 1, DATE_SUB(NOW(), INTERVAL 5 DAY), NOW()),

('FIN-005', 'Black Pepper 200g Pack', 'Whole black pepper packaged', 'Finished Goods', 3,
 350, 'units', 180.00, 'Internal Production', 'Section 3 - Warehouse B',
 'active', 1, DATE_SUB(NOW(), INTERVAL 3 DAY), NOW())
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity);

-- Get inventory IDs for reference
SET @item1 = (SELECT id FROM inventory WHERE item_code = 'FIN-001' LIMIT 1);
SET @item2 = (SELECT id FROM inventory WHERE item_code = 'FIN-002' LIMIT 1);
SET @item3 = (SELECT id FROM inventory WHERE item_code = 'FIN-003' LIMIT 1);
SET @item4 = (SELECT id FROM inventory WHERE item_code = 'FIN-004' LIMIT 1);
SET @item5 = (SELECT id FROM inventory WHERE item_code = 'FIN-005' LIMIT 1);

-- Insert shipping records (processing_logs with process_type='shipping')

-- Delivered Shipments (completed)
INSERT INTO processing_logs (
    section, batch_id, item_id, process_type, process_stage,
    input_quantity, equipment_used, operator_id, notes,
    start_time, end_time, duration_minutes, created_at
) VALUES
-- Shipment 1 - Delivered
(3, 'S3-SHP-20251210-0001', @item1, 'shipping', 'Delivered',
 100, 'Blue Dart Express', 1, 
 'Destination: Mumbai Wholesale Market\nTracking: BD123456789IN\nDelivered: Package delivered to warehouse manager',
 DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), 2880, DATE_SUB(NOW(), INTERVAL 5 DAY)),

-- Shipment 2 - Delivered
(3, 'S3-SHP-20251211-0002', @item2, 'shipping', 'Delivered',
 150, 'DTDC Courier', 1,
 'Destination: Delhi Distribution Center\nTracking: DTDC987654321\nDelivered: Signed by recipient',
 DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), 2880, DATE_SUB(NOW(), INTERVAL 4 DAY)),

-- Shipment 3 - Delivered
(3, 'S3-SHP-20251212-0003', @item3, 'shipping', 'Delivered',
 80, 'Delhivery', 1,
 'Destination: Bangalore Retail Hub\nTracking: DLV456789123\nDelivered: Successfully delivered',
 DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), 2880, DATE_SUB(NOW(), INTERVAL 3 DAY)),

-- In Transit Shipments (active)
-- Shipment 4 - Out for Delivery
(3, 'S3-SHP-20251214-0004', @item4, 'shipping', 'Out for Delivery',
 120, 'Blue Dart Express', 1,
 'Destination: Pune Supermarket Chain\nTracking: BD789456123IN\n\nUpdate: Package out for final delivery',
 DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- Shipment 5 - At Hub
(3, 'S3-SHP-20251214-0005', @item5, 'shipping', 'At Hub',
 90, 'FedEx', 1,
 'Destination: Hyderabad Distribution Center\nTracking: FDX321654987\n\nUpdate: Arrived at regional hub',
 DATE_SUB(NOW(), INTERVAL 18 HOUR), NULL, NULL, DATE_SUB(NOW(), INTERVAL 18 HOUR)),

-- Shipment 6 - In Transit
(3, 'S3-SHP-20251214-0006', @item1, 'shipping', 'In Transit',
 75, 'DTDC Courier', 1,
 'Destination: Chennai Wholesale Market\nTracking: DTDC555666777\n\nUpdate: Package in transit to destination city',
 DATE_SUB(NOW(), INTERVAL 12 HOUR), NULL, NULL, DATE_SUB(NOW(), INTERVAL 12 HOUR)),

-- Shipment 7 - Dispatched (just started)
(3, 'S3-SHP-20251215-0007', @item2, 'shipping', 'Dispatched',
 200, 'Blue Dart Express', 1,
 'Destination: Kolkata Distribution Hub\nTracking: BD111222333IN',
 DATE_SUB(NOW(), INTERVAL 4 HOUR), NULL, NULL, DATE_SUB(NOW(), INTERVAL 4 HOUR)),

-- Shipment 8 - In Transit
(3, 'S3-SHP-20251215-0008', @item3, 'shipping', 'In Transit',
 110, 'Delhivery', 1,
 'Destination: Ahmedabad Retail Chain\nTracking: DLV888999000',
 DATE_SUB(NOW(), INTERVAL 2 HOUR), NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 HOUR)),

-- Additional delivered shipments for statistics
(3, 'S3-SHP-20251208-0009', @item4, 'shipping', 'Delivered',
 95, 'FedEx', 1,
 'Destination: Jaipur Market\nTracking: FDX444555666\nDelivered: Package received',
 DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), 2880, DATE_SUB(NOW(), INTERVAL 7 DAY)),

(3, 'S3-SHP-20251209-0010', @item5, 'shipping', 'Delivered',
 130, 'Blue Dart Express', 1,
 'Destination: Lucknow Distribution\nTracking: BD777888999IN\nDelivered: Successfully delivered to warehouse',
 DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY), 2880, DATE_SUB(NOW(), INTERVAL 6 DAY));

-- Log activities for shipping
INSERT INTO activity_logs (user_id, action, description, ip_address, created_at)
SELECT 1, 'shipping', CONCAT('Shipment dispatched: ', batch_id), '127.0.0.1', start_time
FROM processing_logs
WHERE section = 3 AND process_type = 'shipping'
LIMIT 10;

-- Summary
SELECT 'Shipping dummy data inserted successfully!' as Status;

SELECT 
    process_stage,
    COUNT(*) as count,
    SUM(input_quantity) as total_quantity
FROM processing_logs
WHERE section = 3 AND process_type = 'shipping'
GROUP BY process_stage
ORDER BY FIELD(process_stage, 'Dispatched', 'In Transit', 'At Hub', 'Out for Delivery', 'Delivered');

SELECT 'Recent Shipments:' as Info;
SELECT 
    batch_id,
    (SELECT item_name FROM inventory WHERE id = item_id) as item,
    input_quantity as quantity,
    equipment_used as carrier,
    process_stage as status,
    start_time,
    end_time
FROM processing_logs
WHERE section = 3 AND process_type = 'shipping'
ORDER BY start_time DESC
LIMIT 10;
