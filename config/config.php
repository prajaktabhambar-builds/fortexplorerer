<?php
/**
 * FORT EXPLORER - Configuration File
 * Digital Tourism & Heritage Exploration Platform
 */

// Report all errors in development, but avoid display in production
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set default timezone to Indian Standard Time
date_default_timezone_set('Asia/Kolkata');

// Application Constants
define('APP_NAME', 'FORT EXPLORER');
define('APP_TAGLINE', 'Discover History. Explore Heritage. Create Memories.');
define('APP_VERSION', '1.0.0');

// Database credentials with environment variable support for cloud deployment (Vercel, Railway, Render)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: null);
define('DB_NAME', getenv('DB_NAME') ?: 'fort_explorer');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

// Base Paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOADS_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'uploads');

// Start secure session if not active and running in web context
if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    // Configure session cookie params for security
    $cookieParams = [
        'lifetime' => 86400 * 7, // 7 days
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    session_set_cookie_params($cookieParams);
    session_start();
}

// Compute Base URL dynamically
if (!defined('BASE_URL')) {
    if (php_sapi_name() === 'cli') {
        define('BASE_URL', '/');
    } else {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
        
        // Find the relative directory path from document root
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $dirName = str_replace('\\', '/', dirname($scriptName));
        
        // Normalize path to root of FortExplorer
        if (strpos($dirName, '/admin') !== false) {
            $dirName = str_replace('\\', '/', dirname($dirName));
        }
        if (strpos($dirName, '/api') !== false) {
            $dirName = str_replace('\\', '/', dirname($dirName));
        }
        $dirName = trim(str_replace('\\', '/', $dirName), '/');
        
        $baseUrl = $protocol . $host . ($dirName === '' ? '' : '/' . $dirName) . '/';
        define('BASE_URL', $baseUrl);
    }
}
