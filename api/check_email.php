<?php
/**
 * API para verificar si un correo electrónico ya existe
 */

// Incluir archivos necesarios
require_once '../config/conexion.php';

// Configuración de cabeceras CORS
setCorsHeaders();

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener el correo electrónico
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->email)) {
        // Inicializar la conexión a la base de datos
        $database = new Conexion();
        
        // Verificar si el email ya existe
        $sql = "SELECT COUNT(*) as count FROM agusers WHERE emailUser = ?";
        $result = $database->getRow($sql, [$data->email]);
        
        // Preparar respuesta
        if ($result['count'] > 0) {
            // El email ya existe
            echo json_encode([
                'status' => 'error',
                'exists' => true,
                'message' => 'El correo electrónico ya está registrado'
            ]);
        } else {
            // El email no existe
            echo json_encode([
                'status' => 'success',
                'exists' => false,
                'message' => 'El correo electrónico está disponible'
            ]);
        }
    } else {
        // No se proporcionó un correo electrónico
        echo json_encode([
            'status' => 'error',
            'message' => 'No se proporcionó un correo electrónico'
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