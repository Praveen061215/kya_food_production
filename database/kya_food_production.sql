-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
-- Host: 127.0.0.1
-- Generation Time: Nov 24, 2025 at 09:30 PM
-- Server version: 10.4.22-MariaDB
-- PHP Version: 8.0.13
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Create database
CREATE DATABASE IF NOT EXISTS `kya_food_production` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kya_food_production`;

-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `account_locked` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `suppliers`
DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `inventory`
DROP TABLE IF EXISTS `inventory`;
CREATE TABLE `inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section` enum('1','2','3') NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `subcategory` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(10,3) NOT NULL DEFAULT 0.000,
  `unit` varchar(20) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_value` decimal(12,2) GENERATED ALWAYS AS (`quantity` * `unit_cost`) STORED,
  `min_threshold` decimal(10,3) DEFAULT 0.000,
  `max_threshold` decimal(10,3) DEFAULT 0.000,
  `reorder_level` decimal(10,3) DEFAULT 0.000,
  `supplier_id` int(11) DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `manufacture_date` date DEFAULT NULL,
  `storage_location` varchar(100) DEFAULT NULL,
  `storage_temperature` decimal(5,2) DEFAULT NULL,
  `storage_humidity` decimal(5,2) DEFAULT NULL,
  `quality_grade` enum('A+','A','B','C','D') DEFAULT 'B',
  `status` enum('active','inactive','discontinued') DEFAULT 'active',
  `alert_status` enum('ok','warning','critical') DEFAULT 'ok',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_code` (`item_code`),
  KEY `section` (`section`),
  KEY `category` (`category`),
  KEY `supplier_id` (`supplier_id`),
  KEY `status` (`status`),
  KEY `alert_status` (`alert_status`),
  CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `sections`
DROP TABLE IF EXISTS `sections`;
CREATE TABLE `sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `manager_id` (`manager_id`),
  CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `inventory_history`
