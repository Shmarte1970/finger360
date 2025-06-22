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

// Obtener la imagen BLOB
$imageBlob = $contacto[$imageField];

if (empty($imageBlob)) {
    header('HTTP/1.1 404 Not Found');
    exit('Imagen no encontrada');
}

// Enviar la imagen
header('Content-Type: image/jpeg');
header('Content-Length: ' . strlen($imageBlob));
header('Cache-Control: max-age=86400'); // Caché por 24 horas
echo $imageBlob;
exit;
?>