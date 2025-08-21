<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

require_role('OWNER');

$database = new Database();
$pdo = $database->getConnection();

// Handle export requests
if (isset($_GET['export'])) {
    $format = $_GET['export'];
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';
    $adminId = $_GET['admin_id'] ?? '';
    
    // Build query
    $whereConditions = [];
    $params = [];
    
    if (!empty($startDate)) {
        $whereConditions[] = "DATE(b.created_at) >= ?";
        $params[] = $startDate;
    }
    
    if (!empty($endDate)) {
        $whereConditions[] = "DATE(b.created_at) <= ?";
        $params[] = $endDate;
    }
    
    if (!empty($adminId)) {
        $whereConditions[] = "b.admin_id = ?";
        $params[] = $adminId;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $stmt = $pdo->prepare("
        SELECT b.*, r.display_name, r.type, u.username as admin_name
        FROM bookings b 
        JOIN resources r ON b.resource_id = r.id 
        JOIN users u ON b.admin_id = u.id 
        $whereClause
        ORDER BY b.created_at DESC
    ");
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
    
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="lpst_bookings_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Resource', 'Type', 'Client', 'Check-in', 'Check-out', 'Status', 'Paid', 'Amount', 'Admin', 'Created']);
        
        foreach ($bookings as $booking) {
            fputcsv($output, [
                $booking['id'],
                $booking['display_name'],
                $booking['type'],
                $booking['client_name'],
                $booking['check_in'],
                $booking['check_out'],
                $booking['status'],
                $booking['is_paid'] ? 'Yes' : 'No',
                $booking['total_amount'],
                $booking['admin_name'],
                $booking['created_at']
            ]);
        }
        
        fclose($output);
        exit;
    }
}

// Get filter data
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$adminId = $_GET['admin_id'] ?? '';

// Get all admins for filter
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE role = 'ADMIN' ORDER BY username");
$stmt->execute();
$admins = $stmt->fetchAll();

// Build filtered query
$whereConditions = [];
$params = [];

if (!empty($startDate)) {
    $whereConditions[] = "DATE(b.created_at) >= ?";
    $params[] = $startDate;
}

if (!empty($endDate)) {
    $whereConditions[] = "DATE(b.created_at) <= ?";
    $params[] = $endDate;
}

if (!empty($adminId)) {
    $whereConditions[] = "b.admin_id = ?";
    $params[] = $adminId;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get bookings
$stmt = $pdo->prepare("
    SELECT b.*, r.display_name, r.type, u.username as admin_name
    FROM bookings b 
    JOIN resources r ON b.resource_id = r.id 
    JOIN users u ON b.admin_id = u.id 
    $whereClause
    ORDER BY b.created_at DESC
    LIMIT 100
");
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Get summary stats
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN is_paid = 1 THEN total_amount ELSE 0 END) as total_paid,
        SUM(CASE WHEN is_paid = 0 THEN 1 ELSE 0 END) as unpaid_count,
        COUNT(CASE WHEN status = 'BOOKED' THEN 1 END) as active_bookings,
        COUNT(CASE WHEN status = 'PENDING' THEN 1 END) as pending_bookings
    FROM bookings b 
    $whereClause
");
$stmt->execute($params);
$stats = $stmt->fetch();

$flash = get_flash_message();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - L.P.S.T Bookings</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <nav class="top-nav">
        <div class="nav-links">
            <a href="index.php" class="nav-button">← Dashboard</a>
            <a href="admins.php" class="nav-button">Manage Admins</a>
            <a href="settings.php" class="nav-button">Settings</a>
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

        <h2>Reports & Analytics</h2>
        
        <!-- Filters -->
        <div class="form-container">
            <h3>Filters</h3>
            <form method="GET">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div class="form-group">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" 
                               value="<?= htmlspecialchars($startDate) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" 
                               value="<?= htmlspecialchars($endDate) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_id" class="form-label">Admin</label>
                        <select id="admin_id" name="admin_id" class="form-control">
                            <option value="">All Admins</option>
                            <?php foreach ($admins as $admin): ?>
                                <option value="<?= $admin['id'] ?>" 
                                        <?= $adminId == $admin['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($admin['username']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div style="margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" 
                       class="btn btn-success">Export CSV</a>
                </div>
            </form>
        </div>
        
        <!-- Summary Stats -->
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Total Bookings</h3>
                <div class="dashboard-value"><?= $stats['total_bookings'] ?></div>
            </div>
            
            <div class="dashboard-card">
                <h3>Total Revenue</h3>
                <div class="dashboard-value"><?= format_currency($stats['total_paid'] ?: 0) ?></div>
            </div>
            
            <div class="dashboard-card">
                <h3>Unpaid Bookings</h3>
                <div class="dashboard-value" style="color: var(--danger-color);"><?= $stats['unpaid_count'] ?></div>
            </div>
            
            <div class="dashboard-card">
                <h3>Active Bookings</h3>
                <div class="dashboard-value"><?= $stats['active_bookings'] ?></div>
            </div>
            
            <div class="dashboard-card">
                <h3>Pending Actions</h3>
                <div class="dashboard-value" style="color: var(--warning-color);"><?= $stats['pending_bookings'] ?></div>
            </div>
        </div>
        
        <!-- Bookings Table -->
        <div class="form-container">
            <h3>Recent Bookings (Last 100)</h3>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr style="background: var(--light-color);">
                            <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid var(--border-color);">ID</th>
                            <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid var(--border-color);">Resource</th>
                            <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid var(--border-color);">Client</th>
                            <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid var(--border-color);">Check-in</th>
                            <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid var(--border-color);">Status</th>
                            <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid var(--border-color);">Paid</th>
                            <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid var(--border-color);">Admin</th>
                            <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid var(--border-color);">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td style="padding: 0.5rem; border-bottom: 1px solid var(--border-color);">
                                    <?= $booking['id'] ?>
                                </td>
                                <td style="padding: 0.5rem; border-bottom: 1px solid var(--border-color);">
                                    <?= htmlspecialchars($booking['display_name']) ?>
                                </td>
                                <td style="padding: 0.5rem; border-bottom: 1px solid var(--border-color);">
                                    <?= htmlspecialchars($booking['client_name']) ?>
                                </td>
                                <td style="padding: 0.5rem; border-bottom: 1px solid var(--border-color);">
                                    <?= date('M j, g:i A', strtotime($booking['check_in'])) ?>
                                </td>
                                <td style="padding: 0.5rem; border-bottom: 1px solid var(--border-color);">
                                    <span class="status-badge status-<?= strtolower($booking['status']) ?>">
                                        <?= $booking['status'] ?>
                                    </span>
                                </td>
                                <td style="padding: 0.5rem; border-bottom: 1px solid var(--border-color);">
                                    <span style="color: <?= $booking['is_paid'] ? 'var(--success-color)' : 'var(--danger-color)' ?>">
                                        <?= $booking['is_paid'] ? 'PAID' : 'UNPAID' ?>
                                    </span>
                                </td>
                                <td style="padding: 0.5rem; border-bottom: 1px solid var(--border-color);">
                                    <?= htmlspecialchars($booking['admin_name']) ?>
                                </td>
                                <td style="padding: 0.5rem; border-bottom: 1px solid var(--border-color);">
                                    <?= date('M j, g:i A', strtotime($booking['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>