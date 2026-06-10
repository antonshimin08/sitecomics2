<?php
/**
 * Configuration file for Comic Universe e-commerce application
 * Loads environment variables from .env file
 */

// Load environment variables from .env file
require_once __DIR__ . '/includes/Environment.php';

try {
    Environment::load(__DIR__ . '/.env');
} catch (Exception $e) {
    die('Error loading environment configuration: ' . $e->getMessage());
}

// ========== ENVIRONMENT SETTINGS ==========
define('ENVIRONMENT', Environment::get('ENVIRONMENT', 'development'));
define('DEBUG_MODE', Environment::getBoolean('DEBUG_MODE', false));

// ========== DATABASE CONFIGURATION ==========
define('DB_TYPE', Environment::get('DB_TYPE', 'sqlite'));
define('DB_PATH', Environment::get('DB_PATH', __DIR__ . '/database.sqlite'));
define('DB_HOST', Environment::get('DB_HOST', 'localhost'));
define('DB_NAME', Environment::get('DB_NAME', 'comic_universe'));
define('DB_USER', Environment::get('DB_USER', 'root'));
define('DB_PASS', Environment::get('DB_PASS', ''));
define('DB_PORT', Environment::getInteger('DB_PORT', 3306));
define('DB_CHARSET', Environment::get('DB_CHARSET', 'utf8mb4'));

// ========== APPLICATION SETTINGS ==========
define('APP_NAME', Environment::get('APP_NAME', 'Comic Universe'));
define('APP_VERSION', Environment::get('APP_VERSION', '1.0.0'));
define('SITE_URL', Environment::get('SITE_URL', 'http://comic-universe.xo.je/'));

// ========== SECURITY SETTINGS ==========
define('LOG_ERRORS', Environment::getBoolean('LOG_ERRORS', true));
define('LOG_PATH', Environment::get('LOG_PATH', __DIR__ . '/logs/'));
define('SESSION_TIMEOUT', Environment::getInteger('SESSION_TIMEOUT', 3600));
define('PASSWORD_ALGO', PASSWORD_BCRYPT);

// ========== API SETTINGS ==========
define('ENABLE_CORS', Environment::getBoolean('ENABLE_CORS', false));

// ========== ERROR HANDLING ==========
// Initialize error and exception handlers
require_once __DIR__ . '/includes/Logger.php';
require_once __DIR__ . '/includes/ErrorHandler.php';

if (DEBUG_MODE === false) {
    // Production error handling
    ErrorHandler::register();
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
} else {
    // Development error handling
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
}
define('ALLOWED_ORIGINS', ['localhost:8000']);

// API rate limiting (requests per minute)
define('RATE_LIMIT', 60);

// ========== PAGINATION ==========
define('ITEMS_PER_PAGE', 8);
define('API_DEFAULT_LIMIT', 10);
define('API_MAX_LIMIT', 100);

// ========== FILE UPLOAD SETTINGS ==========
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// ========== EMAIL SETTINGS (for notifications) ==========
define('MAIL_FROM', 'noreply@comicuniverse.com');
define('MAIL_FROM_NAME', 'Comic Universe Store');
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');

// ========== CACHE SETTINGS ==========
define('ENABLE_CACHE', ENVIRONMENT === 'production');
define('CACHE_DIR', __DIR__ . '/cache/');
define('CACHE_TTL', 3600); // 1 hour

// ========== ERROR HANDLING ==========
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// ========== CREATE NECESSARY DIRECTORIES ==========
if (LOG_ERRORS && !is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}

if (ENABLE_CACHE && !is_dir(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0755, true);
}

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// ========== HELPER FUNCTIONS ==========

/**
 * Get configuration value with fallback
 * @param string $key Configuration key (dot notation)
 * @param mixed $default Default value if not found
 * @return mixed
 */
function config($key, $default = null) {
    $parts = explode('.', $key);
    $value = null;
    
    // Try to get constant
    $constant = strtoupper(implode('_', $parts));
    if (defined($constant)) {
        return constant($constant);
    }
    
    return $default;
}

/**
 * Log message to file
 * @param string $message Message to log
 * @param string $level Log level: 'info', 'warning', 'error'
 */
function logMessage($message, $level = 'info') {
    if (!LOG_ERRORS) return;
    
    $timestamp = date('Y-m-d H:i:s');
    $logFile = LOG_PATH . date('Y-m-d') . '.log';
    $logEntry = "[$timestamp] [$level] $message\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Get site URL
 * @param string $path Path to append (without leading slash)
 * @return string Full URL
 */
function siteUrl($path = '') {
    return SITE_URL . ltrim($path, '/');
}

/**
 * Check if application is in production
 * @return bool
 */
function isProduction() {
    return ENVIRONMENT === 'production';
}

/**
 * Check if debug mode is enabled
 * @return bool
 */
function isDebug() {
    return DEBUG_MODE;
}

?>
