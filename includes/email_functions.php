<?php
/**
 * Email Functions for L.P.S.T Bookings System
 * Handles email sending for reports and exports
 */

require_once 'vendor/autoload.php'; // If using PHPMailer via Composer
// Alternative: include PHPMailer files directly if not using Composer
// require_once 'phpmailer/src/PHPMailer.php';
// require_once 'phpmailer/src/SMTP.php';
// require_once 'phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send email with attachment
 * @param string $to_email - Recipient email address
 * @param string $subject - Email subject
 * @param string $body - Email body (HTML)
 * @param string $attachment_path - Path to attachment file (optional)
 * @param string $attachment_name - Name for attachment (optional)
 * @param PDO $pdo - Database connection
 * @param int $admin_id - Admin ID who triggered the email
 * @return array - Response with success status and message
 */
function send_email($to_email, $subject, $body, $attachment_path = null, $attachment_name = null, $pdo, $admin_id) {
    try {
        // Get email settings from database
        $stmt = $pdo->prepare("
            SELECT setting_key, setting_value 
            FROM settings 
            WHERE setting_key IN ('smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'hotel_name')
        ");
        $stmt->execute();
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        $smtp_host = $settings['smtp_host'] ?? '';
        $smtp_port = $settings['smtp_port'] ?? '587';
        $smtp_username = $settings['smtp_username'] ?? '';
        $smtp_password = $settings['smtp_password'] ?? '';
        $smtp_encryption = $settings['smtp_encryption'] ?? 'tls';
        $hotel_name = $settings['hotel_name'] ?? 'L.P.S.T Hotel';
        
        if (empty($smtp_host) || empty($smtp_username) || empty($smtp_password)) {
            throw new Exception('Email SMTP configuration not found. Please configure email settings.');
        }
        
        // Log email attempt
        $stmt = $pdo->prepare("
            INSERT INTO email_logs (recipient_email, subject, email_type, status, admin_id) 
            VALUES (?, ?, 'EXPORT', 'PENDING', ?)
        ");
        $stmt->execute([$to_email, $subject, $admin_id]);
        $email_log_id = $pdo->lastInsertId();
        
        // Create PHPMailer instance
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = $smtp_encryption;
        $mail->Port = $smtp_port;
        
        // Recipients
        $mail->setFrom($smtp_username, $hotel_name);
        $mail->addAddress($to_email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        // Add attachment if provided
        if ($attachment_path && file_exists($attachment_path)) {
            $mail->addAttachment($attachment_path, $attachment_name ?: basename($attachment_path));
        }
        
        // Send email
        $mail->send();
        
        // Update email log with success
        $stmt = $pdo->prepare("
            UPDATE email_logs 
            SET status = 'SENT', response_data = 'Email sent successfully' 
            WHERE id = ?
        ");
        $stmt->execute([$email_log_id]);
        
        return [
            'success' => true,
            'message' => 'Email sent successfully'
        ];
        
    } catch (Exception $e) {
        // Log error
        if (isset($email_log_id)) {
            $stmt = $pdo->prepare("
                UPDATE email_logs 
                SET status = 'FAILED', response_data = ? 
                WHERE id = ?
            ");
            $stmt->execute(['Error: ' . $e->getMessage(), $email_log_id]);
        }
        
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Generate CSV export file
 */
function generate_csv_export($bookings, $filename) {
    $csv_path = 'exports/' . $filename;
    
    // Create exports directory if it doesn't exist
    if (!is_dir('exports')) {
        mkdir('exports', 0755, true);
    }
    
    $output = fopen($csv_path, 'w');
    
    // CSV headers
    fputcsv($output, [
        'ID', 'Resource', 'Type', 'Client Name', 'Mobile', 'Aadhar/License', 
        'Receipt No', 'Payment Mode', 'Check-in', 'Check-out', 'Status', 
        'Paid', 'Amount', 'Admin', 'Created'
    ]);
    
    // CSV data
    foreach ($bookings as $booking) {
        fputcsv($output, [
            $booking['id'],
            $booking['display_name'],
            $booking['type'],
            $booking['client_name'],
            $booking['client_mobile'],
            $booking['client_aadhar'] ?: $booking['client_license'],
            $booking['receipt_number'],
            $booking['payment_mode'],
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
    return $csv_path;
}

/**
 * Generate HTML email body for export
 */
function generate_export_email_body($export_type, $date_range, $total_bookings, $total_revenue) {
    $hotel_name = 'L.P.S.T Hotel'; // You can get this from settings
    
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .stats { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
            .footer { background: #6c757d; color: white; padding: 10px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h2>{$hotel_name}</h2>
            <h3>Booking Export Report</h3>
        </div>
        
        <div class='content'>
            <p>Dear Owner,</p>
            
            <p>Please find attached the booking export report as requested.</p>
            
            <div class='stats'>
                <h4>Export Summary:</h4>
                <ul>
                    <li><strong>Export Type:</strong> {$export_type}</li>
                    <li><strong>Date Range:</strong> {$date_range}</li>
                    <li><strong>Total Bookings:</strong> {$total_bookings}</li>
                    <li><strong>Total Revenue:</strong> ₹" . number_format($total_revenue, 2) . "</li>
                    <li><strong>Generated On:</strong> " . date('d-M-Y H:i:s') . "</li>
                </ul>
            </div>
            
            <p>The attached CSV file contains detailed information about all bookings including:</p>
            <ul>
                <li>Guest information (Name, Mobile, ID proof)</li>
                <li>Booking details (Check-in/out, Status, Payment)</li>
                <li>Admin information</li>
                <li>Receipt numbers and payment modes</li>
            </ul>
            
            <p>Thank you for using L.P.S.T Bookings System.</p>
            
            <p>Best regards,<br>
            L.P.S.T Bookings System</p>
        </div>
        
        <div class='footer'>
            This is an automated email from L.P.S.T Bookings System. Please do not reply to this email.
        </div>
    </body>
    </html>
    ";
}

/**
 * Send export email with CSV attachment
 */
function send_export_email($to_email, $bookings, $filters, $pdo, $admin_id) {
    try {
        // Generate CSV file
        $filename = 'lpst_bookings_export_' . date('Y-m-d_H-i-s') . '.csv';
        $csv_path = generate_csv_export($bookings, $filename);
        
        // Calculate stats
        $total_bookings = count($bookings);
        $total_revenue = array_sum(array_column($bookings, 'total_amount'));
        
        // Generate email content
        $date_range = ($filters['start_date'] ?? 'All') . ' to ' . ($filters['end_date'] ?? 'All');
        $export_type = 'Booking Records';
        
        $subject = 'L.P.S.T Hotel - Booking Export Report - ' . date('d-M-Y');
        $body = generate_export_email_body($export_type, $date_range, $total_bookings, $total_revenue);
        
        // Send email
        $result = send_email($to_email, $subject, $body, $csv_path, $filename, $pdo, $admin_id);
        
        // Clean up temporary file
        if (file_exists($csv_path)) {
            unlink($csv_path);
        }
        
        return $result;
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Test email configuration
 */
function test_email_configuration($test_email, $pdo, $admin_id) {
    $subject = 'L.P.S.T Bookings - Email Configuration Test';
    $body = "
    <html>
    <body>
        <h3>Email Configuration Test</h3>
        <p>This is a test email from L.P.S.T Bookings System.</p>
        <p>If you receive this email, your SMTP configuration is working correctly!</p>
        <p>Test sent on: " . date('d-M-Y H:i:s') . "</p>
    </body>
    </html>
    ";
    
    return send_email($test_email, $subject, $body, null, null, $pdo, $admin_id);
}
?>