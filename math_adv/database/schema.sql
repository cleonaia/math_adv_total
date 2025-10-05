-- Math Advantage Database Schema
-- This script creates the complete database structure for the Math Advantage platform

-- Create database
CREATE DATABASE IF NOT EXISTS math_advantage 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE math_advantage;

-- =============================================
-- CORE TABLES
-- =============================================

-- Inscriptions table
CREATE TABLE inscripcions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefon VARCHAR(20) NOT NULL,
    nivell ENUM('1eso', '2eso', '3eso', '4eso', '1bat', '2bat', 'universitari') NOT NULL,
    modalitat ENUM('presencial', 'online', 'mixta') NOT NULL,
    horari ENUM('mati', 'tarda', 'vespre') NULL,
    comentaris TEXT NULL,
    newsletter BOOLEAN DEFAULT FALSE,
    politica BOOLEAN NOT NULL DEFAULT TRUE,
    estat ENUM('pendent', 'contactat', 'matriculat', 'rebutjat', 'en_espera') DEFAULT 'pendent',
    data_inscripcio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_contacte DATETIME NULL,
    data_matricula DATETIME NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    notes_admin TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_estat (estat),
    INDEX idx_data_inscripcio (data_inscripcio),
    INDEX idx_nivell (nivell)
);

-- Students table (for enrolled students)
CREATE TABLE estudiants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    inscripcio_id INT NULL,
    nom VARCHAR(100) NOT NULL,
    cognoms VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telefon VARCHAR(20) NOT NULL,
    data_naixement DATE NULL,
    dni VARCHAR(20) NULL,
    adresa TEXT NULL,
    nivell_estudis VARCHAR(50) NOT NULL,
    centre_estudis VARCHAR(150) NULL,
    tutor_legal_nom VARCHAR(100) NULL,
    tutor_legal_telefon VARCHAR(20) NULL,
    tutor_legal_email VARCHAR(150) NULL,
    estat ENUM('actiu', 'inactiu', 'suspès', 'graduat') DEFAULT 'actiu',
    data_matricula DATE NOT NULL,
    data_baixa DATE NULL,
    observacions TEXT NULL,
    password_hash VARCHAR(255) NULL,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (inscripcio_id) REFERENCES inscripcions(id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_estat (estat),
    INDEX idx_nivell_estudis (nivell_estudis)
);

-- Professors table
CREATE TABLE professors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    cognoms VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telefon VARCHAR(20) NOT NULL,
    dni VARCHAR(20) UNIQUE NULL,
    titulacio VARCHAR(200) NOT NULL,
    especialitats JSON NULL, -- ["algebra", "calcul", "estadistica"]
    experiencia_anys INT DEFAULT 0,
    biografia TEXT NULL,
    foto_url VARCHAR(255) NULL,
    linkedin_url VARCHAR(255) NULL,
    estat ENUM('actiu', 'inactiu', 'baixa_temporal') DEFAULT 'actiu',
    data_contractacio DATE NOT NULL,
    data_baixa DATE NULL,
    salari_hora DECIMAL(6,2) NULL,
    password_hash VARCHAR(255) NULL,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_estat (estat),
    INDEX idx_especialitats ((CAST(especialitats AS CHAR(255) ARRAY)))
);

-- Courses table
CREATE TABLE cursos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(150) NOT NULL,
    descripcio TEXT NULL,
    nivell ENUM('1eso', '2eso', '3eso', '4eso', '1bat', '2bat', 'universitari') NOT NULL,
    modalitat ENUM('presencial', 'online', 'mixta') NOT NULL,
    durada_setmanes INT NOT NULL DEFAULT 36,
    hores_setmanals INT NOT NULL DEFAULT 2,
    preu_mes DECIMAL(6,2) NOT NULL,
    preu_matricula DECIMAL(6,2) DEFAULT 0.00,
    maxim_estudiants INT DEFAULT 8,
    material_inclòs BOOLEAN DEFAULT FALSE,
    actiu BOOLEAN DEFAULT TRUE,
    data_inici DATE NULL,
    data_final DATE NULL,
    horari JSON NULL, -- {"dilluns": "16:00-18:00", "dimecres": "16:00-18:00"}
    objectius TEXT NULL,
    prerequisits TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_nivell (nivell),
    INDEX idx_modalitat (modalitat),
    INDEX idx_actiu (actiu)
);

