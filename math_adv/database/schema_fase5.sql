-- Math Advantage - Schema Completo Fase 5
-- Analytics y Optimización

-- Tabla para informes de analytics automáticos
CREATE TABLE IF NOT EXISTS analytics_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('daily', 'weekly', 'monthly', 'quarterly') NOT NULL,
    period_days INT NOT NULL,
    generated_at DATETIME NOT NULL,
    summary_data JSON,
    recommendations JSON,
    full_data JSON,
    exported BOOLEAN DEFAULT FALSE,
    export_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type_date (type, generated_at),
    INDEX idx_period (period_days),
    INDEX idx_exported (exported)
);

-- Tabla para tests A/B
CREATE TABLE IF NOT EXISTS ab_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    variants JSON NOT NULL,
    success_metric VARCHAR(100) NOT NULL,
    status ENUM('active', 'paused', 'completed', 'cancelled') DEFAULT 'active',
    start_date DATETIME,
    end_date DATETIME,
    confidence_level DECIMAL(5,2) DEFAULT 95.00,
    sample_size_per_variant INT,
    statistical_significance BOOLEAN DEFAULT FALSE,
    winning_variant VARCHAR(50),
    results JSON,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_created_by (created_by)
);

-- Tabla para eventos de A/B testing
CREATE TABLE IF NOT EXISTS ab_test_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    user_id INT,
    session_id VARCHAR(255),
    variant VARCHAR(50) NOT NULL,
    action VARCHAR(100) NOT NULL,
    value DECIMAL(10,2),
    timestamp DATETIME NOT NULL,
    user_agent TEXT,
    ip_address VARCHAR(45),
    additional_data JSON,
    FOREIGN KEY (test_id) REFERENCES ab_tests(id) ON DELETE CASCADE,
    INDEX idx_test_variant (test_id, variant),
    INDEX idx_action (action),
    INDEX idx_timestamp (timestamp),
    INDEX idx_user (user_id),
    INDEX idx_session (session_id)
);

-- Tabla para encuestas de feedback
CREATE TABLE IF NOT EXISTS feedback_surveys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    questions JSON NOT NULL,
    target_audience ENUM('students', 'teachers', 'parents', 'all') DEFAULT 'all',
    status ENUM('draft', 'active', 'paused', 'completed') DEFAULT 'draft',
    start_date DATETIME,
    end_date DATETIME,
    max_responses INT,
    anonymous BOOLEAN DEFAULT TRUE,
    show_results BOOLEAN DEFAULT FALSE,
    email_notifications BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_audience (target_audience),
    INDEX idx_dates (start_date, end_date)
);

-- Tabla para respuestas de encuestas
CREATE TABLE IF NOT EXISTS feedback_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survey_id INT NOT NULL,
    user_id INT,
    session_id VARCHAR(255),
    responses JSON NOT NULL,
    completion_time INT, -- segundos
    ip_address VARCHAR(45),
    user_agent TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (survey_id) REFERENCES feedback_surveys(id) ON DELETE CASCADE,
    INDEX idx_survey (survey_id),
    INDEX idx_user (user_id),
    INDEX idx_submitted (submitted_at)
);

-- Tabla para métricas SEO
CREATE TABLE IF NOT EXISTS seo_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    page_url VARCHAR(500) NOT NULL,
    organic_traffic INT DEFAULT 0,
    keyword_rankings JSON,
    page_speed_desktop INT,
    page_speed_mobile INT,
    core_web_vitals JSON,
    backlinks_count INT DEFAULT 0,
    indexed_pages INT DEFAULT 0,
    crawl_errors INT DEFAULT 0,
    click_through_rate DECIMAL(5,2),
    bounce_rate DECIMAL(5,2),
    avg_session_duration INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date_url (date, page_url),
    INDEX idx_date (date),
    INDEX idx_traffic (organic_traffic),
    INDEX idx_speed (page_speed_desktop, page_speed_mobile)
);

-- Tabla para keywords SEO
CREATE TABLE IF NOT EXISTS seo_keywords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(255) NOT NULL,
    target_url VARCHAR(500),
    current_position INT,
    previous_position INT,
    position_change INT,
    search_volume INT,
    difficulty DECIMAL(3,1),
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    ctr DECIMAL(5,2),
    last_updated DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_keyword_url (keyword, target_url),
    INDEX idx_keyword (keyword),
    INDEX idx_position (current_position),
    INDEX idx_volume (search_volume),
    INDEX idx_updated (last_updated)
);

-- Tabla para configuración de analytics
CREATE TABLE IF NOT EXISTS analytics_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value JSON NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (config_key),
    INDEX idx_active (is_active)
);

