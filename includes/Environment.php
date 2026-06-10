<?php
/**
 * Environment configuration loader
 * Loads environment variables from .env file
 */

class Environment {
    private static $config = [];
    private static $loaded = false;
    
    /**
     * Load environment configuration from .env file
     */
    public static function load($path = null) {
        if (self::$loaded) {
            return;
        }
        
        if ($path === null) {
            $path = __DIR__ . '/../.env';
        }
        
        if (!file_exists($path)) {
            throw new Exception("Environment file not found: {$path}");
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse line
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes
                if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                    (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                    $value = substr($value, 1, -1);
                }
                
                self::$config[$key] = $value;
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Get environment variable
     */
    public static function get($key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }
        
        return self::$config[$key] ?? $default;
    }
    
    /**
     * Get boolean environment variable
     */
    public static function getBoolean($key, $default = false) {
        $value = self::get($key, $default);
        
        if (is_bool($value)) {
            return $value;
        }
        
        return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
    }
    
    /**
     * Get integer environment variable
     */
    public static function getInteger($key, $default = 0) {
        return (int)self::get($key, $default);
    }
    
    /**
     * Set environment variable (runtime only)
     */
    public static function set($key, $value) {
        self::$config[$key] = $value;
    }
    
    /**
     * Get all configuration
     */
    public static function getAll() {
        if (!self::$loaded) {
            self::load();
        }
        
        return self::$config;
    }
}