-- Groups table (specific instances of courses)
CREATE TABLE grups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    curs_id INT NOT NULL,
    professor_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL, -- "ESO 3 - Grup A"
    codi VARCHAR(20) NOT NULL UNIQUE, -- "3ESO-A-2024"
    modalitat ENUM('presencial', 'online', 'mixta') NOT NULL,
    horari JSON NOT NULL, -- {"dilluns": "16:00-18:00", "dimecres": "16:00-18:00"}
    aula VARCHAR(50) NULL,
    url_online VARCHAR(255) NULL,
    maxim_estudiants INT DEFAULT 8,
    estudiants_matriculats INT DEFAULT 0,
    estat ENUM('planificat', 'actiu', 'finalitzat', 'cancel·lat') DEFAULT 'planificat',
    data_inici DATE NOT NULL,
    data_final DATE NOT NULL,
    preu_mes DECIMAL(6,2) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (curs_id) REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES professors(id) ON DELETE RESTRICT,
    INDEX idx_professor (professor_id),
    INDEX idx_estat (estat),
    INDEX idx_modalitat (modalitat)
);

-- Student enrollments in groups
CREATE TABLE matricules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    estudiant_id INT NOT NULL,
    grup_id INT NOT NULL,
    data_matricula DATE NOT NULL,
    data_baixa DATE NULL,
    estat ENUM('activa', 'suspesa', 'finalitzada', 'baixa') DEFAULT 'activa',
    preu_acordat DECIMAL(6,2) NOT NULL,
    descompte_percentatge DECIMAL(5,2) DEFAULT 0.00,
    motiu_baixa TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (estudiant_id) REFERENCES estudiants(id) ON DELETE CASCADE,
    FOREIGN KEY (grup_id) REFERENCES grups(id) ON DELETE CASCADE,
    UNIQUE KEY unique_matricula (estudiant_id, grup_id),
    INDEX idx_estat (estat),
    INDEX idx_data_matricula (data_matricula)
);

-- =============================================
-- PAYMENT AND BILLING TABLES
-- =============================================

-- Payments table
CREATE TABLE pagaments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    estudiant_id INT NOT NULL,
    matricula_id INT NOT NULL,
    tipus ENUM('matricula', 'mensualitat', 'material', 'extra') NOT NULL,
    import DECIMAL(8,2) NOT NULL,
    estat ENUM('pendent', 'pagat', 'vençut', 'cancel·lat') DEFAULT 'pendent',
    metode_pagament ENUM('efectiu', 'transferencia', 'targeta', 'domiciliacio', 'online') NULL,
    data_venciment DATE NOT NULL,
    data_pagament DATETIME NULL,
    mes_any VARCHAR(7) NULL, -- "2024-10" for mensualitats
    referencia VARCHAR(50) NULL,
    observacions TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (estudiant_id) REFERENCES estudiants(id) ON DELETE CASCADE,
    FOREIGN KEY (matricula_id) REFERENCES matricules(id) ON DELETE CASCADE,
    INDEX idx_estat (estat),
    INDEX idx_data_venciment (data_venciment),
    INDEX idx_mes_any (mes_any)
);

-- =============================================
-- SCHEDULING AND ATTENDANCE TABLES
-- =============================================

