<?php
/**
 * KYA Food Production - Section 1 Storage Management
 * Raw Material Storage Location and Capacity Management
 */

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

// Create storage_locations table if not exists
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS storage_locations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            location_name VARCHAR(100) NOT NULL,
            location_code VARCHAR(20) UNIQUE NOT NULL,
            section INT NOT NULL DEFAULT 1,
            storage_type ENUM('cold_storage', 'dry_storage', 'freezer', 'ambient', 'controlled_atmosphere') DEFAULT 'dry_storage',
            capacity_kg DECIMAL(10,2) DEFAULT 0,
            current_load_kg DECIMAL(10,2) DEFAULT 0,
            temperature_min DECIMAL(5,2) DEFAULT NULL,
            temperature_max DECIMAL(5,2) DEFAULT NULL,
            humidity_min DECIMAL(5,2) DEFAULT NULL,
            humidity_max DECIMAL(5,2) DEFAULT NULL,
            status ENUM('active', 'maintenance', 'inactive') DEFAULT 'active',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_section (section),
            INDEX idx_status (status),
            INDEX idx_storage_type (storage_type)
        )
    ");

    // Insert sample storage locations if table is empty
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM storage_locations WHERE section = 1");
    $checkStmt->execute();
    if ($checkStmt->fetchColumn() == 0) {
        $conn->exec("
            INSERT INTO storage_locations (location_name, location_code, section, storage_type, capacity_kg, current_load_kg, temperature_min, temperature_max, humidity_min, humidity_max, status, notes) VALUES
            ('Storage Room A', 'SR-A-001', 1, 'dry_storage', 5000.00, 3200.50, 15.0, 25.0, 40.0, 60.0, 'active', 'Main dry storage for grains and cereals'),
            ('Storage Room B', 'SR-B-002', 1, 'dry_storage', 4500.00, 2800.75, 15.0, 25.0, 40.0, 60.0, 'active', 'Secondary dry storage for legumes and spices'),
            ('Cold Storage Unit', 'CS-001', 1, 'cold_storage', 3000.00, 1850.25, 2.0, 8.0, 80.0, 95.0, 'active', 'Refrigerated storage for perishables'),
            ('Freezer Unit', 'FZ-001', 1, 'freezer', 2000.00, 1200.00, -18.0, -15.0, NULL, NULL, 'active', 'Frozen storage for long-term preservation'),
            ('Drying Area', 'DA-001', 1, 'controlled_atmosphere', 1500.00, 800.30, 25.0, 35.0, 20.0, 40.0, 'active', 'Controlled drying environment'),
            ('Quarantine Storage', 'QS-001', 1, 'ambient', 1000.00, 150.00, 18.0, 28.0, 45.0, 65.0, 'active', 'Temporary storage for incoming materials')
        ");
    }
} catch (Exception $e) {
    error_log("Storage locations table error: " . $e->getMessage());
}

// Get filter parameters
$location_type = $_GET['location_type'] ?? '';
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'location_name';
$order = $_GET['order'] ?? 'ASC';

// Build query conditions
$whereConditions = ['section = 1'];
$params = [1];

if ($location_type) {
    $whereConditions[] = "storage_type = ?";
    $params[] = $location_type;
}

if ($status) {
    $whereConditions[] = "status = ?";
    $params[] = $status;
}

