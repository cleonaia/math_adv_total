-- Math Advantage - Security Schema
-- Tablas para ciberseguridad y logging

-- Tabla de logs de seguridad
CREATE TABLE IF NOT EXISTS security_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_type VARCHAR(100) NOT NULL,
    user_id INT NULL,
    identifier VARCHAR(255) NULL, -- IP, session, etc.
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    data JSON NULL,
    severity ENUM('info', 'warning', 'critical') DEFAULT 'info',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_severity (severity),
    INDEX idx_ip (ip_address),
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id)
);

-- Tabla de sesiones seguras
CREATE TABLE IF NOT EXISTS secure_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_active (is_active),
    INDEX idx_expires (expires_at)
);

-- Tabla de intentos de login fallidos
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    identifier VARCHAR(255) NOT NULL, -- IP o username
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(255),
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,
    user_agent TEXT,
    blocked_until TIMESTAMP NULL,
    INDEX idx_identifier (identifier),
    INDEX idx_ip (ip_address),
    INDEX idx_attempt_time (attempt_time),
    INDEX idx_blocked (blocked_until)
);

-- Tabla de configuración de seguridad
CREATE TABLE IF NOT EXISTS security_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    INDEX idx_key (config_key)
);

-- Tabla de permisos por rol
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL,
    permission VARCHAR(100) NOT NULL,
    resource VARCHAR(100) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_role_permission (role_name, permission, resource),
    INDEX idx_role (role_name),
    INDEX idx_permission (permission)
);

-- Tabla de tokens de API
CREATE TABLE IF NOT EXISTS api_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    token_name VARCHAR(100),
    permissions JSON,
    expires_at TIMESTAMP NOT NULL,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    UNIQUE KEY unique_token (token_hash),
    INDEX idx_user_id (user_id),
    INDEX idx_active (is_active),
    INDEX idx_expires (expires_at)
);

-- Tabla de archivos subidos con validación
CREATE TABLE IF NOT EXISTS secure_uploads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_hash VARCHAR(64) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    scan_status ENUM('pending', 'clean', 'infected', 'error') DEFAULT 'pending',
    scan_result TEXT NULL,
    upload_path VARCHAR(500) NOT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_hash (file_hash),
    INDEX idx_scan_status (scan_status)
);

-- Insertar configuraciones de seguridad por defecto
INSERT INTO security_config (config_key, config_value, description) VALUES 
('max_login_attempts', '5', 'Máximo número de intentos de login antes del bloqueo'),
('lockout_duration', '900', 'Duración del bloqueo en segundos (15 minutos)'),
('session_timeout', '3600', 'Timeout de sesión en segundos (1 hora)'),
('password_min_length', '8', 'Longitud mínima de contraseña'),
('require_2fa', 'false', 'Requerir autenticación de dos factores'),
('allowed_file_types', 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif', 'Tipos de archivo permitidos para subida'),
('max_file_size', '10485760', 'Tamaño máximo de archivo en bytes (10MB)'),
('rate_limit_api', '100', 'Límite de requests por hora a la API'),
('rate_limit_login', '5', 'Límite de intentos de login por 15 minutos'),
('encryption_enabled', 'true', 'Habilitar encriptación de datos sensibles')
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);

-- Insertar permisos por defecto
INSERT INTO role_permissions (role_name, permission, resource) VALUES 
('admin', 'read', 'analytics'),
('admin', 'write', 'analytics'),
('admin', 'delete', 'analytics'),
('admin', 'export', 'analytics'),
('admin', 'read', 'users'),
('admin', 'write', 'users'),
('admin', 'delete', 'users'),
('teacher', 'read', 'analytics'),
('teacher', 'read', 'students'),
('teacher', 'write', 'evaluations'),
('parent', 'read', 'student_progress'),
('student', 'read', 'own_data'),
('student', 'write', 'evaluations')
ON DUPLICATE KEY UPDATE is_active = VALUES(is_active);

-- Crear índices adicionales para rendimiento
CREATE INDEX IF NOT EXISTS idx_security_logs_composite ON security_logs (event_type, severity, created_at);
CREATE INDEX IF NOT EXISTS idx_sessions_user_active ON secure_sessions (user_id, is_active);
CREATE INDEX IF NOT EXISTS idx_login_attempts_composite ON login_attempts (ip_address, attempt_time, success);

-- Procedimientos para limpieza automática
DELIMITER //

CREATE PROCEDURE CleanupSecurityLogs()
BEGIN
    -- Eliminar logs antiguos (más de 90 días)
    DELETE FROM security_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    -- Eliminar sesiones expiradas
    DELETE FROM secure_sessions 
    WHERE expires_at < NOW() OR last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR);
    
    -- Eliminar intentos de login antiguos (más de 7 días)
    DELETE FROM login_attempts 
    WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 7 DAY);
END //

CREATE PROCEDURE GetSecuritySummary()
BEGIN
    SELECT 
        'Critical Events (24h)' as metric,
        COUNT(*) as value
    FROM security_logs 
    WHERE severity = 'critical' 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    
    UNION ALL
    
    SELECT 
        'Failed Logins (1h)' as metric,
        COUNT(*) as value
    FROM login_attempts 
    WHERE success = FALSE 
    AND attempt_time >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    
    UNION ALL
    
    SELECT 
        'Active Sessions' as metric,
        COUNT(*) as value
    FROM secure_sessions 
    WHERE is_active = TRUE 
    AND expires_at > NOW()
    
    UNION ALL
    
    SELECT 
        'Blocked IPs' as metric,
        COUNT(DISTINCT ip_address) as value
    FROM login_attempts 
    WHERE blocked_until > NOW();
END //

DELIMITER ;

-- Crear evento para limpieza automática (ejecutar diariamente a las 2 AM)
CREATE EVENT IF NOT EXISTS cleanup_security_data
ON SCHEDULE EVERY 1 DAY STARTS '2025-10-06 02:00:00'
DO CALL CleanupSecurityLogs();