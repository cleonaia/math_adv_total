<?php
/**
 * Sistema de Calendario para Math Advantage
 * Gestiona eventos, reservas de clases y citas
 */

require_once '../../php/config.php';

class CalendarioSystem {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    /**
     * Crear un nuevo evento
     */
    public function crearEvento($data) {
        try {
            $sql = "INSERT INTO calendario_eventos (
                titulo, descripcion, fecha_inicio, fecha_fin, 
                tipo_evento, profesor_id, estudiante_id, aula,
                color, estado, configuracion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['titulo'],
                $data['descripcion'],
                $data['fecha_inicio'],
                $data['fecha_fin'],
                $data['tipo_evento'],
                $data['profesor_id'] ?? null,
                $data['estudiante_id'] ?? null,
                $data['aula'] ?? null,
                $data['color'] ?? '#007bff',
                $data['estado'] ?? 'programado',
                json_encode($data['configuracion'] ?? [])
            ]);
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Error al crear evento: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener eventos por rango de fechas
     */
    public function obtenerEventos($fechaInicio, $fechaFin, $filtros = []) {
        try {
            $sql = "SELECT e.*, 
                           p.nombre as profesor_nombre, 
                           s.nombre as estudiante_nombre,
                           COUNT(r.id) as num_reservas
                    FROM calendario_eventos e
                    LEFT JOIN teachers p ON e.profesor_id = p.id
                    LEFT JOIN students s ON e.estudiante_id = s.id
                    LEFT JOIN calendario_reservas r ON e.id = r.evento_id
                    WHERE e.fecha_inicio >= ? AND e.fecha_fin <= ?";
            
            $params = [$fechaInicio, $fechaFin];
            
            // Aplicar filtros
            if (!empty($filtros['tipo_evento'])) {
                $sql .= " AND e.tipo_evento = ?";
                $params[] = $filtros['tipo_evento'];
            }
            
            if (!empty($filtros['profesor_id'])) {
                $sql .= " AND e.profesor_id = ?";
                $params[] = $filtros['profesor_id'];
            }
            
            if (!empty($filtros['estado'])) {
                $sql .= " AND e.estado = ?";
                $params[] = $filtros['estado'];
            }
            
            $sql .= " GROUP BY e.id ORDER BY e.fecha_inicio ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al obtener eventos: " . $e->getMessage());
        }
    }
    
    /**
     * Crear reserva para un evento
     */
    public function crearReserva($eventoId, $estudianteId, $comentarios = '') {
        try {
            $this->pdo->beginTransaction();
            
            // Verificar si el evento existe y acepta reservas
            $evento = $this->obtenerEventoPorId($eventoId);
            if (!$evento) {
                throw new Exception("El evento no existe");
            }
            
            // Verificar capacidad
            $reservasActuales = $this->contarReservas($eventoId);
            $capacidadMaxima = json_decode($evento['configuracion'], true)['capacidad_maxima'] ?? 1;
            
            if ($reservasActuales >= $capacidadMaxima) {
                throw new Exception("El evento ha alcanzado su capacidad máxima");
            }
            
            // Verificar si ya existe una reserva
            $reservaExistente = $this->verificarReservaExistente($eventoId, $estudianteId);
            if ($reservaExistente) {
                throw new Exception("Ya tienes una reserva para este evento");
            }
            
            // Crear la reserva
            $sql = "INSERT INTO calendario_reservas (
                evento_id, estudiante_id, estado, comentarios, fecha_reserva
            ) VALUES (?, ?, 'confirmada', ?, NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$eventoId, $estudianteId, $comentarios]);
            
            $reservaId = $this->pdo->lastInsertId();
            
            // Registrar en historial
            $this->registrarHistorial($eventoId, 'reserva_creada', [
                'reserva_id' => $reservaId,
                'estudiante_id' => $estudianteId
            ]);
            
            $this->pdo->commit();
            return $reservaId;
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }
    
    /**
     * Obtener disponibilidad de horarios
     */
    public function obtenerDisponibilidad($profesorId, $fecha) {
        try {
            $sql = "SELECT d.*
                    FROM calendario_disponibilidad d
                    WHERE d.profesor_id = ? 
                    AND d.dia_semana = DAYOFWEEK(?) - 1
                    AND d.activo = 1
                    ORDER BY d.hora_inicio";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$profesorId, $fecha]);
            
            $disponibilidad = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener eventos ya programados para esa fecha
            $eventosExistentes = $this->obtenerEventosPorFecha($profesorId, $fecha);
            
            // Marcar horarios ocupados
            foreach ($disponibilidad as &$horario) {
                $horario['ocupado'] = false;
                $horario['eventos'] = [];
                
                foreach ($eventosExistentes as $evento) {
                    $horaInicioEvento = date('H:i:s', strtotime($evento['fecha_inicio']));
                    $horaFinEvento = date('H:i:s', strtotime($evento['fecha_fin']));
                    
                    if ($this->horariosSeSuperponen(
                        $horario['hora_inicio'], $horario['hora_fin'],
                        $horaInicioEvento, $horaFinEvento
                    )) {
                        $horario['ocupado'] = true;
                        $horario['eventos'][] = $evento;
                    }
                }
            }
            
            return $disponibilidad;
        } catch (PDOException $e) {
            throw new Exception("Error al obtener disponibilidad: " . $e->getMessage());
        }
    }
    
    /**
     * Configurar disponibilidad de profesor
     */
    public function configurarDisponibilidad($profesorId, $disponibilidades) {
        try {
            $this->pdo->beginTransaction();
            
            // Limpiar disponibilidad anterior
            $sql = "DELETE FROM calendario_disponibilidad WHERE profesor_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$profesorId]);
            
            // Insertar nueva disponibilidad
            $sql = "INSERT INTO calendario_disponibilidad (
                profesor_id, dia_semana, hora_inicio, hora_fin, 
                capacidad_maxima, tipo_disponibilidad
            ) VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($disponibilidades as $disp) {
                $stmt->execute([
                    $profesorId,
                    $disp['dia_semana'],
                    $disp['hora_inicio'],
                    $disp['hora_fin'],
                    $disp['capacidad_maxima'] ?? 1,
                    $disp['tipo_disponibilidad'] ?? 'clase_individual'
                ]);
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }
    
    /**
     * Obtener reservas de un estudiante
     */
    public function obtenerReservasEstudiante($estudianteId, $fechaInicio = null, $fechaFin = null) {
        try {
            $sql = "SELECT r.*, e.titulo, e.descripcion, e.fecha_inicio, e.fecha_fin,
                           e.tipo_evento, e.aula, p.nombre as profesor_nombre
                    FROM calendario_reservas r
                    JOIN calendario_eventos e ON r.evento_id = e.id
                    LEFT JOIN teachers p ON e.profesor_id = p.id
                    WHERE r.estudiante_id = ?";
            
            $params = [$estudianteId];
            
            if ($fechaInicio && $fechaFin) {
                $sql .= " AND e.fecha_inicio >= ? AND e.fecha_fin <= ?";
                $params[] = $fechaInicio;
                $params[] = $fechaFin;
            }
            
            $sql .= " ORDER BY e.fecha_inicio ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al obtener reservas: " . $e->getMessage());
        }
    }
    
    /**
     * Cancelar reserva
     */
    public function cancelarReserva($reservaId, $estudianteId) {
        try {
            $this->pdo->beginTransaction();
            
            // Verificar que la reserva pertenece al estudiante
            $sql = "SELECT * FROM calendario_reservas WHERE id = ? AND estudiante_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$reservaId, $estudianteId]);
            
            $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$reserva) {
                throw new Exception("Reserva no encontrada");
            }
            
            // Verificar si puede cancelarse (por ejemplo, con 24h de anticipación)
            $horasAnticipacion = 24;
            $fechaEvento = $this->obtenerFechaEvento($reserva['evento_id']);
            $horasRestantes = (strtotime($fechaEvento) - time()) / 3600;
            
            if ($horasRestantes < $horasAnticipacion) {
                throw new Exception("No se puede cancelar con menos de {$horasAnticipacion} horas de anticipación");
            }
            
            // Actualizar estado de la reserva
            $sql = "UPDATE calendario_reservas 
                    SET estado = 'cancelada', fecha_cancelacion = NOW()
                    WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$reservaId]);
            
            // Registrar en historial
            $this->registrarHistorial($reserva['evento_id'], 'reserva_cancelada', [
                'reserva_id' => $reservaId,
                'estudiante_id' => $estudianteId
            ]);
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }
    
    /**
     * Obtener estadísticas del calendario
     */
    public function obtenerEstadisticas($profesorId = null, $fechaInicio = null, $fechaFin = null) {
        try {
            $where = "WHERE 1=1";
            $params = [];
            
            if ($profesorId) {
                $where .= " AND e.profesor_id = ?";
                $params[] = $profesorId;
            }
            
            if ($fechaInicio && $fechaFin) {
                $where .= " AND e.fecha_inicio >= ? AND e.fecha_fin <= ?";
                $params[] = $fechaInicio;
                $params[] = $fechaFin;
            }
            
            // Eventos totales
            $sql = "SELECT 
                        COUNT(*) as total_eventos,
                        COUNT(CASE WHEN estado = 'programado' THEN 1 END) as eventos_programados,
                        COUNT(CASE WHEN estado = 'completado' THEN 1 END) as eventos_completados,
                        COUNT(CASE WHEN estado = 'cancelado' THEN 1 END) as eventos_cancelados
                    FROM calendario_eventos e $where";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $estadisticasEventos = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Reservas totales
            $sql = "SELECT 
                        COUNT(*) as total_reservas,
                        COUNT(CASE WHEN r.estado = 'confirmada' THEN 1 END) as reservas_confirmadas,
                        COUNT(CASE WHEN r.estado = 'cancelada' THEN 1 END) as reservas_canceladas
                    FROM calendario_reservas r
                    JOIN calendario_eventos e ON r.evento_id = e.id
                    $where";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $estadisticasReservas = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Ocupación por día de la semana
            $sql = "SELECT 
                        DAYOFWEEK(e.fecha_inicio) as dia_semana,
                        COUNT(*) as eventos_count
                    FROM calendario_eventos e 
                    $where 
                    GROUP BY DAYOFWEEK(e.fecha_inicio)
                    ORDER BY dia_semana";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $ocupacionSemana = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'eventos' => $estadisticasEventos,
                'reservas' => $estadisticasReservas,
                'ocupacion_semana' => $ocupacionSemana
            ];
            
        } catch (PDOException $e) {
            throw new Exception("Error al obtener estadísticas: " . $e->getMessage());
        }
    }
    
    // Métodos auxiliares privados
    
    private function obtenerEventoPorId($eventoId) {
        $sql = "SELECT * FROM calendario_eventos WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$eventoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function contarReservas($eventoId) {
        $sql = "SELECT COUNT(*) FROM calendario_reservas WHERE evento_id = ? AND estado = 'confirmada'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$eventoId]);
        return $stmt->fetchColumn();
    }
    
    private function verificarReservaExistente($eventoId, $estudianteId) {
        $sql = "SELECT id FROM calendario_reservas WHERE evento_id = ? AND estudiante_id = ? AND estado = 'confirmada'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$eventoId, $estudianteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function obtenerEventosPorFecha($profesorId, $fecha) {
        $sql = "SELECT * FROM calendario_eventos 
                WHERE profesor_id = ? AND DATE(fecha_inicio) = ? 
                ORDER BY fecha_inicio";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$profesorId, $fecha]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function horariosSeSuperponen($inicio1, $fin1, $inicio2, $fin2) {
        return ($inicio1 < $fin2) && ($fin1 > $inicio2);
    }
    
    private function obtenerFechaEvento($eventoId) {
        $sql = "SELECT fecha_inicio FROM calendario_eventos WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$eventoId]);
        return $stmt->fetchColumn();
    }
    
    private function registrarHistorial($eventoId, $accion, $detalles = []) {
        $sql = "INSERT INTO calendario_historial (evento_id, accion, detalles, fecha_accion)
                VALUES (?, ?, ?, NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$eventoId, $accion, json_encode($detalles)]);
    }
}
?>