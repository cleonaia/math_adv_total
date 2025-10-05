<?php
/**
 * Math Advantage - Sistema de Notificaciones Push Web
 * Fase 4: Funcionalidades Avanzadas
 */

require_once '../../php/classes/Database.php';

class NotificationPushSystem {
    private $pdo;
    private $vapidKeys;
    
    public function __construct() {
        $database = new Database();
        $this->pdo = $database->getConnection();
        
        // VAPID keys para Web Push (generar en producción)
        $this->vapidKeys = [
            'publicKey' => 'BEl62iUYgUivxIkv69yViEuiBIa40HcCWLWU6Q4ZFRgJFWxmQ47dFK5xUE6KrSlNQ6E0PqGCh3_J0b8xAH4-SSo',
            'privateKey' => 'UfPx7zfQo2ArqM-K3PApEOtk-Y5nykjw1oPMKLNLJG8'
        ];
    }
    
    /**
     * Suscribir usuario a notificaciones push
     */
    public function suscribirUsuario($userId, $userType, $subscription) {
        try {
            // Verificar si ya existe una suscripción activa
            $stmt = $this->pdo->prepare("
                SELECT id FROM suscripciones_push 
                WHERE user_id = ? AND user_type = ? AND endpoint = ? AND activa = 1
            ");
            $stmt->execute([$userId, $userType, $subscription['endpoint']]);
            
            if ($stmt->fetch()) {
                return ['success' => true, 'message' => 'Suscripción ya existe'];
            }
            
            // Insertar nueva suscripción
            $stmt = $this->pdo->prepare("
                INSERT INTO suscripciones_push 
                (user_id, user_type, endpoint, p256dh, auth, user_agent, activa) 
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            
            $stmt->execute([
                $userId,
                $userType,
                $subscription['endpoint'],
                $subscription['keys']['p256dh'],
                $subscription['keys']['auth'],
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
            
            return [
                'success' => true, 
                'message' => 'Suscripción creada correctamente',
                'id' => $this->pdo->lastInsertId()
            ];
            
        } catch (PDOException $e) {
            throw new Exception("Error al suscribir usuario: " . $e->getMessage());
        }
    }
    
    /**
     * Crear nueva notificación push
     */
    public function crearNotificacion($titulo, $mensaje, $opciones = []) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notificaciones_push 
                (titulo, mensaje, icono, imagen, url_destino, tipo, prioridad, 
                 programada_para, creado_por, configuracion) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $titulo,
                $mensaje,
                $opciones['icono'] ?? '/img/logo_math-advantatge.png',
                $opciones['imagen'] ?? null,
                $opciones['url_destino'] ?? '/portal/welcome.php',
                $opciones['tipo'] ?? 'general',
                $opciones['prioridad'] ?? 'normal',
                $opciones['programada_para'] ?? null,
                $opciones['creado_por'] ?? 1,
                json_encode($opciones['configuracion'] ?? [])
            ]);
            
            return $this->pdo->lastInsertId();
            
        } catch (PDOException $e) {
            throw new Exception("Error al crear notificación: " . $e->getMessage());
        }
    }
    
    /**
     * Enviar notificación a usuarios específicos
     */
    public function enviarNotificacion($notificacionId, $destinatarios) {
        try {
            // Obtener datos de la notificación
            $stmt = $this->pdo->prepare("SELECT * FROM notificaciones_push WHERE id = ?");
            $stmt->execute([$notificacionId]);
            $notificacion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$notificacion) {
                throw new Exception("Notificación no encontrada");
            }
            
            $enviosExitosos = 0;
            $errores = [];
            
            foreach ($destinatarios as $destinatario) {
                // Insertar destinatario
                $stmt = $this->pdo->prepare("
                    INSERT INTO destinatarios_notificacion 
                    (notificacion_id, user_id, user_type, filtros) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $notificacionId,
                    $destinatario['user_id'] ?? null,
                    $destinatario['user_type'] ?? 'todos',
                    json_encode($destinatario['filtros'] ?? [])
                ]);
                
                // Obtener suscripciones del usuario
                $suscripciones = $this->obtenerSuscripciones($destinatario);
                
                foreach ($suscripciones as $suscripcion) {
                    try {
                        $resultado = $this->enviarPushNotification(
                            $suscripcion,
                            $notificacion
                        );
                        
                        if ($resultado) {
                            $enviosExitosos++;
                            $this->marcarComoEntregada($notificacionId, $destinatario['user_id']);
                        }
                        
                    } catch (Exception $e) {
                        $errores[] = "Error enviando a {$destinatario['user_id']}: " . $e->getMessage();
                    }
                }
            }
            
