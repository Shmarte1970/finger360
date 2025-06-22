<?php
// session_cleanup.php - Script para limpiar sesiones inactivas
// Se puede ejecutar manualmente o mediante un cron job

// Configuración - Ajusta según tus necesidades
$max_lifetime = 120; // 30 minutos en segundos
$session_path = session_save_path();

// Iniciar contador de sesiones eliminadas
$count_deleted = 0;
$count_total = 0;

// Obtener la lista de archivos de sesión
$session_files = glob($session_path . "/sess_*");

// Iterar sobre los archivos de sesión
foreach ($session_files as $file) {
    $count_total++;
    
    // Obtener última modificación del archivo
    $last_modified = filemtime($file);
    
    // Si el archivo no se ha modificado en el tiempo máximo, eliminarlo
    if ((time() - $last_modified) > $max_lifetime) {
        if (unlink($file)) {
            $count_deleted++;
        }
    }
}

// Mostrar resultados
echo "<html><body>";
echo "<h1>Limpieza de Sesiones</h1>";
echo "<p>Total de sesiones: $count_total</p>";
echo "<p>Sesiones eliminadas: $count_deleted</p>";
echo "<p>Ruta de sesiones: " . htmlspecialchars($session_path) . "</p>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
echo "</body></html>";

// También registrar en log
error_log("Limpieza de sesiones: $count_deleted/$count_total sesiones eliminadas");