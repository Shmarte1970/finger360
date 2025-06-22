<?php
// session_heartbeat.php
// Sistema mejorado para mantener la sesión activa y detectar cuando el navegador se cierra

// Iniciar sesión
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'No hay sesión activa'
    ]);
    exit;
}

// Verificar si es una solicitud POST con datos JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del heartbeat
    $input = json_decode(file_get_contents('php://input'), true);
    $timestamp = isset($input['timestamp']) ? $input['timestamp'] : time() * 1000;
    $browser_id = isset($input['browser_id']) ? $input['browser_id'] : 'unknown';
    
    // Almacenar información del heartbeat en la sesión
    if (!isset($_SESSION['heartbeats'])) {
        $_SESSION['heartbeats'] = [];
    }
    
    // Actualizar el heartbeat para este navegador
    $_SESSION['heartbeats'][$browser_id] = [
        'timestamp' => $timestamp,
        'last_seen' => time(),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];
    
    // Limpiar heartbeats antiguos (más de 30 segundos sin actualizar)
    $timeout = time() - 30;
    foreach ($_SESSION['heartbeats'] as $id => $data) {
        if ($data['last_seen'] < $timeout) {
            unset($_SESSION['heartbeats'][$id]);
            
            // Registrar en el log que un navegador ha sido detectado como cerrado
            error_log("Navegador detectado como cerrado: $id para usuario: {$_SESSION['user_id']}");
        }
    }
} else {
    // Para solicitudes GET simples, solo actualizar la marca de tiempo
    if (!isset($_SESSION['heartbeats'])) {
        $_SESSION['heartbeats'] = [];
    }
}

// Actualizar la marca de tiempo de la última actividad
$_SESSION['last_activity'] = time();

// Registrar actividad en log (opcional, útil para depuración)
if (isset($_SESSION['user_id'])) {
    $browser_count = isset($_SESSION['heartbeats']) ? count($_SESSION['heartbeats']) : 0;
    error_log("Heartbeat de sesión para usuario: {$_SESSION['user_id']}, Navegadores activos: $browser_count");
}

// Responder con estado
header('Content-Type: application/json');
echo json_encode([
    'status' => 'active', 
    'timestamp' => time() * 1000,
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'],
    'active_browsers' => isset($_SESSION['heartbeats']) ? count($_SESSION['heartbeats']) : 0
]);