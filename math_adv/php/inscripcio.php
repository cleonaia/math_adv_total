<?php
/**
 * Math Advantage - Inscription Form Handler - Fase 2
 * Digital Management and Automation Integration
 */

require_once 'config.php';
require_once 'classes/Database.php';
require_once 'classes/Student.php';
require_once 'classes/NotificationSystem.php';

// Set JSON response header
header('Content-Type: application/json; charset=UTF-8');

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Get form data and map to database fields
    $studentData = [
        'nom' => trim($_POST['nom'] ?? ''),
        'cognoms' => trim($_POST['cognoms'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'telefon' => trim($_POST['telefon'] ?? ''),
        'data_naixement' => $_POST['data_naixement'] ?? null,
        'nivell_educatiu' => $_POST['nivell'] ?? '',
        'curs' => $_POST['curs'] ?? '',
        'centre_educatiu' => trim($_POST['centre'] ?? ''),
        'necessitats_especials' => trim($_POST['necessitats'] ?? ''),
        
        // Parent information
        'nom_pare' => trim($_POST['nom_pare'] ?? ''),
        'nom_mare' => trim($_POST['nom_mare'] ?? ''),
        'telefon_urgencies' => trim($_POST['telefon_urgencies'] ?? $_POST['telefon'] ?? ''),
        'email_pares' => trim($_POST['email_pares'] ?? $_POST['email'] ?? ''),
        
        'notes' => trim($_POST['notes'] ?? '')
    ];
    
    // Additional data for inscription
    $inscriptionData = [
        'preferred_schedule' => $_POST['horari_preferit'] ?? '',
        'subjects_interest' => $_POST['assignatures'] ?? '',
        'student_data' => $studentData
    ];
    
    // Validation
    $errors = [];
    
    // Required fields validation
    $requiredFields = ['nom', 'email', 'telefon', 'nivell_educatiu'];
    foreach ($requiredFields as $field) {
        if (empty($studentData[$field])) {
            $errors[] = "El camp '$field' és obligatori";
        }
    }
    
    // Email validation
    if (!empty($studentData['email']) && !filter_var($studentData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email no té un format vàlid";
    }
    
    // Phone validation
    if (!empty($studentData['telefon']) && !preg_match('/^[+]?[\d\s\-\(\)]{9,}$/', $studentData['telefon'])) {
        $errors[] = "El telèfon no té un format vàlid";
    }
    
    // Check if email already exists
    $db = Database::getInstance();
    $existingStudent = $db->query(
        "SELECT id FROM students WHERE email = ?", 
        [$studentData['email']]
    )->fetch(PDO::FETCH_ASSOC);
    
    if ($existingStudent) {
        $errors[] = "Ja existeix un estudiant amb aquest email";
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'errors' => $errors,
            'message' => 'Hi ha errors en el formulari'
        ]);
        exit();
    }
    
    // Create student using the Student class
    $student = new Student();
    $result = $student->enrollStudent($inscriptionData);
    
    if ($result['success']) {
        // Send welcome and enrollment confirmation emails
        try {
            $notificationSystem = new NotificationSystem();
            
            // Add the new student ID to the data
            $studentData['id'] = $result['student_id'];
            
            // Send welcome email
            $notificationSystem->sendWelcomeEmail($studentData);
            
            // Send enrollment confirmation
            $notificationSystem->sendEnrollmentConfirmation($studentData);
            
        } catch (Exception $e) {
            error_log("Email notification failed: " . $e->getMessage());
        }
        
        // Generate WhatsApp link for quick contact
        $whatsappPhone = '34658174783'; // Math Advantage WhatsApp
        $whatsappMessage = urlencode("Hola! Acabo de completar la inscripció per a {$studentData['nom']} ({$studentData['nivell_educatiu']}). M'agradaria coordinar els horaris de classe. Gràcies!");
        $whatsappLink = "https://wa.me/{$whatsappPhone}?text={$whatsappMessage}";
        
        // Success response
        echo json_encode([
            'success' => true,
            'message' => 'Inscripció completada correctament! Rebràs emails de confirmació.',
            'student_id' => $result['student_id'],
            'student_code' => $result['student_code'] ?? null,
            'whatsapp_link' => $whatsappLink,
            'data' => [
                'nom' => $studentData['nom'],
                'email' => $studentData['email'],
                'nivell' => $studentData['nivell_educatiu']
            ]
        ]);
        
    } else {
        throw new Exception($result['message'] ?? 'Error en crear l\'estudiant');
    }
    
} catch (Exception $e) {
    error_log("Inscription error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error intern del servidor. Prova de nou més tard.',
        'error' => DEVELOPMENT_MODE ? $e->getMessage() : 'Internal error'
    ]);
}
?>