DROP TABLE IF EXISTS `inventory_history`;
CREATE TABLE `inventory_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inventory_id` int(11) NOT NULL,
  `change_type` enum('addition','deduction','adjustment','initial') NOT NULL,
  `quantity_change` decimal(10,3) NOT NULL,
  `previous_quantity` decimal(10,3) NOT NULL,
  `new_quantity` decimal(10,3) NOT NULL,
  `reference_type` enum('order','transfer','adjustment','receiving','other') DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `inventory_id` (`inventory_id`),
  KEY `reference_type` (`reference_type`,`reference_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `inventory_history_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `inventory_transfers`
DROP TABLE IF EXISTS `inventory_transfers`;
CREATE TABLE `inventory_transfers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transfer_number` varchar(50) NOT NULL,
  `from_section` enum('1','2','3') NOT NULL,
  `to_section` enum('1','2','3') NOT NULL,
  `status` enum('pending','in_transit','completed','cancelled') DEFAULT 'pending',
  `requested_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `transfer_number` (`transfer_number`),
  KEY `from_section` (`from_section`),
  KEY `to_section` (`to_section`),
  KEY `status` (`status`),
  KEY `requested_by` (`requested_by`),
  KEY `approved_by` (`approved_by`),
  KEY `completed_by` (`completed_by`),
  CONSTRAINT `inventory_transfers_ibfk_1` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
  CONSTRAINT `inventory_transfers_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `inventory_transfers_ibfk_3` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `inventory_transfer_items`
DROP TABLE IF EXISTS `inventory_transfer_items`;
CREATE TABLE `inventory_transfer_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transfer_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `status` enum('pending','in_transit','completed','cancelled') DEFAULT 'pending',
  `received_quantity` decimal(10,3) DEFAULT 0.000,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `transfer_id` (`transfer_id`),
  KEY `inventory_id` (`inventory_id`),
  CONSTRAINT `inventory_transfer_items_ibfk_1` FOREIGN KEY (`transfer_id`) REFERENCES `inventory_transfers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_transfer_items_ibfk_2` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `inventory_logs`
DROP TABLE IF EXISTS `inventory_logs`;
CREATE TABLE `inventory_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inventory_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `inventory_id` (`inventory_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `inventory_logs_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `orders`
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `order_items`
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(12,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `quality_requirements` text DEFAULT NULL,
  `packaging_requirements` text DEFAULT NULL,
  `allocated_quantity` decimal(10,3) DEFAULT 0.000,
  `fulfilled_quantity` decimal(10,3) DEFAULT 0.000,
  `status` enum('pending','allocated','processed','packaged','fulfilled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `inventory_id` (`inventory_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `notifications`
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `system_settings`
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `category` varchar(50) DEFAULT 'general',
  `description` text DEFAULT NULL,
  `is_editable` tinyint(1) DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `temperature_logs`
DROP TABLE IF EXISTS `temperature_logs`;
CREATE TABLE `temperature_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section` enum('1','2','3') NOT NULL,
  `location` varchar(100) NOT NULL,
  `temperature` decimal(5,2) NOT NULL,
  `humidity` decimal(5,2) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `recorded_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `section` (`section`),
  KEY `location` (`location`),
  KEY `recorded_at` (`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data
LOCK TABLES `users` WRITE;
INSERT INTO `users` (`id`, `username`, `password`, `role`, `full_name`, `email`, `phone`, `profile_image`, `last_login`, `login_attempts`, `account_locked`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator', 'admin@kyafood.com', '+94-123-456-789', NULL, NULL, 0, 0, 1, '2024-11-24 16:30:00', '2024-11-24 16:30:00'),
(2, 'section1_mgr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'section1_manager', 'Section 1 Manager', 'section1@kyafood.com', '+94-123-456-791', NULL, NULL, 0, 0, 1, '2024-11-24 16:30:00', '2024-11-24 16:30:00'),
(3, 'section2_mgr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'section2_manager', 'Section 2 Manager', 'section2@kyafood.com', '+94-123-456-792', NULL, NULL, 0, 0, 1, '2024-11-24 16:30:00', '2024-11-24 16:30:00'),
(4, 'section3_mgr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'section3_manager', 'Section 3 Manager', 'section3@kyafood.com', '+94-123-456-793', NULL, NULL, 0, 0, 1, '2024-11-24 16:30:00', '2024-11-24 16:30:00');
UNLOCK TABLES;

LOCK TABLES `suppliers` WRITE;
INSERT INTO `suppliers` (`id`, `name`, `email`, `phone`, `address`, `contact_person`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Global Foods Supply', 'info@globalfoods.com', '+94-11-234-5678', '123 Food Street, Colombo', 'John Smith', 'active', '2024-11-24 16:30:00', '2024-11-24 16:30:00'),
(2, 'Tropical Fruits Ltd', 'sales@tropicalfruits.lk', '+94-11-345-6789', '456 Fruit Avenue, Kandy', 'Jane Doe', 'active', '2024-11-24 16:30:00', '2024-11-24 16:30:00'),
(3, 'Organic Farms Co', 'info@organicfarms.lk', '+94-11-456-7890', '789 Farm Road, Gampaha', 'Robert Brown', 'active', '2024-11-24 16:30:00', '2024-11-24 16:30:00'),
(4, 'Fresh Produce Ltd', 'contact@freshproduce.lk', '+94-11-567-8901', '321 Market Lane, Negombo', 'Sarah Johnson', 'active', '2024-11-24 16:30:00', '2024-11-24 16:30:00'),
(5, 'Packaging Solutions', 'sales@packagingsl.lk', '+94-11-678-9012', '159 Industrial Zone, Biyagama', 'David Wilson', 'active', '2024-11-24 16:30:00', '2024-11-24 16:30:00');
UNLOCK TABLES;

LOCK TABLES `sections` WRITE;
INSERT INTO `sections` (`id`, `name`, `description`, `manager_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Section 1 - Raw Materials', 'Handling of raw materials and initial processing', 2, 'active', '2024-11-24 16:30:00', '2024-11-24 16:30:00'),
(2, 'Section 2 - Processing', 'Main food processing and production', 3, 'active', '2024-11-24 16:30:00', '2024-11-24 16:30:00'),
(3, 'Section 3 - Packaging', 'Final packaging and quality control', 4, 'active', '2024-11-24 16:30:00', '2024-11-24 16:30:00');
UNLOCK TABLES;

LOCK TABLES `system_settings` WRITE;
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `category`, `description`, `is_editable`, `updated_by`, `updated_at`) VALUES
(1, 'site_name', 'KYA Food Production', 'string', 'general', 'The name of the website', 1, 1, '2024-11-24 16:30:00'),
(2, 'company_name', 'KYA Food Production (Pvt) Ltd', 'string', 'general', 'The name of the company', 1, 1, '2024-11-24 16:30:00'),
(3, 'site_email', 'info@kyafood.com', 'string', 'general', 'Default email address for the site', 1, 1, '2024-11-24 16:30:00'),
(4, 'items_per_page', '10', 'number', 'pagination', 'Number of items to show per page', 1, 1, '2024-11-24 16:30:00'),
(5, 'max_login_attempts', '3', 'number', 'security', 'Maximum number of failed login attempts before account is locked', 1, 1, '2024-11-24 16:30:00'),
(6, 'session_timeout', '1800', 'number', 'security', 'Session timeout in seconds (30 minutes)', 1, 1, '2024-11-24 16:30:00'),
(7, 'maintenance_mode', '0', 'boolean', 'system', 'Whether the site is in maintenance mode', 1, 1, '2024-11-24 16:30:00'),
(8, 'default_currency', 'LKR', 'string', 'general', 'Default currency symbol', 1, 1, '2024-11-24 16:30:00'),
(9, 'date_format', 'Y-m-d', 'string', 'general', 'Default date format (PHP date format)', 1, 1, '2024-11-24 16:30:00'),
(10, 'time_format', 'H:i:s', 'string', 'general', 'Default time format (PHP time format)', 1, 1, '2024-11-24 16:30:00'),
(11, 'inventory_low_stock_threshold', '10', 'number', 'inventory', 'Percentage threshold for low stock alerts', 1, 1, '2024-11-24 16:30:00'),
(12, 'inventory_critical_stock_threshold', '5', 'number', 'inventory', 'Percentage threshold for critical stock alerts', 1, 1, '2024-11-24 16:30:00');
UNLOCK TABLES;

-- Create views, procedures, and triggers here
-- (Include all your views, stored procedures, and triggers from the original file)

COMMIT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;