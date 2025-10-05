<?php
/**
 * Math Advantage - Login Handler
 * Fase 3: Portal de Familias - Proceso de autenticación
 */

require_once 'auth.php';

// Headers de seguridad
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Verificar rate limiting básico
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimitFile = sys_get_temp_dir() . '/login_attempts_' . md5($clientIP);

if (file_exists($rateLimitFile)) {
    $attempts = json_decode(file_get_contents($rateLimitFile), true);
    $currentTime = time();
    
    // Limpiar intentos antiguos (más de 15 minutos)
    $attempts = array_filter($attempts, function($timestamp) use ($currentTime) {
        return ($currentTime - $timestamp) < 900; // 15 minutos
    });
    
    if (count($attempts) >= 10) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Massa intents de login. Prova de nou més tard.',
            'retry_after' => 900
        ]);
        exit();
    }
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');
    $userType = trim($input['userType'] ?? 'student');
    $rememberMe = !empty($input['rememberMe']);
    
    // Validación básica
    if (empty($email) || empty($password)) {
        throw new Exception('Email i contrasenya són obligatoris');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Format d\'email invàlid');
    }
    
    if (!in_array($userType, ['student', 'parent', 'teacher', 'admin'])) {
        throw new Exception('Tipus d\'usuari invàlid');
    }
    
    // Intentar login
    $auth = new AuthenticationSystem();
    $result = $auth->login($email, $password, $userType);
    
    if ($result['success']) {
        // Login exitoso
        
        // Si es "recordar-me", extender la sesión
        if ($rememberMe) {
            ini_set('session.cookie_lifetime', 30 * 24 * 3600); // 30 días
        }
        
        // Limpiar intentos de rate limiting
        if (file_exists($rateLimitFile)) {
            unlink($rateLimitFile);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Login exitós',
            'redirect' => $result['redirect'],
            'user' => [
                'name' => $result['user']['nom'] . ' ' . $result['user']['cognoms'],
                'email' => $result['user']['email'],
                'type' => $userType
            ]
        ]);
        
    } else {
        // Login fallido - registrar intento
        $attempts = file_exists($rateLimitFile) ? 
                   json_decode(file_get_contents($rateLimitFile), true) : [];
        $attempts[] = time();
        file_put_contents($rateLimitFile, json_encode($attempts));
        
        http_response_code(401);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    // Error interno
    error_log("Login handler error: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>