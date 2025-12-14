-- =====================================================
-- KYA Food Production - Dummy Data Insertion Script
-- =====================================================
-- This script inserts sample data for testing purposes
-- Includes: Users, Suppliers, Inventory, Orders, Processing Logs, Receiving Records
-- =====================================================

USE kya_food_production;

-- =====================================================
-- 1. INSERT USERS (Admin, Section Managers, Operators)
-- =====================================================
-- Password for all users: password123 (hashed)

INSERT INTO users (username, password, email, full_name, role, section, is_active, created_at) VALUES
-- Admin User
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@kyafood.com', 'Admin User', 'admin', NULL, 1, NOW()),

-- Section 1 Users (Raw Materials)
('section1_manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'section1.manager@kyafood.com', 'Rajesh Kumar', 'section1_manager', 1, 1, NOW()),
('section1_operator1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operator1.s1@kyafood.com', 'Priya Sharma', 'section1_operator', 1, 1, NOW()),
('section1_operator2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operator2.s1@kyafood.com', 'Amit Patel', 'section1_operator', 1, 1, NOW()),

-- Section 2 Users (Processing)
('section2_manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'section2.manager@kyafood.com', 'Sunita Reddy', 'section2_manager', 2, 1, NOW()),
('section2_operator1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operator1.s2@kyafood.com', 'Vikram Singh', 'section2_operator', 2, 1, NOW()),
('section2_operator2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operator2.s2@kyafood.com', 'Anita Desai', 'section2_operator', 2, 1, NOW()),

-- Section 3 Users (Packaging)
('section3_manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'section3.manager@kyafood.com', 'Ramesh Gupta', 'section3_manager', 3, 1, NOW()),
('section3_operator1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operator1.s3@kyafood.com', 'Kavita Nair', 'section3_operator', 3, 1, NOW()),
('section3_operator2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operator2.s3@kyafood.com', 'Suresh Iyer', 'section3_operator', 3, 1, NOW());

-- =====================================================
-- 2. INSERT SUPPLIERS
-- =====================================================

INSERT INTO suppliers (supplier_code, supplier_name, contact_person, email, phone, address, city, state, country, status, created_at) VALUES
('SUP-001', 'Sri Lanka Spice Traders', 'Mr. Perera', 'contact@slspice.lk', '+94-11-2345678', '123 Galle Road', 'Colombo', 'Western', 'Sri Lanka', 'active', NOW()),
('SUP-002', 'Kerala Organic Farms', 'Ms. Lakshmi', 'info@keralaorganic.in', '+91-484-2567890', '45 MG Road', 'Kochi', 'Kerala', 'India', 'active', NOW()),
('SUP-003', 'Tamil Nadu Masala Co.', 'Mr. Ravi', 'sales@tnmasala.in', '+91-44-28901234', '78 Anna Salai', 'Chennai', 'Tamil Nadu', 'India', 'active', NOW()),
('SUP-004', 'Mumbai Packaging Solutions', 'Ms. Priya', 'orders@mumbaipack.in', '+91-22-26789012', '12 Industrial Estate', 'Mumbai', 'Maharashtra', 'India', 'active', NOW()),
('SUP-005', 'Bangalore Food Ingredients', 'Mr. Suresh', 'contact@bfingredients.in', '+91-80-41234567', '34 Whitefield Road', 'Bangalore', 'Karnataka', 'India', 'active', NOW());

-- =====================================================
-- 3. INSERT INVENTORY ITEMS
-- =====================================================

INSERT INTO inventory (section, item_code, item_name, category, quantity, unit, unit_cost, min_threshold, max_threshold, reorder_level, supplier_id, manufacture_date, expiry_date, batch_number, storage_location, alert_status, status, created_by, created_at) VALUES
-- Section 1 - Raw Materials
(1, 'RM-001', 'Cinnamon Sticks', 'Spices', 500.00, 'kg', 850.00, 50.00, 1000.00, 100.00, 1, '2024-11-01', '2025-11-01', 'BATCH-CIN-2024-001', 'Warehouse A-1', 'normal', 'active', 2, NOW()),
(1, 'RM-002', 'Black Pepper', 'Spices', 350.00, 'kg', 650.00, 40.00, 800.00, 80.00, 1, '2024-11-05', '2025-11-05', 'BATCH-PEP-2024-002', 'Warehouse A-2', 'normal', 'active', 2, NOW()),
(1, 'RM-003', 'Cardamom', 'Spices', 150.00, 'kg', 2500.00, 20.00, 400.00, 50.00, 2, '2024-11-10', '2025-11-10', 'BATCH-CAR-2024-003', 'Warehouse A-3', 'normal', 'active', 2, NOW()),
(1, 'RM-004', 'Turmeric Powder', 'Spices', 600.00, 'kg', 350.00, 80.00, 1200.00, 150.00, 3, '2024-11-15', '2025-11-15', 'BATCH-TUR-2024-004', 'Warehouse A-4', 'normal', 'active', 2, NOW()),
(1, 'RM-005', 'Cumin Seeds', 'Spices', 280.00, 'kg', 450.00, 50.00, 600.00, 100.00, 2, '2024-11-20', '2025-11-20', 'BATCH-CUM-2024-005', 'Warehouse A-5', 'normal', 'active', 2, NOW()),
(1, 'RM-006', 'Cloves', 'Spices', 120.00, 'kg', 1800.00, 15.00, 300.00, 40.00, 1, '2024-12-01', '2025-12-01', 'BATCH-CLO-2024-006', 'Warehouse A-6', 'normal', 'active', 2, NOW()),

-- Section 2 - Processed Items
(2, 'PR-001', 'Cinnamon Powder', 'Processed Spices', 450.00, 'kg', 950.00, 50.00, 900.00, 100.00, NULL, '2024-12-05', '2025-12-05', 'BATCH-CINP-2024-001', 'Processing Area B-1', 'normal', 'active', 5, NOW()),
(2, 'PR-002', 'Mixed Spice Blend', 'Processed Spices', 320.00, 'kg', 750.00, 40.00, 700.00, 80.00, NULL, '2024-12-08', '2025-12-08', 'BATCH-MIX-2024-002', 'Processing Area B-2', 'normal', 'active', 5, NOW()),
(2, 'PR-003', 'Curry Powder Premium', 'Processed Spices', 280.00, 'kg', 680.00, 35.00, 600.00, 70.00, NULL, '2024-12-10', '2025-12-10', 'BATCH-CUR-2024-003', 'Processing Area B-3', 'normal', 'active', 5, NOW()),
(2, 'PR-004', 'Garam Masala', 'Processed Spices', 200.00, 'kg', 820.00, 30.00, 500.00, 60.00, NULL, '2024-12-12', '2025-12-12', 'BATCH-GAR-2024-004', 'Processing Area B-4', 'normal', 'active', 5, NOW()),

-- Section 3 - Packaged Products
(3, 'PKG-001', 'Cinnamon Powder 100g Pack', 'Packaged Products', 5000.00, 'units', 45.00, 500.00, 10000.00, 1000.00, NULL, '2024-12-13', '2025-12-13', 'BATCH-PKG-2024-001', 'Packaging Area C-1', 'normal', 'active', 8, NOW()),
(3, 'PKG-002', 'Mixed Spice Blend 250g Pack', 'Packaged Products', 3500.00, 'units', 85.00, 400.00, 8000.00, 800.00, NULL, '2024-12-13', '2025-12-13', 'BATCH-PKG-2024-002', 'Packaging Area C-2', 'normal', 'active', 8, NOW()),
(3, 'PKG-003', 'Curry Powder 200g Pack', 'Packaged Products', 4200.00, 'units', 65.00, 450.00, 9000.00, 900.00, NULL, '2024-12-13', '2025-12-13', 'BATCH-PKG-2024-003', 'Packaging Area C-3', 'normal', 'active', 8, NOW()),
(3, 'PKG-004', 'Garam Masala 150g Pack', 'Packaged Products', 2800.00, 'units', 75.00, 350.00, 6000.00, 700.00, NULL, '2024-12-13', '2025-12-13', 'BATCH-PKG-2024-004', 'Packaging Area C-4', 'normal', 'active', 8, NOW());

-- =====================================================
-- 4. INSERT RECEIVING RECORDS (Section 1)
-- =====================================================

INSERT INTO receiving_records (supplier_name, item_name, item_code, category, quantity, unit, unit_cost, total_cost, batch_number, expiry_date, quality_grade, temperature, humidity, received_by, received_date, status, notes, approved_by, approved_date) VALUES
('Sri Lanka Spice Traders', 'Cinnamon Sticks', 'RM-001', 'Spices', 500.00, 'kg', 850.00, 425000.00, 'BATCH-CIN-2024-001', '2025-11-01', 'A', 24.5, 55.0, 2, '2024-11-01 09:30:00', 'approved', 'Good quality, no damage', 2, '2024-11-01 10:00:00'),
('Sri Lanka Spice Traders', 'Black Pepper', 'RM-002', 'Spices', 350.00, 'kg', 650.00, 227500.00, 'BATCH-PEP-2024-002', '2025-11-05', 'A', 23.8, 52.0, 3, '2024-11-05 10:15:00', 'approved', 'Premium quality', 2, '2024-11-05 11:00:00'),
('Kerala Organic Farms', 'Cardamom', 'RM-003', 'Spices', 150.00, 'kg', 2500.00, 375000.00, 'BATCH-CAR-2024-003', '2025-11-10', 'A+', 22.5, 48.0, 4, '2024-11-10 08:45:00', 'approved', 'Organic certified', 2, '2024-11-10 09:30:00'),
('Tamil Nadu Masala Co.', 'Turmeric Powder', 'RM-004', 'Spices', 600.00, 'kg', 350.00, 210000.00, 'BATCH-TUR-2024-004', '2025-11-15', 'A', 25.0, 50.0, 3, '2024-11-15 11:20:00', 'approved', 'Bright color, good aroma', 2, '2024-11-15 12:00:00'),
('Kerala Organic Farms', 'Cumin Seeds', 'RM-005', 'Spices', 280.00, 'kg', 450.00, 126000.00, 'BATCH-CUM-2024-005', '2025-11-20', 'A', 24.0, 53.0, 4, '2024-11-20 09:00:00', 'approved', 'Fresh stock', 2, '2024-11-20 10:00:00'),
('Sri Lanka Spice Traders', 'Cloves', 'RM-006', 'Spices', 120.00, 'kg', 1800.00, 216000.00, 'BATCH-CLO-2024-006', '2025-12-01', 'A', 23.5, 51.0, 3, '2024-12-01 10:30:00', 'approved', 'High oil content', 2, '2024-12-01 11:15:00');

-- =====================================================
-- 5. INSERT PROCESSING LOGS (Section 2)
-- =====================================================

INSERT INTO processing_logs (section, batch_id, process_type, item_id, input_quantity, output_quantity, yield_percentage, start_time, end_time, duration_minutes, operator_id, supervisor_id, temperature, humidity, quality_check, notes, created_at) VALUES
-- Cinnamon Processing
(2, 'BATCH-S2-20241205-001', 'Grinding', 1, 500.00, 450.00, 90.00, '2024-12-05 08:00:00', '2024-12-05 12:00:00', 240, 6, 5, 26.5, 45.0, 'pass', 'Grinding completed successfully, fine powder achieved', '2024-12-05 12:00:00'),

-- Mixed Spice Processing
(2, 'BATCH-S2-20241208-002', 'Mixing', 2, 350.00, 320.00, 91.43, '2024-12-08 09:00:00', '2024-12-08 13:30:00', 270, 7, 5, 25.0, 48.0, 'pass', 'Uniform mixing, good blend consistency', '2024-12-08 13:30:00'),

-- Curry Powder Processing
(2, 'BATCH-S2-20241210-003', 'Mixing', 3, 300.00, 280.00, 93.33, '2024-12-10 10:00:00', '2024-12-10 14:00:00', 240, 6, 5, 24.5, 50.0, 'pass', 'Premium quality curry blend', '2024-12-10 14:00:00'),

-- Garam Masala Processing
(2, 'BATCH-S2-20241212-004', 'Grinding', 4, 220.00, 200.00, 90.91, '2024-12-12 08:30:00', '2024-12-12 12:30:00', 240, 7, 5, 26.0, 47.0, 'pass', 'Aromatic blend, good texture', '2024-12-12 12:30:00'),

-- Quality Check Process
(2, 'BATCH-S2-20241213-005', 'Quality Check', 7, 450.00, 450.00, 100.00, '2024-12-13 09:00:00', '2024-12-13 10:00:00', 60, 6, 5, 25.5, 49.0, 'pass', 'All quality parameters met', '2024-12-13 10:00:00');

-- =====================================================
-- 6. INSERT ORDERS
-- =====================================================

INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, delivery_address, order_date, status, total_amount, notes, created_by, created_at) VALUES
('ORD-20241201-1001', 'Spice World Retail', 'orders@spiceworld.com', '+91-80-12345678', '123 MG Road, Bangalore, Karnataka 560001', '2024-12-01', 'delivered', 125000.00, 'Regular customer, priority delivery', 1, '2024-12-01 10:00:00'),
('ORD-20241205-1002', 'Gourmet Foods Ltd', 'purchase@gourmetfoods.in', '+91-22-87654321', '456 Linking Road, Mumbai, Maharashtra 400050', '2024-12-05', 'shipped', 185000.00, 'Export quality required', 1, '2024-12-05 11:30:00'),
('ORD-20241208-1003', 'Fresh Mart Supermarket', 'orders@freshmart.in', '+91-44-23456789', '789 Anna Salai, Chennai, Tamil Nadu 600002', '2024-12-08', 'processing', 95000.00, 'Weekly order', 1, '2024-12-08 09:15:00'),
('ORD-20241210-1004', 'Organic Store Chain', 'supply@organicstore.in', '+91-484-34567890', '321 Marine Drive, Kochi, Kerala 682031', '2024-12-10', 'pending', 145000.00, 'Organic certification required', 1, '2024-12-10 14:20:00'),
('ORD-20241212-1005', 'Hotel Taj Group', 'procurement@tajhotels.com', '+91-11-45678901', '567 Connaught Place, New Delhi 110001', '2024-12-12', 'pending', 220000.00, 'Bulk order for hotel chain', 1, '2024-12-12 16:45:00');

-- =====================================================
-- 7. INSERT ORDER ITEMS
-- =====================================================

INSERT INTO order_items (order_id, item_id, quantity, unit_price, total_price, notes) VALUES
-- Order 1 items
(1, 11, 1000, 45.00, 45000.00, '100g packs'),
(1, 12, 500, 85.00, 42500.00, '250g packs'),
(1, 13, 500, 65.00, 32500.00, '200g packs'),

-- Order 2 items
(2, 11, 1500, 45.00, 67500.00, 'Premium packaging'),
(2, 13, 800, 65.00, 52000.00, 'Export quality'),
(2, 14, 800, 75.00, 60000.00, 'Special blend'),

-- Order 3 items
(3, 12, 600, 85.00, 51000.00, 'Standard packaging'),
(3, 13, 600, 65.00, 39000.00, 'Regular stock'),

-- Order 4 items
(4, 11, 1200, 45.00, 54000.00, 'Organic certified'),
(4, 12, 700, 85.00, 59500.00, 'Organic certified'),
(4, 14, 400, 75.00, 30000.00, 'Organic certified'),

-- Order 5 items
(5, 11, 2000, 45.00, 90000.00, 'Bulk order'),
(5, 12, 800, 85.00, 68000.00, 'Bulk order'),
(5, 13, 900, 65.00, 58500.00, 'Bulk order');

-- =====================================================
-- 8. INSERT INVENTORY HISTORY
-- =====================================================

INSERT INTO inventory_history (item_id, transaction_type, quantity_change, quantity_before, quantity_after, reference_type, reference_id, user_id, notes, created_at) VALUES
-- Receiving transactions
(1, 'received', 500.00, 0.00, 500.00, 'receiving', 1, 2, 'Initial stock received', '2024-11-01 10:00:00'),
(2, 'received', 350.00, 0.00, 350.00, 'receiving', 2, 3, 'Initial stock received', '2024-11-05 11:00:00'),
(3, 'received', 150.00, 0.00, 150.00, 'receiving', 3, 4, 'Initial stock received', '2024-11-10 09:30:00'),

-- Processing transactions
(1, 'used', -500.00, 500.00, 0.00, 'processing', 1, 6, 'Used for grinding', '2024-12-05 12:00:00'),
(7, 'produced', 450.00, 0.00, 450.00, 'processing', 1, 6, 'Produced from grinding', '2024-12-05 12:00:00'),

-- Order fulfillment
(11, 'sold', -1000.00, 5000.00, 4000.00, 'order', 1, 1, 'Order ORD-20241201-1001', '2024-12-01 15:00:00'),
(12, 'sold', -500.00, 3500.00, 3000.00, 'order', 1, 1, 'Order ORD-20241201-1001', '2024-12-01 15:00:00');

-- =====================================================
-- 9. INSERT ACTIVITY LOGS
-- =====================================================

INSERT INTO activity_logs (user_id, action, module, record_id, ip_address, user_agent, created_at) VALUES
(1, 'login', 'auth', NULL, '192.168.1.100', 'Mozilla/5.0', '2024-12-14 08:00:00'),
(2, 'login', 'auth', NULL, '192.168.1.101', 'Mozilla/5.0', '2024-12-14 08:15:00'),
(5, 'login', 'auth', NULL, '192.168.1.102', 'Mozilla/5.0', '2024-12-14 08:30:00'),
(8, 'login', 'auth', NULL, '192.168.1.103', 'Mozilla/5.0', '2024-12-14 08:45:00'),
(1, 'order_created', 'orders', 1, '192.168.1.100', 'Mozilla/5.0', '2024-12-01 10:00:00'),
(2, 'receiving_approved', 'receiving', 1, '192.168.1.101', 'Mozilla/5.0', '2024-11-01 10:00:00'),
(6, 'processing_completed', 'processing', 1, '192.168.1.102', 'Mozilla/5.0', '2024-12-05 12:00:00');

-- =====================================================
-- 10. INSERT NOTIFICATIONS
-- =====================================================

INSERT INTO notifications (user_id, type, title, message, is_read, created_at) VALUES
(1, 'info', 'New Order Received', 'Order ORD-20241212-1005 has been placed', 0, '2024-12-12 16:45:00'),
(2, 'warning', 'Low Stock Alert', 'Cloves (RM-006) is running low. Current: 120kg', 0, '2024-12-13 09:00:00'),
(5, 'success', 'Processing Complete', 'Batch BATCH-S2-20241213-005 quality check passed', 1, '2024-12-13 10:00:00'),
(8, 'info', 'Packaging Ready', '5000 units of PKG-001 ready for dispatch', 1, '2024-12-13 15:00:00'),
(1, 'warning', 'Pending Approvals', 'You have 2 pending receiving approvals', 0, '2024-12-14 08:00:00');

-- =====================================================
-- SUMMARY OF INSERTED DATA
-- =====================================================
-- Users: 10 (1 Admin, 3 Section Managers, 6 Operators)
-- Suppliers: 5
-- Inventory Items: 14 (6 Raw, 4 Processed, 4 Packaged)
-- Receiving Records: 6
-- Processing Logs: 5
-- Orders: 5
-- Order Items: 14
-- Inventory History: 8
-- Activity Logs: 7
-- Notifications: 5
-- =====================================================

SELECT 'Dummy data inserted successfully!' as Status;
