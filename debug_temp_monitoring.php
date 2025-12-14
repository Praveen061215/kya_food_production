<?php
/**
 * Debug Temperature Monitoring Data
 */

require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>Temperature Monitoring Debug</h2>";
echo "<hr>";

// Test 1: Check if table exists and has data
echo "<h3>1. Table Check</h3>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM temperature_logs WHERE section = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Total Section 1 records: <strong>" . $result['total'] . "</strong><br>";
    
    if ($result['total'] == 0) {
        echo "<div style='color: red; padding: 10px; background: #fee; margin: 10px 0;'>";
        echo "❌ <strong>NO DATA FOUND!</strong> You need to insert temperature data first.";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 2: Check current readings query
echo "<h3>2. Current Readings Query</h3>";
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
    $currentReadings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found <strong>" . count($currentReadings) . "</strong> current readings:<br>";
    if (count($currentReadings) > 0) {
        echo "<table border='1' cellpadding='5' style='margin-top: 10px;'>";
        echo "<tr><th>Room ID</th><th>Room Name</th><th>Temp</th><th>Humidity</th><th>Recorded At</th><th>Status</th></tr>";
        foreach ($currentReadings as $r) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($r['room_id']) . "</td>";
            echo "<td>" . htmlspecialchars($r['room_name']) . "</td>";
            echo "<td>" . number_format($r['temperature'], 1) . "°C</td>";
            echo "<td>" . number_format($r['humidity'], 1) . "%</td>";
            echo "<td>" . $r['recorded_at'] . "</td>";
            echo "<td>" . $r['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='color: red; padding: 10px; background: #fee; margin: 10px 0;'>";
        echo "❌ No current readings found!";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 3: Check hourly trends (last 24 hours)
echo "<h3>3. Hourly Trends (Last 24 Hours)</h3>";
try {
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(recorded_at, '%Y-%m-%d %H:00:00') as hour,
            room_id,
            room_name,
            AVG(temperature) as avg_temp,
            AVG(humidity) as avg_humidity,
            COUNT(*) as reading_count
        FROM temperature_logs
        WHERE section = 1 
        AND recorded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY DATE_FORMAT(recorded_at, '%Y-%m-%d %H:00:00'), room_id, room_name
        ORDER BY hour DESC, room_name
        LIMIT 20
    ");
    $stmt->execute();
    $trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found <strong>" . count($trends) . "</strong> hourly trend records:<br>";
    if (count($trends) > 0) {
        echo "<table border='1' cellpadding='5' style='margin-top: 10px;'>";
        echo "<tr><th>Hour</th><th>Room</th><th>Avg Temp</th><th>Avg Humidity</th><th>Readings</th></tr>";
        foreach ($trends as $t) {
            echo "<tr>";
            echo "<td>" . $t['hour'] . "</td>";
            echo "<td>" . htmlspecialchars($t['room_name']) . "</td>";
            echo "<td>" . number_format($t['avg_temp'], 1) . "°C</td>";
            echo "<td>" . number_format($t['avg_humidity'], 1) . "%</td>";
            echo "<td>" . $t['reading_count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='color: orange; padding: 10px; background: #ffeaa7; margin: 10px 0;'>";
        echo "⚠️ No data in last 24 hours. Charts will be empty.";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 4: Statistics
echo "<h3>4. Statistics</h3>";
try {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_readings,
            AVG(temperature) as avg_temp,
            MIN(temperature) as min_temp,
            MAX(temperature) as max_temp,
            AVG(humidity) as avg_humidity,
            MIN(humidity) as min_humidity,
            MAX(humidity) as max_humidity,
            COUNT(CASE WHEN alert_triggered = 1 THEN 1 END) as alert_count,
            COUNT(DISTINCT room_id) as room_count
        FROM temperature_logs
        WHERE section = 1
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    echo "<li>Total Readings: <strong>" . $stats['total_readings'] . "</strong></li>";
    echo "<li>Rooms: <strong>" . $stats['room_count'] . "</strong></li>";
    echo "<li>Avg Temperature: <strong>" . number_format($stats['avg_temp'], 1) . "°C</strong></li>";
    echo "<li>Temp Range: <strong>" . number_format($stats['min_temp'], 1) . "°C - " . number_format($stats['max_temp'], 1) . "°C</strong></li>";
    echo "<li>Avg Humidity: <strong>" . number_format($stats['avg_humidity'], 1) . "%</strong></li>";
    echo "<li>Alerts: <strong>" . $stats['alert_count'] . "</strong></li>";
    echo "</ul>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 5: Sample recent records
echo "<h3>5. Recent Records (Last 10)</h3>";
try {
    $stmt = $conn->query("
        SELECT room_id, room_name, temperature, humidity, recorded_at, status 
        FROM temperature_logs 
        WHERE section = 1 
        ORDER BY recorded_at DESC 
        LIMIT 10
    ");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($records) > 0) {
        echo "<table border='1' cellpadding='5' style='margin-top: 10px;'>";
        echo "<tr><th>Room</th><th>Temp</th><th>Humidity</th><th>Recorded At</th><th>Status</th></tr>";
        foreach ($records as $r) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($r['room_name']) . "</td>";
            echo "<td>" . number_format($r['temperature'], 1) . "°C</td>";
            echo "<td>" . number_format($r['humidity'], 1) . "%</td>";
            echo "<td>" . $r['recorded_at'] . "</td>";
            echo "<td>" . $r['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Action Required:</h3>";
echo "<ol>";
echo "<li>If NO DATA: Run the SQL insert script in phpMyAdmin</li>";
echo "<li>If data exists but page shows nothing: Check PHP error logs</li>";
echo "<li><a href='modules/section1/temperature_monitoring.php'>Go to Temperature Monitoring Page</a></li>";
echo "</ol>";
?>