-- Tabla para eventos personalizados de tracking
CREATE TABLE IF NOT EXISTS custom_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(100) NOT NULL,
    event_category VARCHAR(50),
    event_action VARCHAR(100),
    event_label VARCHAR(255),
    event_value DECIMAL(10,2),
    user_id INT,
    session_id VARCHAR(255),
    page_url VARCHAR(500),
    user_agent TEXT,
    ip_address VARCHAR(45),
    device_type ENUM('desktop', 'tablet', 'mobile'),
    browser VARCHAR(50),
    os VARCHAR(50),
    referrer VARCHAR(500),
    utm_source VARCHAR(100),
    utm_medium VARCHAR(100),
    utm_campaign VARCHAR(100),
    timestamp DATETIME NOT NULL,
    additional_data JSON,
    INDEX idx_event_name (event_name),
    INDEX idx_category_action (event_category, event_action),
    INDEX idx_user (user_id),
    INDEX idx_session (session_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_device (device_type),
    INDEX idx_utm (utm_source, utm_medium, utm_campaign)
);

-- Tabla para heatmaps y tracking de clics
CREATE TABLE IF NOT EXISTS heatmap_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_url VARCHAR(500) NOT NULL,
    element_selector VARCHAR(255),
    click_x INT,
    click_y INT,
    viewport_width INT,
    viewport_height INT,
    device_type ENUM('desktop', 'tablet', 'mobile'),
    user_id INT,
    session_id VARCHAR(255),
    timestamp DATETIME NOT NULL,
    INDEX idx_page (page_url),
    INDEX idx_device (device_type),
    INDEX idx_timestamp (timestamp),
    INDEX idx_coordinates (click_x, click_y)
);

-- Tabla para configuración PWA
CREATE TABLE IF NOT EXISTS pwa_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    manifest_data JSON NOT NULL,
    service_worker_version VARCHAR(50),
    cache_strategy ENUM('cache-first', 'network-first', 'cache-only', 'network-only') DEFAULT 'cache-first',
    offline_pages JSON,
    push_notifications_enabled BOOLEAN DEFAULT TRUE,
    background_sync_enabled BOOLEAN DEFAULT TRUE,
    install_prompt_settings JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla para instalaciones PWA
CREATE TABLE IF NOT EXISTS pwa_installations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    session_id VARCHAR(255),
    install_source ENUM('banner', 'menu', 'shortcut', 'unknown') DEFAULT 'unknown',
    platform ENUM('android', 'ios', 'windows', 'macos', 'linux', 'other') DEFAULT 'other',
    browser VARCHAR(50),
    device_type ENUM('desktop', 'tablet', 'mobile'),
    user_agent TEXT,
    installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uninstalled_at TIMESTAMP NULL,
    usage_sessions INT DEFAULT 0,
    last_usage TIMESTAMP NULL,
    INDEX idx_user (user_id),
    INDEX idx_platform (platform),
    INDEX idx_installed (installed_at),
    INDEX idx_source (install_source)
);

-- Insertar configuración inicial de analytics
INSERT INTO analytics_config (config_key, config_value, description) VALUES
('dashboard_refresh_interval', '300', 'Intervalo de actualización del dashboard en segundos'),
('retention_days', '365', 'Días de retención de datos analíticos'),
('heatmap_sampling_rate', '0.1', 'Tasa de muestreo para heatmaps (0.0-1.0)'),
('ab_test_default_confidence', '95', 'Nivel de confianza por defecto para tests A/B'),
('seo_tracking_enabled', 'true', 'Activar tracking de métricas SEO'),
('custom_events_enabled', 'true', 'Activar eventos personalizados'),
('performance_monitoring', 'true', 'Activar monitoreo de rendimiento'),
('real_user_monitoring', 'true', 'Activar Real User Monitoring (RUM)'),
('privacy_mode', 'false', 'Modo privacidad - anonimizar datos sensibles'),
('data_export_enabled', 'true', 'Permitir exportación de datos')
ON DUPLICATE KEY UPDATE 
    config_value = VALUES(config_value),
    updated_at = CURRENT_TIMESTAMP;

-- Insertar test A/B de ejemplo
INSERT INTO ab_tests (name, description, variants, success_metric, status, confidence_level) VALUES
('Botón Inscripción Color', 'Test del color del botón principal de inscripción', 
 '{"A": {"color": "blue", "text": "Inscriu-te Ara"}, "B": {"color": "green", "text": "Comença Gratis"}}', 
 'conversion_rate', 'active', 95.00),
