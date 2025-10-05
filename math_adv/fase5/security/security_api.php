<?php
/**
 * Math Advantage - Security API
 * API para el dashboard de ciberseguridad
 */

require_once '../../php/config.php';
require_once '../../php/classes/Database.php';
require_once '../../php/classes/SecurityManager.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

$security = SecurityManager::getInstance();

// Verificar autenticación de administrador
if (!$security->validateAccess('admin', 'security')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Accés no autoritzat']);
    exit;
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $action = $_GET['action'] ?? '';
    
    switch($action) {
        case 'dashboard':
            $data = getSecurityDashboardData($pdo);
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'export_report':
            exportSecurityReport($pdo);
            break;
            
        case 'update_config':
            $input = json_decode(file_get_contents('php://input'), true);
            updateSecurityConfig($pdo, $input);
            echo json_encode(['success' => true]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Acció no vàlida']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error intern del servidor']);
    error_log("Security API Error: " . $e->getMessage());
}

function getSecurityDashboardData($pdo) {
    $data = [];
    
    // Critical incidents in last 24h
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM security_logs 
                         WHERE severity = 'critical' 
                         AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $data['critical_incidents'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Failed logins in last hour
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM login_attempts 
                         WHERE success = FALSE 
                         AND attempt_time >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $data['failed_logins'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Currently blocked IPs
    $stmt = $pdo->query("SELECT COUNT(DISTINCT ip_address) as count FROM login_attempts 
                         WHERE blocked_until > NOW()");
    $data['blocked_ips'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Active sessions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM secure_sessions 
                         WHERE is_active = TRUE AND expires_at > NOW()");
    $data['active_sessions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Recent alerts
    $stmt = $pdo->prepare("SELECT event_type, severity, created_at, data 
                           FROM security_logs 
                           WHERE severity IN ('critical', 'warning') 
                           AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                           ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $data['alerts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent security logs
    $stmt = $pdo->prepare("SELECT id, event_type, severity, ip_address, user_id, created_at 
                           FROM security_logs 
                           ORDER BY created_at DESC LIMIT 20");
    $stmt->execute();
    $data['recent_logs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate threat level
    $criticalCount = $data['critical_incidents'];
    $failedLoginCount = $data['failed_logins'];
    
    if ($criticalCount > 5 || $failedLoginCount > 50) {
        $data['threat_level'] = 'high';
    } elseif ($criticalCount > 2 || $failedLoginCount > 20) {
        $data['threat_level'] = 'medium';
    } else {
        $data['threat_level'] = 'low';
    }
    
    return $data;
}

function exportSecurityReport($pdo) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="security_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Headers
    fputcsv($output, ['Timestamp', 'Event Type', 'Severity', 'IP Address', 'User ID', 'Data']);
    
    // Data
    $stmt = $pdo->prepare("SELECT created_at, event_type, severity, ip_address, user_id, data 
                           FROM security_logs 
                           WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                           ORDER BY created_at DESC");
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['created_at'],
            $row['event_type'],
            $row['severity'],
            $row['ip_address'],
            $row['user_id'],
            $row['data']
        ]);
    }
    
    fclose($output);
}

function updateSecurityConfig($pdo, $config) {
    $updates = [
        'max_login_attempts' => $config['max_login_attempts'] ?? 5,
        'lockout_duration' => ($config['lockout_duration'] ?? 15) * 60, // Convert to seconds
        'session_timeout' => ($config['session_timeout'] ?? 60) * 60 // Convert to seconds
    ];
    
    foreach ($updates as $key => $value) {
        $stmt = $pdo->prepare("UPDATE security_config SET config_value = ? WHERE config_key = ?");
        $stmt->execute([$value, $key]);
    }
}
?>