<?php
/**
 * Math Advantage - Contact Form Handler
 * Fase 2: Digital Management Integration
 */

require_once 'config.php';
require_once 'classes/Database.php';
require_once 'classes/NotificationSystem.php';

// Set JSON response header
header('Content-Type: application/json; charset=UTF-8');

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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
    // Get form data
    $data = [
        'nom' => $_POST['nom'] ?? '',
        'email' => $_POST['email'] ?? '',
        'telefon' => $_POST['telefon'] ?? '',
        'nivell_interes' => $_POST['nivell_interes'] ?? null,
        'missatge' => $_POST['missatge'] ?? '',
        'tipus_consulta' => 'informacio'
    ];
    
    // Validate required fields
    $required = ['nom', 'email', 'missatge'];
    $errors = [];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $errors[] = "El camp '$field' és obligatori";
        }
    }
    
    // Validate email
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email no té un format vàlid";
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
    
    // Save to database
    $db = Database::getInstance();
    $contactId = $db->insert('contact_submissions', $data);
    
    if ($contactId) {
        // Send auto-response if enabled
        $autoResponse = $db->query(
            "SELECT setting_value FROM settings WHERE setting_key = 'auto_response_contact_form'"
        )->fetchColumn();
        
        if ($autoResponse === 'true' || $autoResponse === '1') {
            try {
                $notificationSystem = new NotificationSystem();
                $notificationSystem->sendContactAutoResponse($data['email'], $data['nom']);
            } catch (Exception $e) {
                error_log("Auto-response email failed: " . $e->getMessage());
            }
        }
        
        // Success response
        echo json_encode([
            'success' => true,
            'message' => 'Missatge enviat correctament. Rebràs una resposta aviat.',
            'id' => $contactId
        ]);
        
    } else {
        throw new Exception('Error saving contact form');
    }
    
} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error intern del servidor. Prova de nou més tard.',
        'error' => DEVELOPMENT_MODE ? $e->getMessage() : 'Internal error'
    ]);
}