<?php
/**
 * API Endpoint para login de usuarios
 * 
 * Permite autenticar usuarios y devuelve un token JWT
 */

// Incluir archivos necesarios
require_once '../config/conexion.php';

// Configuración de cabeceras CORS
setCorsHeaders();

// Inicializar la conexión a la base de datos
$database = new Conexion();
$db = $database->getConnection();

// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del cuerpo de la solicitud
    $data = json_decode(file_get_contents("php://input"));
    
    // Verificar que todos los campos requeridos estén presentes
    if (!empty($data->emailUser) && !empty($data->passwordUser)) {
        // Buscar usuario por email
        $sql = "SELECT * FROM agusers WHERE emailUser = ?";
        $user = $database->getRow($sql, [$data->emailUser]);
        
        if ($user) {
            // Verificar contraseña
            if (password_verify($data->passwordUser, $user['passwordUser'])) {
                // Verificar si el usuario ha iniciado sesión con una contraseña temporal
                $requirePasswordChange = false;
                
                // Obtener la última contraseña de recuperación para este email
                $recoverySql = "SELECT ar.passwordrecover 
                               FROM agrecuperacion ar
                               WHERE ar.email = ? 
                               ORDER BY ar.fechahora DESC 
                               LIMIT 1";
                $recoveryData = $database->getRow($recoverySql, [$data->emailUser]);
                
                if ($recoveryData && isset($recoveryData['passwordrecover'])) {
                    // Si las contraseñas son iguales, el usuario ha iniciado sesión con una contraseña temporal
                    if ($recoveryData['passwordrecover'] === $user['passwordUser']) {
                        $requirePasswordChange = true;
                    }
                }
                
                // Generar token JWT (en una implementación real)
                // Aquí simplemente devolvemos los datos del usuario
                
                // Preparar respuesta
                $response = [
                    'status' => 'success',
                    'message' => 'Login exitoso',
                    'user' => [
                        'id' => $user['idusers'],
                        'nombre' => $user['nomUsuario'],
                        'email' => $user['emailUser'],
                        'creado' => $user['creado']
                    ],
                    'require_password_change' => $requirePasswordChange,
                    'token' => 'jwt_token_simulado_' . time() // En una implementación real, esto sería un JWT válido
                ];
                
                http_response_code(200);
                echo json_encode($response);
            } else {
                // Contraseña incorrecta
                http_response_code(401);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Credenciales inválidas'
                ]);
            }
        } else {
            // Usuario no encontrado
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'message' => 'Usuario no encontrado'
            ]);
        }
    } else {
        // Datos incompletos
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Datos incompletos. Se requieren email y contraseña'
        ]);
    }
} else {
    // Método no permitido
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Método no permitido'
    ]);
}