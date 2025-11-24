<?php
/**
 * KYA Food Production - User Preferences
 * Logged-in user can manage their own UI / notification preferences
 */

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

SessionManager::start();
SessionManager::requireLogin();

$userInfo = SessionManager::getUserInfo();

$db = (new Database())->connect();

// Create user_preferences table if it doesn't exist
$createTableSql = "
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    theme ENUM('light','dark','system') DEFAULT 'system',
    sidebar_collapsed TINYINT(1) DEFAULT 0,
    notify_email TINYINT(1) DEFAULT 1,
    notify_system TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user (user_id),
    CONSTRAINT fk_user_preferences_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB";

try {
    $db->exec($createTableSql);
} catch (PDOException $e) {
    error_log('user_preferences table creation failed: ' . $e->getMessage());
}

$userId = (int)$userInfo['id'];

// Load or create default preferences
$stmt = $db->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
$stmt->execute([$userId]);
$prefs = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prefs) {
    $ins = $db->prepare("INSERT INTO user_preferences (user_id) VALUES (?)");
    $ins->execute([$userId]);

    $stmt->execute([$userId]);
    $prefs = $stmt->fetch(PDO::FETCH_ASSOC);
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme             = $_POST['theme'] ?? 'system';
    $sidebarCollapsed  = isset($_POST['sidebar_collapsed']) ? 1 : 0;
    $notifyEmail       = isset($_POST['notify_email']) ? 1 : 0;
    $notifySystem      = isset($_POST['notify_system']) ? 1 : 0;

    if (!in_array($theme, ['light','dark','system'], true)) {
        $theme = 'system';
    }

    if (empty($errors)) {
        $upd = $db->prepare("UPDATE user_preferences SET theme = ?, sidebar_collapsed = ?, notify_email = ?, notify_system = ? WHERE user_id = ?");
        $upd->execute([$theme, $sidebarCollapsed, $notifyEmail, $notifySystem, $userId]);

        // Reload
        $stmt->execute([$userId]);
        $prefs = $stmt->fetch(PDO::FETCH_ASSOC);

        // Optionally, push theme to session for quick access
        $_SESSION['theme'] = $prefs['theme'];

        $success = true;
    }
}

$pageTitle = 'My Preferences';
include '../../includes/header.php';
?>

<div class="content-area">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Preferences</h1>
            <p class="text-muted mb-0">Customize your interface and notification settings</p>
        </div>
        <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                    <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif ($success): ?>
        <div class="alert alert-success">
            Preferences updated successfully.
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post">
                <h5 class="mb-3"><i class="fas fa-paint-brush me-2"></i>Interface</h5>
                <div class="mb-3">
                    <label class="form-label">Theme</label>
                    <select name="theme" class="form-select">
                        <option value="system" <?php echo $prefs['theme'] === 'system' ? 'selected' : ''; ?>>System default</option>
                        <option value="light"  <?php echo $prefs['theme'] === 'light'  ? 'selected' : ''; ?>>Light</option>
                        <option value="dark"   <?php echo $prefs['theme'] === 'dark'   ? 'selected' : ''; ?>>Dark</option>
                    </select>
                </div>

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="sidebar_collapsed" id="sidebar_collapsed" <?php echo (int)$prefs['sidebar_collapsed'] === 1 ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="sidebar_collapsed">Collapse sidebar by default</label>
                </div>

                <h5 class="mb-3"><i class="fas fa-bell me-2"></i>Notifications</h5>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="notify_email" id="notify_email" <?php echo (int)$prefs['notify_email'] === 1 ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="notify_email">Email notifications (where applicable)</label>
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="notify_system" id="notify_system" <?php echo (int)$prefs['notify_system'] === 1 ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="notify_system">In-app/system notifications</label>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Save Preferences
                </button>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