('Landing Layout', 'Comparación entre layout vertical y horizontal', 
 '{"A": {"layout": "vertical"}, "B": {"layout": "horizontal"}}', 
 'engagement_time', 'active', 95.00)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Insertar configuración PWA inicial
INSERT INTO pwa_config (manifest_data, service_worker_version, cache_strategy, offline_pages, install_prompt_settings) VALUES
('{"name": "Math Advantage", "short_name": "MathAdvantage", "theme_color": "#2563eb"}',
 'v1.0.0',
 'cache-first',
 '["/offline.html", "/", "/portal/"]',
 '{"show_after_days": 3, "show_after_visits": 5, "dismiss_period_days": 7}')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Crear vistas para analytics rápidos
CREATE OR REPLACE VIEW analytics_overview AS
SELECT 
    DATE(ce.timestamp) as date,
    COUNT(DISTINCT ce.session_id) as unique_sessions,
    COUNT(DISTINCT ce.user_id) as unique_users,
    COUNT(*) as total_events,
    COUNT(CASE WHEN ce.event_name = 'page_view' THEN 1 END) as page_views,
    COUNT(CASE WHEN ce.event_name = 'conversion' THEN 1 END) as conversions,
    AVG(CASE WHEN ce.event_name = 'session_duration' THEN ce.event_value END) as avg_session_duration,
    COUNT(CASE WHEN ce.device_type = 'mobile' THEN 1 END) / COUNT(*) * 100 as mobile_percentage
FROM custom_events ce
WHERE ce.timestamp >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
GROUP BY DATE(ce.timestamp)
ORDER BY date DESC;

CREATE OR REPLACE VIEW user_engagement_summary AS
SELECT 
    u.id as user_id,
    u.first_name,
    u.last_name,
    u.email,
    COUNT(DISTINCT ce.session_id) as total_sessions,
    COUNT(CASE WHEN ce.event_name = 'page_view' THEN 1 END) as page_views,
    COUNT(CASE WHEN ce.event_name = 'file_download' THEN 1 END) as downloads,
    COUNT(CASE WHEN ce.event_name = 'evaluation_completed' THEN 1 END) as evaluations,
    MAX(ce.timestamp) as last_activity,
    AVG(CASE WHEN ce.event_name = 'session_duration' THEN ce.event_value END) as avg_session_time
FROM students u
LEFT JOIN custom_events ce ON u.id = ce.user_id
WHERE ce.timestamp >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
GROUP BY u.id, u.first_name, u.last_name, u.email
ORDER BY last_activity DESC;

-- Índices adicionales para optimización
CREATE INDEX idx_custom_events_compound ON custom_events(event_name, timestamp, user_id);
CREATE INDEX idx_ab_events_compound ON ab_test_events(test_id, timestamp, action);
CREATE INDEX idx_analytics_reports_type_date ON analytics_reports(type, generated_at);
CREATE INDEX idx_seo_metrics_date_traffic ON seo_metrics(date, organic_traffic);
CREATE INDEX idx_feedback_responses_survey_date ON feedback_responses(survey_id, submitted_at);

-- Función para limpiar datos antiguos
DELIMITER //
CREATE PROCEDURE CleanOldAnalyticsData(IN retention_days INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Limpiar eventos antiguos
    DELETE FROM custom_events 
    WHERE timestamp < DATE_SUB(CURRENT_DATE, INTERVAL retention_days DAY);
    
    -- Limpiar datos de heatmap antiguos
    DELETE FROM heatmap_data 
    WHERE timestamp < DATE_SUB(CURRENT_DATE, INTERVAL retention_days DAY);
    
    -- Limpiar eventos de A/B testing completados
    DELETE abe FROM ab_test_events abe
    JOIN ab_tests ab ON abe.test_id = ab.id
    WHERE ab.status = 'completed' 
    AND abe.timestamp < DATE_SUB(CURRENT_DATE, INTERVAL retention_days DAY);
    
    -- Limpiar métricas SEO antiguas (mantener solo datos mensuales después de 90 días)
    DELETE FROM seo_metrics 
    WHERE date < DATE_SUB(CURRENT_DATE, INTERVAL retention_days DAY)
    AND DAY(date) != 1; -- Mantener primer día de cada mes
    
    COMMIT;
END//
DELIMITER ;

-- Evento programado para limpieza automática (ejecutar semanalmente)
CREATE EVENT IF NOT EXISTS auto_cleanup_analytics
ON SCHEDULE EVERY 1 WEEK
STARTS CURRENT_TIMESTAMP
DO
  CALL CleanOldAnalyticsData(365);

-- Activar programador de eventos
SET GLOBAL event_scheduler = ON;