-- Classes table (individual class sessions)
CREATE TABLE classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grup_id INT NOT NULL,
    professor_id INT NOT NULL,
    data_hora DATETIME NOT NULL,
    durada_minuts INT NOT NULL DEFAULT 120,
    aula VARCHAR(50) NULL,
    url_online VARCHAR(255) NULL,
    tema VARCHAR(200) NULL,
    contingut TEXT NULL,
    material_necessari TEXT NULL,
    deures TEXT NULL,
    observacions TEXT NULL,
    estat ENUM('planificada', 'en_curs', 'finalitzada', 'cancel·lada', 'ajornada') DEFAULT 'planificada',
    motiu_cancellacio TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (grup_id) REFERENCES grups(id) ON DELETE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES professors(id) ON DELETE RESTRICT,
    INDEX idx_data_hora (data_hora),
    INDEX idx_estat (estat)
);

-- Attendance table
CREATE TABLE assistencia (
    id INT PRIMARY KEY AUTO_INCREMENT,
    classe_id INT NOT NULL,
    estudiant_id INT NOT NULL,
    estat ENUM('present', 'absent', 'retard', 'justificat') NOT NULL,
    hora_arribada TIME NULL,
    motiu_absencia TEXT NULL,
    observacions TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (classe_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (estudiant_id) REFERENCES estudiants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assistencia (classe_id, estudiant_id),
    INDEX idx_estat (estat)
);

-- =============================================
-- EVALUATION AND PROGRESS TABLES
-- =============================================

-- Evaluations table
CREATE TABLE avaluacions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    estudiant_id INT NOT NULL,
    grup_id INT NOT NULL,
    professor_id INT NOT NULL,
    tipus ENUM('examen', 'practica', 'projecte', 'participacio', 'deures') NOT NULL,
    titol VARCHAR(150) NOT NULL,
    descripcio TEXT NULL,
    nota DECIMAL(4,2) NULL, -- 0.00 to 10.00
    nota_maxima DECIMAL(4,2) DEFAULT 10.00,
    percentatge_nota_final DECIMAL(5,2) DEFAULT 0.00,
    data_avaluacio DATE NOT NULL,
    observacions TEXT NULL,
    fitxers JSON NULL, -- URLs of uploaded files
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (estudiant_id) REFERENCES estudiants(id) ON DELETE CASCADE,
    FOREIGN KEY (grup_id) REFERENCES grups(id) ON DELETE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES professors(id) ON DELETE CASCADE,
    INDEX idx_data_avaluacio (data_avaluacio),
    INDEX idx_tipus (tipus)
);

-- =============================================
-- COMMUNICATION TABLES
-- =============================================

-- Messages table (internal messaging system)
CREATE TABLE missatges (
    id INT PRIMARY KEY AUTO_INCREMENT,
    remitent_id INT NOT NULL,
    remitent_tipus ENUM('estudiant', 'professor', 'admin') NOT NULL,
    destinatari_id INT NOT NULL,
    destinatari_tipus ENUM('estudiant', 'professor', 'admin') NOT NULL,
    assumpte VARCHAR(200) NOT NULL,
    missatge TEXT NOT NULL,
    llegit BOOLEAN DEFAULT FALSE,
    data_lectura DATETIME NULL,
    prioritat ENUM('baixa', 'normal', 'alta', 'urgent') DEFAULT 'normal',
    fitxers JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_destinatari (destinatari_id, destinatari_tipus),
    INDEX idx_llegit (llegit),
    INDEX idx_prioritat (prioritat)
);

-- Notifications table
CREATE TABLE notificacions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuari_id INT NOT NULL,
    usuari_tipus ENUM('estudiant', 'professor', 'admin') NOT NULL,
    tipus ENUM('info', 'advertencia', 'error', 'exit') NOT NULL,
    titol VARCHAR(150) NOT NULL,
    missatge TEXT NOT NULL,
    llegida BOOLEAN DEFAULT FALSE,
    data_lectura DATETIME NULL,
    url_accio VARCHAR(255) NULL,
    caducitat DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_usuari (usuari_id, usuari_tipus),
    INDEX idx_llegida (llegida),
    INDEX idx_caducitat (caducitat)
);

-- =============================================
-- SYSTEM TABLES
-- =============================================

