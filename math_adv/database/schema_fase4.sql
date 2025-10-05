-- ===== SCHEMA FASE 4: MEJORAS AVANZADAS =====
-- Base de datos extendida para funcionalidades avanzadas
-- Math Advantage - Fase 4

-- Tabla para evaluaciones y exámenes
CREATE TABLE IF NOT EXISTS evaluaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    teacher_id INT NOT NULL,
    tipo ENUM('examen', 'quiz', 'tarea', 'evaluacion_continua') DEFAULT 'quiz',
    tiempo_limite INT DEFAULT NULL, -- en minutos
    intentos_permitidos INT DEFAULT 1,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    puntuacion_maxima DECIMAL(5,2) DEFAULT 100.00,
    mostrar_resultados BOOLEAN DEFAULT TRUE,
    estado ENUM('borrador', 'publicado', 'cerrado') DEFAULT 'borrador',
    configuracion JSON, -- configuraciones adicionales
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

-- Tabla para preguntas de evaluaciones
CREATE TABLE IF NOT EXISTS preguntas_evaluacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluacion_id INT NOT NULL,
    tipo_pregunta ENUM('multiple_choice', 'verdadero_falso', 'texto_libre', 'numerica', 'matching') NOT NULL,
    pregunta TEXT NOT NULL,
    opciones JSON, -- opciones para multiple choice, matching, etc.
    respuesta_correcta TEXT,
    puntos DECIMAL(5,2) DEFAULT 1.00,
    orden_pregunta INT DEFAULT 0,
    explicacion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evaluacion_id) REFERENCES evaluaciones(id) ON DELETE CASCADE
);

-- Tabla para respuestas de estudiantes
CREATE TABLE IF NOT EXISTS respuestas_estudiantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluacion_id INT NOT NULL,
    student_id INT NOT NULL,
    pregunta_id INT NOT NULL,
    respuesta TEXT,
    es_correcta BOOLEAN DEFAULT FALSE,
    puntos_obtenidos DECIMAL(5,2) DEFAULT 0.00,
    tiempo_respuesta INT, -- en segundos
    intento_numero INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evaluacion_id) REFERENCES evaluaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (pregunta_id) REFERENCES preguntas_evaluacion(id) ON DELETE CASCADE,
    UNIQUE KEY unique_respuesta (evaluacion_id, student_id, pregunta_id, intento_numero)
);

-- Tabla para resultados finales de evaluaciones
CREATE TABLE IF NOT EXISTS resultados_evaluaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluacion_id INT NOT NULL,
    student_id INT NOT NULL,
    puntuacion_total DECIMAL(5,2) NOT NULL,
    porcentaje DECIMAL(5,2) NOT NULL,
    tiempo_total INT, -- en segundos
    fecha_completada DATETIME NOT NULL,
    intento_numero INT DEFAULT 1,
    estado ENUM('en_progreso', 'completado', 'abandonado') DEFAULT 'en_progreso',
    comentarios TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evaluacion_id) REFERENCES evaluaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Tabla para videollamadas
CREATE TABLE IF NOT EXISTS videollamadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    host_id INT NOT NULL, -- profesor que organiza
    host_type ENUM('teacher', 'admin') DEFAULT 'teacher',
    room_id VARCHAR(100) UNIQUE NOT NULL, -- ID único para la sala
    fecha_programada DATETIME NOT NULL,
    duracion_estimada INT DEFAULT 60, -- en minutos
    tipo ENUM('clase', 'tutoria', 'reunion', 'webinar') DEFAULT 'clase',
    estado ENUM('programada', 'en_curso', 'finalizada', 'cancelada') DEFAULT 'programada',
    max_participantes INT DEFAULT 50,
    requiere_aprobacion BOOLEAN DEFAULT FALSE,
    grabacion_activa BOOLEAN DEFAULT FALSE,
    configuracion JSON, -- configuraciones de la videollamada
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla para participantes de videollamadas
CREATE TABLE IF NOT EXISTS participantes_videollamadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    videollamada_id INT NOT NULL,
    user_id INT NOT NULL,
    user_type ENUM('student', 'parent', 'teacher', 'admin') NOT NULL,
    estado ENUM('invitado', 'confirmado', 'rechazado', 'presente', 'ausente') DEFAULT 'invitado',
    hora_entrada DATETIME NULL,
    hora_salida DATETIME NULL,
    tiempo_conexion INT DEFAULT 0, -- en minutos
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (videollamada_id) REFERENCES videollamadas(id) ON DELETE CASCADE
);

