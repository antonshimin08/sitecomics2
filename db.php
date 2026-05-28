<?php
/**
 * Database connection and initialization
 * Supports both SQLite (development) and MySQL (production)
 */

require_once __DIR__ . '/config.php';

// PDO options
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // Initialize database connection based on configuration
    if (DB_TYPE === 'sqlite') {
        // SQLite connection (development/small projects)
        if (!file_exists(DB_PATH)) {
            file_put_contents(DB_PATH, '');
        }
        
        $dsn = 'sqlite:' . DB_PATH;
        $pdo = new PDO($dsn, null, null, $options);
        
        // Enable foreign keys in SQLite
        $pdo->exec('PRAGMA foreign_keys = ON');
        
    } elseif (DB_TYPE === 'mysql') {
        // MySQL connection (production)
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
    } else {
        throw new Exception('Invalid database type: ' . DB_TYPE);
    }
    
    // Load and execute schema if database is empty
    if (DB_TYPE === 'sqlite') {
        $tableCount = $pdo->query('SELECT COUNT(*) FROM sqlite_master WHERE type="table"')->fetchColumn();
        if ($tableCount == 0) {
            $schema = file_get_contents(__DIR__ . '/schema.sql');
            $pdo->exec($schema);
        }
    }
    
} catch (PDOException $e) {
    logMessage('Database connection error: ' . $e->getMessage(), 'error');
    
    if (DEBUG_MODE) {
        die('Database Error: ' . htmlspecialchars($e->getMessage()));
    } else {
        die('Database connection failed. Please contact administrator.');
    }
}
