<?php
// Math Advantage - Database Configuration

// Environment configuration
define('DEVELOPMENT_MODE', true);
define('ENVIRONMENT', DEVELOPMENT_MODE ? 'development' : 'production');

class DatabaseConfig {
    // Database connection settings
    private static $config = [
        'development' => [
            'host' => 'localhost',
            'port' => 3306,
            'dbname' => 'math_advantage',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        ],
        'production' => [
            'host' => 'localhost',
            'port' => 3306,
            'dbname' => 'math_advantage_prod',
            'username' => 'math_user',
            'password' => 'secure_password_here',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        ]
    ];
    
    public static function getConnection($environment = 'development') {
        if (!isset(self::$config[$environment])) {
            throw new Exception("Environment {$environment} not configured");
        }
        
        $config = self::$config[$environment];
        
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
            $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            
            return $pdo;
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    public static function testConnection($environment = 'development') {
        try {
            $pdo = self::getConnection($environment);
            $stmt = $pdo->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Email configuration
class EmailConfig {
    public static $config = [
        'smtp' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'info@math-advantage.com',
            'password' => 'your_app_password_here',
            'from_email' => 'info@math-advantage.com',
            'from_name' => 'Math Advantage'
        ],
        'admin_emails' => [
            'inscriptions' => 'admissions@math-advantage.com',
            'support' => 'support@math-advantage.com',
            'payments' => 'payments@math-advantage.com'
        ],
        'templates' => [
            'confirmation' => 'templates/email_confirmation.html',
            'admin_notification' => 'templates/admin_notification.html',
            'payment_reminder' => 'templates/payment_reminder.html',
            'welcome' => 'templates/welcome.html'
        ]
    ];
}

// Application settings
class AppConfig {
    public static $config = [
        'site' => [
            'name' => 'Math Advantage',
            'url' => 'https://www.math-advantage.com',
            'email' => 'info@math-advantage.com',
            'phone' => '933 123 456',
            'whatsapp' => '34644789012',
            'address' => 'Carrer de les Matemàtiques, 123, 08001 Barcelona',
            'timezone' => 'Europe/Madrid'
        ],
        'features' => [
            'chatbot_enabled' => true,
            'whatsapp_integration' => true,
            'online_payments' => false, // To be implemented in Phase 4
            'student_portal' => false, // To be implemented in Phase 3
            'email_notifications' => true,
            'sms_notifications' => false
        ],
        'limits' => [
            'max_students_per_group' => 8,
            'max_file_upload_size' => 5242880, // 5MB in bytes
            'session_timeout' => 3600, // 1 hour
            'login_attempts' => 5
        ],
        'pricing' => [
            '1eso' => 60.00,
            '2eso' => 60.00,
            '3eso' => 65.00,
            '4eso' => 65.00,
            '1bat' => 70.00,
            '2bat' => 75.00,
            'universitari' => 80.00
        ]
    ];
}

// Security settings
class SecurityConfig {
    public static $config = [
        'password' => [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_special_chars' => false
        ],
        'session' => [
            'cookie_lifetime' => 0,
            'cookie_secure' => true,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict'
        ],
        'csrf' => [
            'token_name' => 'math_advantage_token',
            'token_lifetime' => 3600
        ],
        'rate_limiting' => [
            'contact_form' => [
                'max_attempts' => 5,
                'time_window' => 3600 // 1 hour
            ],
            'login' => [
                'max_attempts' => 5,
                'time_window' => 900 // 15 minutes
            ]
        ]
    ];
}

// File paths
class PathConfig {
    public static function getBasePath() {
        return dirname(__DIR__);
    }
    
    public static function getUploadsPath() {
        return self::getBasePath() . '/uploads';
    }
    
    public static function getLogsPath() {
        return self::getBasePath() . '/logs';
    }
    
    public static function getTemplatesPath() {
        return self::getBasePath() . '/templates';
    }
    
    public static function getBackupsPath() {
        return self::getBasePath() . '/backups';
    }
}

// Error handling
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    $log_message = date('Y-m-d H:i:s') . " - Error: {$message} in {$file} on line {$line}\n";
    error_log($log_message, 3, PathConfig::getLogsPath() . '/error.log');
    
    // In production, don't display errors to users
    if (AppConfig::$config['site']['url'] !== 'http://localhost') {
        return true;
    }
    
    return false;
});

// Exception handler
set_exception_handler(function($exception) {
    $log_message = date('Y-m-d H:i:s') . " - Uncaught Exception: " . $exception->getMessage() . 
                   " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n";
    error_log($log_message, 3, PathConfig::getLogsPath() . '/error.log');
    
    // Show generic error page in production
    if (AppConfig::$config['site']['url'] !== 'http://localhost') {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error intern del servidor',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo "Uncaught Exception: " . $exception->getMessage();
    }
});

// Set timezone
date_default_timezone_set(AppConfig::$config['site']['timezone']);

// Create necessary directories if they don't exist
$directories = [
    PathConfig::getUploadsPath(),
    PathConfig::getLogsPath(),
    PathConfig::getBackupsPath(),
    PathConfig::getUploadsPath() . '/students',
    PathConfig::getUploadsPath() . '/professors',
    PathConfig::getUploadsPath() . '/documents'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Autoloader for classes (simple implementation)
spl_autoload_register(function ($class_name) {
    $directories = [
        PathConfig::getBasePath() . '/php/classes/',
        PathConfig::getBasePath() . '/php/models/',
        PathConfig::getBasePath() . '/php/controllers/',
        PathConfig::getBasePath() . '/php/utils/'
    ];
    
    foreach ($directories as $dir) {
        $file = $dir . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
?>