-- Tabla para sistema de gamificación - Logros
CREATE TABLE IF NOT EXISTS logros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    icono VARCHAR(100), -- clase de Font Awesome o ruta de imagen
    tipo ENUM('academico', 'participacion', 'tiempo', 'especial') DEFAULT 'academico',
    condiciones JSON, -- condiciones para obtener el logro
    puntos_recompensa INT DEFAULT 0,
    rareza ENUM('comun', 'raro', 'epico', 'legendario') DEFAULT 'comun',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla para puntos y logros de estudiantes
CREATE TABLE IF NOT EXISTS estudiantes_gamificacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    puntos_totales INT DEFAULT 0,
    nivel INT DEFAULT 1,
    experiencia INT DEFAULT 0,
    racha_dias INT DEFAULT 0, -- días consecutivos activo
    ultima_actividad DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_gamification (student_id)
);

-- Tabla para logros obtenidos por estudiantes
CREATE TABLE IF NOT EXISTS estudiantes_logros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    logro_id INT NOT NULL,
    fecha_obtenido DATETIME DEFAULT CURRENT_TIMESTAMP,
    progreso JSON, -- progreso hacia el logro
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (logro_id) REFERENCES logros(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_achievement (student_id, logro_id)
);

-- Tabla para calendario y reservas
CREATE TABLE IF NOT EXISTS eventos_calendario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    tipo ENUM('clase', 'examen', 'tutoria', 'evento', 'feriado', 'reunion') DEFAULT 'clase',
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    todo_el_dia BOOLEAN DEFAULT FALSE,
    color VARCHAR(7) DEFAULT '#8b5cf6', -- código hexadecimal
    teacher_id INT NULL,
    aula VARCHAR(100),
    max_reservas INT DEFAULT 1,
    requiere_aprobacion BOOLEAN DEFAULT FALSE,
    es_publico BOOLEAN DEFAULT TRUE,
    configuracion JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
);

-- Tabla para reservas de eventos
CREATE TABLE IF NOT EXISTS reservas_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    user_id INT NOT NULL,
    user_type ENUM('student', 'parent', 'teacher') NOT NULL,
    estado ENUM('pendiente', 'confirmado', 'cancelado', 'completado') DEFAULT 'pendiente',
    comentarios TEXT,
    fecha_reserva DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evento_id) REFERENCES eventos_calendario(id) ON DELETE CASCADE
);

-- Tabla para chat en tiempo real - Conversaciones
CREATE TABLE IF NOT EXISTS conversaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255), -- nombre del grupo o null para chat directo
    tipo ENUM('directo', 'grupo', 'clase', 'soporte') DEFAULT 'directo',
    descripcion TEXT,
    icono VARCHAR(100),
    es_privado BOOLEAN DEFAULT TRUE,
    creado_por INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla para participantes de conversaciones
CREATE TABLE IF NOT EXISTS participantes_conversacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversacion_id INT NOT NULL,
    user_id INT NOT NULL,
    user_type ENUM('student', 'parent', 'teacher', 'admin') NOT NULL,
    rol ENUM('miembro', 'admin', 'moderador') DEFAULT 'miembro',
    fecha_union DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultimo_mensaje_leido INT DEFAULT 0,
    notificaciones_activas BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (conversacion_id) REFERENCES conversaciones(id) ON DELETE CASCADE
);

-- Tabla para mensajes de chat
CREATE TABLE IF NOT EXISTS mensajes_chat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversacion_id INT NOT NULL,
    user_id INT NOT NULL,
    user_type ENUM('student', 'parent', 'teacher', 'admin') NOT NULL,
    mensaje TEXT NOT NULL,
    tipo_mensaje ENUM('texto', 'imagen', 'archivo', 'audio', 'video', 'sistema') DEFAULT 'texto',
    archivo_url VARCHAR(500),
    respondiendo_a INT NULL, -- ID del mensaje al que responde
    editado BOOLEAN DEFAULT FALSE,
    fecha_edicion DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversacion_id) REFERENCES conversaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (respondiendo_a) REFERENCES mensajes_chat(id) ON DELETE SET NULL
);

