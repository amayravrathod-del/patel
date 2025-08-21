# L.P.S.T Bookings System - Complete Setup Guide

## Overview
L.P.S.T Bookings System is a comprehensive hotel and hall booking management system with SMS notifications, email exports, and real-time tracking capabilities.

## Features
- **Room & Hall Management**: 26 rooms + 2 halls with custom naming
- **Advanced Booking System**: Regular and advance bookings
- **Guest Information Management**: Name, mobile, Aadhar/License, receipt numbers
- **SMS Notifications**: Automatic SMS for booking, checkout, and cancellations
- **Email Reports**: Export data via email with CSV attachments
- **Admin Activity Tracking**: Individual admin performance monitoring
- **Real-time Dashboard**: Live status updates and duration tracking
- **Payment Management**: Online/Offline payment tracking with UPI integration

## System Requirements
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache/Nginx
- **Extensions**: PDO, cURL, OpenSSL
- **Composer**: For PHPMailer (optional)

## Installation Guide

### Step 1: Database Setup

1. **Upload Files**: Upload all files to your hosting directory
2. **Database Creation**: 
   - Go to your hosting panel (cPanel/Hostinger Panel)
   - Create a new MySQL database
   - Import the `database_setup.sql` file OR run the SQL commands manually

3. **Database Configuration**:
   - Edit `config/database.php`
   - Update your database credentials:
   ```php
   private $host = 'localhost';           // Your DB host
   private $db_name = 'your_database';    // Your DB name
   private $username = 'your_username';   // Your DB username
   private $password = 'your_password';   // Your DB password
   ```

### Step 2: File Permissions
Set proper permissions for directories:
```bash
chmod 755 exports/
chmod 644 *.php
chmod 644 assets/*
```

### Step 3: Initial Login
- **Owner Login**: username = `owner`, password = `admin123`
- **Admin Login**: username = `admin1` to `admin6`, password = `admin123`

**⚠️ IMPORTANT**: Change default passwords immediately after first login!

## SMS API Configuration

### Supported SMS Providers
The system supports multiple SMS providers. Configure in Owner Settings:

#### 1. TextLocal (Recommended)
- **API URL**: `https://api.textlocal.in/send/`
- **API Key**: Get from TextLocal dashboard
- **Sender ID**: 6-character sender ID (optional)

#### 2. MSG91
- **API URL**: `https://api.msg91.com/api/sendhttp.php`
- **API Key**: Your MSG91 auth key
- **Sender ID**: Your approved sender ID

#### 3. Fast2SMS
- **API URL**: `https://www.fast2sms.com/dev/bulkV2`
- **API Key**: Your Fast2SMS authorization key
- **Sender ID**: Your sender ID

#### 4. Custom SMS Provider
Edit `includes/sms_functions.php` to add your SMS provider:

```php
// Example for custom provider
$post_data = [
    'api_key' => $api_key,
    'to' => $mobile,
    'message' => $message,
    'sender' => $sender_id
];
```

### SMS Configuration Steps
1. Login as Owner
2. Go to Settings → SMS Configuration
3. Enter your SMS provider details:
   - Hotel Name
   - SMS API URL
   - SMS API Key
   - SMS Sender ID (if required)
4. Test SMS configuration with your mobile number
5. Save settings

### SMS Message Templates
The system sends automatic SMS for:

- **Booking Confirmation**: "Dear {name}, your room {room} booked successfully at {time} at {hotel}. Thank you!"
- **Checkout**: "Dear {name}, checkout from {room} completed at {hotel}. Thank you for your visit! Please visit again."
- **Cancellation**: "Dear {name}, your booking for {room} at {hotel} has been cancelled. Thank you."
- **Advance Booking**: "Dear {name}, your advance booking for {room} on {date} at {hotel} confirmed. Thank you!"

## Email Configuration (SMTP)

### Gmail Setup (Recommended)
1. Enable 2-Factor Authentication on your Gmail account
2. Generate App Password:
   - Go to Google Account Settings
   - Security → 2-Step Verification → App passwords
   - Generate password for "Mail"
3. Use these settings:
   - **SMTP Host**: `smtp.gmail.com`
   - **SMTP Port**: `587`
   - **Username**: Your Gmail address
   - **Password**: Generated app password (not your Gmail password)
   - **Encryption**: TLS

### Other Email Providers
- **Outlook/Hotmail**: `smtp-mail.outlook.com`, Port 587, TLS
- **Yahoo**: `smtp.mail.yahoo.com`, Port 587, TLS
- **Custom SMTP**: Contact your hosting provider for SMTP details

### Email Configuration Steps
1. Login as Owner
2. Go to Settings → Email Configuration
3. Enter SMTP details
4. Test email configuration
5. Save settings

## PHPMailer Installation

### Option 1: Composer (Recommended)
```bash
composer require phpmailer/phpmailer
```

### Option 2: Manual Installation
1. Download PHPMailer from GitHub
2. Extract to `vendor/phpmailer/` directory
3. Update the include path in `includes/email_functions.php`

## Admin Features

### Booking Management
- **Create Booking**: Fill guest details including mobile, ID proof
- **Edit Details**: Only booking creator can edit Aadhar/License/Receipt
- **SMS Notifications**: Automatic SMS sent on booking actions
- **Real-time Status**: Live duration tracking and status updates

