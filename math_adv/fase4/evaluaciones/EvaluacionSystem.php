<?php
/**
 * Sistema de Evaluaciones y Exámenes Online
 * Math Advantage - Fase 4
 */

class EvaluacionSystem {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Crear nueva evaluación
     */
    public function crearEvaluacion($datos) {
        try {
            $sql = "INSERT INTO evaluaciones (
                titulo, descripcion, teacher_id, tipo, tiempo_limite, 
                intentos_permitidos, fecha_inicio, fecha_fin, 
                puntuacion_maxima, mostrar_resultados, configuracion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $datos['titulo'],
                $datos['descripcion'],
                $datos['teacher_id'],
                $datos['tipo'],
                $datos['tiempo_limite'],
                $datos['intentos_permitidos'],
                $datos['fecha_inicio'],
                $datos['fecha_fin'],
                $datos['puntuacion_maxima'],
                $datos['mostrar_resultados'] ? 1 : 0,
                json_encode($datos['configuracion'] ?? [])
            ]);
            
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Error al crear evaluación: " . $e->getMessage());
        }
    }
    
    /**
     * Agregar pregunta a evaluación
     */
    public function agregarPregunta($evaluacion_id, $pregunta_data) {
        try {
            $sql = "INSERT INTO preguntas_evaluacion (
                evaluacion_id, tipo_pregunta, pregunta, opciones, 
                respuesta_correcta, puntos, orden_pregunta, explicacion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $evaluacion_id,
                $pregunta_data['tipo'],
                $pregunta_data['pregunta'],
                json_encode($pregunta_data['opciones'] ?? []),
                $pregunta_data['respuesta_correcta'],
                $pregunta_data['puntos'],
                $pregunta_data['orden'] ?? 0,
                $pregunta_data['explicacion'] ?? ''
            ]);
            
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Error al agregar pregunta: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener evaluaciones por profesor
     */
    public function obtenerEvaluacionesProfesor($teacher_id) {
        try {
            $sql = "SELECT e.*, 
                    COUNT(DISTINCT p.id) as total_preguntas,
                    COUNT(DISTINCT r.student_id) as total_estudiantes
                    FROM evaluaciones e
                    LEFT JOIN preguntas_evaluacion p ON e.id = p.evaluacion_id
                    LEFT JOIN resultados_evaluaciones r ON e.id = r.evaluacion_id
                    WHERE e.teacher_id = ?
                    GROUP BY e.id
                    ORDER BY e.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$teacher_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al obtener evaluaciones: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener evaluaciones disponibles para estudiante
     */
    public function obtenerEvaluacionesEstudiante($student_id) {
        try {
            $sql = "SELECT e.*, 
                    t.first_name as profesor_nombre,
                    COUNT(DISTINCT p.id) as total_preguntas,
                    r.puntuacion_total,
                    r.porcentaje,
                    r.estado as resultado_estado,
                    r.fecha_completada
                    FROM evaluaciones e
                    JOIN teachers t ON e.teacher_id = t.id
                    LEFT JOIN preguntas_evaluacion p ON e.id = p.evaluacion_id
                    LEFT JOIN resultados_evaluaciones r ON e.id = r.evaluacion_id AND r.student_id = ?
                    WHERE e.estado = 'publicado' 
                    AND e.fecha_inicio <= NOW() 
                    AND e.fecha_fin >= NOW()
                    GROUP BY e.id
                    ORDER BY e.fecha_fin ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$student_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al obtener evaluaciones: " . $e->getMessage());
        }
    }
    
    /**
     * Iniciar evaluación para estudiante
     */
    public function iniciarEvaluacion($evaluacion_id, $student_id) {
        try {
            // Verificar si ya existe un intento
            $sql = "SELECT COUNT(*) as intentos FROM resultados_evaluaciones 
                    WHERE evaluacion_id = ? AND student_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$evaluacion_id, $student_id]);
            $intentos = $stmt->fetch(PDO::FETCH_ASSOC)['intentos'];
            
            // Obtener límite de intentos
            $sql = "SELECT intentos_permitidos FROM evaluaciones WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$evaluacion_id]);
            $limite_intentos = $stmt->fetch(PDO::FETCH_ASSOC)['intentos_permitidos'];
            
            if ($intentos >= $limite_intentos) {
                throw new Exception("Has agotado los intentos permitidos para esta evaluación");
            }
            
            // Crear nuevo resultado
            $sql = "INSERT INTO resultados_evaluaciones (
                evaluacion_id, student_id, puntuacion_total, porcentaje, 
                fecha_completada, intento_numero, estado
            ) VALUES (?, ?, 0, 0, NOW(), ?, 'en_progreso')";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$evaluacion_id, $student_id, $intentos + 1]);
            
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Error al iniciar evaluación: " . $e->getMessage());
        }
    }
    
    /**
     * Guardar respuesta de estudiante
     */
    public function guardarRespuesta($evaluacion_id, $student_id, $pregunta_id, $respuesta, $intento = 1) {
        try {
            // Obtener respuesta correcta
            $sql = "SELECT respuesta_correcta, puntos FROM preguntas_evaluacion WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$pregunta_id]);
            $pregunta = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $es_correcta = $this->evaluarRespuesta($respuesta, $pregunta['respuesta_correcta']);
            $puntos = $es_correcta ? $pregunta['puntos'] : 0;
            
            // Insertar o actualizar respuesta
            $sql = "INSERT INTO respuestas_estudiantes (
                evaluacion_id, student_id, pregunta_id, respuesta, 
                es_correcta, puntos_obtenidos, intento_numero
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                respuesta = VALUES(respuesta),
                es_correcta = VALUES(es_correcta),
                puntos_obtenidos = VALUES(puntos_obtenidos)";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $evaluacion_id, $student_id, $pregunta_id, 
                $respuesta, $es_correcta, $puntos, $intento
            ]);
        } catch (Exception $e) {
            throw new Exception("Error al guardar respuesta: " . $e->getMessage());
        }
    }
    
    /**
     * Finalizar evaluación y calcular calificación
     */
    public function finalizarEvaluacion($evaluacion_id, $student_id, $intento = 1) {
        try {
            // Calcular puntuación total
            $sql = "SELECT SUM(puntos_obtenidos) as puntos_totales 
                    FROM respuestas_estudiantes 
                    WHERE evaluacion_id = ? AND student_id = ? AND intento_numero = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$evaluacion_id, $student_id, $intento]);
            $puntos_totales = $stmt->fetch(PDO::FETCH_ASSOC)['puntos_totales'] ?? 0;
            
            // Obtener puntuación máxima
            $sql = "SELECT puntuacion_maxima FROM evaluaciones WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$evaluacion_id]);
            $puntuacion_maxima = $stmt->fetch(PDO::FETCH_ASSOC)['puntuacion_maxima'];
            
            $porcentaje = ($puntos_totales / $puntuacion_maxima) * 100;
            
            // Actualizar resultado
            $sql = "UPDATE resultados_evaluaciones SET 
                    puntuacion_total = ?, porcentaje = ?, 
                    fecha_completada = NOW(), estado = 'completado'
                    WHERE evaluacion_id = ? AND student_id = ? AND intento_numero = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$puntos_totales, $porcentaje, $evaluacion_id, $student_id, $intento]);
            
            // Actualizar gamificación si está activa
            $this->actualizarGamificacion($student_id, 'examen_completado', [
                'puntuacion' => $puntos_totales,
                'porcentaje' => $porcentaje
            ]);
            
            return [
                'puntuacion_total' => $puntos_totales,
                'puntuacion_maxima' => $puntuacion_maxima,
                'porcentaje' => $porcentaje
            ];
        } catch (Exception $e) {
            throw new Exception("Error al finalizar evaluación: " . $e->getMessage());
        }
    }
    
    /**
     * Evaluar si una respuesta es correcta
     */
    private function evaluarRespuesta($respuesta_estudiante, $respuesta_correcta) {
        // Normalizar respuestas para comparación
        $respuesta_estudiante = trim(strtolower($respuesta_estudiante));
        $respuesta_correcta = trim(strtolower($respuesta_correcta));
        
        return $respuesta_estudiante === $respuesta_correcta;
    }
    
    /**
     * Actualizar sistema de gamificación
     */
    private function actualizarGamificacion($student_id, $accion, $datos = []) {
        try {
            // Verificar si la gamificación está activa
            $sql = "SELECT valor FROM configuraciones_admin WHERE clave = 'gamificacion_activa'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $gamificacion_activa = $stmt->fetch(PDO::FETCH_ASSOC)['valor'] ?? 'false';
            
            if ($gamificacion_activa !== 'true') {
                return;
            }
            
            $puntos = 0;
            
            switch ($accion) {
                case 'examen_completado':
                    $sql = "SELECT valor FROM configuraciones_admin WHERE clave = 'puntos_por_examen'";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute();
                    $puntos = intval($stmt->fetch(PDO::FETCH_ASSOC)['valor'] ?? 50);
                    
                    // Bonus por examen perfecto
                    if ($datos['porcentaje'] >= 100) {
                        $puntos *= 2; // Doble puntos por examen perfecto
                    }
                    break;
                    
                case 'tarea_completada':
                    $sql = "SELECT valor FROM configuraciones_admin WHERE clave = 'puntos_por_tarea'";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute();
                    $puntos = intval($stmt->fetch(PDO::FETCH_ASSOC)['valor'] ?? 10);
                    break;
            }
            
            if ($puntos > 0) {
                $this->agregarPuntosEstudiante($student_id, $puntos);
            }
        } catch (Exception $e) {
            // Log error but don't throw - gamification is not critical
            error_log("Error en gamificación: " . $e->getMessage());
        }
    }
    
    /**
     * Agregar puntos a estudiante
     */
    private function agregarPuntosEstudiante($student_id, $puntos) {
        try {
            $sql = "INSERT INTO estudiantes_gamificacion (student_id, puntos_totales, experiencia, ultima_actividad) 
                    VALUES (?, ?, ?, CURDATE())
                    ON DUPLICATE KEY UPDATE 
                        puntos_totales = puntos_totales + VALUES(puntos_totales),
                        experiencia = experiencia + VALUES(experiencia),
                        ultima_actividad = VALUES(ultima_actividad)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$student_id, $puntos, $puntos]);
        } catch (Exception $e) {
            error_log("Error al agregar puntos: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener estadísticas de evaluación
     */
    public function obtenerEstadisticasEvaluacion($evaluacion_id) {
        try {
            $sql = "SELECT 
                    COUNT(DISTINCT r.student_id) as total_participantes,
                    AVG(r.porcentaje) as promedio_porcentaje,
                    MAX(r.porcentaje) as mejor_puntuacion,
                    MIN(r.porcentaje) as peor_puntuacion,
                    COUNT(CASE WHEN r.porcentaje >= 70 THEN 1 END) as aprobados,
                    COUNT(CASE WHEN r.porcentaje < 70 THEN 1 END) as reprobados
                    FROM resultados_evaluaciones r 
                    WHERE r.evaluacion_id = ? AND r.estado = 'completado'";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$evaluacion_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al obtener estadísticas: " . $e->getMessage());
        }
    }
    
    /**
     * Exportar resultados a CSV
     */
    public function exportarResultados($evaluacion_id) {
        try {
            $sql = "SELECT 
                    s.first_name, s.last_name, s.email,
                    r.puntuacion_total, r.porcentaje, r.fecha_completada,
                    r.intento_numero
                    FROM resultados_evaluaciones r
                    JOIN students s ON r.student_id = s.id
                    WHERE r.evaluacion_id = ? AND r.estado = 'completado'
                    ORDER BY r.porcentaje DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$evaluacion_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al exportar resultados: " . $e->getMessage());
        }
    }
}