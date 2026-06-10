<?php
/**
 * Quick Testing Suite for Comic Universe
 * Проверяет все основные функции приложения
 * 
 * Usage: php test.php
 * Access via browser: http://localhost:8000/test.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/myauth.php';

// Get test results
$results = [];
$passed = 0;
$failed = 0;

// ========== TEST FUNCTIONS ==========

function test($name, $condition, &$results, &$passed, &$failed) {
    if ($condition) {
        $results[] = [
            'name' => $name,
            'status' => 'PASS',
            'color' => 'green'
        ];
        $passed++;
    } else {
        $results[] = [
            'name' => $name,
            'status' => 'FAIL',
            'color' => 'red'
        ];
        $failed++;
    }
}

// ========== CONFIGURATION TESTS ==========

test('Config file exists', file_exists(__DIR__ . '/config.php'), $results, $passed, $failed);
test('Database file exists (SQLite)', DB_TYPE === 'sqlite' ? file_exists(DB_PATH) : true, $results, $passed, $failed);
test('Logs directory exists', is_dir(LOG_PATH), $results, $passed, $failed);
test('Config file readable', is_readable(__DIR__ . '/config.php'), $results, $passed, $failed);

// ========== DATABASE TESTS ==========

try {
    require_once __DIR__ . '/db.php';
    test('Database connection successful', isset($pdo) && $pdo instanceof PDO, $results, $passed, $failed);
    
    if (isset($pdo)) {
        $tableCount = $pdo->query('SELECT COUNT(*) FROM sqlite_master WHERE type="table"')->fetchColumn();
        test('Database tables created', $tableCount > 0, $results, $passed, $failed);
        
        $comicsCount = $pdo->query('SELECT COUNT(*) FROM comics')->fetchColumn();
        test('Comics table has data', $comicsCount > 0, $results, $passed, $failed);
        
        $categoriesCount = $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
        test('Categories table has data', $categoriesCount > 0, $results, $passed, $failed);
        
        $usersTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetch();
        test('Users table exists', $usersTable !== false, $results, $passed, $failed);
    }
} catch (Exception $e) {
    test('Database connection', false, $results, $passed, $failed);
    logMessage('Database test error: ' . $e->getMessage(), 'error');
}

// ========== FILE & DIRECTORY TESTS ==========

$files = [
    'index.php' => 'Main page',
    'login.php' => 'Login page',
    'register.php' => 'Register page',
    'cart.php' => 'Cart page',
    'about.php' => 'About page',
    'logout.php' => 'Logout script',
    'includes/auth.php' => 'Auth functions',
    'api/read.php' => 'API read endpoint',
    'api/get.php' => 'API get endpoint',
    'api/create.php' => 'API create endpoint',
    'api/update.php' => 'API update endpoint',
    'api/delete.php' => 'API delete endpoint',
    'styles.css' => 'Stylesheet',
];

foreach ($files as $file => $name) {
    test($name . ' exists', file_exists(__DIR__ . '/' . $file), $results, $passed, $failed);
}

// ========== DIRECTORY PERMISSIONS TESTS ==========

test('Logs directory writable', is_writable(LOG_PATH), $results, $passed, $failed);

if (defined('UPLOAD_DIR') && is_dir(UPLOAD_DIR)) {
    test('Upload directory writable', is_writable(UPLOAD_DIR), $results, $passed, $failed);
}

if (defined('CACHE_DIR') && is_dir(CACHE_DIR)) {
    test('Cache directory writable', is_writable(CACHE_DIR), $results, $passed, $failed);
}

// ========== FUNCTION TESTS ==========

test('isLoggedIn() function exists', function_exists('isLoggedIn'), $results, $passed, $failed);
test('requireLogin() function exists', function_exists('requireLogin'), $results, $passed, $failed);
test('sanitizeOutput() function exists', function_exists('sanitizeOutput'), $results, $passed, $failed);
test('sanitizeInput() function exists', function_exists('sanitizeInput'), $results, $passed, $failed);
test('hashPassword() function exists', function_exists('hashPassword'), $results, $passed, $failed);
test('verifyPassword() function exists', function_exists('verifyPassword'), $results, $passed, $failed);

// ========== PHP CONFIGURATION TESTS ==========

test('PHP version >= 8.0', version_compare(PHP_VERSION, '8.0.0', '>='), $results, $passed, $failed);
test('PDO extension loaded', extension_loaded('pdo'), $results, $passed, $failed);

if (DB_TYPE === 'sqlite') {
    test('PDO SQLite driver available', extension_loaded('pdo_sqlite'), $results, $passed, $failed);
} elseif (DB_TYPE === 'mysql') {
    test('PDO MySQL driver available', extension_loaded('pdo_mysql'), $results, $passed, $failed);
}

test('JSON extension loaded', extension_loaded('json'), $results, $passed, $failed);
test('SPL extension loaded', extension_loaded('spl'), $results, $passed, $failed);

// ========== SECURITY TESTS ==========

test('htmlspecialchars() available', function_exists('htmlspecialchars'), $results, $passed, $failed);
test('password_hash() available', function_exists('password_hash'), $results, $passed, $failed);
test('password_verify() available', function_exists('password_verify'), $results, $passed, $failed);

// ========== SESSION TESTS ==========

test('Session support enabled', function_exists('session_start'), $results, $passed, $failed);

// ========== GENERATE HTML OUTPUT ==========

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comic Universe - Test Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 40px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-value {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-passed .stat-value {
            color: #28a745;
        }
        
        .stat-failed .stat-value {
            color: #dc3545;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .results {
            padding: 40px;
        }
        
        .result-group {
            margin-bottom: 30px;
        }
        
        .result-group-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 15px;
        }
        
        .result-item {
            display: flex;
            align-items: center;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 5px;
            background: #f8f9fa;
            border-left: 4px solid #e9ecef;
        }
        
        .result-item.pass {
            border-left-color: #28a745;
            background: #f1f8f6;
        }
        
        .result-item.fail {
            border-left-color: #dc3545;
            background: #fdf6f7;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 15px;
            min-width: 60px;
            text-align: center;
        }
        
        .status-badge.pass {
            background: #28a745;
            color: white;
        }
        
        .status-badge.fail {
            background: #dc3545;
            color: white;
        }
        
        .result-name {
            flex: 1;
            color: #333;
            font-size: 14px;
        }
        
        .footer {
            background: #f8f9fa;
            padding: 20px 40px;
            border-top: 1px solid #e9ecef;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        
        .progress-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Test Report</h1>
            <p>Comic Universe Application Testing</p>
        </div>
        
        <div class="stats">
            <div class="stat stat-passed">
                <div class="stat-value"><?= $passed ?></div>
                <div class="stat-label">Tests Passed</div>
            </div>
            <div class="stat stat-failed">
                <div class="stat-value"><?= $failed ?></div>
                <div class="stat-label">Tests Failed</div>
            </div>
        </div>
        
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?= ($passed / ($passed + $failed) * 100) ?>%"></div>
        </div>
        
        <div class="results">
            <?php
            $categories = [
                'Configuration' => ['Config file exists', 'Database file exists'],
                'Database' => ['Database connection successful', 'Database tables created', 'Comics table has data'],
                'Files & Directories' => ['Main page exists', 'Login page exists', 'Stylesheet exists'],
                'Security' => ['htmlspecialchars() available', 'password_hash() available'],
                'PHP Configuration' => ['PHP version >= 8.0', 'PDO extension loaded', 'JSON extension loaded'],
            ];
            
            foreach ($categories as $category => $keywords) {
                $categoryResults = array_filter($results, function($r) use ($keywords) {
                    foreach ($keywords as $k) {
                        if (strpos($r['name'], $k) !== false) return true;
                    }
                    return false;
                });
                
                if (count($categoryResults) > 0) {
                    echo '<div class="result-group">';
                    echo '<div class="result-group-title">' . $category . '</div>';
                    
                    foreach ($categoryResults as $result) {
                        $statusClass = $result['status'] === 'PASS' ? 'pass' : 'fail';
                        echo '<div class="result-item ' . $statusClass . '">';
                        echo '<span class="status-badge ' . $statusClass . '">' . $result['status'] . '</span>';
                        echo '<div class="result-name">' . $result['name'] . '</div>';
                        echo '</div>';
                    }
                    
                    echo '</div>';
                }
            }
            ?>
        </div>
        
        <div class="footer">
            <p>Generated at <?= date('Y-m-d H:i:s') ?> | Total Tests: <?= count($results) ?></p>
            <p style="margin-top: 10px;">Remove this file (test.php) before deploying to production!</p>
        </div>
    </div>
</body>
</html>
