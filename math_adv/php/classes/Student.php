<?php

require_once __DIR__ . '/BaseModel.php';

class Student extends BaseModel {
    protected $table = 'students';
    
    protected function hasTimestamps() {
        return true;
    }
    
    protected function filterData($data) {
        $allowedFields = [
            'nom', 'cognoms', 'email', 'telefon', 'data_naixement',
            'nivell_educatiu', 'curs', 'centre_educatiu', 'necessitats_especials',
            'nom_pare', 'nom_mare', 'telefon_urgencies', 'estat', 'notes'
        ];
        
        return array_intersect_key($data, array_flip($allowedFields));
    }
    
    public function getValidationRules() {
        return [
            'nom' => 'required|min:2|max:50',
            'cognoms' => 'required|min:2|max:100',
            'email' => 'required|email|max:100',
            'telefon' => 'required|min:9|max:15',
            'data_naixement' => 'required',
            'nivell_educatiu' => 'required',
            'curs' => 'required|max:50',
            'centre_educatiu' => 'required|max:100',
            'nom_pare' => 'max:100',
            'nom_mare' => 'max:100',
            'telefon_urgencies' => 'max:15'
        ];
    }
    
    public function findByEmail($email) {
        return $this->findOneWhere('email = :email', ['email' => $email]);
    }
    
    public function findByNivell($nivell) {
        return $this->findWhere('nivell_educatiu = :nivell', ['nivell' => $nivell]);
    }
    
    public function getActiveStudents() {
        return $this->findWhere('estat = :estat', ['estat' => 'actiu']);
    }
    
    public function searchStudents($searchTerm) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE nom LIKE :search 
                OR cognoms LIKE :search 
                OR email LIKE :search 
                OR centre_educatiu LIKE :search
                ORDER BY cognoms, nom";
        
        return $this->db->fetchAll($sql, ['search' => "%{$searchTerm}%"]);
    }
    
    public function getStatistics() {
        $stats = [];
        
        // Total students
        $stats['total'] = $this->count();
        
        // Active students
        $stats['active'] = $this->count('estat = :estat', ['estat' => 'actiu']);
        
        // Students by level
        $levels = $this->db->fetchAll(
            "SELECT nivell_educatiu, COUNT(*) as count 
             FROM {$this->table} 
             WHERE estat = 'actiu' 
             GROUP BY nivell_educatiu"
        );
        $stats['by_level'] = $levels;
        
        // Recent enrollments (last 30 days)
        $stats['recent'] = $this->count(
            'created_at >= :date AND estat = :estat', 
            [
                'date' => date('Y-m-d H:i:s', strtotime('-30 days')),
                'estat' => 'actiu'
            ]
        );
        
        return $stats;
    }
    
    public function enrollStudent($data) {
        try {
            // Validate data
            $errors = $this->validate($data, $this->getValidationRules());
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            // Check if email already exists
            if ($this->findByEmail($data['email'])) {
                return ['success' => false, 'errors' => ['email' => 'Este email ya está registrado']];
            }
            
            // Set default status
            $data['estat'] = 'pendent';
            
            // Create student
            $studentId = $this->create($data);
            
            return ['success' => true, 'student_id' => $studentId];
            
        } catch (Exception $e) {
            error_log("Error enrolling student: " . $e->getMessage());
            return ['success' => false, 'errors' => ['general' => 'Error en el sistema. Intentalo más tarde.']];
        }
    }
    
    public function updateStatus($id, $status) {
        $allowedStatuses = ['pendent', 'actiu', 'inactiu', 'graduat'];
        
        if (!in_array($status, $allowedStatuses)) {
            return false;
        }
        
        return $this->update($id, ['estat' => $status]);
    }
}