<?php
/**
 * Authentication and Authorization Functions
 * Handles user login, registration, session management and role-based access control
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']) && isset($_SESSION['role']);
}

/**
 * Redirect to login if user is not logged in
 * Prevents access to protected pages
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit();
    }
}

/**
 * Check if current user has required role
 * @param string $requiredRole - 'admin', 'manager', 'user'
 * @return bool
 */
function hasRole($requiredRole) {
    return isLoggedIn() && $_SESSION['role'] === $requiredRole;
}

/**
 * Redirect if user doesn't have required role
 * @param string $requiredRole - 'admin', 'manager', 'user'
 */
function requireRole($requiredRole) {
    requireLogin();
    if ($_SESSION['role'] !== $requiredRole) {
        http_response_code(403);
        die('Access Denied: You do not have permission to access this page.');
    }
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current username
 * @return string|null
 */
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

/**
 * Get current user role
 * @return string|null
 */
function getCurrentRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Logout user - destroy session
 */
function logout() {
    session_destroy();
    header('Location: /index.php');
    exit();
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * @param string $password
 * @return bool
 */
function validatePassword($password) {
    // At least 6 characters
    return strlen($password) >= 6;
}

/**
 * Validate username
 * @param string $username
 * @return bool
 */
function validateUsername($username) {
    // 3-20 characters, alphanumeric and underscore only
    return strlen($username) >= 3 && strlen($username) <= 20 && preg_match('/^[a-zA-Z0-9_]+$/', $username);
}

/**
 * Hash password using bcrypt
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify password against hash
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Sanitize output to prevent XSS attacks
 * @param string $data
 * @return string
 */
function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate input data - remove extra spaces, trim
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    return trim(stripslashes($data));
}
?>
