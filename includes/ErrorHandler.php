<?php
/**
 * Error and Exception Handler for production environment
 * Handles fatal errors, exceptions, and warnings
 */

class ErrorHandler {
    private static $logger;
    
    /**
     * Initialize error handler
     */
    public static function register() {
        self::$logger = new Logger();
        
        // Set error handler
        set_error_handler([self::class, 'handleError']);
        
        // Set exception handler
        set_exception_handler([self::class, 'handleException']);
        
        // Handle shutdown (fatal errors)
        register_shutdown_function([self::class, 'handleShutdown']);
    }
    
    /**
     * Handle PHP errors
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        // Skip errors that are suppressed with @
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $errorType = self::getErrorType($errno);
        
        // Log error
        self::$logger->log($errorType, $errstr, [
            'file' => $errfile,
            'line' => $errline,
            'errno' => $errno
        ]);
        
        // Show user-friendly error in production
        if (!DEBUG_MODE && $errno !== E_WARNING && $errno !== E_NOTICE) {
            header('HTTP/1.1 500 Internal Server Error');
            exit('Sorry, something went wrong. Please try again later.');
        }
        
        return true;
    }
    
    /**
     * Handle uncaught exceptions
     */
    public static function handleException($exception) {
        self::$logger->critical('Uncaught Exception: ' . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        if (DEBUG_MODE) {
            echo '<pre>';
            echo 'Exception: ' . $exception->getMessage() . "\n\n";
            echo $exception->getTraceAsString();
            echo '</pre>';
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            exit('Sorry, something went wrong. Please try again later.');
        }
    }
    
    /**
     * Handle fatal errors during shutdown
     */
    public static function handleShutdown() {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            self::$logger->critical('Fatal Error: ' . $error['message'], [
                'file' => $error['file'],
                'line' => $error['line']
            ]);
            
            if (!DEBUG_MODE) {
                header('HTTP/1.1 500 Internal Server Error');
                exit('Sorry, something went wrong. Please try again later.');
            }
        }
    }
    
    /**
     * Convert error code to string
     */
    private static function getErrorType($errno) {
        switch ($errno) {
            case E_ERROR:
                return Logger::ERROR;
            case E_WARNING:
                return Logger::WARNING;
            case E_NOTICE:
                return Logger::INFO;
            case E_DEPRECATED:
                return Logger::WARNING;
            default:
                return Logger::INFO;
        }
    }
}