-- Tabla para notificaciones push web
CREATE TABLE IF NOT EXISTS notificaciones_push (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    mensaje TEXT NOT NULL,
    icono VARCHAR(500),
    imagen VARCHAR(500),
    url_destino VARCHAR(500),
    tipo ENUM('general', 'academica', 'administrativa', 'social', 'sistema') DEFAULT 'general',
    prioridad ENUM('baja', 'normal', 'alta', 'urgente') DEFAULT 'normal',
    programada_para DATETIME NULL, -- NULL para envío inmediato
    enviada BOOLEAN DEFAULT FALSE,
    fecha_envio DATETIME NULL,
    creado_por INT NOT NULL,
    configuracion JSON, -- configuraciones adicionales
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla para destinatarios de notificaciones
CREATE TABLE IF NOT EXISTS destinatarios_notificacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notificacion_id INT NOT NULL,
    user_id INT NULL, -- NULL para envío masivo
    user_type ENUM('student', 'parent', 'teacher', 'admin', 'todos') DEFAULT 'todos',
    filtros JSON, -- filtros adicionales (clase, curso, etc.)
    entregada BOOLEAN DEFAULT FALSE,
    leida BOOLEAN DEFAULT FALSE,
    fecha_entrega DATETIME NULL,
    fecha_lectura DATETIME NULL,
    FOREIGN KEY (notificacion_id) REFERENCES notificaciones_push(id) ON DELETE CASCADE
);

-- Tabla para suscripciones push (endpoints de usuarios)
CREATE TABLE IF NOT EXISTS suscripciones_push (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('student', 'parent', 'teacher', 'admin') NOT NULL,
    endpoint VARCHAR(500) NOT NULL,
    p256dh VARCHAR(255) NOT NULL,
    auth VARCHAR(255) NOT NULL,
    user_agent TEXT,
    activa BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_endpoint (user_id, user_type, endpoint(255))
);

-- Tabla para configuraciones del administrador
CREATE TABLE IF NOT EXISTS configuraciones_admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    tipo ENUM('texto', 'numero', 'booleano', 'json', 'archivo') DEFAULT 'texto',
    categoria VARCHAR(100) DEFAULT 'general',
    descripcion TEXT,
    actualizable_usuario BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar configuraciones por defecto
INSERT INTO configuraciones_admin (clave, valor, tipo, categoria, descripcion) VALUES
('gamificacion_activa', 'true', 'booleano', 'gamificacion', 'Activar sistema de gamificación'),
('puntos_por_tarea', '10', 'numero', 'gamificacion', 'Puntos otorgados por tarea completada'),
('puntos_por_examen', '50', 'numero', 'gamificacion', 'Puntos otorgados por examen aprobado'),
('videollamadas_max_duracion', '120', 'numero', 'videollamadas', 'Duración máxima de videollamadas en minutos'),
('chat_max_archivos_mb', '10', 'numero', 'chat', 'Tamaño máximo de archivos en chat (MB)'),
('notificaciones_push_activas', 'true', 'booleano', 'notificaciones', 'Activar notificaciones push'),
('evaluaciones_tiempo_limite_defecto', '60', 'numero', 'evaluaciones', 'Tiempo límite por defecto para evaluaciones (minutos)')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

-- Insertar logros por defecto
INSERT INTO logros (nombre, descripcion, icono, tipo, condiciones, puntos_recompensa, rareza) VALUES
('Primer Paso', 'Completa tu primera tarea', 'fas fa-baby', 'academico', '{"tareas_completadas": 1}', 10, 'comun'),
('Estudiante Dedicado', 'Completa 10 tareas', 'fas fa-graduation-cap', 'academico', '{"tareas_completadas": 10}', 50, 'raro'),
('Maestro del Tiempo', 'Completa una tarea antes de tiempo', 'fas fa-clock', 'tiempo', '{"tarea_temprana": 1}', 25, 'raro'),
('Perfectionist', 'Obtén 100% en un examen', 'fas fa-star', 'academico', '{"examen_perfecto": 1}', 100, 'epico'),
('Racha de Fuego', 'Mantén una racha de 7 días consecutivos', 'fas fa-fire', 'participacion', '{"racha_dias": 7}', 75, 'epico'),
('Leyenda Matemática', 'Completa 100 tareas con más de 90%', 'fas fa-trophy', 'academico', '{"tareas_excelentes": 100}', 500, 'legendario')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- Índices adicionales para optimización
CREATE INDEX idx_evaluaciones_teacher ON evaluaciones(teacher_id);
CREATE INDEX idx_evaluaciones_fecha ON evaluaciones(fecha_inicio, fecha_fin);
CREATE INDEX idx_respuestas_estudiante ON respuestas_estudiantes(student_id, evaluacion_id);
CREATE INDEX idx_videollamadas_fecha ON videollamadas(fecha_programada);
CREATE INDEX idx_mensajes_conversacion ON mensajes_chat(conversacion_id, created_at);
CREATE INDEX idx_notificaciones_programada ON notificaciones_push(programada_para);
CREATE INDEX idx_eventos_fecha ON eventos_calendario(fecha_inicio, fecha_fin);