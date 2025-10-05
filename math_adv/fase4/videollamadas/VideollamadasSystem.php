<?php
/**
 * Sistema de Videollamadas Integrado
 * Math Advantage - Fase 4
 * Integración con WebRTC y Jitsi Meet
 */

class VideollamadasSystem {
    private $pdo;
    private $jitsi_domain;
    private $jwt_secret;
    
    public function __construct($pdo, $config = []) {
        $this->pdo = $pdo;
        $this->jitsi_domain = $config['jitsi_domain'] ?? 'meet.jit.si';
        $this->jwt_secret = $config['jwt_secret'] ?? '';
    }
    
    /**
     * Crear nueva videollamada
     */
    public function crearVideollamada($datos) {
        try {
            $room_id = $this->generarRoomId();
            
            $sql = "INSERT INTO videollamadas (
                titulo, descripcion, host_id, host_type, room_id,
                fecha_programada, duracion_estimada, tipo, max_participantes,
                requiere_aprobacion, grabacion_activa, configuracion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $datos['titulo'],
                $datos['descripcion'],
                $datos['host_id'],
                $datos['host_type'],
                $room_id,
                $datos['fecha_programada'],
                $datos['duracion_estimada'] ?? 60,
                $datos['tipo'],
                $datos['max_participantes'] ?? 50,
                $datos['requiere_aprobacion'] ? 1 : 0,
                $datos['grabacion_activa'] ? 1 : 0,
                json_encode($datos['configuracion'] ?? [])
            ]);
            
            $videollamada_id = $this->pdo->lastInsertId();
            
            // Agregar participantes si se especificaron
            if (!empty($datos['participantes'])) {
                $this->agregarParticipantes($videollamada_id, $datos['participantes']);
            }
            
