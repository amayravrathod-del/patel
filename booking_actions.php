<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

require_role('ADMIN');

$database = new Database();
$pdo = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
    redirect_with_message('grid.php', 'Invalid request', 'error');
}

$action = $_POST['action'] ?? '';
$bookingId = $_POST['booking_id'] ?? '';

if (empty($bookingId)) {
    redirect_with_message('grid.php', 'Booking ID required', 'error');
}

try {
    switch ($action) {
        case 'cancel_advanced':
            // Cancel advanced booking
            $stmt = $pdo->prepare("
                SELECT b.*, r.display_name, r.custom_name, u.username as admin_name
                FROM bookings b 
                JOIN resources r ON b.resource_id = r.id 
                JOIN users u ON b.admin_id = u.id 
                WHERE b.id = ?
            ");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();
            
            if (!$booking) {
                redirect_with_message('grid.php', 'Booking not found', 'error');
            }
            
            // Update booking status to cancelled
            $stmt = $pdo->prepare("
                UPDATE bookings 
                SET status = 'COMPLETED', 
                    payment_notes = CONCAT(IFNULL(payment_notes, ''), ' - Advanced booking cancelled by admin')
                WHERE id = ?
            ");
            if ($stmt->execute([$bookingId])) {
                // Send cancellation SMS
                require_once 'includes/sms_functions.php';
                send_cancellation_sms($bookingId, $pdo);
                
                // Record cancellation for owner dashboard
                try {
                    $resourceName = $booking['custom_name'] ?: $booking['display_name'];
                    $stmt = $pdo->prepare("
                        INSERT INTO booking_cancellations (booking_id, resource_id, cancelled_by, cancellation_reason, original_client_name, original_advance_date) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $bookingId, 
                        $booking['resource_id'], 
                        $_SESSION['user_id'],
                        "Advanced booking cancelled by " . $_SESSION['username'],
                        $booking['client_name'],
                        $booking['advance_date']
                    ]);
                } catch (Exception $e) {
                    // Continue even if cancellation recording fails
                }
                
                redirect_with_message('grid.php', 'Advanced booking cancelled successfully! Room is now available.', 'success');
            } else {
                redirect_with_message('grid.php', 'Failed to cancel advanced booking', 'error');
            }
            break;
            
        case 'mark_paid':
            // Get booking and resource details
            $stmt = $pdo->prepare("
                SELECT b.*, r.display_name, r.custom_name 
                FROM bookings b 
                JOIN resources r ON b.resource_id = r.id 
                WHERE b.id = ?
            ");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();
            
            if (!$booking) {
                redirect_with_message('grid.php', 'Booking not found', 'error');
            }
            
            $resourceName = $booking['custom_name'] ?: $booking['display_name'];
            
            // Calculate duration for amount (you can modify this logic)
            $duration = calculate_duration($booking['check_in']);
            $amount = max(500, $duration['hours'] * 100); // Minimum 500, then 100 per hour
            
            if (mark_booking_paid($bookingId, $pdo)) {
                // Send checkout SMS
                require_once 'includes/sms_functions.php';
                send_checkout_confirmation_sms($bookingId, $pdo);
                
                // Record the payment
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO payments (booking_id, resource_id, amount, payment_method, payment_status, admin_id, payment_notes) 
                        VALUES (?, ?, ?, 'CHECKOUT', 'COMPLETED', ?, ?)
                    ");
                    $stmt->execute([
                        $bookingId, 
                        $booking['resource_id'], 
                        $amount, 
                        $_SESSION['user_id'],
                        "Checkout payment for {$resourceName} - Duration: {$duration['formatted']}"
                    ]);
                } catch (Exception $e) {
                    // Continue even if payment recording fails
                }
                
                redirect_with_message('grid.php', 'Booking marked as paid! Room is now available.', 'success');
            } else {
                redirect_with_message('grid.php', 'Failed to mark as paid', 'error');
            }
            break;
            
        case 'checkout':
            // Get booking details for payment recording
            $stmt = $pdo->prepare("
                SELECT b.*, r.display_name, r.custom_name 
                FROM bookings b 
                JOIN resources r ON b.resource_id = r.id 
                WHERE b.id = ?
            ");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();
            
            if (complete_checkout($bookingId, $pdo)) {
                // Send checkout SMS
                require_once 'includes/sms_functions.php';
                send_checkout_confirmation_sms($bookingId, $pdo);
                
                // Record checkout completion
                if ($booking) {
                    $resourceName = $booking['custom_name'] ?: $booking['display_name'];
                    $duration = calculate_duration($booking['check_in']);
                    $amount = max(500, $duration['hours'] * 100);
                    
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO payments (booking_id, resource_id, amount, payment_method, payment_status, admin_id, payment_notes) 
                            VALUES (?, ?, ?, 'CHECKOUT_COMPLETE', 'COMPLETED', ?, ?)
                        ");
                        $stmt->execute([
                            $bookingId, 
                            $booking['resource_id'], 
                            $amount, 
                            $_SESSION['user_id'],
                            "Checkout completed for {$resourceName} - Duration: {$duration['formatted']}"
                        ]);
                    } catch (Exception $e) {
                        // Continue even if payment recording fails
                    }
                }
                
                redirect_with_message('grid.php', 'Checkout completed successfully!', 'success');
            } else {
                redirect_with_message('grid.php', 'Failed to complete checkout', 'error');
            }
            break;
            
        case 'cancel_booking':
            // Cancel regular booking
            $stmt = $pdo->prepare("
                SELECT b.*, r.display_name, r.custom_name, u.username as admin_name
                FROM bookings b 
                JOIN resources r ON b.resource_id = r.id 
                JOIN users u ON b.admin_id = u.id 
                WHERE b.id = ?
            ");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();
            
            if (!$booking) {
                redirect_with_message('grid.php', 'Booking not found', 'error');
            }
            
            $stmt = $pdo->prepare("
                UPDATE bookings 
                SET status = 'COMPLETED', 
                    actual_check_out = NOW(),
                    payment_notes = CONCAT(IFNULL(payment_notes, ''), ' - Booking cancelled by admin')
                WHERE id = ?
            ");
            if ($stmt->execute([$bookingId])) {
                // Send cancellation SMS
                require_once 'includes/sms_functions.php';
                send_cancellation_sms($bookingId, $pdo);
                
                // Record cancellation for owner dashboard
                try {
                    $resourceName = $booking['custom_name'] ?: $booking['display_name'];
                    $duration = calculate_duration($booking['check_in']);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO booking_cancellations (booking_id, resource_id, cancelled_by, cancellation_reason, original_client_name, duration_at_cancellation) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $bookingId, 
                        $booking['resource_id'], 
                        $_SESSION['user_id'],
                        "Regular booking cancelled by " . $_SESSION['username'] . " after " . $duration['formatted'],
                        $booking['client_name'],
                        $duration['total_minutes']
                    ]);
                } catch (Exception $e) {
                    // Continue even if cancellation recording fails
                }
                
                redirect_with_message('grid.php', 'Booking cancelled successfully! Room is now available.', 'success');
            } else {
                redirect_with_message('grid.php', 'Failed to cancel booking', 'error');
            }
            break;
            
        default:
            redirect_with_message('grid.php', 'Invalid action', 'error');
    }
} catch (Exception $e) {
    redirect_with_message('grid.php', 'Operation failed: ' . $e->getMessage(), 'error');
}
?>