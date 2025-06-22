<?php
// Iniciar sesión
session_start();

// Log para depuración
error_log("get_contacts_blob.php - Método: " . $_SERVER['REQUEST_METHOD']);
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

// Obtener el término de búsqueda si existe
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
error_log("Término de búsqueda: " . ($searchTerm ? $searchTerm : 'ninguno'));

try {
    // Preparar la consulta para obtener los contactos del usuario
    if (!empty($searchTerm)) {
        // Usar diferentes marcadores de parámetros para cada condición LIKE
        $sql = "SELECT 
                idContacto, idUser, nomContacto, telefonoContacto, adress, sexo, creadoContacto, enable,
                CASE WHEN foto1 IS NOT NULL THEN 1 ELSE 0 END AS has_foto1,
                CASE WHEN foto2 IS NOT NULL THEN 1 ELSE 0 END AS has_foto2,
                CASE WHEN foto3 IS NOT NULL THEN 1 ELSE 0 END AS has_foto3
                FROM agcontactos 
                WHERE idUser = :user_id AND 
                (LOWER(nomContacto) LIKE LOWER(:search_name) OR 
                 LOWER(telefonoContacto) LIKE LOWER(:search_phone))
                ORDER BY nomContacto ASC";
        
        $searchPattern = "%" . $searchTerm . "%";
        $params = [
            ':user_id' => $userId,
            ':search_name' => $searchPattern,
            ':search_phone' => $searchPattern
        ];
        
        error_log("Consulta de búsqueda: " . $sql);
        error_log("Parámetros: " . print_r($params, true));
    } else {
        // Consulta sin filtro
        $sql = "SELECT 
                idContacto, idUser, nomContacto, telefonoContacto, adress, sexo, creadoContacto, enable,
                CASE WHEN foto1 IS NOT NULL THEN 1 ELSE 0 END AS has_foto1,
                CASE WHEN foto2 IS NOT NULL THEN 1 ELSE 0 END AS has_foto2,
                CASE WHEN foto3 IS NOT NULL THEN 1 ELSE 0 END AS has_foto3
                FROM agcontactos 
                WHERE idUser = :user_id 
                ORDER BY nomContacto ASC";
        $params = [':user_id' => $userId];
    }
    
    error_log("Consulta a ejecutar: " . $sql);
    
    // Ejecutar la consulta
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    // Obtener los resultados
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log para depuración
    error_log("Contactos encontrados: " . count($contacts));
    
    // Responder con los contactos
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'contacts' => $contacts
    ]);
    
} catch (PDOException $e) {
    // Registrar el error
    error_log('Error de PDO al obtener contactos: ' . $e->getMessage());
    
    // Responder con error
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Capturar cualquier otro error
    error_log('Error general al obtener contactos: ' . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
?>