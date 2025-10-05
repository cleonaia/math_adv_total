<?php
// Math Advantage - File Management System
require_once '../classes/Database.php';

class FileManager {
    private $pdo;
    private $upload_path;
    private $allowed_types;
    private $max_file_size;
    
    public function __construct() {
        $database = new Database();
        $this->pdo = $database->getConnection();
        $this->upload_path = $_SERVER['DOCUMENT_ROOT'] . '/math_adv/uploads/';
        $this->allowed_types = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'mp4' => 'video/mp4',
            'avi' => 'video/avi',
            'mov' => 'video/quicktime'
        ];
        $this->max_file_size = 50 * 1024 * 1024; // 50MB
        
        // Crear directorios si no existen
        $this->createDirectories();
    }
    
    private function createDirectories() {
        $dirs = [
            $this->upload_path,
            $this->upload_path . 'materials/',
            $this->upload_path . 'exercises/',
            $this->upload_path . 'submissions/',
            $this->upload_path . 'temp/'
        ];
        
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    public function uploadFile($file, $class_id, $uploaded_by, $file_type, $title = '', $description = '') {
        try {
            // Validar archivo
            $validation = $this->validateFile($file);
            if (!$validation['success']) {
                return $validation;
            }
            
            // Generar nombre único
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = $this->generateUniqueFilename($file['name'], $extension);
            
            // Determinar directorio según tipo
            $subdir = $this->getSubdirectory($file_type);
            $full_path = $this->upload_path . $subdir . $filename;
            
            // Mover archivo
            if (move_uploaded_file($file['tmp_name'], $full_path)) {
                // Guardar en base de datos
                $file_id = $this->saveFileRecord(
                    $filename,
                    $file['name'],
                    $subdir . $filename,
                    $file['size'],
                    $extension,
                    $class_id,
                    $uploaded_by,
                    $file_type,
                    $title,
                    $description
                );
                
                return [
                    'success' => true,
                    'message' => 'Archivo subido correctamente',
                    'file_id' => $file_id,
                    'filename' => $filename
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al mover el archivo'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    private function validateFile($file) {
        // Verificar errores de subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => 'Error en la subida del archivo: ' . $file['error']
            ];
        }
        
        // Verificar tamaño
        if ($file['size'] > $this->max_file_size) {
            return [
                'success' => false,
                'message' => 'El archivo es demasiado grande. Máximo 50MB.'
            ];
        }
        
        // Verificar tipo MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!isset($this->allowed_types[$extension]) || $this->allowed_types[$extension] !== $mime_type) {
            return [
                'success' => false,
                'message' => 'Tipo de archivo no permitido. Extensión: ' . $extension
            ];
        }
        
        return ['success' => true];
    }
    
    private function generateUniqueFilename($original_name, $extension) {
        $base_name = pathinfo($original_name, PATHINFO_FILENAME);
        $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $base_name);
        return date('Y-m-d_H-i-s') . '_' . $safe_name . '.' . $extension;
    }
    
    private function getSubdirectory($file_type) {
        switch ($file_type) {
            case 'material':
            case 'theory':
                return 'materials/';
            case 'exercise':
            case 'homework':
                return 'exercises/';
            case 'submission':
                return 'submissions/';
            default:
                return 'materials/';
        }
    }
    
    private function saveFileRecord($filename, $original_name, $file_path, $file_size, $file_type_ext, $class_id, $uploaded_by, $file_type, $title, $description) {
        $stmt = $this->pdo->prepare("
            INSERT INTO class_files (
                filename, original_name, file_path, file_size, file_type, 
                class_id, uploaded_by, upload_type, title, description, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $filename, $original_name, $file_path, $file_size, $file_type_ext,
            $class_id, $uploaded_by, $file_type, $title, $description
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    public function getFilesByClass($class_id, $file_type = null) {
        $sql = "
            SELECT cf.*, 
                   CASE 
                       WHEN cf.uploaded_by IN (SELECT id FROM teachers) THEN 'teacher'
                       WHEN cf.uploaded_by IN (SELECT id FROM students) THEN 'student'
                       ELSE 'admin'
                   END as uploader_type,
                   COALESCE(t.first_name, s.first_name, 'Admin') as uploader_name,
                   COALESCE(t.last_name, s.last_name, '') as uploader_lastname
            FROM class_files cf
            LEFT JOIN teachers t ON cf.uploaded_by = t.id AND cf.uploaded_by IN (SELECT id FROM teachers)
            LEFT JOIN students s ON cf.uploaded_by = s.id AND cf.uploaded_by IN (SELECT id FROM students)
            WHERE cf.class_id = ?
        ";
        
        $params = [$class_id];
        
        if ($file_type) {
            $sql .= " AND cf.upload_type = ?";
            $params[] = $file_type;
        }
        
        $sql .= " ORDER BY cf.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getFilesByStudent($student_id) {
        $stmt = $this->pdo->prepare("
            SELECT cf.*, c.name as class_name,
                   CASE 
                       WHEN cf.uploaded_by IN (SELECT id FROM teachers) THEN 'teacher'
                       WHEN cf.uploaded_by IN (SELECT id FROM students) THEN 'student'
                       ELSE 'admin'
                   END as uploader_type,
                   COALESCE(t.first_name, st.first_name, 'Admin') as uploader_name,
                   COALESCE(t.last_name, st.last_name, '') as uploader_lastname
            FROM class_files cf
            INNER JOIN students s ON cf.class_id = s.class_id
            INNER JOIN classes c ON cf.class_id = c.id
            LEFT JOIN teachers t ON cf.uploaded_by = t.id AND cf.uploaded_by IN (SELECT id FROM teachers)
            LEFT JOIN students st ON cf.uploaded_by = st.id AND cf.uploaded_by IN (SELECT id FROM students)
            WHERE s.id = ?
            ORDER BY cf.created_at DESC
        ");
        
        $stmt->execute([$student_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function downloadFile($file_id, $user_id, $user_type) {
        // Verificar permisos
        if (!$this->hasDownloadPermission($file_id, $user_id, $user_type)) {
            return [
                'success' => false,
                'message' => 'No tienes permisos para descargar este archivo'
            ];
        }
        
        // Obtener información del archivo
        $stmt = $this->pdo->prepare("SELECT * FROM class_files WHERE id = ?");
        $stmt->execute([$file_id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$file) {
            return [
                'success' => false,
                'message' => 'Archivo no encontrado'
            ];
        }
        
        $full_path = $this->upload_path . $file['file_path'];
        
        if (!file_exists($full_path)) {
            return [
                'success' => false,
                'message' => 'El archivo no existe en el servidor'
            ];
        }
        
        // Registrar descarga
        $this->logDownload($file_id, $user_id, $user_type);
        
        return [
            'success' => true,
            'file_path' => $full_path,
            'original_name' => $file['original_name'],
            'mime_type' => $this->getMimeType($file['file_type'])
        ];
    }
    
    private function hasDownloadPermission($file_id, $user_id, $user_type) {
        $stmt = $this->pdo->prepare("
            SELECT cf.*, c.teacher_id
            FROM class_files cf
            INNER JOIN classes c ON cf.class_id = c.id
            WHERE cf.id = ?
        ");
        $stmt->execute([$file_id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$file) {
            return false;
        }
        
        switch ($user_type) {
            case 'admin':
                return true;
            case 'teacher':
                // Los profesores pueden descargar archivos de sus clases
                return $file['teacher_id'] == $user_id;
            case 'student':
                // Los estudiantes pueden descargar archivos de sus clases
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(*) as count
                    FROM students s
                    WHERE s.id = ? AND s.class_id = ?
                ");
                $stmt->execute([$user_id, $file['class_id']]);
                return $stmt->fetch()['count'] > 0;
            case 'parent':
                // Los padres pueden descargar archivos de las clases de sus hijos
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(*) as count
                    FROM students s
                    WHERE s.parent_id = ? AND s.class_id = ?
                ");
                $stmt->execute([$user_id, $file['class_id']]);
                return $stmt->fetch()['count'] > 0;
            default:
                return false;
        }
    }
    
    private function logDownload($file_id, $user_id, $user_type) {
        $stmt = $this->pdo->prepare("
            INSERT INTO file_downloads (file_id, user_id, user_type, downloaded_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$file_id, $user_id, $user_type]);
    }
    
    private function getMimeType($file_extension) {
        return $this->allowed_types[$file_extension] ?? 'application/octet-stream';
    }
    
    public function deleteFile($file_id, $user_id, $user_type) {
        // Verificar permisos de eliminación
        if (!$this->hasDeletePermission($file_id, $user_id, $user_type)) {
            return [
                'success' => false,
                'message' => 'No tienes permisos para eliminar este archivo'
            ];
        }
        
        // Obtener información del archivo
        $stmt = $this->pdo->prepare("SELECT * FROM class_files WHERE id = ?");
        $stmt->execute([$file_id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$file) {
            return [
                'success' => false,
                'message' => 'Archivo no encontrado'
            ];
        }
        
        // Eliminar archivo físico
        $full_path = $this->upload_path . $file['file_path'];
        if (file_exists($full_path)) {
            unlink($full_path);
        }
        
        // Eliminar registro de base de datos
        $stmt = $this->pdo->prepare("DELETE FROM class_files WHERE id = ?");
        $stmt->execute([$file_id]);
        
        return [
            'success' => true,
            'message' => 'Archivo eliminado correctamente'
        ];
    }
    
    private function hasDeletePermission($file_id, $user_id, $user_type) {
        $stmt = $this->pdo->prepare("SELECT * FROM class_files WHERE id = ?");
        $stmt->execute([$file_id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$file) {
            return false;
        }
        
        // Administradores pueden eliminar cualquier archivo
        if ($user_type === 'admin') {
            return true;
        }
        
        // Los usuarios solo pueden eliminar sus propios archivos
        return $file['uploaded_by'] == $user_id;
    }
    
    public function getFileStats($class_id = null) {
        $sql = "
            SELECT 
                upload_type,
                COUNT(*) as file_count,
                SUM(file_size) as total_size
            FROM class_files
        ";
        
        $params = [];
        if ($class_id) {
            $sql .= " WHERE class_id = ?";
            $params[] = $class_id;
        }
        
        $sql .= " GROUP BY upload_type";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>