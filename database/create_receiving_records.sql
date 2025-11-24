-- KYA Food Production - Create receiving_records table
-- Run this SQL script in phpMyAdmin

-- Drop table if it exists (to ensure clean creation)
DROP TABLE IF EXISTS `receiving_records`;

-- Create receiving_records table
CREATE TABLE `receiving_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(255) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `item_code` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(50) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `batch_number` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `quality_grade` enum('A+','A','B','C','D') DEFAULT 'B',
  `temperature` decimal(5,2) DEFAULT NULL,
  `humidity` decimal(5,2) DEFAULT NULL,
  `received_by` int(11) NOT NULL DEFAULT 1,
  `received_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_received_date` (`received_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data
INSERT INTO `receiving_records` (
  `supplier_name`, `item_name`, `item_code`, `category`, `quantity`, `unit`, 
  `unit_cost`, `total_cost`, `batch_number`, `expiry_date`, `quality_grade`, 
  `temperature`, `humidity`, `received_by`, `received_date`, `status`, `notes`
) VALUES
('Global Foods Supply', 'Fresh Mangoes', 'RM0001', 'Raw Materials', 500.00, 'kg', 2.50, 1250.00, 'MG20241224001', '2024-12-31', 'A+', 4.0, 85.0, 1, '2024-12-24 10:30:00', 'pending', 'Premium quality mangoes from local farms'),
('Tropical Fruits Ltd', 'Green Chilies', 'SP0001', 'Spices', 100.00, 'kg', 8.00, 800.00, 'CH20241224001', '2025-06-30', 'A', 25.0, 60.0, 1, '2024-12-24 11:15:00', 'pending', 'Fresh green chilies with good color'),
('Organic Farms Co', 'Organic Rice', 'GR0001', 'Grains', 1000.00, 'kg', 3.20, 3200.00, 'RC20241224001', '2025-12-31', 'A+', 20.0, 50.0, 1, '2024-12-24 14:20:00', 'approved', 'Premium organic basmati rice'),
('Fresh Produce Ltd', 'Fresh Tomatoes', 'VE0001', 'Vegetables', 200.00, 'kg', 1.80, 360.00, 'TM20241224001', '2024-12-29', 'A', 8.0, 90.0, 1, '2024-12-24 15:45:00', 'pending', 'Ripe tomatoes from greenhouse'),
('Packaging Solutions', 'Plastic Bags', 'PK0001', 'Packaging', 1000.00, 'pcs', 0.50, 500.00, 'PK20241224001', '2027-12-31', 'A', 22.0, 45.0, 1, '2024-12-24 16:00:00', 'pending', 'Food grade plastic bags');

-- Verify table creation
SELECT 'Table created successfully' as status;

-- Show table structure
DESCRIBE `receiving_records`;

-- Show sample data
SELECT id, supplier_name, item_name, item_code, category, quantity, unit, status 
FROM `receiving_records` 
LIMIT 5;
