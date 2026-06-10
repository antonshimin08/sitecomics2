<?php
/**
 * Logger class for application error and event logging
 * Supports different log levels: DEBUG, INFO, WARNING, ERROR, CRITICAL
 */

class Logger {
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const CRITICAL = 'CRITICAL';
    
    private $logPath;
    private $logFile;
    private $enableLogging;
    
    /**
     * Initialize logger
     */
    public function __construct() {
        $this->logPath = defined('LOG_PATH') ? LOG_PATH : __DIR__ . '/../logs/';
        $this->enableLogging = defined('LOG_ERRORS') ? LOG_ERRORS : true;
        
        // Create logs directory if it doesn't exist
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
        
        $this->logFile = $this->logPath . 'app_' . date('Y-m-d') . '.log';
    }
    
    /**
     * Write log message
     */
    public function log($level, $message, $context = []) {
        if (!$this->enableLogging) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';
        $logMessage = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
        
        // Write to file
        error_log($logMessage, 3, $this->logFile);
        
        // Also log to error_log if DEBUG mode is on
        if (DEBUG_MODE) {
            error_log($logMessage);
        }
    }
    
    /**
     * Log debug message
     */
    public function debug($message, $context = []) {
        $this->log(self::DEBUG, $message, $context);
    }
    
    /**
     * Log info message
     */
    public function info($message, $context = []) {
        $this->log(self::INFO, $message, $context);
    }
    
    /**
     * Log warning message
     */
    public function warning($message, $context = []) {
        $this->log(self::WARNING, $message, $context);
    }
    
    /**
     * Log error message
     */
    public function error($message, $context = []) {
        $this->log(self::ERROR, $message, $context);
    }
    
    /**
     * Log critical message
     */
    public function critical($message, $context = []) {
        $this->log(self::CRITICAL, $message, $context);
    }
    
    /**
     * Get log file path
     */
    public function getLogFile() {
        return $this->logFile;
    }
    
    /**
     * Clear old logs (older than days)
     */
    public function clearOldLogs($days = 30) {
        $cutoff = time() - ($days * 24 * 60 * 60);
        
        if (!is_dir($this->logPath)) {
            return;
        }
        
        $files = glob($this->logPath . 'app_*.log');
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
}
