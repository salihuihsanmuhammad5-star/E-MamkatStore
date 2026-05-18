<?php

// config.php
// Load environment variables from .env
$env = parse_ini_file(__DIR__ . '/.env');
if (!$env) {
    die('Missing .env file. Create it from .env.example.');
}

define('DB_HOST', $env['DB_HOST']);
define('DB_NAME', $env['DB_NAME']);
define('DB_USER', $env['DB_USER']);
define('DB_PASS', $env['DB_PASS']);
define('BASE_URL', $env['BASE_URL']);
define('GOOGLE_CLIENT_ID', $env['GOOGLE_CLIENT_ID']);
define('GOOGLE_CLIENT_SECRET', $env['GOOGLE_CLIENT_SECRET']);
define('GOOGLE_REDIRECT_URI', $env['GOOGLE_REDIRECT_URI']);
define('MAIL_HOST', $env['MAIL_HOST']);
define('MAIL_USERNAME', $env['MAIL_USERNAME']);
define('MAIL_PASSWORD', $env['MAIL_PASSWORD']);
define('MAIL_PORT', $env['MAIL_PORT']);
define('MAIL_ENCRYPTION', $env['MAIL_ENCRYPTION']);
// Monnify (Moniepoint) Payment Gateway
define('MONNIFY_API_KEY',       $env['MONNIFY_API_KEY']);
define('MONNIFY_SECRET_KEY',    $env['MONNIFY_SECRET_KEY']);
define('MONNIFY_CONTRACT_CODE', $env['MONNIFY_CONTRACT_CODE']);
define('MONNIFY_BASE_URL',      $env['MONNIFY_BASE_URL']);
// Flutterwave Payment Gateway
define('FLW_PUBLIC_KEY',     $env['FLW_PUBLIC_KEY']);
define('FLW_SECRET_KEY',     $env['FLW_SECRET_KEY']);
define('FLW_ENCRYPTION_KEY', $env['FLW_ENCRYPTION_KEY']);
define('FLW_BASE_URL',       $env['FLW_BASE_URL']);

// Database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Composer autoload
require_once __DIR__ . '/vendor/autoload.php';

