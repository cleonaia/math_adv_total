-- Math Advantage - Complete Database Schema for Digital Platform
-- Phases 2-5: Digital Management, Private Portal, Advanced Features, Analytics

-- Drop existing tables if they exist
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS payment_transactions;
DROP TABLE IF EXISTS student_evaluations;
DROP TABLE IF EXISTS homework_submissions;
DROP TABLE IF EXISTS class_materials;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS erasmus_projects;
DROP TABLE IF EXISTS analytics_data;
DROP TABLE IF EXISTS system_settings;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS families;
DROP TABLE IF EXISTS teachers;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS classes;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS payments;

-- Users table (unified authentication)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('student', 'family', 'teacher', 'admin') NOT NULL,
    phone VARCHAR(20),
    avatar VARCHAR(255),
    active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (rol),
    INDEX idx_active (active)
);

-- Students table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    family_id INT,
    student_code VARCHAR(20) UNIQUE NOT NULL,
    nom VARCHAR(100) NOT NULL,
    cognoms VARCHAR(150) NOT NULL,
    data_naixement DATE,
    nivell_estudis VARCHAR(50) NOT NULL,
    curs_academic VARCHAR(20) NOT NULL,
    estat ENUM('actiu', 'inactiu', 'graduat') DEFAULT 'actiu',
    notes_especials TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_family (family_id),
    INDEX idx_nivell (nivell_estudis),
    INDEX idx_estat (estat)
);

-- Families table
CREATE TABLE families (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    nom_referent VARCHAR(100) NOT NULL,
    telefon_principal VARCHAR(20) NOT NULL,
    telefon_secundari VARCHAR(20),
    adreca TEXT,
    observacions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Teachers table
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    employee_code VARCHAR(20) UNIQUE NOT NULL,
    especialitat VARCHAR(100) NOT NULL,
    titulacio TEXT,
    experiencia_anys INT DEFAULT 0,
    horari_setmanal JSON,
    salari_hora DECIMAL(8,2),
    estat ENUM('actiu', 'inactiu', 'vacances') DEFAULT 'actiu',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_especialitat (especialitat),
    INDEX idx_estat (estat)
);

-- Courses table
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    descripcio TEXT,
    nivell VARCHAR(50) NOT NULL,
    materia VARCHAR(50) NOT NULL,
    modalitat ENUM('presencial', 'online', 'mixta') NOT NULL,
    preu_mensual DECIMAL(8,2) NOT NULL,
    hores_setmanals INT NOT NULL,
    max_alumnes INT DEFAULT 15,
    teacher_id INT,
    actiu BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL,
    INDEX idx_nivell (nivell),
    INDEX idx_materia (materia),
    INDEX idx_modalitat (modalitat)
);

-- Classes table (individual class sessions)
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    teacher_id INT NOT NULL,
    data_classe DATE NOT NULL,
    hora_inici TIME NOT NULL,
    hora_final TIME NOT NULL,
    aula VARCHAR(50),
    tema VARCHAR(200),
    estat ENUM('programada', 'en_curs', 'finalitzada', 'cancel·lada') DEFAULT 'programada',
    materials_url VARCHAR(255),
    notes_classe TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE RESTRICT,
    INDEX idx_data (data_classe),
    INDEX idx_estat (estat)
);

-- Enrollments table
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    data_inscripcio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estat ENUM('pendent', 'confirmat', 'actiu', 'pausat', 'finalitzat') DEFAULT 'pendent',
    data_inici DATE,
    data_final DATE,
    descompte_percentatge DECIMAL(5,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, course_id),
    INDEX idx_estat (estat),
    INDEX idx_data_inscripcio (data_inscripcio)
);

-- Payments table (Phase 4: Online Payments)
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    family_id INT NOT NULL,
    enrollment_id INT,
    tipus ENUM('matricula', 'mensualitat', 'material', 'examen', 'taller', 'altres') NOT NULL,
    import DECIMAL(10,2) NOT NULL,
    estat ENUM('pendent', 'processat', 'completat', 'fallat', 'reemborsat') DEFAULT 'pendent',
    metode_pagament ENUM('efectiu', 'transferencia', 'targeta', 'bizum', 'paypal') NOT NULL,
    referencia_externa VARCHAR(100),
    data_venciment DATE NOT NULL,
    data_pagament TIMESTAMP NULL,
    factura_numero VARCHAR(50),
    observacions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE SET NULL,
    INDEX idx_estat (estat),
    INDEX idx_data_venciment (data_venciment),
    INDEX idx_tipus (tipus)
);

-- Student Evaluations table (Phase 4: Digital Assessments)
CREATE TABLE student_evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    teacher_id INT NOT NULL,
    tipus ENUM('examen', 'tasca', 'projecte', 'participacio', 'continua') NOT NULL,
    nom_avaluacio VARCHAR(150) NOT NULL,
    nota DECIMAL(4,2),
    nota_maxima DECIMAL(4,2) DEFAULT 10.00,
    percentatge_curs DECIMAL(5,2) DEFAULT 0,
    comentaris TEXT,
    data_avaluacio DATE NOT NULL,
    arxiu_adjunt VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE RESTRICT,
    INDEX idx_data (data_avaluacio),
    INDEX idx_tipus (tipus)
);

