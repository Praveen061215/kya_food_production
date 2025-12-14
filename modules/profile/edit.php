<?php
/**
 * KYA Food Production - Edit Profile
 * Logged-in user can view and update their own profile
 */

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

SessionManager::start();
SessionManager::requireLogin();

$userInfo = SessionManager::getUserInfo();

$db = (new Database())->connect();

// Load current user row
$userId = (int)$userInfo['id'];
$stmt = $db->prepare("SELECT id, username, full_name, email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    // If user row missing, force logout
    SessionManager::destroy();
    header('Location: ../../login.php');
    exit();
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }

    if ($password !== '' || $confirm !== '') {
        if ($password !== $confirm) {
            $errors[] = 'Password and confirmation do not match.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password should be at least 6 characters.';
        }
    }

    if (empty($errors)) {
        // Check email uniqueness (excluding self)
        $emailCheck = $db->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
        $emailCheck->execute([$email, $userId]);
        if ($emailCheck->fetch()) {
            $errors[] = 'This email is already used by another account.';
        }
    }

    if (empty($errors)) {
        // Build update query
        $params = [$fullName, $email, $userId];
        $updateSql = "UPDATE users SET full_name = ?, email = ?";

        if ($password !== '' && $password === $confirm) {
            // Use password_hash for security; adjust if your schema is different
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $updateSql .= ", password = ?";
            array_splice($params, 2, 0, [$hashed]); // insert hash before id
        }

        $updateSql .= " WHERE id = ?";

        $upd = $db->prepare($updateSql);
        $upd->execute($params);

        // Refresh data
        $stmt = $db->prepare("SELECT id, username, full_name, email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

        // Optionally update session values
        $_SESSION['full_name'] = $currentUser['full_name'];
        $_SESSION['email']     = $currentUser['email'];

        $success = true;
    }
}

$pageTitle = 'My Profile';
include '../../includes/header.php';
?>

<div class="content-area">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">My Profile</h1>
            <p class="text-muted mb-0">View and update your profile details</p>
        </div>
        <a href="../../dashboard.php" class="btn btn-outline-secondary btn-sm">
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
            Profile updated successfully.
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['username']); ?>" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($currentUser['full_name'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>" required>
                </div>

                <hr>

                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Re-type new password">
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Save Changes
                </button>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
