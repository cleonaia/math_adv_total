<?php
/**
 * Math Advantage - Analytics API SEGURA
 * API protegida con ciberseguridad para datos de analytics
 */

// Headers de seguridad
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// CORS más restrictivo - solo permitir el dominio propio
$allowed_origins = ['http://localhost:8080', 'https://math-advantage.local'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: null');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../php/config.php';
require_once '../../php/classes/Database.php';
require_once '../../php/classes/SecurityManager.php';
require_once 'AdvancedAnalyticsSystem.php';

// Inicializar sistema de seguridad
$security = SecurityManager::getInstance();

try {
    // Verificar autenticación para acceso a analytics
    if (!$security->validateAccess('admin', 'analytics')) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Accés no autoritzat. Es requereix autenticació d\'administrador.'
        ]);
        exit;
    }
    
    // Rate limiting para API
    if (!$security->checkRateLimit('api', $_SESSION['user_id'] ?? $security->getClientIP())) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'error' => 'Límit de sol·licituds excedit. Prova més tard.'
        ]);
        exit;
    }
    
    $db = Database::getInstance();
    $analytics = new AdvancedAnalyticsSystem($db->getConnection());
    
    // Sanitizar y validar entrada
    $action = $security->sanitizeInput($_GET['action'] ?? '', 'alphanumeric');
    
    // Detectar ataques
    if ($security->detectAttack($_SERVER['REQUEST_URI'] ?? '')) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Sol·licitud sospitosa detectada.'
        ]);
        exit;
    }
    
    switch($action) {
        case 'dashboard':
            $data = $analytics->getDashboardData();
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            break;
            
        case 'users':
            $days = intval($_GET['days'] ?? 30);
            $data = $analytics->getUserAnalytics($days);
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            break;
            
        case 'engagement':
            $days = intval($_GET['days'] ?? 30);
            $data = $analytics->getEngagementMetrics($days);
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            break;
            
        case 'performance':
            $days = intval($_GET['days'] ?? 30);
            $data = $analytics->getPerformanceMetrics($days);
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            break;
            
        case 'content':
            $days = intval($_GET['days'] ?? 30);
            $data = $analytics->getContentAnalytics($days);
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            break;
            
        case 'conversion':
            $days = intval($_GET['days'] ?? 30);
            $data = $analytics->getConversionMetrics($days);
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            break;
            
        case 'ab_tests':
            $data = $analytics->getABTestResults(30); // Últimos 30 días
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            break;
            
        case 'reports':
            $data = $analytics->generateAutomatedReport();
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            break;
            
        case 'realtime':
            // Métricas en tiempo real
            $realtime_metrics = new RealtimeMetrics($db->getConnection());
            $realtime = [
                'active_users' => $realtime_metrics->getRealTimeUsers(),
                'current_sessions' => $realtime_metrics->getCurrentSessions(),
                'live_evaluations' => $realtime_metrics->getLiveEvaluations(),
                'chat_activity' => 0,
                'server_metrics' => [
                    'cpu_usage' => round(sys_getloadavg()[0] * 100, 2),
                    'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2),
                    'disk_usage' => round(disk_free_space('.') / disk_total_space('.') * 100, 2),
                    'response_time' => round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2)
                ]
            ];
            echo json_encode([
                'success' => true,
                'data' => $realtime,
                'timestamp' => time()
            ]);
            break;
            
        case 'export':
            $format = $_GET['format'] ?? 'json';
            $type = $_GET['type'] ?? 'dashboard';
            
            switch($type) {
                case 'dashboard':
                    $data = $analytics->getDashboardData();
                    break;
                case 'users':
                    $data = $analytics->getUserAnalytics(30);
                    break;
                case 'engagement':
                    $data = $analytics->getEngagementMetrics(30);
                    break;
                default:
                    $data = $analytics->getDashboardData();
            }
            
            if ($format === 'csv') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="analytics_' . $type . '_' . date('Y-m-d') . '.csv"');
                
                // Convertir a CSV
                $output = fopen('php://output', 'w');
                if (!empty($data) && is_array($data)) {
                    // Headers
                    fputcsv($output, array_keys($data[0]));
                    // Data
                    foreach ($data as $row) {
                        fputcsv($output, $row);
                    }
                }
                fclose($output);
            } else {
                echo json_encode([
                    'success' => true,
                    'data' => $data,
                    'export_time' => date('Y-m-d H:i:s')
                ]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Acción no válida. Acciones disponibles: dashboard, users, engagement, performance, content, conversion, ab_tests, reports, realtime, export'
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno: ' . $e->getMessage()
    ]);
}

// Métodos auxiliares para métricas en tiempo real
class RealtimeMetrics {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getRealTimeUsers() {
        $sql = "
        SELECT COUNT(DISTINCT user_id) as count
        FROM user_sessions 
        WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        AND is_active = 1
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    }
    
    public function getCurrentSessions() {
        $sql = "
        SELECT 
            COUNT(*) as total_sessions,
            AVG(TIMESTAMPDIFF(MINUTE, session_start, NOW())) as avg_duration
        FROM user_sessions 
        WHERE is_active = 1
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getLiveEvaluations() {
        $sql = "
        SELECT COUNT(*) as count
        FROM evaluaciones_en_curso 
        WHERE fecha_inicio >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
        AND estado = 'en_progreso'
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    }
}
?>