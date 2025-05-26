<?php
// Security constants
define('SESSION_TIMEOUT', 3600); // 60 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 30);
define('MIN_PASSWORD_LENGTH', 8);
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour
define('PASSWORD_RESET_EXPIRY', 3600); // 1 hour
define('EMAIL_VERIFICATION_EXPIRY', 86400); // 24 hours

// This must be the VERY FIRST thing in your config.php file
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']), // Enable if HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}
// Display errors during development (disable in production)
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Define base URL (adjust for your environment)
define('BASE_URL', 'http://localhost/inventory_pos/');

// Set default timezone
date_default_timezone_set('Asia/Dhaka');



// Force HTTPS in production
if ($_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1') {
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
        header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit();
    }
}
// Regenerate session ID periodically for security
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Include helper files
//require_once __DIR__ . '/includes/auth_functions.php';
//require_once __DIR__ . '/includes/db_helper.php';
//require_once __DIR__ . '/includes/email_helper.php';