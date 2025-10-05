<?php
// Math Advantage - File Deletion Handler
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Incluir dependencias
require_once 'auth.php';
require_once '../php/classes/FileManager.php';

// Verificar que se ha proporcionado un ID de archivo
if (!isset($_POST['file_id']) || !is_numeric($_POST['file_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de archivo inválido']);
    exit;
}

$file_id = (int)$_POST['file_id'];
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

$fileManager = new FileManager();

// Intentar eliminar el archivo
$result = $fileManager->deleteFile($file_id, $user_id, $user_type);

// Enviar respuesta JSON
header('Content-Type: application/json');
echo json_encode($result);
exit;
?>