-- Class Materials table (Phase 3: Digital Resources)
CREATE TABLE class_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    teacher_id INT NOT NULL,
    class_id INT,
    nom_material VARCHAR(200) NOT NULL,
    descripcio TEXT,
    tipus ENUM('pdf', 'video', 'presentacio', 'exercici', 'examen', 'altres') NOT NULL,
    url_arxiu VARCHAR(255) NOT NULL,
    mida_arxiu BIGINT,
    public BOOLEAN DEFAULT FALSE,
    data_publicacio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    descarregues INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE RESTRICT,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
    INDEX idx_tipus (tipus),
    INDEX idx_public (public)
);

-- Homework Submissions table
CREATE TABLE homework_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_material_id INT NOT NULL,
    arxiu_resposta VARCHAR(255),
    text_resposta TEXT,
    data_entrega TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_limit DATE NOT NULL,
    estat ENUM('pendent', 'entregat', 'revisat', 'aprovat', 'suspès') DEFAULT 'pendent',
    nota DECIMAL(4,2),
    comentaris_professor TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_material_id) REFERENCES class_materials(id) ON DELETE CASCADE,
    INDEX idx_data_limit (data_limit),
    INDEX idx_estat (estat)
);

-- Messages table (Phase 3: Communication)
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    remitent_id INT NOT NULL,
    destinatari_id INT NOT NULL,
    assumpte VARCHAR(200) NOT NULL,
    missatge TEXT NOT NULL,
    llegit BOOLEAN DEFAULT FALSE,
    data_lectura TIMESTAMP NULL,
    arxiu_adjunt VARCHAR(255),
    tipus ENUM('privat', 'grup', 'avisos', 'sistema') DEFAULT 'privat',
    prioritat ENUM('baixa', 'normal', 'alta', 'urgent') DEFAULT 'normal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (remitent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (destinatari_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_destinatari (destinatari_id),
    INDEX idx_llegit (llegit),
    INDEX idx_created (created_at)
);

-- Notifications table (Phase 2: Automated Communications)
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tipus ENUM('email', 'sms', 'push', 'interna') NOT NULL,
    titol VARCHAR(200) NOT NULL,
    contingut TEXT NOT NULL,
    estat ENUM('pendent', 'enviat', 'llegit', 'fallat') DEFAULT 'pendent',
    data_envio TIMESTAMP NULL,
    data_lectura TIMESTAMP NULL,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_estat (user_id, estat),
    INDEX idx_tipus (tipus),
    INDEX idx_created (created_at)
);

-- Erasmus Projects table (Phase 4: Erasmus+ Portal)
CREATE TABLE erasmus_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_projecte VARCHAR(200) NOT NULL,
    descripcio TEXT,
    pais_soci VARCHAR(100) NOT NULL,
    instituci_soci VARCHAR(200) NOT NULL,
    data_inici DATE NOT NULL,
    data_final DATE NOT NULL,
    pressupost DECIMAL(12,2),
    estat ENUM('planificacio', 'aprovat', 'en_curs', 'finalitzat', 'cancel·lat') DEFAULT 'planificacio',
    participants JSON,
    documents_url VARCHAR(255),
    contact_person VARCHAR(100),
    email_contacte VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_estat (estat),
    INDEX idx_dates (data_inici, data_final)
);

-- Analytics Data table (Phase 5: Analytics & Optimization)
CREATE TABLE analytics_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(15,4) NOT NULL,
    dimensions JSON,
    data_registre DATE NOT NULL,
    hora_registre TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    source VARCHAR(50) NOT NULL,
    INDEX idx_metric (metric_name),
    INDEX idx_data (data_registre),
    INDEX idx_source (source)
);

-- System Settings table
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    descripcio TEXT,
    tipus_valor ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    categoria VARCHAR(50) DEFAULT 'general',
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_categoria (categoria)
);

-- User Sessions table (Security & Analytics)
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    data_inici TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_final TIMESTAMP NULL,
    actiu BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (session_token),
    INDEX idx_user_actiu (user_id, actiu)
);

-- Payment Transactions table (Detailed payment tracking)
CREATE TABLE payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    transaction_id VARCHAR(100) UNIQUE NOT NULL,
    gateway_response JSON,
    import_processat DECIMAL(10,2) NOT NULL,
    comissio DECIMAL(8,2) DEFAULT 0,
    estat_transaccio ENUM('pendent', 'autoritzat', 'capturat', 'fallat', 'reemborsat') NOT NULL,
    data_transaccio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    INDEX idx_transaction (transaction_id),
    INDEX idx_estat (estat_transaccio)
);

