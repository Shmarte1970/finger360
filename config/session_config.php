<?php
// Asegurarse de que no hay salida antes
if (headers_sent()) {
    error_log("Headers ya enviados antes de configurar la sesión");
    return false;
} 

// Configuración de la sesión antes de iniciarla
session_name('Finger360Session');

// Configurar la cookie
session_set_cookie_params([
    'lifetime' => 0,            // 0 = hasta que se cierre el navegador
    'path' => '/',              // Disponible en todo el sitio
    'domain' => '',             // Dominio actual
    'secure' => false,          // Cambiar a true si usas HTTPS
    'httponly' => true,         // No accesible mediante JavaScript
    'samesite' => 'Lax'         // Protección contra CSRF
]);

// Otras configuraciones importantes
ini_set('session.gc_maxlifetime', 1800);  // 30 minutos
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);    // Protección adicional
ini_set('session.use_trans_sid', 0);      // No propagar el ID de sesión en URLs
ini_set('session.cache_limiter', 'nocache'); // Evitar caché de páginas con sesión

// Iniciar la sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerar el ID de sesión periódicamente para prevenir fijación de sesión
if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration']) > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Función para verificar si la sesión está activa
function is_session_active() {
    return isset($_SESSION) && count($_SESSION) > 0;
}