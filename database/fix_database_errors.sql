-- Migration script to fix database schema issues
-- Run this script to update your existing database without losing data
-- Execute this in phpMyAdmin or MySQL command line

USE `kya_food_production`;

-- Fix 1: Update inventory table alert_status enum values (if column exists)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'kya_food_production' 
    AND TABLE_NAME = 'inventory' 
    AND COLUMN_NAME = 'alert_status');

SET @sql = IF(@col_exists > 0, 
    'ALTER TABLE `inventory` MODIFY COLUMN `alert_status` enum(''normal'',''low_stock'',''critical'',''expiring_soon'') DEFAULT ''normal''',
    'SELECT ''Skipping: alert_status column does not exist'' as Info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Fix 2: Update inventory_history table structure
-- Check if change_type exists (old name) and rename to transaction_type
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'kya_food_production' 
    AND TABLE_NAME = 'inventory_history' 
    AND COLUMN_NAME = 'change_type');

SET @sql = IF(@col_exists > 0, 
    'ALTER TABLE `inventory_history` CHANGE COLUMN `change_type` `transaction_type` enum(''in'',''out'',''adjustment'',''initial'') NOT NULL',
    'SELECT ''Skipping: change_type column already renamed or does not exist'' as Info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if created_by exists (old name) and rename to user_id
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'kya_food_production' 
    AND TABLE_NAME = 'inventory_history' 
    AND COLUMN_NAME = 'created_by');

SET @sql = IF(@col_exists > 0, 
    'ALTER TABLE `inventory_history` CHANGE COLUMN `created_by` `user_id` int(11) DEFAULT NULL',
    'SELECT ''Skipping: created_by column already renamed or does not exist'' as Info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key constraint for user_id if it doesn't exist
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = 'kya_food_production' 
    AND TABLE_NAME = 'inventory_history' 
    AND CONSTRAINT_NAME = 'inventory_history_ibfk_2');

SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE `inventory_history` ADD CONSTRAINT `inventory_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL',
    'SELECT ''Skipping: Foreign key inventory_history_ibfk_2 already exists'' as Info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Fix 3: Add order_date column to orders table if it doesn't exist
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'kya_food_production' 
    AND TABLE_NAME = 'orders' 
    AND COLUMN_NAME = 'order_date');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `orders` ADD COLUMN `order_date` date NOT NULL DEFAULT ''2024-01-01'' AFTER `delivery_address`',
    'SELECT ''Skipping: order_date column already exists'' as Info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Set order_date to created_at date for existing records (if column was just added)
UPDATE `orders` SET `order_date` = DATE(`created_at`) WHERE `order_date` = '2024-01-01';

-- Add index on order_date if it doesn't exist
SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'kya_food_production' 
    AND TABLE_NAME = 'orders' 
    AND INDEX_NAME = 'order_date');

SET @sql = IF(@idx_exists = 0, 
    'ALTER TABLE `orders` ADD INDEX `order_date` (`order_date`)',
    'SELECT ''Skipping: Index order_date already exists'' as Info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Fix 4: Create receiving_records table if it doesn't exist
CREATE TABLE IF NOT EXISTS `receiving_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(255) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `category` varchar(100) NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_cost` decimal(12,2) NOT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `quality_grade` enum('A+','A','B','C','D') DEFAULT 'B',
  `temperature` decimal(5,2) DEFAULT NULL,
  `humidity` decimal(5,2) DEFAULT NULL,
  `received_by` int(11) NOT NULL,
  `received_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `supplier_name` (`supplier_name`),
  KEY `item_code` (`item_code`),
  KEY `category` (`category`),
  KEY `status` (`status`),
  KEY `received_date` (`received_date`),
  KEY `received_by` (`received_by`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `receiving_records_ibfk_1` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `receiving_records_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fix 5: Create processing_logs table if it doesn't exist
CREATE TABLE IF NOT EXISTS `processing_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section` enum('1','2','3') NOT NULL,
  `batch_id` varchar(50) NOT NULL,
  `process_type` varchar(100) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `input_quantity` decimal(10,3) NOT NULL,
  `output_quantity` decimal(10,3) DEFAULT NULL,
  `yield_percentage` decimal(5,2) DEFAULT NULL,
  `start_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_time` timestamp NULL DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `operator_id` int(11) DEFAULT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `temperature` decimal(5,2) DEFAULT NULL,
  `humidity` decimal(5,2) DEFAULT NULL,
  `quality_check` enum('pass','fail','pending') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `section` (`section`),
  KEY `batch_id` (`batch_id`),
  KEY `process_type` (`process_type`),
  KEY `item_id` (`item_id`),
  KEY `operator_id` (`operator_id`),
  KEY `supervisor_id` (`supervisor_id`),
  KEY `start_time` (`start_time`),
  CONSTRAINT `processing_logs_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`id`) ON DELETE SET NULL,
  CONSTRAINT `processing_logs_ibfk_2` FOREIGN KEY (`operator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `processing_logs_ibfk_3` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verification - Show updated structures
SELECT 'Database migration completed successfully!' as Status;
SHOW COLUMNS FROM `inventory` LIKE 'alert_status';
SHOW COLUMNS FROM `inventory_history` LIKE 'transaction_type';
SHOW COLUMNS FROM `inventory_history` LIKE 'user_id';
SHOW COLUMNS FROM `orders` LIKE 'order_date';
SHOW TABLES LIKE 'receiving_records';
SHOW TABLES LIKE 'processing_logs';
