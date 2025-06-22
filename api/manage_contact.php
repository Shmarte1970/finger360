<?php
// Iniciar sesión
session_start();

// Log para depuración
error_log("manage_contact.php - Método: " . $_SERVER['REQUEST_METHOD']);
error_log("Sesión actual: " . print_r($_SESSION, true));

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    // Log de error
    error_log("Error: Usuario no autenticado");
    
    // Responder con error
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Obtener el ID del usuario de la sesión
$userId = $_SESSION['user_id'];
error_log("ID de usuario: $userId");

// Incluir el archivo de conexión a la base de datos
error_log("Intentando incluir el archivo de conexión: ../config/conexion.php");
try {
    require_once '../config/conexion.php';
    error_log("Archivo de conexión incluido correctamente");
} catch (Exception $e) {
    error_log("Error al incluir el archivo de conexión: " . $e->getMessage());
    
    // Responder con error
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos: ' . $e->getMessage()]);
    exit;
}

// Crear una instancia de la clase Conexion
$conexion = new Conexion();
$conn = $conexion->getConnection();

// Función para validar los datos del contacto
function validateContactData($data) {
    $errors = [];
    
    // Validar nombre del contacto
    if (empty($data['nomContacto'])) {
        $errors[] = "El nombre del contacto es obligatorio";
    }
    
    // Validar teléfono
    if (empty($data['telefonoContacto'])) {
        $errors[] = "El teléfono es obligatorio";
    } elseif (!preg_match('/^[0-9+\s()-]{6,20}$/', $data['telefonoContacto'])) {
        $errors[] = "El formato del teléfono no es válido";
    }
    
    return $errors;
}

// Crear un nuevo contacto (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Procesando solicitud POST para crear contacto");
    
    // Obtener los datos enviados
    $data = json_decode(file_get_contents('php://input'), true);
    error_log("Datos recibidos: " . print_r($data, true));
    
    // Si no hay datos JSON, intentar obtener de POST
    if (empty($data)) {
        $data = $_POST;
        error_log("Datos obtenidos de POST: " . print_r($data, true));
    }
    
    // Verificar si es una solicitud de eliminación (simulada con _method=DELETE)
    if (isset($data['_method']) && $data['_method'] === 'DELETE') {
        // Redirigir al manejador de DELETE
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        // Asegurarse de que el ID esté disponible
        if (isset($_GET['id'])) {
            // Ya tenemos el ID en $_GET, no hacemos nada
        } elseif (isset($data['id'])) {
            $_GET['id'] = $data['id'];
        } elseif (isset($data['idContacto'])) {
            $_GET['id'] = $data['idContacto'];
        }
        // Saltar a la sección de DELETE
        goto delete_handler;
    }
    
    // Validar datos para creación/actualización
    $errors = validateContactData($data);
    
    if (!empty($errors)) {
        error_log("Errores de validación: " . implode(", ", $errors));
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => implode(", ", $errors)]);
        exit;
    }
    
    try {
        // Preparar los datos para insertar
        $contactData = [
            'idUser' => $userId,
            'nomContacto' => trim($data['nomContacto']),
            'telefonoContacto' => trim($data['telefonoContacto']),
            'adress' => isset($data['adress']) ? trim($data['adress']) : '',
            'sexo' => isset($data['sexo']) ? trim($data['sexo']) : '',
            'creadoContacto' => date('Y-m-d H:i:s'),
            'enable' => 1
        ];
        
        // Insertar el contacto
        $contactId = $conexion->insert('agcontactos', $contactData);
        
        if ($contactId) {
            error_log("Contacto creado con ID: $contactId");
            
            // Obtener el contacto recién creado
            $sql = "SELECT * FROM agcontactos WHERE idContacto = :idContacto";
            $newContact = $conexion->getRow($sql, [':idContacto' => $contactId]);
            
            // Responder con éxito
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Contacto creado correctamente',
                'contact' => $newContact
            ]);
        } else {
            error_log("Error al crear el contacto");
            
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No se pudo crear el contacto']);
        }
    } catch (PDOException $e) {
        error_log("Error de PDO al crear contacto: " . $e->getMessage());
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Error general al crear contacto: " . $e->getMessage());
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
    }
    exit;
}