-- Insert default admin user
INSERT INTO users (nom, email, password_hash, rol, phone, active) VALUES 
('Administrador', 'admin@math-advantage.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '931163457', TRUE);

-- Insert demo users for testing
INSERT INTO users (nom, email, password_hash, rol, phone, active) VALUES 
('Alumne Demo', 'alumne@math-advantage.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '666111222', TRUE),
('Família Demo', 'familia@math-advantage.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'family', '666333444', TRUE),
('Professor Demo', 'professor@math-advantage.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', '666555666', TRUE);

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value, descripcio, categoria) VALUES 
('site_name', 'Math Advantage', 'Nom del centre educatiu', 'general'),
('contact_email', 'info@math-advantage.com', 'Email de contacte principal', 'contacte'),
('contact_phone', '931163457', 'Telèfon principal', 'contacte'),
('address', 'Pare Sallarès, 67, Sabadell', 'Adreça física del centre', 'contacte'),
('erasmus_partner_since', '2016', 'Any d\'inici com a Erasmus+ Partner', 'erasmus'),
('max_file_upload', '10485760', 'Mida màxima de fitxers (bytes)', 'sistema'),
('session_timeout', '7200', 'Timeout de sessió (segons)', 'sistema'),
('enable_notifications', 'true', 'Activar sistema de notificacions', 'notificacions'),
('whatsapp_api_enabled', 'true', 'API de WhatsApp activa', 'comunicacio');

-- Insert sample courses
INSERT INTO courses (nom, descripcio, nivell, materia, modalitat, preu_mensual, hores_setmanals, teacher_id, actiu) VALUES 
('Matemàtiques 1r ESO', 'Matemàtiques per a primer d\'ESO amb enfocament personalitzat', '1r ESO', 'Matemàtiques', 'presencial', 65.00, 2, NULL, TRUE),
('Física 2n Batxillerat', 'Preparació per a selectivitat en Física', '2n Batxillerat', 'Física', 'mixta', 75.00, 3, NULL, TRUE),
('Càlcul Universitari', 'Càlcul diferencial i integral per a enginyeries', 'Universitari', 'Matemàtiques', 'online', 85.00, 2, NULL, TRUE);

-- Create indexes for performance
CREATE INDEX idx_students_family ON students(family_id);
CREATE INDEX idx_enrollments_student ON enrollments(student_id);
CREATE INDEX idx_payments_family ON payments(family_id);
CREATE INDEX idx_messages_created ON messages(created_at);
CREATE INDEX idx_notifications_user_date ON notifications(user_id, created_at);

-- Create views for common queries
CREATE VIEW student_progress_view AS
SELECT 
    s.id as student_id,
    s.nom,
    s.cognoms,
    c.nom as course_name,
    AVG(se.nota) as average_grade,
    COUNT(se.id) as total_evaluations,
    e.estat as enrollment_status
FROM students s
JOIN enrollments e ON s.id = e.student_id
JOIN courses c ON e.course_id = c.id
LEFT JOIN student_evaluations se ON s.id = se.student_id AND c.id = se.course_id
WHERE e.estat = 'actiu'
GROUP BY s.id, c.id;

CREATE VIEW payment_summary_view AS
SELECT 
    f.id as family_id,
    f.nom_referent,
    COUNT(p.id) as total_payments,
    SUM(CASE WHEN p.estat = 'completat' THEN p.import ELSE 0 END) as paid_amount,
    SUM(CASE WHEN p.estat = 'pendent' THEN p.import ELSE 0 END) as pending_amount
FROM families f
LEFT JOIN payments p ON f.id = p.family_id
GROUP BY f.id;

-- Triggers for automated notifications
DELIMITER //

CREATE TRIGGER after_payment_insert 
AFTER INSERT ON payments
FOR EACH ROW
BEGIN
    INSERT INTO notifications (user_id, tipus, titol, contingut, estat)
    SELECT u.id, 'email', 
           'Nou pagament registrat',
           CONCAT('S\'ha registrat un pagament de ', NEW.import, '€ per ', NEW.tipus),
           'pendent'
    FROM families f
    JOIN users u ON f.user_id = u.id
    WHERE f.id = NEW.family_id;
END//

CREATE TRIGGER after_evaluation_insert 
AFTER INSERT ON student_evaluations
FOR EACH ROW
BEGIN
    INSERT INTO notifications (user_id, tipus, titol, contingut, estat)
    SELECT u.id, 'email',
           'Nova avaluació disponible',
           CONCAT('Nova avaluació per ', NEW.nom_avaluacio, ' - Nota: ', NEW.nota),
           'pendent'
    FROM students s
    JOIN families f ON s.family_id = f.id
    JOIN users u ON f.user_id = u.id
    WHERE s.id = NEW.student_id;
END//

DELIMITER ;

-- Insert sample analytics data
INSERT INTO analytics_data (metric_name, metric_value, dimensions, data_registre, source) VALUES 
('website_visits', 1247, '{"source": "organic", "device": "desktop"}', CURDATE(), 'google_analytics'),
('new_enrollments', 89, '{"course_type": "eso", "month": "october"}', CURDATE(), 'internal'),
('student_satisfaction', 94, '{"survey_type": "monthly", "responses": 156}', CURDATE(), 'survey_system'),
('active_students', 156, '{"status": "active", "level": "all"}', CURDATE(), 'internal');

-- Performance optimization
ANALYZE TABLE users, students, courses, enrollments, payments;
OPTIMIZE TABLE messages, notifications, analytics_data;