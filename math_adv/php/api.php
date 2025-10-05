<?php
/**
 * Math Advantage API Endpoints - Fase 2
 * Digital Management and Automation API
 */

require_once 'config.php';
require_once 'classes/Database.php';
require_once 'classes/BaseModel.php';
require_once 'classes/Student.php';
require_once 'classes/Teacher.php';
require_once 'classes/NotificationSystem.php';

class MathAdvantageAPI {
    private $db;
    private $notificationSystem;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->notificationSystem = new NotificationSystem();
        
        // Set CORS headers
        $this->setCorsHeaders();
    }
    
    private function setCorsHeaders() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Content-Type: application/json; charset=UTF-8');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));
        
        // Remove 'api.php' from path parts if present
        if (end($pathParts) === 'api.php') {
            array_pop($pathParts);
        }
        
        $endpoint = $pathParts[count($pathParts) - 1] ?? '';
        $action = $pathParts[count($pathParts) - 2] ?? '';
        
        try {
            switch ($action) {
                case 'students':
                    return $this->handleStudentsEndpoint($method, $endpoint);
                case 'teachers':
                    return $this->handleTeachersEndpoint($method, $endpoint);
                case 'classes':
                    return $this->handleClassesEndpoint($method, $endpoint);
                case 'contact':
                    return $this->handleContactEndpoint($method, $endpoint);
                case 'notifications':
                    return $this->handleNotificationsEndpoint($method, $endpoint);
                case 'automation':
                    return $this->handleAutomationEndpoint($method, $endpoint);
                case 'dashboard':
                    return $this->handleDashboardEndpoint($method, $endpoint);
                default:
                    return $this->sendResponse(['error' => 'Endpoint not found'], 404);
            }
        } catch (Exception $e) {
            error_log("API Error: " . $e->getMessage());
            return $this->sendResponse([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    // Students endpoints
    private function handleStudentsEndpoint($method, $endpoint) {
        $student = new Student();
        
        switch ($method) {
            case 'GET':
                if ($endpoint === 'statistics') {
                    return $this->sendResponse($student->getStatistics());
                } elseif (is_numeric($endpoint)) {
                    $studentData = $student->findById($endpoint);
                    return $studentData ? 
                        $this->sendResponse($studentData) : 
                        $this->sendResponse(['error' => 'Student not found'], 404);
                } else {
                    $filters = $_GET;
                    return $this->sendResponse($student->searchStudents($filters));
                }
                
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                
                if ($endpoint === 'enroll') {
                    $result = $student->enrollStudent($data);
                    return $this->sendResponse($result, $result['success'] ? 201 : 400);
                } else {
                    $result = $student->create($data);
                    return $this->sendResponse($result, $result['id'] ? 201 : 400);
                }
                
            case 'PUT':
                if (is_numeric($endpoint)) {
                    $data = json_decode(file_get_contents('php://input'), true);
                    $result = $student->update($endpoint, $data);
                    return $this->sendResponse($result ? ['success' => true] : ['error' => 'Update failed']);
                }
                break;
                
            case 'DELETE':
                if (is_numeric($endpoint)) {
                    $result = $student->delete($endpoint);
                    return $this->sendResponse($result ? ['success' => true] : ['error' => 'Delete failed']);
                }
                break;
        }
        
        return $this->sendResponse(['error' => 'Method not allowed'], 405);
    }
    
    // Teachers endpoints
    private function handleTeachersEndpoint($method, $endpoint) {
        $teacher = new Teacher();
        
        switch ($method) {
            case 'GET':
                if ($endpoint === 'available') {
                    return $this->sendResponse($teacher->getAvailableTeachers());
                } elseif (is_numeric($endpoint)) {
                    $teacherData = $teacher->findById($endpoint);
                    return $teacherData ? 
                        $this->sendResponse($teacherData) : 
                        $this->sendResponse(['error' => 'Teacher not found'], 404);
                } else {
                    return $this->sendResponse($teacher->findAll());
                }
                
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                
                if ($endpoint === 'assign') {
                    $result = $teacher->assignToClass($data['teacher_id'], $data['class_id']);
                    return $this->sendResponse($result);
                } else {
                    $result = $teacher->createTeacher($data);
                    return $this->sendResponse($result, $result['id'] ? 201 : 400);
                }
                
            case 'PUT':
                if (is_numeric($endpoint)) {
                    $data = json_decode(file_get_contents('php://input'), true);
                    $result = $teacher->update($endpoint, $data);
                    return $this->sendResponse($result ? ['success' => true] : ['error' => 'Update failed']);
                }
                break;
        }
        
        return $this->sendResponse(['error' => 'Method not allowed'], 405);
    }
    
    // Classes endpoints
    private function handleClassesEndpoint($method, $endpoint) {
        switch ($method) {
            case 'GET':
                if (is_numeric($endpoint)) {
                    $class = $this->db->query(
                        "SELECT * FROM classes WHERE id = ?", 
                        [$endpoint]
                    )->fetch(PDO::FETCH_ASSOC);
                    
                    if ($class) {
                        // Get enrolled students
                        $students = $this->db->query(
                            "SELECT s.*, e.data_inscripcio FROM students s 
                             JOIN enrollments e ON s.id = e.student_id 
                             WHERE e.class_id = ? AND e.estat = 'actiu'", 
                            [$endpoint]
                        )->fetchAll(PDO::FETCH_ASSOC);
                        
                        $class['enrolled_students'] = $students;
                        return $this->sendResponse($class);
                    }
                    return $this->sendResponse(['error' => 'Class not found'], 404);
                } else {
                    $classes = $this->db->query("SELECT * FROM classes ORDER BY dia_setmana, hora_inici")->fetchAll(PDO::FETCH_ASSOC);
                    return $this->sendResponse($classes);
                }
                
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $this->db->insert('classes', $data);
                return $this->sendResponse(['id' => $id, 'success' => true], 201);
        }
        
        return $this->sendResponse(['error' => 'Method not allowed'], 405);
    }
    
    // Contact form endpoint
    private function handleContactEndpoint($method, $endpoint) {
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required = ['nom', 'email', 'missatge'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->sendResponse(['error' => "Field '$field' is required"], 400);
                }
            }
            
            // Save to database
            $contactId = $this->db->insert('contact_submissions', $data);
            
            // Send auto-response if enabled
            $autoResponse = $this->db->query(
                "SELECT setting_value FROM settings WHERE setting_key = 'auto_response_contact_form'"
            )->fetchColumn();
            
            if ($autoResponse === 'true') {
                $this->notificationSystem->sendContactAutoResponse($data['email'], $data['nom']);
            }
            
            return $this->sendResponse([
                'id' => $contactId,
                'success' => true,
                'message' => 'Missatge enviat correctament'
            ], 201);
        }
        
        return $this->sendResponse(['error' => 'Method not allowed'], 405);
    }
    
    // Notifications endpoints
    private function handleNotificationsEndpoint($method, $endpoint) {
        switch ($method) {
            case 'GET':
                if ($endpoint === 'pending') {
                    $notifications = $this->db->query(
                        "SELECT * FROM notifications_log WHERE status = 'pending' ORDER BY created_at DESC LIMIT 50"
                    )->fetchAll(PDO::FETCH_ASSOC);
                    return $this->sendResponse($notifications);
                } else {
                    $notifications = $this->db->query(
                        "SELECT * FROM notifications_log ORDER BY created_at DESC LIMIT 100"
                    )->fetchAll(PDO::FETCH_ASSOC);
                    return $this->sendResponse($notifications);
                }
                
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                
                if ($endpoint === 'send-bulk') {
                    $result = $this->notificationSystem->sendBulkNotification(
                        $data['type'],
                        $data['recipients'],
                        $data['subject'],
                        $data['message'],
                        $data['data'] ?? []
                    );
                    return $this->sendResponse($result);
                }
                break;
        }
        
        return $this->sendResponse(['error' => 'Method not allowed'], 405);
    }
    
    // Dashboard endpoints
    private function handleDashboardEndpoint($method, $endpoint) {
        if ($method === 'GET') {
            $stats = [];
            
            // Students statistics
            $studentStats = $this->db->query("
                SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN estat = 'actiu' THEN 1 END) as actius,
                    COUNT(CASE WHEN created_at >= CURDATE() - INTERVAL 30 DAY THEN 1 END) as nous_mes
                FROM students
            ")->fetch(PDO::FETCH_ASSOC);
            
            // Classes statistics
            $classStats = $this->db->query("
                SELECT 
                    COUNT(*) as total_classes,
                    COUNT(CASE WHEN estat = 'actiu' THEN 1 END) as classes_actives
                FROM classes
            ")->fetch(PDO::FETCH_ASSOC);
            
            // Enrollments this month
            $enrollmentStats = $this->db->query("
                SELECT COUNT(*) as inscripcions_mes
                FROM enrollments 
                WHERE data_inscripcio >= CURDATE() - INTERVAL 30 DAY
            ")->fetchColumn();
            
            // Recent notifications
            $recentNotifications = $this->db->query("
                SELECT type, status, COUNT(*) as count
                FROM notifications_log 
                WHERE created_at >= CURDATE() - INTERVAL 7 DAY
                GROUP BY type, status
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->sendResponse([
                'students' => $studentStats,
                'classes' => $classStats,
                'enrollments_this_month' => $enrollmentStats,
                'notifications' => $recentNotifications,
                'generated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        return $this->sendResponse(['error' => 'Method not allowed'], 405);
    }
    
    // Automation endpoints
    private function handleAutomationEndpoint($method, $endpoint) {
        if ($method === 'POST') {
            switch ($endpoint) {
                case 'process-pending':
                    $this->processPendingAutomations();
                    return $this->sendResponse(['success' => true, 'message' => 'Automations processed']);
                    
                case 'schedule-task':
                    $data = json_decode(file_get_contents('php://input'), true);
                    $taskId = $this->scheduleAutomationTask($data);
                    return $this->sendResponse(['task_id' => $taskId, 'success' => true]);
            }
        }
        
        return $this->sendResponse(['error' => 'Method not allowed'], 405);
    }
    
    // Utility methods
    private function processPendingAutomations() {
        // Process pending notifications
        $pendingNotifications = $this->db->query(
            "SELECT * FROM notifications_log WHERE status = 'pending' AND delivery_attempts < 3"
        )->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($pendingNotifications as $notification) {
            try {
                $result = $this->notificationSystem->processNotification($notification);
                
                $this->db->update('notifications_log', $notification['id'], [
                    'status' => $result ? 'sent' : 'failed',
                    'sent_at' => date('Y-m-d H:i:s'),
                    'delivery_attempts' => $notification['delivery_attempts'] + 1
                ]);
            } catch (Exception $e) {
                error_log("Notification processing error: " . $e->getMessage());
            }
        }
        
        // Process scheduled automation tasks
        $pendingTasks = $this->db->query(
            "SELECT * FROM automation_tasks WHERE status = 'pending' AND scheduled_for <= NOW()"
        )->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($pendingTasks as $task) {
            $this->executeAutomationTask($task);
        }
    }
    
    private function scheduleAutomationTask($data) {
        return $this->db->insert('automation_tasks', [
            'task_type' => $data['task_type'],
            'scheduled_for' => $data['scheduled_for'],
            'target_type' => $data['target_type'],
            'target_ids' => json_encode($data['target_ids']),
            'task_config' => json_encode($data['task_config']),
            'is_recurring' => $data['is_recurring'] ?? false,
            'recurrence_pattern' => $data['recurrence_pattern'] ?? null
        ]);
    }
    
    private function executeAutomationTask($task) {
        $this->db->update('automation_tasks', $task['id'], ['status' => 'running']);
        
        try {
            $config = json_decode($task['task_config'], true);
            $targetIds = json_decode($task['target_ids'], true);
            
            switch ($task['task_type']) {
                case 'payment_reminder':
                    $this->sendPaymentReminders($targetIds, $config);
                    break;
                case 'class_reminder':
                    $this->sendClassReminders($targetIds, $config);
                    break;
                case 'enrollment_followup':
                    $this->sendEnrollmentFollowup($targetIds, $config);
                    break;
            }
            
            $this->db->update('automation_tasks', $task['id'], [
                'status' => 'completed',
                'executed_at' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            $this->db->update('automation_tasks', $task['id'], [
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
        }
    }
    
    // Automation task execution methods
    private function sendPaymentReminders($targetIds, $config) {
        $message = $config['message'] ?? 'Recordatori de pagament pendent per Math Advantage';
        
        foreach ($targetIds as $studentId) {
            $student = $this->db->query(
                "SELECT * FROM students WHERE id = ?", 
                [$studentId]
            )->fetch(PDO::FETCH_ASSOC);
            
            if ($student) {
                $this->notificationSystem->sendPaymentReminder(
                    $student['email'], 
                    $student['nom'], 
                    $message
                );
            }
        }
    }
    
    private function sendClassReminders($targetIds, $config) {
        $message = $config['message'] ?? 'Recordatori de classe per Math Advantage';
        
        foreach ($targetIds as $classId) {
            $students = $this->db->query(
                "SELECT s.* FROM students s 
                 JOIN enrollments e ON s.id = e.student_id 
                 WHERE e.class_id = ? AND e.estat = 'actiu'", 
                [$classId]
            )->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($students as $student) {
                $this->notificationSystem->sendClassReminder(
                    $student['email'], 
                    $student['nom'], 
                    $message
                );
            }
        }
    }
    
    private function sendEnrollmentFollowup($targetIds, $config) {
        $message = $config['message'] ?? 'Seguiment d\'inscripció - Math Advantage';
        
        foreach ($targetIds as $studentId) {
            $student = $this->db->query(
                "SELECT * FROM students WHERE id = ?", 
                [$studentId]
            )->fetch(PDO::FETCH_ASSOC);
            
            if ($student) {
                $this->notificationSystem->sendEnrollmentFollowup(
                    $student['email'], 
                    $student['nom'], 
                    $message
                );
            }
        }
    }
    
    private function sendResponse($data, $httpCode = 200) {
        http_response_code($httpCode);
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Initialize and handle API request
$api = new MathAdvantageAPI();
$api->handleRequest();