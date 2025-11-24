<?php
/**
 * KYA Food Production - Fix Receiving Records Table
 * Create the missing receiving_records table with proper structure
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
    
    echo "<h2>KYA Food Production - Fix Receiving Records Table</h2>";
    echo "<div style='font-family: Arial; padding: 20px;'>";
    
    // Use database
    $pdo->exec("USE `$database`");
    echo "✅ Connected to database '$database'<br>";
    
    // Drop existing table if it exists (to fix any structure issues)
    $pdo->exec("DROP TABLE IF EXISTS `receiving_records`");
    echo "✅ Dropped existing receiving_records table (if any)<br>";
    
    // Create receiving_records table with correct structure
    $pdo->exec("
        CREATE TABLE `receiving_records` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `supplier_name` varchar(255) NOT NULL,
            `item_name` varchar(255) NOT NULL,
            `item_code` varchar(100) NOT NULL,
            `category` varchar(100) NOT NULL,
            `quantity` decimal(10,2) NOT NULL,
            `unit` varchar(50) NOT NULL,
            `unit_cost` decimal(10,2) NOT NULL,
            `total_cost` decimal(10,2) NOT NULL,
            `batch_number` varchar(100) DEFAULT NULL,
            `expiry_date` date DEFAULT NULL,
            `quality_grade` enum('A+','A','B','C','D') DEFAULT 'B',
            `temperature` decimal(5,2) DEFAULT NULL,
            `humidity` decimal(5,2) DEFAULT NULL,
            `received_by` int(11) NOT NULL,
            `received_date` datetime NOT NULL,
            `status` enum('pending','approved','rejected') DEFAULT 'pending',
            `approved_by` int(11) DEFAULT NULL,
            `approved_date` datetime DEFAULT NULL,
            `notes` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            INDEX `idx_supplier_name` (`supplier_name`),
            INDEX `idx_item_name` (`item_name`),
            INDEX `idx_category` (`category`),
            INDEX `idx_status` (`status`),
            INDEX `idx_received_date` (`received_date`),
            INDEX `idx_received_by` (`received_by`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created receiving_records table with correct structure<br>";
    
    // Check if users table exists and get its structure
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Users table exists<br>";
        
        // Get users table structure
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "✅ Users table columns: " . implode(', ', $columns) . "<br>";
        
        // Add foreign key constraints if users table has proper structure
        if (in_array('id', $columns)) {
            $pdo->exec("
                ALTER TABLE `receiving_records`
                ADD CONSTRAINT `fk_receiving_received_by` 
                FOREIGN KEY (`received_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ");
            echo "✅ Added foreign key constraint for received_by<br>";
            
            $pdo->exec("
                ALTER TABLE `receiving_records`
                ADD CONSTRAINT `fk_receiving_approved_by` 
                FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
            ");
            echo "✅ Added foreign key constraint for approved_by<br>";
        }
    } else {
        echo "⚠️ Users table not found - skipping foreign key constraints<br>";
    }
    
    // Insert sample data for testing
    echo "<br><strong>Inserting sample receiving records...</strong><br>";
    
    // Get user IDs for sample data
    $sampleUserId = 1; // Default to admin user
    
    $stmt = $pdo->prepare("INSERT INTO receiving_records (
        supplier_name, item_name, item_code, category, quantity, unit, 
        unit_cost, total_cost, batch_number, expiry_date, quality_grade, 
        temperature, humidity, received_by, received_date, status, notes
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $sampleRecords = [
        [
            'Global Foods Supply', 'Fresh Mangoes', 'RM0001', 'Raw Materials', 
            500.00, 'kg', 2.50, 1250.00, 'MG20241224001', '2024-12-31', 
            'A+', 4.0, 85.0, $sampleUserId, '2024-12-24 10:30:00', 
            'pending', 'Premium quality mangoes from local farms'
        ],
        [
            'Tropical Fruits Ltd', 'Green Chilies', 'SP0001', 'Spices', 
            100.00, 'kg', 8.00, 800.00, 'CH20241224001', '2025-06-30', 
            'A', 25.0, 60.0, $sampleUserId, '2024-12-24 11:15:00', 
            'pending', 'Fresh green chilies with good color'
        ],
        [
            'Organic Farms Co', 'Organic Rice', 'GR0001', 'Grains', 
            1000.00, 'kg', 3.20, 3200.00, 'RC20241224001', '2025-12-31', 
            'A+', 20.0, 50.0, $sampleUserId, '2024-12-24 14:20:00', 
            'approved', 'Premium organic basmati rice'
        ],
        [
            'Fresh Produce Ltd', 'Fresh Tomatoes', 'VE0001', 'Vegetables', 
            200.00, 'kg', 1.80, 360.00, 'TM20241224001', '2024-12-29', 
            'A', 8.0, 90.0, $sampleUserId, '2024-12-24 15:45:00', 
            'pending', 'Ripe tomatoes from greenhouse'
        ]
    ];
    
    foreach ($sampleRecords as $record) {
        try {
            $stmt->execute($record);
            echo "✅ Added sample record: {$record[1]}<br>";
        } catch (Exception $e) {
            echo "⚠️ Sample record already exists or error: " . $e->getMessage() . "<br>";
        }
    }
    
    // Verify table creation
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM receiving_records");
    $count = $stmt->fetch()['count'];
    echo "<br>✅ Total receiving records: $count<br>";
    
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
    
    echo "<br><h3>🎉 Receiving Records Table Fixed!</h3>";
    echo "<p><strong>What was fixed:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Created receiving_records table with correct structure</li>";
    echo "<li>✅ Added proper indexes for performance</li>";
    echo "<li>✅ Added foreign key constraints (if users table exists)</li>";
    echo "<li>✅ Inserted sample data for testing</li>";
    echo "<li>✅ Fixed column definitions and data types</li>";
    echo "</ul>";
    
    echo "<p><a href='../modules/section1/receiving.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Go to Receiving Module</a></p>";
    echo "<p><em>You can delete this fix file after successful verification.</em></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='color: red; font-family: Arial; padding: 20px;'>";
    echo "<h3>❌ Table Fix Failed</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Solution:</strong></p>";
    echo "<ul>";
    echo "<li>1. Make sure XAMPP is running</li>";
    echo "<li>2. Start MySQL service from XAMPP control panel</li>";
    echo "<li>3. Check MySQL credentials (default: root, no password)</li>";
    echo "<li>4. Ensure database 'kya_food_production' exists</li>";
    echo "<li>5. Try running the complete database fix first</li>";
    echo "</ul>";
    echo "<p><strong>Try this first:</strong></p>";
    echo "<p><a href='complete_fix.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 Run Complete Database Fix</a></p>";
    echo "</div>";
}
?>
