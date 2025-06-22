<?php
// cleanup_sessions.php
// Script para limpiar sesiones inactivas y cerrar sesiones de navegadores cerrados
// Este script debe ejecutarse periódicamente mediante un cron job o tarea programada

// Configuración
$session_dir = session_save_path();
if (empty($session_dir)) {
    $session_dir = sys_get_temp_dir();
}

// Función para leer datos de sesión
function read_session_data($session_file) {
    $session_data = file_get_contents($session_file);
    if ($session_data === false) {
        return false;
    }
    
    // Intentar deserializar los datos de sesión
    $session_data = preg_replace_callback(
        '!s:(\d+):"(.*?)";!',
        function($match) {
            return ($match[1] == strlen($match[2])) ? $match[0] : 's:' . strlen($match[2]) . ':"' . $match[2] . '";';
        },
        $session_data
    );
    
    $session_data = @unserialize($session_data);
    return $session_data;
}

// Función para cerrar una sesión
function close_session($session_id, $user_id) {
    // Registrar en el log
    error_log("Cerrando automáticamente la sesión: $session_id para usuario: $user_id");
    
    // Iniciar la sesión con el ID específico
    session_id($session_id);
    session_start();
    
    // Limpiar variables de sesión
    $_SESSION = array();
    
    // Destruir la sesión
    session_destroy();
}

// Escanear el directorio de sesiones
$count_total = 0;
$count_closed = 0;

if (is_dir($session_dir)) {
    $files = glob($session_dir . '/sess_*');
    
    foreach ($files as $file) {
        $count_total++;
        $session_id = basename($file, 'sess_');
        
        // Leer datos de sesión
        $session_data = read_session_data($file);
        
        if ($session_data && isset($session_data['user_id']) && isset($session_data['heartbeats'])) {
            $user_id = $session_data['user_id'];
            $heartbeats = $session_data['heartbeats'];
            
            // Si no hay heartbeats activos, cerrar la sesión
            if (empty($heartbeats)) {
                close_session($session_id, $user_id);
                $count_closed++;
            } else {
                // Verificar si hay heartbeats antiguos
                $has_active_browsers = false;
                $timeout = time() - 30; // 30 segundos
                
                foreach ($heartbeats as $browser_id => $data) {
                    if (isset($data['last_seen']) && $data['last_seen'] > $timeout) {
                        $has_active_browsers = true;
                        break;
                    }
                }
                
                // Si no hay navegadores activos, cerrar la sesión
                if (!$has_active_browsers) {
                    close_session($session_id, $user_id);
                    $count_closed++;
                }
            }
        }
    }
}

// Mostrar resultados
echo "Limpieza de sesiones completada.\n";
echo "Total de sesiones escaneadas: $count_total\n";
echo "Sesiones cerradas automáticamente: $count_closed\n";

// También registrar en el log
error_log("Limpieza de sesiones completada. Total: $count_total, Cerradas: $count_closed");