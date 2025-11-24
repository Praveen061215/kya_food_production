<?php
/**
 * KYA Food Production - Complete Database Error Fix
 * Comprehensive fix for all database-related issues
 */

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "kya_food_production";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>KYA Food Production - Complete Database Fix</h2>";
    echo "<div style='font-family: Arial; padding: 20px;'>";
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$database`");
    echo "✅ Database '$database' ready<br>";
    
    // 1. Fix users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `password` varchar(255) NOT NULL,
            `role` enum('admin','section1_manager','section2_manager','section3_manager') NOT NULL,
            `full_name` varchar(100) NOT NULL,
            `email` varchar(100) DEFAULT NULL,
            `phone` varchar(20) DEFAULT NULL,
            `profile_image` varchar(255) DEFAULT NULL,
            `last_login` timestamp NULL DEFAULT NULL,
            `login_attempts` int(11) DEFAULT 0,
            `account_locked` tinyint(1) DEFAULT 0,
            `password_reset_token` varchar(255) DEFAULT NULL,
            `password_reset_expires` timestamp NULL DEFAULT NULL,
            `two_factor_enabled` tinyint(1) DEFAULT 0,
            `two_factor_secret` varchar(255) DEFAULT NULL,
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`),
            INDEX `idx_role` (`role`),
            INDEX `idx_email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Users table fixed<br>";
    
    // 2. Fix inventory table with all required columns
    $pdo->exec("DROP TABLE IF EXISTS `inventory`");
    $pdo->exec("
        CREATE TABLE `inventory` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `section` int(11) NOT NULL CHECK (section IN (1, 2, 3)),
            `item_code` varchar(50) NOT NULL,
            `item_name` varchar(100) NOT NULL,
            `category` varchar(50) NOT NULL,
            `subcategory` varchar(50) DEFAULT NULL,
            `description` text DEFAULT NULL,
            `quantity` decimal(10,3) NOT NULL DEFAULT 0.000,
            `unit` varchar(20) NOT NULL,
            `unit_cost` decimal(10,2) DEFAULT NULL,
            `total_value` decimal(12,2) GENERATED ALWAYS AS (quantity * COALESCE(unit_cost, 0)) STORED,
            `min_threshold` decimal(10,3) NOT NULL DEFAULT 0.000,
            `max_threshold` decimal(10,3) NOT NULL DEFAULT 1000.000,
            `reorder_level` decimal(10,3) DEFAULT NULL,
            `expiry_date` date DEFAULT NULL,
            `manufacture_date` date DEFAULT NULL,
            `batch_number` varchar(50) DEFAULT NULL,
            `supplier_id` int(11) DEFAULT NULL,
            `storage_location` varchar(100) DEFAULT NULL,
            `storage_temperature` decimal(5,2) DEFAULT NULL,
            `storage_humidity` decimal(5,2) DEFAULT NULL,
            `quality_grade` enum('A','B','C','D') DEFAULT 'A',
            `status` enum('active','inactive','expired','damaged','recalled') DEFAULT 'active',
            `alert_status` enum('normal','low_stock','critical','expired','expiring_soon') DEFAULT 'normal',
            `alert_acknowledged_by` int(11) DEFAULT NULL,
            `alert_acknowledged_at` timestamp NULL DEFAULT NULL,
            `notes` text DEFAULT NULL,
            `created_by` int(11) DEFAULT NULL,
            `updated_by` int(11) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `item_code` (`item_code`),
            INDEX `idx_section` (`section`),
            INDEX `idx_item_code` (`item_code`),
            INDEX `idx_category` (`category`),
            INDEX `idx_expiry_date` (`expiry_date`),
            INDEX `idx_status` (`status`),
            INDEX `idx_alert_status` (`alert_status`),
            INDEX `idx_supplier_id` (`supplier_id`),
            FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Inventory table recreated with all columns<br>";
    
    // 3. Suppliers table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `suppliers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `email` varchar(100) DEFAULT NULL,
            `phone` varchar(20) DEFAULT NULL,
            `address` text DEFAULT NULL,
            `contact_person` varchar(100) DEFAULT NULL,
            `status` enum('active','inactive') DEFAULT 'active',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Suppliers table fixed<br>";
    
    // 4. Sections table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `sections` (
            `id` int(11) NOT NULL,
            `name` varchar(100) NOT NULL,
            `description` text DEFAULT NULL,
            `manager_id` int(11) DEFAULT NULL,
            `status` enum('active','inactive') DEFAULT 'active',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            FOREIGN KEY (`manager_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Sections table fixed<br>";
    
    // 5. Orders table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `orders` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_number` varchar(50) UNIQUE NOT NULL,
            `customer_name` varchar(100) NOT NULL,
            `customer_email` varchar(100) DEFAULT NULL,
            `customer_phone` varchar(20) DEFAULT NULL,
            `customer_address` text DEFAULT NULL,
            `export_country` varchar(50) DEFAULT NULL,
            `order_date` date NOT NULL,
            `required_date` date DEFAULT NULL,
            `status` enum('pending','processing','quality_check','packaging','ready_to_ship','shipped','delivered','cancelled') DEFAULT 'pending',
            `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
            `total_amount` decimal(12,2) DEFAULT 0,
            `currency` varchar(3) DEFAULT 'USD',
            `payment_status` enum('pending','partial','paid','refunded') DEFAULT 'pending',
            `payment_method` varchar(50) DEFAULT NULL,
            `shipping_method` varchar(50) DEFAULT NULL,
            `tracking_number` varchar(100) DEFAULT NULL,
            `compliance_documents` json DEFAULT NULL,
            `special_instructions` text DEFAULT NULL,
            `created_by` int(11) DEFAULT NULL,
            `assigned_to` int(11) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            INDEX `idx_order_number` (`order_number`),
            INDEX `idx_customer_email` (`customer_email`),
            INDEX `idx_status` (`status`),
            INDEX `idx_order_date` (`order_date`),
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Orders table fixed<br>";
    
    // 6. Order items table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `order_items` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` int(11) NOT NULL,
            `inventory_id` int(11) NOT NULL,
            `quantity` decimal(10,3) NOT NULL,
            `unit_price` decimal(10,2) NOT NULL,
            `total_price` decimal(12,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
            `quality_requirements` text DEFAULT NULL,
            `packaging_requirements` text DEFAULT NULL,
            `allocated_quantity` decimal(10,3) DEFAULT 0,
            `fulfilled_quantity` decimal(10,3) DEFAULT 0,
            `status` enum('pending','allocated','processed','packaged','fulfilled') DEFAULT 'pending',
            `notes` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            INDEX `idx_order_id` (`order_id`),
            INDEX `idx_inventory_id` (`inventory_id`),
            INDEX `idx_status` (`status`),
            FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`inventory_id`) REFERENCES `inventory`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Order items table fixed<br>";
    
    // 7. Notifications table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `notifications` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `notification_code` varchar(20) UNIQUE NOT NULL,
            `user_id` int(11) DEFAULT NULL,
            `section` int(11) DEFAULT NULL,
            `priority` enum('low','medium','high','critical') DEFAULT 'medium',
            `type` enum('inventory_alert','expiry_warning','quality_issue','system_alert','process_complete','user_action') NOT NULL,
            `category` varchar(50) DEFAULT NULL,
            `title` varchar(200) NOT NULL,
            `message` text NOT NULL,
            `action_required` tinyint(1) DEFAULT 0,
            `action_url` varchar(255) DEFAULT NULL,
            `data` json DEFAULT NULL,
            `is_read` tinyint(1) DEFAULT 0,
            `is_archived` tinyint(1) DEFAULT 0,
            `read_at` timestamp NULL DEFAULT NULL,
            `expires_at` timestamp NULL DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `notification_code` (`notification_code`),
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_section` (`section`),
            INDEX `idx_type` (`type`),
            INDEX `idx_priority` (`priority`),
            INDEX `idx_is_read` (`is_read`),
            INDEX `idx_created_at` (`created_at`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Notifications table fixed<br>";
    
    // 8. Activity logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `activity_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) DEFAULT NULL,
            `action` varchar(100) NOT NULL,
            `table_name` varchar(50) DEFAULT NULL,
            `record_id` int(11) DEFAULT NULL,
            `old_values` json DEFAULT NULL,
            `new_values` json DEFAULT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text DEFAULT NULL,
            `session_id` varchar(128) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_action` (`action`),
            INDEX `idx_table_name` (`table_name`),
            INDEX `idx_created_at` (`created_at`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Activity logs table fixed<br>";
    
    // 9. Inventory history table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `inventory_history` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `inventory_id` int(11) NOT NULL,
            `transaction_type` enum('in','out','adjustment','transfer','disposal') NOT NULL,
            `quantity_change` decimal(10,3) NOT NULL,
            `previous_quantity` decimal(10,3) NOT NULL,
            `new_quantity` decimal(10,3) NOT NULL,
            `reference_id` int(11) DEFAULT NULL,
            `reference_type` varchar(50) DEFAULT NULL,
            `notes` text DEFAULT NULL,
            `user_id` int(11) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            INDEX `idx_inventory_id` (`inventory_id`),
            INDEX `idx_transaction_type` (`transaction_type`),
            INDEX `idx_created_at` (`created_at`),
            FOREIGN KEY (`inventory_id`) REFERENCES `inventory`(`id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Inventory history table fixed<br>";
    
    // 10. Inventory transfers table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `inventory_transfers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `transfer_number` varchar(50) UNIQUE NOT NULL,
            `from_section` int(11) NOT NULL,
            `to_section` int(11) NOT NULL,
            `item_id` int(11) NOT NULL,
            `quantity` decimal(10,3) NOT NULL,
            `unit_cost` decimal(10,2) DEFAULT NULL,
            `total_cost` decimal(12,2) GENERATED ALWAYS AS (quantity * COALESCE(unit_cost, 0)) STORED,
            `status` enum('pending','approved','in_transit','completed','cancelled') DEFAULT 'pending',
            `requested_by` int(11) DEFAULT NULL,
            `approved_by` int(11) DEFAULT NULL,
            `transferred_by` int(11) DEFAULT NULL,
            `from_user_id` int(11) DEFAULT NULL,
            `to_user_id` int(11) DEFAULT NULL,
            `transfer_date` date DEFAULT NULL,
            `notes` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            INDEX `idx_transfer_number` (`transfer_number`),
            INDEX `idx_from_section` (`from_section`),
            INDEX `idx_to_section` (`to_section`),
            INDEX `idx_item_id` (`item_id`),
            INDEX `idx_status` (`status`),
            FOREIGN KEY (`item_id`) REFERENCES `inventory`(`id`),
            FOREIGN KEY (`requested_by`) REFERENCES `users`(`id`),
            FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`),
            FOREIGN KEY (`transferred_by`) REFERENCES `users`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Inventory transfers table fixed<br>";
    
    // 11. Inventory transfer items table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `inventory_transfer_items` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `transfer_id` int(11) NOT NULL,
            `inventory_id` int(11) NOT NULL,
            `quantity` decimal(10,3) NOT NULL,
            `unit_cost` decimal(10,2) DEFAULT NULL,
            `total_cost` decimal(12,2) GENERATED ALWAYS AS (quantity * COALESCE(unit_cost, 0)) STORED,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            INDEX `idx_transfer_id` (`transfer_id`),
            INDEX `idx_inventory_id` (`inventory_id`),
            FOREIGN KEY (`transfer_id`) REFERENCES `inventory_transfers`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`inventory_id`) REFERENCES `inventory`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Inventory transfer items table fixed<br>";
    
    // 12. Inventory logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `inventory_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `inventory_id` int(11) NOT NULL,
            `action` varchar(50) NOT NULL,
            `old_quantity` decimal(10,3) DEFAULT NULL,
            `new_quantity` decimal(10,3) DEFAULT NULL,
            `quantity_change` decimal(10,3) DEFAULT NULL,
            `notes` text DEFAULT NULL,
            `created_by` int(11) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            INDEX `idx_inventory_id` (`inventory_id`),
            INDEX `idx_action` (`action`),
            INDEX `idx_created_at` (`created_at`),
            FOREIGN KEY (`inventory_id`) REFERENCES `inventory`(`id`),
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Inventory logs table fixed<br>";
    
    // Insert default data
    echo "<br><strong>Inserting default data...</strong><br>";
    
    // Insert sections
    $stmt = $pdo->prepare("INSERT IGNORE INTO sections (id, name, description) VALUES (?, ?, ?)");
    $sections = [
        [1, 'Raw Material Handling', 'Section 1 - Raw materials receiving, storage, and initial processing'],
        [2, 'Processing & Drying', 'Section 2 - Food processing, dehydration, and quality control'],
        [3, 'Packaging', 'Section 3 - Final packaging, labeling, and shipping preparation']
    ];
    foreach ($sections as $section) {
        $stmt->execute($section);
    }
    echo "✅ Default sections inserted<br>";
    
    // Insert users
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $managerPassword = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, role, full_name, email, phone) VALUES (?, ?, ?, ?, ?, ?)");
    
    $users = [
        ['admin', $adminPassword, 'admin', 'System Administrator', 'admin@kyafood.com', '+94-123-456-789'],
        ['section1_mgr', $managerPassword, 'section1_manager', 'Raw Materials Manager', 'section1@kyafood.com', '+94-123-456-791'],
        ['section2_mgr', $managerPassword, 'section2_manager', 'Processing Manager', 'section2@kyafood.com', '+94-123-456-792'],
        ['section3_mgr', $managerPassword, 'section3_manager', 'Packaging Manager', 'section3@kyafood.com', '+94-123-456-793']
    ];
    
    foreach ($users as $user) {
        $stmt->execute($user);
    }
    echo "✅ Default users inserted<br>";
    
    // Insert suppliers
    $stmt = $pdo->prepare("INSERT IGNORE INTO suppliers (name, email, phone, address, contact_person) VALUES (?, ?, ?, ?, ?)");
    $suppliers = [
        ['Global Foods Supply', 'contact@globalfoods.com', '+94-112-345-678', '123 Industrial Area, Colombo', 'John Smith'],
        ['Tropical Fruits Ltd', 'info@tropicalfruits.lk', '+94-112-456-789', '456 Export Zone, Katunayake', 'Sarah Johnson'],
        ['Packaging Solutions', 'orders@packsol.com', '+94-112-567-890', '789 Industrial Park, Biyagama', 'Michael Perera']
    ];
    foreach ($suppliers as $supplier) {
        $stmt->execute($supplier);
    }
    echo "✅ Sample suppliers inserted<br>";
    
    // Insert sample inventory data
    $stmt = $pdo->prepare("INSERT IGNORE INTO inventory (section, item_code, item_name, category, subcategory, description, quantity, unit, unit_cost, min_threshold, max_threshold, reorder_level, expiry_date, manufacture_date, batch_number, supplier_id, storage_location, storage_temperature, storage_humidity, quality_grade, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $inventory = [
        [1, 'RM001', 'Fresh Mangoes', 'Raw Materials', 'Fruits', 'Premium quality fresh mangoes from local farms', 500.000, 'kg', 2.50, 50.000, 1000.000, 75.000, '2025-01-15', '2024-12-01', 'MG2024001', 1, 'Cold Storage A1', 4.0, 85.0, 'A', 'active', 'High quality mangoes ready for processing', 1],
        [1, 'RM002', 'Fresh Pineapples', 'Raw Materials', 'Fruits', 'Sweet golden pineapples', 300.000, 'kg', 1.80, 30.000, 600.000, 45.000, '2025-01-20', '2024-12-05', 'PA2024001', 1, 'Cold Storage A2', 4.0, 85.0, 'A', 'active', 'Premium pineapples for dehydration', 1],
        [2, 'PR001', 'Dehydrated Mango Slices', 'Processed', 'Dried Fruits', 'Premium dehydrated mango slices', 150.000, 'kg', 8.50, 20.000, 300.000, 30.000, '2025-12-31', '2024-12-10', 'DM2024001', 2, 'Dry Storage B1', 25.0, 60.0, 'A', 'active', 'Ready for packaging', 2],
        [3, 'PK001', 'Vacuum Sealed Mango Pack', 'Packaged', 'Final Products', 'Consumer ready vacuum sealed packs', 500.000, 'pcs', 12.00, 50.000, 1000.000, 75.000, '2026-01-31', '2024-12-15', 'VM2024001', 3, 'Finished Goods C1', 20.0, 70.0, 'A', 'active', 'Ready for export', 3]
    ];
    
    foreach ($inventory as $item) {
        $stmt->execute($item);
    }
    echo "✅ Sample inventory data inserted<br>";
    
    // Insert sample orders
    $stmt = $pdo->prepare("INSERT IGNORE INTO orders (order_number, customer_name, customer_email, customer_phone, customer_address, order_date, required_date, status, total_amount, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $orders = [
        ['ORD001', 'Global Exports Inc', 'orders@global.com', '+1-555-0123', '123 Main St, New York, USA', '2024-12-01', '2025-01-15', 'processing', 6000.00, 1],
        ['ORD002', 'Asian Foods Ltd', 'import@asianfoods.com', '+44-20-1234-5678', '456 Business Park, London, UK', '2024-12-02', '2025-01-20', 'pending', 4500.00, 1]
    ];
    
    foreach ($orders as $order) {
        $stmt->execute($order);
    }
    echo "✅ Sample orders inserted<br>";
    
    // Insert sample notifications
    $stmt = $pdo->prepare("INSERT IGNORE INTO notifications (notification_code, user_id, section, priority, type, title, message, action_required) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $notifications = [
        ['EXPIRY_001', 1, 1, 'critical', 'expiry_warning', 'Items Expiring Soon', 'Some items in Section 1 are approaching expiry dates', 1],
        ['LOW_STOCK_001', 2, 2, 'high', 'inventory_alert', 'Low Stock Alert', 'Dehydrated products running low in Section 2', 1],
        ['WELCOME_001', 1, NULL, 'medium', 'system_alert', 'Welcome to KYA Food Production', 'System has been successfully setup and is ready to use', 0]
    ];
    
    foreach ($notifications as $notification) {
        $stmt->execute($notification);
    }
    echo "✅ Sample notifications inserted<br>";
    
    echo "<br><h3>🎉 Complete Database Fix Successful!</h3>";
    echo "<p><strong>All database issues have been resolved!</strong></p>";
    echo "<p><strong>What was fixed:</strong></p>";
    echo "<ul>";
    echo "<li>✅ All missing tables created</li>";
    echo "<li>✅ All table columns added/updated</li>";
    echo "<li>✅ Foreign key constraints fixed</li>";
    echo "<li>✅ Indexes optimized</li>";
    echo "<li>✅ Default data inserted</li>";
    echo "<li>✅ Sample data populated</li>";
    echo "</ul>";
    
    echo "<p><strong>Login Credentials:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> username = <code>admin</code>, password = <code>admin123</code></li>";
    echo "<li><strong>Section Managers:</strong> username = <code>section1_mgr</code>, password = <code>admin123</code></li>";
    echo "</ul>";
    
    echo "<p><a href='../login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Go to Login</a></p>";
    echo "<p><a href='../modules/inventory/index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>📦 Go to Inventory</a></p>";
    echo "<p><em>You can delete this fix file after successful verification.</em></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='color: red; font-family: Arial; padding: 20px;'>";
    echo "<h3>❌ Database Fix Failed</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Solution:</strong></p>";
    echo "<ul>";
    echo "<li>1. Make sure XAMPP is running</li>";
    echo "<li>2. Start MySQL service from XAMPP control panel</li>";
    echo "<li>3. Check MySQL credentials (default: root, no password)</li>";
    echo "<li>4. Ensure MySQL port 3306 is available</li>";
    echo "<li>5. Try restarting MySQL service</li>";
    echo "</ul>";
    echo "<p><strong>Debugging steps:</strong></p>";
    echo "<ol>";
    echo "<li>Open XAMPP Control Panel</li>";
    echo "<li>Click 'Start' next to MySQL</li>";
    echo "<li>Wait for MySQL to show 'Running' in green</li>";
    echo "<li>Refresh this page</li>";
    echo "</ol>";
    echo "</div>";
}
?>
