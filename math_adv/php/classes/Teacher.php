<?php

require_once __DIR__ . '/BaseModel.php';

class Teacher extends BaseModel {
    protected $table = 'teachers';
    
    protected function hasTimestamps() {
        return true;
    }
    
    protected function filterData($data) {
        $allowedFields = [
            'nom', 'cognoms', 'email', 'telefon', 'especialitat',
            'experiencia_anys', 'titulacions', 'horari_disponible',
            'estat', 'data_incorporacio', 'salari_hora', 'notes'
        ];
        
        return array_intersect_key($data, array_flip($allowedFields));
    }
    
    public function getValidationRules() {
        return [
            'nom' => 'required|min:2|max:50',
            'cognoms' => 'required|min:2|max:100',
            'email' => 'required|email|max:100',
            'telefon' => 'required|min:9|max:15',
            'especialitat' => 'required|max:100',
            'experiencia_anys' => 'required',
            'titulacions' => 'required|max:500'
        ];
    }
    
    public function findByEmail($email) {
        return $this->findOneWhere('email = :email', ['email' => $email]);
    }
    
    public function findByEspecialitat($especialitat) {
        return $this->findWhere('especialitat = :especialitat AND estat = :estat', [
            'especialitat' => $especialitat,
            'estat' => 'actiu'
        ]);
    }
    
    public function getActiveTeachers() {
        return $this->findWhere('estat = :estat', ['estat' => 'actiu']);
    }
    
    public function getAvailableTeachers($day = null, $time = null) {
        $sql = "SELECT * FROM {$this->table} WHERE estat = 'actiu'";
        
        if ($day && $time) {
            // This would require more complex logic for schedule matching
            // For now, return all active teachers
        }
        
        return $this->db->fetchAll($sql . " ORDER BY cognoms, nom");
    }
    
    public function searchTeachers($searchTerm) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE nom LIKE :search 
                OR cognoms LIKE :search 
                OR email LIKE :search 
                OR especialitat LIKE :search
                ORDER BY cognoms, nom";
        
        return $this->db->fetchAll($sql, ['search' => "%{$searchTerm}%"]);
    }
    
    public function getStatistics() {
        $stats = [];
        
        // Total teachers
        $stats['total'] = $this->count();
        
        // Active teachers
        $stats['active'] = $this->count('estat = :estat', ['estat' => 'actiu']);
        
        // Teachers by specialty
        $specialties = $this->db->fetchAll(
            "SELECT especialitat, COUNT(*) as count 
             FROM {$this->table} 
             WHERE estat = 'actiu' 
             GROUP BY especialitat"
        );
        $stats['by_specialty'] = $specialties;
        
        // Average experience
        $avgExp = $this->db->fetchOne(
            "SELECT AVG(experiencia_anys) as avg_experience 
             FROM {$this->table} 
             WHERE estat = 'actiu'"
        );
        $stats['avg_experience'] = round($avgExp['avg_experience'], 1);
        
        return $stats;
    }
    
    public function createTeacher($data) {
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
            
            // Set default values
            $data['estat'] = 'actiu';
            $data['data_incorporacio'] = date('Y-m-d');
            
            // Create teacher
            $teacherId = $this->create($data);
            
            return ['success' => true, 'teacher_id' => $teacherId];
            
        } catch (Exception $e) {
            error_log("Error creating teacher: " . $e->getMessage());
            return ['success' => false, 'errors' => ['general' => 'Error en el sistema. Intentalo más tarde.']];
        }
    }
    
    public function updateStatus($id, $status) {
        $allowedStatuses = ['actiu', 'inactiu', 'vacances'];
        
        if (!in_array($status, $allowedStatuses)) {
            return false;
        }
        
        return $this->update($id, ['estat' => $status]);
    }
    
    public function assignToClass($teacherId, $classId) {
        // This would typically involve a separate classes table
        // For now, we'll implement basic assignment logic
        
        $sql = "INSERT INTO teacher_classes (teacher_id, class_id, created_at) 
                VALUES (:teacher_id, :class_id, :created_at)";
        
        return $this->db->query($sql, [
            'teacher_id' => $teacherId,
            'class_id' => $classId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}