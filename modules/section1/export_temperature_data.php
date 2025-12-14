<?php
/**
 * KYA Food Production - Export Temperature Data
 * Export temperature monitoring data to CSV format
 */

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';

SessionManager::start();
SessionManager::requireLogin();
SessionManager::requireSection(1);

$userInfo = SessionManager::getUserInfo();
$db = new Database();
$conn = $db->connect();

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$room_id = $_GET['room_id'] ?? '';
$export_format = $_GET['export'] ?? 'csv';

// Build query conditions
$whereConditions = ['section = 1'];
$params = [1];

if ($date_from) {
    $whereConditions[] = "DATE(recorded_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $whereConditions[] = "DATE(recorded_at) <= ?";
    $params[] = $date_to;
}

if ($room_id) {
    $whereConditions[] = "room_id = ?";
    $params[] = $room_id;
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

try {
    // Get temperature data
    $stmt = $conn->prepare("
        SELECT 
            room_id,
            room_name,
            temperature,
            humidity,
            recorded_at,
            alert_triggered,
            status,
            notes
        FROM temperature_logs
        $whereClause
        ORDER BY recorded_at DESC, room_name
    ");
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($data)) {
        die('No data available for export with the selected filters.');
    }
    
    // Export as CSV
    if ($export_format === 'csv') {
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="temperature_data_' . date('Y-m-d_His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 support
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add header row
        fputcsv($output, [
            'Room ID',
            'Room Name',
            'Temperature (°C)',
            'Humidity (%)',
            'Recorded At',
            'Alert Triggered',
            'Status',
            'Notes'
        ]);
        
        // Add data rows
        foreach ($data as $row) {
            fputcsv($output, [
                $row['room_id'],
                $row['room_name'],
                number_format($row['temperature'], 2),
                number_format($row['humidity'], 2),
                $row['recorded_at'],
                $row['alert_triggered'] ? 'Yes' : 'No',
                $row['status'],
                $row['notes'] ?? ''
            ]);
        }
        
        fclose($output);
        exit();
    }
    
    // Export as JSON (alternative format)
    elseif ($export_format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="temperature_data_' . date('Y-m-d_His') . '.json"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo json_encode([
            'export_date' => date('Y-m-d H:i:s'),
            'filters' => [
                'date_from' => $date_from,
                'date_to' => $date_to,
                'room_id' => $room_id ?: 'All'
            ],
            'total_records' => count($data),
            'data' => $data
        ], JSON_PRETTY_PRINT);
        exit();
    }
    
    else {
        die('Invalid export format. Supported formats: csv, json');
    }
    
} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    die('Error exporting data: ' . $e->getMessage());
}
?>
