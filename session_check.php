<?php
// session_check.php - Verificación de tiempo de inactividad
// Incluir este archivo al principio de dashboard.php después de session_start()

// Configurar tiempo máximo de inactividad (30 minutos)
$max_inactive_time = 120;

// Si existe la variable de última actividad y ha pasado el tiempo máximo
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $max_inactive_time)) {
    // Redirigir al logout seguro
    header("Location: logout_secure.php?reason=inactive");
    exit;
}

// Actualizar tiempo de última actividad
$_SESSION['last_activity'] = time();