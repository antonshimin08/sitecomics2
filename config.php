<?php
/**
 * Configuration file for Comic Universe e-commerce application
 * Contains database settings, environment variables, and debug options
 */

// ========== ENVIRONMENT SETTINGS ==========
// 'development' | 'production' | 'staging'
define('ENVIRONMENT', 'development');

// ========== DATABASE CONFIGURATION ==========
// Database type: 'sqlite' or 'mysql'
define('DB_TYPE', 'sqlite');

// SQLite configuration (for development/small projects)
define('DB_PATH', __DIR__ . '/database.sqlite');

// MySQL configuration (for production)
define('DB_HOST', 'localhost');
define('DB_NAME', 'comic_universe');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

// ========== APPLICATION SETTINGS ==========
// Application name
define('APP_NAME', 'Comic Universe');
define('APP_VERSION', '1.0.0');

// Site URL (with trailing slash)
define('SITE_URL', 'http://localhost:8000/');

// ========== SECURITY SETTINGS ==========
// Enable/disable debug mode
define('DEBUG_MODE', ENVIRONMENT === 'development');

// Log errors to file
define('LOG_ERRORS', true);
define('LOG_PATH', __DIR__ . '/logs/');

// Session timeout (in seconds)
define('SESSION_TIMEOUT', 3600); // 1 hour

// Password hashing algorithm
define('PASSWORD_ALGO', PASSWORD_BCRYPT);

// ========== API SETTINGS ==========
// Allow CORS requests
define('ENABLE_CORS', false);
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
