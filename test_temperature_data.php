<?php
/**
 * Test Temperature Data - Debug Script
 */

require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>Temperature Data Debug</h2>";

// Test 1: Check total records
echo "<h3>Test 1: Total Records</h3>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM temperature_logs");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total records in temperature_logs: " . $result['total'] . "<br>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Test 2: Check Section 1 records
echo "<h3>Test 2: Section 1 Records</h3>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM temperature_logs WHERE section = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Section 1 records: " . $result['total'] . "<br>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Test 3: Show all rooms
echo "<h3>Test 3: All Rooms in Section 1</h3>";
try {
    $stmt = $conn->query("SELECT DISTINCT room_id, room_name FROM temperature_logs WHERE section = 1 ORDER BY room_name");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($rooms);
    echo "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Test 4: Latest reading per room (same query as temperature_monitor.php)
echo "<h3>Test 4: Latest Reading Per Room (Main Query)</h3>";
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
    echo "Error: " . $e->getMessage() . "<br>";
}

// Test 5: Sample of recent records
echo "<h3>Test 5: Recent Records (Last 10)</h3>";
try {
    $stmt = $conn->query("SELECT * FROM temperature_logs WHERE section = 1 ORDER BY recorded_at DESC LIMIT 10");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($records);
    echo "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><a href='modules/section1/temperature_monitor.php'>Go to Temperature Monitor</a></p>";
?>
