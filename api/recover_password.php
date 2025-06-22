<?php
/**
 * API mejorada para recuperar contraseña
 */

// Habilitar registro de errores
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Establecer zona horaria a Madrid/España
date_default_timezone_set('Europe/Madrid');

// Configuración de cabeceras CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Si es una solicitud OPTIONS, terminar aquí (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Crear directorio de logs si no existe
$log_dir = __DIR__ . '/../logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0777, true);
}

// Función para registrar mensajes de depuración
function debug_log($message) {
    $log_file = __DIR__ . '/../logs/debug.log';
    $dateTime = new DateTime('now', new DateTimeZone('Europe/Madrid'));
    $timestamp = $dateTime->format('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Función para generar una contraseña aleatoria
function generarPassword($longitud = 10) {
    $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
    $password = '';
    $max = strlen($caracteres) - 1;
    
    for ($i = 0; $i < $longitud; $i++) {
        $password .= $caracteres[random_int(0, $max)];
    }
    
    return $password;
}

// Función para enviar correo con SendGrid usando cURL
function enviarPasswordPorCorreo($email, $password) {
    debug_log("Preparando envío de correo a: $email");
    
    // IMPORTANTE: Reemplazar con tu API key de SendGrid
    $api_key = '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';
    $url = 'https://api.sendgrid.com/v3/mail/send';
    
    // Contenido del correo
    $contenido_texto = "Su nueva contraseña temporal es: $password\n\n";
    $contenido_texto .= "Por favor, cambie esta contraseña después de iniciar sesión por motivos de seguridad.";
    
    $contenido_html = "<h2>Recuperación de Contraseña</h2>";
    $contenido_html .= "<p>Su nueva contraseña temporal es: <strong>$password</strong></p>";
    $contenido_html .= "<p>Por favor, cambie esta contraseña después de iniciar sesión por motivos de seguridad.</p>";
    
    // Crear el cuerpo de la solicitud
    $data = [
        'personalizations' => [
            [
                'to' => [
                    ['email' => $email, 'name' => 'Usuario']
                ],
                'subject' => "Recuperación de Contraseña - " . date("Y-m-d H:i:s")
            ]
        ],
        'from' => [
            'email' => 'shmarte@gmail.com', // Reemplazar con tu correo verificado en SendGrid
            'name' => 'Sistema de Recuperación de Contraseña'
        ],
        'content' => [
            [
                'type' => 'text/plain',
                'value' => $contenido_texto
            ],
            [
                'type' => 'text/html',
                'value' => $contenido_html
            ]
        ]
    ];
    
    // Convertir a JSON
    $json_data = json_encode($data);
    debug_log("Datos JSON para SendGrid: " . $json_data);
    
    // Configurar cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);
    
    // Configuración SSL - Intenta primero con verificación SSL habilitada
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    // Timeout
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Ejecutar cURL
    debug_log("Enviando solicitud a SendGrid con SSL...");
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    // Si hay error SSL, intentar de nuevo sin verificación SSL
    if ($error && (strpos($error, 'SSL') !== false || strpos($error, 'certificate') !== false)) {
        debug_log("Error SSL detectado, intentando sin verificación SSL: " . $error);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
    }
    
    debug_log("Respuesta de SendGrid: Código $status_code, Error: $error, Respuesta: $response");
    
    curl_close($ch);
    
    return [
        'success' => $status_code >= 200 && $status_code < 300,
        'status_code' => $status_code,
        'message' => $response ?: $error
    ];
}

// Registrar inicio de la solicitud
debug_log("=== NUEVA SOLICITUD DE RECUPERACIÓN ===");
debug_log("Método HTTP: " . $_SERVER['REQUEST_METHOD']);
debug_log("User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'No disponible'));

// Incluir archivos necesarios
try {
    require_once __DIR__ . '/../config/conexion.php';
    debug_log("Archivo de conexión incluido correctamente");
} catch (Exception $e) {
    debug_log("Error al incluir archivo de conexión: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error interno del servidor al cargar dependencias'
    ]);
    exit;
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    debug_log("Procesando solicitud POST");
    
    // Obtener el correo electrónico
    try {
        $input = file_get_contents("php://input");
        debug_log("Datos recibidos: " . $input);
        
        // Verificar si los datos son JSON válido
        $data = json_decode($input);
        if (json_last_error() !== JSON_ERROR_NONE) {
            debug_log("Error al decodificar JSON: " . json_last_error_msg());
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Formato de datos inválido: ' . json_last_error_msg()
            ]);
            exit;
        }
    } catch (Exception $e) {
        debug_log("Error al leer datos de entrada: " . $e->getMessage());
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al leer datos de entrada'
        ]);
        exit;
    }
    
    // Verificar si se proporcionó un email
    if (empty($data->email)) {
        debug_log("No se proporcionó un correo electrónico");
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'No se proporcionó un correo electrónico'
        ]);
        exit;
    }
    
    debug_log("Email recibido: " . $data->email);
    
    try {
        // Inicializar la conexión a la base de datos
        $database = new Conexion();
        debug_log("Conexión a la base de datos inicializada");
        
        // Verificar si el email existe en la base de datos
        $sql = "SELECT idUser, emailUser FROM agusers WHERE emailUser = ?";
        debug_log("Verificando si el email existe: " . $sql);
        
        $user = $database->getRow($sql, [$data->email]);
        
        if (!$user) {
            debug_log("El email no existe en la base de datos");
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'message' => 'Este correo electrónico no está registrado en nuestro sistema.'
            ]);
            exit;
        }
        
        debug_log("Usuario encontrado: ID=" . $user['idUser']);
        
        // Generar nueva contraseña
        $newPassword = generarPassword(10);
        debug_log("Nueva contraseña generada");
        
        // Hashear la contraseña para almacenarla en la base de datos
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        debug_log("Contraseña hasheada");
        
        // Actualizar la contraseña del usuario
        $updateSql = "UPDATE agusers SET passwordUser = ? WHERE idUser = ?";
        debug_log("Actualizando contraseña: " . $updateSql);
        
        $result = $database->query($updateSql, [$hashedPassword, $user['idUser']]);
        debug_log("Resultado de la actualización: " . ($result ? "Éxito" : "Error"));
        
        // Verificar si la actualización fue exitosa
        if (!$result) {
            debug_log("¡ERROR! No se pudo actualizar la contraseña del usuario");
            throw new Exception("Error al actualizar la contraseña");
        }
        
        $checkSql = "SELECT passwordUser FROM agusers WHERE idUser = ?";
        $updatedUser = $database->getRow($checkSql, [$user['idUser']]);
        if ($updatedUser && password_verify($newPassword, $updatedUser['passwordUser'])) {
            debug_log("Verificación exitosa: la contraseña se actualizó correctamente");
        } else {
            debug_log("¡ERROR! La verificación de la contraseña actualizada falló");
        }
        
        $database->query($updateSql, [$hashedPassword, $user['idUser']]);
        debug_log("Contraseña actualizada en la base de datos");
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        // Registrar la solicitud de recuperación
        $currentDate = date('Y-m-d H:i:s');
        $sql = "INSERT INTO agrecuperacion (emailrecuperacion, daterecuperacion, passwordrecover, is_temporary) VALUES (?, ?, ?, 1)";
        debug_log("Registrando solicitud: " . $sql);
        
        $stmt = $database->query($sql, [$data->email, $currentDate, $hashedPassword]);
        $recoveryId = $database->getConnection()->lastInsertId();
        debug_log("Solicitud registrada: ID=" . ($recoveryId ? $recoveryId : 'Error'));
        
        // Enviar la nueva contraseña por correo electrónico
        $emailResult = enviarPasswordPorCorreo($data->email, $newPassword);
        debug_log("Resultado del envío de correo: " . json_encode($emailResult));
        
        if ($emailResult['success']) {
            // Éxito al enviar el correo
            http_response_code(200);
            $response = [
                'status' => 'success',
                'message' => 'Se ha enviado una nueva contraseña a tu correo electrónico. Revise la carpeta spam',
                'id' => $recoveryId
            ];
            debug_log("Respuesta de éxito: " . json_encode($response));
            echo json_encode($response);
        } else {
            // Error al enviar el correo, pero la contraseña se actualizó
            http_response_code(200);
            $response = [
                'status' => 'warning',
                'message' => 'Se ha generado una nueva contraseña, pero hubo un problema al enviar el correo. Por favor, contacta con soporte.',
                'id' => $recoveryId
            ];
            debug_log("Respuesta de advertencia: " . json_encode($response));
            echo json_encode($response);
        }
    } catch (Exception $e) {
        // Error al procesar la solicitud
        $errorMsg = $e->getMessage();
        debug_log("Excepción: " . $errorMsg);
        
        http_response_code(500);
        $response = [
            'status' => 'error',
            'message' => 'Error al procesar la solicitud: ' . $errorMsg
        ];
        debug_log("Respuesta de error (excepción): " . json_encode($response));
        echo json_encode($response);
    }
} else {
    // Método no permitido
    http_response_code(405);
    $response = [
        'status' => 'error',
        'message' => 'Método no permitido'
    ];
    debug_log("Método no permitido: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode($response);
}

debug_log("Fin de la solicitud");
?>