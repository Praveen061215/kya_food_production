<?php
/**
 * KYA Food Production - Section 1 Temperature Monitoring
 * Real-time temperature and humidity monitoring for raw material storage
 */

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

SessionManager::start();
SessionManager::requireLogin();
SessionManager::requireSection(1);

$userInfo = SessionManager::getUserInfo();
$db = new Database();
$conn = $db->connect();

// Debug output
$debugMode = isset($_GET['debug']) && $_GET['debug'] == '1';

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$room_id = $_GET['room_id'] ?? '';

// Table is already created by temperature_monitor.php with room_id and room_name columns

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

// Get temperature monitoring data
try {
    // First, verify data exists
    $checkStmt = $conn->query("SELECT COUNT(*) as count FROM temperature_logs WHERE section = 1");
    $checkResult = $checkStmt->fetch();
    
    if ($debugMode) {
        echo "<div style='background: yellow; padding: 10px; margin: 10px;'>";
        echo "Total records in database for section 1: " . $checkResult['count'];
        echo "</div>";
    }
    
    // Current conditions (latest readings) - Simplified query
    $currentConditions = $conn->prepare("
        SELECT 
            tl1.room_id,
            tl1.room_name,
            tl1.temperature,
            tl1.humidity,
            tl1.recorded_at,
            tl1.alert_triggered,
            tl1.status
        FROM temperature_logs tl1
        INNER JOIN (
            SELECT room_id, MAX(recorded_at) as max_time
            FROM temperature_logs
            WHERE section = 1
            GROUP BY room_id
        ) tl2 ON tl1.room_id = tl2.room_id AND tl1.recorded_at = tl2.max_time
        WHERE tl1.section = 1
        ORDER BY tl1.room_name
    ");
    $currentConditions->execute();
    $currentReadings = $currentConditions->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistics - use simple WHERE for section only
    $statsStmt = $conn->prepare("
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
    $statsStmt->execute();
    $stats = $statsStmt->fetch();
    
    // Hourly trends for charts (last 24 hours)
    $trendsStmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(recorded_at, '%Y-%m-%d %H:00:00') as hour,
            room_id,
            room_name,
            AVG(temperature) as avg_temp,
            AVG(humidity) as avg_humidity
        FROM temperature_logs
        WHERE section = 1 
        AND recorded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY DATE_FORMAT(recorded_at, '%Y-%m-%d %H:00:00'), room_id, room_name
        ORDER BY hour, room_name
    ");
    $trendsStmt->execute();
    $hourlyTrends = $trendsStmt->fetchAll();
    
    // Recent alerts
    $alertsStmt = $conn->prepare("
        SELECT *
        FROM temperature_logs
        WHERE section = 1 
        AND alert_triggered = 1
        AND recorded_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
        ORDER BY recorded_at DESC
        LIMIT 20
    ");
    $alertsStmt->execute();
    $recentAlerts = $alertsStmt->fetchAll();
    
    // Get unique rooms for filter
    $roomsStmt = $conn->prepare("SELECT DISTINCT room_id, room_name FROM temperature_logs WHERE section = 1 ORDER BY room_name");
    $roomsStmt->execute();
    $rooms = $roomsStmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Temperature monitoring data error: " . $e->getMessage());
    $errorMessage = "Database Error: " . $e->getMessage();
    $currentReadings = [];
    $stats = ['total_readings' => 0, 'avg_temp' => 0, 'min_temp' => 0, 'max_temp' => 0, 'avg_humidity' => 0, 'min_humidity' => 0, 'max_humidity' => 0, 'alert_count' => 0, 'room_count' => 0];
    $hourlyTrends = [];
    $recentAlerts = [];
    $rooms = [];
}

// Debug output if enabled
if ($debugMode) {
    echo "<div style='background: #f0f0f0; padding: 20px; margin: 20px; border: 2px solid #333;'>";
    echo "<h3>DEBUG MODE</h3>";
    echo "<p><strong>Current Readings Count:</strong> " . count($currentReadings) . "</p>";
    echo "<p><strong>Stats:</strong> " . json_encode($stats) . "</p>";
    echo "<p><strong>Hourly Trends Count:</strong> " . count($hourlyTrends) . "</p>";
    echo "<p><strong>Rooms Count:</strong> " . count($rooms) . "</p>";
    if (isset($errorMessage)) {
        echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($errorMessage) . "</p>";
    }
    echo "<hr>";
    echo "<p><strong>Sample Current Reading:</strong></p>";
    if (!empty($currentReadings)) {
        echo "<pre>" . print_r($currentReadings[0], true) . "</pre>";
    } else {
        echo "<p style='color: red;'>No current readings found!</p>";
    }
    echo "</div>";
}

$pageTitle = 'Section 1 - Temperature Monitoring';
include '../../includes/header.php';
?>

<style>
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        transition: transform 0.2s;
    }
    .stats-card:hover {
        transform: translateY(-5px);
    }
    .stats-card .card-body {
        padding: 1.5rem;
    }
    .stats-card h3 {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
        color: white;
    }
    .stats-card p {
        margin-bottom: 0;
        opacity: 0.9;
        color: white;
    }
    .stats-card i {
        font-size: 2.5rem;
        opacity: 0.8;
        color: white;
    }
    
    .alert-card {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        color: white;
    }
    .alert-card h3, .alert-card p, .alert-card i {
        color: white;
    }
    
    .success-card {
        background: linear-gradient(135deg, #51cf66 0%, #40c057 100%);
        color: white;
    }
    .success-card h3, .success-card p, .success-card i {
        color: white;
    }
    
    .info-card {
        background: linear-gradient(135deg, #339af0 0%, #228be6 100%);
        color: white;
    }
    .info-card h3, .info-card p, .info-card i {
        color: white;
    }
    
    .warning-card {
        background: linear-gradient(135deg, #ffd43b 0%, #fab005 100%);
        color: #212529;
    }
    .warning-card h3, .warning-card p, .warning-card i {
        color: #212529;
    }
    
    .dark-card {
        background: linear-gradient(135deg, #495057 0%, #343a40 100%);
        color: white;
    }
    .dark-card h3, .dark-card p, .dark-card i {
        color: white;
    }
    
    .purple-card {
        background: linear-gradient(135deg, #845ec2 0%, #6c5ce7 100%);
        color: white;
    }
    .purple-card h3, .purple-card p, .purple-card i {
        color: white;
    }
    
    /* Fix table text contrast */
    .table-dark {
        background-color: #212529;
        color: #ffffff;
    }
    .table-dark th,
    .table-dark td {
        color: #ffffff;
        border-color: #454d55;
    }
    .table-dark .text-muted {
        color: #adb5bd !important;
    }
    
    /* Fix filter section contrast */
    .bg-light {
        background-color: #f8f9fa !important;
        color: #212529 !important;
    }
    .bg-light .form-label,
    .bg-light .form-control,
    .bg-light .form-select {
        color: #212529 !important;
    }
    
    /* Fix card text contrast */
    .card {
        background-color: #ffffff;
        color: #212529;
    }
    .card-header {
        background-color: #f8f9fa;
        color: #212529;
        border-bottom: 1px solid #dee2e6;
    }
    .card-body {
        color: #212529;
    }
    
    /* Fix badge contrast */
    .badge {
        color: #ffffff;
    }
    .badge.bg-success {
        background-color: #198754 !important;
        color: #ffffff !important;
    }
    .badge.bg-warning {
        background-color: #ffc107 !important;
        color: #212529 !important;
    }
    .badge.bg-danger {
        background-color: #dc3545 !important;
        color: #ffffff !important;
    }
    .badge.bg-info {
        background-color: #0dcaf0 !important;
        color: #212529 !important;
    }
    
    /* Fix text muted contrast */
    .text-muted {
        color: #6c757d !important;
    }
    
    /* Fix alert section */
    .alert-section {
        background-color: #ffffff;
        color: #212529;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 1rem;
    }
    
    /* Fix no data message */
    .no-data {
        background-color: #f8f9fa;
        color: #6c757d;
        padding: 3rem;
        text-align: center;
        border-radius: 0.375rem;
    }
</style>

<div class="content-area">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <span class="badge me-2" style="background-color: <?php echo SECTIONS[1]['color']; ?>">
                    Section 1
                </span>
                Temperature Monitoring
            </h1>
            <p class="text-muted mb-0">Real-time monitoring of storage conditions for raw materials</p>
        </div>
        <div class="btn-group" role="group">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
            <button onclick="exportData('csv')" class="btn btn-outline-primary">
                <i class="fas fa-download me-2"></i>Export Data
            </button>
            <button onclick="location.reload()" class="btn btn-primary">
                <i class="fas fa-sync-alt me-2"></i>Refresh
            </button>
        </div>
    </div>
    
    <!-- Current Status Alert -->
    <?php 
    $criticalAlerts = array_filter($currentReadings, function($reading) {
        return $reading['alert_triggered'] || $reading['status'] !== 'Normal';
    });
    ?>
    <?php if (!empty($criticalAlerts)): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Environmental Alert!</strong> 
            <?php echo count($criticalAlerts); ?> room(s) have conditions outside optimal ranges.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="stats-card">
                <div class="stats-icon primary">
                    <i class="fas fa-thermometer-half"></i>
                </div>
                <div class="stats-number"><?php echo number_format($stats['avg_temp'], 1); ?>°C</div>
                <div class="stats-label">Avg Temperature</div>
                <div class="stats-sublabel">
                    <?php echo number_format($stats['min_temp'], 1); ?>° - <?php echo number_format($stats['max_temp'], 1); ?>°
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="stats-card info">
                <div class="stats-icon info">
                    <i class="fas fa-tint"></i>
                </div>
                <div class="stats-number"><?php echo number_format($stats['avg_humidity'], 1); ?>%</div>
                <div class="stats-label">Avg Humidity</div>
                <div class="stats-sublabel">
                    <?php echo number_format($stats['min_humidity'], 1); ?>% - <?php echo number_format($stats['max_humidity'], 1); ?>%
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="stats-card <?php echo $stats['alert_count'] > 0 ? 'danger' : 'success'; ?>">
                <div class="stats-icon <?php echo $stats['alert_count'] > 0 ? 'danger' : 'success'; ?>">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stats-number"><?php echo number_format($stats['alert_count']); ?></div>
                <div class="stats-label">Alerts</div>
                <div class="stats-sublabel">In selected period</div>
            </div>
        </div>
        
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="stats-card secondary">
                <div class="stats-icon secondary">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="stats-number"><?php echo number_format($stats['room_count']); ?></div>
                <div class="stats-label">Rooms</div>
                <div class="stats-sublabel">Monitored</div>
            </div>
        </div>
        
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="stats-card warning">
                <div class="stats-icon warning">
                    <i class="fas fa-database"></i>
                </div>
                <div class="stats-number"><?php echo number_format($stats['total_readings']); ?></div>
                <div class="stats-label">Total Readings</div>
                <div class="stats-sublabel">In selected period</div>
            </div>
        </div>
        
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="stats-card success">
                <div class="stats-icon success">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stats-number">
                    <?php echo !empty($currentReadings) ? timeAgo($currentReadings[0]['recorded_at']) : 'N/A'; ?>
                </div>
                <div class="stats-label">Last Update</div>
                <div class="stats-sublabel">Most recent reading</div>
            </div>
        </div>
    </div>
    
    <!-- Current Conditions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-thermometer-half me-2"></i>Current Conditions
                        <span class="badge bg-primary ms-2"><?php echo count($currentReadings); ?> rooms</span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($currentReadings)): ?>
                        <div class="row">
                            <?php foreach ($currentReadings as $reading): ?>
                                <div class="col-lg-3 col-md-6 mb-3">
                                    <div class="card h-100 <?php echo $reading['alert_triggered'] ? 'border-danger' : 'border-success'; ?>">
                                        <div class="card-body text-center">
                                            <h6 class="card-title">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars($reading['room_name']); ?>
                                            </h6>
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="display-6 <?php echo ($reading['temperature'] < 2 || $reading['temperature'] > 25) ? 'text-danger' : 'text-info'; ?>">
                                                        <?php echo number_format($reading['temperature'], 1); ?>°C
                                                    </div>
                                                    <small class="text-muted">Temperature</small>
                                                </div>
                                                <div class="col-6">
                                                    <div class="display-6 <?php echo ($reading['humidity'] < 20 || $reading['humidity'] > 80) ? 'text-danger' : 'text-success'; ?>">
                                                        <?php echo number_format($reading['humidity'], 1); ?>%
                                                    </div>
                                                    <small class="text-muted">Humidity</small>
                                                </div>
                                            </div>
                                            <hr>
                                            <span class="badge bg-<?php echo $reading['status'] === 'Normal' ? 'success' : 'warning'; ?>">
                                                <?php echo $reading['status']; ?>
                                            </span>
                                            <br><small class="text-muted">
                                                <?php echo formatDateTime($reading['recorded_at']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-thermometer-half fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Current Readings</h5>
                            <p class="text-muted">No temperature data available for the selected criteria.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="room_id" class="form-label">Room</label>
                    <select name="room_id" id="room_id" class="form-select">
                        <option value="">All Rooms</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo htmlspecialchars($room['room_id']); ?>" 
                                    <?php echo $room_id === $room['room_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($room['room_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" 
                           value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" 
                           value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block w-100">
                        <i class="fas fa-search me-2"></i>Filter Data
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Temperature Trends -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>Temperature Trends (24 Hours)
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="temperatureChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Humidity Trends -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-area me-2"></i>Humidity Trends (24 Hours)
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="humidityChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Alerts -->
    <?php if (!empty($recentAlerts)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>Recent Alerts
                        <span class="badge bg-danger ms-2"><?php echo count($recentAlerts); ?> alerts</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Room</th>
                                    <th>Alert Type</th>
                                    <th>Value</th>
                                    <th>Threshold</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentAlerts as $alert): ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($alert['room_name']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $alert['alert_type'] === 'temperature' ? 'danger' : 'warning'; ?>">
                                                <i class="fas fa-<?php echo $alert['alert_type'] === 'temperature' ? 'thermometer-half' : 'tint'; ?> me-1"></i>
                                                <?php echo ucfirst($alert['alert_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong class="text-<?php echo $alert['alert_type'] === 'temperature' ? 'danger' : 'warning'; ?>">
                                                <?php echo number_format($alert['value'], 1); ?><?php echo $alert['alert_type'] === 'temperature' ? '°C' : '%'; ?>
                                            </strong>
                                        </td>
                                        <td class="text-muted">
                                            <?php echo $alert['threshold']; ?>
                                        </td>
                                        <td>
                                            <?php echo formatDateTime($alert['recorded_at']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger">Active</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
        <div class="alert-section no-data">
            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
            <h5 class="text-success">No Active Alerts</h5>
            <p class="text-muted">All environmental conditions are within normal ranges.</p>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Temperature Trends Chart
<?php if (!empty($hourlyTrends)): ?>
const tempCtx = document.getElementById('temperatureChart').getContext('2d');
const tempData = <?php echo json_encode($hourlyTrends); ?>;

// Group data by room
const tempByRoom = {};
tempData.forEach(item => {
    if (!tempByRoom[item.room_name]) {
        tempByRoom[item.room_name] = {
            labels: [],
            data: []
        };
    }
    tempByRoom[item.room_name].labels.push(new Date(item.hour).toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'}));
    tempByRoom[item.room_name].data.push(parseFloat(item.avg_temp));
});

const tempDatasets = Object.keys(tempByRoom).map((room_name, index) => {
    const colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1'];
    return {
        label: room_name,
        data: tempByRoom[room_name].data,
        borderColor: colors[index % colors.length],
        backgroundColor: colors[index % colors.length] + '20',
        tension: 0.4,
        fill: false
    };
});

new Chart(tempCtx, {
    type: 'line',
    data: {
        labels: Object.values(tempByRoom)[0]?.labels || [],
        datasets: tempDatasets
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: false,
                title: {
                    display: true,
                    text: 'Temperature (°C)'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Time'
                }
            }
        },
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Humidity Trends Chart
const humidityCtx = document.getElementById('humidityChart').getContext('2d');

const humidityByRoom = {};
tempData.forEach(item => {
    if (!humidityByRoom[item.room_name]) {
        humidityByRoom[item.room_name] = {
            labels: [],
            data: []
        };
    }
    humidityByRoom[item.room_name].labels.push(new Date(item.hour).toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'}));
    humidityByRoom[item.room_name].data.push(parseFloat(item.avg_humidity));
});

const humidityDatasets = Object.keys(humidityByRoom).map((room_name, index) => {
    const colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1'];
    return {
        label: room_name,
        data: humidityByRoom[room_name].data,
        borderColor: colors[index % colors.length],
        backgroundColor: colors[index % colors.length] + '20',
        tension: 0.4,
        fill: true
    };
});

new Chart(humidityCtx, {
    type: 'line',
    data: {
        labels: Object.values(humidityByRoom)[0]?.labels || [],
        datasets: humidityDatasets
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                title: {
                    display: true,
                    text: 'Humidity (%)'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Time'
                }
            }
        },
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
<?php endif; ?>

// Export function
function exportData(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', format);
    window.open('export_temperature_data.php?' + params.toString(), '_blank');
}

// Auto-refresh every 2 minutes
setInterval(function() {
    if (document.visibilityState === 'visible') {
        location.reload();
    }
}, 120000);

// Real-time status indicator
function updateStatusIndicator() {
    const alertCount = <?php echo count($criticalAlerts); ?>;
    const statusElement = document.querySelector('.navbar .badge');
    if (statusElement && alertCount > 0) {
        statusElement.classList.add('bg-danger');
        statusElement.textContent = alertCount + ' alerts';
    }
}

updateStatusIndicator();
</script>

<?php include '../../includes/footer.php'; ?>
