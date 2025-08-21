<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

require_role('OWNER');

$database = new Database();
$pdo = $database->getConnection();

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security token mismatch';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'change_username':
                $newUsername = sanitize_input($_POST['new_username'] ?? '');
                
                if (empty($newUsername)) {
                    $error = 'New username is required';
                } else {
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
                        $stmt->execute([$newUsername, $_SESSION['user_id']]);
                        $_SESSION['username'] = $newUsername;
                        redirect_with_message('settings.php', 'Username changed successfully!', 'success');
                    } catch (Exception $e) {
                        $error = 'Failed to change username - it may already exist';
                    }
                }
                break;
                
            case 'update_upi':
                $upiId = sanitize_input($_POST['upi_id'] ?? '');
                
                if (empty($upiId)) {
                    $error = 'UPI ID is required';
                } else {
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO settings (setting_key, setting_value, updated_by) 
                            VALUES ('upi_id', ?, ?) 
                            ON DUPLICATE KEY UPDATE setting_value = ?, updated_by = ?
                        ");
                        $stmt->execute([$upiId, $_SESSION['user_id'], $upiId, $_SESSION['user_id']]);
                        
                        // Also update upi_name if not exists
                        $stmt = $pdo->prepare("
                            INSERT INTO settings (setting_key, setting_value, updated_by) 
                            VALUES ('upi_name', 'L.P.S.T Bookings', ?) 
                            ON DUPLICATE KEY UPDATE updated_by = ?
                        ");
                        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
                        
                        redirect_with_message('settings.php', 'UPI settings updated successfully!', 'success');
                    } catch (Exception $e) {
                        $error = 'Failed to update UPI settings';
                    }
                }
                break;
                
            case 'change_password':
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                if (empty($newPassword) || empty($confirmPassword)) {
                    $error = 'Both password fields are required';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'Passwords do not match';
                } elseif (strlen($newPassword) < 6) {
                    $error = 'Password must be at least 6 characters long';
                } else {
                    try {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                        
                        // Force logout after password change
                        session_destroy();
                        redirect_with_message('../login.php', 'Password changed successfully! Please login again.', 'success');
                    } catch (Exception $e) {
                        $error = 'Failed to change password';
                    }
                }
                break;
        }
    }
}

// Get current settings
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('upi_id', 'qr_image')");
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$flash = get_flash_message();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - L.P.S.T Bookings</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <nav class="top-nav">
        <div class="nav-links">
            <a href="index.php" class="nav-button">← Dashboard</a>
            <a href="admins.php" class="nav-button">Manage Admins</a>
            <a href="reports.php" class="nav-button">Reports</a>
        </div>
        <a href="/" class="nav-brand">L.P.S.T Bookings</a>
        <div class="nav-links">
            <span style="margin-right: 1rem;">Owner Panel</span>
            <a href="../logout.php" class="nav-button danger">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if ($flash): ?>
            <div class="flash-message flash-<?= $flash['type'] ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="flash-message flash-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <h2>System Settings</h2>
        
        <!-- Username Change -->
        <div class="form-container">
            <h3>Change Username</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="action" value="change_username">
                
                <div class="form-group">
                    <label for="new_username" class="form-label">New Username *</label>
                    <input type="text" id="new_username" name="new_username" class="form-control" required
                           value="<?= htmlspecialchars($_SESSION['username']) ?>">
                </div>
                
                <button type="submit" class="btn btn-warning">Change Username</button>
            </form>
        </div>
        
        <!-- UPI Settings -->
        <div class="form-container">
            <h3>UPI Payment Settings</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="action" value="update_upi">
                
                <div class="form-group">
                    <label for="upi_id" class="form-label">UPI ID *</label>
                    <input type="text" id="upi_id" name="upi_id" class="form-control" required
                           value="<?= htmlspecialchars($settings['upi_id'] ?? 'owner@upi') ?>"
                           placeholder="yourname@upi">
                    <small style="color: var(--dark-color); font-size: 0.9rem;">
                        This UPI ID will be used for payment redirections
                    </small>
                </div>
                
                <button type="submit" class="btn btn-primary">Update UPI Settings</button>
            </form>
        </div>
        
        <!-- Password Change -->
        <div class="form-container">
            <h3>Change Password</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label for="new_password" class="form-label">New Password *</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required
                           minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm New Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required
                           minlength="6">
                </div>
                
                <button type="submit" class="btn btn-danger" 
                        onclick="return confirm('You will be logged out after changing password. Continue?')">
                    Change Password
                </button>
            </form>
        </div>
        
        <!-- System Information -->
        <div class="form-container">
            <h3>System Information</h3>
            <div class="dashboard-card">
                <p><strong>PHP Version:</strong> <?= PHP_VERSION ?></p>
                <p><strong>Server Time:</strong> <?= date('Y-m-d H:i:s T') ?></p>
                <p><strong>Database:</strong> Connected</p>
                <p><strong>Auto Refresh:</strong> 30 seconds</p>
                <p><strong>Grace Period:</strong> 24 hours</p>
            </div>
        </div>
    </div>
</body>
</html>