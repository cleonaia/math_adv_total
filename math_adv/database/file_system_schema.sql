-- Math Advantage - File Management System Tables
-- Extensión de la base de datos para sistema de archivos

-- Tabla para archivos de clases (materiales, ejercicios, etc.)
CREATE TABLE IF NOT EXISTS class_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT NOT NULL,
    file_type VARCHAR(10) NOT NULL,
    class_id INT,
    uploaded_by INT NOT NULL,
    upload_type ENUM('material', 'theory', 'exercise', 'homework', 'submission', 'resource') NOT NULL,
    title VARCHAR(200),
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    download_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_class_files_class_id (class_id),
    INDEX idx_class_files_uploaded_by (uploaded_by),
    INDEX idx_class_files_upload_type (upload_type),
    INDEX idx_class_files_created_at (created_at)
);

-- Tabla para registrar descargas de archivos
CREATE TABLE IF NOT EXISTS file_downloads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_id INT NOT NULL,
    user_id INT NOT NULL,
    user_type ENUM('student', 'parent', 'teacher', 'admin') NOT NULL,
    downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    INDEX idx_file_downloads_file_id (file_id),
    INDEX idx_file_downloads_user (user_id, user_type),
    INDEX idx_file_downloads_date (downloaded_at),
    FOREIGN KEY (file_id) REFERENCES class_files(id) ON DELETE CASCADE
);

-- Tabla para carpetas organizativas (opcional, para organización avanzada)
CREATE TABLE IF NOT EXISTS file_folders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    class_id INT NOT NULL,
    parent_folder_id INT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_file_folders_class_id (class_id),
    INDEX idx_file_folders_parent (parent_folder_id),
    FOREIGN KEY (parent_folder_id) REFERENCES file_folders(id) ON DELETE CASCADE
);

-- Tabla para permisos de archivos (para control granular)
CREATE TABLE IF NOT EXISTS file_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_id INT NOT NULL,
    user_id INT,
    user_type ENUM('student', 'parent', 'teacher', 'admin'),
    permission_type ENUM('read', 'write', 'delete') NOT NULL,
    granted_by INT NOT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_file_permissions_file_id (file_id),
    INDEX idx_file_permissions_user (user_id, user_type),
    FOREIGN KEY (file_id) REFERENCES class_files(id) ON DELETE CASCADE
);

-- Tabla para comentarios en archivos (feedback de profesores/estudiantes)
CREATE TABLE IF NOT EXISTS file_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_id INT NOT NULL,
    user_id INT NOT NULL,
    user_type ENUM('student', 'parent', 'teacher', 'admin') NOT NULL,
    comment TEXT NOT NULL,
    is_private BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_file_comments_file_id (file_id),
    INDEX idx_file_comments_user (user_id, user_type),
    INDEX idx_file_comments_created_at (created_at),
    FOREIGN KEY (file_id) REFERENCES class_files(id) ON DELETE CASCADE
);

-- Tabla para tareas/assignments (ejercicios con fecha límite)
CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    class_id INT NOT NULL,
    teacher_id INT NOT NULL,
    file_id INT,
    due_date DATETIME,
    max_points DECIMAL(5,2) DEFAULT 100.00,
    is_active BOOLEAN DEFAULT TRUE,
    submission_allowed BOOLEAN DEFAULT TRUE,
    late_submission_allowed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_assignments_class_id (class_id),
    INDEX idx_assignments_teacher_id (teacher_id),
    INDEX idx_assignments_due_date (due_date),
    FOREIGN KEY (file_id) REFERENCES class_files(id) ON DELETE SET NULL
);

-- Tabla para entregas de estudiantes
CREATE TABLE IF NOT EXISTS student_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    file_id INT,
    submission_text TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    grade DECIMAL(5,2),
    teacher_feedback TEXT,
    graded_at TIMESTAMP NULL,
    graded_by INT,
    is_late BOOLEAN DEFAULT FALSE,
    attempt_number INT DEFAULT 1,
    INDEX idx_student_submissions_assignment_id (assignment_id),
    INDEX idx_student_submissions_student_id (student_id),
    INDEX idx_student_submissions_submitted_at (submitted_at),
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (file_id) REFERENCES class_files(id) ON DELETE SET NULL,
    UNIQUE KEY unique_submission (assignment_id, student_id, attempt_number)
);

-- Actualizar la tabla existente login_attempts si es necesario
-- (Esta ya debería existir de la implementación anterior)

-- Crear triggers para actualizar contadores automáticamente
DELIMITER $$

CREATE TRIGGER update_download_count 
AFTER INSERT ON file_downloads 
FOR EACH ROW 
BEGIN 
    UPDATE class_files 
    SET download_count = download_count + 1 
    WHERE id = NEW.file_id;
END$$

DELIMITER ;

-- Insertar algunos tipos de archivos de ejemplo para testing
INSERT IGNORE INTO class_files (filename, original_name, file_path, file_size, file_type, class_id, uploaded_by, upload_type, title, description) VALUES
('2023-10-05_algebra_basics.pdf', 'Álgebra Básica.pdf', 'materials/2023-10-05_algebra_basics.pdf', 1048576, 'pdf', 1, 1, 'theory', 'Álgebra Básica - Teoría', 'Conceptos fundamentales de álgebra para principiantes'),
('2023-10-05_exercises_week1.pdf', 'Ejercicios Semana 1.pdf', 'exercises/2023-10-05_exercises_week1.pdf', 524288, 'pdf', 1, 1, 'exercise', 'Ejercicios Semana 1', 'Problemas de práctica para la primera semana'),
('2023-10-05_geometry_formulas.pdf', 'Fórmulas Geometría.pdf', 'materials/2023-10-05_geometry_formulas.pdf', 786432, 'pdf', 2, 1, 'resource', 'Fórmulas de Geometría', 'Colección de fórmulas geométricas importantes');