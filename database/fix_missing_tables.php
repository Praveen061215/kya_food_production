<?php
/**
 * KYA Food Production - Fix Missing Database Tables
 * Creates all missing tables referenced in the inventory module
 */

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "kya_food_production";

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>KYA Food Production - Fix Missing Tables</h2>";
    echo "<div style='font-family: Arial; padding: 20px;'>";
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$database`");
    echo "✅ Using database '$database'<br>";
    
    // 1. Suppliers table
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
            UNIQUE KEY `name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Suppliers table created/verified<br>";
    
    // 2. Enhanced inventory table with missing columns
    $pdo->exec("
        ALTER TABLE `inventory` 
        ADD COLUMN IF NOT EXISTS `subcategory` varchar(50) DEFAULT NULL AFTER `category`,
        ADD COLUMN IF NOT EXISTS `total_value` decimal(12,2) GENERATED ALWAYS AS (quantity * COALESCE(unit_cost, 0)) STORED AFTER `unit_cost`,
        ADD COLUMN IF NOT EXISTS `reorder_level` decimal(10,3) DEFAULT NULL AFTER `max_threshold`,
        ADD COLUMN IF NOT EXISTS `manufacture_date` date DEFAULT NULL AFTER `expiry_date`,
        ADD COLUMN IF NOT EXISTS `supplier_id` int(11) DEFAULT NULL AFTER `batch_number`,
        ADD COLUMN IF NOT EXISTS `storage_temperature` decimal(5,2) DEFAULT NULL AFTER `storage_location`,
        ADD COLUMN IF NOT EXISTS `storage_humidity` decimal(5,2) DEFAULT NULL AFTER `storage_temperature`,
        ADD COLUMN IF NOT EXISTS `notes` text DEFAULT NULL AFTER `quality_grade`,
        ADD COLUMN IF NOT EXISTS `updated_by` int(11) DEFAULT NULL AFTER `created_by`,
        ADD INDEX IF NOT EXISTS `idx_supplier_id` (`supplier_id`),
        ADD INDEX IF NOT EXISTS `idx_expiry_date` (`expiry_date`)
    ");
    echo "✅ Inventory table enhanced with missing columns<br>";
    
    // 3. Orders table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `orders` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_number` varchar(50) UNIQUE NOT NULL,
            `customer_name` varchar(100) NOT NULL,
            `customer_email` varchar(100) DEFAULT NULL,
            `customer_phone` varchar(20) DEFAULT NULL,
            `customer_address` text DEFAULT NULL,
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
            `special_instructions` text DEFAULT NULL,
            `created_by` int(11) DEFAULT NULL,
            `assigned_to` int(11) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            INDEX `idx_order_number` (`order_number`),
            INDEX `idx_customer_email` (`customer_email`),
            INDEX `idx_status` (`status`),
            INDEX `idx_order_date` (`order_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Orders table created/verified<br>";
    
    // 4. Order items table
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
    echo "✅ Order items table created/verified<br>";
    
    // 5. Inventory history table
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
    echo "✅ Inventory history table created/verified<br>";
    
    // 6. Inventory transfers table
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
    echo "✅ Inventory transfers table created/verified<br>";
    
    // 7. Inventory transfer items table
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
    echo "✅ Inventory transfer items table created/verified<br>";
    
    // 8. Sections table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `sections` (
            `id` int(11) NOT NULL,
            `name` varchar(100) NOT NULL,
            `description` text DEFAULT NULL,
            `manager_id` int(11) DEFAULT NULL,
            `status` enum('active','inactive') DEFAULT 'active',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            FOREIGN KEY (`manager_id`) REFERENCES `users`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Sections table created/verified<br>";
    
    // 9. Inventory logs table (for stock alerts)
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
    echo "✅ Inventory logs table created/verified<br>";
    
    // Insert default sections
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
    
    // Insert sample suppliers
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
    
    // Update existing inventory items with supplier references
    $pdo->exec("UPDATE inventory SET supplier_id = 1 WHERE supplier_id IS NULL AND section = 1 LIMIT 2");
    $pdo->exec("UPDATE inventory SET supplier_id = 2 WHERE supplier_id IS NULL AND section = 2 LIMIT 1");
    $pdo->exec("UPDATE inventory SET supplier_id = 3 WHERE supplier_id IS NULL AND section = 3 LIMIT 1");
    echo "✅ Inventory items updated with supplier references<br>";
    
    echo "<br><h3>🎉 Database Fix Complete!</h3>";
    echo "<p><strong>All missing tables have been created and existing tables have been enhanced.</strong></p>";
    echo "<p><strong>Tables created/updated:</strong></p>";
    echo "<ul>";
    echo "<li>✅ suppliers</li>";
    echo "<li>✅ inventory (enhanced with missing columns)</li>";
    echo "<li>✅ orders</li>";
    echo "<li>✅ order_items</li>";
    echo "<li>✅ inventory_history</li>";
    echo "<li>✅ inventory_transfers</li>";
    echo "<li>✅ inventory_transfer_items</li>";
    echo "<li>✅ sections</li>";
    echo "<li>✅ inventory_logs</li>";
    echo "</ul>";
    echo "<p><a href='../modules/inventory/index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Go to Inventory Module</a></p>";
    echo "<p><em>You can delete this fix file after successful verification.</em></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='color: red; font-family: Arial; padding: 20px;'>";
    echo "<h3>❌ Database Fix Failed</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check:</p>";
    echo "<ul>";
    echo "<li>XAMPP is running</li>";
    echo "<li>MySQL service is started</li>";
    echo "<li>Database credentials are correct</li>";
    echo "<li>Required permissions are available</li>";
    echo "</ul>";
    echo "</div>";
}
?>
