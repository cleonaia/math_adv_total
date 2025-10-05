<?php
/**
 * Sistema de Gamificación y Recompensas
 * Math Advantage - Fase 4
 */

class GamificacionSystem {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtener perfil de gamificación del estudiante
     */
    public function obtenerPerfilEstudiante($student_id) {
        try {
            $sql = "SELECT eg.*, s.first_name, s.last_name 
                    FROM estudiantes_gamificacion eg
                    JOIN students s ON eg.student_id = s.id
                    WHERE eg.student_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$student_id]);
            $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$perfil) {
                // Crear perfil si no existe
                $this->crearPerfilEstudiante($student_id);
                $stmt->execute([$student_id]);
                $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // Obtener logros del estudiante
            $sql = "SELECT l.*, el.fecha_obtenido, el.progreso 
                    FROM estudiantes_logros el
                    JOIN logros l ON el.logro_id = l.id
                    WHERE el.student_id = ?
                    ORDER BY el.fecha_obtenido DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$student_id]);
            $perfil['logros'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular nivel y experiencia
            $perfil['nivel_actual'] = $this->calcularNivel($perfil['experiencia']);
            $perfil['experiencia_siguiente_nivel'] = $this->experienciaSiguienteNivel($perfil['nivel_actual']);
            $perfil['progreso_nivel'] = $this->calcularProgresoNivel($perfil['experiencia']);
            
            // Obtener ranking
            $perfil['ranking'] = $this->obtenerRanking($student_id);
            
            return $perfil;
        } catch (Exception $e) {
            throw new Exception("Error al obtener perfil: " . $e->getMessage());
        }
    }
    
    /**
     * Crear perfil de gamificación para nuevo estudiante
     */
    private function crearPerfilEstudiante($student_id) {
        try {
            $sql = "INSERT INTO estudiantes_gamificacion (
                student_id, puntos_totales, nivel, experiencia, 
                racha_dias, ultima_actividad
            ) VALUES (?, 0, 1, 0, 0, CURDATE())";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$student_id]);
        } catch (Exception $e) {
            throw new Exception("Error al crear perfil: " . $e->getMessage());
        }
    }
    
    /**
     * Agregar puntos y experiencia
     */
    public function agregarPuntos($student_id, $puntos, $razon = '', $datos_extra = []) {
        try {
            $this->pdo->beginTransaction();
            
            // Actualizar puntos y experiencia
            $sql = "UPDATE estudiantes_gamificacion 
                    SET puntos_totales = puntos_totales + ?,
                        experiencia = experiencia + ?,
                        ultima_actividad = CURDATE()
                    WHERE student_id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$puntos, $puntos, $student_id]);
            
            // Verificar si ha subido de nivel
            $nuevo_nivel = $this->verificarSubidaNivel($student_id);
            
            // Verificar logros desbloqueados
            $logros_nuevos = $this->verificarLogros($student_id, $razon, $datos_extra);
            
            // Actualizar racha si es necesario
            $this->actualizarRacha($student_id);
            
            $this->pdo->commit();
            
            return [
                'puntos_agregados' => $puntos,
                'nuevo_nivel' => $nuevo_nivel,
                'logros_desbloqueados' => $logros_nuevos
            ];
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw new Exception("Error al agregar puntos: " . $e->getMessage());
        }
    }
    
    /**
     * Verificar si el estudiante ha subido de nivel
     */
    private function verificarSubidaNivel($student_id) {
        try {
            $sql = "SELECT experiencia, nivel FROM estudiantes_gamificacion WHERE student_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$student_id]);
            $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $nivel_actual = $estudiante['nivel'];
            $nivel_calculado = $this->calcularNivel($estudiante['experiencia']);
            
            if ($nivel_calculado > $nivel_actual) {
                // Actualizar nivel en la base de datos
                $sql = "UPDATE estudiantes_gamificacion SET nivel = ? WHERE student_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$nivel_calculado, $student_id]);
                
                // Otorgar recompensa por subir de nivel
                $this->otorgarRecompensaNivel($student_id, $nivel_calculado);
                
                return $nivel_calculado;
            }
            
            return false;
        } catch (Exception $e) {
            throw new Exception("Error al verificar nivel: " . $e->getMessage());
        }
    }
    
    /**
     * Calcular nivel basado en experiencia
     */
    private function calcularNivel($experiencia) {
        // Fórmula: nivel = sqrt(experiencia / 100) + 1
        return floor(sqrt($experiencia / 100)) + 1;
    }
    
    /**
     * Calcular experiencia necesaria para el siguiente nivel
     */
    private function experienciaSiguienteNivel($nivel_actual) {
        return pow($nivel_actual, 2) * 100;
    }
    
    /**
     * Calcular progreso hacia el siguiente nivel (0-100%)
     */
    private function calcularProgresoNivel($experiencia) {
        $nivel_actual = $this->calcularNivel($experiencia);
        $exp_nivel_actual = pow($nivel_actual - 1, 2) * 100;
        $exp_siguiente_nivel = pow($nivel_actual, 2) * 100;
        
        if ($exp_siguiente_nivel == $exp_nivel_actual) return 100;
        
        $progreso = (($experiencia - $exp_nivel_actual) / ($exp_siguiente_nivel - $exp_nivel_actual)) * 100;
        return min(100, max(0, $progreso));
    }
    
    /**
     * Verificar logros desbloqueados
     */
    private function verificarLogros($student_id, $accion, $datos = []) {
        try {
            $sql = "SELECT l.* FROM logros l
                    WHERE l.activo = 1 
                    AND l.id NOT IN (
                        SELECT logro_id FROM estudiantes_logros 
                        WHERE student_id = ?
                    )";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$student_id]);
            $logros_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $logros_desbloqueados = [];
            
            foreach ($logros_disponibles as $logro) {
                $condiciones = json_decode($logro['condiciones'], true);
                
                if ($this->evaluarCondicionesLogro($student_id, $condiciones, $accion, $datos)) {
                    // Desbloquear logro
                    $this->desbloquearLogro($student_id, $logro['id']);
                    $logros_desbloqueados[] = $logro;
                    
                    // Agregar puntos de recompensa
                    if ($logro['puntos_recompensa'] > 0) {
                        $this->agregarPuntosDirectos($student_id, $logro['puntos_recompensa']);
                    }
                }
            }
            
            return $logros_desbloqueados;
        } catch (Exception $e) {
            throw new Exception("Error al verificar logros: " . $e->getMessage());
        }
    }
    
    /**
     * Evaluar si se cumplen las condiciones para un logro
     */
    private function evaluarCondicionesLogro($student_id, $condiciones, $accion, $datos) {
        try {
            // Obtener estadísticas del estudiante
            $stats = $this->obtenerEstadisticasEstudiante($student_id);
            
            foreach ($condiciones as $clave => $valor_requerido) {
                switch ($clave) {
                    case 'tareas_completadas':
                        if ($stats['tareas_completadas'] < $valor_requerido) return false;
                        break;
                        
                    case 'examen_perfecto':
                        if ($accion === 'examen_completado' && isset($datos['porcentaje'])) {
                            if ($datos['porcentaje'] < 100) return false;
                        } else {
                            if ($stats['examenes_perfectos'] < $valor_requerido) return false;
                        }
                        break;
                        
                    case 'racha_dias':
                        if ($stats['racha_actual'] < $valor_requerido) return false;
                        break;
                        
                    case 'tarea_temprana':
                        if ($accion === 'tarea_completada' && isset($datos['entrega_temprana'])) {
                            if (!$datos['entrega_temprana']) return false;
                        } else {
                            if ($stats['tareas_tempranas'] < $valor_requerido) return false;
                        }
                        break;
                        
                    case 'tareas_excelentes':
                        if ($stats['tareas_excelentes'] < $valor_requerido) return false;
                        break;
                        
                    default:
                        // Condición personalizada
                        if (!isset($stats[$clave]) || $stats[$clave] < $valor_requerido) {
                            return false;
                        }
                        break;
                }
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtener estadísticas del estudiante para evaluación de logros
     */
    private function obtenerEstadisticasEstudiante($student_id) {
        try {
            // Tareas completadas
            $sql = "SELECT COUNT(*) as tareas_completadas 
                    FROM respuestas_estudiantes re
                    JOIN evaluaciones e ON re.evaluacion_id = e.id
                    WHERE re.student_id = ? AND e.tipo = 'tarea'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$student_id]);
            $tareas = $stmt->fetch(PDO::FETCH_ASSOC)['tareas_completadas'];
            
            // Exámenes perfectos
            $sql = "SELECT COUNT(*) as examenes_perfectos 
                    FROM resultados_evaluaciones re
                    JOIN evaluaciones e ON re.evaluacion_id = e.id
                    WHERE re.student_id = ? AND e.tipo = 'examen' AND re.porcentaje = 100";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$student_id]);
            $examenes_perfectos = $stmt->fetch(PDO::FETCH_ASSOC)['examenes_perfectos'];
            
            // Racha actual
            $sql = "SELECT racha_dias FROM estudiantes_gamificacion WHERE student_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$student_id]);
            $racha = $stmt->fetch(PDO::FETCH_ASSOC)['racha_dias'];
            
            // Tareas con más del 90%
            $sql = "SELECT COUNT(*) as tareas_excelentes 
                    FROM resultados_evaluaciones re
                    JOIN evaluaciones e ON re.evaluacion_id = e.id
                    WHERE re.student_id = ? AND e.tipo = 'tarea' AND re.porcentaje >= 90";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$student_id]);
            $tareas_excelentes = $stmt->fetch(PDO::FETCH_ASSOC)['tareas_excelentes'];
            
            return [
                'tareas_completadas' => $tareas,
                'examenes_perfectos' => $examenes_perfectos,
                'racha_actual' => $racha,
                'tareas_excelentes' => $tareas_excelentes,
                'tareas_tempranas' => 0 // Se implementaría con lógica de fechas de entrega
            ];
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Desbloquear logro para estudiante
     */
    private function desbloquearLogro($student_id, $logro_id) {
        try {
            $sql = "INSERT INTO estudiantes_logros (student_id, logro_id, fecha_obtenido) 
                    VALUES (?, ?, NOW())";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$student_id, $logro_id]);
        } catch (Exception $e) {
            throw new Exception("Error al desbloquear logro: " . $e->getMessage());
        }
    }
    
    /**
     * Agregar puntos directos sin verificar logros
     */
    private function agregarPuntosDirectos($student_id, $puntos) {
        try {
            $sql = "UPDATE estudiantes_gamificacion 
                    SET puntos_totales = puntos_totales + ?,
                        experiencia = experiencia + ?
                    WHERE student_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$puntos, $puntos, $student_id]);
        } catch (Exception $e) {
            throw new Exception("Error al agregar puntos directos: " . $e->getMessage());
        }
    }
    
    /**
     * Actualizar racha de días activos
     */
    private function actualizarRacha($student_id) {
        try {
            $sql = "SELECT ultima_actividad, racha_dias FROM estudiantes_gamificacion 
                    WHERE student_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$student_id]);
            $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $ultima_actividad = new DateTime($estudiante['ultima_actividad']);
            $hoy = new DateTime();
            $ayer = new DateTime();
            $ayer->modify('-1 day');
            
            $nueva_racha = $estudiante['racha_dias'];
            
            if ($ultima_actividad->format('Y-m-d') === $ayer->format('Y-m-d')) {
                // Actividad ayer, continúa la racha
                $nueva_racha++;
            } elseif ($ultima_actividad->format('Y-m-d') !== $hoy->format('Y-m-d')) {
                // No hubo actividad ayer, se rompe la racha
                $nueva_racha = 1;
            }
            
            // Actualizar racha
            $sql = "UPDATE estudiantes_gamificacion 
                    SET racha_dias = ?, ultima_actividad = CURDATE() 
                    WHERE student_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$nueva_racha, $student_id]);
            
            return $nueva_racha;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Otorgar recompensa por subir de nivel
     */
    private function otorgarRecompensaNivel($student_id, $nivel) {
        try {
            $puntos_bonus = $nivel * 25; // 25 puntos por nivel
            
            $sql = "UPDATE estudiantes_gamificacion 
                    SET puntos_totales = puntos_totales + ?
                    WHERE student_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$puntos_bonus, $student_id]);
        } catch (Exception $e) {
            // Log error but don't throw
            error_log("Error al otorgar recompensa de nivel: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener ranking del estudiante
     */
    private function obtenerRanking($student_id) {
        try {
            $sql = "SELECT COUNT(*) + 1 as ranking 
                    FROM estudiantes_gamificacion eg1
                    JOIN estudiantes_gamificacion eg2 ON eg2.student_id = ?
                    WHERE eg1.puntos_totales > eg2.puntos_totales";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$student_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC)['ranking'];
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Obtener leaderboard (top estudiantes)
     */
    public function obtenerLeaderboard($limite = 10) {
        try {
            $sql = "SELECT eg.*, s.first_name, s.last_name,
                    ROW_NUMBER() OVER (ORDER BY eg.puntos_totales DESC) as ranking
                    FROM estudiantes_gamificacion eg
                    JOIN students s ON eg.student_id = s.id
                    ORDER BY eg.puntos_totales DESC
                    LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$limite]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al obtener leaderboard: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener todos los logros disponibles
     */
    public function obtenerTodosLosLogros() {
        try {
            $sql = "SELECT * FROM logros WHERE activo = 1 ORDER BY rareza DESC, puntos_recompensa DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al obtener logros: " . $e->getMessage());
        }
    }
    
    /**
     * Crear nuevo logro (para administradores)
     */
    public function crearLogro($datos) {
        try {
            $sql = "INSERT INTO logros (
                nombre, descripcion, icono, tipo, condiciones, 
                puntos_recompensa, rareza, activo
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $datos['nombre'],
                $datos['descripcion'],
                $datos['icono'],
                $datos['tipo'],
                json_encode($datos['condiciones']),
                $datos['puntos_recompensa'],
                $datos['rareza'],
                $datos['activo'] ? 1 : 0
            ]);
            
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Error al crear logro: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener configuraciones de gamificación
     */
    public function obtenerConfiguraciones() {
        try {
            $sql = "SELECT clave, valor FROM configuraciones_admin 
                    WHERE categoria = 'gamificacion'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $configuraciones = [];
            foreach ($configs as $config) {
                $configuraciones[$config['clave']] = $config['valor'];
            }
            
            return $configuraciones;
        } catch (Exception $e) {
            return [
                'gamificacion_activa' => 'true',
                'puntos_por_tarea' => '10',
                'puntos_por_examen' => '50'
            ];
        }
    }
}