-- Admin users table
CREATE TABLE administradors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('superadmin', 'admin', 'secretaria', 'coordinador') NOT NULL,
    permisos JSON NULL,
    actiu BOOLEAN DEFAULT TRUE,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_rol (rol)
);

-- Settings table
CREATE TABLE configuracio (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clau VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT NOT NULL,
    descripcio TEXT NULL,
    tipus ENUM('text', 'number', 'boolean', 'json', 'email', 'url') DEFAULT 'text',
    categoria VARCHAR(50) DEFAULT 'general',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_categoria (categoria)
);

-- Activity log table
CREATE TABLE log_activitat (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuari_id INT NULL,
    usuari_tipus ENUM('estudiant', 'professor', 'admin', 'sistema') NOT NULL,
    accio VARCHAR(100) NOT NULL,
    taula_afectada VARCHAR(50) NULL,
    registre_id INT NULL,
    detalls JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_usuari (usuari_id, usuari_tipus),
    INDEX idx_accio (accio),
    INDEX idx_created_at (created_at)
);

-- =============================================
-- INITIAL DATA
-- =============================================

-- Insert default configuration
INSERT INTO configuracio (clau, valor, descripcio, tipus, categoria) VALUES
('site_name', 'Math Advantage', 'Nom del centre d\'estudis', 'text', 'general'),
('site_email', 'info@math-advantage.com', 'Email principal del centre', 'email', 'general'),
('site_phone', '933 123 456', 'Telèfon principal', 'text', 'general'),
('site_address', 'Carrer de les Matemàtiques, 123, 08001 Barcelona', 'Adreça del centre', 'text', 'general'),
('whatsapp_phone', '34644789012', 'Número de WhatsApp', 'text', 'comunicacio'),
('email_notifications', 'true', 'Activar notificacions per email', 'boolean', 'comunicacio'),
('max_students_per_group', '8', 'Màxim d\'estudiants per grup', 'number', 'academic'),
('default_class_duration', '120', 'Durada per defecte de les classes (minuts)', 'number', 'academic'),
('academic_year_start', '2024-09-15', 'Inici del curs acadèmic', 'text', 'academic'),
('academic_year_end', '2025-06-30', 'Final del curs acadèmic', 'text', 'academic');

-- Insert sample courses
INSERT INTO cursos (nom, descripcio, nivell, modalitat, preu_mes, maxim_estudiants) VALUES
('Matemàtiques 1r ESO', 'Curs de matemàtiques per a primer d\'ESO amb continguts adaptats al currículum oficial', '1eso', 'presencial', 60.00, 8),
('Matemàtiques 2n ESO', 'Curs de matemàtiques per a segon d\'ESO amb continguts adaptats al currículum oficial', '2eso', 'presencial', 60.00, 8),
('Matemàtiques 3r ESO', 'Curs de matemàtiques per a tercer d\'ESO amb continguts adaptats al currículum oficial', '3eso', 'presencial', 65.00, 8),
('Matemàtiques 4t ESO', 'Curs de matemàtiques per a quart d\'ESO amb continguts adaptats al currículum oficial', '4eso', 'presencial', 65.00, 8),
('Matemàtiques I - 1r Batxillerat', 'Matemàtiques I per a primer de batxillerat científic-tecnològic', '1bat', 'presencial', 70.00, 6),
('Matemàtiques II - 2n Batxillerat', 'Matemàtiques II per a segon de batxillerat amb preparació per selectivitat', '2bat', 'presencial', 75.00, 6),
('Càlcul Universitari', 'Càlcul diferencial i integral per a estudiants universitaris', 'universitari', 'mixta', 80.00, 6),
('Àlgebra Lineal', 'Àlgebra lineal per a estudiants d\'enginyeria i ciències', 'universitari', 'mixta', 80.00, 6);

-- Insert default admin user (password: admin123 - CHANGE IN PRODUCTION!)
INSERT INTO administradors (nom, email, password_hash, rol) VALUES
('Administrador', 'admin@math-advantage.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin');

