<?php

abstract class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function findAll($orderBy = null, $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->db->fetchAll($sql);
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }
    
    public function findWhere($conditions, $params = []) {
        $sql = "SELECT * FROM {$this->table} WHERE {$conditions}";
        return $this->db->fetchAll($sql, $params);
    }
    
    public function findOneWhere($conditions, $params = []) {
        $sql = "SELECT * FROM {$this->table} WHERE {$conditions} LIMIT 1";
        return $this->db->fetchOne($sql, $params);
    }
    
    public function create($data) {
        // Remove any data that doesn't belong to this table
        $data = $this->filterData($data);
        
        // Add timestamps if they exist in the table
        if ($this->hasTimestamps()) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->insert($this->table, $data);
    }
    
    public function update($id, $data) {
        // Remove any data that doesn't belong to this table
        $data = $this->filterData($data);
        
        // Add updated timestamp if it exists in the table
        if ($this->hasTimestamps()) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->update(
            $this->table,
            $data,
            "{$this->primaryKey} = :id",
            ['id' => $id]
        );
    }
    
    public function delete($id) {
        return $this->db->delete(
            $this->table,
            "{$this->primaryKey} = :id",
            ['id' => $id]
        );
    }
    
    public function count($conditions = '', $params = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        
        if ($conditions) {
            $sql .= " WHERE {$conditions}";
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return $result['total'];
    }
    
    protected function filterData($data) {
        // Override in child classes to filter allowed fields
        return $data;
    }
    
    protected function hasTimestamps() {
        // Override in child classes if table has created_at/updated_at
        return false;
    }
    
    public function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            if (strpos($rule, 'required') !== false && empty($value)) {
                $errors[$field] = "El campo {$field} es obligatorio";
                continue;
            }
            
            if (strpos($rule, 'email') !== false && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = "El formato del email no es válido";
            }
            
            if (strpos($rule, 'min:') !== false) {
                preg_match('/min:(\d+)/', $rule, $matches);
                $min = (int)$matches[1];
                if (strlen($value) < $min) {
                    $errors[$field] = "El campo {$field} debe tener al menos {$min} caracteres";
                }
            }
            
            if (strpos($rule, 'max:') !== false) {
                preg_match('/max:(\d+)/', $rule, $matches);
                $max = (int)$matches[1];
                if (strlen($value) > $max) {
                    $errors[$field] = "El campo {$field} no puede tener más de {$max} caracteres";
                }
            }
        }
        
        return $errors;
    }
}