<?php
// Archivo: api/simple_update.php
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

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents('php://input'), true);

// Log de los datos recibidos
error_log("Datos recibidos en simple_update.php: " . print_r($data, true));

// Verificar que tenemos los datos necesarios
if (!isset($data['idContacto']) || !isset($data['nomContacto']) || !isset($data['telefonoContacto'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
    exit;
}

$contactId = $data['idContacto'];
$nombre = $data['nomContacto'];
$telefono = $data['telefonoContacto'];
$direccion = $data['adress'] ?? '';
$sexo = $data['sexo'] ?? '';

// Verificar que el contacto pertenece al usuario
try {
    $stmt = $conexion->getConnection()->prepare("SELECT COUNT(*) FROM agcontactos WHERE idContacto = ? AND idUser = ?");
    $stmt->execute([$contactId, $userId]);
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        echo json_encode(['success' => false, 'message' => 'Contacto no encontrado o no autorizado']);
        exit;
    }
    
    // Actualizar el contacto
    $stmt = $conexion->getConnection()->prepare("UPDATE agcontactos SET nomContacto = ?, telefonoContacto = ?, adress = ?, sexo = ? WHERE idContacto = ? AND idUser = ?");
    $result = $stmt->execute([$nombre, $telefono, $direccion, $sexo, $contactId, $userId]);
    
    if ($result) {
        // Obtener los datos actualizados
        $stmt = $conexion->getConnection()->prepare("SELECT * FROM agcontactos WHERE idContacto = ?");
        $stmt->execute([$contactId]);
        $contacto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Contacto actualizado correctamente',
            'contact' => $contacto
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el contacto']);
    }
} catch (Exception $e) {
    error_log("Error al actualizar contacto: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>