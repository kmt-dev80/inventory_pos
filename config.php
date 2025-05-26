<?php
// Display errors during development (disable in production)
// ini_set('display_errors', 'Off/0');
ini_set('display_errors', 1); // Show errors on screen
ini_set('log_errors', 1); // Also log errors to file
ini_set('error_log', __DIR__ . '/error.log'); // Log file path

// Define base URL (adjust for your environment)
define('BASE_URL', '/inventory_pos/');

// Set default timezone
date_default_timezone_set('Asia/Dhaka');
// Database connection
$conn = new mysqli("localhost", "root", "", "inventory");

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed. Please try again later.");
}

// Set charset to utf8
$conn->set_charset("utf8");