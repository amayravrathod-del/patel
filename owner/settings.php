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
                
            case 'update_sms':
                $smsApiUrl = sanitize_input($_POST['sms_api_url'] ?? '');
                $smsApiKey = sanitize_input($_POST['sms_api_key'] ?? '');
                $smsSenderId = sanitize_input($_POST['sms_sender_id'] ?? '');
                $hotelName = sanitize_input($_POST['hotel_name'] ?? '');
                
                if (empty($smsApiUrl) || empty($smsApiKey)) {
                    $error = 'SMS API URL and API Key are required';
                } else {
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO settings (setting_key, setting_value, updated_by) 
                            VALUES (?, ?, ?) 
                            ON DUPLICATE KEY UPDATE setting_value = ?, updated_by = ?
                        ");
                        
                        $stmt->execute(['sms_api_url', $smsApiUrl, $_SESSION['user_id'], $smsApiUrl, $_SESSION['user_id']]);
                        $stmt->execute(['sms_api_key', $smsApiKey, $_SESSION['user_id'], $smsApiKey, $_SESSION['user_id']]);
                        $stmt->execute(['sms_sender_id', $smsSenderId, $_SESSION['user_id'], $smsSenderId, $_SESSION['user_id']]);
                        $stmt->execute(['hotel_name', $hotelName, $_SESSION['user_id'], $hotelName, $_SESSION['user_id']]);
                        
                        redirect_with_message('settings.php', 'SMS settings updated successfully!', 'success');
                    } catch (Exception $e) {
                        $error = 'Failed to update SMS settings';
                    }
                }
                break;
                
            case 'update_email':
                $smtpHost = sanitize_input($_POST['smtp_host'] ?? '');
                $smtpPort = sanitize_input($_POST['smtp_port'] ?? '');
                $smtpUsername = sanitize_input($_POST['smtp_username'] ?? '');
                $smtpPassword = $_POST['smtp_password'] ?? '';
                $smtpEncryption = $_POST['smtp_encryption'] ?? 'tls';
                $ownerEmail = sanitize_input($_POST['owner_email'] ?? '');
                
                if (empty($smtpHost) || empty($smtpUsername) || empty($smtpPassword)) {
                    $error = 'SMTP Host, Username and Password are required';
                } else {
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO settings (setting_key, setting_value, updated_by) 
                            VALUES (?, ?, ?) 
                            ON DUPLICATE KEY UPDATE setting_value = ?, updated_by = ?
                        ");
                        
                        $stmt->execute(['smtp_host', $smtpHost, $_SESSION['user_id'], $smtpHost, $_SESSION['user_id']]);
                        $stmt->execute(['smtp_port', $smtpPort, $_SESSION['user_id'], $smtpPort, $_SESSION['user_id']]);
                        $stmt->execute(['smtp_username', $smtpUsername, $_SESSION['user_id'], $smtpUsername, $_SESSION['user_id']]);
                        $stmt->execute(['smtp_password', $smtpPassword, $_SESSION['user_id'], $smtpPassword, $_SESSION['user_id']]);
                        $stmt->execute(['smtp_encryption', $smtpEncryption, $_SESSION['user_id'], $smtpEncryption, $_SESSION['user_id']]);
                        $stmt->execute(['owner_email', $ownerEmail, $_SESSION['user_id'], $ownerEmail, $_SESSION['user_id']]);
                        
                        redirect_with_message('settings.php', 'Email settings updated successfully!', 'success');
                    } catch (Exception $e) {
                        $error = 'Failed to update email settings';
                    }
                }
                break;
                
            case 'test_sms':
                $testMobile = sanitize_input($_POST['test_mobile'] ?? '');
                
                if (empty($testMobile) || !preg_match('/^[6-9]\d{9}$/', $testMobile)) {
                    $error = 'Valid 10-digit mobile number is required for testing';
                } else {
                    require_once '../includes/sms_functions.php';
                    $result = test_sms_configuration($testMobile, $pdo, $_SESSION['user_id']);
                    
                    if ($result['success']) {
                        redirect_with_message('settings.php', 'Test SMS sent successfully!', 'success');
                    } else {
                        $error = 'SMS test failed: ' . $result['message'];
                    }
                }
                break;
                
            case 'test_email':
                $testEmail = sanitize_input($_POST['test_email'] ?? '');
                
                if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Valid email address is required for testing';
                } else {
                    require_once '../includes/email_functions.php';
                    $result = test_email_configuration($testEmail, $pdo, $_SESSION['user_id']);
                    
                    if ($result['success']) {
                        redirect_with_message('settings.php', 'Test email sent successfully!', 'success');
                    } else {
                        $error = 'Email test failed: ' . $result['message'];
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
$stmt = $pdo->prepare("
    SELECT setting_key, setting_value 
    FROM settings 
    WHERE setting_key IN (
        'upi_id', 'hotel_name', 'sms_api_url', 'sms_api_key', 'sms_sender_id',
        'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'owner_email'
    )
");
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
        
        <!-- SMS Settings -->
        <div class="form-container">
            <h3>SMS Configuration</h3>
            <p style="color: var(--dark-color); margin-bottom: 1rem;">
                Configure SMS API for sending booking notifications to customers.
            </p>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="action" value="update_sms">
                
                <div class="form-group">
                    <label for="hotel_name" class="form-label">Hotel Name *</label>
                    <input type="text" id="hotel_name" name="hotel_name" class="form-control" required
                           value="<?= htmlspecialchars($settings['hotel_name'] ?? 'L.P.S.T Hotel') ?>">
                </div>
                
                <div class="form-group">
                    <label for="sms_api_url" class="form-label">SMS API URL *</label>
                    <input type="url" id="sms_api_url" name="sms_api_url" class="form-control" required
                           value="<?= htmlspecialchars($settings['sms_api_url'] ?? 'https://api.textlocal.in/send/') ?>"
                           placeholder="https://api.textlocal.in/send/">
                    <small style="color: var(--dark-color); font-size: 0.9rem;">
                        Examples: TextLocal, MSG91, Fast2SMS API endpoint
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="sms_api_key" class="form-label">SMS API Key *</label>
                    <input type="text" id="sms_api_key" name="sms_api_key" class="form-control" required
                           value="<?= htmlspecialchars($settings['sms_api_key'] ?? '') ?>"
                           placeholder="Your SMS API Key">
                </div>
                
                <div class="form-group">
                    <label for="sms_sender_id" class="form-label">SMS Sender ID</label>
                    <input type="text" id="sms_sender_id" name="sms_sender_id" class="form-control"
                           value="<?= htmlspecialchars($settings['sms_sender_id'] ?? 'LPSTHT') ?>"
                           placeholder="LPSTHT" maxlength="6">
                    <small style="color: var(--dark-color); font-size: 0.9rem;">
                        6 character sender ID (if supported by your SMS provider)
                    </small>
                </div>
                
                <button type="submit" class="btn btn-primary">Update SMS Settings</button>
            </form>
            
            <!-- Test SMS -->
            <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                <h4>Test SMS Configuration</h4>
                <form method="POST" style="display: flex; gap: 1rem; align-items: end;">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="action" value="test_sms">
                    <div class="form-group" style="margin: 0; flex: 1;">
                        <label for="test_mobile" class="form-label">Test Mobile Number</label>
                        <input type="tel" id="test_mobile" name="test_mobile" class="form-control"
                               pattern="[6-9][0-9]{9}" maxlength="10"
                               placeholder="10 digit mobile number">
                    </div>
                    <button type="submit" class="btn btn-warning">Send Test SMS</button>
                </form>
            </div>
        </div>
        
        <!-- Email Settings -->
        <div class="form-container">
            <h3>Email Configuration (SMTP)</h3>
            <p style="color: var(--dark-color); margin-bottom: 1rem;">
                Configure SMTP settings for sending export reports via email.
            </p>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="action" value="update_email">
                
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="smtp_host" class="form-label">SMTP Host *</label>
                        <input type="text" id="smtp_host" name="smtp_host" class="form-control" required
                               value="<?= htmlspecialchars($settings['smtp_host'] ?? 'smtp.gmail.com') ?>"
                               placeholder="smtp.gmail.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="smtp_port" class="form-label">SMTP Port *</label>
                        <input type="number" id="smtp_port" name="smtp_port" class="form-control" required
                               value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>"
                               placeholder="587">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="smtp_username" class="form-label">SMTP Username (Email) *</label>
                    <input type="email" id="smtp_username" name="smtp_username" class="form-control" required
                           value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>"
                           placeholder="your-email@gmail.com">
                </div>
                
                <div class="form-group">
                    <label for="smtp_password" class="form-label">SMTP Password *</label>
                    <input type="password" id="smtp_password" name="smtp_password" class="form-control" required
                           value="<?= htmlspecialchars($settings['smtp_password'] ?? '') ?>"
                           placeholder="Your email password or app password">
                    <small style="color: var(--dark-color); font-size: 0.9rem;">
                        For Gmail, use App Password instead of regular password
                    </small>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="smtp_encryption" class="form-label">Encryption</label>
                        <select id="smtp_encryption" name="smtp_encryption" class="form-control">
                            <option value="tls" <?= ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                            <option value="ssl" <?= ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="owner_email" class="form-label">Owner Email</label>
                        <input type="email" id="owner_email" name="owner_email" class="form-control"
                               value="<?= htmlspecialchars($settings['owner_email'] ?? '') ?>"
                               placeholder="owner@lpsthotel.com">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Email Settings</button>
            </form>
            
            <!-- Test Email -->
            <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                <h4>Test Email Configuration</h4>
                <form method="POST" style="display: flex; gap: 1rem; align-items: end;">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="action" value="test_email">
                    <div class="form-group" style="margin: 0; flex: 1;">
                        <label for="test_email" class="form-label">Test Email Address</label>
                        <input type="email" id="test_email" name="test_email" class="form-control"
                               placeholder="test@example.com">
                    </div>
                    <button type="submit" class="btn btn-warning">Send Test Email</button>
                </form>
            </div>
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