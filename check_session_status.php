<?php
// check_session_status.php
// Verifica si la sesión sigue activa y si hay navegadores conectados

// Iniciar sesión
session_start();

// Verificar si es una solicitud AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
if (!$is_ajax) {
    header('HTTP/1.1 403 Forbidden');
    exit('Acceso prohibido');
}

// Verificar si el usuario está autenticado
$is_authenticated = isset($_SESSION['user_id']);

// Verificar si hay navegadores activos
$active_browsers = 0;
$last_activity = 0;

if ($is_authenticated && isset($_SESSION['heartbeats'])) {
    // Limpiar heartbeats antiguos (más de 30 segundos sin actualizar)
    $timeout = time() - 30;
    foreach ($_SESSION['heartbeats'] as $id => $data) {
        if ($data['last_seen'] < $timeout) {
            unset($_SESSION['heartbeats'][$id]);
        }
    }
    
    $active_browsers = count($_SESSION['heartbeats']);
}

if ($is_authenticated && isset($_SESSION['last_activity'])) {
    $last_activity = $_SESSION['last_activity'];
}

// Verificar si la sesión ha expirado por inactividad
$session_expired = false;
if ($last_activity > 0) {
    $session_timeout = 30 * 60; // 30 minutos
    $session_expired = (time() - $last_activity) > $session_timeout;
}

// Responder con el estado de la sesión
header('Content-Type: application/json');
echo json_encode([
    'active' => $is_authenticated && !$session_expired,
    'authenticated' => $is_authenticated,
    'expired' => $session_expired,
    'active_browsers' => $active_browsers,
    'last_activity' => $last_activity,
    'current_time' => time()
]);