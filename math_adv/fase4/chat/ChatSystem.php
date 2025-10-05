<?php
/**
 * Sistema de Chat en Tiempo Real para Math Advantage
 * Gestiona conversaciones, mensajes y notificaciones
 */

require_once '../../php/config.php';

class ChatSystem {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    /**
     * Crear nueva conversación
     */
    public function crearConversacion($data) {
        try {
            $this->pdo->beginTransaction();
            
            $sql = "INSERT INTO chat_conversaciones (
                nombre, tipo, descripcion, creador_id, configuracion
            ) VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['nombre'],
                $data['tipo'],
                $data['descripcion'] ?? null,
                $data['creador_id'],
                json_encode($data['configuracion'] ?? [])
            ]);
            
            $conversacionId = $this->pdo->lastInsertId();
            
            // Agregar participantes
            if (!empty($data['participantes'])) {
                $this->agregarParticipantes($conversacionId, $data['participantes']);
            }
            
            $this->pdo->commit();
            return $conversacionId;
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw new Exception("Error al crear conversación: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener conversaciones de un usuario
     */
    public function obtenerConversaciones($usuarioId, $tipo = null) {
        try {
            $sql = "SELECT DISTINCT c.*, 
                           p.rol as mi_rol,
                           (SELECT COUNT(*) FROM chat_participantes cp WHERE cp.conversacion_id = c.id) as total_participantes,
                           (SELECT COUNT(*) FROM chat_mensajes cm WHERE cm.conversacion_id = c.id AND cm.leido = 0 AND cm.usuario_id != ?) as mensajes_no_leidos,
                           u.nombre as creador_nombre,
                           (SELECT cm.contenido FROM chat_mensajes cm WHERE cm.conversacion_id = c.id ORDER BY cm.fecha_envio DESC LIMIT 1) as ultimo_mensaje,
                           (SELECT cm.fecha_envio FROM chat_mensajes cm WHERE cm.conversacion_id = c.id ORDER BY cm.fecha_envio DESC LIMIT 1) as fecha_ultimo_mensaje
                    FROM chat_conversaciones c
                    JOIN chat_participantes p ON c.id = p.conversacion_id
                    LEFT JOIN users u ON c.creador_id = u.id
                    WHERE p.usuario_id = ? AND p.activo = 1";
            
            $params = [$usuarioId, $usuarioId];
            
            if ($tipo) {
                $sql .= " AND c.tipo = ?";
                $params[] = $tipo;
            }
            
            $sql .= " ORDER BY fecha_ultimo_mensaje DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al obtener conversaciones: " . $e->getMessage());
        }
    }
    
    /**
     * Enviar mensaje
     */
    public function enviarMensaje($conversacionId, $usuarioId, $contenido, $tipoMensaje = 'texto', $adjuntos = []) {
        try {
            $this->pdo->beginTransaction();
            
            // Verificar que el usuario es participante
            if (!$this->esParticipante($conversacionId, $usuarioId)) {
                throw new Exception("No tienes permisos para enviar mensajes en esta conversación");
            }
            
            // Insertar mensaje
            $sql = "INSERT INTO chat_mensajes (
                conversacion_id, usuario_id, contenido, tipo_mensaje, adjuntos
            ) VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $conversacionId,
                $usuarioId,
                $contenido,
                $tipoMensaje,
                json_encode($adjuntos)
            ]);
            
            $mensajeId = $this->pdo->lastInsertId();
            
            // Actualizar última actividad de la conversación
            $this->actualizarUltimaActividad($conversacionId);
            
            // Marcar como no leído para otros participantes
            $this->marcarNoLeidoParaOtros($conversacionId, $usuarioId);
            
            $this->pdo->commit();
            
            // Obtener datos completos del mensaje
            return $this->obtenerMensajePorId($mensajeId);
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }
    
    /**
     * Obtener mensajes de una conversación
     */
    public function obtenerMensajes($conversacionId, $usuarioId, $limite = 50, $offset = 0) {
        try {
            // Verificar que el usuario es participante
            if (!$this->esParticipante($conversacionId, $usuarioId)) {
                throw new Exception("No tienes permisos para ver esta conversación");
            }
            
            $sql = "SELECT m.*, u.nombre as usuario_nombre, u.avatar as usuario_avatar
                    FROM chat_mensajes m
                    JOIN users u ON m.usuario_id = u.id
                    WHERE m.conversacion_id = ?
                    ORDER BY m.fecha_envio DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$conversacionId, $limite, $offset]);
            
            $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Marcar mensajes como leídos
            $this->marcarMensajesLeidos($conversacionId, $usuarioId);
            
            return array_reverse($mensajes); // Devolver en orden cronológico
            
        } catch (PDOException $e) {
            throw new Exception("Error al obtener mensajes: " . $e->getMessage());
        }
    }
    
    /**
     * Agregar participantes a una conversación
     */
    public function agregarParticipantes($conversacionId, $participantes) {
        try {
            $sql = "INSERT INTO chat_participantes (conversacion_id, usuario_id, rol, fecha_union)
                    VALUES (?, ?, ?, NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($participantes as $participante) {
                $stmt->execute([
                    $conversacionId,
                    $participante['usuario_id'],
                    $participante['rol'] ?? 'miembro'
                ]);
            }
            
            return true;
        } catch (PDOException $e) {
            throw new Exception("Error al agregar participantes: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener participantes de una conversación
     */
    public function obtenerParticipantes($conversacionId) {
        try {
            $sql = "SELECT p.*, u.nombre as usuario_nombre, u.avatar as usuario_avatar, u.email
                    FROM chat_participantes p
                    JOIN users u ON p.usuario_id = u.id
                    WHERE p.conversacion_id = ? AND p.activo = 1
                    ORDER BY p.fecha_union ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$conversacionId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al obtener participantes: " . $e->getMessage());
        }
    }
    
    /**
     * Buscar conversaciones y mensajes
     */
    public function buscar($usuarioId, $termino, $tipo = 'todos') {
        try {
            $resultados = [];
            
            // Buscar en conversaciones
            if ($tipo === 'todos' || $tipo === 'conversaciones') {
                $sql = "SELECT DISTINCT c.*, 'conversacion' as resultado_tipo
                        FROM chat_conversaciones c
                        JOIN chat_participantes p ON c.id = p.conversacion_id
                        WHERE p.usuario_id = ? AND (c.nombre LIKE ? OR c.descripcion LIKE ?)";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$usuarioId, "%$termino%", "%$termino%"]);
                
                $resultados['conversaciones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Buscar en mensajes
            if ($tipo === 'todos' || $tipo === 'mensajes') {
                $sql = "SELECT m.*, c.nombre as conversacion_nombre, u.nombre as usuario_nombre, 'mensaje' as resultado_tipo
                        FROM chat_mensajes m
                        JOIN chat_conversaciones c ON m.conversacion_id = c.id
                        JOIN chat_participantes p ON c.id = p.conversacion_id
                        JOIN users u ON m.usuario_id = u.id
                        WHERE p.usuario_id = ? AND m.contenido LIKE ?
                        ORDER BY m.fecha_envio DESC";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$usuarioId, "%$termino%"]);
                
                $resultados['mensajes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            return $resultados;
            
        } catch (PDOException $e) {
            throw new Exception("Error en la búsqueda: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener estadísticas de chat para un usuario
     */
    public function obtenerEstadisticasUsuario($usuarioId) {
        try {
            // Conversaciones totales
            $sql = "SELECT COUNT(DISTINCT p.conversacion_id) as total_conversaciones
                    FROM chat_participantes p
                    WHERE p.usuario_id = ? AND p.activo = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$usuarioId]);
            $totalConversaciones = $stmt->fetchColumn();
            
            // Mensajes enviados
            $sql = "SELECT COUNT(*) as mensajes_enviados
                    FROM chat_mensajes m
                    WHERE m.usuario_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$usuarioId]);
            $mensajesEnviados = $stmt->fetchColumn();
            
            // Mensajes no leídos
            $sql = "SELECT COUNT(*) as mensajes_no_leidos
                    FROM chat_mensajes m
                    JOIN chat_participantes p ON m.conversacion_id = p.conversacion_id
                    WHERE p.usuario_id = ? AND m.usuario_id != ? AND m.leido = 0";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$usuarioId, $usuarioId]);
            $mensajesNoLeidos = $stmt->fetchColumn();
            
            // Actividad semanal
            $sql = "SELECT DATE(m.fecha_envio) as fecha, COUNT(*) as mensajes
                    FROM chat_mensajes m
                    WHERE m.usuario_id = ? AND m.fecha_envio >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY DATE(m.fecha_envio)
                    ORDER BY fecha DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$usuarioId]);
            $actividadSemanal = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'total_conversaciones' => $totalConversaciones,
                'mensajes_enviados' => $mensajesEnviados,
                'mensajes_no_leidos' => $mensajesNoLeidos,
                'actividad_semanal' => $actividadSemanal
            ];
            
        } catch (PDOException $e) {
            throw new Exception("Error al obtener estadísticas: " . $e->getMessage());
        }
    }
    
    /**
     * Configurar notificaciones para una conversación
     */
    public function configurarNotificaciones($conversacionId, $usuarioId, $configuracion) {
        try {
            $sql = "UPDATE chat_participantes 
                    SET notificaciones = ?
                    WHERE conversacion_id = ? AND usuario_id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                json_encode($configuracion),
                $conversacionId,
                $usuarioId
            ]);
            
            return true;
        } catch (PDOException $e) {
            throw new Exception("Error al configurar notificaciones: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener conversaciones activas (para WebSocket)
     */
    public function obtenerConversacionesActivas($usuarioId) {
        try {
            $sql = "SELECT p.conversacion_id, p.usuario_id, p.ultima_conexion,
                           c.nombre as conversacion_nombre
                    FROM chat_participantes p
                    JOIN chat_conversaciones c ON p.conversacion_id = c.id
                    WHERE p.conversacion_id IN (
                        SELECT conversacion_id FROM chat_participantes WHERE usuario_id = ?
                    ) AND p.ultima_conexion > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$usuarioId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al obtener conversaciones activas: " . $e->getMessage());
        }
    }
    
    // Métodos auxiliares privados
    
    private function esParticipante($conversacionId, $usuarioId) {
        $sql = "SELECT COUNT(*) FROM chat_participantes 
                WHERE conversacion_id = ? AND usuario_id = ? AND activo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$conversacionId, $usuarioId]);
        return $stmt->fetchColumn() > 0;
    }
    
    private function actualizarUltimaActividad($conversacionId) {
        $sql = "UPDATE chat_conversaciones SET ultima_actividad = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$conversacionId]);
    }
    
    private function marcarNoLeidoParaOtros($conversacionId, $remitente) {
        $sql = "UPDATE chat_participantes 
                SET mensajes_no_leidos = mensajes_no_leidos + 1
                WHERE conversacion_id = ? AND usuario_id != ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$conversacionId, $remitente]);
    }
    
    private function marcarMensajesLeidos($conversacionId, $usuarioId) {
        $sql = "UPDATE chat_mensajes 
                SET leido = 1 
                WHERE conversacion_id = ? AND usuario_id != ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$conversacionId, $usuarioId]);
        
        // Resetear contador de no leídos
        $sql = "UPDATE chat_participantes 
                SET mensajes_no_leidos = 0 
                WHERE conversacion_id = ? AND usuario_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$conversacionId, $usuarioId]);
    }
    
    private function obtenerMensajePorId($mensajeId) {
        $sql = "SELECT m.*, u.nombre as usuario_nombre, u.avatar as usuario_avatar
                FROM chat_mensajes m
                JOIN users u ON m.usuario_id = u.id
                WHERE m.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$mensajeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>