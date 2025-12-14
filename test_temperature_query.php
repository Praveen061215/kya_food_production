<?php
/**
 * Test Temperature Data Query
 * Debug script to check if data can be retrieved
 */

require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>Temperature Data Debug Test</h2>";

// Test 1: Check if table exists
echo "<h3>Test 1: Table Exists?</h3>";
try {
    $result = $conn->query("SHOW TABLES LIKE 'temperature_logs'");
    if ($result->rowCount() > 0) {
        echo "✅ Table 'temperature_logs' exists<br>";
    } else {
        echo "❌ Table 'temperature_logs' does NOT exist<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 2: Check table structure
echo "<h3>Test 2: Table Structure</h3>";
try {
    $result = $conn->query("DESCRIBE temperature_logs");
    echo "<pre>";
    print_r($result->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 3: Count total records
echo "<h3>Test 3: Total Records</h3>";
try {
    $result = $conn->query("SELECT COUNT(*) as total FROM temperature_logs");
    $count = $result->fetch(PDO::FETCH_ASSOC);
    echo "Total records: " . $count['total'] . "<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 4: Count Section 1 records
echo "<h3>Test 4: Section 1 Records</h3>";
try {
    $result = $conn->query("SELECT COUNT(*) as total FROM temperature_logs WHERE section = 1");
    $count = $result->fetch(PDO::FETCH_ASSOC);
    echo "Section 1 records: " . $count['total'] . "<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 5: Get latest readings per room
echo "<h3>Test 5: Latest Readings Per Room</h3>";
try {
    $stmt = $conn->prepare("
        SELECT 
            room_id,
            room_name,
            temperature,
            humidity,
            recorded_at,
            alert_triggered,
            status
        FROM temperature_logs tl1
        WHERE section = 1
        AND recorded_at = (
            SELECT MAX(recorded_at) 
            FROM temperature_logs tl2 
            WHERE tl2.room_id = tl1.room_id 
            AND tl2.section = 1
        )
        ORDER BY room_name
    ");
    $stmt->execute();
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($rooms) . " rooms:<br>";
    echo "<pre>";
    print_r($rooms);
    echo "</pre>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 6: Show all distinct rooms
echo "<h3>Test 6: All Distinct Rooms</h3>";
try {
    $result = $conn->query("SELECT DISTINCT room_id, room_name FROM temperature_logs WHERE section = 1 ORDER BY room_name");
    echo "<pre>";
    print_r($result->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 7: Show sample records
echo "<h3>Test 7: Sample Records (Latest 10)</h3>";
try {
    $result = $conn->query("SELECT * FROM temperature_logs WHERE section = 1 ORDER BY recorded_at DESC LIMIT 10");
    echo "<pre>";
    print_r($result->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><strong>If all tests pass, the issue is in the temperature_monitor.php page logic or session/permissions.</strong></p>";
echo "<p><a href='modules/section1/temperature_monitor.php'>Go to Temperature Monitor Page</a></p>";
?>
