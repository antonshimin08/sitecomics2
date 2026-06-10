<?php
/**
 * Database Initialization Script
 * Run this script to set up the database for production
 * Usage: php setup-db.php
 */

require_once __DIR__ . '/config.php';

class DatabaseSetup {
    private $pdo;
    private $logger;
    
    public function __construct() {
        $this->logger = new Logger();
    }
    
    /**
     * Initialize database
     */
    public function init() {
        try {
            echo "🔄 Initializing database...\n";
            
            // Create database connection
            $this->createConnection();
            echo "✓ Connected to " . DB_TYPE . " database\n";
            
            // Load and execute schema
            $this->loadSchema();
            echo "✓ Database schema created/updated\n";
            
            // Verify tables
            $tables = $this->getTables();
            echo "✓ Found " . count($tables) . " tables: " . implode(', ', $tables) . "\n";
            
            $this->logger->info('Database initialization completed successfully');
            echo "\n✅ Database initialization successful!\n";
            
            return true;
        } catch (Exception $e) {
            $this->logger->error('Database initialization failed', ['error' => $e->getMessage()]);
            echo "\n❌ Error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Create database connection
     */
    private function createConnection() {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        if (DB_TYPE === 'sqlite') {
            if (!file_exists(DB_PATH)) {
                file_put_contents(DB_PATH, '');
            }
            
            $dsn = 'sqlite:' . DB_PATH;
            $this->pdo = new PDO($dsn, null, null, $options);
            $this->pdo->exec('PRAGMA foreign_keys = ON');
            
        } elseif (DB_TYPE === 'mysql') {
            // Create database if it doesn't exist
            $dsn = sprintf(
                'mysql:host=%s;port=%d;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_CHARSET
            );
            
            $tempPdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $tempPdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
            
            // Now connect to the database
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } else {
            throw new Exception('Invalid database type: ' . DB_TYPE);
        }
    }
    
    /**
     * Load and execute schema
     */
    private function loadSchema() {
        $schemaFile = __DIR__ . '/schema.sql';
        
        if (!file_exists($schemaFile)) {
            throw new Exception("Schema file not found: {$schemaFile}");
        }
        
        $schema = file_get_contents($schemaFile);
        
        // Execute schema
        if (DB_TYPE === 'mysql') {
            // For MySQL, execute statements separated by semicolon
            $statements = array_filter(explode(';', $schema));
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    $this->pdo->exec($statement);
                }
            }
        } else {
            // For SQLite
            $this->pdo->exec($schema);
        }
    }
    
    /**
     * Get list of tables
     */
    private function getTables() {
        if (DB_TYPE === 'sqlite') {
            $result = $this->pdo->query(
                "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"
            )->fetchAll();
        } else {
            $result = $this->pdo->query(
                "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='" . DB_NAME . "'"
            )->fetchAll();
        }
        
        return array_column($result, DB_TYPE === 'sqlite' ? 'name' : 'TABLE_NAME');
    }
    
    /**
     * Verify database structure
     */
    public function verify() {
        try {
            echo "🔍 Verifying database structure...\n";
            
            $this->createConnection();
            $tables = $this->getTables();
            
            if (empty($tables)) {
                echo "⚠️  No tables found. Run 'php setup-db.php' to initialize.\n";
                return false;
            }
            
            foreach ($tables as $table) {
                echo "  ✓ Table: {$table}\n";
            }
            
            echo "\n✅ Database verification successful!\n";
            return true;
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// Run setup
if (php_sapi_name() === 'cli') {
    $setup = new DatabaseSetup();
    
    if (isset($argv[1]) && $argv[1] === 'verify') {
        $setup->verify();
    } else {
        $setup->init();
    }
} else {
    // Web interface
    $setup = new DatabaseSetup();
    
    if ($_GET['action'] ?? '' === 'verify') {
        header('Content-Type: application/json');
        $result = $setup->verify();
        echo json_encode(['success' => $result]);
    } else {
        header('Content-Type: application/json');
        $result = $setup->init();
        echo json_encode(['success' => $result]);
    }
}
