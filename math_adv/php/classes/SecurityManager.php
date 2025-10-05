<?php
/**
 * Math Advantage - Security Manager
 * Sistema de ciberseguridad y protección avanzada
 */

class SecurityManager {
    private static $instance = null;
    private $db;
    private $config;
    
    // Rate limiting
    private $rateLimits = [
        'api' => ['requests' => 100, 'window' => 3600], // 100 requests per hour
        'login' => ['requests' => 5, 'window' => 900],   // 5 login attempts per 15 min
        'export' => ['requests' => 10, 'window' => 3600] // 10 exports per hour
    ];
    
    private function __construct() {
        $this->db = Database::getInstance();
        $this->config = $this->loadSecurityConfig();
        $this->initializeSecurity();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inicializar configuraciones de seguridad
     */
    private function initializeSecurity() {
        // Headers de seguridad
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        
        // CSP (Content Security Policy)
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://code.jquery.com; " .
               "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; " .
               "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self';";
        
        header("Content-Security-Policy: $csp");
    }
    
    /**
     * Validar autenticación y autorización
     */
    public function validateAccess($requiredRole = null, $resource = null) {
        session_start();
        
        // Verificar sesión activa
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
            $this->logSecurityEvent('unauthorized_access_attempt', [
                'ip' => $this->getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'resource' => $resource
            ]);
            return false;
        }
        
        // Verificar timeout de sesión
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > 3600)) {
            session_destroy();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        
        // Verificar rol si se especifica
        if ($requiredRole && !$this->hasRole($_SESSION['user_type'], $requiredRole)) {
            $this->logSecurityEvent('insufficient_privileges', [
                'user_id' => $_SESSION['user_id'],
                'user_type' => $_SESSION['user_type'],
                'required_role' => $requiredRole,
                'resource' => $resource
            ]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Rate limiting
     */
    public function checkRateLimit($action, $identifier = null) {
        if (!isset($this->rateLimits[$action])) {
            return true;
        }
        
        $identifier = $identifier ?? $this->getClientIP();
        $limit = $this->rateLimits[$action];
        
        $sql = "SELECT COUNT(*) as count FROM security_logs 
                WHERE action = ? AND identifier = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([$action, $identifier, $limit['window']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] >= $limit['requests']) {
                $this->logSecurityEvent('rate_limit_exceeded', [
                    'action' => $action,
                    'identifier' => $identifier,
                    'count' => $result['count'],
                    'limit' => $limit['requests']
                ]);
                return false;
            }
            
            // Log la acción
            $this->logRateLimitAction($action, $identifier);
            return true;
            
        } catch (Exception $e) {
            error_log("Rate limit check error: " . $e->getMessage());
            return true; // En caso de error, permitir acceso
        }
    }
    
    /**
     * Sanitizar y validar entrada
     */
    public function sanitizeInput($input, $type = 'string') {
        if ($input === null || $input === '') {
            return null;
        }
        
        switch ($type) {
            case 'int':
                return filter_var($input, FILTER_VALIDATE_INT) !== false ? 
                       (int)$input : null;
                       
            case 'float':
                return filter_var($input, FILTER_VALIDATE_FLOAT) !== false ? 
                       (float)$input : null;
                       
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL) !== false ? 
                       $input : null;
                       
            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL) !== false ? 
                       $input : null;
                       
            case 'alpha':
                return preg_match('/^[a-zA-Z]+$/', $input) ? $input : null;
                
            case 'alphanumeric':
                return preg_match('/^[a-zA-Z0-9]+$/', $input) ? $input : null;
                
            case 'filename':
                return preg_match('/^[a-zA-Z0-9._-]+$/', $input) ? $input : null;
                
            default: // string
                return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Detectar y prevenir ataques
     */
    public function detectAttack($input) {
        $attacks = [
            'sql_injection' => [
                '/(\bunion\b.*\bselect\b)|(\bselect\b.*\bunion\b)/i',
                '/\b(select|insert|update|delete|drop|create|alter)\b.*\b(from|into|table|database)\b/i',
                '/(\bor\b|\band\b).*[\'"][^\'\"]*[\'"].*=/i'
            ],
            'xss' => [
                '/<script[^>]*>.*?<\/script>/is',
                '/javascript:/i',
                '/on\w+\s*=\s*[\'"][^\'"]*[\'"]/i',
                '/<iframe[^>]*>.*?<\/iframe>/is'
            ],
            'path_traversal' => [
                '/\.\.\//',
                '/\.\.\\\\/',
                '/\.\.\%2f/',
                '/\.\.\%5c/'
            ],
            'command_injection' => [
                '/[;&|`$(){}[\]]/i',
                '/\b(eval|exec|system|shell_exec|passthru|file_get_contents)\b/i'
            ]
        ];
        
        foreach ($attacks as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $input)) {
                    $this->logSecurityEvent('attack_detected', [
                        'type' => $type,
                        'pattern' => $pattern,
                        'input' => substr($input, 0, 200), // Solo primeros 200 chars
                        'ip' => $this->getClientIP(),
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                    ]);
                    return $type;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Encriptar datos sensibles
     */
    public function encrypt($data, $key = null) {
        $key = $key ?? $this->config['encryption_key'];
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Desencriptar datos
     */
    public function decrypt($encryptedData, $key = null) {
        $key = $key ?? $this->config['encryption_key'];
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Generar token CSRF
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validar token CSRF
     */
    public function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Logging de eventos de seguridad
     */
    private function logSecurityEvent($event, $data = []) {
        try {
            $sql = "INSERT INTO security_logs (event_type, user_id, ip_address, user_agent, 
                    data, severity, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $severity = $this->getEventSeverity($event);
            $userId = $_SESSION['user_id'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([
                $event,
                $userId,
                $this->getClientIP(),
                $userAgent,
                json_encode($data),
                $severity
            ]);
            
            // Alertas críticas
            if ($severity === 'critical') {
                $this->sendSecurityAlert($event, $data);
            }
            
        } catch (Exception $e) {
            error_log("Security logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener IP real del cliente
     */
    public function getClientIP() {
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Verificar roles y permisos
     */
    private function hasRole($userType, $requiredRole) {
        $roleHierarchy = [
            'admin' => ['admin', 'teacher', 'parent', 'student'],
            'teacher' => ['teacher', 'student'],
            'parent' => ['parent', 'student'],
            'student' => ['student']
        ];
        
        return isset($roleHierarchy[$userType]) && 
               in_array($requiredRole, $roleHierarchy[$userType]);
    }
    
    /**
     * Cargar configuración de seguridad
     */
    private function loadSecurityConfig() {
        return [
            'encryption_key' => hash('sha256', 'MathAdvantage2025SecureKey' . date('Y-m-d')),
            'max_login_attempts' => 5,
            'lockout_time' => 900, // 15 minutos
            'session_timeout' => 3600, // 1 hora
            'password_min_length' => 8,
            'require_2fa' => false
        ];
    }
    
    /**
     * Log para rate limiting
     */
    private function logRateLimitAction($action, $identifier) {
        try {
            $sql = "INSERT INTO security_logs (event_type, identifier, created_at) VALUES (?, ?, NOW())";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([$action, $identifier]);
        } catch (Exception $e) {
            error_log("Rate limit logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Determinar severidad del evento
     */
    private function getEventSeverity($event) {
        $critical = ['attack_detected', 'unauthorized_access_attempt', 'sql_injection', 'xss'];
        $warning = ['rate_limit_exceeded', 'insufficient_privileges'];
        
        if (in_array($event, $critical)) return 'critical';
        if (in_array($event, $warning)) return 'warning';
        return 'info';
    }
    
    /**
     * Enviar alerta de seguridad crítica
     */
    private function sendSecurityAlert($event, $data) {
        // En implementación real, enviarías email/SMS/Slack
        error_log("SECURITY ALERT: $event - " . json_encode($data));
    }
}
?>