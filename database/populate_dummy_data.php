<?php
/**
 * KYA Food Production - Complete Dummy Data Population Script
 * Populates all tables with realistic sample data for demonstration
 */

require_once 'setup.php';

echo "<h1>KYA Food Production - Dummy Data Population</h1>";
echo "<p>This script will populate the entire system with realistic sample data...</p>";

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Enable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    echo "<h2>1. Clearing Existing Data...</h2>";
    
    // Clear tables in correct order (respecting foreign keys)
    $tables = [
        'notifications', 'activity_log', 'order_items', 'orders', 'processing_logs',
        'temperature_logs', 'receiving_records', 'storage_locations', 'inventory',
        'customers', 'suppliers', 'users', 'system_settings'
    ];
    
    foreach ($tables as $table) {
        try {
            $conn->exec("DELETE FROM $table");
            echo "✓ Cleared $table<br>";
        } catch (Exception $e) {
            echo "⚠ Could not clear $table: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h2>2. Creating Users...</h2>";
    
    // Create users with different roles
    $users = [
        ['admin', 'admin123', 'System Administrator', 'admin', 'admin@kyafood.com', '+1234567890', 'Administrator'],
        ['section1_mgr', 'section1123', 'Raw Materials Manager', 'section1', 'rawmaterials@kyafood.com', '+1234567891', 'Section 1 Manager'],
        ['section2_mgr', 'section2123', 'Processing Manager', 'section2', 'processing@kyafood.com', '+1234567892', 'Section 2 Manager'],
        ['section3_mgr', 'section3123', 'Packaging Manager', 'section3', 'packaging@kyafood.com', '+1234567893', 'Section 3 Manager'],
        ['quality_mgr', 'quality123', 'Quality Control Manager', 'section4', 'quality@kyafood.com', '+1234567894', 'Quality Manager'],
        ['warehouse_mgr', 'warehouse123', 'Warehouse Manager', 'section5', 'warehouse@kyafood.com', '+1234567895', 'Warehouse Manager'],
        ['export_mgr', 'export123', 'Export Manager', 'section6', 'export@kyafood.com', '+1234567896', 'Export Manager'],
        ['operator1', 'op123', 'Machine Operator 1', 'section2', 'operator1@kyafood.com', '+1234567897', 'Operator'],
        ['operator2', 'op123', 'Machine Operator 2', 'section2', 'operator2@kyafood.com', '+1234567898', 'Operator'],
        ['qc_inspector1', 'qc123', 'QC Inspector 1', 'section4', 'qc1@kyafood.com', '+1234567899', 'QC Inspector'],
        ['qc_inspector2', 'qc123', 'QC Inspector 2', 'section4', 'qc2@kyafood.com', '+1234567900', 'QC Inspector'],
        ['worker1', 'worker123', 'Production Worker 1', 'section2', 'worker1@kyafood.com', '+1234567901', 'Worker'],
        ['worker2', 'worker123', 'Production Worker 2', 'section2', 'worker2@kyafood.com', '+1234567902', 'Worker']
    ];
    
    foreach ($users as $userData) {
        $hashedPassword = password_hash($userData[1], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO users (username, password, full_name, role, email, phone, department, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userData[0], $hashedPassword, $userData[2], $userData[3], 
            $userData[4], $userData[5], $userData[6]
        ]);
        echo "✓ Created user: {$userData[2]} ({$userData[3]})<br>";
    }
    
    echo "<h2>3. Creating Suppliers...</h2>";
    
    $suppliers = [
        ['Fresh Farms Co', 'contact@freshfarms.com', '+12345678901', '123 Farm Road, Agricultural District'],
        ['Organic Suppliers Ltd', 'info@organicsuppliers.com', '+12345678902', '456 Organic Avenue, Green Valley'],
        ['Global Spices Inc', 'sales@globalspices.com', '+12345678903', '789 Spice Street, International Market'],
        ['Dairy Partners', 'orders@dairypartners.com', '+12345678904', '321 Milk Boulevard, Dairy District'],
        ['Grain Traders Co', 'info@graintraders.com', '+12345678905', '654 Grain Road, Agricultural Hub'],
        ['Seafood Suppliers', 'fresh@seafood.com', '+12345678906', '987 Harbor Drive, Coastal City'],
        ['Fruit Importers Ltd', 'import@fruitimporters.com', '+12345678907', '147 Fruit Lane, Import Zone'],
        ['Vegetable Wholesalers', 'wholesale@vegwholesale.com', '+12345678908', '258 Veg Market, Fresh District']
    ];
    
    foreach ($suppliers as $supplier) {
        $stmt = $conn->prepare("
            INSERT INTO suppliers (name, email, phone, address, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute($supplier);
        echo "✓ Created supplier: {$supplier[0]}<br>";
    }
    
    echo "<h2>4. Creating Customers...</h2>";
    
    $customers = [
        ['Global Food Distributors', 'orders@globalfood.com', '+12345678910', '456 Distribution Ave, Trade City', 'USA'],
        ['International Export Co', 'export@intlexport.com', '+12345678911', '789 Export Boulevard, Port City', 'UK'],
        ['Asian Food Imports', 'import@asianfood.com', '+12345678912', '321 Import Street, Asian Market', 'Japan'],
        ['European Wholesale Ltd', 'wholesale@eurowholesale.com', '+12345678913', '654 Wholesale Road, European Zone', 'Germany'],
        ['Middle East Traders', 'trade@metraders.com', '+12345678914', '987 Trade Center, Dubai', 'UAE'],
        ['African Food Corp', 'orders@africanfood.com', '+12345678915', '147 Food Street, Nairobi', 'Kenya'],
        ['South American Importers', 'import@samerica.com', '+12345678916', '258 Import Ave, Buenos Aires', 'Argentina'],
        ['Oceanic Food Distributors', 'distribute@oceanicfood.com', '+12345678917', '369 Ocean Road, Sydney', 'Australia']
    ];
    
    foreach ($customers as $customer) {
        $stmt = $conn->prepare("
            INSERT INTO customers (name, email, phone, address, export_country, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute($customer);
        echo "✓ Created customer: {$customer[0]} ({$customer[4]})<br>";
    }
    
    echo "<h2>5. Creating Storage Locations...</h2>";
    
    $storageLocations = [
        ['Storage Room A', 'dry_storage', 1000, 25, 18, 22, 60, 70, 'active'],
        ['Storage Room B', 'dry_storage', 800, 20, 18, 22, 60, 70, 'active'],
        ['Cold Storage Unit', 'cold_storage', 500, 15, 2, 8, 85, 95, 'active'],
        ['Freezer Unit', 'freezer', 300, 10, -18, -15, 80, 90, 'active'],
        ['Drying Area', 'ambient', 600, 5, 25, 30, 40, 50, 'active'],
        ['Quarantine Storage', 'controlled_atmosphere', 200, 2, 15, 20, 50, 60, 'active']
    ];
    
    foreach ($storageLocations as $location) {
        $stmt = $conn->prepare("
            INSERT INTO storage_locations (name, type, capacity, current_load, min_temp, max_temp, min_humidity, max_humidity, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute($location);
        echo "✓ Created storage location: {$location[0]}<br>";
    }
    
    echo "<h2>6. Creating Inventory Items...</h2>";
    
    // Section 1 - Raw Materials
    $section1Items = [
        ['RM001', 'Raw Wheat Grain', 'Grains', 'tons', 50, 280.50, 'A+', '2024-12-31', 1, 1, 25, 100],
        ['RM002', 'Organic Rice', 'Grains', 'tons', 30, 450.00, 'A', '2025-01-15', 1, 2, 20, 80],
        ['RM003', 'Fresh Tomatoes', 'Vegetables', 'kg', 500, 2.80, 'A+', '2024-11-30', 1, 3, 300, 600],
        ['RM004', 'Onions', 'Vegetables', 'kg', 800, 1.50, 'A', '2025-01-10', 1, 3, 400, 1000],
        ['RM005', 'Fresh Milk', 'Dairy', 'liters', 1000, 1.20, 'A+', '2024-11-25', 1, 4, 500, 2000],
        ['RM006', 'Raw Chicken', 'Meat', 'kg', 200, 8.50, 'A', '2024-11-28', 1, 6, 100, 500],
        ['RM007', 'Seafood Mix', 'Seafood', 'kg', 150, 12.00, 'A+', '2024-11-26', 1, 6, 75, 300],
        ['RM008', 'Spices Mix', 'Spices', 'kg', 100, 15.00, 'A', '2025-02-28', 1, 3, 50, 200],
        ['RM009', 'Cooking Oil', 'Oils', 'liters', 500, 3.20, 'A', '2025-03-15', 1, 1, 200, 1000],
        ['RM010', 'Sugar', 'Sweeteners', 'kg', 300, 1.80, 'A', '2025-04-30', 1, 1, 150, 600]
    ];
    
    // Section 2 - Processing Materials
    $section2Items = [
        ['PM001', 'Processed Wheat Flour', 'Flours', 'kg', 800, 2.50, 'A', '2025-01-31', 2, 1, 400, 1600],
        ['PM002', 'Rice Flour', 'Flours', 'kg', 400, 3.80, 'A+', '2025-02-15', 2, 2, 200, 800],
        ['PM003', 'Tomato Paste', 'Processed', 'kg', 200, 4.20, 'A', '2024-12-15', 2, 3, 100, 400],
        ['PM004', 'Processed Cheese', 'Dairy', 'kg', 150, 12.00, 'A+', '2024-12-20', 2, 4, 75, 300],
        ['PM005', 'Cooked Chicken Pieces', 'Meat', 'kg', 100, 15.00, 'A', '2024-11-30', 2, 6, 50, 200],
        ['PM006', 'Processed Seafood', 'Seafood', 'kg', 80, 18.00, 'A+', '2024-12-05', 2, 6, 40, 160],
        ['PM007', 'Seasoning Mix', 'Spices', 'kg', 120, 25.00, 'A', '2025-03-31', 2, 3, 60, 240],
        ['PM008', 'Food Additives', 'Additives', 'kg', 50, 35.00, 'A', '2025-05-31', 2, 8, 25, 100]
    ];
    
    // Section 3 - Packaging Materials
    $section3Items = [
        ['PKG001', 'Food Grade Plastic Bags', 'Packaging', 'pieces', 5000, 0.50, 'A', '2025-12-31', 3, 1, 2500, 10000],
        ['PKG002', 'Cardboard Boxes', 'Packaging', 'pieces', 3000, 1.20, 'A', '2025-12-31', 3, 1, 1500, 6000],
        ['PKG003', 'Glass Jars', 'Packaging', 'pieces', 2000, 2.50, 'A+', '2025-12-31', 3, 1, 1000, 4000],
        ['PKG004', 'Metal Cans', 'Packaging', 'pieces', 1500, 3.00, 'A', '2025-12-31', 3, 1, 750, 3000],
        ['PKG005', 'Labels and Stickers', 'Packaging', 'pieces', 10000, 0.10, 'A', '2025-12-31', 3, 1, 5000, 20000],
        ['PKG006', 'Sealing Materials', 'Packaging', 'pieces', 8000, 0.15, 'A', '2025-12-31', 3, 1, 4000, 16000]
    ];
    
    // Section 4 - Quality Control Materials
    $section4Items = [
        ['QC001', 'Testing Chemicals', 'Testing', 'kits', 50, 45.00, 'A', '2025-06-30', 4, 8, 25, 100],
        ['QC002', 'Lab Equipment', 'Equipment', 'pieces', 20, 150.00, 'A+', '2025-12-31', 4, 8, 10, 40],
        ['QC003', 'Sample Containers', 'Testing', 'pieces', 500, 2.00, 'A', '2025-12-31', 4, 8, 250, 1000],
        ['QC004', 'Quality Standards Manual', 'Documentation', 'copies', 100, 25.00, 'A', '2025-12-31', 4, 8, 50, 200]
    ];
    
    // Section 5 - Warehouse Materials
    $section5Items = [
        ['WH001', 'Pallets', 'Equipment', 'pieces', 100, 15.00, 'A', '2025-12-31', 5, 1, 50, 200],
        ['WH002', 'Forklift Fuel', 'Fuel', 'liters', 500, 1.50, 'A', '2025-03-31', 5, 1, 250, 1000],
        ['WH003', 'Cleaning Supplies', 'Supplies', 'kits', 30, 25.00, 'A', '2025-09-30', 5, 8, 15, 60],
        ['WH004', 'Safety Equipment', 'Safety', 'pieces', 50, 35.00, 'A+', '2025-12-31', 5, 8, 25, 100]
    ];
    
    // Section 6 - Export Materials
    $section6Items = [
        ['EXP001', 'Export Containers', 'Containers', 'pieces', 20, 500.00, 'A', '2025-12-31', 6, 1, 10, 40],
        ['EXP002', 'Shipping Documents', 'Documentation', 'sets', 100, 5.00, 'A', '2025-12-31', 6, 8, 50, 200],
        ['EXP003', 'Customs Forms', 'Documentation', 'sets', 200, 2.50, 'A', '2025-12-31', 6, 8, 100, 400],
        ['EXP004', 'Export Labels', 'Packaging', 'pieces', 5000, 0.20, 'A', '2025-12-31', 6, 1, 2500, 10000]
    ];
    
    $allItems = array_merge($section1Items, $section2Items, $section3Items, $section4Items, $section5Items, $section6Items);
    
    foreach ($allItems as $item) {
        $stmt = $conn->prepare("
            INSERT INTO inventory (item_code, item_name, category, unit, quantity, unit_cost, quality_grade, 
                                expiry_date, section, supplier_id, min_threshold, max_threshold, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        $stmt->execute($item);
        echo "✓ Created inventory item: {$item[1]} (Section {$item[8]})<br>";
    }
    
    echo "<h2>7. Creating Orders...</h2>";
    
    $orders = [
        ['ORD-2024-001', 'Global Food Distributors', 'orders@globalfood.com', '+12345678910', '456 Distribution Ave, Trade City', 'USA', '2024-11-01', '2024-11-30', 'pending', 'high', 15000.00, 'unpaid', 'Air Freight', ''],
        ['ORD-2024-002', 'International Export Co', 'export@intlexport.com', '+12345678911', '789 Export Boulevard, Port City', 'UK', '2024-11-05', '2024-12-15', 'processing', 'urgent', 25000.00, 'partial', 'Sea Freight', 'EXP-123456'],
        ['ORD-2024-003', 'Asian Food Imports', 'import@asianfood.com', '+12345678912', '321 Import Street, Asian Market', 'Japan', '2024-11-10', '2024-12-20', 'quality_check', 'medium', 18000.00, 'paid', 'Air Freight', ''],
        ['ORD-2024-004', 'European Wholesale Ltd', 'wholesale@eurowholesale.com', '+12345678913', '654 Wholesale Road, European Zone', 'Germany', '2024-11-15', '2025-01-10', 'packaging', 'low', 12000.00, 'unpaid', 'Sea Freight', ''],
        ['ORD-2024-005', 'Middle East Traders', 'trade@metraders.com', '+12345678914', '987 Trade Center, Dubai', 'UAE', '2024-11-20', '2024-12-25', 'ready_to_ship', 'urgent', 30000.00, 'paid', 'Air Freight', 'EXP-789012'],
        ['ORD-2024-006', 'African Food Corp', 'orders@africanfood.com', '+12345678915', '147 Food Street, Nairobi', 'Kenya', '2024-11-22', '2025-01-15', 'shipped', 'high', 22000.00, 'partial', 'Sea Freight', 'EXP-345678'],
        ['ORD-2024-007', 'South American Importers', 'import@samerica.com', '+12345678916', '258 Import Ave, Buenos Aires', 'Argentina', '2024-11-23', '2025-01-20', 'delivered', 'medium', 28000.00, 'paid', 'Air Freight', 'EXP-901234'],
        ['ORD-2024-008', 'Oceanic Food Distributors', 'distribute@oceanicfood.com', '+12345678917', '369 Ocean Road, Sydney', 'Australia', '2024-11-24', '2025-01-25', 'pending', 'high', 20000.00, 'unpaid', 'Sea Freight', '']
    ];
    
    foreach ($orders as $order) {
        $stmt = $conn->prepare("
            INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, customer_address, 
                              export_country, order_date, required_date, status, priority, total_amount, 
                              payment_status, shipping_method, tracking_number, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute($order);
        echo "✓ Created order: {$order[0]} - {$order[1]}<br>";
    }
    
    echo "<h2>8. Creating Order Items...</h2>";
    
    // Get order IDs
    $orderStmt = $conn->query("SELECT id, order_number FROM orders ORDER BY id");
    $orders = $orderStmt->fetchAll();
    
    // Get inventory items
    $invStmt = $conn->query("SELECT id, item_name, item_code, unit_cost FROM inventory ORDER BY RAND() LIMIT 20");
    $inventoryItems = $invStmt->fetchAll();
    
    foreach ($orders as $order) {
        // Add 3-6 items per order
        $numItems = rand(3, 6);
        $selectedItems = array_rand($inventoryItems, min($numItems, count($inventoryItems)));
        
        if (!is_array($selectedItems)) {
            $selectedItems = [$selectedItems];
        }
        
        foreach ($selectedItems as $index) {
            $item = $inventoryItems[$index];
            $quantity = rand(10, 500);
            $unitPrice = $item['unit_cost'] * 1.5; // Add markup
            
            $stmt = $conn->prepare("
                INSERT INTO order_items (order_id, inventory_id, quantity, unit_price, 
                                       quality_requirements, packaging_requirements, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $qualityReqs = ['Grade A quality required', 'Premium quality only', 'Standard quality acceptable'][rand(0, 2)];
            $packagingReqs = ['Export quality packaging', 'Standard packaging', 'Premium packaging required'][rand(0, 2)];
            
            $stmt->execute([
                $order['id'], 
                $item['id'], 
                $quantity, 
                $unitPrice,
                $qualityReqs,
                $packagingReqs
            ]);
        }
        echo "✓ Added items to order: {$order['order_number']}<br>";
    }
    
    echo "<h2>9. Creating Processing Logs...</h2>";
    
    $processTypes = ['drying', 'dehydration', 'packaging', 'quality_check', 'storage'];
    $processStatuses = ['active', 'completed', 'pending'];
    
    for ($i = 1; $i <= 50; $i++) {
        $batchId = 'BATCH-' . str_pad($i, 4, '0', STR_PAD_LEFT);
        $processType = $processTypes[array_rand($processTypes)];
        $status = $processStatuses[array_rand($processStatuses)];
        $section = rand(1, 3);
        
        $stmt = $conn->prepare("
            INSERT INTO processing_logs (batch_id, process_type, section, inventory_id, input_quantity, 
                                       output_quantity, yield_percentage, duration_minutes, temperature, 
                                       quality_grade, operator_id, supervisor_id, status, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $inputQty = rand(100, 1000);
        $outputQty = rand(80, 950);
        $yield = $inputQty > 0 ? round(($outputQty / $inputQty) * 100, 2) : 0;
        $duration = rand(30, 480);
        $temperature = rand(15, 85);
        $qualityGrade = ['A+', 'A', 'B+', 'B'][rand(0, 3)];
        $operatorId = rand(8, 13);
        $supervisorId = rand(2, 4);
        $notes = ['Standard processing', 'Quality check passed', 'Optimal conditions', 'Minor adjustments made'][rand(0, 3)];
        
        $stmt->execute([
            $batchId, $processType, $section, rand(1, 20), $inputQty, $outputQty, 
            $yield, $duration, $temperature, $qualityGrade, $operatorId, $supervisorId, 
            $status, $notes
        ]);
    }
    echo "✓ Created 50 processing logs<br>";
    
    echo "<h2>10. Creating Temperature Logs...</h2>";
    
    $locations = ['Storage Room A', 'Storage Room B', 'Cold Storage Unit', 'Freezer Unit', 'Drying Area', 'Quarantine Storage'];
    
    for ($i = 1; $i <= 100; $i++) {
        $location = $locations[array_rand($locations)];
        $temperature = rand(-20, 35);
        $humidity = rand(30, 95);
        $date = date('Y-m-d H:i:s', strtotime("-" . rand(0, 720) . " hours"));
        
        $stmt = $conn->prepare("
            INSERT INTO temperature_logs (location, temperature, humidity, recorded_by, recorded_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$location, $temperature, $humidity, rand(1, 13), $date]);
    }
    echo "✓ Created 100 temperature logs<br>";
    
    echo "<h2>11. Creating Receiving Records...</h2>";
    
    for ($i = 1; $i <= 30; $i++) {
        $supplierId = rand(1, 8);
        $inventoryId = rand(1, 20);
        $quantity = rand(50, 500);
        $qualityGrade = ['A+', 'A', 'B+', 'B'][rand(0, 3)];
        $status = ['pending', 'approved', 'rejected'][rand(0, 2)];
        $date = date('Y-m-d H:i:s', strtotime("-" . rand(0, 168) . " hours"));
        
        $stmt = $conn->prepare("
            INSERT INTO receiving_records (supplier_id, inventory_id, quantity_received, quality_grade, 
                                         temperature, humidity, batch_number, expiry_date, status, 
                                         received_by, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $batchNumber = 'BATCH-' . str_pad($i, 4, '0', STR_PAD_LEFT);
        $expiryDate = date('Y-m-d', strtotime("+6 months"));
        $receivedBy = rand(1, 13);
        $notes = ['Quality check passed', 'Standard quality', 'Premium grade', 'Needs inspection'][rand(0, 3)];
        
        $stmt->execute([
            $supplierId, $inventoryId, $quantity, $qualityGrade, rand(15, 25), rand(60, 80),
            $batchNumber, $expiryDate, $status, $receivedBy, $notes, $date
        ]);
    }
    echo "✓ Created 30 receiving records<br>";
    
    echo "<h2>12. Creating Notifications...</h2>";
    
    $notificationTypes = ['inventory', 'quality', 'production', 'system', 'alert'];
    $notificationCategories = ['Info', 'Warning', 'Error', 'Success'];
    $priorities = ['low', 'medium', 'high', 'urgent'];
    
    $messages = [
        'Low stock alert for inventory item',
        'Quality inspection completed successfully',
        'Production batch completed',
        'System maintenance scheduled',
        'Temperature alert in storage',
        'Order requires attention',
        'Supplier delivery arrived',
        'Quality check failed',
        'Processing equipment maintenance',
        'Export documentation ready'
    ];
    
    for ($i = 1; $i <= 50; $i++) {
        $type = $notificationTypes[array_rand($notificationTypes)];
        $category = $notificationCategories[array_rand($notificationCategories)];
        $priority = $priorities[array_rand($priorities)];
        $userId = rand(1, 13);
        $message = $messages[array_rand($messages)];
        $isRead = rand(0, 1);
        $date = date('Y-m-d H:i:s', strtotime("-" . rand(0, 168) . " hours"));
        
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, type, category, priority, title, message, is_read, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$userId, $type, $category, $priority, ucfirst($type) . ' Notification', $message, $isRead, $date]);
    }
    echo "✓ Created 50 notifications<br>";
    
    echo "<h2>13. Creating Activity Logs...</h2>";
    
    $activities = [
        'user_login', 'user_logout', 'order_created', 'order_updated', 'inventory_added',
        'inventory_updated', 'processing_started', 'processing_completed', 'quality_check',
        'system_backup', 'export_generated', 'report_generated', 'item_allocated', 'item_fulfilled'
    ];
    
    for ($i = 1; $i <= 100; $i++) {
        $activity = $activities[array_rand($activities)];
        $userId = rand(1, 13);
        $description = ucfirst(str_replace('_', ' ', $activity)) . ' operation completed';
        $date = date('Y-m-d H:i:s', strtotime("-" . rand(0, 720) . " hours"));
        
        $stmt = $conn->prepare("
            INSERT INTO activity_log (user_id, activity_type, description, created_at)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([$userId, $activity, $description, $date]);
    }
    echo "✓ Created 100 activity logs<br>";
    
    echo "<h2>14. Creating System Settings...</h2>";
    
    $settings = [
        ['company_name', 'KYA Food Production', 'Company Name'],
        ['company_email', 'info@kyafood.com', 'Company Email'],
        ['company_phone', '+12345678900', 'Company Phone'],
        ['company_address', '123 Food Production Road, Industrial Zone', 'Company Address'],
        ['default_currency', 'USD', 'Default Currency'],
        ['low_stock_threshold', '20', 'Low Stock Threshold (%)'],
        ['expiry_warning_days', '30', 'Expiry Warning Days'],
        ['backup_frequency', 'daily', 'Backup Frequency'],
        ['max_login_attempts', '3', 'Max Login Attempts'],
        ['session_timeout', '3600', 'Session Timeout (seconds)'],
        ['email_notifications', '1', 'Email Notifications Enabled'],
        ['auto_refresh_interval', '300', 'Auto Refresh Interval (seconds)']
    ];
    
    foreach ($settings as $setting) {
        $stmt = $conn->prepare("
            INSERT INTO system_settings (setting_key, setting_value, description, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute($setting);
    }
    echo "✓ Created system settings<br>";
    
    // Re-enable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<h2>✅ Dummy Data Population Complete!</h2>";
    echo "<h3>Summary:</h3>";
    echo "<ul>";
    echo "<li>✓ 13 Users with different roles</li>";
    echo "<li>✓ 8 Suppliers</li>";
    echo "<li>✓ 8 Customers</li>";
    echo "<li>✓ 6 Storage Locations</li>";
    echo "<li>✓ 28 Inventory Items across all sections</li>";
    echo "<li>✓ 8 Orders with complete details</li>";
    echo "<li>✓ Order Items for all orders</li>";
    echo "<li>✓ 50 Processing Logs</li>";
    echo "<li>✓ 100 Temperature Logs</li>";
    echo "<li>✓ 30 Receiving Records</li>";
    echo "<li>✓ 50 Notifications</li>";
    echo "<li>✓ 100 Activity Logs</li>";
    echo "<li>✓ 12 System Settings</li>";
    echo "</ul>";
    
    echo "<h3>Login Credentials:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Username</th><th>Password</th><th>Role</th></tr>";
    echo "<tr><td>admin</td><td>admin123</td><td>Administrator</td></tr>";
    echo "<tr><td>section1_mgr</td><td>section1123</td><td>Raw Materials Manager</td></tr>";
    echo "<tr><td>section2_mgr</td><td>section2123</td><td>Processing Manager</td></tr>";
    echo "<tr><td>section3_mgr</td><td>section3123</td><td>Packaging Manager</td></tr>";
    echo "<tr><td>quality_mgr</td><td>quality123</td><td>Quality Control Manager</td></tr>";
    echo "</table>";
    
    echo "<p><strong>The system is now fully populated with realistic data!</strong></p>";
    echo "<p>You can now login with any of the above credentials to explore the system.</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
