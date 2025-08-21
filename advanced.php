<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

require_role('ADMIN');

$database = new Database();
$pdo = $database->getConnection();

// Get all resources for selection
$stmt = $pdo->prepare("SELECT * FROM resources WHERE is_active = 1 ORDER BY type, CAST(identifier AS UNSIGNED), identifier");
$stmt->execute();
$resources = $stmt->fetchAll();

// Get all advanced bookings
$stmt = $pdo->prepare("
    SELECT b.*, r.display_name, r.type, r.identifier 
    FROM bookings b 
    JOIN resources r ON b.resource_id = r.id 
    WHERE b.booking_type = 'advanced' AND b.status = 'ADVANCED_BOOKED'
    ORDER BY b.advance_date ASC
");
$stmt->execute();
$advancedBookings = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security token mismatch';
    } else {
        $resourceId = $_POST['resource_id'] ?? '';
        $advanceDate = $_POST['advance_date'] ?? '';
        $clientName = sanitize_input($_POST['client_name'] ?? '');
        
        if (empty($resourceId) || empty($advanceDate) || empty($clientName)) {
            $error = 'All fields are required';
        } elseif (strtotime($advanceDate) <= strtotime('today')) {
            $error = 'Advance date must be in the future';
        } else {
            // Check if resource already has advance booking for this date
            $stmt = $pdo->prepare("
                SELECT id FROM bookings 
                WHERE resource_id = ? AND advance_date = ? AND status = 'ADVANCED_BOOKED'
            ");
            $stmt->execute([$resourceId, $advanceDate]);
            
            if ($stmt->fetch()) {
                $error = 'Resource already has an advance booking for this date';
            } else {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO bookings (resource_id, client_name, advance_date, admin_id, status, booking_type, check_in, check_out) 
                        VALUES (?, ?, ?, ?, 'ADVANCED_BOOKED', 'advanced', ?, ?)
                    ");
                    // Set dummy check-in/out for advance bookings
                    $dummyTime = $advanceDate . ' 12:00:00';
                    $stmt->execute([$resourceId, $clientName, $advanceDate, $_SESSION['user_id'], $dummyTime, $dummyTime]);
                    
                    redirect_with_message('advanced.php', 'Advanced booking created successfully!', 'success');
                } catch (Exception $e) {
                    $error = 'Failed to create advance booking';
                }
            }
        }
    }
}

$flash = get_flash_message();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Bookings - L.P.S.T Bookings</title>
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
        <?php if ($flash): ?>
            <div class="flash-message flash-<?= $flash['type'] ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <h2>Advanced Bookings</h2>
        
        <div class="form-container">
            <h3>Create New Advanced Booking</h3>
            
            <?php if (isset($error)): ?>
                <div class="flash-message flash-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                
                <div class="form-group">
                    <label for="resource_id" class="form-label">Select Room/Hall *</label>
                    <select id="resource_id" name="resource_id" class="form-control" required>
                        <option value="">Choose a resource...</option>
                        <?php foreach ($resources as $resource): ?>
                            <option value="<?= $resource['id'] ?>" 
                                    <?= (isset($_POST['resource_id']) && $_POST['resource_id'] == $resource['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($resource['custom_name'] ?: $resource['display_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="advance_date" class="form-label">Advance Date *</label>
                    <input type="date" id="advance_date" name="advance_date" class="form-control" 
                           min="<?= date('Y-m-d', strtotime('tomorrow')) ?>" required
                           value="<?= isset($_POST['advance_date']) ? $_POST['advance_date'] : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="client_name" class="form-label">Client Name *</label>
                    <input type="text" id="client_name" name="client_name" class="form-control" required
                           value="<?= isset($_POST['client_name']) ? htmlspecialchars($_POST['client_name']) : '' ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">Create Advanced Booking</button>
            </form>
        </div>
        
        <?php if (!empty($advancedBookings)): ?>
            <div class="advanced-section">
                <h3>Current Advanced Bookings</h3>
                <div class="advanced-grid">
                    <?php foreach ($advancedBookings as $booking): ?>
                        <div class="advanced-box">
                            <div class="advanced-room-number" style="font-size: 2rem; margin-bottom: 0.5rem;">
                                <?php 
                                // Show custom name if available, otherwise show display name
                                echo htmlspecialchars($booking['custom_name'] ?: $booking['display_name']);
                                ?>
                            </div>
                            <div style="font-size: 0.9rem; margin-bottom: 0.5rem; opacity: 0.8;">
                                <?= htmlspecialchars($booking['custom_name'] ? $booking['display_name'] : '') ?>
                            </div>
                            <strong>Date:</strong> <?= date('M j, Y', strtotime($booking['advance_date'])) ?><br>
                            <strong>Client:</strong> <?= htmlspecialchars($booking['client_name']) ?><br>
                            <strong>Status:</strong> 
                            <span class="status-badge status-advanced">ADVANCED BOOKED</span><br>
                            
                            <?php if ($booking['advance_date'] === date('Y-m-d')): ?>
                                <div style="margin-top: 0.5rem;">
                                    <span style="background: var(--warning-color); color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.7rem;">
                                        DUE TODAY
                                    </span>
                                    <a href="booking_advance_convert.php?resource_id=<?= $booking['resource_id'] ?>" 
                                       class="btn btn-success" style="margin-top: 0.5rem; font-size: 0.8rem;">
                                        Convert to Active
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="assets/script.js"></script>
</body>
</html>