            return $videollamada_id;
        } catch (Exception $e) {
            throw new Exception("Error al crear videollamada: " . $e->getMessage());
        }
    }
    
    /**
     * Agregar participantes a videollamada
     */
    public function agregarParticipantes($videollamada_id, $participantes) {
        try {
            $sql = "INSERT INTO participantes_videollamadas (
                videollamada_id, user_id, user_type, estado
            ) VALUES (?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($participantes as $participante) {
                $stmt->execute([
                    $videollamada_id,
                    $participante['user_id'],
                    $participante['user_type'],
                    'invitado'
                ]);
            }
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Error al agregar participantes: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener videollamadas por usuario
     */
    public function obtenerVideollamadasUsuario($user_id, $user_type) {
        try {
            $sql = "SELECT v.*, 
                    COUNT(DISTINCT p.id) as total_participantes,
                    pv.estado as mi_estado
                    FROM videollamadas v
                    LEFT JOIN participantes_videollamadas p ON v.id = p.videollamada_id
                    LEFT JOIN participantes_videollamadas pv ON v.id = pv.videollamada_id 
                        AND pv.user_id = ? AND pv.user_type = ?
                    WHERE v.host_id = ? AND v.host_type = ?
                    OR pv.user_id = ?
                    GROUP BY v.id
                    ORDER BY v.fecha_programada ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$user_id, $user_type, $user_id, $user_type, $user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al obtener videollamadas: " . $e->getMessage());
        }
    }
    
    /**
     * Unirse a videollamada
     */
    public function unirseVideollamada($videollamada_id, $user_id, $user_type) {
        try {
            // Verificar si la videollamada existe y está activa
            $sql = "SELECT * FROM videollamadas WHERE id = ? AND estado IN ('programada', 'en_curso')";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$videollamada_id]);
            $videollamada = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$videollamada) {
                throw new Exception("Videollamada no encontrada o finalizada");
            }
            
            // Verificar límite de participantes
            $sql = "SELECT COUNT(*) as participantes_actuales FROM participantes_videollamadas 
                    WHERE videollamada_id = ? AND estado = 'presente'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$videollamada_id]);
            $participantes_actuales = $stmt->fetch(PDO::FETCH_ASSOC)['participantes_actuales'];
            
            if ($participantes_actuales >= $videollamada['max_participantes']) {
                throw new Exception("Videollamada llena. Máximo {$videollamada['max_participantes']} participantes");
            }
            
            // Registrar entrada
            $sql = "UPDATE participantes_videollamadas SET 
                    estado = 'presente', hora_entrada = NOW()
                    WHERE videollamada_id = ? AND user_id = ? AND user_type = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$videollamada_id, $user_id, $user_type]);
            
            // Si no existía el participante, agregarlo
            if ($stmt->rowCount() === 0) {
                $sql = "INSERT INTO participantes_videollamadas (
                    videollamada_id, user_id, user_type, estado, hora_entrada
                ) VALUES (?, ?, ?, 'presente', NOW())";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$videollamada_id, $user_id, $user_type]);
            }
            
            // Actualizar estado de videollamada a 'en_curso' si es la primera vez
            if ($videollamada['estado'] === 'programada') {
                $sql = "UPDATE videollamadas SET estado = 'en_curso' WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$videollamada_id]);
            }
            
            return [
                'room_id' => $videollamada['room_id'],
                'jwt_token' => $this->generarJWTToken($videollamada, $user_id, $user_type),
                'config' => json_decode($videollamada['configuracion'], true)
            ];
        } catch (Exception $e) {
            throw new Exception("Error al unirse a videollamada: " . $e->getMessage());
        }
    }
    
    /**
     * Salir de videollamada
     */
    public function salirVideollamada($videollamada_id, $user_id, $user_type) {
        try {
            // Calcular tiempo de conexión
            $sql = "SELECT hora_entrada FROM participantes_videollamadas 
                    WHERE videollamada_id = ? AND user_id = ? AND user_type = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$videollamada_id, $user_id, $user_type]);
            $participante = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($participante && $participante['hora_entrada']) {
                $tiempo_conexion = (strtotime('now') - strtotime($participante['hora_entrada'])) / 60;
                
                $sql = "UPDATE participantes_videollamadas SET 
                        estado = 'ausente', hora_salida = NOW(), tiempo_conexion = ?
                        WHERE videollamada_id = ? AND user_id = ? AND user_type = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$tiempo_conexion, $videollamada_id, $user_id, $user_type]);
            }
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Error al salir de videollamada: " . $e->getMessage());
        }
    }
    
    /**
     * Finalizar videollamada
     */
    public function finalizarVideollamada($videollamada_id, $host_id, $host_type) {
        try {
            // Verificar permisos del host
            $sql = "SELECT * FROM videollamadas WHERE id = ? AND host_id = ? AND host_type = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$videollamada_id, $host_id, $host_type]);
            $videollamada = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$videollamada) {
                throw new Exception("No tienes permisos para finalizar esta videollamada");
            }
            
            // Finalizar videollamada
            $sql = "UPDATE videollamadas SET estado = 'finalizada' WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$videollamada_id]);
            
            // Marcar todos los participantes como ausentes si aún están presentes
            $sql = "UPDATE participantes_videollamadas SET 
                    estado = 'ausente', hora_salida = NOW()
                    WHERE videollamada_id = ? AND estado = 'presente'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$videollamada_id]);
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Error al finalizar videollamada: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener estadísticas de videollamada
     */
    public function obtenerEstadisticas($videollamada_id) {
        try {
            $sql = "SELECT 
                    v.*,
                    COUNT(DISTINCT p.user_id) as total_participantes,
                    AVG(p.tiempo_conexion) as tiempo_promedio_conexion,
                    MAX(p.tiempo_conexion) as tiempo_maximo_conexion
                    FROM videollamadas v
                    LEFT JOIN participantes_videollamadas p ON v.id = p.videollamada_id
                    WHERE v.id = ?
                    GROUP BY v.id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$videollamada_id]);
            $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obtener lista detallada de participantes
            $sql = "SELECT p.*, 
                    CASE p.user_type
                        WHEN 'student' THEN s.first_name
                        WHEN 'teacher' THEN t.first_name
                        WHEN 'parent' THEN pr.first_name
                        ELSE 'Admin'
                    END as nombre,
                    CASE p.user_type
                        WHEN 'student' THEN s.last_name
                        WHEN 'teacher' THEN t.last_name
                        WHEN 'parent' THEN pr.last_name
                        ELSE 'User'
                    END as apellido
                    FROM participantes_videollamadas p
                    LEFT JOIN students s ON p.user_id = s.id AND p.user_type = 'student'
                    LEFT JOIN teachers t ON p.user_id = t.id AND p.user_type = 'teacher'
                    LEFT JOIN parents pr ON p.user_id = pr.id AND p.user_type = 'parent'
                    WHERE p.videollamada_id = ?
                    ORDER BY p.hora_entrada ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$videollamada_id]);
            $estadisticas['participantes_detalle'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $estadisticas;
        } catch (Exception $e) {
            throw new Exception("Error al obtener estadísticas: " . $e->getMessage());
        }
    }
    
    /**
     * Generar ID único para la sala
     */
    private function generarRoomId() {
        return 'mathadvantage_' . uniqid() . '_' . time();
    }
    
    /**
     * Generar JWT token para Jitsi Meet (si se usa JWT)
     */
    private function generarJWTToken($videollamada, $user_id, $user_type) {
        if (empty($this->jwt_secret)) {
            return null;
        }
        
        // Obtener información del usuario
        $usuario = $this->obtenerInfoUsuario($user_id, $user_type);
        
        $payload = [
            'iss' => 'mathadvantage',
            'aud' => 'jitsi',
            'exp' => time() + 3600, // Válido por 1 hora
            'sub' => $this->jitsi_domain,
            'room' => $videollamada['room_id'],
            'context' => [
                'user' => [
                    'id' => $user_id,
                    'name' => $usuario['nombre_completo'],
                    'email' => $usuario['email'],
                    'avatar' => $usuario['avatar'] ?? ''
                ],
                'features' => [
                    'livestreaming' => $videollamada['host_id'] == $user_id,
                    'recording' => $videollamada['grabacion_activa'],
                    'transcription' => false
                ]
            ],
            'moderator' => $videollamada['host_id'] == $user_id
        ];
        
        return $this->jwt_encode($payload, $this->jwt_secret);
    }
    
    /**
     * Obtener información del usuario
     */
    private function obtenerInfoUsuario($user_id, $user_type) {
        try {
            $table = $user_type . 's';
            $sql = "SELECT first_name, last_name, email FROM {$table} WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'nombre_completo' => $user['first_name'] . ' ' . $user['last_name'],
                'email' => $user['email']
            ];
        } catch (Exception $e) {
            return [
                'nombre_completo' => 'Usuario',
                'email' => ''
            ];
        }
    }
    
    /**
     * Codificar JWT (implementación básica)
     */
    private function jwt_encode($payload, $secret) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode($payload);
        
        $headerEncoded = $this->base64UrlEncode($header);
        $payloadEncoded = $this->base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, $secret, true);
        $signatureEncoded = $this->base64UrlEncode($signature);
        
        return $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;
    }
    
    /**
     * Codificación Base64 URL-safe
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Obtener configuración máxima de duración
     */
    public function obtenerConfiguracion() {
        try {
            $sql = "SELECT clave, valor FROM configuraciones_admin 
                    WHERE categoria = 'videollamadas'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $configuracion = [];
            foreach ($configs as $config) {
                $configuracion[$config['clave']] = $config['valor'];
            }
            
            return $configuracion;
        } catch (Exception $e) {
            return [
                'videollamadas_max_duracion' => 120,
                'max_participantes_defecto' => 50
            ];
        }
    }
}