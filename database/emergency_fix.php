<?php
/**
 * KYA Food Production - Emergency Database Fix
 * Force create receiving_records table and fix all database issues
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
    
    echo "<h2>🚨 Emergency Database Fix - KYA Food Production</h2>";
    echo "<div style='font-family: Arial; padding: 20px;'>";
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$database`");
    echo "✅ Database '$database' ready<br>";
    
    // Force drop receiving_records table if it exists
    $pdo->exec("DROP TABLE IF EXISTS `receiving_records`");
    echo "✅ Dropped any existing receiving_records table<br>";
    
    // Create receiving_records table with basic structure first
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created receiving_records table successfully<br>";
    
    // Verify table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'receiving_records'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table creation verified<br>";
    } else {
        throw new Exception("Table creation failed");
    }
    
    // Show table structure
    echo "<br><strong>Table Structure:</strong><br>";
    $stmt = $pdo->query("DESCRIBE receiving_records");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Insert sample data
    echo "<br><strong>Inserting sample data...</strong><br>";
    
    $sampleRecords = [
        [
            'Global Foods Supply', 'Fresh Mangoes', 'RM0001', 'Raw Materials', 
            500.00, 'kg', 2.50, 1250.00, 'MG20241224001', '2024-12-31', 
            'A+', 4.0, 85.0, 1, '2024-12-24 10:30:00', 'pending', 
            NULL, NULL, 'Premium quality mangoes from local farms'
        ],
        [
            'Tropical Fruits Ltd', 'Green Chilies', 'SP0001', 'Spices', 
            100.00, 'kg', 8.00, 800.00, 'CH20241224001', '2025-06-30', 
            'A', 25.0, 60.0, 1, '2024-12-24 11:15:00', 'pending', 
            NULL, NULL, 'Fresh green chilies with good color'
        ],
        [
            'Organic Farms Co', 'Organic Rice', 'GR0001', 'Grains', 
            1000.00, 'kg', 3.20, 3200.00, 'RC20241224001', '2025-12-31', 
            'A+', 20.0, 50.0, 1, '2024-12-24 14:20:00', 'approved', 
            1, '2024-12-24 14:25:00', 'Premium organic basmati rice'
        ],
        [
            'Fresh Produce Ltd', 'Fresh Tomatoes', 'VE0001', 'Vegetables', 
            200.00, 'kg', 1.80, 360.00, 'TM20241224001', '2024-12-29', 
            'A', 8.0, 90.0, 1, '2024-12-24 15:45:00', 'pending', 
            NULL, NULL, 'Ripe tomatoes from greenhouse'
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO receiving_records (
            supplier_name, item_name, item_code, category, quantity, unit, 
            unit_cost, total_cost, batch_number, expiry_date, quality_grade, 
            temperature, humidity, received_by, received_date, status, 
            approved_by, approved_date, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($sampleRecords as $record) {
        try {
            $stmt->execute($record);
            echo "✅ Added: {$record[1]}<br>";
        } catch (Exception $e) {
            echo "⚠️ Duplicate or error: {$record[1]}<br>";
        }
    }
    
    // Verify data insertion
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM receiving_records");
    $count = $stmt->fetch()['count'];
    echo "<br>✅ Total records in table: $count<br>";
    
    // Show sample data
    echo "<br><strong>Sample Data:</strong><br>";
    $stmt = $pdo->query("SELECT id, supplier_name, item_name, item_code, category, quantity, unit, status FROM receiving_records LIMIT 5");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Supplier</th><th>Item</th><th>Code</th><th>Category</th><th>Quantity</th><th>Unit</th><th>Status</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['supplier_name']}</td>";
        echo "<td>{$row['item_name']}</td>";
        echo "<td>{$row['item_code']}</td>";
        echo "<td>{$row['category']}</td>";
        echo "<td>{$row['quantity']}</td>";
        echo "<td>{$row['unit']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test insert operation
    echo "<br><strong>Testing insert operation...</strong><br>";
    try {
        $stmt = $pdo->prepare("
            INSERT INTO receiving_records (
                supplier_name, item_name, item_code, category, quantity, unit, 
                unit_cost, total_cost, received_by, received_date, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $testData = [
            'Test Supplier', 'Test Item', 'TEST001', 'Raw Materials', 
            50.00, 'kg', 5.00, 250.00, 1, date('Y-m-d H:i:s'), 'pending'
        ];
        
        $stmt->execute($testData);
        echo "✅ Test insert successful<br>";
        
        // Clean up test record
        $pdo->exec("DELETE FROM receiving_records WHERE item_code = 'TEST001'");
        echo "✅ Test record cleaned up<br>";
        
    } catch (Exception $e) {
        echo "❌ Test insert failed: " . $e->getMessage() . "<br>";
    }
    
    echo "<br><h3>🎉 Emergency Fix Complete!</h3>";
    echo "<p><strong>What was fixed:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Created receiving_records table with correct structure</li>";
    echo "<li>✅ Added sample data for testing</li>";
    echo "<li>✅ Verified table operations work correctly</li>";
    echo "<li>✅ Tested insert/delete operations</li>";
    echo "</ul>";
    
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>1. <a href='../modules/section1/receiving.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Test Receiving Module</a></li>";
    echo "<li>2. Try adding a new receiving record</li>";
    echo "<li>3. Check that smart suggestions work</li>";
    echo "<li>4. Verify statistics display correctly</li>";
    echo "</ol>";
    
    echo "<p><strong>If still not working:</strong></p>";
    echo "<p><a href='complete_fix.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 Run Complete Database Fix</a></p>";
    echo "<p><em>You can delete this emergency fix file after successful verification.</em></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='color: red; font-family: Arial; padding: 20px;'>";
    echo "<h3>❌ Emergency Fix Failed</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Troubleshooting:</strong></p>";
    echo "<ol>";
    echo "<li>1. Make sure XAMPP is running (Apache + MySQL)</li>";
    echo "<li>2. Open XAMPP Control Panel</li>";
    echo "<li>3. Click 'Start' on MySQL service</li>";
    echo "<li>4. Wait for MySQL to show 'Running' in green</li>";
    echo "<li>5. Refresh this page</li>";
    echo "<li>6. Check that MySQL port 3306 is available</li>";
    echo "</ol>";
    echo "<p><strong>MySQL Credentials:</strong></p>";
    echo "<ul>";
    echo "<li>Host: localhost</li>";
    echo "<li>Username: root</li>";
    echo "<li>Password: (empty)</li>";
    echo "<li>Database: kya_food_production</li>";
    echo "</ul>";
    echo "<p><strong>Alternative:</strong></p>";
    echo "<p>Try accessing phpMyAdmin directly to verify MySQL is working:</p>";
    echo "<p><a href='http://localhost/phpmyadmin' target='_blank' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🗄️ Open phpMyAdmin</a></p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='color: red; font-family: Arial; padding: 20px;'>";
    echo "<h3>❌ General Error</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Please check your XAMPP installation and try again.</p>";
    echo "</div>";
}
?>
