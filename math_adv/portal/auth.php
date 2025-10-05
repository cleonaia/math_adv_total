<?php
/**
 * Math Advantage - Authentication System
 * Fase 3: Portal de Familias - Sistema de Autenticación Segura
 */

require_once '../php/config.php';
require_once '../php/classes/Database.php';

class AuthenticationSystem {
    private $db;
    private $sessionTimeout = 3600; // 1 hora
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->startSecureSession();
    }
    
    private function startSecureSession() {
        // Configuración de sesión segura
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 1);
        
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerar ID de sesión periódicamente
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    public function login($email, $password, $userType = 'student') {
        try {
            // Verificar intentos de login
            if ($this->isLoginBlocked($email)) {
                return [
                    'success' => false,
                    'message' => 'Massa intents fallits. Prova de nou en 15 minuts.',
                    'blocked' => true
                ];
            }
            
            $user = $this->validateCredentials($email, $password, $userType);
            
            if ($user) {
                // Login exitoso
                $this->createUserSession($user, $userType);
                $this->clearFailedAttempts($email);
                $this->logActivity($user['id'], 'login_success', $userType);
                
                return [
                    'success' => true,
                    'user' => $user,
                    'redirect' => $this->getRedirectUrl($userType, $user['rol'] ?? null)
                ];
            } else {
                // Login fallido
                $this->recordFailedAttempt($email);
                $this->logActivity(null, 'login_failed', $userType, ['email' => $email]);
                
                return [
                    'success' => false,
                    'message' => 'Email o contrasenya incorrectes'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error intern del sistema'
            ];
        }
    }
    
    private function validateCredentials($email, $password, $userType) {
        switch ($userType) {
            case 'student':
                return $this->validateStudentCredentials($email, $password);
            case 'teacher':
                return $this->validateTeacherCredentials($email, $password);
            case 'admin':
                return $this->validateAdminCredentials($email, $password);
            case 'parent':
                return $this->validateParentCredentials($email, $password);
            default:
                return false;
        }
    }
    
    private function validateStudentCredentials($email, $password) {
        $student = $this->db->query(
            "SELECT id, nom, cognoms, email, portal_password, student_code, estat 
             FROM students 
             WHERE email = ? AND estat = 'actiu'",
            [$email]
        )->fetch(PDO::FETCH_ASSOC);
        
        if ($student && password_verify($password, $student['portal_password'])) {
            // Actualizar último acceso
            $this->db->update('students', $student['id'], [
                'last_portal_access' => date('Y-m-d H:i:s')
            ]);
            
            return $student;
        }
        
        return false;
    }
    
    private function validateTeacherCredentials($email, $password) {
        $teacher = $this->db->query(
            "SELECT id, nom, cognoms, email, portal_password, teacher_code, estat 
             FROM teachers 
             WHERE email = ? AND estat = 'actiu'",
            [$email]
        )->fetch(PDO::FETCH_ASSOC);
        
        if ($teacher && password_verify($password, $teacher['portal_password'])) {
            $this->db->update('teachers', $teacher['id'], [
                'last_portal_access' => date('Y-m-d H:i:s')
            ]);
            
            return $teacher;
        }
        
        return false;
    }
    
    private function validateAdminCredentials($email, $password) {
        $admin = $this->db->query(
            "SELECT id, username, email, password_hash, rol, nom, cognoms, estat 
             FROM users 
             WHERE email = ? AND estat = 'actiu'",
            [$email]
        )->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            $this->db->update('users', $admin['id'], [
                'last_login' => date('Y-m-d H:i:s'),
                'failed_login_attempts' => 0
            ]);
            
            return $admin;
        }
        
        return false;
    }
    
    private function validateParentCredentials($email, $password) {
        // Los padres acceden con el mismo email que tienen registrado
        $student = $this->db->query(
            "SELECT s.*, CONCAT(s.nom, ' ', s.cognoms) as student_name
             FROM students s 
             WHERE s.email_pares = ? AND s.estat = 'actiu'",
            [$email]
        )->fetch(PDO::FETCH_ASSOC);
        
        if ($student) {
            // Para padres, la contraseña es el código del estudiante por defecto
            // O pueden establecer su propia contraseña
            $parentPassword = $student['portal_password'] ?? password_hash($student['student_code'], PASSWORD_DEFAULT);
            
            if (password_verify($password, $parentPassword)) {
                return [
                    'id' => 'parent_' . $student['id'],
                    'nom' => $student['nom_pare'] ?: $student['nom_mare'] ?: 'Família',
                    'cognoms' => $student['cognoms'],
                    'email' => $email,
                    'student_id' => $student['id'],
                    'student_name' => $student['student_name'],
                    'tipus' => 'parent'
                ];
            }
        }
        
        return false;
    }
    
    private function createUserSession($user, $userType) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = $userType;
        $_SESSION['user_name'] = $user['nom'] . ' ' . $user['cognoms'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Datos específicos por tipo de usuario
        switch ($userType) {
            case 'student':
                $_SESSION['student_code'] = $user['student_code'];
                break;
            case 'teacher':
                $_SESSION['teacher_code'] = $user['teacher_code'];
                break;
            case 'admin':
                $_SESSION['user_rol'] = $user['rol'];
                $_SESSION['permissions'] = json_decode($user['permissions'] ?? '{}', true);
                break;
            case 'parent':
                $_SESSION['student_id'] = $user['student_id'];
                $_SESSION['student_name'] = $user['student_name'];
                break;
        }
    }
    
    private function getRedirectUrl($userType, $rol = null) {
        switch ($userType) {
            case 'student':
                return 'student/dashboard.php';
            case 'teacher':
                return 'teacher/dashboard.php';
            case 'parent':
                return 'parent/dashboard.php';
            case 'admin':
                return 'admin/dashboard.php';
            default:
                return 'login.php';
        }
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', $_SESSION['user_type']);
        }
        
        session_destroy();
        return true;
    }
    
    public function isAuthenticated() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['login_time'])) {
            return false;
        }
        
        // Verificar timeout
        if (time() - $_SESSION['last_activity'] > $this->sessionTimeout) {
            $this->logout();
            return false;
        }
        
        // Actualizar última actividad
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    public function requireAuth($allowedTypes = []) {
        if (!$this->isAuthenticated()) {
            header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit();
        }
        
        if (!empty($allowedTypes) && !in_array($_SESSION['user_type'], $allowedTypes)) {
            header('Location: unauthorized.php');
            exit();
        }
        
        return true;
    }
    
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'type' => $_SESSION['user_type'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'login_time' => $_SESSION['login_time'],
            'rol' => $_SESSION['user_rol'] ?? null,
            'permissions' => $_SESSION['permissions'] ?? []
        ];
    }
    
    private function isLoginBlocked($email) {
        $attempts = $this->db->query(
            "SELECT failed_attempts, locked_until 
             FROM login_attempts 
             WHERE email = ?",
            [$email]
        )->fetch(PDO::FETCH_ASSOC);
        
        if ($attempts) {
            if ($attempts['locked_until'] && strtotime($attempts['locked_until']) > time()) {
                return true;
            }
            
            return $attempts['failed_attempts'] >= 5;
        }
        
        return false;
    }
    
    private function recordFailedAttempt($email) {
        $existing = $this->db->query(
            "SELECT id, failed_attempts FROM login_attempts WHERE email = ?",
            [$email]
        )->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            $newAttempts = $existing['failed_attempts'] + 1;
            $lockedUntil = null;
            
            if ($newAttempts >= 5) {
                $lockedUntil = date('Y-m-d H:i:s', time() + 900); // 15 minutos
            }
            
            $this->db->update('login_attempts', $existing['id'], [
                'failed_attempts' => $newAttempts,
                'locked_until' => $lockedUntil,
                'last_attempt' => date('Y-m-d H:i:s')
            ]);
        } else {
            $this->db->insert('login_attempts', [
                'email' => $email,
                'failed_attempts' => 1,
                'last_attempt' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    private function clearFailedAttempts($email) {
        $this->db->query(
            "DELETE FROM login_attempts WHERE email = ?",
            [$email]
        );
    }
    
    private function logActivity($userId, $action, $userType, $data = []) {
        $this->db->insert('activity_log', [
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => 'auth',
            'entity_id' => $userId ?: 0,
            'description' => "$action for $userType",
            'new_values' => json_encode($data),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }
    
    // Métodos para gestión de contraseñas
    public function generateTempPassword($userType, $userId) {
        $tempPassword = $this->generateRandomPassword();
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
        
        switch ($userType) {
            case 'student':
                $this->db->update('students', $userId, [
                    'portal_password' => $hashedPassword
                ]);
                break;
            case 'teacher':
                $this->db->update('teachers', $userId, [
                    'portal_password' => $hashedPassword
                ]);
                break;
        }
        
        return $tempPassword;
    }
    
    public function changePassword($userType, $userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        switch ($userType) {
            case 'student':
                return $this->db->update('students', $userId, [
                    'portal_password' => $hashedPassword
                ]);
            case 'teacher':
                return $this->db->update('teachers', $userId, [
                    'portal_password' => $hashedPassword
                ]);
            case 'admin':
                return $this->db->update('users', $userId, [
                    'password_hash' => $hashedPassword
                ]);
        }
        
        return false;
    }
    
    private function generateRandomPassword($length = 8) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        return substr(str_shuffle($chars), 0, $length);
    }
}
?>