<?php
/**
 * Math Advantage - Sistema de Feedback y Encuestas
 * Fase 5: Feedback System
 */

require_once '../../php/classes/Database.php';

class FeedbackSystem {
    private $pdo;
    
    public function __construct() {
        $database = new Database();
        $this->pdo = $database->getConnection();
    }
    
    /**
     * Crear nueva encuesta
     */
    public function createSurvey($data) {
        try {
            $sql = "
            INSERT INTO feedback_surveys 
            (title, description, questions, target_audience, start_date, end_date, 
             max_responses, anonymous, show_results, email_notifications, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['title'],
                $data['description'] ?? null,
                json_encode($data['questions']),
                $data['target_audience'] ?? 'all',
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $data['max_responses'] ?? null,
                $data['anonymous'] ?? true,
                $data['show_results'] ?? false,
                $data['email_notifications'] ?? true,
                $data['created_by'] ?? null
            ]);
            
            return $this->pdo->lastInsertId();
            
        } catch (Exception $e) {
            throw new Exception("Error creando encuesta: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener encuesta por ID
     */
    public function getSurvey($surveyId) {
        try {
            $sql = "
            SELECT fs.*, 
                   COUNT(fr.id) as response_count,
                   (SELECT COUNT(*) FROM feedback_responses fr2 
                    WHERE fr2.survey_id = fs.id AND fr2.submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as recent_responses
            FROM feedback_surveys fs
            LEFT JOIN feedback_responses fr ON fs.id = fr.survey_id
            WHERE fs.id = ?
            GROUP BY fs.id
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$surveyId]);
            $survey = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($survey) {
                $survey['questions'] = json_decode($survey['questions'], true);
            }
            
            return $survey;
            
        } catch (Exception $e) {
            throw new Exception("Error obteniendo encuesta: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener todas las encuestas
     */
    public function getAllSurveys($filters = []) {
        try {
            $conditions = [];
            $params = [];
            
            if (!empty($filters['status'])) {
                $conditions[] = "fs.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['target_audience'])) {
                $conditions[] = "(fs.target_audience = ? OR fs.target_audience = 'all')";
                $params[] = $filters['target_audience'];
            }
            
            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            $sql = "
            SELECT fs.*, 
                   COUNT(fr.id) as response_count,
                   (SELECT COUNT(*) FROM feedback_responses fr2 
                    WHERE fr2.survey_id = fs.id AND fr2.submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as recent_responses,
                   CASE 
                       WHEN fs.max_responses IS NOT NULL AND COUNT(fr.id) >= fs.max_responses THEN 'completed'
                       WHEN fs.end_date IS NOT NULL AND fs.end_date < NOW() THEN 'expired'
                       WHEN fs.start_date IS NULL OR fs.start_date <= NOW() THEN fs.status
                       ELSE 'scheduled'
                   END as effective_status
            FROM feedback_surveys fs
            LEFT JOIN feedback_responses fr ON fs.id = fr.survey_id
            {$whereClause}
            GROUP BY fs.id
            ORDER BY fs.created_at DESC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($surveys as &$survey) {
                $survey['questions'] = json_decode($survey['questions'], true);
            }
            
            return $surveys;
            
        } catch (Exception $e) {
            throw new Exception("Error obteniendo encuestas: " . $e->getMessage());
        }
    }
    
    /**
     * Enviar respuesta a encuesta
     */
    public function submitResponse($surveyId, $responses, $userId = null, $sessionId = null) {
        try {
            // Verificar si la encuesta está activa
            $survey = $this->getSurvey($surveyId);
            if (!$survey || $survey['effective_status'] !== 'active') {
                throw new Exception("La encuesta no está disponible");
            }
            
            // Verificar límite de respuestas
            if ($survey['max_responses'] && $survey['response_count'] >= $survey['max_responses']) {
                throw new Exception("Se ha alcanzado el límite de respuestas");
            }
            
            // Verificar si el usuario ya ha respondido
            if ($userId && !$survey['anonymous']) {
                $sql = "SELECT id FROM feedback_responses WHERE survey_id = ? AND user_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$surveyId, $userId]);
                if ($stmt->fetch()) {
                    throw new Exception("Ya has respondido a esta encuesta");
                }
            }
            
            $sql = "
            INSERT INTO feedback_responses 
            (survey_id, user_id, session_id, responses, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $surveyId,
                $userId,
                $sessionId,
                json_encode($responses),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            return $this->pdo->lastInsertId();
            
        } catch (Exception $e) {
            throw new Exception("Error enviando respuesta: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener resultados de encuesta
     */
    public function getSurveyResults($surveyId) {
        try {
            $survey = $this->getSurvey($surveyId);
            if (!$survey) {
                throw new Exception("Encuesta no encontrada");
            }
            
            // Obtener todas las respuestas
            $sql = "
            SELECT fr.responses, fr.submitted_at, fr.user_id,
                   CASE 
                       WHEN fr.user_id IS NOT NULL THEN CONCAT(s.first_name, ' ', s.last_name)
                       ELSE 'Anónimo'
                   END as respondent_name
            FROM feedback_responses fr
            LEFT JOIN students s ON fr.user_id = s.id
            WHERE fr.survey_id = ?
            ORDER BY fr.submitted_at DESC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$surveyId]);
            $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar respuestas
            $results = [
                'survey' => $survey,
                'total_responses' => count($responses),
                'questions' => [],
                'responses' => []
            ];
            
            foreach ($responses as $response) {
                $responseData = json_decode($response['responses'], true);
                $results['responses'][] = [
                    'respondent' => $response['respondent_name'],
                    'submitted_at' => $response['submitted_at'],
                    'answers' => $responseData
                ];
            }
            
            // Analizar cada pregunta
            foreach ($survey['questions'] as $index => $question) {
                $questionResults = [
                    'question' => $question,
                    'responses' => [],
                    'statistics' => []
                ];
                
                $answers = [];
                foreach ($responses as $response) {
                    $responseData = json_decode($response['responses'], true);
                    if (isset($responseData[$index])) {
                        $answers[] = $responseData[$index];
                    }
                }
                
                $questionResults['responses'] = $answers;
                $questionResults['statistics'] = $this->calculateQuestionStatistics($question, $answers);
                
                $results['questions'][] = $questionResults;
            }
            
            return $results;
            
        } catch (Exception $e) {
            throw new Exception("Error obteniendo resultados: " . $e->getMessage());
        }
    }
    
    /**
     * Calcular estadísticas por pregunta
     */
    private function calculateQuestionStatistics($question, $answers) {
        $stats = [];
        
        switch ($question['type']) {
            case 'multiple_choice':
            case 'single_choice':
                $counts = array_count_values($answers);
                $total = count($answers);
                
                foreach ($question['options'] as $option) {
                    $count = $counts[$option] ?? 0;
                    $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                    $stats[] = [
                        'option' => $option,
                        'count' => $count,
                        'percentage' => round($percentage, 2)
                    ];
                }
                break;
                
            case 'rating':
                $numeric_answers = array_filter($answers, 'is_numeric');
                if (!empty($numeric_answers)) {
                    $stats = [
                        'average' => round(array_sum($numeric_answers) / count($numeric_answers), 2),
                        'min' => min($numeric_answers),
                        'max' => max($numeric_answers),
                        'count' => count($numeric_answers),
                        'distribution' => array_count_values($numeric_answers)
                    ];
                }
                break;
                
            case 'text':
            case 'textarea':
                $stats = [
                    'total_responses' => count(array_filter($answers)),
                    'average_length' => round(array_sum(array_map('strlen', $answers)) / count($answers), 2),
                    'word_count' => array_sum(array_map(function($text) {
                        return str_word_count($text);
                    }, $answers))
                ];
                break;
        }
        
        return $stats;
    }
    
    /**
     * Crear encuesta de satisfacción automática
     */
    public function createSatisfactionSurvey($type = 'general') {
        $templates = [
            'general' => [
                'title' => 'Enquesta de Satisfacció General',
                'description' => 'Ens agradaria conèixer la teva opinió sobre Math Advantage',
                'questions' => [
                    [
                        'type' => 'rating',
                        'question' => 'Com valoraries la teva experiència general amb Math Advantage?',
                        'scale' => [1, 10],
                        'required' => true
                    ],
                    [
                        'type' => 'single_choice',
                        'question' => 'Quina funcionalitat t\'ha semblat més útil?',
                        'options' => ['Evaluacions', 'Chat', 'Videollamades', 'Gestió d\'arxius', 'Gamificació'],
                        'required' => true
                    ],
                    [
                        'type' => 'textarea',
                        'question' => 'Què milloraries de la plataforma?',
                        'required' => false
                    ],
                    [
                        'type' => 'rating',
                        'question' => 'Recomanaries Math Advantage a altres estudiants?',
                        'scale' => [1, 10],
                        'required' => true
                    ]
                ]
            ],
            'course' => [
                'title' => 'Enquesta de Satisfacció del Curs',
                'questions' => [
                    [
                        'type' => 'rating',
                        'question' => 'Com valoraries el contingut del curs?',
                        'scale' => [1, 5],
                        'required' => true
                    ],
                    [
                        'type' => 'rating',
                        'question' => 'Com valoraries la metodologia d\'ensenyament?',
                        'scale' => [1, 5],
                        'required' => true
                    ]
                ]
            ]
        ];
        
        $template = $templates[$type] ?? $templates['general'];
        $template['target_audience'] = 'students';
        $template['anonymous'] = false;
        $template['show_results'] = true;
        
        return $this->createSurvey($template);
    }
    
    /**
     * Obtener estadísticas de feedback
     */
    public function getFeedbackStatistics($days = 30) {
        try {
            $sql = "
            SELECT 
                COUNT(DISTINCT fs.id) as total_surveys,
                COUNT(DISTINCT fr.id) as total_responses,
                COUNT(DISTINCT CASE WHEN fs.status = 'active' THEN fs.id END) as active_surveys,
                AVG(CASE WHEN JSON_EXTRACT(fr.responses, '$[0]') REGEXP '^[0-9]' 
                         THEN CAST(JSON_EXTRACT(fr.responses, '$[0]') AS DECIMAL) END) as avg_satisfaction,
                COUNT(DISTINCT fr.user_id) as unique_respondents,
                COUNT(fr.id) / COUNT(DISTINCT fs.id) as avg_responses_per_survey
            FROM feedback_surveys fs
            LEFT JOIN feedback_responses fr ON fs.id = fr.survey_id 
                AND fr.submitted_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            WHERE fs.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$days, $days]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            throw new Exception("Error obteniendo estadísticas: " . $e->getMessage());
        }
    }
    
    /**
     * Generar informe de feedback
     */
    public function generateFeedbackReport($surveyId = null) {
        try {
            if ($surveyId) {
                // Informe de una encuesta específica
                return $this->getSurveyResults($surveyId);
            } else {
                // Informe general de todas las encuestas
                $surveys = $this->getAllSurveys(['status' => 'active']);
                $statistics = $this->getFeedbackStatistics();
                
                return [
                    'surveys' => $surveys,
                    'statistics' => $statistics,
                    'generated_at' => date('Y-m-d H:i:s')
                ];
            }
            
        } catch (Exception $e) {
            throw new Exception("Error generando informe: " . $e->getMessage());
        }
    }
    
    /**
     * Actualizar estado de encuesta
     */
    public function updateSurveyStatus($surveyId, $status) {
        try {
            $sql = "UPDATE feedback_surveys SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$status, $surveyId]);
            
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            throw new Exception("Error actualizando estado: " . $e->getMessage());
        }
    }
    
    /**
     * Eliminar encuesta
     */
    public function deleteSurvey($surveyId) {
        try {
            $this->pdo->beginTransaction();
            
            // Eliminar respuestas
            $sql = "DELETE FROM feedback_responses WHERE survey_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$surveyId]);
            
            // Eliminar encuesta
            $sql = "DELETE FROM feedback_surveys WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$surveyId]);
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Error eliminando encuesta: " . $e->getMessage());
        }
    }
    
    /**
     * Crear encuestas automáticas basadas en eventos
     */
    public function createEventBasedSurvey($event, $userId) {
        $surveys = [
            'course_completion' => [
                'title' => 'Valoració del Curs Completat',
                'questions' => [
                    [
                        'type' => 'rating',
                        'question' => 'Com valoraries el curs que acabes de completar?',
                        'scale' => [1, 5]
                    ]
                ],
                'target_audience' => 'students'
            ],
            'monthly_usage' => [
                'title' => 'Feedback Mensual d\'Ús',
                'questions' => [
                    [
                        'type' => 'rating',
                        'question' => 'Com ha estat la teva experiència aquest mes?',
                        'scale' => [1, 10]
                    ]
                ],
                'target_audience' => 'all'
            ]
        ];
        
        if (isset($surveys[$event])) {
            return $this->createSurvey($surveys[$event]);
        }
        
        return false;
    }
}
?>