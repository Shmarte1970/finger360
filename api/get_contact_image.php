<?php
// Configurar manejo de errores
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('No autorizado');
}

// Obtener el ID del usuario de la sesión
$userId = $_SESSION['user_id'];

// Incluir conexión
require_once '../config/conexion.php';
$conexion = new Conexion();

// Obtener parámetros
$contactId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$imageField = isset($_GET['field']) ? $_GET['field'] : '';

if (!$contactId || !in_array($imageField, ['foto1', 'foto2', 'foto3'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('Parámetros inválidos');
}

// Verificar si el contacto pertenece al usuario
$sql = "SELECT * FROM agcontactos WHERE idContacto = :idContacto AND idUser = :idUser";
$contacto = $conexion->getRow($sql, [':idContacto' => $contactId, ':idUser' => $userId]);

if (!$contacto) {
    header('HTTP/1.1 404 Not Found');
    exit('Contacto no encontrado');
}

// Obtener la ruta de la imagen
$imagePath = $contacto[$imageField];

if (empty($imagePath)) {
    // Si no hay imagen, redirigir a la imagen de placeholder
    header('Location: ../assets/img/placeholder.jpg');
    exit;
}

// Construir la ruta completa al archivo
$fullPath = __DIR__ . '/../' . $imagePath;

// Verificar si el archivo existe
if (!file_exists($fullPath)) {
    header('HTTP/1.1 404 Not Found');
    exit('Archivo de imagen no encontrado');
}

// Obtener el tipo MIME
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $fullPath);
finfo_close($finfo);

// Enviar la imagen con cabeceras para evitar caché
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1
header('Pragma: no-cache'); // HTTP 1.0
header('Expires: 0'); // Proxies
readfile($fullPath);
exit;
?>