<?php
// Incluir configuración de sesión
require_once 'config/session_config.php';

// Solo destruir la sesión, sin redirigir
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
echo "Session terminated";