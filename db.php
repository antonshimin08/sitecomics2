<?php
/**
 * Database connection and initialization
 * Supports both SQLite (development) and MySQL (production)
 */

require_once __DIR__ . '/config.php';

/**
 * Database connection factory
 */
class DatabaseConnection {
    private static $instance = null;
    private $pdo;
    private $logger;
    
    private function __construct() {
        $this->logger = new Logger();
        $this->connect();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     */
    public function getPDO() {
        return $this->pdo;
    }
    
    /**
     * Establish database connection
     */
    private function connect() {
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4" // Принудительно ставим правильную кодировку для MySQL
            ];
            
            // На хостинге всегда переключаемся на MySQL
            $dbType = 'mysql'; 
            
            if ($dbType === 'sqlite') {
                $dbPath = defined('DB_PATH') ? DB_PATH : './database.sqlite';
                if (!file_exists($dbPath)) {
                    file_put_contents($dbPath, '');
                }
                $dsn = 'sqlite:' . $dbPath;
                $this->pdo = new PDO($dsn, null, null, $options);
                $this->pdo->exec('PRAGMA foreign_keys = ON');
                $this->logger->info('SQLite database connected', ['path' => $dbPath]);
                
            } elseif ($dbType === 'mysql') {
                // Жёстко прописываем твои параметры от InfinityFree, чтобы не зависеть от сбоев в config.php
                $host    = 'sql309.infinityfree.com';
                $port    = 3306;
                $db_name = 'if0_42045828_comic_universe';
                $user    = 'if0_42045828';
                $pass    = '8j2H2aOq8yxaS'; 
                
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                    $host,
                    $port,
                    $db_name
                );
                
                $this->pdo = new PDO($dsn, $user, $pass, $options);
                
                $this->logger->info('MySQL database connected', [
                    'host' => $host,
                    'database' => $db_name
                ]);
                
            } else {
                throw new Exception('Invalid database type: ' . $dbType);
            }
            
        } catch (PDOException $e) {
            $this->logger->critical('Database connection failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            
            // Выкидываем ошибку на экран, чтобы сразу видеть, если пароль указан неверно
            throw $e;
        }
    }
}

// Initialize database connection
try {
    $db = DatabaseConnection::getInstance();
    $pdo = $db->getPDO();
} catch (Exception $e) {
    $logger = new Logger();
    $logger->critical('Failed to initialize database', ['error' => $e->getMessage()]);
    
    // Показываем детальное описание проблемы на этапе отладки
    echo "<div style='color:red; background:#fee; padding:15px; border:1px solid #fcc; font-family:sans-serif;'>";
    echo "<h3>Ошибка подключения к базе данных!</h3>";
    echo "<p>Проверь правильность пароля в файле <b>db.php</b>.</p>";
    echo "<p><b>Текст ошибки:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    exit;
}