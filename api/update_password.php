<?php
ini_set('display_errors', 1); // Cambiar a 1 para ver errores
error_reporting(E_ALL);

session_start();

// Incluir archivos necesarios
require_once '../config/conexion.php';

// Configuración de cabeceras CORS
if (function_exists('setCorsHeaders')) {
    setCorsHeaders();
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado. Debe iniciar sesión primero.'
    ]);
    exit;
}

// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del cuerpo de la solicitud
    $data = json_decode(file_get_contents("php://input"));
    
    // Verificar que se envió la nueva contraseña
    if (!empty($data->newPassword)) {
        $newPassword = $data->newPassword;
        $email = $_SESSION['user_email'];
        
        // Validar la contraseña (12 caracteres, letras, números y símbolos @ o #)
        if (
            strlen($newPassword) < 12 || 
            !preg_match('/[A-Za-z]/', $newPassword) || 
            !preg_match('/[0-9]/', $newPassword) ||
            !preg_match('/[@#]/', $newPassword)
        ) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'La contraseña debe tener al menos 12 caracteres y contener letras, números y al menos uno de estos símbolos: @ #'
            ]);
            exit;
        }
        
        // Inicializar la conexión a la base de datos
        $database = new Conexion();
        
        // Cifrar la nueva contraseña
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        try {
            // Actualizar la contraseña en agusers
            $updateUserSql = "UPDATE agusers SET passwordUser = ? WHERE emailUser = ?";
            $updateUserStmt = $database->query($updateUserSql, [$hashedPassword, $email]);
            $updateUserResult = $updateUserStmt->rowCount() > 0;
            
            // Actualizar is_temporary en agrecuperacion
            $updateRecoverySql = "UPDATE agrecuperacion SET is_temporary = 0 WHERE emailrecuperacion = ? ORDER BY daterecuperacion DESC LIMIT 1";
            $updateRecoveryStmt = $database->query($updateRecoverySql, [$email]);
            $updateRecoveryResult = $updateRecoveryStmt->rowCount() > 0;
            
            if ($updateUserResult) {
                // Eliminar la marca de requerimiento de cambio de contraseña
                unset($_SESSION['require_password_change']);
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Contraseña actualizada correctamente'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al actualizar la contraseña del usuario'
                ]);
            }
        } catch (Exception $e) {
            error_log("Error en la consulta: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No se proporcionó una nueva contraseña'
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}