### Guest Information Fields
- **Name**: Required
- **Mobile**: Required (10 digits, 6-9 starting)
- **Aadhar Number**: Optional (12 digits)
- **Driving License**: Optional (minimum 8 characters)
- **Receipt Number**: Optional (for payment tracking)
- **Payment Mode**: Online/Offline selection

### Advanced Booking
- Same guest information fields as regular booking
- **Advance Date**: Future date selection
- **Advance Payment Mode**: Online/Offline tracking
- **Auto-conversion**: Convert to active booking on due date

## Owner Features

### Dashboard Analytics
- Real-time booking statistics
- Admin activity tracking
- Revenue monitoring
- Recent activities log

### Export & Reports
- **Date Range Filtering**: Custom date selection
- **Admin Filtering**: Individual admin performance
- **CSV Export**: Download detailed reports
- **Email Export**: Send reports via email
- **Comprehensive Data**: All guest information included

### Settings Management
- **SMS Configuration**: API settings and testing
- **Email Configuration**: SMTP settings and testing
- **Admin Management**: Add/edit/delete admin accounts
- **Resource Management**: Customize room/hall names
- **UPI Settings**: Payment gateway configuration

## Security Features

### Data Protection
- **CSRF Protection**: All forms protected against CSRF attacks
- **Input Sanitization**: All user inputs sanitized
- **SQL Injection Prevention**: Prepared statements used
- **Password Hashing**: Secure password storage
- **Session Management**: Secure session handling

### Access Control
- **Role-based Access**: Owner and Admin roles
- **Admin Restrictions**: Admins can only edit their own bookings
- **Emergency Login**: Owner emergency access
- **Activity Logging**: All actions logged with admin ID

## Troubleshooting

### Common Issues

#### SMS Not Working
1. Check SMS API credentials in settings
2. Verify mobile number format (10 digits, 6-9 starting)
3. Check SMS provider balance/credits
4. Test SMS configuration in settings
5. Check `sms_logs` table for error details

#### Email Not Working
1. Verify SMTP credentials
2. Check if Gmail App Password is used (not regular password)
3. Ensure firewall allows SMTP connections
4. Test email configuration in settings
5. Check `email_logs` table for error details

#### Database Connection Issues
1. Verify database credentials in `config/database.php`
2. Ensure database exists and user has proper permissions
3. Check if hosting provider requires specific host (not localhost)
4. Run `test_connection.php` to diagnose issues

#### Permission Errors
1. Set proper file permissions (755 for directories, 644 for files)
2. Ensure web server can write to `exports/` directory
3. Check PHP error logs for detailed error messages

### Error Logs
Check these locations for error details:
- **SMS Logs**: Database table `sms_logs`
- **Email Logs**: Database table `email_logs`
- **PHP Errors**: Server error logs
- **Database Errors**: MySQL error logs

## API Integration Examples

### SMS API Integration
```php
// Example for TextLocal
$post_data = [
    'apikey' => 'YOUR_API_KEY',
    'numbers' => '9876543210',
    'message' => 'Your booking confirmed!',
    'sender' => 'LPSTHT'
];

// Example for MSG91
$post_data = [
    'authkey' => 'YOUR_AUTH_KEY',
    'mobiles' => '9876543210',
    'message' => 'Your booking confirmed!',
    'sender' => 'LPSTHT',
    'route' => '4'
];
```

### Email SMTP Configuration
```php
// Gmail Configuration
$mail->Host = 'smtp.gmail.com';
$mail->Port = 587;
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password';
$mail->SMTPSecure = 'tls';
```

## Database Schema

### Key Tables
- **users**: Admin and owner accounts
- **resources**: Rooms and halls configuration
- **bookings**: All booking records with guest information
- **payments**: Payment tracking and UPI transactions
- **settings**: System configuration (SMS, Email, etc.)
- **sms_logs**: SMS delivery tracking
- **email_logs**: Email delivery tracking
- **booking_cancellations**: Cancellation tracking for reports

### Important Fields
- **bookings.client_mobile**: 10-digit mobile number (required)
- **bookings.client_aadhar**: 12-digit Aadhar number (optional)
- **bookings.client_license**: Driving license number (optional)
- **bookings.receipt_number**: Payment receipt tracking
- **bookings.payment_mode**: ONLINE/OFFLINE payment type
- **bookings.admin_id**: Tracks which admin created booking

## Backup & Maintenance

### Regular Backups
1. **Database Backup**: Export MySQL database regularly
2. **File Backup**: Backup all PHP files and uploads
3. **Settings Backup**: Export settings table for configuration backup

### Maintenance Tasks
1. **Clean Logs**: Regularly clean old SMS and email logs
2. **Update Passwords**: Change default passwords
3. **Monitor Storage**: Check database size and optimize if needed
4. **Test APIs**: Regularly test SMS and email configurations

## Support & Updates

### Getting Help
1. Check this README for common solutions
2. Review error logs for specific issues
3. Test individual components (SMS, Email, Database)
4. Contact your hosting provider for server-related issues

### System Updates
- Keep PHP and MySQL updated
- Monitor SMS provider API changes
- Update email settings if provider changes
- Regular security updates for dependencies

## License & Credits
L.P.S.T Bookings System - Custom Hotel Management Solution
Built with PHP, MySQL, and modern web technologies.

---

**Note**: This system is designed for hotel and hall booking management. Ensure you comply with local data protection laws when storing guest information.