-- =============================================
-- VIEWS FOR REPORTING
-- =============================================

-- View for student overview
CREATE VIEW vista_estudiants AS
SELECT 
    e.id,
    e.nom,
    e.cognoms,
    e.email,
    e.telefon,
    e.nivell_estudis,
    e.estat,
    e.data_matricula,
    COUNT(m.id) as total_matricules,
    COUNT(CASE WHEN m.estat = 'activa' THEN 1 END) as matricules_actives
FROM estudiants e
LEFT JOIN matricules m ON e.id = m.estudiant_id
GROUP BY e.id;

-- View for payment overview
CREATE VIEW vista_pagaments_resum AS
SELECT 
    e.nom,
    e.cognoms,
    COUNT(p.id) as total_pagaments,
    SUM(CASE WHEN p.estat = 'pagat' THEN p.import ELSE 0 END) as total_pagat,
    SUM(CASE WHEN p.estat = 'pendent' THEN p.import ELSE 0 END) as total_pendent,
    SUM(CASE WHEN p.estat = 'vençut' THEN p.import ELSE 0 END) as total_vençut
FROM estudiants e
LEFT JOIN pagaments p ON e.id = p.estudiant_id
GROUP BY e.id;

-- View for group statistics
CREATE VIEW vista_grups_estadistiques AS
SELECT 
    g.id,
    g.nom,
    c.nom as curs_nom,
    CONCAT(p.nom, ' ', p.cognoms) as professor,
    g.maxim_estudiants,
    COUNT(m.id) as estudiants_matriculats,
    g.estat,
    g.data_inici,
    g.data_final
FROM grups g
LEFT JOIN cursos c ON g.curs_id = c.id
LEFT JOIN professors p ON g.professor_id = p.id
LEFT JOIN matricules m ON g.id = m.grup_id AND m.estat = 'activa'
GROUP BY g.id;

-- =============================================
-- INDEXES FOR PERFORMANCE
-- =============================================

-- Additional indexes for better performance
CREATE INDEX idx_inscripcions_email_estat ON inscripcions(email, estat);
CREATE INDEX idx_estudiants_nivell_estat ON estudiants(nivell_estudis, estat);
CREATE INDEX idx_classes_grup_data ON classes(grup_id, data_hora);
CREATE INDEX idx_pagaments_estudiant_estat ON pagaments(estudiant_id, estat);
CREATE INDEX idx_avaluacions_estudiant_data ON avaluacions(estudiant_id, data_avaluacio);

-- =============================================
-- TRIGGERS FOR AUTOMATION
-- =============================================

-- Trigger to update student count in groups when enrollment changes
DELIMITER $$
CREATE TRIGGER update_group_student_count 
AFTER INSERT ON matricules
FOR EACH ROW
BEGIN
    UPDATE grups 
    SET estudiants_matriculats = (
        SELECT COUNT(*) 
        FROM matricules 
        WHERE grup_id = NEW.grup_id AND estat = 'activa'
    )
    WHERE id = NEW.grup_id;
END$$

CREATE TRIGGER update_group_student_count_delete
AFTER UPDATE ON matricules
FOR EACH ROW
BEGIN
    IF OLD.estat != NEW.estat THEN
        UPDATE grups 
        SET estudiants_matriculats = (
            SELECT COUNT(*) 
            FROM matricules 
            WHERE grup_id = NEW.grup_id AND estat = 'activa'
        )
        WHERE id = NEW.grup_id;
    END IF;
END$$
DELIMITER ;

-- Trigger to log activity
DELIMITER $$
CREATE TRIGGER log_inscription_insert
AFTER INSERT ON inscripcions
FOR EACH ROW
BEGIN
    INSERT INTO log_activitat (usuari_tipus, accio, taula_afectada, registre_id, detalls)
    VALUES ('sistema', 'INSERT', 'inscripcions', NEW.id, JSON_OBJECT('nom', NEW.nom, 'email', NEW.email));
END$$
DELIMITER ;

COMMIT;