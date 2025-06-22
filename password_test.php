<?php
/**
 * Script de diagnóstico para verificar contraseñas
 */

// Incluir archivo de conexión
require_once 'config/conexion.php';

// Función para depuración
function print_r_formatted($value) {
    echo "<pre>";
    print_r($value);
    echo "</pre>";
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico de Contraseñas</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background-color: #f5f5f5; padding: 10px; }
    </style>
</head>
<body>";

echo "<h1>Diagnóstico de Contraseñas</h1>";

// Conectar a la base de datos
try {
    $database = new Conexion();
    echo "<p class='success'>Conexión a la base de datos exitosa.</p>";
} catch (Exception $e) {
    echo "<p class='error'>Error de conexión: " . $e->getMessage() . "</p>";
    exit;
}

// Obtener usuario por email
$testEmail = isset($_GET['email']) ? $_GET['email'] : 'riosmacias@gmail.com';
$testPassword = isset($_GET['password']) ? $_GET['password'] : '';

echo "<h2>Buscando usuario: $testEmail</h2>";

$sql = "SELECT * FROM agusers WHERE emailUser = ?";
$user = $database->getRow($sql, [$testEmail]);

if (!$user) {
    echo "<p class='error'>Usuario no encontrado en la base de datos.</p>";
} else {
    echo "<p class='success'>Usuario encontrado.</p>";
    
    echo "<h3>Información del usuario:</h3>";
    echo "<ul>";
    echo "<li>ID: " . $user['idUser'] . "</li>";
    echo "<li>Nombre: " . $user['nomUsuario'] . "</li>";
    echo "<li>Email: " . $user['emailUser'] . "</li>";
    
    // Mostrar hash de contraseña e información relacionada
    echo "<li>Hash almacenado: " . $user['passwordUser'] . "</li>";
    
    $hashInfo = password_get_info($user['passwordUser']);
    echo "<li>Algoritmo: " . ($hashInfo['algoName'] ?: 'No detectado') . "</li>";
    echo "</ul>";
    
    // Si se proporcionó una contraseña para probar
    if (!empty($testPassword)) {
        echo "<h3>Prueba de verificación:</h3>";
        
        $verified = password_verify($testPassword, $user['passwordUser']);
        
        if ($verified) {
            echo "<p class='success'>¡La contraseña es correcta!</p>";
        } else {
            echo "<p class='error'>La contraseña no es correcta.</p>";
            
            // Crear un nuevo hash para mostrar cómo debería ser
            $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
            echo "<p>Hash que se generaría para '$testPassword': $newHash</p>";
            
            // Comprobar si el hash almacenado tiene un formato válido
            if (!empty($hashInfo['algoName'])) {
                echo "<p>El hash almacenado parece tener un formato correcto.</p>";
            } else {
                echo "<p class='error'>El hash almacenado no tiene un formato reconocible por PHP.</p>";
                
                // Actualizar la contraseña con un hash correcto
                echo "<h3>¿Quieres actualizar la contraseña?</h3>";
                echo "<form method='post'>";
                echo "<input type='hidden' name='email' value='$testEmail'>";
                echo "<input type='hidden' name='password' value='$testPassword'>";
                echo "<button type='submit' name='update' style='padding: 5px 10px;'>Actualizar contraseña</button>";
                echo "</form>";
            }
        }
    }
    
    // Formulario para probar con una contraseña
    echo "<h3>Probar con una contraseña:</h3>";
    echo "<form method='get'>";
    echo "<input type='hidden' name='email' value='$testEmail'>";
    echo "Contraseña: <input type='text' name='password' value='$testPassword'> ";
    echo "<button type='submit' style='padding: 5px 10px;'>Verificar</button>";
    echo "</form>";
    
    // Formulario para actualizar contraseña
    echo "<h3>Actualizar contraseña:</h3>";
    echo "<form method='post'>";
    echo "Email: <input type='text' name='email' value='$testEmail'><br><br>";
    echo "Nueva contraseña: <input type='text' name='new_password' value=''><br><br>";
    echo "<button type='submit' name='update_password' style='padding: 5px 10px;'>Actualizar contraseña</button>";
    echo "</form>";
}

// Procesar actualización de contraseña
if (isset($_POST['update_password'])) {
    $email = $_POST['email'];
    $newPassword = $_POST['new_password'];
    
    // Verificar que el usuario existe
    $sql = "SELECT idUser FROM agusers WHERE emailUser = ?";
    $updateUser = $database->getRow($sql, [$email]);
    
    if ($updateUser) {
        // Crear hash
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Actualizar
        $updateSql = "UPDATE agusers SET passwordUser = ? WHERE idUser = ?";
        $stmt = $database->query($updateSql, [$hashedPassword, $updateUser['idUser']]);
        
        echo "<p class='success'>Contraseña actualizada. El nuevo hash es: $hashedPassword</p>";
        echo "<p>Puedes probar iniciar sesión con la nueva contraseña ahora.</p>";
    } else {
        echo "<p class='error'>No se encontró el usuario para actualizar.</p>";
    }
}

// Si se solicita actualizar con el mismo password
if (isset($_POST['update']) && isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Verificar que el usuario existe
    $sql = "SELECT idUser FROM agusers WHERE emailUser = ?";
    $updateUser = $database->getRow($sql, [$email]);
    
    if ($updateUser) {
        // Crear hash correcto
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Actualizar
        $updateSql = "UPDATE agusers SET passwordUser = ? WHERE idUser = ?";
        $stmt = $database->query($updateSql, [$hashedPassword, $updateUser['idUser']]);
        
        echo "<p class='success'>Contraseña actualizada con el hash correcto: $hashedPassword</p>";
        echo "<p>Puedes intentar iniciar sesión ahora.</p>";
    } else {
        echo "<p class='error'>No se encontró el usuario para actualizar.</p>";
    }
}

echo "</body></html>";
?>