            // Marcar notificación como enviada
            $stmt = $this->pdo->prepare("
                UPDATE notificaciones_push 
                SET enviada = 1, fecha_envio = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$notificacionId]);
            
            return [
                'success' => true,
                'enviados' => $enviosExitosos,
                'errores' => $errores
            ];
            
        } catch (Exception $e) {
            throw new Exception("Error al enviar notificación: " . $e->getMessage());
        }
    }
    
    /**
     * Enviar push notification usando cURL
     */
    private function enviarPushNotification($suscripcion, $notificacion) {
        $payload = json_encode([
            'title' => $notificacion['titulo'],
            'body' => $notificacion['mensaje'],
            'icon' => $notificacion['icono'],
            'image' => $notificacion['imagen'],
            'badge' => '/img/badge.png',
            'url' => $notificacion['url_destino'],
            'tag' => 'math-advantage-' . $notificacion['id'],
            'requireInteraction' => $notificacion['prioridad'] === 'urgente',
            'actions' => [
                [
                    'action' => 'view',
                    'title' => 'Ver',
                    'icon' => '/img/view-icon.png'
                ],
                [
                    'action' => 'dismiss',
                    'title' => 'Cerrar',
                    'icon' => '/img/close-icon.png'
                ]
            ]
        ]);
        
        // Headers para Web Push
        $headers = [
            'Content-Type: application/octet-stream',
            'Content-Length: ' . strlen($payload),
            'TTL: 86400',
            'Content-Encoding: aes128gcm'
        ];
        
        // Configurar cURL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $suscripcion['endpoint'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }
        
        // Verificar respuesta exitosa
        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        } else {
            throw new Exception("HTTP Error: " . $httpCode . " - " . $result);
        }
    }
    
    /**
     * Obtener suscripciones de usuarios
     */
    private function obtenerSuscripciones($destinatario) {
        $sql = "SELECT * FROM suscripciones_push WHERE activa = 1";
        $params = [];
        
        if (isset($destinatario['user_id']) && $destinatario['user_id']) {
            $sql .= " AND user_id = ?";
            $params[] = $destinatario['user_id'];
        }
        
        if (isset($destinatario['user_type']) && $destinatario['user_type'] !== 'todos') {
            $sql .= " AND user_type = ?";
            $params[] = $destinatario['user_type'];
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Marcar notificación como entregada
     */
    private function marcarComoEntregada($notificacionId, $userId) {
        $stmt = $this->pdo->prepare("
            UPDATE destinatarios_notificacion 
            SET entregada = 1, fecha_entrega = NOW() 
            WHERE notificacion_id = ? AND user_id = ?
        ");
        $stmt->execute([$notificacionId, $userId]);
    }
    
    /**
     * Marcar notificación como leída
     */
    public function marcarComoLeida($notificacionId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE destinatarios_notificacion 
                SET leida = 1, fecha_lectura = NOW() 
                WHERE notificacion_id = ? AND user_id = ?
            ");
            $stmt->execute([$notificacionId, $userId]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            throw new Exception("Error al marcar como leída: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener estadísticas de notificaciones
     */
    public function obtenerEstadisticas($dias = 30) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    n.tipo,
                    n.prioridad,
                    COUNT(n.id) as total_notificaciones,
                    COUNT(d.id) as total_destinatarios,
                    SUM(CASE WHEN d.entregada = 1 THEN 1 ELSE 0 END) as entregadas,
                    SUM(CASE WHEN d.leida = 1 THEN 1 ELSE 0 END) as leidas,
                    DATE(n.created_at) as fecha
                FROM notificaciones_push n
                LEFT JOIN destinatarios_notificacion d ON n.id = d.notificacion_id
                WHERE n.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY n.tipo, n.prioridad, DATE(n.created_at)
                ORDER BY fecha DESC
            ");
            $stmt->execute([$dias]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            throw new Exception("Error al obtener estadísticas: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener notificaciones no leídas de un usuario
     */
    public function obtenerNotificacionesNoLeidas($userId, $userType) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT n.*, d.entregada, d.leida, d.fecha_entrega
                FROM notificaciones_push n
                INNER JOIN destinatarios_notificacion d ON n.id = d.notificacion_id
                WHERE (d.user_id = ? OR d.user_type = 'todos' OR d.user_type = ?)
                AND d.leida = 0
                ORDER BY n.created_at DESC
                LIMIT 20
            ");
            $stmt->execute([$userId, $userType]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            throw new Exception("Error al obtener notificaciones: " . $e->getMessage());
        }
    }
    
    /**
     * Desactivar suscripción
     */
    public function desactivarSuscripcion($userId, $userType, $endpoint) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE suscripciones_push 
                SET activa = 0 
                WHERE user_id = ? AND user_type = ? AND endpoint = ?
            ");
            $stmt->execute([$userId, $userType, $endpoint]);
            
            return ['success' => true, 'message' => 'Suscripción desactivada'];
            
        } catch (PDOException $e) {
            throw new Exception("Error al desactivar suscripción: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener claves VAPID públicas
     */
    public function obtenerClavePublica() {
        return $this->vapidKeys['publicKey'];
    }
    
    /**
     * Enviar notificación rápida (método helper)
     */
    public function enviarNotificacionRapida($titulo, $mensaje, $destinatarios, $opciones = []) {
        try {
            // Crear notificación
            $notificacionId = $this->crearNotificacion($titulo, $mensaje, $opciones);
            
            // Enviar inmediatamente
            return $this->enviarNotificacion($notificacionId, $destinatarios);
            
        } catch (Exception $e) {
            throw new Exception("Error al enviar notificación rápida: " . $e->getMessage());
        }
    }
}
?>