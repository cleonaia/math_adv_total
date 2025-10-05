-- Math Advantage Database Schema - Fase 2: Gestió Digital i Automatització
-- Version: 2.0

CREATE DATABASE IF NOT EXISTS math_advantage_phase2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE math_advantage_phase2;

-- Students table (Enhanced for Phase 2)
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    cognoms VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telefon VARCHAR(15),
    data_naixement DATE,
    nivell_educatiu ENUM('ESO', 'Batxillerat', 'Universitari', 'Selectivitat') NOT NULL,
    curs VARCHAR(50),
    centre_educatiu VARCHAR(100),
    necessitats_especials TEXT,
    
    -- Parent information
    nom_pare VARCHAR(100),
    nom_mare VARCHAR(100),
    telefon_urgencies VARCHAR(15),
    email_pares VARCHAR(100),
    
    -- Digital features for Phase 2
    student_code VARCHAR(20) UNIQUE,
    portal_password VARCHAR(255),
    last_portal_access TIMESTAMP NULL,
    
    -- Status and metadata
    estat ENUM('pendent', 'actiu', 'inactiu', 'graduat') DEFAULT 'pendent',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_nivell (nivell_educatiu),
    INDEX idx_estat (estat),
    INDEX idx_student_code (student_code)
);

-- Teachers table (Enhanced for Phase 2)
CREATE TABLE teachers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    cognoms VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telefon VARCHAR(15),
    especialitat VARCHAR(100) NOT NULL,
    experiencia_anys INT NOT NULL,
    titulacions TEXT,
    horari_disponible JSON,
    
    -- Employment details
    estat ENUM('actiu', 'inactiu', 'vacances') DEFAULT 'actiu',
    data_incorporacio DATE,
    salari_hora DECIMAL(6,2),
    
    -- Digital features for Phase 2
    teacher_code VARCHAR(20) UNIQUE,
    portal_password VARCHAR(255),
    last_portal_access TIMESTAMP NULL,
    
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_especialitat (especialitat),
    INDEX idx_estat (estat),
    INDEX idx_teacher_code (teacher_code)
);

-- Classes table
CREATE TABLE classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    descripcio TEXT,
    nivell_educatiu ENUM('ESO', 'Batxillerat', 'Universitari', 'Selectivitat') NOT NULL,
    assignatura VARCHAR(100) NOT NULL,
    
    -- Schedule
    dia_setmana ENUM('Dilluns', 'Dimarts', 'Dimecres', 'Dijous', 'Divendres', 'Dissabte', 'Diumenge') NOT NULL,
    hora_inici TIME NOT NULL,
    hora_fi TIME NOT NULL,
    
    -- Capacity and pricing
    capacitat_maxima INT DEFAULT 8,
    preu_mes DECIMAL(8,2) NOT NULL,
    
    estat ENUM('actiu', 'inactiu', 'complet') DEFAULT 'actiu',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_nivell (nivell_educatiu),
    INDEX idx_assignatura (assignatura),
    INDEX idx_estat (estat),
    INDEX idx_horari (dia_setmana, hora_inici)
);

-- Enrollments (Student-Class relationship)
CREATE TABLE enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    data_inscripcio DATE NOT NULL,
    data_baixa DATE NULL,
    estat ENUM('actiu', 'inactiu', 'completat') DEFAULT 'actiu',
    notes TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_enrollment (student_id, class_id),
    INDEX idx_student (student_id),
    INDEX idx_class (class_id),
    INDEX idx_estat (estat)
);

-- System users for admin panel
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    
    rol ENUM('admin', 'professor', 'secretaria', 'coordinador') NOT NULL,
    nom VARCHAR(100) NOT NULL,
    cognoms VARCHAR(100) NOT NULL,
    telefon VARCHAR(15),
    
    -- Access control
    estat ENUM('actiu', 'inactiu', 'suspès') DEFAULT 'actiu',
    permissions JSON,
    last_login TIMESTAMP NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_rol (rol)
);

-- Notifications log (Automation system)
CREATE TABLE notifications_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('welcome_email', 'enrollment_confirmation', 'class_reminder', 'payment_reminder', 'general', 'whatsapp', 'sms') NOT NULL,
    recipient_email VARCHAR(100),
    recipient_phone VARCHAR(15),
    subject VARCHAR(200),
    message TEXT,
    
    status ENUM('pending', 'sent', 'failed', 'delivered', 'read') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    delivery_attempts INT DEFAULT 0,
    
    -- Related data
    student_id INT NULL,
    teacher_id INT NULL,
    class_id INT NULL,
    data JSON,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
    
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_recipient_email (recipient_email),
    INDEX idx_sent_at (sent_at)
);

-- Contact form submissions (Enhanced for automation)
CREATE TABLE contact_submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefon VARCHAR(15),
    nivell_interes ENUM('ESO', 'Batxillerat', 'Universitari', 'Selectivitat'),
    missatge TEXT NOT NULL,
    
    tipus_consulta ENUM('informacio', 'inscripcio', 'suport', 'altres') DEFAULT 'informacio',
    estat ENUM('pendent', 'en_proces', 'respost', 'tancat') DEFAULT 'pendent',
    prioritat ENUM('baixa', 'mitjana', 'alta', 'urgent') DEFAULT 'mitjana',
    
    -- Response tracking
    resposta TEXT,
    data_resposta TIMESTAMP NULL,
    responded_by INT NULL,
    
    -- Automation features
    auto_response_sent BOOLEAN DEFAULT FALSE,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (responded_by) REFERENCES users(id) ON SET NULL,
    
    INDEX idx_email (email),
    INDEX idx_estat (estat),
    INDEX idx_tipus (tipus_consulta)
);

