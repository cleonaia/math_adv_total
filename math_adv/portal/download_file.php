<?php
// Math Advantage - File Download Handler
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('HTTP/1.0 403 Forbidden');
    exit('Acceso denegado');
}

// Incluir dependencias
require_once 'auth.php';
require_once '../php/classes/FileManager.php';

// Verificar que se ha proporcionado un ID de archivo
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.0 400 Bad Request');
    exit('ID de archivo inválido');
}

$file_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

$fileManager = new FileManager();

// Intentar descargar el archivo
$result = $fileManager->downloadFile($file_id, $user_id, $user_type);

if (!$result['success']) {
    header('HTTP/1.0 404 Not Found');
    exit($result['message']);
}

// Preparar la descarga
$file_path = $result['file_path'];
$original_name = $result['original_name'];
$mime_type = $result['mime_type'];

// Verificar que el archivo existe
if (!file_exists($file_path)) {
    header('HTTP/1.0 404 Not Found');
    exit('Archivo no encontrado en el servidor');
}

// Headers para la descarga
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . $original_name . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Expires: 0');

// Leer y enviar el archivo
readfile($file_path);
exit;
?>