<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'online_shop');
define('DB_USER', 'root');
define('DB_PASS', '');

define('SITE_URL', 'http://localhost/online-shop');
define('UPLOAD_PATH', __DIR__ . '/../uploads');

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('SMTP_HOST', 'smtp.example.com');
define('SMTP_USERNAME', 'your-email@example.com');
define('SMTP_PASSWORD', 'your-smtp-password');
define('SMTP_PORT', 587);
define('SMTP_FROM_EMAIL', 'noreply@yourstore.com');
define('SMTP_FROM_NAME', 'Your Store');

// File Upload Settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_PATH_RECEIPTS', __DIR__ . '/../uploads/receipts/');
 