if ($search) {
    $whereConditions[] = "(location_name LIKE ? OR location_code LIKE ? OR notes LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Validate sort column
$allowedSorts = ['location_name', 'location_code', 'storage_type', 'capacity_kg', 'current_load_kg', 'status', 'created_at'];
if (!in_array($sort, $allowedSorts)) {
    $sort = 'location_name';
}

$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

// Get storage locations
try {
    $stmt = $conn->prepare("
        SELECT sl.*,
               ROUND((current_load_kg / capacity_kg) * 100, 2) as utilization_percent,
               (capacity_kg - current_load_kg) as available_capacity,
               CASE 
                   WHEN (current_load_kg / capacity_kg) * 100 >= 90 THEN 'critical'
                   WHEN (current_load_kg / capacity_kg) * 100 >= 75 THEN 'warning'
                   ELSE 'normal'
               END as capacity_status
        FROM storage_locations sl
        $whereClause
        ORDER BY $sort $order
    ");
    $stmt->execute($params);
    $storageLocations = $stmt->fetchAll();
    
    // Get summary statistics
    $summaryStmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_locations,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_locations,
            COUNT(CASE WHEN status = 'maintenance' THEN 1 END) as maintenance_locations,
            SUM(capacity_kg) as total_capacity,
            SUM(current_load_kg) as total_current_load,
            ROUND(AVG((current_load_kg / capacity_kg) * 100), 2) as avg_utilization,
            COUNT(CASE WHEN (current_load_kg / capacity_kg) * 100 >= 90 THEN 1 END) as critical_locations,
            COUNT(CASE WHEN (current_load_kg / capacity_kg) * 100 >= 75 AND (current_load_kg / capacity_kg) * 100 < 90 THEN 1 END) as warning_locations
        FROM storage_locations sl
        $whereClause
    ");
    $summaryStmt->execute($params);
    $summary = $summaryStmt->fetch();
    
    // Get storage types for filter
    $typesStmt = $conn->prepare("SELECT DISTINCT storage_type FROM storage_locations WHERE section = 1 ORDER BY storage_type");
    $typesStmt->execute();
    $storageTypes = $typesStmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    error_log("Storage locations query error: " . $e->getMessage());
    $storageLocations = [];
    $summary = [
        'total_locations' => 0, 'active_locations' => 0, 'maintenance_locations' => 0,
        'total_capacity' => 0, 'total_current_load' => 0, 'avg_utilization' => 0,
        'critical_locations' => 0, 'warning_locations' => 0
    ];
    $storageTypes = [];
}

$pageTitle = 'Section 1 - Storage Management';
include '../../includes/header.php';
?>

<!-- Add Location Modal -->
<div class="modal fade" id="addLocationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Storage Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addLocationForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="location_name" class="form-label">Location Name *</label>
                                <input type="text" class="form-control" id="location_name" name="location_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="location_code" class="form-label">Location Code *</label>
                                <input type="text" class="form-control" id="location_code" name="location_code" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="storage_type" class="form-label">Storage Type *</label>
                                <select class="form-select" id="storage_type" name="storage_type" required>
                                    <option value="">Select Type</option>
                                    <option value="cold_storage">Cold Storage</option>
                                    <option value="dry_storage">Dry Storage</option>
                                    <option value="freezer">Freezer</option>
                                    <option value="ambient">Ambient</option>
                                    <option value="controlled_atmosphere">Controlled Atmosphere</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="capacity_kg" class="form-label">Capacity (kg) *</label>
                                <input type="number" class="form-control" id="capacity_kg" name="capacity_kg" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="temp_min" class="form-label">Min Temp (°C)</label>
                                <input type="number" class="form-control" id="temp_min" name="temp_min" step="0.1">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="temp_max" class="form-label">Max Temp (°C)</label>
                                <input type="number" class="form-control" id="temp_max" name="temp_max" step="0.1">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="humidity_min" class="form-label">Min Humidity (%)</label>
                                <input type="number" class="form-control" id="humidity_min" name="humidity_min" step="0.1">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="humidity_max" class="form-label">Max Humidity (%)</label>
                                <input type="number" class="form-control" id="humidity_max" name="humidity_max" step="0.1">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Location</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Capacity Modal -->
<div class="modal fade" id="updateCapacityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Storage Capacity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="updateCapacityForm">
                <div class="modal-body">
                    <input type="hidden" id="update_location_id" name="location_id">
                    <div class="mb-3">
                        <label for="current_load" class="form-label">Current Load (kg) *</label>
                        <input type="number" class="form-control" id="current_load" name="current_load" step="0.01" required>
                        <div class="form-text">Update the current storage load for this location.</div>
                    </div>
                    <div class="mb-3">
                        <label for="update_notes" class="form-label">Update Notes</label>
                        <textarea class="form-control" id="update_notes" name="update_notes" rows="2" placeholder="Reason for capacity update..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Capacity</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-refresh every 3 minutes
setInterval(function() {
    if (document.visibilityState === 'visible') {
        location.reload();
    }
}, 180000);

// Refresh data function
function refreshData() {
    location.reload();
}

// Export data function
function exportData() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.open('storage.php?' + params.toString(), '_blank');
}

// View location details
function viewLocation(locationId) {
    // Implementation for viewing location details
    alert('View location details feature - ID: ' + locationId);
}

// Edit location
function editLocation(locationId) {
    // Implementation for editing location
    alert('Edit location feature - ID: ' + locationId);
}

// Update capacity
function updateCapacity(locationId) {
    document.getElementById('update_location_id').value = locationId;
    
    // Get current capacity data (you could fetch this via AJAX)
    // For now, just show the modal
    const modal = new bootstrap.Modal(document.getElementById('updateCapacityModal'));
    modal.show();
}

// Handle add location form submission
document.getElementById('addLocationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'add_location');
    
    fetch('storage_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Storage location added successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding the location.');
    });
});

// Handle update capacity form submission
document.getElementById('updateCapacityForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'update_capacity');
    
    fetch('storage_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Storage capacity updated successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the capacity.');
    });
});

// Auto-generate location code based on name
document.getElementById('location_name').addEventListener('input', function() {
    const name = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 8);
    if (name) {
        document.getElementById('location_code').value = name + '-001';
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        const modal = new bootstrap.Modal(document.getElementById('addLocationModal'));
        modal.show();
    }
    if (e.ctrlKey && e.key === 'f') {
        e.preventDefault();
        document.getElementById('search').focus();
    }
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        refreshData();
    }
});

// Handle CSV export
<?php if (isset($_GET['export']) && $_GET['export'] === 'csv'): ?>
// Generate CSV export
<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="storage_locations_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, [
    'Location Name', 'Location Code', 'Storage Type', 'Capacity (kg)', 
    'Current Load (kg)', 'Utilization %', 'Available Capacity (kg)',
    'Temperature Range', 'Humidity Range', 'Status', 'Notes', 'Created'
]);

foreach ($storageLocations as $location) {
    $tempRange = '';
    if ($location['temperature_min'] !== null) {
        $tempRange = $location['temperature_min'] . '°C - ' . $location['temperature_max'] . '°C';
    }
    
    $humidityRange = '';
    if ($location['humidity_min'] !== null) {
        $humidityRange = $location['humidity_min'] . '% - ' . $location['humidity_max'] . '%';
    }
    
    fputcsv($output, [
        $location['location_name'],
        $location['location_code'],
        ucfirst(str_replace('_', ' ', $location['storage_type'])),
        $location['capacity_kg'],
        $location['current_load_kg'],
        $location['utilization_percent'],
        $location['available_capacity'],
        $tempRange,
        $humidityRange,
        ucfirst($location['status']),
        $location['notes'],
        $location['created_at']
    ]);
}

fclose($output);
exit;
?>
<?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>