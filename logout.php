<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpiar todas las variables de sesión
$_SESSION = array();

// Obtener los parámetros de la cookie de sesión
$params = session_get_cookie_params();

// Eliminar la cookie de sesión
setcookie(session_name(), '', time() - 42000,
    $params["path"], $params["domain"],
    $params["secure"], $params["httponly"]
);

// Destruir la sesión
session_destroy();

// Verificar si es una solicitud desde la ventana de confirmación
$from_confirm = isset($_GET['from_confirm']) ? true : false;

if ($from_confirm) {
    // Si viene de la ventana de confirmación, mostrar un script que cierre todas las ventanas
    ?>
<!DOCTYPE html>
<html>

<head>
    <title>Cerrando sesión...</title>
    <script>
    // Cerrar la ventana principal si existe
    if (window.opener && !window.opener.closed) {
        window.opener.close();
    }
    // Cerrar esta ventana después de un breve retraso
    setTimeout(function() {
        window.close();
    }, 500);
    // Si no se puede cerrar, redirigir al login
    setTimeout(function() {
        window.location.href = 'index.php';
    }, 1000);
    </script>
</head>

<body>
    <p>Cerrando sesión y ventanas...</p>
</body>

</html>
<?php
    exit;
} else {
    // Redirigir al login normalmente
    header('Location: index.php');
    exit;
}