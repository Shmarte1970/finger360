<?php
// session_destroyer.php - Destruye la sesión completamente

// 1. Iniciar la sesión para poder destruirla
session_start();

// 2. Guardar el ID de sesión actual
$session_id = session_id();
$session_name = session_name();

// 3. Registrar lo que estamos haciendo
error_log("Eliminando sesión: {$session_id}");

// 4. Limpiar todas las variables de sesión
$_SESSION = array();

// 5. Eliminar la cookie de sesión de forma forzada
if (isset($_COOKIE[$session_name])) {
    $params = session_get_cookie_params();
    
    // Eliminar la cookie con los mismos parámetros que se usaron para crearla
    setcookie($session_name, '', time() - 86400, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    
    // Asegurarse de que la cookie se elimina inmediatamente en el navegador
    setcookie($session_name, '', time() - 86400, '/');
    
    // Intentar más métodos para eliminar la cookie
    unset($_COOKIE[$session_name]);
    
    error_log("Cookie de sesión eliminada para {$session_name}");
}

// 6. Destruir la sesión
session_destroy();

// 7. Eliminar físicamente el archivo de sesión
$session_path = session_save_path();
$session_file = "{$session_path}/sess_{$session_id}";

if (file_exists($session_file)) {
    unlink($session_file);
    error_log("Archivo de sesión eliminado: {$session_file}");
}

// 8. Forzar la creación de una nueva sesión con nuevo ID
session_start();
session_regenerate_id(true);
session_destroy();

// 9. Redirigir a la página de login con un mensaje claro
header("Location: index.php?msg=" . urlencode("Su sesión ha sido cerrada correctamente. Por favor inicie sesión nuevamente."));
exit;