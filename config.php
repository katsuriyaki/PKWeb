<?php
// Secure configuration file

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'pkweb');
define('DB_USER', 'pkweb');
define('DB_PASS', 'pkweb123');

// Session security configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 1 : 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_only_cookies', 1);

session_start();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Regenerate session ID periodically
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 300) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Error reporting - disable in production
error_reporting(0);
ini_set('display_errors', 0);

// Database connection function
function getDB() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        die("Database connection failed");
    }
}

// Security: Escape output
function htmlEncode($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['authenticated']);
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /auth/index.php');
        exit;
    }
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Require admin — redirects to index if not logged in, dashboard if not admin
function requireAdmin() {
    if (!isLoggedIn()) {
        header('Location: /auth/index.php');
        exit;
    }
    if (!isAdmin()) {
        header('Location: /dashboard.php');
        exit;
    }
}