// Actualizar un contacto existente (PUT)
if ($_SERVER['REQUEST_METHOD'] === 'PUT' || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'PUT')) {
    error_log("Procesando solicitud para actualizar contacto");
    
    // Obtener los datos enviados
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Si no hay datos JSON, intentar obtener de POST
    if (empty($data)) {
        $data = $_POST;
    }
    
    error_log("Datos recibidos: " . print_r($data, true));
    
    // Verificar que se proporcionó un ID de contacto
    if (!isset($data['id']) || empty($data['id'])) {
        error_log("Error: No se proporcionó ID de contacto");
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Se requiere ID de contacto']);
        exit;
    }
    
    $contactId = $data['id'];
    
    // Validar datos
    $errors = validateContactData($data);
    
    if (!empty($errors)) {
        error_log("Errores de validación: " . implode(", ", $errors));
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => implode(", ", $errors)]);
        exit;
    }
    
    try {
        // Verificar que el contacto pertenece al usuario
        $sql = "SELECT COUNT(*) FROM agcontactos WHERE idContacto = :idContacto AND idUser = :user_id";
        $count = $conexion->getRow($sql, [':idContacto' => $contactId, ':user_id' => $userId]);
        
        if (!$count || $count['COUNT(*)'] == 0) {
            error_log("Error: El contacto no pertenece al usuario o no existe");
            
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Contacto no encontrado o no autorizado']);
            exit;
        }
        
        // Preparar los datos para actualizar
        $contactData = [
            'nomContacto' => trim($data['nomContacto']),
            'telefonoContacto' => trim($data['telefonoContacto']),
            'adress' => isset($data['adress']) ? trim($data['adress']) : '',
            'sexo' => isset($data['sexo']) ? trim($data['sexo']) : ''
        ];
        
        // Actualizar el contacto
        $result = $conexion->update('agcontactos', $contactData, 'idContacto = ?', [$contactId]);
        
        if ($result) {
            error_log("Contacto actualizado correctamente");
            
            // Obtener el contacto actualizado
            $sql = "SELECT * FROM agcontactos WHERE idContacto = :idContacto";
            $updatedContact = $conexion->getRow($sql, [':idContacto' => $contactId]);
            
            // Responder con éxito
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Contacto actualizado correctamente',
                'contact' => $updatedContact
            ]);
        } else {
            error_log("No se realizaron cambios en el contacto");
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'No se realizaron cambios']);
        }
    } catch (PDOException $e) {
        error_log("Error de PDO al actualizar contacto: " . $e->getMessage());
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Error general al actualizar contacto: " . $e->getMessage());
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
    }
    exit;
}

// Eliminar un contacto (DELETE)
delete_handler:
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'DELETE')) {
    error_log("Procesando solicitud para eliminar contacto");
    
    // Obtener el ID del contacto
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Si no hay datos JSON, intentar obtener de POST o GET
    if (empty($data)) {
        $data = $_POST;
    }
    
    if (isset($_GET['idContacto'])) {
        $contactId = $_GET['idContacto'];
    } elseif (isset($data['idContacto'])) {
        $contactId = $data['idContacto'];
    } elseif (isset($_GET['id'])) {
        $contactId = $_GET['id'];
    } elseif (isset($data['id'])) {
        $contactId = $data['id'];
    } else {
        error_log("Error: No se proporcionó ID de contacto");
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Se requiere ID de contacto']);
        exit;
    }
    
    error_log("ID de contacto a eliminar: $contactId");
    
    try {
        // Verificar que el contacto pertenece al usuario
        $sql = "SELECT COUNT(*) FROM agcontactos WHERE idContacto = :idContacto AND idUser = :user_id";
        $count = $conexion->getRow($sql, [':idContacto' => $contactId, ':user_id' => $userId]);
        
        if (!$count || $count['COUNT(*)'] == 0) {
            error_log("Error: El contacto no pertenece al usuario o no existe");
            
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Contacto no encontrado o no autorizado']);
            exit;
        }
        
        // Eliminar el contacto
        $result = $conexion->delete('agcontactos', 'idContacto = ?', [$contactId]);
        
        if ($result) {
            error_log("Contacto eliminado correctamente");
            
            // Responder con éxito
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Contacto eliminado correctamente'
            ]);
        } else {
            error_log("Error al eliminar el contacto");
            
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el contacto']);
        }
    } catch (PDOException $e) {
        error_log("Error de PDO al eliminar contacto: " . $e->getMessage());
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Error general al eliminar contacto: " . $e->getMessage());
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
    }
    exit;
}

// Si el método no es válido
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Método no permitido']);
exit;