<?php
/**
 * Deployment Status Check
 * Verify that all systems are ready for production
 */

require_once __DIR__ . '/config.php';

// Get logger
$logger = new Logger();

class DeploymentStatus {
    private $checks = [];
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Check PHP version
     */
    public function checkPhpVersion() {
        $version = phpversion();
        $required = '8.1.0';
        $pass = version_compare($version, $required, '>=');
        
        $this->checks['PHP Version'] = [
            'status' => $pass ? 'OK' : 'FAIL',
            'current' => $version,
            'required' => ">= {$required}"
        ];
        
        return $pass;
    }
    
    /**
     * Check required PHP extensions
     */
    public function checkExtensions() {
        $required = ['pdo', 'json', 'curl', 'mbstring'];
        $this->checks['PHP Extensions'] = [];
        
        foreach ($required as $ext) {
            $installed = extension_loaded($ext);
            $this->checks['PHP Extensions'][$ext] = $installed ? 'OK' : 'MISSING';
        }
        
        return !in_array('MISSING', $this->checks['PHP Extensions']);
    }
    
    /**
     * Check database connection
     */
    public function checkDatabase() {
        try {
            require_once __DIR__ . '/db.php';
            
            // Test simple query
            $result = $pdo->query('SELECT 1');
            $this->checks['Database Connection'] = [
                'status' => 'OK',
                'type' => DB_TYPE,
                'host' => DB_TYPE === 'mysql' ? DB_HOST : 'SQLite'
            ];
            
            return true;
        } catch (Exception $e) {
            $this->checks['Database Connection'] = [
                'status' => 'FAIL',
                'error' => $e->getMessage()
            ];
            
            $this->logger->error('Database connection failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Check required directories
     */
    public function checkDirectories() {
        $required = [
            'logs' => LOG_PATH,
            'includes' => __DIR__ . '/includes',
            'api' => __DIR__ . '/api'
        ];
        
        $this->checks['Directories'] = [];
        
        foreach ($required as $name => $path) {
            $exists = is_dir($path);
            $writable = is_writable($path);
            
            $status = 'OK';
            if (!$exists) {
                $status = 'MISSING';
                mkdir($path, 0755, true);
            } elseif (!$writable && in_array($name, ['logs'])) {
                $status = 'NOT_WRITABLE';
                chmod($path, 0755);
            }
            
            $this->checks['Directories'][$name] = $status;
        }
        
        return !in_array('MISSING', $this->checks['Directories']);
    }
    
    /**
     * Check configuration
     */
    public function checkConfiguration() {
        $this->checks['Configuration'] = [
            'environment' => ENVIRONMENT,
            'debug_mode' => DEBUG_MODE ? 'ON' : 'OFF',
            'database_type' => DB_TYPE,
            'site_url' => SITE_URL
        ];
        
        return ENVIRONMENT === 'production' && !DEBUG_MODE;
    }
    
    /**
     * Check HTTPS (for production)
     */
    public function checkHttps() {
        if (ENVIRONMENT !== 'production') {
            $this->checks['HTTPS'] = ['status' => 'SKIPPED', 'environment' => 'development'];
            return true;
        }
        
        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $this->checks['HTTPS'] = [
            'status' => $isHttps ? 'OK' : 'WARNING',
            'message' => 'HTTPS should be enabled in production'
        ];
        
        return $isHttps;
    }
    
    /**
     * Run all checks
     */
    public function runAll() {
        $this->checkPhpVersion();
        $this->checkExtensions();
        $this->checkDatabase();
        $this->checkDirectories();
        $this->checkConfiguration();
        $this->checkHttps();
        
        return $this->getResults();
    }
    
    /**
     * Get check results
     */
    public function getResults() {
        return $this->checks;
    }
    
    /**
     * Check if all critical checks passed
     */
    public function isPassed() {
        foreach ($this->checks as $check) {
            if (is_array($check)) {
                foreach ($check as $key => $value) {
                    if (is_array($value) && isset($value['status']) && $value['status'] === 'FAIL') {
                        return false;
                    } elseif ($value === 'FAIL' || $value === 'MISSING') {
                        return false;
                    }
                }
            } elseif ($check === 'FAIL' || $check === 'MISSING') {
                return false;
            }
        }
        
        return true;
    }
}

// Run deployment checks
$status = new DeploymentStatus($logger);
$results = $status->runAll();
$passed = $status->isPassed();

// Log results
$logger->info('Deployment status check completed', [
    'passed' => $passed,
    'results' => $results
]);

// Return JSON or HTML based on request type
$acceptJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;

if ($acceptJson) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $passed ? 'OK' : 'FAILED',
        'timestamp' => date('Y-m-d H:i:s'),
        'environment' => ENVIRONMENT,
        'checks' => $results
    ], JSON_PRETTY_PRINT);
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Deployment Status</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            
            .container {
                background: white;
                border-radius: 10px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                max-width: 600px;
                width: 100%;
                overflow: hidden;
            }
            
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 30px;
                text-align: center;
            }
            
            .header h1 {
                font-size: 28px;
                margin-bottom: 10px;
            }
            
            .status-badge {
                display: inline-block;
                padding: 8px 16px;
                border-radius: 20px;
                font-weight: bold;
                font-size: 14px;
                background: rgba(255, 255, 255, 0.3);
            }
            
            .status-badge.passed {
                background: #4caf50;
            }
            
            .status-badge.failed {
                background: #f44336;
            }
            
            .content {
                padding: 30px;
            }
            
            .check-group {
                margin-bottom: 25px;
            }
            
            .check-title {
                font-weight: bold;
                font-size: 16px;
                color: #333;
                margin-bottom: 10px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .check-item {
                background: #f5f5f5;
                padding: 10px 15px;
                margin: 5px 0;
                border-radius: 5px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 14px;
            }
            
            .check-status {
                font-weight: bold;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 12px;
            }
            
            .status-ok {
                background: #c8e6c9;
                color: #2e7d32;
            }
            
            .status-fail {
                background: #ffcdd2;
                color: #c62828;
            }
            
            .status-warning {
                background: #fff9c4;
                color: #f57f17;
            }
            
            .status-missing {
                background: #ffccbc;
                color: #d84315;
            }
            
            .footer {
                background: #f5f5f5;
                padding: 15px 30px;
                text-align: center;
                font-size: 12px;
                color: #666;
                border-top: 1px solid #eee;
            }
            
            .icon {
                font-size: 18px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1><?php echo APP_NAME; ?></h1>
                <div class="status-badge <?php echo $passed ? 'passed' : 'failed'; ?>">
                    <?php echo $passed ? '✓ READY' : '✗ NOT READY'; ?>
                </div>
            </div>
            
            <div class="content">
                <?php foreach ($results as $category => $items): ?>
                    <div class="check-group">
                        <div class="check-title">
                            <span class="icon">📋</span>
                            <?php echo $category; ?>
                        </div>
                        
                        <?php if (is_array($items)): ?>
                            <?php foreach ($items as $key => $value): ?>
                                <div class="check-item">
                                    <span><?php echo is_numeric($key) ? $value : $key; ?></span>
                                    <?php if (is_array($value)): ?>
                                        <span class="check-status status-<?php echo strtolower($value['status'] ?? 'ok'); ?>">
                                            <?php echo $value['status'] ?? 'OK'; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="check-status status-<?php echo strtolower($value); ?>">
                                            <?php echo $value; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="check-item">
                                <span><?php echo $category; ?></span>
                                <span class="check-status status-ok"><?php echo $items; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="footer">
                <p>Last check: <?php echo date('Y-m-d H:i:s'); ?> | Environment: <?php echo ENVIRONMENT; ?></p>
            </div>
        </div>
    </body>
    </html>
    <?php
}
