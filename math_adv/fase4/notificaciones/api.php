<?php
/**
 * Math Advantage - API para Notificaciones Push Web
 * Fase 4: Funcionalidades Avanzadas
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

require_once 'NotificationPushSystem.php';

try {
    $pushSystem = new NotificationPushSystem();
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'] ?? 'student';
    
    // Determinar acción
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    if (!$action) {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? null;
    }
    
    switch ($action) {
        
        case 'subscribe':
            // Suscribirse a notificaciones push
            $input = json_decode(file_get_contents('php://input'), true);
            $subscription = $input['subscription'];
            
            $result = $pushSystem->suscribirUsuario($userId, $userType, $subscription);
            echo json_encode($result);
            break;
            
        case 'unsubscribe':
            // Desuscribirse de notificaciones push
            $input = json_decode(file_get_contents('php://input'), true);
            $endpoint = $input['endpoint'] ?? '';
            
            $result = $pushSystem->desactivarSuscripcion($userId, $userType, $endpoint);
            echo json_encode($result);
            break;
            
        case 'send_test':
            // Enviar notificación de prueba
            $input = json_decode(file_get_contents('php://input'), true);
            
            $result = $pushSystem->enviarNotificacionRapida(
                $input['title'],
                $input['message'],
                [['user_id' => $userId, 'user_type' => $userType]],
                $input['options'] ?? []
            );
            
            echo json_encode($result);
            break;
            
        case 'send_notification':
            // Enviar notificación personalizada (solo admin/teacher)
            if (!in_array($userType, ['admin', 'teacher'])) {
                throw new Exception('No tienes permisos para enviar notificaciones');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $options = $input['options'] ?? [];
            $options['creado_por'] = $userId;
            
            $result = $pushSystem->enviarNotificacionRapida(
                $input['title'],
                $input['message'],
                $input['destinatarios'] ?? [['user_type' => 'todos']],
                $options
            );
            
            echo json_encode($result);
            break;
            
        case 'get_notifications':
            // Obtener notificaciones del usuario
            $notifications = $pushSystem->obtenerNotificacionesNoLeidas($userId, $userType);
            echo json_encode($notifications);
            break;
            
        case 'mark_read':
            // Marcar notificación como leída
            $input = json_decode(file_get_contents('php://input'), true);
            $notificationId = $input['notification_id'];
            
            $result = $pushSystem->marcarComoLeida($notificationId, $userId);
            echo json_encode($result);
            break;
            
        case 'mark_all_read':
            // Marcar todas las notificaciones como leídas
            $pdo = $pushSystem->pdo ?? (new Database())->getConnection();
            
            $stmt = $pdo->prepare("
                UPDATE destinatarios_notificacion 
                SET leida = 1, fecha_lectura = NOW() 
                WHERE (user_id = ? OR user_type = ?) AND leida = 0
            ");
            $stmt->execute([$userId, $userType]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'get_stats':
            // Obtener estadísticas (solo admin/teacher)
            if (!in_array($userType, ['admin', 'teacher'])) {
                // Para usuarios normales, devolver estadísticas básicas
                echo json_encode([
                    'total' => 0,
                    'delivered' => 0,
                    'read' => 0,
                    'active_subscriptions' => 1
                ]);
                break;
            }
            
            $stats = $pushSystem->obtenerEstadisticas(30);
            
            // Procesar estadísticas
            $totalNotifications = array_sum(array_column($stats, 'total_notificaciones'));
            $totalDelivered = array_sum(array_column($stats, 'entregadas'));
            $totalRead = array_sum(array_column($stats, 'leidas'));
            
            // Contar suscripciones activas
            $pdo = $pushSystem->pdo ?? (new Database())->getConnection();
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM suscripciones_push WHERE activa = 1");
            $activeSubscriptions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo json_encode([
                'total' => $totalNotifications,
                'delivered' => $totalDelivered,
                'read' => $totalRead,
                'active_subscriptions' => $activeSubscriptions,
                'chart_data' => array_slice($stats, 0, 7) // últimos 7 días
            ]);
            break;
            
        case 'get_public_key':
            // Obtener clave pública VAPID
            echo json_encode([
                'publicKey' => $pushSystem->obtenerClavePublica()
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'success' => false
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage(),
        'success' => false
    ]);
}
?>