-- Automation tasks
CREATE TABLE automation_tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_type ENUM('payment_reminder', 'class_reminder', 'enrollment_followup', 'welcome_sequence', 'birthday_greeting') NOT NULL,
    
    -- Scheduling
    scheduled_for TIMESTAMP NOT NULL,
    executed_at TIMESTAMP NULL,
    next_execution TIMESTAMP NULL,
    is_recurring BOOLEAN DEFAULT FALSE,
    recurrence_pattern VARCHAR(50),
    
    -- Task data
    target_type ENUM('student', 'teacher', 'class', 'all', 'custom') NOT NULL,
    target_ids JSON,
    task_config JSON,
    
    status ENUM('pending', 'running', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    result_data JSON,
    error_message TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_task_type (task_type),
    INDEX idx_scheduled_for (scheduled_for),
    INDEX idx_status (status)
);

-- WhatsApp messages (Integration)
CREATE TABLE whatsapp_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    phone_number VARCHAR(15) NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('text', 'template', 'media') DEFAULT 'text',
    
    -- WhatsApp API data
    whatsapp_message_id VARCHAR(100),
    template_name VARCHAR(100),
    
    status ENUM('pending', 'sent', 'delivered', 'read', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    
    -- Related entities
    student_id INT NULL,
    teacher_id INT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL,
    
    INDEX idx_phone (phone_number),
    INDEX idx_status (status)
);

-- Configuration settings
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json', 'email') DEFAULT 'text',
    category VARCHAR(50) DEFAULT 'general',
    description TEXT,
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL,
    
    FOREIGN KEY (updated_by) REFERENCES users(id) ON SET NULL,
    
    INDEX idx_key (setting_key),
    INDEX idx_category (category)
);

-- Activity log (Digital management tracking)
CREATE TABLE activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    
    description TEXT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id)
);

-- Insert initial configuration
INSERT INTO settings (setting_key, setting_value, setting_type, category, description) VALUES
-- General
('academy_name', 'Math Advantage', 'text', 'general', 'Nom de l\'acadèmia'),
('contact_email', 'info@math-advantage.com', 'email', 'contact', 'Email principal'),
('contact_phone', '931 16 34 57', 'text', 'contact', 'Telèfon principal'),
('whatsapp_number', '658174783', 'text', 'contact', 'WhatsApp'),
('address', 'Pare Sallarès, 67, Sabadell', 'text', 'general', 'Adreça'),

-- Automation
('auto_welcome_emails', 'true', 'boolean', 'automation', 'Emails de benvinguda automàtics'),
('auto_enrollment_confirmation', 'true', 'boolean', 'automation', 'Confirmació d\'inscripció automàtica'),
('auto_response_contact_form', 'true', 'boolean', 'automation', 'Resposta automàtica formulari contacte'),

-- Email configuration
('smtp_host', 'smtp.gmail.com', 'text', 'email', 'Servidor SMTP'),
('smtp_port', '587', 'number', 'email', 'Port SMTP'),
('smtp_username', 'info@math-advantage.com', 'email', 'email', 'Usuari SMTP'),
('email_from_name', 'Math Advantage', 'text', 'email', 'Nom remitent');

-- Create admin user
INSERT INTO users (username, email, password_hash, rol, nom, cognoms, permissions) VALUES
('admin', 'admin@math-advantage.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator', 'System', '{"all": true}'),
('lucia', 'lucia@math-advantage.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Lucia', 'Emilova', '{"students": true, "teachers": true, "automation": true}');

-- Sample data
INSERT INTO students (nom, cognoms, email, telefon, data_naixement, nivell_educatiu, curs, centre_educatiu, student_code, estat) VALUES
('Maria', 'García López', 'maria.garcia@email.com', '666111222', '2008-05-15', 'ESO', '4t ESO', 'IES Sabadell', 'MA2024001', 'actiu'),
('Joan', 'Martínez Valls', 'joan.martinez@email.com', '666333444', '2006-09-22', 'Batxillerat', '2n Batxillerat', 'IES Centre', 'MA2024002', 'actiu');

INSERT INTO teachers (nom, cognoms, email, telefon, especialitat, experiencia_anys, titulacions, teacher_code, estat) VALUES
('Lucia', 'Emilova', 'lucia@math-advantage.com', '658174783', 'Matemàtiques i Física', 8, 'Llicenciatura en Matemàtiques', 'TEA001', 'actiu'),
('Irene', 'Valderrama', 'irene@math-advantage.com', '666777888', 'Matemàtiques, Física i Química', 5, 'Grau en Física', 'TEA002', 'actiu');

INSERT INTO classes (nom, descripcio, nivell_educatiu, assignatura, dia_setmana, hora_inici, hora_fi, preu_mes) VALUES
('Matemàtiques 4t ESO', 'Matemàtiques per a 4t ESO', 'ESO', 'Matemàtiques', 'Dimarts', '17:00:00', '18:00:00', 80.00),
('Física 2n Batxillerat', 'Física per a 2n Batxillerat', 'Batxillerat', 'Física', 'Dijous', '18:00:00', '19:00:00', 90.00);

-- Login attempts tracking (Fase 3: Portal Security)
CREATE TABLE login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    failed_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_locked_until (locked_until)
);