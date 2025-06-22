<?php
// logout_secure.php - Cierre de sesión seguro mejorado

// Iniciar sesión (para poder cerrarla)
session_start();

// Guardar algunos datos para el log
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'unknown';
$session_id = session_id();
$is_auto = isset($_GET['auto']) && $_GET['auto'] == '1';
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
$is_beacon = isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] == '*/*';

// 1. Limpiar todas las variables de sesión
$_SESSION = array();

// 2. Eliminar la cookie de sesión si existe
if (isset($_COOKIE[session_name()])) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Destruir la sesión
session_destroy();

// 4. Registrar en log con información adicional
$log_message = "Sesión cerrada: User=$user_id, Session=$session_id, ";
$log_message .= $is_auto ? "Automático" : "Manual";
$log_message .= ", Método: " . ($_SERVER['REQUEST_METHOD'] ?? 'Unknown');
$log_message .= ", Tipo: " . ($is_ajax ? "AJAX" : ($is_beacon ? "Beacon" : "Normal"));
error_log($log_message);

// 5. Responder según el tipo de solicitud
if ($is_ajax || $is_beacon || $is_auto) {
    // Para solicitudes AJAX, Beacon o automáticas, devolver JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Sesión cerrada correctamente',
        'auto' => $is_auto
    ]);
} else {
    // Para solicitudes normales, redirigir a la página de login
    header("Location: index.php?msg=" . urlencode("Ha cerrado sesión de forma segura."));
}
exit;