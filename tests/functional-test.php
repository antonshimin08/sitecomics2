<?php
/**
 * API and Functionality Testing Script
 * Comprehensive tests for all application endpoints and features
 * Usage: php tests/functional-test.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

class FunctionalTest {
    private $baseUrl;
    private $testResults = [];
    private $passed = 0;
    private $failed = 0;
    private $logger;
    
    public function __construct($baseUrl = 'http://localhost:8000') {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->logger = new Logger();
    }
    
    /**
     * Run all tests
     */
    public function runAll() {
        echo "🧪 Running Functional Tests\n";
        echo "================================\n\n";
        
        $this->testDatabaseConnection();
        $this->testPageLoading();
        $this->testApiEndpoints();
        $this->testAuthentication();
        $this->testFormValidation();
        
        $this->printResults();
    }
    
    /**
     * Test database connection
     */
    private function testDatabaseConnection() {
        echo "📊 Database Tests\n";
        
        try {
            $stmt = $pdo->query('SELECT 1');
            $this->pass('Database connection');
        } catch (Exception $e) {
            $this->fail('Database connection', $e->getMessage());
        }
        
        // Check tables
        $tables = ['users', 'comics', 'categories', 'orders', 'order_items'];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
                $this->pass("Table: $table exists");
            } catch (Exception $e) {
                $this->fail("Table: $table", $e->getMessage());
            }
        }
        
        echo "\n";
    }
    
    /**
     * Test page loading
     */
    private function testPageLoading() {
        echo "📄 Page Loading Tests\n";
        
        $pages = [
            'index.php' => 'Home page',
            'login.php' => 'Login page',
            'register.php' => 'Registration page',
            'about.php' => 'About page',
            'cart.php' => 'Cart page',
            'deploy-status.php' => 'Deployment status'
        ];
        
        foreach ($pages as $page => $label) {
            $response = $this->makeRequest($page);
            
            if ($response['code'] === 200) {
                $this->pass($label);
            } else {
                $this->fail($label, "HTTP {$response['code']}");
            }
        }
        
        echo "\n";
    }
    
    /**
     * Test API endpoints
     */
    private function testApiEndpoints() {
        echo "🔌 API Endpoint Tests\n";
        
        // Test GET comics
        $response = $this->makeRequest('api/get.php?type=comics');
        if ($response['code'] === 200) {
            $this->pass('GET /api/get.php - Comics list');
        } else {
            $this->fail('GET /api/get.php - Comics list', "HTTP {$response['code']}");
        }
        
        // Test GET categories
        $response = $this->makeRequest('api/get.php?type=categories');
        if ($response['code'] === 200) {
            $this->pass('GET /api/get.php - Categories');
        } else {
            $this->fail('GET /api/get.php - Categories', "HTTP {$response['code']}");
        }
        
        // Test API headers
        $response = $this->makeRequest('api/get.php?type=comics');
        if (isset($response['headers']['Content-Type']) && strpos($response['headers']['Content-Type'], 'json') !== false) {
            $this->pass('API returns JSON content type');
        } else {
            $this->fail('API returns JSON content type');
        }
        
        echo "\n";
    }
    
    /**
     * Test authentication
     */
    private function testAuthentication() {
        echo "🔐 Authentication Tests\n";
        
        // Check if login page requires POST
        $response = $this->makeRequest('api/get.php?type=auth', 'GET', []);
        if ($response['code'] !== 500) {
            $this->pass('Auth endpoint accessible');
        } else {
            $this->fail('Auth endpoint accessible');
        }
        
        // Test session handling
        session_start();
        if (isset($_SESSION) && is_array($_SESSION)) {
            $this->pass('Session handling');
        } else {
            $this->fail('Session handling');
        }
        
        echo "\n";
    }
    
    /**
     * Test form validation
     */
    private function testFormValidation() {
        echo "✅ Form Validation Tests\n";
        
        // Test email validation
        $validEmails = [
            'user@example.com' => true,
            'test.user@domain.co.uk' => true,
            'invalid.email@' => false,
            'notanemail' => false
        ];
        
        foreach ($validEmails as $email => $shouldBeValid) {
            $isValid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
            
            if ($isValid === $shouldBeValid) {
                $this->pass("Email validation: $email");
            } else {
                $this->fail("Email validation: $email");
            }
        }
        
        // Test password strength
        $passwords = [
            '123456' => true,  // At least 6 chars
            'short' => false,
            'validPassword123' => true
        ];
        
        foreach ($passwords as $pass => $valid) {
            $isValid = strlen($pass) >= 6;
            
            if ($isValid === $valid) {
                $this->pass("Password validation: " . str_repeat('*', strlen($pass)));
            } else {
                $this->fail("Password validation: " . str_repeat('*', strlen($pass)));
            }
        }
        
        echo "\n";
    }
    
    /**
     * Make HTTP request
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        
        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'timeout' => 5,
                'ignore_errors' => true
            ]
        ]);
        
        try {
            $response = @file_get_contents($url, false, $context);
            $statusCode = isset($http_response_header[0]) ? 
                (int)explode(' ', $http_response_header[0])[1] : 0;
            
            return [
                'code' => $statusCode,
                'body' => $response,
                'headers' => $http_response_header ?? []
            ];
        } catch (Exception $e) {
            return [
                'code' => 0,
                'body' => '',
                'error' => $e->getMessage(),
                'headers' => []
            ];
        }
    }
    
    /**
     * Record passed test
     */
    private function pass($testName) {
        $this->passed++;
        echo "  ✅ $testName\n";
        $this->logger->info("Test passed: $testName");
    }
    
    /**
     * Record failed test
     */
    private function fail($testName, $reason = '') {
        $this->failed++;
        $message = "  ❌ $testName";
        if ($reason) {
            $message .= " [$reason]";
        }
        echo "$message\n";
        
        $this->logger->warning("Test failed: $testName", ['reason' => $reason]);
    }
    
    /**
     * Print test results
     */
    private function printResults() {
        echo "================================\n";
        echo "📈 Test Results\n";
        echo "================================\n";
        echo "✅ Passed: {$this->passed}\n";
        echo "❌ Failed: {$this->failed}\n";
        echo "📊 Total:  " . ($this->passed + $this->failed) . "\n";
        echo "💯 Success Rate: " . round(($this->passed / ($this->passed + $this->failed)) * 100, 2) . "%\n\n";
        
        if ($this->failed === 0) {
            echo "🎉 All tests passed!\n";
            return true;
        } else {
            echo "⚠️  Some tests failed. Please review the logs.\n";
            return false;
        }
    }
}

// Run tests
if (php_sapi_name() === 'cli') {
    // Command line mode
    $baseUrl = $argv[1] ?? 'http://localhost:8000';
    $test = new FunctionalTest($baseUrl);
    $test->runAll();
} else {
    // Web mode
    header('Content-Type: application/json');
    
    $baseUrl = $_GET['url'] ?? 'http://localhost:8000';
    $test = new FunctionalTest($baseUrl);
    $test->runAll();
}
