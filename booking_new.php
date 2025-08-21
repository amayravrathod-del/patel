<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

require_role('ADMIN');

$database = new Database();
$pdo = $database->getConnection();

$resourceId = $_GET['id'] ?? '';
$resourceType = $_GET['type'] ?? '';

// Get resource details
$stmt = $pdo->prepare("SELECT * FROM resources WHERE id = ? AND is_active = 1");
$stmt->execute([$resourceId]);
$resource = $stmt->fetch();

if (!$resource) {
    redirect_with_message('grid.php', 'Resource not found', 'error');
}

// Check if resource is available
$existing = get_resource_status($resourceId, $pdo);
if ($existing) {
    redirect_with_message('grid.php', 'Resource is not available for booking', 'error');
}

// Check if resource has advanced booking
$hasAdvanced = has_advanced_booking($resourceId, $pdo);
if ($hasAdvanced) {
    redirect_with_message('grid.php', 'Resource has an advanced booking and cannot be booked', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security token mismatch';
    } else {
        $clientName = sanitize_input($_POST['client_name'] ?? '');
        $checkin = $_POST['check_in'] ?? '';
        $checkout = $_POST['check_out'] ?? '';
        
        if (empty($clientName) || empty($checkin) || empty($checkout)) {
            $error = 'All fields are required';
        } elseif (strtotime($checkout) <= strtotime($checkin)) {
            $error = 'Check-out must be after check-in';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO bookings (resource_id, client_name, check_in, check_out, actual_check_in, admin_id, status, booking_type) 
                    VALUES (?, ?, ?, ?, NOW(), ?, 'BOOKED', 'regular')
                ");
                $stmt->execute([$resourceId, $clientName, $checkin, $checkout, $_SESSION['user_id']]);
                
                redirect_with_message('grid.php', 'Booking created successfully!', 'success');
            } catch (Exception $e) {
                $error = 'Failed to create booking: ' . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Booking - <?= htmlspecialchars($resource['display_name']) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <nav class="top-nav">
        <div class="nav-links">
            <a href="grid.php" class="nav-button">← Back to Grid</a>
        </div>
        <a href="/" class="nav-brand">L.P.S.T Bookings</a>
        <div class="nav-links">
            <a href="logout.php" class="nav-button danger">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="form-container">
            <h2>New Booking - <?= htmlspecialchars($resource['display_name']) ?></h2>
            
            <?php if (isset($error)): ?>
                <div class="flash-message flash-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" onsubmit="return validateBookingForm()">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                
                <div class="form-group">
                    <label for="client_name" class="form-label">Client Name *</label>
                    <input type="text" id="client_name" name="client_name" class="form-control" required
                           value="<?= isset($_POST['client_name']) ? htmlspecialchars($_POST['client_name']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="check_in" class="form-label">Check-in Date & Time *</label>
                    <input type="datetime-local" id="check_in" name="check_in" class="form-control" required
                           value="<?= isset($_POST['check_in']) ? $_POST['check_in'] : date('Y-m-d\TH:i') ?>">
                </div>
                
                <div class="form-group">
                    <label for="check_out" class="form-label">Check-out Date & Time *</label>
                    <input type="datetime-local" id="check_out" name="check_out" class="form-control" required
                           value="<?= isset($_POST['check_out']) ? $_POST['check_out'] : date('Y-m-d\TH:i', strtotime('+1 day')) ?>">
                </div>
                
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Create Booking</button>
                    <a href="grid.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/script.js"></script>
</body>
</html>