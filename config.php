<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'agent');
define('DB_USER', 'admin');
define('DB_PASS', 'admin2023');
define('DB_CHARSET', 'utf8mb4');

// Configuración de JWT
define('JWT_SECRET', 'your_secret_key_here'); // Cambiar en producción
define('JWT_EXPIRATION', 3600); // 1 hora en segundos

// Configuración de la aplicación
define('APP_DEBUG', true);
define('APP_URL', 'http://localhost/agent');

// Función para configurar cabeceras CORS para API
function setCorsHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization, X-Requested-With');
    header('Content-Type: application/json');
    
    // Si es una solicitud OPTIONS, terminar aquí (preflight CORS)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }
}