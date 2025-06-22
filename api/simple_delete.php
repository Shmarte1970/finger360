<?php
// Archivo: api/simple_delete.php
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];

// Incluir conexión
require_once '../config/conexion.php';
$conexion = new Conexion();

// Obtener datos
$data = json_decode(file_get_contents('php://input'), true);
$contactId = null;
if (isset($data['idContacto'])) {
    $contactId = (int)$data['idContacto'];
} elseif (isset($_POST['idContacto'])) {
    $contactId = (int)$_POST['idContacto'];
} elseif (isset($_POST['id'])) {
    $contactId = (int)$_POST['id'];
}

error_log("ID recibido: $contactId, Usuario: $userId");

if (!$contactId) {
    echo json_encode(['success' => false, 'message' => 'ID de contacto requerido']);
    exit;
}

// Ejecutar consulta directa
try {
    // Consulta simplificada - ajusta el nombre de la columna según tu base de datos
    $stmt = $conexion->getConnection()->prepare("DELETE FROM agcontactos WHERE idContacto = ? AND idUser = ?");
    $result = $stmt->execute([$contactId, $userId]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Contacto eliminado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró el contacto o no tiene permisos']);
    }
} catch (Exception $e) {
    error_log("Error al eliminar: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>