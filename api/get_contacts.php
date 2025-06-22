<?php
// Iniciar sesión
session_start();

// Configurar cabeceras CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Cabeceras para evitar caché
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1
header('Pragma: no-cache'); // HTTP 1.0
header('Expires: 0'); // Proxies

// Si es una solicitud OPTIONS, terminar aquí (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Añadir depuración adicional
error_log("get_contacts.php - Inicio del script");
error_log("get_contacts.php - Método: " . $_SERVER['REQUEST_METHOD']);
error_log("Sesión actual: " . print_r($_SESSION, true));

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    // Log de error
    error_log("Error: Usuario no autenticado");
    
    // Responder con error
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Añadir más logs para depuración
error_log("Usuario autenticado con ID: " . $_SESSION['user_id']);

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
        // SOLUCIÓN: Usar diferentes marcadores de parámetros para cada condición LIKE
        $sql = "SELECT idContacto, idUser, nomContacto, telefonoContacto, creadoContacto, enable, adress, sexo, foto1, foto2, foto3 FROM agcontactos WHERE idUser = :user_id AND 
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
        // Consulta sin filtro - Incluir campos de imágenes (foto1, foto2, foto3)
        $sql = "SELECT idContacto, idUser, nomContacto, telefonoContacto, creadoContacto, enable, adress, sexo, foto1, foto2, foto3 FROM agcontactos WHERE idUser = :user_id ORDER BY nomContacto ASC";
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
    
    // Crear respuesta con timestamp para evitar caché
    $response = [
        'success' => true,
        'contacts' => $contacts,
        'timestamp' => time() // Añadir timestamp para evitar caché
    ];
    
    // Convertir a JSON con opciones para manejar caracteres especiales
    $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    // Verificar si hubo error en la codificación JSON
    if ($jsonResponse === false) {
        error_log("Error en json_encode: " . json_last_error_msg());
        echo json_encode([
            'success' => false,
            'message' => 'Error al codificar la respuesta: ' . json_last_error_msg()
        ]);
    } else {
        // Responder con los contactos
        error_log("Respuesta JSON generada correctamente");
        echo $jsonResponse;
    }
    
} catch (PDOException $e) {
    // Registrar el error
    error_log('Error de PDO al obtener contactos: ' . $e->getMessage());
    
    // Responder con error
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Capturar cualquier otro error
    error_log('Error general al obtener contactos: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
} finally {
    // Asegurarse de que siempre se envíe una respuesta JSON válida
    error_log("Finalizando script get_contacts.php");
}