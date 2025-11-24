<?php
/**
 * KYA Food Production - Admin Database Backup
 * Generate and download SQL backup of the application database
 */

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

SessionManager::start();
SessionManager::requireLogin();
// Require admin role (adjust role name if different in USER_ROLES)
SessionManager::requireRole('admin');

$userInfo = SessionManager::getUserInfo();

$database = new Database();
$db = $database->connect();

// Handle backup generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_backup'])) {
    // Optional: CSRF protection if you have tokens in your system
    // verifyCSRFToken($_POST['csrf_token'] ?? '');

    $dbName = 'kya_food_production';
    $filename = sprintf('%s_backup_%s.sql', $dbName, date('Ymd_His'));

    // Build SQL dump
    $sqlDump = "-- KYA Food Production Database Backup\n";
    $sqlDump .= "-- Database: `{$dbName}`\n";
    $sqlDump .= "-- Generated at: " . date('Y-m-d H:i:s') . "\n\n";
    $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    // Get all tables
    $tablesStmt = $db->query('SHOW TABLES');
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        // Get CREATE TABLE
        $createStmt = $db->query("SHOW CREATE TABLE `{$table}`");
        $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
        $createSql = $createRow['Create Table'] ?? $createRow['Create Table'] ?? '';

        $sqlDump .= "-- ---------------------------------------------\n";
        $sqlDump .= "-- Table structure for table `{$table}`\n";
        $sqlDump .= "-- ---------------------------------------------\n\n";
        $sqlDump .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sqlDump .= $createSql . ";\n\n";

        // Dump data
        $dataStmt = $db->query("SELECT * FROM `{$table}`");
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            $sqlDump .= "-- Data for table `{$table}`\n";
            foreach ($rows as $row) {
                $columns = array_map(function ($col) {
                    return '`' . str_replace('`', '``', $col) . '`';
                }, array_keys($row));

                $values = array_map(function ($value) use ($db) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    return $db->quote($value);
                }, array_values($row));

                $sqlDump .= sprintf(
                    "INSERT INTO `%s` (%s) VALUES (%s);\n",
                    $table,
                    implode(', ', $columns),
                    implode(', ', $values)
                );
            }
            $sqlDump .= "\n";
        }
    }

    $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";

    // Send file to browser
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($sqlDump));

    echo $sqlDump;
    exit;
}

$pageTitle = 'Admin - Database Backup';
include '../../includes/header.php';
?>

<div class="content-area">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <span class="badge me-2 bg-danger">Admin</span>
                Database Backup
            </h1>
            <p class="text-muted mb-0">Generate and download a full SQL backup of the KYA Food Production database.</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Admin Dashboard
        </a>
    </div>

    <div class="alert alert-warning">
        <strong>Important:</strong> This backup contains all application data, including users and logs. Store it securely and do not share it publicly.
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3"><i class="fas fa-database me-2"></i>Create Backup</h5>
            <p class="text-muted">Click the button below to generate a downloadable <code>.sql</code> file with the full database structure and data.</p>

            <form method="post">
                <button type="submit" name="generate_backup" class="btn btn-primary">
                    <i class="fas fa-download me-1"></i> Download SQL Backup
                </button>
            </form>

            <hr>
            <p class="small text-muted mb-0">
                Tip: Keep multiple backups and store them in a safe location (e.g., external drive or secure cloud storage).
            </p>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
